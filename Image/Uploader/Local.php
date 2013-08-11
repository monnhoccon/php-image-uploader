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
use ChipVN\Exception;

class Local
{
    /**
     * Upload an image to local server then return new image path after upload success
     * 
     * @param array $file [tmp_name, name, error, type, size] 
     * @param string image path for saving (this may constain filename)
     * @param string image filename for saving
     * @return string Image path after uploaded
     * @throws Exception If upload failed
     */
    public function upload($file, $newImagePath = '.', $newImageName = NULL)
    {
        if (!empty($file['error'])) {
            $this->_throwException(':method: Upload error. Number: :number', array(
                ':method' => __METHOD__,
                ':number' => $file['error']
            ));
        }
        if (getimagesize($file['tmp_name']) === FALSE) {
            $this->_throwException(':method: The file is not an image file.', array(
                ':method' => __METHOD__
            ));
        }
        $fileInfo = new \SplFileInfo($newImagePath);

        if (!$fileInfo->getRealPath() OR !$fileInfo->isDir()) {
            $this->_throwException(':method: The ":dir" must be a directory.', array(
                ':method' => __METHOD__,
                ':dir' => $newImagePath
            ));
        }
        $defaultExtension = '.jpg';
        if (empty($newImageName)) {
            if (!in_array($fileInfo->getBasename(), array('.', '..')) AND $fileInfo->getExtension()) {
                $newImageName = $fileInfo->getBasename();
            } else {
                $newImageName = uniqid() . $defaultExtension;
            }
        }
        if (!$fileInfo->isWritable()) {
            $this->_throwException(':method: Directory ":dir" is not writeable.', array(
                ':method' => __METHOD__,
                ':dir' => $fileInfo->getRealPath()
            ));
        }
        $destination = $fileInfo->getRealPath() . DIRECTORY_SEPARATOR . $newImageName;
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return $destination;
        } else {
            $this->_throwException(':method: Cannot move uploaded file to :path', array(
                ':method' => __METHOD__,
                ':path' => $fileInfo->getRealPath()
            ));
        }
    }

    /**
     * 
     */
    public function login()
    {
        $this->_throwException(':method: Local uploader does not need to logged in.', array(
            ':method' => __METHOD__
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

}

