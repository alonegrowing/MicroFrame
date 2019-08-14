<?php
namespace MicroFrame\Base;

/**
 * Exception represents a generic exception for all purposes.
 *
 * @author info <alonegrowing@gmail.com>
 * @version v0.1.0
 */
class Exception extends \Exception 
{
	/**
	 * @return string the user-friendly name of this exception
	 */
	public function getName() 
	{
		return 'Exception';
	}
}