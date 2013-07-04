<?php
/**
 * Picasa Uploader, used to upload an image to picasaweb.com
 * You must have an account for upload
 * 
 * @project		Image Uploader
 * @class		Picasa Uploader
 * @author		Phan Thanh Cong <chiplove.9xpro@gmail.com>
 * @version		1.1
 * @since		June 12, 2012
 * @copyright	chiplove.9xpro
*/

class Ptc_Image_Uploader_Picasa extends Ptc_Image_Uploader
{
	public function getPluginName()
	{
		return 'Picasa';
	}

	public function login($username, $password)
	{
		$username = preg_replace('#@.*?$#', '', $username);
		return parent::login($username, $password);
	}
	
	/**
	 * Execute login, and return result
	 *
	 * @return	boolean		true|false
	*/
	protected function _doLogin()
	{
		if( ! $this->session('login' . $this->_username) OR $this->session('loginTime') + 60*5 < time()) {
			$this->http->reset();	
			$this->http->useCurl(false);
			$this->http->setParam(array(
				'accountType' 	=> 'HOSTED_OR_GOOGLE',  
				'Email' 		=> $this->_username,  
				'Passwd' 		=> $this->_password,  
				'source'		=> __CLASS__,  
				'service'		=> 'lh2'
			));
			$this->http->execute('https://www.google.com/accounts/ClientLogin', 'POST');
			
			if(!empty($this->http->errors)) {
				throw new Exception(__METHOD__.': Login falied. ' . implode('. ', $this->http->errors));
			}
			$cookie = '';
			if(preg_match("/Auth=([a-z0-9_\-]+)/i", $this->http->getResponseText(), $match))
			{
				$cookie = $match[1];
			}
			else
			{
				throw new Exception(__METHOD__.': Login falied.' . $this->http->getResponseText());
			}
			if($cookie == '') {
				$this->session('login' . $this->_username, NULL);
				throw new Exception(__METHOD__.': Login falied. Please check your username/password again.');
			}
			$this->session('login' . $this->_username, $cookie);
			$this->session('loginTime', time());
		}
		$this->session('loginCookie', $this->session('login' . $this->_username));
		
		return $this->session('loginCookie') != NULL;	
	}
	
	/**
	 * Delete an album by albumid
	 *
	 * @param	string	albumid
	*/
	public function deleteAlbum($albumId)
	{
		$this->checkPermission(__METHOD__);
		
		$this->http->setHeader(array(
			"Authorization: GoogleLogin auth=" . $this->session('loginCookie'),
			"MIME-Version: 1.0",
			"GData-Version: 3.0",
			"If-Match: *"
		));
		$this->http->execute('https://picasaweb.google.com/data/entry/api/user/' . $this->_username. '/albumid/' . $albumId, 'DELETE');
		
		return (strpos($this->http->getHeader('status'), '200') !== false);
		
	}
	
	private function _checkPermission($method)
	{
		if( ! $this->session('loginCookie')) {
			throw new Exception('You must be logged in or use logged cookie before call the method "'.$method.'"');
		}
	}
	
	/**
	 * Create new album and return albumId created
	 *
	 * @param	string	the title of the album
	 * @param	string	access public|private
	 * @param	string	the description of the album
	 * @return	string|boolean - FALSE if failed
	*/
	public function addAlbum($title, $access = 'public', $description = '')
	{
		$this->_checkPermission(__METHOD__);
		
		$this->http->setHeader(array(
			"Authorization: GoogleLogin auth=" . $this->session('loginCookie'),
			"MIME-Version: 1.0",	
		));
		$this->http->setMimeContentType("application/atom+xml");
		$this->http->setRawPost("<entry xmlns='http://www.w3.org/2005/Atom' xmlns:media='http://search.yahoo.com/mrss/' xmlns:gphoto='http://schemas.google.com/photos/2007'>
		  <title type='text'>" . $title. "</title>
		  <summary type='text'>" . $description . "</summary>
		  <gphoto:access>" . $access . "</gphoto:access>
		  <category scheme='http://schemas.google.com/g/2005#kind' term='http://schemas.google.com/photos/2007#album'></category>
		</entry>");
		$this->http->execute('https://picasaweb.google.com/data/feed/api/user/' . $this->_username, 'POST');
		
		if(preg_match('#<id>(.+?)</id>#i', $this->http->getResponseText(), $match)) {
			return $match[1];
		}
		return false;
	}
	
	/*
	 * AlbumId to archive image uploaded. Picasa limit 1000 images per album and total 10.000 images per account
	 * 
	 * @var	string
 	*/
	private $_albumId = 'default';
	
	/**
	 * Set albumid to uploading
	 * 
	 * @param	string	albumid
	*/
	public function setAlbumId($albumId)
	{
		if( is_array($albumId))
		{
			$albumId = $albumId[array_rand($albumId)];
		}
		$this->_albumId = $albumId;
	}
	
	/**
	 * Size of image after uploaded. This calculated by max(with, height)
	 *
	 * @var	integer
	*/
	private $_size = NULL;
	
	public function setSize($size)
	{
		$this->_size = $size;
	}
	
	/**
	 * Upload a file on server to picasa
	 *
	 * @param 	string 			realpath of an image
	 * @return 	boolean|string	false if upload is failed or return image url
	*/
	protected function _doUpload($imagePath)
	{
		$this->_checkPermission(__METHOD__);
		
		if( ! $this->_albumId) {
			$this->errors[] = 'Missing albumId to upload';
			return false;
		}
		
		$this->http->reset();	
		$this->http->useCurl(false);
		$this->http->setSubmitMultipart('related');
		
		$this->http->setHeader(array(
			"Authorization: GoogleLogin auth=" . $this->session('loginCookie'),
			"MIME-Version: 1.0",
		));
		$this->http->setRawPost("Content-Type: application/atom+xml\r\n
			<entry xmlns='http://www.w3.org/2005/Atom'>
			<title>".preg_replace('#\..*?$#i', '', basename($imagePath))."</title>
			<category scheme=\"http://schemas.google.com/g/2005#kind\" term=\"http://schemas.google.com/photos/2007#photo\"/>
			</entry>
		"); 
		$this->http->setParam(array(
			'data' => '@' . $imagePath
		));
		$this->http->execute('https://picasaweb.google.com/data/feed/api/user/'.$this->_username.'/albumid/' . $this->_albumId);
		
		$result = $this->http->getResponseText(); 
		
		// upload is failed
		if($this->http->getResponseStatus() != 201) { //201  Created 
			return FALSE;
		}
		preg_match('#<gphoto:width>(\d+)</gphoto:width>#', $result, $match);
		$width = $match[1];
		preg_match('#<gphoto:height>(\d+)</gphoto:height>#', $result, $match);
		$height = $match[1];
		preg_match('#src=\'([^\'"]+)\'#', $result, $match);
		$url = $match[1];
		
		$size = max($width, $height);
		if($this->_size !== NULL) {
			$size = $this->_size;
		}
		$url = str_replace(basename($url), 's' . $size . '/' . basename($url), $url);
		return $url;
	}
	
	protected function _doTransload($imageUrl)
	{
		throw new Exception(__METHOD__ . ': Now, this plugin do not support transload image.');
	}
}