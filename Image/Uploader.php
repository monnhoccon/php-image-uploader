<?php
/** 
 * ChipVN Library
 * 
 * @package		ChipVN
 * @author		Phan Thanh Cong <ptcong90 at gmail dot com>
 * @copright	chiplove.9xpro aka ptcong90
 * @version		2.1
 * @release		Jul 27, 2013
*/
namespace ChipVN;

class Image_Uploader
{
	/**
	 * Load a plugin for uploading
	 * 
	 * @param string plugin name like "Picasa", "Imageshack", "Imgur" or empty or NULL
	 *	If plugin name is not empty, the method will make a REMOTE uploader
	 * @return object Uploader instance like ChipVN_Image_Uploader_Picasa
	 * @throws Exception if cannot load the plugin
	*/
	public static function factory($pluginName = NULL)
	{
        if(empty($pluginName)) {
            $pluginName = 'Local';
        }
        else {
            $pluginName = 'Remote_' . ucfirst($pluginName);
            if( ! class_exists('\ChipVN\Image_Uploader\Remote', FALSE)) {
                Loader::load(__CLASS__ . '\Remote');	
            }
        }
        $className = __CLASS__ . '\\' . $pluginName;
        Loader::load($className);	
        
		return new $className;
	}
}