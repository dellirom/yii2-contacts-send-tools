<?php

namespace dellirom\com\components;

class HTTPHelper
{

	/**
	*	Настройки работы скрипта. Объявляются в файле congig.ini
	*/
	private $timeDel;
	private $amountEntry;
	private $amountNullIP;
	private $timeClear;
	private $timeCleanLog;
	private $banAmount;

	/**
	*	Пути к файлам для работы скрипта.
	*/
	private $dataPath;
	private $banFile 		 		= "banip.ini"; 		// Файл для забаненых IP
	private $dataFile 	 		= "data.ini"; 		// Файл для проверки количества посещений и период посещений
	private $dataNulIP 	 		= "nullip.ini"; 	// Файл если IP у клиента не существует
	public $chat 						= "chat.ini";
	public $range 					= "range.ini";

	/**
	*	Объявляем свойства для работы
	*/
	private $config;
	private $new 		 				= true; 			// Для проверки нового IP
	private $patternIP 	 		= true; 			// Проверка на сответствие IP регулярному выражению
	private $result 		 		= true; 			// Конечный результат
	private $_data 		 			= array();		// Хранения данных из $_POST масива
	public $ips 		 				= array(); 		// Храним все IP клиента в масиве
	public $ip_str; 											// Храним строку для записи в файл $dataFile в формате ip=timstamp=count
	public $check_ip;											// IP для проверки
	public $ip;

	/**
	*	Получаем несколько IP.
	* Обрабатываем их и записываем в масив для дальнейшего использования.
	* Проверяем IP регулярным выражением.
	*/
	function __construct()
	{
		$this->config 			= Config::get();

		$this->timeDel 	 		= $this->config->timeDel;
		$this->amountEntry  = $this->config->amountEntry;
		$this->amountNullIP = $this->config->amountNullIP;
		$this->timeClear	 	= $this->config->timeClear;
		$this->timeCleanLog = $this->config->timeCleanLog;
		$this->banAmount 	 	= $this->config->banAmount;

		$this->dataPath 		= dirname(__FILE__).DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR;
		$this->banFile 			= $this->dataPath.$this->banFile;
		$this->dataFile			= $this->dataPath.$this->dataFile;
		$this->dataNulIP 		= $this->dataPath.$this->dataNulIP;
		$this->chat 				= $this->dataPath.$this->chat;
		$this->range 				= $this->dataPath.$this->range;

		/*========================================== test ==========================================*/
		 //$this->ip1 		= '192.168.0.1';
		 //$this->ip2 		= '192.168.0.2';
		 //$this->ip3 		= '192.168.0.3';
		 //$this->ip 			= '192.75.82.222';
		 //$this->ips 		= array(
		 //	'192.168.0.1',
		 //	'192.168.0.2',
		 //	'192.168.0.3'
		 //	);
		//$this->name 	= 'dellirom';
		//$this->tel 		= '050 832 88 74';
		//$this->url 		= 'site.com.ua';
		//$this->q_ip 		= '192.168.0.1';
		//$this->q_ips 	= '192.168.25.12;126.21.215.255';
		/*========================================== end test ==========================================*/
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
}
