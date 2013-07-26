<?php
/** 
 * ChipVN Library
 * 
 * @package		ChipVN
 * @author		Phan Thanh Cong <ptcong90 at gmail dot com>
 * @copright	chiplove.9xpro aka ptcong90
 * @version		4.0
 * @release		Jul 25, 2013
*/

/**
 * This plugin for imageshack.us old version, imageshack.com API is comming soon also it doesn't work
 */
namespace ChipVN\Image_Uploader;

class Remote_Imageshack extends Remote
{
    /**
     * Array of infomation received from Imageshack
	 * There are all keys and values example
     * 
	 * image_link:  		http://img402.imageshack.us/img402/909/chiplovebiz01923.jpg
	 * thumb_link: 			http://img402.imageshack.us/img402/909/chiplovebiz01923.th.jpg
	 * image_location:		img402/909/chiplovebiz01923.jpg
	 * thumb_location		img402/909/chiplovebiz01923.th.jpg
	 * server				img402
	 * image_name			chiplovebiz01923.jpg
	 * done_page			http://img402.imageshack.us/content.php?page=done&amp;l=img402/909/chiplovebiz01923.jpg
	 * resolution			320x240
	 * filesize				22034
     * error                message here
     * 
     * @var array
     */
    private $_info = array();

    protected function _getPluginName() 
    {
        return __CLASS__;
    }
    
    protected function _doLogin() 
    {
        if( ! $this->get('cookieLogin')) {
            $this->http->reset();
            $this->http->setReferer('http://imageshack.us/');
            $this->http->execute('http://imageshack.us/auth.php', 'POST', array(
                'username'       => $this->_username,
                'password'      => $this->_password,
                'format'        => 'json',
                'stay_logged_in'=> 'on',
            ));
            $result = json_decode($this->http->getResponseText(), TRUE);

            if($this->http->errors) {
                $this->_throwHttpError(__METHOD__);
            }
            else if(isset($result['status']) AND $result['status'] == 1) {
                $this->set('cookieLogin', $this->http->getResponseCookie());
            }
            else {
                $this->set('cookieLogin', NULL);
                $this->_throwException(':method: Login falied. Please check your username/password again. :res', array(
                    ':method' => __METHOD__,
                    ':res' => $this->http->getResponseText()
                ));
            }
        }
        return TRUE;
    }

    protected function _doUpload() 
    {
        if (empty($this->_apiKey)) {
			$target	= 'http://post.imageshack.us/';
            $apiKey = $this->get('cookieLogin') ? '' : $this->_getFreeApiKey();
        }
		else {
			$target = 'http://www.imageshack.us/upload_api.php';
			$apiKey = $this->_apiKey;
		}
		$this->http->reset();	
		$this->http->setSubmitMultipart();
		$this->http->setCookie($this->get('cookieLogin')); // if use login
		$this->http->setParam(array(
			'fileupload' 	=> '@' . $this->_imagePath, 
			'xml' 			=> 'yes', 
			'key'			=> $apiKey,
		));
		$this->http->execute($target, 'POST');
        $this->_parseResponseText();
        
        if($this->http->errors) {
            $this->_throwHttpError(__METHOD__);
        }
        else if( ! isset($this->_info['image_link'])) {
			if(isset($this->_info['error'])) {
				$this->_throwException(':method: :error', array(
                    ':method' => __METHOD__,
                    ':error' => $this->_info['error']
                ));
			}
			else {
				$this->_throwException(':method: Has an error occurred. :res ', array(
                    ':method' => __METHOD__,
                    ':res' => $this->http->getResponseText()
                ));
			}
		}
        return $this->_info['image_link'];
    }	
    protected function _doTransload() 
    {
        if(empty($this->_apiKey)) {
			$target = 'http://post.imageshack.us/transload.php';
			$apiKey = $this->get('cookieLogin') ? '' : $this->_getFreeApiKey();
		}
		else {
			$target = 'http://www.imageshack.us/upload_api.php';
			$apiKey = $this->_apiKey;
		}
		
		$this->http->reset();	
		$this->http->setCookie($this->get('cookieLogin'));
		$this->http->setParam(array(
			'url' 		=> $this->_imageUrl, 
			'xml' 		=> 'yes',
			'key'		=> $apiKey,
		));
		$this->http->execute($target, 'POST');
		$this->_parseResponseText();
        
        if($this->http->errors) {
            $this->_throwHttpError(__METHOD__);
        }
        else if( ! isset($this->_info['image_link'])) {
			if(isset($this->_info['error'])) {
				$this->_throwException(':method: :error', array(
                    ':method' => __METHOD__,
                    ':error' => $this->_info['error']
                ));
			}
			else {
				$this->_throwException(':method: Has an error occurred. :res ', array(
                    ':method' => __METHOD__,
                    ':res' => $this->http->getResponseText()
                ));
			}
		}
        return $this->_info['image_link'];
    }
    
    /**
     * Get free API key (free upload also the image may remove after a period of time)
     * 
     * @return string API key
     * @throws Exception if cannot get free API key
     */
    private function _getFreeApiKey()
    {
        if( ! $this->get('freeApi'))  {
			$this->http->reset();	
			$this->http->execute('http://imageshack.us/?no_flash=y');	
            if($this->http->errors) {
                $this->_throwHttpError(__METHOD__);
            }
			else if(preg_match('#name="key"\s+value="([^"]+)"#i', $this->http->getResponseText(), $match)) {
				$this->set('freeApi', $match[1]);
			}
			else {
				$this->_throwException(':method: Cannot get free API key.', array(
                    ':method' => __METHOD__
                ));
			}
		}
		return $this->get('freeApi');
    }

    private function _parseResponseText()
    {
        $this->_info = array();
		preg_match_all('#<([\w_]+)[^>]*?>([^<]+?)</([\w_][^>]+)>#', $this->http->getResponseText(), $matches, PREG_SET_ORDER);
		foreach($matches as $match)
		{
			$this->_info[$match[1]] = $match[2];
		}
    }
}