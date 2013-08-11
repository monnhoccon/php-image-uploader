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
use ChipVN\Loader;
use ChipVN\Http;
use ChipVN\Exception;

abstract class Remote
{

    /**
     * This var to do remoting
     * @var Http
     */
    public $http;

    public function __construct()
    {
        // need to use SESSION to save cookie logged in
        if (!session_id()) {
            session_start();
        }
        if (!class_exists('\ChipVN\Http', FALSE)) {
            Loader::load('Http');
        }
        $this->http = new Http;
    }

    /**
     * Usernname to login to service
     * @var string
     */
    protected $_username = '';

    /**
     * Password to login to service
     * @var string
     */
    protected $_password = '';

    /**
     * API key if needed
     * @var string
     */
    protected $_apiKey = '';

    /**
     * Image file path for uploading
     * @var string
     */
    protected $_imagePath = '';

    /**
     * Image URL for transloading
     * @var string
     */
    protected $_imageUrl = '';

    /**
     * Execute login action and return status of the action
     *
     * @return boolean 
     * @throw Exception if has an error occured while execution
     */
    public function login($username, $password)
    {
        $this->_username = $username;
        $this->_password = $password;

        return $this->_doLogin();
    }

    /**
     * Set API key. 
     * You can set api by an array, then method will get random a key
     * 
     * @param string|array 
     */
    public function setApi($apiKeys)
    {
        $this->_apiKey = is_array($apiKeys) ? $apiKeys[array_rand($apiKeys, 1)] : $apiKeys;
    }

    /**
     * Execute upload action and return url of the image after uploaded success
     *
     * @return string URL of the image after uploaded success
     * @throws Exception if has an error occured while execution
     */
    public function upload($filePath)
    {
        if (!$realFilePath = realpath($filePath)) {
            $this->_throwException(':method File ":file" is not exist.', array(
                ':method' => __METHOD__,
                ':file' => $filePath
            ));
        }
        if (getimagesize($realFilePath) === FALSE) {
            $this->_throwException(':method: The file is not an image file.', array(
                ':method' => __METHOD__
            ));
        }
        $this->_imagePath = $realFilePath;
        return $this->_doUpload();
    }

    /**
     * Execute transload action and return url of the image after uploaded success
     *
     * @return string URL of the image after transloaded success
     * @throws Exception if has an error occured while execution
     */
    public function transload($imageUrl)
    {
        $this->_imageUrl = $imageUrl;
        return $this->_doTransload();
    }

    protected function _getKeyForSession($key)
    {
        return $this->_getPluginName() . $this->_username . $key;
    }

    /**
     * Save cookie/session
     * 
     * @param string $key
     * @param mixed $value
     */
    public function set($key, $value)
    {
        $key = $this->_getKeyForSession($key);
        $_SESSION[$key] = $value;
    }

    /**
     * Get cookie/session
     * 
     * @param type $key
     * @return mixed FALSE if value is not set
     */
    public function get($key)
    {
        $key = $this->_getKeyForSession($key);
        if (isset($_SESSION[$key])) {
            return $_SESSION[$key];
        }
        return FALSE;
    }

    /**
     * Throw Http Exception at the method
     * 
     * @param string $method
     * @throws \ChipVN\Exception
     */
    protected function _throwHttpError($method)
    {
		$this->_throwException(':method: ' . implode('; ', $this->http->errors), array(
    		':method' => $method
        ));
    }

    /**
     * 
     * @param type $message
     * @param type $param
     * @throws \ChipVN\Exception
     */
    protected function _throwException($message, $param = array())
    {
		if (!class_exists('\ChipVN\Exception', FALSE)) {
            Loader::load('Exception');
        }
        throw new Exception($message, $param);
    }

    /**
     * Get plugin name, ex: Imageshack
     * 
     * @return string
     */
    abstract protected function _getPluginName();
    /**
     * Execute login action and return status of the action
     * If login success, keep the session/cookie for execute other next action what need it
     *
     * @return boolean 
     * @throws Exception if has an error occured while execution
     */
    abstract protected function _doLogin();
    /**
     * Execute upload action and return url of the image after uploaded success
     *
     * @return string URL of the image after uploaded success
     * @throws Exception if has an error occured while execution
     */
    abstract protected function _doUpload();
    /**
     * Execute transload action and return url of the image after uploaded success
     *
     * @return string URL of the image after transloaded success
     * @throws Exception if has an error occured while execution
     */
    abstract protected function _doTransload();
}

