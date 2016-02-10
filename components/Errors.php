<?php
//Done
namespace dellirom\com\components;

class Errors
{
	/**
	* Включает вывод ошибок
	*/
	public static function show()
	{
		$this->config = Config::get();

		if ($this->config->ShowErrors == 1) {
			ini_set('error_reporting', E_ALL);
			ini_set('display_errors', 1);
			ini_set('display_startup_errors', 1);
		}
	}
}
?>