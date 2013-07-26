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
namespace ChipVN; 

class Exception extends \Exception
{
	public function __construct($message, $params = array(), $code = 0, Exception $previous = null)
	{
		$message = strtr($message, $params);
		
		parent::__construct($message, $code, $previous);
	}
}