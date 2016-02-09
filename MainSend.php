<?php

namespace dellirom\com;

//use dellirom\com\MailSend;

/**
 * This is just an example.
 */
class MainSend extends \yii\base\Widget
{
	public function initClass()
	{
		//return new MailSend();
	}


	public function initConfig()
	{
		 return (object) parse_ini_file( __DIR__ . DIRECTORY_SEPARATOR . "config.ini" );
	}

	public function run()
	{
		echo "<pre style='background:#FFF'>";
		$mail = new \dellirom\com\components\MailHelper;
		var_dump($mail);
		//$config = $this->initConfig();
		//$AmoCRM = new \dellirom\com\components\AmoCRM();
		//echo $config->SMS_Mail;
		//echo $config->SMS_Subject;
		//var_dump($AmoCRM->listData());
		//var_dump($this->initConfig());
		echo "</pre>";
	}
}
