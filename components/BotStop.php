<?php

namespace dellirom\com\components;

/**
* Class Mail Send
*
*	Защита от спама. (Начальное ТЗ)
*	Блокировать письма которые идут с одного ip не более $amountEntry писем в $timeDel минут.
*	Смотрим в файле существование такого ip и время когда с данного ip была оставлена заявка.
*	Если такого ip нет то записываем его и записываем время когда была данная заявка оставленна.
* Забаненые IP записывать в файл ban_ip.ini. Для проверки вытягивать из файла в масив.
* Через foreach проверять наличие ip пользовтелья в файле ban_ip.ini. Если совпал возвращать false. Если в файле ip нет возвращать true.
* В файл ban_ip.ini забаненые ip можно вносить вручную, а можно автоматически записывать при проверке методом botStop
*/
class BotStop
{
	/**
	*	Seter. Для приема и обработки глобального масива $_POST через private $_data
	*/
	public function __SET($name,$data)
	{
		if (is_array($data)) {
			foreach ($data as $name => $value) {
				$this->_data[$name] = $value;
			}
		}else{
			$this->_data[$name] = $data;
		}
	}

	/**
	*	Geter. Для выдачи обработаных данных
	*/
	public function __GET($name)
	{
		return $this->_data[$name];
	}

	/**
	*	Возвращает результат соответствия IP клиента с регулярным выражением.
		*/
	public function getPatternIP()
	{
		return $this->patternIP;
	}

	public function addPatternIP($ips){

		$this->ips = unserialize($ips);

		$this->ips =  array_unique(array_filter($this->ips)); // Удаляем из масива $this->ips ячейки со значением NULL и удаляет одинаковые IP

		$pattern = "/([0-9]{1,3}[\.]){3}[0-9]{1,3}/"; // Регулярное выражение для IP
		if (is_array($this->ips)) {
			foreach ($this->ips as $id => $ip) {
				//возвращает 1, если параметр pattern соответствует переданному параметру ip, 0 если нет, или FALSE в случае ошибки.
				if (preg_match($pattern, $ip) == 0 )
					$this->patternIP = false;
			}
		}
	}

	/**
	*	BotStop.
	*/
	public function botStop()
	{
		if (!$this->getPatternIP() || $this->ip == "unknown" || $this->ip == "unknown,unknown" || empty($this->ips)) {
			$fileNull = file_get_contents($this->dataNullIP);
			$fileNullData = explode('=', $fileNull);
			if ($fileNullData[1]<$this->amountNullIP) {
				if ($fileNullData[0] < time() - $this->timeClear ) { // Если прошла минута с момент отправки письма
					file_put_contents($this->dataNullIP, time()."=1"); // Очищаем файл
					$this->result = true;// Отправляем письмо
				} elseif ($fileNullData[0] >= time() - $this->timeClear) { // Если минута не прошла
					$fileNullData[1] ++;
					file_put_contents($this->dataNullIP, $fileNullData[0]."=".$fileNullData[1]);
					$this->result = true; // Отправляем письмо
				}
			} elseif ($fileNullData[1] >= $this->amountNullIP) {
				if ($fileNullData[0] < time() - $this->timeClear ) { // Если прошла минута с момент отправки письма
					file_put_contents($this->dataNullIP, time()."=1"); // Очищаем файл
					$this->return = true; // Отправляем письмо
				} elseif ($fileNullData[0] >= time() - $this->timeClear) { // Если минута не прошла
					$this->result = false; // Письмо не отправляем
				}
			}
		} else {
			$file = file($this->dataFile);
			foreach ($this->ips as $key => $checkIP) {
				foreach ($file as $id => $string) {
					$str = explode("=", $string);
					if ($str[1] <= time() - $this->timeDel) {  // Делаем очистку $this->dataFile если прошло больше чем $this->timeDel минут
						unset($file[$id]);
						$refile = implode("", $file);
						file_put_contents($this->dataFile, $refile);
					}
					if ($str[0] == $checkIP) { // Проверяет есть ли в файле такой же ip
						$this->new = false; // Возвращаем false чтобы не записывать в файл. Такой ip уже есть.
						if (!isset($str[2])) { // Если флага еще нет(это вторая заявка с данного ip) то добавляем флаг 1
							$file[$id] = substr($file[$id], 0, -1); // Обрезаем перенос на новую строку \n
							$file[$id] = $file[$id]."=1\n"; // Добавляем количество запросов к странице с этого ip
						}elseif($str[2] >= 1 && $str[1] >= time() - $this->timeDel){
							$flag = (int)($str[2]); // Прибавляем к флагу единицу каждый раз
							$flag ++;  // Добавляем флаг ++1
							if ($flag <= 10) {
								$file[$id] = substr($file[$id], 0, strlen($file[$id]) - 2)."$flag\n"; // Подготавливаем место под цифру флага от 1 до 9
							} elseif ($flag <= $this->banAmount && $flag > 10) {
								$file[$id] = substr($file[$id], 0, strlen($file[$id]) - 3)."$flag\n"; // Подготавливаем место под цифру флага от 10 до 99
							}
							if ($str[2] >= $this->amountEntry) { // Если флаг больше $this->amountEntry возвращаем false (не отпраавляем письмо)
								$this->result = false;
								if ($str[2] >= $this->banAmount) { // Если флаг больше $this->banAmount тогда запускаем функцию Бан возвращаем false (не отпраавляем письмо)
									$this->banIP($str[0]);
								}
							}
						}
						$refile = implode("", $file);
						file_put_contents($this->dataFile, $refile); // Перезаписываем $this->dataFile после изменений
					}
				}
				if ($this->new) {
					$this->ip_str = $checkIP."=".time()."\n"; // Подготавливаем строку для записи в файл в нужном формате
					file_put_contents($this->dataFile, $this->ip_str, FILE_APPEND); // Записываем в файл
				}
			}
		}
		return $this->result;
	}

	/**
	*	IP для проверки
	*/
	public  function getIP(){
		return $this->ip;
	}

	/**
	*	Баним IP, если зпросов было больше чем $amountEntry
		*/
	public function banIP($banIP)
	{
		if (!$this->checkBanIP($banIP))
			return false;
		file_put_contents($this->banFile, $banIP."\n", FILE_APPEND);
	}

	public function checkRange($ip){
		$rangeIps = file($this->range);
		foreach ($rangeIps as $rangeIp) {
			//$rangeIp = trim(substr($rangeIp, 0, -1)); // Обрезаем перенос на новую строку \n
			$result = strpos($ip, $rangeIp);
			if ($result === false) {
				return false; // если не совпадает с диапазоном
			} else {
				return true; // если совпадает с диапазоном
			}
		}

	}
	/**
	*	Проверка на наявность IP клиента в файле BAN_FILE
	*/
	public function checkBanIP($banIP)
	{
		if ($this->checkRange($banIP)) {
			return false;
		} else {
			$file= file($this->banFile);
			foreach ($file as $id => $ip) {
				$ip = trim(substr($ip, 0, -1)); // Обрезаем перенос на новую строку \n
				if ($banIP == $ip ) {
					return false; // Не записываем  забаненый IP, если он там уже есть
				}
			}
			//return $ip;
			return true;
		}
	}

	/**
	*	Проверка перед отправкой письма
	*/
	public  function checkBeforeSend()
	{
		if ($this->ip != "unknown, unknown" && $this->botStop() && $this->checkBanIP($this->ip))
			return true;
		return false;
	}
}
