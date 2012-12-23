<?php
/**
 * Imgur Uploader, used to upload an image to imgur.com
 * You can free upload (no account)
 *
 * @project		Image Uploader
 * @class		Imgur Uploader
 * @author		Phan Thanh Cong <chiplove.9xpro@gmail.com>
 * @since		November 8, 2011
 * @version		1.3
 * @since		Nov 5, 2012
 * @copyright	chiplove.9xpro
*/

class Ptc_Image_Uploader_Imgur extends Ptc_Image_Uploader
{
	public function getPluginName()
	{
		return 'Imgur';
	}
	
	/**
	 * Imgur API
	*/
	protected $_apiKey;
	
	/**
	 * Set API by array or string. If set by arrray, this method fetch only an api by random to set
	 *
	 * @param	array|string	Array of api or api
	 * @return void
	*/
	public function setApi($api)
	{
		if( is_array($api)) {
			$api = $api[array_rand($api)];
		}
		$this->_apiKey = $api;
	}

	/**
	 * Execute login, and return result
	 *
	 * @return	boolean		true|false
	*/
	protected function _doLogin()
	{
		if( ! $this->session('login' . $this->_username)) {
			$this->http->reset();
			$this->http->useCurl(false);
			$this->http->execute('https://imgur.com/signin', 'POST', array(
				'username'	=> $this->_username,
				'password'	=> $this->_password,
				'submit'	=> '',
			));
			if($this->http->getResponseStatus() != 302) {
				$this->session('login' . $this->_username, NULL);
				throw new Exception(__METHOD__ . ': Login failed. Please check your username/password again.');
			}
			
			$responseCookies = $this->http->getResponseHeaders('set-cookie');
			print_r($responseCookies);
			unset($responseCookies[0]);
			$cookie = implode(';', $responseCookies);
			
			$this->session('login' . $this->_username, $cookie);
		}
		$this->session('loginCookie', $this->session('login' . $this->_username));
		return $this->http->getResponseStatus() == 302;
	}
	
	/**
	 * Upload a file on server to imgur.com
	 *
	 * @param 	string 			realpath of an image
	 * @return 	boolean|string	false if upload is failed or return image url
	*/
	protected function _doUpload($imagePath)
	{
		if(empty($this->_apiKey)) {
			$this->errors[] = 'Api key must be required.';
			return false;
		}
		$this->http->reset();
		$this->http->useCurl(false);
		$this->http->setSubmitMultipart();
		$this->http->setCookie($this->session('loginCookie'));
		$this->http->setParam(array(
			'key'	=> $this->_apiKey,
			'image'	=> '@'. $imagePath,
		));
		$this->http->execute('http://api.imgur.com/2/upload.json', 'POST');
		
		$result =  json_decode($this->http->getResponseText(), true);
		if(isset($result['error'])) {
			$this->errors[] = $result['error']['message'];
			return false;
		}
		// url<s>.extension => small
		// url<l>.extension => large
		return $result['upload']['links']['original'];
	}

	/**
	 * Transload an image url to imgur.com
	 *
	 * @param	string	an image url
	 * @return	boolean|string	false if transload is failed or return an image url
	*/
	protected function _doTransload($imageUrl)
	{
		$this->http->reset();
		$this->http->useCurl(false);
		$this->http->setCookie($this->session('loginCookie'));
		$this->http->setParam(array(
			'url'	=> $imageUrl,
		));
		$this->http->execute('http://imgur.com/upload', 'GET');
		$link = $this->http->getResponseHeaders('location');
		if(strpos($link, 'error')) {
			return false;
		}
		$extension = '.jpg';
		if(preg_match('#\.(gif|jpg|jpeg|bmp|png)$#i', $imageUrl, $match)) {
			$extension = $match[0];
		}
		return $link . $extension;
	}
}