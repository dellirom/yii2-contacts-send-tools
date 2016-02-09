<?php

namespace dellirom\com\components;

/**
* Возвращает объект с конфигурационными данными
*	@return object
*/
class Config
{

	public static function get()
	{
		return (object) parse_ini_file( dirname(__DIR__) . DIRECTORY_SEPARATOR . "config.ini" );

	}
}


?>