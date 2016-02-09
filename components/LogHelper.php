<?php

namespace dellirom\com\components;

class HTTPHelper
{
	private $dataWrite 	 		= "log.ini"; 			// log файл. Запись всей информации.
	private $erroLog				= "error.ini";		// Файл для отладки.

	public function __constract()
	{
		$this->dataPath 			= dirname(__FILE__).DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR;
		$this->dataWrite 			= $this->dataPath.$this->dataWrite;
		$this->erroLog 				= $this->dataPath.$this->erroLog;
	}

	/**
	* Записываем в файл log.ini. Очищаем файл каждые timeCleanLog суток. Отправляем содиржимое файла на email перед очисткой.
	*/
	public function logData()
	{
		$file 			= file($this->dataWrite);

		$this->clearMails();
		$this->addMail('1@gslots.net');
		$this->addMail('dellirom@gmail.com');

		$txt 				= $this->url.";";
		$dtime 			= date("d.m.y_G:i");
		$txt 				.= $dtime.";";
		$txt 				.= $this->name.";";
		$txt 				.= $this->tel.";";
		foreach ($this->ips as $key => $value) {
			$txt 			.= $value.";";
		}
		$txt 				.= "\n";
		if (isset($file[0]) && !empty($file[0])) {
			$firstLine 	=	explode(";", $file[0]); // Вытягиваем первую строку файла log.ini
			$date = explode(".", str_replace('_', '.', $firstLine[1])); // Вытягиваем дату. Преобразуем к удобному формату. Разбиваем на отдельные части (год, месяц ...)
			$time = explode(":", $date[3]); // Разбиваем врем на отдельные части
			$unixTime = mktime($time[0], $time[1], 0, $date[1], $date[0], $date[2]); // Переводим в UNIXTIME
			$period = $this->timeCleanLog * 24  * 60 * 60; // Период за через который будем очищать log файл
			$timeNow = time(); // Текущее время
			if($timeNow > ($unixTime + $period)){ // Очищаем log файл если прошло больше чем $timeCleanLog суток
				// Отправляем письмо с содержанием log файла перед его очисткой.
				$subject = $dtime." - Log файл  - ".$_SERVER['HTTP_HOST'];
				$message = "Log файл очистился за период: ".$this->timeCleanLog." суток \n\n";
				$message .= "Содержимое файла:\n\n";
				foreach ($file as $key => $value) {
					$message .= "\t$value \n";
				}
				$this->mail($subject, $message, 'log@gslots.net', 'log');
				file_put_contents($this->dataWrite, ""); // Очищаем файл dataWrite (log.ini) после отправки информации на email.
			}
		}
		file_put_contents($this->dataWrite, $txt, FILE_APPEND); // Записываем информацию в файл dataWrite (log.ini)
	}

	private function writeFile($fileName, $content, $const = FILE_APPEND){
		file_put_contents($fileName, $content, $const);
	}

	/**
	*	Скрипт для удалленной отладки и записи данных в файл $this->errrLog
	*/
	public function writeError($var)
	{
		$content = '';
		$date = date("m.d.y H:i:s");
		$content .= $date." - ";

		if(is_array($var)){
			//$content .= implode(";", $var);
			foreach ($var as $value) {
				if (is_array($value)) {
					foreach ($value as $value2) {
							$content .= $value2.";";
					}
				} else {
					$content .= $value.";";
				}
			}
			$content .= "\n";
			$this->writeFile($this->erroLog, $content);
		} else {
			$content .= $var."\n";
			$this->writeFile($this->erroLog, $content);
		}
	}
}

?>