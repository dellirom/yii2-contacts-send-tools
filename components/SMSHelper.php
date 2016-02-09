<?php

namespace dellirom\com\components;

class SMSHelper extends MailHelper
{
	/**
	*  Отправка сообщения для СМС оповещения менеджеров.
	*/
	protected $smsSubject; #"Gslots;380997589428;H6S9j4Byex9;127590"
	protected $smsMail; #'380997589428@mail.smsukraine.com.ua'
	protected $config;

	public function __constract()
	{
		$config 					= Config::get;
		$this->smsSubject = $config->SMS_Subject;
		$this->smsMail 		= $config->SMS_Mail;
	}

	public function sendSMS()
	{

		if ($this->name != "may" && $this->name != "dellirom"){

			$message="$this->name\r\n";
			if ($this->tel) {$message.="$this->tel\r\n";}
			//if ($this->email) {$message.="$this->email\r\n";}
			$message = substr($message, 0, 60);

			//$subject  = "Gslots;380997589428;H6S9j4Byex9;127590";

			$this->clearMails();
			$this->addMail($this->smsMail);
			$this->mail($this->smsSubject, $message);
		}
	}
}

?>