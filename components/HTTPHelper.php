<?php
//Done
namespace dellirom\com\components;

class HTTPHelper
{
	protected $_data 			= array();	// Храним данные из $_POST масива
	protected $patternIP 	= true;

	public $ips 		 			= array(); 	// Храним все IP клиента в масиве
	public $ip;
	public $url;
	public $host;

	/**
	*	Инициализируем IP.
	* Обрабатываем их и записываем в переменные
	*/
	function __construct()
	{
		$this->ip 	= $this->getIP();
		$this->ips 	= $this->getIPs();
		$this->url 	= $this->getURL();
		$this->host = $this->getHOST();
	}

	/**
	*	Обработка полученных данных.
	*/
	public function clearData($data){
		return trim(strip_tags($data));
	}

	/**
	*	Seter. Принимаем и обрабатываем глобальный массив $_POST. Используем protected $_data
	*/
	public function __SET($name, $data){
		if (is_array($data)) {
			foreach ($data as $name => $value) {
				$this->_data[$name] = $this->clearData($value);
			}
		} else {
			$this->_data[$name] = $this->clearData($data);
		}
	}

	/**
	*	Geter. Возвращает полученные из глобального массива $_POST данные
	*/
	public function __GET($name){
		return $this->_data[$name];
	}

	/**
	* Возвращает url с которого перешли
	* @return String
	*/
	public function getURL()
	{
		if (getenv("HTTP_REFERER")) {
			$url = getenv("REQUEST_URI");
		} elseif(getenv("REQUEST_URI") == "/") {
			$url = getenv("HTTP_HOST");
		} else {
			$url = getenv("HTTP_REFERER");
		}
		return $url;
	}

	/**
	* Возвращает домен сайта с которого перешли
	* @return String
	*/
	public function getHOST()
	{
		return $_SERVER['HTTP_HOST'];
	}

	/**
	* Возвращаем возможные варианты IP
	*	@return Array
	*/
	public function getIPs()
	{
		(isset($_SERVER['HTTP_CLIENT_IP'])) ? $httpClientIp = $_SERVER['HTTP_CLIENT_IP'] : $httpClientIp = Null;
		(isset($_SERVER['REMOTE_ADDR'])) ? $remoteAddr = $_SERVER['REMOTE_ADDR'] : $remoteAddr = Null;
		(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) ? $httpForwardedFor = $_SERVER['HTTP_X_FORWARDED_FOR'] : $httpForwardedFor = Null;
		$allIPs = array( $httpClientIp, $remoteAddr, $httpForwardedFor );
		$IPs 		= $this->checkIPs($allIPs);
		return $IPs;
	}

	/**
	*	Возвращает IP для проверки
	* @return String
	*/
	public  function getIP()
	{
		if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif (!empty($_SERVER['REMOTE_ADDR'])){
			$ip = $_SERVER['REMOTE_ADDR'];
		} else {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		return $ip;
	}

	/**
	* Проверяем масcив с возможными IP.
	*	@return Array
	*/
	public function checkIPs($IPs)
	{
		$IPs 				=  array_unique(array_filter($IPs)); 	// Удаляем из масива $ip ячейки со значением NULL и удаляет одинаковые IP
		$pattern 		= "/([0-9]{1,3}[\.]){3}[0-9]{1,3}/";  // Регулярное выражение для IP
		if (is_array($IPs)) {
			foreach ($IPs as $id => $ip) {
				if (preg_match($pattern, $ip) == 0 )
					$this->patternIP = false;
			}
		}
		return $IPs;
	}

	/**
	* Соответсвие IP c патерном.
	*	@return Boolean
	*/
	public function getPatternIP()
	{
		return $this->patternIP;
	}
}
