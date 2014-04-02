<?php
/**
 * You must loggedin for uploading.
 * This plugin doesn't support transloading.
 */

class ChipVN_ImageUploader_Plugins_Picasa extends ChipVN_ImageUploader_Plugins_Abstract
{

    /**
     * Size of image after uploaded. This calculated by max(with, height).
     *
     * @var	integer
     */
    private $size = NULL;

    /*
     * AlbumId to archive image.
     * Account upload limits:
     *
     * Maximum photo size: Each image can be no larger than 20 megabytes and are restricted to 50 megapixels or less.
     * Maximum video size: Each video uploaded can be no larger than 1GB in size.
     * Maximum number of web albums: 20,000
     * Maximum number of photos and videos per web album: 1,000
     * Total storage space: Picasa Web provides 1 GB for photos and videos. Files under
     *
     * @var	string
     */
    private $albumId = 'default';

    /**
     * {@inheritdoc}
     */
    protected function doLogin()
    {
        // normalize username
        $this->username = preg_replace('#@.*?$#', '', $this->username);

        if (!$this->get('sessionLogin') OR $this->get('loginTime') + 300 < $_SERVER['REQUEST_TIME']) {
            $this->request->reset();
            $this->request->setParameters(array(
                'accountType'   => 'HOSTED_OR_GOOGLE',
                'Email'         => $this->username,
                'Passwd'        => $this->password,
                'source'        => __CLASS__,
                'service'       => 'lh2'
            ));
            $this->request->execute('https://www.google.com/accounts/ClientLogin', 'POST');

            $this->checkRequestErrors(__METHOD__);

            if (preg_match('#Auth=([a-z0-9_\-]+)#i', $this->request->getResponseText(), $match)) {
                $this->set('sessionLogin', $match[1]);
                $this->set('loginTime', $_SERVER['REQUEST_TIME']);

            } else {
                $this->set('sessionLogin', NULL);
                $this->throwException(sprintf('%s: Login failed. %s', __METHOD__, $this->request->getResponseText()));
            }
        }

        return true;
    }

    /**
     * Set size for URL after uploaded. (s<X>)
     *
     * @param integer $size
     */
    public function setSize($size)
    {
        $this->size = $size;
    }

    /**
     * Set AlbumID.
     * You can set AlbumId by an array, then method will get random an id
     *
     * @param string|array
     */
    public function setAlbumId($albumIds)
    {
        $albumIds = (array) $albumIds;

        $this->albumId = $albumIds[array_rand($albumIds, 1)];
    }

    /**
     * {@inheritdoc}
     */
    protected function doUpload()
    {
        $this->checkPermission(__METHOD__);

        $this->request->reset();
        $this->request->setSubmitMultipart('related');
        $this->request->setHeaders(array(
            "Authorization: GoogleLogin auth=" . $this->get('sessionLogin'),
            "MIME-Version: 1.0",
        ));
        $this->request->setRawPost("Content-Type: application/atom+xml\r\n
            <entry xmlns='http://www.w3.org/2005/Atom'>
            <title>" . preg_replace('#\..*?$#i', '', basename($this->file)) . "</title>
            <category scheme=\"http://schemas.google.com/g/2005#kind\" term=\"http://schemas.google.com/photos/2007#photo\"/>
            </entry>");

        $this->request->setParameters(array(
            'data' => '@' . $this->file
        ));
        $this->request->execute(
            'https://picasaweb.google.com/data/feed/api/user/' . $this->username . '/albumid/' . $this->albumId . '?alt=json'
        );

        $result = json_decode($this->request->getResponseText(), true);

        $this->checkRequestErrors(__METHOD__);

        if (
            $this->request->getResponseStatus() != 201
            || empty($result['entry']['media$group']['media$content'][0])
        ) {
            $this->throwException(sprintf('%s: Upload failed. %s', __METHOD__, $this->request->getResponseText()));
        }

        // url, width, height, type
        extract($result['entry']['media$group']['media$content'][0]);

        $size = $this->size ?: max($width, $height);
        $url  = str_replace(basename($url), 's' . $size . '/' . basename($url), $url);

        return $url;
    }

    /**
     * {@inheritdoc}
     */
    protected function doTransload()
    {
        $this->throwException(sprintf('%s: Currently, this plugin doesn\'t support transload image.', __METHOD__));
    }

    /**
     * Delete an album by albumid
     *
     * @param  string  $albumId
     * @return boolean True if album was deleted
     *
     * @throws \Exception
     */
    public function deleteAlbum($albumId)
    {
        $this->checkPermission(__METHOD__);

        $this->request->reset();
        $this->request->setHeaders(array(
            "Authorization: GoogleLogin auth=" . $this->get('sessionLogin'),
            "MIME-Version: 1.0",
            "GData-Version: 3.0",
            "If-Match: *"
        ));
        $this->request->execute('https://picasaweb.google.com/data/entry/api/user/' . $this->username . '/albumid/' . $albumId, 'DELETE');

        $this->checkRequestErrors(__METHOD__);

        return ($this->request->getResponseHeaders('status') == 200);
    }

    /**
     * Create new album and return albumId was created.
     *
     * @param  string       $title
     * @param  string       $access
     * @param  string       $description
     * @return string|false
     *
     * @throws \Exception
     */
    public function addAlbum($title, $access = 'public', $description = '')
    {
        $this->checkPermission(__METHOD__);

        $this->request->reset();
        $this->request->setHeaders(array(
            "Authorization: GoogleLogin auth=" . $this->get('sessionLogin'),
            "MIME-Version: 1.0",
        ));
        $this->request->setMimeContentType("application/atom+xml");
        $this->request->setRawPost("<entry xmlns='http://www.w3.org/2005/Atom' xmlns:media='http://search.yahoo.com/mrss/' xmlns:gphoto='http://schemas.google.com/photos/2007'>
            <title type='text'>" . $title . "</title>
            <summary type='text'>" . $description . "</summary>
            <gphoto:access>" . $access . "</gphoto:access>
            <category scheme='http://schemas.google.com/g/2005#kind' term='http://schemas.google.com/photos/2007#album'></category>
        </entry>");
        $this->request->execute('https://picasaweb.google.com/data/feed/api/user/' . $this->username, 'POST');

        $this->checkRequestErrors(__METHOD__);

        if (preg_match('#<id>.+?albumid/(.+?)</id>#i', $this->request->getResponseText(), $match)) {
            return $match[1];
        }

        return false;
    }

    /**
     * @param  string     $method
     * @throws \Exception if sessionLogin is empty
     */
    private function checkPermission($method)
    {
        if (!$this->get('sessionLogin')) {
            $this->throwException(sprintf('You must be logged in before call the method "%s"', __METHOD__));
        }
    }

}
