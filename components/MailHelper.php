<?php
//Done
namespace dellirom\com\components;

class MailHelper extends HTTPHelper
{
	/**
	*	Mails. Кому отправлять письма.
	*/
	public $config;
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
		$this->ips 			= $this->getIPs();
		$this->ip				= $this->getIP();
		$this->host 		= $this->getHOST();
	}

	/**
	*	Записываем email в массив для отправки
	*	@return Array
	*/
	public function addMails()
	{
		//Выбор адресатов в зависимости от условий
		$this->clearMails();
		foreach ($this->config->mails as $name => $mail) {
			if ($this->name == $name  && !empty($name)){
				$this->_mails[] = $mail;
			}
		}
		if(empty($this->_mails)){
			foreach ($this->config->mails as $name => $mail) {
				foreach ($this->config->mailExc as $exc) {
					if($name !== $exc){
						$this->_mails[] = $mail;
					}
				}
			}
		}
		return $this->_mails;
	}

	public function addMail($mail)
	{
		$this->_mails[] = $mail;
	}

	/**
	* Возвращает массив mails
	*  @return Array
	*/
	public function getMails()
	{
		return $this->_mails;
	}

	/**
	*	Очищаем список e-mail
	* @return Boolean
	*/
	public function clearMails()
	{
		$this->_mails = array();
		return true;
	}

	/**
	*	Отправка писем.
	* @return Boolean
	*/
	public function sendMail($subject, $message, $allMails = true)
	{
		if ($allMails ==  true) {
			$this->addMails();
		}
		$mail 					= new PhpMailer;
		$mail->IsMAIL();
		$mail->CharSet  = "utf-8";
		$mail->From     = $this->config->fromMail;
		$mail->FromName = $this->config->fromName;
		$mail->Host     = "localhost";
		$mail->Subject  = $subject;
		foreach ($this->_mails as $to) {
			$mail->AddAddress($to);
		}
		$mail->IsHTML(0);
		$mail->Body = $message;
		$mail->Send();
		$mail->ClearAddresses();
		return true;
	}

	/**
	*	Отправка email сообщения с информацией, на указанные адресса.
	* @return Boolean
	*/
	public function sendClientIfo($lastInsertID = false, $roistat = false)
	{
		// Подготовка шаблона сообщения с информациией о клиенте
		$time 		= date("d.m.y_G:i");
		$message 	= "Время $time \n\n Заявка была заполнена на сайте \n" . $this->url . "\n\n";
		$message .= "Имя: $this->name\n";
		$message .= "Телефон: $this->tel\n";

		if (isset($lastInsertID) && !empty($lastInsertID || $lastInsertID != false)){
			$message .= "\n\nCcылка в админку:\n";
			$message .= $this->config->contactsUrl . $lastInsertID;
		}
		if($roistat !== false ){
			$message .= "\n\nRoistat: " . $roistat . "\n";
		}
		$message .= "\n\nВсе варианты ip:\n";
		foreach ($this->ips as $key => $value) {
			$message .= $value."\n";
		}
		$message .= "\nhttp://sypexgeo.net/ru/demo/";

		$this->sendMail($time, $message);
		return true;
	}

}
