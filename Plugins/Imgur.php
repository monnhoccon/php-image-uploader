<?php
/**
 * You may upload to your account or without account.
 */

class ChipVN_ImageUploader_Plugins_Imgur extends ChipVN_ImageUploader_Plugins_Abstract
{
    /**
     * {@inheritdoc}
     */
    protected function doLogin()
    {
        if (!$this->get('sessionLogin')) {
            $this->request->reset();
            $this->request->execute('https://imgur.com/signin', 'POST', array(
                'username' => $this->username,
                'password' => $this->password,
                'submit'   => '',
            ));

            $this->checkRequestErrors(__METHOD__);

            if ($this->request->getResponseStatus() == 302
                || $this->request->getResponseArrayCookies('just_logged_in') == 1
                || (stripos($this->request->getResponseHeaders('location'), $this->username))
            ) {
                $this->set('sessionLogin', $this->request->getResponseArrayCookies());

            } else {
                $this->throwException(sprintf('%s: Login failed. %s', __METHOD__, $this->request->getResponseText()));
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function doUpload()
    {
        if (!$this->get('sessionLogin')) {
            return $this->doUploadFree();
        }

        $this->request->reset();
        $this->request->setSubmitMultipart();
        $this->request->setCookies($this->get('sessionLogin'));
        $this->request->setParameters(array(
            'key'   => $this->apiKey,
            'image' => '@' . $this->file,
        ));
        $this->request->execute('http://api.imgur.com/2/upload.json', 'POST');
        $result = json_decode($this->request->getResponseText(), true);

        $this->checkRequestErrors(__METHOD__);

        if (isset($result['error'])) {
            $this->throwException(sprintf('%s: %s', __METHOD__ , $result['error']['message']));
        }

        return $this->getLinkFromUploadedResult($result);
    }

    /**
     * {@inheritdoc}
     */
    protected function doTransload()
    {
        if (!$this->get('sessionLogin')) {
            return $this->doTransloadFree();
        }

        $this->request->reset();
        $this->request->setCookies($this->get('sessionLogin'));
        $this->request->setParameters(array(
            'url' => $this->url,
        ));
        $this->request->execute('http://imgur.com/upload', 'POST');
        $result = json_decode($this->request->getResponseText(), true);

        $this->checkRequestErrors(__METHOD__);

        if (strpos($this->request->getResponseHeaders('location'), 'error')) {
            $this->throwException(sprintf('%s: Image format not supported, or image is corrupt.', __METHOD__));
        }

        return 'http://i.imgur.com/' . $result['data']['hash'] . $this->getExtensionFormImage($this->url);
    }

    /**
     * Free upload also the image may remove after a period of time
     *
     * @return string    Image URL after upload
     * @throws Exception if upload failed
     */
    private function doUploadFree()
    {
        $this->getFreeSID();

        $this->request->reset();
        $this->request->setSubmitMultipart();
        $this->request->setHeaders(array(
            'X-Requested-With' => 'XMLHttpRequest',
            'Referer'          => 'http://imgur.com/',
        ));
        $this->request->setCookies($this->get('cookieFreeSID'));
        $this->request->setParameters(array(
            'current_upload' => 1,
            'total_uploads'  => 1,
            'terms'          => 0,
            'album_title'    => __CLASS__,
            'gallery_title'  => __CLASS__,
            'sid'            => $this->get('freeSID'),
            'Filedata'       => '@' . $this->file,
        ));
        $this->request->execute('http://imgur.com/upload', 'POST');
        $result = json_decode($this->request->getResponseText(), true);

        $this->checkRequestErrors(__METHOD__);

        if (isset($result['data']['hash']) AND isset($result['success']) AND $result['success']) {
            return 'http://i.imgur.com/' . $result['data']['hash'] . $this->getExtensionFormImage($this->file);
        } else {
            $this->throwException(sprintf('%s: Free upload failed.', __METHOD__));
        }

        return false;
    }

    /**
     * Free transload also the image may remove after a period of time
     *
     * @return string    Image URL after transload
     * @throws Exception if upload failed
     */
    private function doTransloadFree()
    {
        $this->getFreeSID();

        $this->request->reset();
        $this->request->setHeaders(array(
            'X-Requested-With' => 'XMLHttpRequest',
            'Referer'          => 'http://imgur.com/',
        ));
        $this->request->setCookies($this->get('cookieFreeSID'));
        $this->request->setParameters(array(
            'current_upload' => 1,
            'total_uploads'  => 1,
            'terms'          => 0,
            'album_title'    => __CLASS__,
            'gallery_title'  => __CLASS__,
            'sid'            => $this->get('freeSID'),
            'url'            => $this->url,
        ));
        $this->request->execute('http://imgur.com/upload', 'POST');
        $result = json_decode($this->request->getResponseText(), true);

        $this->checkRequestErrors(__METHOD__);

        if (isset($result['data']['hash']) AND isset($result['success']) AND $result['success']) {
            return 'http://i.imgur.com/' . $result['data']['hash'] . $this->getExtensionFormImage($this->url);
        } else {
            $this->throwException(sprintf('%s: Free transload failed.', __METHOD__));
        }

        return false;
    }

    /**
     * [upload] => Array
      (
      [image] => Array
      (
      [name] =>
      [title] =>
      [caption] =>
      [hash] => BP2HdFa
      [deletehash] => XXXXXXX
      [datetime] => 2013-07-25 19:29:57
      [type] => image/jpeg
      [animated] => false
      [width] => 420
      [height] => 420
      [size] => 34056
      [views] => 0
      [bandwidth] => 0
      )

      [links] => Array
      (
      [original] => http://i.imgur.com/BP2HdFa.jpg
      [imgur_page] => http://imgur.com/BP2HdFa
      [delete_page] => http://imgur.com/delete/XXXXXXX
      [small_square] => http://i.imgur.com/BP2HdFas.jpg
      [big_square] => http://i.imgur.com/BP2HdFab.jpg
      [small_thumbnail] => http://i.imgur.com/BP2HdFat.jpg
      [medium_thumbnail] => http://i.imgur.com/BP2HdFam.jpg
      [large_thumbnail] => http://i.imgur.com/BP2HdFal.jpg
      [huge_thumbnail] => http://i.imgur.com/BP2HdFah.jpg
      )

      )
     * @param  array  $result
     * @return string
     */
    private function getLinkFromUploadedResult($result)
    {
        return $result['upload']['links']['original'];
    }

    private function getFreeSID()
    {
        if (!$this->get('freeSID')) {
            $this->request->reset();
            $this->request->execute('http://imgur.com/upload/start_session');
            $result = json_decode($this->request->getResponseText(), true);

            $this->checkRequestErrors(__METHOD__);

            if (isset($result['sid'])) {
                $this->set('freeSID', $result['sid']);
                $this->set('cookieFreeSID', $this->request->getResponseCookies());
            } else {
                $this->throwException(sprintf('%s: Cannot get free IMGURSESSION.', __METHOD__));
            }
        }

        return $this->get('freeSID');
    }

    /**
     * Get extension for image url (free upload or transload)
     * This method help to don't need to read the page after upload completed to get extension for the image
     *
     * @param  string $fileName
     * @return string
     */
    private function getExtensionFormImage($fileName)
    {
        $extension = '.jpg';
        if (preg_match('#\.(gif|jpg|jpeg|bmp|png)$#i', $fileName, $match)) {
            $extension = $match[0];
        }

        return $extension;
    }
}
