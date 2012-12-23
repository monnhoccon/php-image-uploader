<?php
/**
 * Class Image Uploader, use to upload images to some services. Eg: Picasa, Imgur, ImageShack ...
 * 
 * @project		Image Uploader
 * @author		Phan Thanh Cong <chiplove.9xpro@gmail.com>
 * @since		June 17, 2010
 * @version		3.3
 * @since		Nov 5, 2012
 * @copyright	chiplove.9xpro
*/

abstract class Ptc_Image_Uploader 
{
	const VERSION = '3.3';
	
	const VERSIONID = 20121105; // YYYY-DD-MM
	
	protected static $_pluginDir;
	
	/**
	 * Username for logging
	 * @var string
	*/
	protected $_username;
	
	/**
	 * Password for logging
	 * @var string
	*/
	protected $_password;
	
	/**
	 * Errors while upload/transloading
	 * @var array
	*/
	public $errors = array();
	
	/**
	 * Ptc_Http instance use for sending request
	 * @var Ptc_Http
	*/
	public $http;
	
	/**
	 *
	*/
	public function __construct()
	{
		if( ! session_id()) {
			session_start();
		}
		// require Ptc_Htpp class for sending request
		if( ! class_exists('Ptc_Http')) {
			Ptc_Loader::load('Http');
		}
		$this->http = new Ptc_Http;
	}
	
	/** 
	 * @return	Ptc_Image_Uploader
	*/
	public function factory($pluginName, $pluginDir = NULL)
	{
		$pluginName = ucfirst($pluginName);
		if($pluginDir !== NULL) {
			self::$_pluginDir = $pluginDir;
		}
		if(empty(self::$_pluginDir)) {
			self::$_pluginDir = __DIR__ . DIRECTORY_SEPARATOR . 'Uploader' . DIRECTORY_SEPARATOR;
		}
		if( ! file_exists(self::$_pluginDir . $pluginName . '.php')) {
			throw new Exception('Plugin "' . $pluginName . '" does not exists.');
		}
		require_once(self::$_pluginDir . $pluginName . '.php');
		
		$class = __CLASS__ . '_' . $pluginName;
		return new $class;
	}
	
	/** 
	 * Set plugin dir
	*/
	public static function setPluginsDir($dir)
	{
		self::$_pluginDir = $dir;
	}
	
	/**
	 * Execute login, and return result
	 * Child class can be redeclare this function
	 *
	 * @return	boolean		true|false
	*/
	public function login($username, $password)
	{
		$this->_username = $username;
		$this->_password = $password;
		
		return $this->_doLogin();
	}
	
	/**
	 * Execute upload an image from server to service target,
	 * and return url of the image uploaded
	 *
	 * @param	string			the realpath of an image
	 * @return	boolean|string	FALSE if upload is failed or image url uploaded
	*/
	final public function upload($imagePath)
	{
		if( ! file_exists($imagePath)) 
		{
			throw new Exception('The file "'.$imagePath.'" does not exists.');
		}
		return $this->_doUpload($imagePath);
	}
	
	/**
	 * Execute transload an image from url to service target 
	 * and return url of the image transloaded
	 *
	 * @param	string			an image url
	 * @return	boolean|string	FALSE if transload is failed or image url transloaded
	*/
	final public function transload($imageUrl)
	{
		return $this->_doTransload($imageUrl);
	}
	
	/**
	 * Get name of plugin
	 *
	 * @return	string
	*/
	abstract public function getPluginName();
	/**
	 * Child class must be declare these functions
	*/
	abstract protected function _doLogin();
	abstract protected function _doUpload($imagePath);
	abstract protected function _doTransload($imageUrl);
	
	
	/**
	 * Helper method to set/get session
	*/
	public function session($name, $value = NULL) 
	{
		if(func_num_args() == 1) {
			if(isset($_SESSION[$this->getPluginName()][$name])) {
				return $_SESSION[$this->getPluginName()][$name];
			}
			return false;
		}
		$_SESSION[$this->getPluginName()][$name] = $value;
	}
}