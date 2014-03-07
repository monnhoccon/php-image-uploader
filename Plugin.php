<?php

namespace ChipVN\ImageUploader;

use ChipVN\Http\Request;
use Exception;

abstract class Plugin
{
    /**
     * Username to login hosting service.
     *
     * @var string
     */
    protected $username;

    /**
     * Password to login hosting service.
     *
     * @var string
     */
    protected $password;

    /**
     * API key if needed.
     *
     * @var string
     */
    protected $apiKey;

    /**
     * Image file path for uploading.
     *
     * @var string
     */
    protected $file;

    /**
     * Image url for transloading.
     *
     * @var string
     */
    protected $url;

    /**
     * \ChipVN\Http\Request instance for sending request.
     *
     * @var \ChipVN\Http\Request
     */
    protected $request;

    /**
     * Create a plugin instance.
     *
     * @return void
     *
     * @throws \Exception If session is not initialized
     */
    final public function __construct()
    {
        // Sure that session is initialized for saving somethings.
        if (!session_id()) {
            if (headers_sent()) {
                $this->throwException('Session is not initialized. Please sure that session_start(); was called at the top of the script.');
            }
            session_start();
        }

        $this->request = new Request;
        $this->request->useCurl(false);
    }

    /**
     * Use cURL for sending request.
     *
     * @param  boolean $useCurl
     * @return void
     */
    public function useCurl($useCurl)
    {
        $this->request->useCurl($useCurl);
    }

    /**
     * Execute login action and return results.
     *
     * @param  string  $username
     * @param  string  $password
     * @return boolean
     *
     * @throws \Exception If login failed.
     */
    final public function login($username, $password)
    {
        $this->username = $username;
        $this->password = $password;

        return $this->doLogin($username, $password);
    }

    /**
     * Set API key.
     * You may set $apiKey by array of keys but it use only one for a request.
     *
     * @param  array|string $keys
     * @return void
     */
    final public function setApi($keys)
    {
        $keys = (array) $keys;

        $this->apiKey = $keys[array_rand($keys, 1)];
    }

    /**
     * Execute upload action and return URL.
     *
     * @param  string       $file
     * @return string|false
     *
     * @throws \Exception If have an error occurred
     */
    final public function upload($file)
    {
        if (!$filepath = realpath($file)) {
            $this->throwException(sprintf('%s: File "%s" is not exists.', __METHOD__, $file));
        }
        if (!getimagesize($filepath)) {
            $this->throwException(sprintf('%: The file "%s" is not an image.', __METHOD__, $file));
        }
        $this->file = $filepath;

        return $this->doUpload();
    }

    /**
     * Execute transload action and return URL.
     *
     * @param  string       $url
     * @return string|false
     *
     * @throws \Exception If have an error occurred
     */
    final public function transload($url)
    {
        $this->url = trim($url);

        return $this->doTransload();
    }

    /**
     * Get plugin name.
     *
     * @return string
     */
    final public function getPluginName()
    {
        return get_called_class();
    }

    /**
     * Get cookie, session set by name.
     *
     * @param  string $name
     * @param  mixed  $default
     * @return mixed
     */
    final public function get($name, $default = null)
    {
        $key = $this->getSessionKey($name);

        if (isset($_SESSION[$key])) {
            return $_SESSION[$key];
        }

        return $default;
    }

    /**
     * Save cookies, sessions authentication to execute next request faster.
     *
     * @param  string $name
     * @param  mixed  $value
     * @return void
     */
    final public function set($name, $value)
    {
        $key = $this->getSessionKey($name);

        $_SESSION[$key] = $value;
    }

    /**
     * Get session key.
     *
     * @param  string $name
     * @return string
     */
    final protected function getSessionKey($name)
    {
        return $this->getPluginName() . $this->username . $name;
    }

    /**
     * Execute login action and return results.
     *
     * @return boolean
     *
     * @throws \Exception If login failed.
     */
    abstract protected function doLogin();

    /**
     * Execute upload action and return URL.
     *
     * @return string|false
     *
     * @throws \Exception If have an error occurred
     */
    abstract protected function doUpload();

    /**
     * Execute transload action and return URL.
     *
     * @return string|false
     *
     * @throws \Exception If have an error occurred
     */
    abstract protected function doTransload();

    /**
     * Throws an exception.
     *
     * @param  string     $message
     * @return \Exception
     */
    protected function throwException($message)
    {
        throw new Exception($message);
    }

    /**
     * Throws an exception.
     *
     * @param  string     $method
     * @return \Exception
     */
    protected function throwHttpError($method)
    {
        return $this->throwException(sprintf('%s: %s', $method, implode(', ', $this->request->errors)));
    }
}
