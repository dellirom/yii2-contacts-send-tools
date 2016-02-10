<?php
//Done
namespace dellirom\com\components;

class Config
{
	/**
	* Возвращает данные из конфигурационного файла config.ini
	* @return Object
	*/
	public static function get()
	{
		return (object) parse_ini_file( dirname(__DIR__) . DIRECTORY_SEPARATOR . "config.ini" );

	}
}


?>