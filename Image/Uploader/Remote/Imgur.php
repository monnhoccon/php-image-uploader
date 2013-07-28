<?php

/**
 * ChipVN Library
 * 
 * @package		ChipVN
 * @author		Phan Thanh Cong <ptcong90 at gmail dot com>
 * @copright	chiplove.9xpro aka ptcong90
 * @version		2.0
 * @release		Jul 25, 2013
 */

namespace ChipVN\Image_Uploader;

class Remote_Imgur extends Remote
{
    protected function _getPluginName()
    {
        return __CLASS__;
    }

    protected function _doLogin()
    {
        if (!$this->get('cookieLogin')) {
            $this->http->reset();
            $this->http->execute('https://imgur.com/signin', 'POST', array(
                'username' => $this->_username,
                'password' => $this->_password,
                'submit' => '',
            ));
            if ($this->http->errors) {
                $this->_throwHttpError(__METHOD__);
            } else if ($this->http->getResponseStatus() == 302 OR stripos($this->http->getResponseCookie(), 'just_logged_in=1') OR (stripos($this->http->getResponseHeaders('location'), $this->_username))
            ) {
                $arrayCookies = $this->http->getResponseHeaders('set-cookie');
                // remove IMGURSESSION at the first, that is old or fake SESSION
                array_shift($arrayCookies);
                $this->set('cookieLogin', implode(';', $arrayCookies));
            } else {
                $this->set('cookieLogin', NULL);
                $this->_throwException(':method: Login failed. Please check your username/password again. :res', array(
                    ':method' => __METHOD__,
                    ':res' => $this->http->getResponseText()
                ));
            }
        }
        return TRUE;
    }

    protected function _doUpload()
    {
        if (!$this->get('cookieLogin')) {
            return $this->_doUploadFree();
        }

        $this->http->reset();
        $this->http->setSubmitMultipart();
        $this->http->setCookie($this->get('cookieLogin'));
        $this->http->setParam(array(
            'key' => $this->_apiKey,
            'image' => '@' . $this->_imagePath,
        ));
        $this->http->execute('http://api.imgur.com/2/upload.json', 'POST');
        $result = json_decode($this->http->getResponseText(), true);

        if ($this->http->errors) {
            $this->_throwHttpError(__METHOD__);
        } else if (isset($result['error'])) {
            $this->_throwException(':method: :error', array(
                ':method' => __METHOD__,
                ':error' => $result['error']['message']
            ));
        }
        return $this->_getLinkFromUploadedResult($result);
    }

    protected function _doTransload()
    {
        if (!$this->get('cookieLogin')) {
            return $this->_doTransloadFree();
        }

        $this->http->reset();
        $this->http->setCookie($this->get('cookieLogin'));
        $this->http->setParam(array(
            'url' => $this->_imageUrl,
        ));
        $this->http->execute('http://imgur.com/upload', 'POST');
        $result = json_decode($this->http->getResponseText(), TRUE);
        if ($this->http->errors) {
            $this->_throwHttpError(__METHOD__);
        } else if (strpos($this->http->getResponseHeaders('location'), 'error')) {
            $this->_throwException(':method: Image format not supported, or image is corrupt.', array(
                ':method' => __METHOD__
            ));
        }
        return 'http://i.imgur.com/' . $result['data']['hash'] . $this->_getExtensionFormImage($this->_imageUrl);
    }

    /**
     * Free upload also the image may remove after a period of time
     * 
     * @return string Image URL after upload
     * @throws Exception if upload failed
     */
    private function _doUploadFree()
    {
        $this->_getFreeSID();

        $this->http->reset();
        $this->http->setSubmitMultipart();
        $this->http->setHeader(array(
            'X-Requested-With' => 'XMLHttpRequest',
            'Referer' => 'http://imgur.com/',
        ));
        $this->http->setCookie($this->get('cookieFreeSID'));
        $this->http->setParam(array(
            'current_upload' => 1,
            'total_uploads' => 1,
            'terms' => 0,
            'album_title' => __CLASS__,
            'gallery_title' => __CLASS__,
            'sid' => $this->get('freeSID'),
            'Filedata' => '@' . $this->_imagePath,
        ));
        $this->http->execute('http://imgur.com/upload', 'POST');
        $result = json_decode($this->http->getResponseText(), TRUE);
        if ($this->http->errors) {
            $this->_throwHttpError(__METHOD__);
        } else if (isset($result['data']['hash']) AND isset($result['success']) AND $result['success']) {
            return 'http://i.imgur.com/' . $result['data']['hash'] . $this->_getExtensionFormImage($this->_imagePath);
        } else {
            $this->_throwException(':method: Free upload failed.', array(
                ':method' => __METHOD__
            ));
        }
    }

    /**
     * Free transload also the image may remove after a period of time
     * 
     * @return string Image URL after transload
     * @throws Exception if upload failed
     */
    private function _doTransloadFree()
    {
        $this->_getFreeSID();

        $this->http->reset();
        $this->http->setHeader(array(
            'X-Requested-With' => 'XMLHttpRequest',
            'Referer' => 'http://imgur.com/',
        ));
        $this->http->setCookie($this->get('cookieFreeSID'));
        $this->http->setParam(array(
            'current_upload' => 1,
            'total_uploads' => 1,
            'terms' => 0,
            'album_title' => __CLASS__,
            'gallery_title' => __CLASS__,
            'sid' => $this->get('freeSID'),
            'url' => $this->_imageUrl,
        ));
        $this->http->execute('http://imgur.com/upload', 'POST');
        $result = json_decode($this->http->getResponseText(), TRUE);
        if ($this->http->errors) {
            $this->_throwHttpError(__METHOD__);
        } else if (isset($result['data']['hash']) AND isset($result['success']) AND $result['success']) {
            return 'http://i.imgur.com/' . $result['data']['hash'] . $this->_getExtensionFormImage($this->_imageUrl);
        } else {
            $this->_throwException(':method: Free transload failed.', array(
                ':method' => __METHOD__
            ));
        }
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
      [deletehash] => 2kQ9jV8p5dQfTQW
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
      [delete_page] => http://imgur.com/delete/2kQ9jV8p5dQfTQW
      [small_square] => http://i.imgur.com/BP2HdFas.jpg
      [big_square] => http://i.imgur.com/BP2HdFab.jpg
      [small_thumbnail] => http://i.imgur.com/BP2HdFat.jpg
      [medium_thumbnail] => http://i.imgur.com/BP2HdFam.jpg
      [large_thumbnail] => http://i.imgur.com/BP2HdFal.jpg
      [huge_thumbnail] => http://i.imgur.com/BP2HdFah.jpg
      )

      )
     * @param array $result
     * @return string
     */
    private function _getLinkFromUploadedResult($result)
    {
        return $result['upload']['links']['original'];
    }

    private function _getFreeSID()
    {
        if (!$this->get('freeSID')) {
            $this->http->reset();
            $this->http->execute('http://imgur.com/upload/start_session');
            $result = json_decode($this->http->getResponseText(), TRUE);
            if ($this->http->errors) {
                $this->_throwHttpError(__METHOD__);
            } else if (isset($result['sid'])) {
                $this->set('freeSID', $result['sid']);
                $this->set('cookieFreeSID', $this->http->getResponseCookie());
            } else {
                $this->_throwException(':method: Cannot get free IMGURSESSION.', array(
                    ':method' => __METHOD__
                ));
            }
        }
        return $this->get('freeSID');
    }

    /**
     * Get extension for image url (free upload or transload)
     * This method help to don't need to read the page after upload completed to get extension for the image
     * 
     * @param string $fileName
     * @return string
     */
    private function _getExtensionFormImage($fileName)
    {
        $extension = '.jpg';
        if (preg_match('#\.(gif|jpg|jpeg|bmp|png)$#i', $fileName, $match)) {
            $extension = $match[0];
        }
        return $extension;
    }

}

