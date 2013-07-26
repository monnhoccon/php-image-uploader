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
namespace ChipVN\Image_Uploader;

class Remote_Picasa extends Remote
{
    /**
	 * Size of image after uploaded. This calculated by max(with, height)
	 * @var	integer
	*/
	private $_size = NULL;
    
    /*
	 * AlbumId to archive image. 
     * Account upload limits:
     * 
     * Maximum photo size: Each image can be no larger than 20 megabytes and are restricted to 50 megapixels or less.
     * Maximum video size: Each video uploaded can be no larger than 1GB in size.
     * Maximum number of web albums: 20,000
     * Maximum number of photos and videos per web album: 1,000
     * Total storage space: Picasa Web provides 1 GB for photos and videos. Files under 
     * 
	 * @var	string
 	*/
	private $_albumId = 'default';
    
    protected function _getPluginName()
    {
        return __CLASS__;
    }
    
    /**
     * Override parent to get real account
    */
    public function login($username, $password)
	{
		$username = preg_replace('#@.*?$#', '', $username);
		return parent::login($username, $password);
	}
    
    protected function _doLogin()
    {
        if( ! $this->get('cookieLogin') OR $this->get('loginTime') + 300 < $_SERVER['REQUEST_TIME']) {
			$this->http->reset();	
			$this->http->setParam(array(
				'accountType' 	=> 'HOSTED_OR_GOOGLE',  
				'Email' 		=> $this->_username,  
				'Passwd' 		=> $this->_password,  
				'source'		=> __CLASS__,  
				'service'		=> 'lh2'
			));
			$this->http->execute('https://www.google.com/accounts/ClientLogin', 'POST');
			
            if($this->http->errors) {
                $this->_throwHttpError(__METHOD__);
            }
            else if(preg_match('#Auth=([a-z0-9_\-]+)#i', $this->http->getResponseText(), $match)) {
				$this->set('cookieLogin', $match[1]);
				$this->set('loginTime', $_SERVER['REQUEST_TIME']);
			}
			else {
				$this->set('cookieLogin', NULL);
				$this->_throwException(':method: Login falied. :res', array(
                    ':method' => __METHOD__,
                    ':res' => $this->http->getResponseText()
                ));
			}
		}
		return TRUE;	
    }

    /**
     * Set size (s[number]) for image after uploaded
     * 
     * @param integer $size
    */    
    public function setSize($size)
	{
		$this->_size = $size;
	}
    
    /**
	 * Set AlbumID. 
	 * You can set AlbumId by an array, then method will get random an id
	 * 
	 * @param string|array 
	*/
	public function setAlbumId($albumIds)
	{
		$this->_albumId = is_array($albumId) ? $albumIds[array_rand($albumIds, 1)] : $albumIds;
	}
    
    protected function _doUpload()
    {
        $this->_checkPermission(__METHOD__);
        
        $this->http->reset();	
		$this->http->setSubmitMultipart('related');
		$this->http->setHeader(array(
			"Authorization: GoogleLogin auth=" . $this->get('cookieLogin'),
			"MIME-Version: 1.0",
		));
		$this->http->setRawPost("Content-Type: application/atom+xml\r\n
			<entry xmlns='http://www.w3.org/2005/Atom'>
			<title>".preg_replace('#\..*?$#i', '', basename($this->_imagePath))."</title>
			<category scheme=\"http://schemas.google.com/g/2005#kind\" term=\"http://schemas.google.com/photos/2007#photo\"/>
			</entry>
		 "); 
		$this->http->setParam(array(
			'data' => '@' . $this->_imagePath
		));
		$this->http->execute('https://picasaweb.google.com/data/feed/api/user/'.$this->_username.'/albumid/' . $this->_albumId);
		
        if($this->http->errors) {
            $this->_throwHttpError(__METHOD__);
        }
        else if($this->http->getResponseStatus() != 201) { //201  Created 
			$this->_throwException(':method: Upload failed. :res', array(
                ':method' => __METHOD__,
                ':res' => $this->http->getResponseText()
            ));
		}
        $result = $this->http->getResponseText();
        preg_match('#<gphoto:width>(\d+)</gphoto:width>#', $result, $match);
		$width = $match[1];
		preg_match('#<gphoto:height>(\d+)</gphoto:height>#', $result, $match);
		$height = $match[1];
		preg_match('#src=\'([^\'"]+)\'#', $result, $match);
		$url = $match[1];
        
		$size = ($this->_size !== NULL) ? $this->_size :  max($width, $height);
        $url = str_replace(basename($url), 's' . $size . '/' . basename($url), $url);
        
		return $url;
    }
    
    protected function _doTransload()
    {
        $this->_throwException(':method: Currently, this plugin doesn\'t support transload image.', array(
            ':method' => __METHOD__
        ));
    }
    
    /**
	 * Delete an album by albumid
	 *
	 * @param string	albumid
     * @return boolean TRUE if album was deleted
     * @throws Exception if Http has an error
	*/
	public function deleteAlbum($albumId)
	{
		$this->checkPermission(__METHOD__);
		
		$this->http->setHeader(array(
			"Authorization: GoogleLogin auth=" . $this->get('cookieLogin'),
			"MIME-Version: 1.0",
			"GData-Version: 3.0",
			"If-Match: *"
		));
		$this->http->execute('https://picasaweb.google.com/data/entry/api/user/' . $this->_username. '/albumid/' . $albumId, 'DELETE');
		if($this->http->errors) {
            $this->_throwHttpError(__METHOD__);
        }
		return ($this->http->getResponseHeaders('status') == 200);
	}
	
	/**
	 * Create new album and return albumId was created
	 *
	 * @param	string	the title of the album
	 * @param	string	access public|private
	 * @param	string	the description of the album
	 * @return	string  AlbumId was created
     * @throws Exception if Http has an error
	*/
	public function addAlbum($title, $access = 'public', $description = '')
	{
		$this->_checkPermission(__METHOD__);
		
		$this->http->setHeader(array(
			"Authorization: GoogleLogin auth=" . $this->get('cookieLogin'),
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
        
		if($this->http->errors) {
            $this->_throwHttpError(__METHOD__);
        }
		if(preg_match('#<id>.+?albumid/(.+?)</id>#i', $this->http->getResponseText(), $match)) {
			return $match[1];
		}
        return FALSE;
	}
    
    /**
     * @param string $method
     * @throws Exception if cookieLogin is empty
     */
    private function _checkPermission($method)
	{
		if( ! $this->get('cookieLogin')) {
			$this->_throwException('You must be logged in before call the method ":method"', array(
                ':method' => $method
            ));
		}
	}
}