<?php

namespace dellirom\com;

class MainSend extends \yii\base\Widget
{

	public function run()
	{
		echo "<pre style='background:#FFF'>";

		$_POST['name'] = 'dellirom';
		$_POST['tel'] = '0508328874';

		$class = new \dellirom\com\components\LogHelper;
		//$class->
		$class->$_data = $_POST;

		//var_dump($class);
		var_dump($class->writeError('afdsgasdgsadg'));

		//$class->url = 'host';
		//var_dump($class);
		//var_dump($class->sendMail('dellirom', '0508328874'));
		//var_dump($class->sendClientIfo());
		//var_dump($class->addMail());

		//var_dump($class->sendClientIfo());
		//var_dump($class->banIP("125.152.115.5"));
		//$config = $this->initConfig();
		//$AmoCRM = new \dellirom\com\components\AmoCRM();
		//echo $config->SMS_Mail;
		//echo $config->SMS_Subject;
		//var_dump($AmoCRM->listData());
		//var_dump($this->initConfig());
		echo "</pre>";
	}
}
