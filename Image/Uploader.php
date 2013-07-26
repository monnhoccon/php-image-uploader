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
        // debuging
        //ini_set('display_errors', 1);
        
		$pluginName = empty($pluginName) ? 'Local' : 'Remote_' . ucfirst($pluginName);
		
		$className = __CLASS__ . '\\' . $pluginName;	
        
		Loader::load($className);	
        
		return new $className;
	}
}