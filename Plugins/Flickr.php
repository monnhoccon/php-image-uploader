<?php
/**
 * You must loggedin for uploading.
 * This plugin doesn't support transloading.
 * Register an API here: {@link https://www.flickr.com/services/api/misc.api_keys.html}.
 *
 */
namespace ChipVN\ImageUploader\Plugins;

use ChipVN\ImageUploader\Plugin;

class Flickr extends Plugin
{
    protected $key = '08ddd9dc59de9a454714a3f5ebd6ac86';

    protected $secret = '9cbbae9502521139';

    public function setKeySecret($key, $secret)
    {

    }

    public function getAuthUrl($perms = 'write')
    {
        $sign = md5($this->secret . 'api_key' . $this->key .  'perms' . $perms);

        // $url = 'http://www.flickr.com/services/auth/?api_key=' . $this->key. '&perms=' . $perms . '&api_sig=' . $sign;
        $url = 'http://www.flickr.com/signin/?acf=/services/auth/?api_key=' . $this->key. '&perms=' . $perms . '&api_sig=' . $sign;

        $this->request->reset();
        $this->request->execute($url); // 1
        $cookies = $this->request->getResponseCookies();
        $url = $this->request->getResponseHeaders('location');

        $this->request->reset();
        $this->request->setCookie($cookies);
        $this->request->execute($url); // 2

        $text = $this->request->getResponseText();
        $text = explode("<fieldset id='fsLogin'", $text, 2);
        $text = $text[1];
        $text = explode("</fieldset>", $text, 2);
        $text = $text[0];


        preg_match_all('#<input.*?type="hidden".*?name="([^"]*?)".*?value="([^"]*?)"#is', $text, $matches);
        $data = array_combine($matches[1], $matches[2]);
        $data['login'] = 'clf.cute1';
        $data['passwd'] = 'chandoiwa';

        $this->request->reset();
        $this->request->execute('https://login.yahoo.com/config/login?', 'POST', $data); // 3

        $cookies = $this->request->getResponseCookies();
        $url = $this->request->getResponseHeaders('location');

        $this->request->reset();
        $this->request->setCookie($cookies);
        $this->request->execute($url); // 4

        print_r($this->request);

    }

    protected function doLogin()
    {

    }
    protected function doUpload()
    {

    }
    protected function doTransload()
    {

    }
}

http://www.flickr.com/services/auth/?api_key=9a0554259914a86fb9e7eb014e4e5d52&perms=write&api_sig=a02506b31c1cd46c2e0b6380fb94eb3d
