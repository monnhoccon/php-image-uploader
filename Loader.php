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

class Loader
{
    /**
     * Load class name without ChipVN prefix and .php extension
     * 
     * @param string class name
     * @return void
     * @throws Exception if file is not exist.
     */
    public static function load($class)
    {
		$file = dirname(__FILE__) . DIRECTORY_SEPARATOR . strtr($class, array(
            'ChipVN'    => '',
            '\\'        => '/',
            '_'         => DIRECTORY_SEPARATOR,
            '.php'      => '',
        )) . '.php';

        if (!is_file($file)) {
            if (!class_exists('\ChipVN\Exception', FALSE)) {
                require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Exception.php';
            }
            throw new Exception(':method: File ":file" not found', array(
                ':method' => __METHOD__,
                ':file' => $file
            ));
        }
        require_once $file;
    }

    public static function registerAutoLoad()
    {
        spl_autoload_register(array(__CLASS__, 'autoLoad'));
    }

    public static function autoLoad($class)
    {
        if (strpos($class, 'ChipVN') === 0) {
            self::load($class);
        }
    }

}

