<?php
/**
 * Use "imageshack.com" rest API v2, insteadof "imageshack.us".
 * Register an API here: {@link https://imageshack.com/contact/api}.
 * You must login and have an API for uploading, transloading.
 *
 * @update Mar 07, 2014
 */

namespace ChipVN\ImageUploader\Plugins;

use ChipVN\ImageUploader\Plugin;

class Imageshack extends Plugin
{
    /**
     * API endpoint URL.
     *
     * @var string
     */
    const API_ENDPOINT = 'https://imageshack.com/rest_api/v2/';

    /**
     * Get API endpoint URL.
     *
     * @param  string $path
     * @return string
     */
    protected function getApiURL($path)
    {
        return self::API_ENDPOINT . $path;
    }

    /**
     * {@inheritdoc}
     */
    protected function doLogin()
    {
        // sessionLogin is array
        if (!$this->get('sessionLogin')) {
            $this->request->reset();
            $this->request->setReferer('https://imageshack.com/');
            $this->request->execute($this->getApiURL('user/login'), 'POST', array(
                'username' => $this->username,
                'password' => $this->password,
                'remember_me' => 'true',
                'set_cookies' => 'true',
            ));
            $result = json_decode($this->request->getResponseText(), true);
            if ($this->request->errors) {
                $this->throwHttpError(__METHOD__);

            } elseif (!empty($result['result']['userid'])) {
                $this->set('sessionLogin', $result['result']);

            } else {
                $this->set('sessionLogin', null);
                if (isset($request['error']['error_message'])) {
                    $message = $request['error']['error_message'];
                } else {
                    $message = 'Login failed.';
                }
                $this->throwException(sprintf('%s: %s. %s', __METHOD__, $message, $this->request->getResponseText()));
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function doUpload()
    {
        return $this->sendRequest(array('file' => '@' . $this->file));
    }

    /**
     * {@inheritdoc}
     */
    protected function doTransload()
    {
        return $this->sendRequest(array('url' => $this->url));
    }

    /**
     * Send request to API and get image URL.
     *
     * @param  array  $param
     * @return string
     *
     * @throws \Exception If have an error occured
     */
    private function sendRequest(array $param)
    {
        if (!$this->get('sessionLogin') || empty($this->apiKey)) {
            $this->throwException('You must be loggedin and have an API key. Register API here: https://imageshack.com/contact/api');
        }

        $target = $this->getApiURL('images');
        $apiKey = $this->apiKey;
        $session = $this->get('sessionLogin');

        $this->request->reset();
        $this->request->setSubmitMultipart();
        $this->request->setParam($param + array(
            'auth_token' => $session['auth_token'],
            'api_key' => $apiKey,
        ));
        $this->request->execute($target, 'POST');

        $result = json_decode($this->request->getResponseText(), true);

        if ($this->request->errors) {
            $this->throwHttpError(__METHOD__);

        } elseif (isset($result['error']['error_message'])) {
            $this->throwException(__METHOD__ . ': ' . $result['error']['error_message'] . $this->request->getResponseText());

        } elseif (isset($result['result']['images'][0]['direct_link'])) {
            $url = $result['result']['images'][0]['direct_link'];
            if (strpos($url, 'http://') !== 0) {
                $url = 'http://' . $url;
            }

            return $url;
        }
    }
}
