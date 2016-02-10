<?php
//Done
namespace dellirom\com\components;

class Questions extends HTTPHelper{

	/**
	*	Отправка вопросов заполненых клиентом
	*/
	public function sendQuestions($data)
	{
		$mail = new MailHelper;

		$this->ips 			= $this->getIPs();
		$this->ip				= $this->getIP();
		$this->host 		= $this->getHOST();

		// Заголовок письма с датой
		$subject = date("d.m.y_G:i", strtotime($data['date']));
		// Подготовка тела письма
		$message = '';
		$message .= "Имя: $this->name\n\n";
		foreach ($data as $key => $value) {
			if (is_array($value)){
				$message .=	"Вопрос $key: ";
				$message .=  $value['question']."\n";
				$message .=  $value['answer']."\n";
			}
		}
		$message .= "\n";
		$message .= "ID клиента: ".$data['id'];
		$message .="\n\n\nВопросы были заполнены на сайте - $this->q_url";
		$message.= "\n\n\nВсе варианты ip: $this->q_ip\n";
		$message.= "\n http://sypexgeo.net/ru/demo/";
		$mail->mailSend($subject, $message);
	}
}
 ?>