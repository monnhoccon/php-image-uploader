<?php
/**
 * ImageShack Uploader, used to upload/ transfer an image to imageshack.us
 * You can free upload or upload to your account
 * 
 * @project		Image Uploader
 * @class		ImageShack Uploader
 * @author		Phan Thanh Cong <chiplove.9xpro@gmail.com>
 * @since		June 17, 2010
 * @version		3.3
 * @since		June 12, 2012
 * @copyright	chiplove.9xpro
*/
class Ptc_Image_Uploader_Imageshack extends Ptc_Image_Uploader
{
	public function getPluginName()
	{
		return 'Imageshack';
	}
	
	/**
	 * API registed from imageshack.us
	 * Use this for upload faster 
	 * Register: http://stream.imageshack.us/api/
	 * 
	 * @var string
	*/
	protected $_apiKey;

	/**
	 * Set API by array or string. If set by arrray, 
	 * this method fetch only an api by random to set
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
			$this->http->clear();
			
			$this->http->execute('http://imageshack.us/auth.php', 'POST', array(
				'username'			=> $this->_username,
				'password'			=> $this->_password,
				'stay_logged_in'	=> 'true',
				'format'			=> 'json',
			));
			$login = json_decode($this->http->getResponseText(), TRUE);
			
			if(isset($login['status']) AND $login['status'] == 1) {
				preg_match_all('#(ulevel|myimages|isUSER|myid)[^;]+#i', $this->http->getResponseCookie(), $match);
				$cookie = implode(';', $match[0]);
				$this->session('login' . $this->_username, $cookie);
			}
			else {
				$this->session('login' . $this->_username, NULL);
				throw new Exception(__METHOD__.': Login falied. Please check your username/password again.');
			}
			$this->http->clear();
		}
		$this->session('loginCookie', $this->session('login' . $this->_username));
		
		return stripos($this->session('loginCookie'), 'isUSER') !== false;
	}

	/**
	 * Method get free key (upload ko cần đăng nhập hoặc ko cần đăng ký API key với imageshack)
	 * Nên tham khảo quy định về sử dụng hình ảnh với free account tại đây: http://imageshack.us/p/rules/
	*/
	protected function _getFreeApiKey()
	{
		if( ! $this->session('freeApi'))  {
			$this->http->reset();	
			$this->http->useCurl(true);
			$this->http->execute('http://imageshack.us/?no_flash=y');	
			if(preg_match('#name="key"\s+value="([^"]+)"#i', $this->http->getResponseText(), $match)) {
				$this->session('freeApi', $match[1]);
			}
			else {
				throw new Exception(__METHOD__ . ': Cannot get free API key.');
			}
		}
		return $this->session('freeApi');
	}
	
	
	/**
	 * Upload a file on server to imageshack.us
	 *
	 * @param 	string 			realpath of an image
	 * @return 	boolean|string	false if upload is failed or return image url
	*/
	protected function _doUpload($filePath)
	{			
		if(empty($this->_apiKey)) {
			$target	= 'http://imageshack.us/';
			// is guest
			if( ! $this->session('loginCookie')) {
				$apiKey	= $this->_getFreeApiKey();
			}
		}
		else {
			$target = 'http://www.imageshack.us/upload_api.php';
			$apiKey =& $this->_apiKey;
		}
		$this->http->reset();	
		$this->http->useCurl(false);
		$this->http->setSubmitMultipart();
		$this->http->setCookie($this->session('loginCookie'));
		$this->http->setParam(array(
			'fileupload' 	=> '@' . $filePath, 
			'xml' 			=> 'yes', 
			'key'			=> $apiKey,
		));
		$this->http->execute($target, 'POST');	
		
		$this->_parseXML();
		
		if( ! isset($this->_images['image_link'])) {
			return FALSE;
		}
		return $this->_images['image_link'];
	}
	
	/**
	 * Transload an image url to imageshack.us
	 *
	 * @param	string	an image url
	 * @return	boolean|string	false if transload is failed or return an image url
	*/
	protected function _doTransload($imageUrl)
	{
		if(empty($this->_apiKey)) {
			$target = 'http://post.imageshack.us/transload.php';
			// is guest
			if( ! $this->session('loginCookie')) {
				$apiKey	= $this->_getFreeApiKey();
			}
		}
		else {
			$target = 'http://www.imageshack.us/upload_api.php';
			$apiKey =& $this->_apiKey;
		}
		
		$this->http->reset();	
		$this->http->useCurl(false);
		$this->http->setCookie($this->session('loginCookie'));
		$this->http->setParam(array(
			'url' 		=> $imageUrl, 
			'xml' 		=> 'yes',
			'key'		=> $apiKey,
		));
		$this->http->execute($target, 'POST');
		
		$this->_parseXML();
		
		if( ! isset($this->_images['image_link'])) {
			return FALSE;
		}
		return $this->_images['image_link'];
	}
	
	/**
	 * Parse image form source to array
	 *
	 * $this->_images array
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
	*/
	private $_images;
	
	private function _parseXML()
	{
		$this->_images = array();
		preg_match_all('#<([\w_][^>]+)>([^<]+?)</([\w_][^>]+)>#', $this->http->getResponseText(), $matches, PREG_SET_ORDER);
		foreach($matches as $match)
		{
			$this->_images[$match[1]] = $match[2];
		}
	}
}