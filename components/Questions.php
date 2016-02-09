<?php
class Questions extends MailHelper{

	/**
	*	Отправка вопросов заполненых клиентом
	*/
	public function sendQuestions($data)
	{
		$this->clearMails();
		if ($this->name == "may"){
			$this->clearMails();
			$this->addMail('1@gslots.net');
		} elseif ($this->name == "dellirom"){
			$this->clearMails();
			$this->addMail('dellirom@gmail.com');
		} else {
			$this->clearMails();
			$this->addMail('1@gslots.net');
			$this->addMail('kingogurcov@gmail.com');
			$this->addMail('go@gslots.net');
		}

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
		//var_dump($data);
		$this->mail($subject, $message);
	}
}
 ?>