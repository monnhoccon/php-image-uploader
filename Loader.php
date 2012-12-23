<?php

class Ptc_Loader
{
	public static function load($class)
	{
		$file = strtr($class, '_', DIRECTORY_SEPARATOR) . '.php';
		$file = __DIR__ . DIRECTORY_SEPARATOR . $file;
		require_once $file;
	}
	
	public static function registerAutoLoad()
	{
		spl_autoload_register(array('Ptc_Loader', 'autoLoad'));
	}
	
	public static function autoLoad($class)
	{
		if(substr($class, 0, 4) === 'Ptc_') {
			self::load(substr($class, 4));
		}
	}
}