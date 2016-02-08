<?php
/**
* Class Mail Send
*
*	Защита от спама. (Начальное ТЗ)
*	Блокировать письма которые идут с одного ip не более $amountEntry писем в $timeDel минут.
*	Смотрим в файле существование такого ip и время когда с данного ip была оставлена заявка.
*	Если такого ip нет то записываем его и записываем время когда была данная заявка оставленна.
* Забаненые IP записывать в файл ban_ip.ini. Для проверки вытягивать из файла в масив.
* Через foreach проверять наличие ip пользовтелья в файле ban_ip.ini. Если совпал возвращать false. Если в файле ip нет возвращать true.
* В файл ban_ip.ini забаненые ip можно вносить вручную, а можно автоматически записывать при проверке методом botStop
*/
class MailSend extends CApplicationComponent
{

	/**
	*	Пути к файлам для работы скрипта.
	*/
	private $dataPath;
	private $banFile 		 		= "banip.ini"; 		// Файл для забаненых IP
	private $dataFile 	 		= "data.ini"; 		// Файл для проверки количества посещений и период посещений
	private $dataNulIP 	 		= "nullip.ini"; 	// Файл если IP у клиента не существует
	private $dataWrite 	 		= "log.ini"; 			// log файл. Запись всей информации.
	private $erroLog				= "error.ini";		// Файл для отладки.
	public $chat 						= "chat.ini";
	public $range 					= "range.ini";
	/**
	*	Mails. Кому отправлять письма.
	*/
	private $_mails = array();
	/**
	*	Настройки работы скрипта
	*/
	private $timeDel 	 			= 500; 			// Время до очистки файла $dataFile
	private $amountEntry  	= 5; 				// Количество записей в файл $dataFile  c одного IP
	private $amountNullIP 	= 15;				// Количество записей в файл за время $timeClear
	private $timeClear	 		= 60;				// Время до очистки файла $dataNullIP
	private $timeCleanLog 	= 2;				// Время для очищения $dataWrite (изменяется в сутках)
	private $banAmount 	 		= 5; 				// Количество записей в файл $dataFile  c одного IP

	/**
	*	Объявляем свойства для работы
	*/
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
		$this->dataPath 			= dirname(__FILE__).DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR;
		$this->banFile 				= $this->dataPath.$this->banFile;
		$this->dataFile				= $this->dataPath.$this->dataFile;
		$this->dataNulIP 			= $this->dataPath.$this->dataNulIP;
		$this->dataWrite 			= $this->dataPath.$this->dataWrite;
		$this->erroLog 				= $this->dataPath.$this->erroLog;
		$this->chat 					= $this->dataPath.$this->chat;
		$this->range 					= $this->dataPath.$this->range;

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
	private function writeFile($fileName, $content, $const = FILE_APPEND){
		file_put_contents($fileName, $content, $const);
	}
	/**
	*	Скрипт для удалленной отладки и записи данных в файл $this->errrLog
	*/
	public function writeError($var)
	{
		$content = '';
		$date = date("m.d.y H:i:s");
		$content .= $date." - ";

		if(is_array($var)){
			//$content .= implode(";", $var);
			foreach ($var as $value) {
				if (is_array($value)) {
					foreach ($value as $value2) {
							$content .= $value2.";";
					}
				} else {
					$content .= $value.";";
				}
			}
			$content .= "\n";
			$this->writeFile($this->erroLog, $content);
		} else {
			$content .= $var."\n";
			$this->writeFile($this->erroLog, $content);
		}
	}
	/**
	*	Добавляем email в список для отправки
	*/
	public function addMail($address, $name = "")
	{
		$cur = count($this->_mails);
		$this->_mails[$cur] = trim($address);
	}
	/**
	*	Очищаем список e-mail
	*/
	public function clearMails()
	{
		$this->_mails = array();
	}

	public function getMails()
	{
		return $this->_mails;
	}
	/**
	*	Отправка писем.
	*/
	protected function mail($subject, $message, $fromMail = '1@gslots.net', $fromName = 'Globalslots')
	{
		require_once (dirname(__FILE__).DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'class.phpmailer.php');

		$mailLog = new phpmailer();

		$mailLog->IsMAIL();
		$mailLog->CharSet  = "utf-8";
		$mailLog->From     = $fromMail;
		$mailLog->FromName = $fromName;
		$mailLog->Host     = "localhost";
		$mailLog->Subject  = $subject;
		foreach ($this->_mails as $mail) {
			$mailLog->AddAddress ($mail);
		}
		//$mailLog->AddAddress('1@gslots.net');
		//$mailLog->AddAddress('dellirom@gmail.com');
		$mailLog->IsHTML(0);
		$mailLog->Body = $message;
		$mailLog->Send();
		$mailLog->ClearAddresses();
	}
	/**
	* Записываем в файл log.ini. Очищаем файл каждые timeCleanLog суток. Отправляем содиржимое файла на email перед очисткой.
	*/
	public function logData()
	{
		$file 			= file($this->dataWrite);

		$this->clearMails();
		$this->addMail('1@gslots.net');
		$this->addMail('dellirom@gmail.com');

		$txt 				= $this->url.";";
		$dtime 			= date("d.m.y_G:i");
		$txt 				.= $dtime.";";
		$txt 				.= $this->name.";";
		$txt 				.= $this->tel.";";
		foreach ($this->ips as $key => $value) {
			$txt 			.= $value.";";
		}
		$txt 				.= "\n";
		if (isset($file[0]) && !empty($file[0])) {
			$firstLine 	=	explode(";", $file[0]); // Вытягиваем первую строку файла log.ini
			$date = explode(".", str_replace('_', '.', $firstLine[1])); // Вытягиваем дату. Преобразуем к удобному формату. Разбиваем на отдельные части (год, месяц ...)
			$time = explode(":", $date[3]); // Разбиваем врем на отдельные части
			$unixTime = mktime($time[0], $time[1], 0, $date[1], $date[0], $date[2]); // Переводим в UNIXTIME
			$period = $this->timeCleanLog * 24  * 60 * 60; // Период за через который будем очищать log файл
			$timeNow = time(); // Текущее время
			if($timeNow > ($unixTime + $period)){ // Очищаем log файл если прошло больше чем $timeCleanLog суток
				// Отправляем письмо с содержанием log файла перед его очисткой.
				$subject = $dtime." - Log файл  - ".$_SERVER['HTTP_HOST'];
				$message = "Log файл очистился за период: ".$this->timeCleanLog." суток \n\n";
				$message .= "Содержимое файла:\n\n";
				foreach ($file as $key => $value) {
					$message .= "\t$value \n";
				}
				$this->mail($subject, $message, 'log@gslots.net', 'log');
				file_put_contents($this->dataWrite, ""); // Очищаем файл dataWrite (log.ini) после отправки информации на email.
			}
		}
		file_put_contents($this->dataWrite, $txt, FILE_APPEND); // Записываем информацию в файл dataWrite (log.ini)
	}
	/**
	*	Отправка email сообщения с информацие, на указанные адресса.
	*/
	public function sendClientIfo($lastInsertID, $roistat = false)
	{
		// Выбор адресатов в зависимости от условий
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

		// Подготовка шаблона сообщения с информациией о клиенте
		$time = date("d.m.y_G:i");
		$message ="Время $time \n\n Заявка была заполнена на сайте \n".$this->url."\n\n";
		//if (isset($this->subject)){$message.="Форма: $this->subject\n";}
		$message.="Имя: $this->name\n";
		$message.="Телефон: $this->tel\n";
		//if ($this->email!=''){$message.="Email: $this->email\n";}
		//if (isset($this->formid)){$message.="Form id: $this->formid\n";}
		$message .= "\n\nCcылка в админку:\n";
		if (isset($lastInsertID) && !empty($lastInsertID)){
			$message .= "http://dev-admin.info/admin/info/update/id/".$lastInsertID;
		}
		if($roistat !== false ){
			$message .= "\n\nRoistat: " . $roistat;
		}
		$message .= "\n\n\nВсе варианты ip:\n";
		foreach ($this->ips as $key => $value) {
			$message .= $value."\n";
		}
		$message.= "\n http://sypexgeo.net/ru/demo/";

		$this->mail($time, $message);
	}
	/**
	*	 Отправка сообщения для СМС оповещения менеджеров.
	*/
	public function sendSMS()
	{
		if ($this->name != "may" && $this->name != "dellirom"){

			$message="$this->name\r\n";
			if ($this->tel) {$message.="$this->tel\r\n";}
			//if ($this->email) {$message.="$this->email\r\n";}
			$message = substr($message, 0, 60);

			$subject  = "Gslots;380997589428;H6S9j4Byex9;127590";

			$this->clearMails();
			$this->addMail('380997589428@mail.smsukraine.com.ua');
			$this->mail($subject, $message);
		}
	}
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


	/**
	*	Отправка данных в Amo CRM
	*/
	public function AmoCRM()
	{
		require_once __DIR__ . DIRECTORY_SEPARATOR . 'components/class.amocrm.php';
		return new AmoCRM();
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

	/**
	*	BotStop.
	*/
	public function botStop()
	{
		if (!$this->getPatternIP() || $this->ip == "unknown" || $this->ip == "unknown,unknown" || empty($this->ips)) {
			$fileNull = file_get_contents($this->dataNullIP);
			$fileNullData = explode('=', $fileNull);
			if ($fileNullData[1]<$this->amountNullIP) {
				if ($fileNullData[0] < time() - $this->timeClear ) { // Если прошла минута с момент отправки письма
					file_put_contents($this->dataNullIP, time()."=1"); // Очищаем файл
					$this->result = true;// Отправляем письмо
				} elseif ($fileNullData[0] >= time() - $this->timeClear) { // Если минута не прошла
					$fileNullData[1] ++;
					file_put_contents($this->dataNullIP, $fileNullData[0]."=".$fileNullData[1]);
					$this->result = true; // Отправляем письмо
				}
			} elseif ($fileNullData[1] >= $this->amountNullIP) {
				if ($fileNullData[0] < time() - $this->timeClear ) { // Если прошла минута с момент отправки письма
					file_put_contents($this->dataNullIP, time()."=1"); // Очищаем файл
					$this->return = true; // Отправляем письмо
				} elseif ($fileNullData[0] >= time() - $this->timeClear) { // Если минута не прошла
					$this->result = false; // Письмо не отправляем
				}
			}
		} else {
			$file = file($this->dataFile);
			foreach ($this->ips as $key => $checkIP) {
				foreach ($file as $id => $string) {
					$str = explode("=", $string);
					if ($str[1] <= time() - $this->timeDel) {  // Делаем очистку $this->dataFile если прошло больше чем $this->timeDel минут
						unset($file[$id]);
						$refile = implode("", $file);
						file_put_contents($this->dataFile, $refile);
					}
					if ($str[0] == $checkIP) { // Проверяет есть ли в файле такой же ip
						$this->new = false; // Возвращаем false чтобы не записывать в файл. Такой ip уже есть.
						if (!isset($str[2])) { // Если флага еще нет(это вторая заявка с данного ip) то добавляем флаг 1
							$file[$id] = substr($file[$id], 0, -1); // Обрезаем перенос на новую строку \n
							$file[$id] = $file[$id]."=1\n"; // Добавляем количество запросов к странице с этого ip
						}elseif($str[2] >= 1 && $str[1] >= time() - $this->timeDel){
							$flag = (int)($str[2]); // Прибавляем к флагу единицу каждый раз
							$flag ++;  // Добавляем флаг ++1
							if ($flag <= 10) {
								$file[$id] = substr($file[$id], 0, strlen($file[$id]) - 2)."$flag\n"; // Подготавливаем место под цифру флага от 1 до 9
							} elseif ($flag <= $this->banAmount && $flag > 10) {
								$file[$id] = substr($file[$id], 0, strlen($file[$id]) - 3)."$flag\n"; // Подготавливаем место под цифру флага от 10 до 99
							}
							if ($str[2] >= $this->amountEntry) { // Если флаг больше $this->amountEntry возвращаем false (не отпраавляем письмо)
								$this->result = false;
								if ($str[2] >= $this->banAmount) { // Если флаг больше $this->banAmount тогда запускаем функцию Бан возвращаем false (не отпраавляем письмо)
									$this->banIP($str[0]);
								}
							}
						}
						$refile = implode("", $file);
						file_put_contents($this->dataFile, $refile); // Перезаписываем $this->dataFile после изменений
					}
				}
				if ($this->new) {
					$this->ip_str = $checkIP."=".time()."\n"; // Подготавливаем строку для записи в файл в нужном формате
					file_put_contents($this->dataFile, $this->ip_str, FILE_APPEND); // Записываем в файл
				}
			}
		}
		return $this->result;
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
	/**
	*	Проверка на наявность IP клиента в файле BAN_FILE
	*/
	public function checkBanIP($banIP)
	{
		if ($this->checkRange($banIP)) {
			return false;
		} else {
			$file= file($this->banFile);
			foreach ($file as $id => $ip) {
				$ip = trim(substr($ip, 0, -1)); // Обрезаем перенос на новую строку \n
				if ($banIP == $ip ) {
					return false; // Не записываем  забаненый IP, если он там уже есть
				}
			}
			//return $ip;
			return true;
		}
	}

	/**
	*	Проверка перед отправкой письма
	*/
	public  function checkBeforeSend()
	{
		if ($this->ip != "unknown, unknown" && $this->botStop() && $this->checkBanIP($this->ip))
			return true;
		return false;
	}
}
