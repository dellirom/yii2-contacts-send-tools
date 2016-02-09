<?php

namespace dellirom\com\components;

class MailHelper extends PhpMailer
{
	/**
	*	Mails. Кому отправлять письма.
	*/
	protected $config;
	protected $fromMail;
	protected $fromName;
	private $_mails = array();

	/**
	*	Получаем несколько IP.
	* Обрабатываем их и записываем в масив для дальнейшего использования.
	* Проверяем IP регулярным выражением.
	*/
	public function __construct()
	{
		$this->config 	= Config::get();
		$this->_mails 	= $this->config->mails;

		// Выбор адресатов в зависимости от условий
		foreach ($this->config->mails as $name => $mail) {
			if ($this->name == $name) {
				$this->clearMails();
				$this->addMail($mail);
			} else {
				$this->clearMails();
				$this->addMail($mail);
			}
		}
	}

	/**
	*	Записываем email в массив для отправки
	*/
	public function addMail($address, $name = "")
	{
		$cur = count($this->_mails);
		$this->_mails[$cur] = trim($address);
	}

	/**
	* Возвращает  @var array @return mails
	*/
	public function getMails()
	{
		return $this->_mails;
	}

	/**
	*	Отправка писем.
	*/
	protected function sendMail($subject, $message)
	{
		$this->IsMAIL();
		$this->CharSet  = "utf-8";
		$this->From     = $this->config->fromMail;
		$this->FromName = $this->config->fromName;
		$this->Host     = "localhost";
		$this->Subject  = $subject;
		foreach ($this->_mails as $mail) {
			$this->AddAddress ($mail);
		}
		$this->IsHTML(0);
		$this->Body = $message;
		$this->Send();
		$this->ClearAddresses();
	}

	/**
	*	Очищаем список e-mail
	*/
	public function clearMails()
	{
		$this->_mails = array();
	}

	/**
	*	Отправка email сообщения с информацией, на указанные адресса.
	*/
	public function sendClientIfo($lastInsertID, $roistat = false)
	{
		// Подготовка шаблона сообщения с информациией о клиенте
		$time 		= date("d.m.y_G:i");
		$message 	= "Время $time \n\n Заявка была заполнена на сайте \n" . $this->url . "\n\n";
		$message .= "Имя: $this->name\n";
		$message .= "Телефон: $this->tel\n";
		$message .= "\n\nCcылка в админку:\n";
		if (isset($lastInsertID) && !empty($lastInsertID)){
			$message .= $this->config->contactsUrl . $lastInsertID;
		}
		if($roistat !== false ){
			$message .= "\n\nRoistat: " . $roistat;
		}
		$message .= "\n\n\nВсе варианты ip:\n";
		foreach ($this->ips as $key => $value) {
			$message .= $value."\n";
		}
		$message .= "\n http://sypexgeo.net/ru/demo/";

		$this->sendMail($time, $message);
	}

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

}
