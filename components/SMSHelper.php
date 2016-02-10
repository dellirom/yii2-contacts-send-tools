<?php

namespace dellirom\com\components;

class SMSHelper extends HTTPHelper
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
		$this->ips 				= $this->getIPs();
		$this->ip					= $this->getIP();
		$this->host 			= $this->getHOST();

		if ($this->name != "may" && $this->name != "dellirom"){
			$message="$this->name\r\n";
			if ($this->tel) {$message.="$this->tel\r\n";}
			$message 	= substr($message, 0, 60);
			$mail 		= new MailHelper;
			$mail->clearMails();
			$mail->addMail($this->smsMail);
			$mail->mailSend($this->smsSubject, $message, false);
		}
		return true;
	}

}

?>