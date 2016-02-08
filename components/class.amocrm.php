<?php
/**
* 	Интеграция с Amo CRM через REST API
*/
class AmoCRM
{

	protected $subdomain = '4chayki'; #Наш аккаунт - поддомен
	protected $user = array(
			'USER_LOGIN'=>'rigidpeople@gmail.com', #Ваш логин (электронная почта)
			'USER_HASH'=>'d2d8c883dec722a489450e9926e11dba' #Хэш для доступа к API (смотрите в профиле пользователя)
			);
	protected $data;

	const HTTP_HEADER 		= true;
	const TYPE_CONTACTS 	= 1;
	const TYPE_LEADS 			= 2;
	const TYPE_NOTES 			= 3;

	public function __construct()
	{
		// Авторизация с AmoCRM
		$link				= 'https://'.$this->subdomain.'.amocrm.ru/private/api/auth.php?type=json';#Формируем ссылку для запроса
		$postFields = http_build_query($this->user);
		$result			= $this->initCurl($link, $postFields);
		$response 	= json_decode($result, true);
		$response 	= $response['response'];
		if(!isset($response['auth'])) #Флаг авторизации доступен в свойстве "auth"
			return 'Авторизация не удалась';
	}

	/**
	* Добавление события в Amocrm
	*/
	// public function addNotes($data, $type = self::TYPE_NOTES)
	// {
	// 	$link				='https://' . $this->subdomain . '.amocrm.ru/private/api/v2/json/notes/set';
	// 	$postFields = json_encode($data);
	// 	$result 		= $this->initCurl($link, $postFields, self::HTTP_HEADER);
	// 	return $result;
	// }

	/**
	* Добавляем данные в AmoCRM в зависимости от типов данных. (contacts, leads, notes)
	*/
	public function addData($data, $type = self::TYPE_NOTES)
	{
		$type 			= $this->amoType($type);
		$link				='https://' . $this->subdomain . '.amocrm.ru/private/api/v2/json/' . $type . '/set';
		$postFields = json_encode($data);
		$result 		= $this->initCurl($link, $postFields, self::HTTP_HEADER);
		return $result;
	}

	/**
	* Выборка данных из AmoCRM в зависимости от типов данных. (contacts, leads, notes)
	*/
	public function listData($query = false, $type = self::TYPE_LEADS)
	{
		$type 		= $this->amoType($type);
		if ($query != false) {
			$query = '?' . http_build_query($query);
		}
		$link			= 'https://' . $this->subdomain . '.amocrm.ru/private/api/v2/json/' . $type . '/list'. $query;
		$postFields = false;
		$result 		= $this->initCurl($link, $postFields, self::HTTP_HEADER);
		return json_decode($result);
	}
	/**
	* Добавляем полную переписку с JivoSite (Cинхронизировано с Roistat)
	*/
	public function addChat($roistat, $jivoSiteId, $jivoSiteChat)
	{
		$queryRoistat = array(
					'query' => $roistat,
					);
		$out = $this->listData($queryRoistat);
		if ($out !== NULL) {
			if (count($out->response->leads == 1)) {
				foreach ($out->response->leads as $lead) {
					foreach ($lead->custom_fields as $field) {
						if ($field->name = 'roistat') {
							foreach ($field->values as $value) {
								if( $roistat == $value->value){
									$notesQuery = array(
										'type' => self::TYPE_LEADS,
										'element_id' => $lead->id
										);
									$notes = $this->listData($notesQuery, self::TYPE_NOTES);
									foreach ($notes->response->notes as $note) {
										if(preg_match("!admin.jivosite!si", $note->text)){
											$id = explode(';', $note->text);
											if($id[2] == $jivoSiteId){
												$addNotes['request']['notes']['add'] = array(
													array(
														'element_id'=> $note->element_id,
														'element_type'=>2,
														'note_type'=>4,
														'text'=> $jivoSiteChat,
														'responsible_user_id'=>220402,
														)
													);
												$res = 	$this->addData($addNotes, self::TYPE_NOTES);
												echo 'success';
												var_dump($note);
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}
	}

	/**
	* Добавление контакта в AmoCRM/Данные передаются ввиде ьассива
	*/
	public function addContacts($data)
	{
		$this->data = $data;
		if(empty($this->data['name']))
			$this->data['name'] = 'NoName';
			//die('Не заполнено имя контакта');
		$fields 				= array('POSITION','PHONE');
		$custom_fields 	= $this->checkCustomFields($fields, self::TYPE_CONTACTS);
		$query					= array('query' => $data['phone']);
		$out 						= $this->listData($query, self::TYPE_CONTACTS);
		if($out)
			die(); //die('Такой контакт уже существует в amoCRM');

		$set['request']['contacts']['add'][] = array(
			'name' => $data['name'],
			'custom_fields' => array(
				array(
					'id' => $custom_fields['PHONE'],
					'values' => array(
						array(
							'value' => $data['phone'],
							'enum' => 'WORK'
							)
						)
					)
				)
			);

		$response 	= $this->addData($set, $type = self::TYPE_CONTACTS);
		$response 	= json_decode($out,true);
		$response 	=	$response['response']['contacts']['add'];
		$output			= 'ID добавленных контактов:' . PHP_EOL;
		foreach($response as $v){
			if(is_array($v))
				$output .= $v['id'] . PHP_EOL;
			return $output;
		}
	}

	public function listCurrentAccounts()
	{
		$link			= 'https://'.$this->subdomain.'.amocrm.ru/private/api/v2/json/accounts/current'; #$this->subdomain уже объявляли выше
		$out 			= $this->initCurl($link, false);
		$response = json_decode($out,true);
		return $response;
	}
	/**
	*	Проверяет есть ли поле PHONE в контактах AmoCRM
	*/
	public function checkCustomFields($customFields, $type = self::TYPE_CONTACTS)
	{
		$type 		= $this->amoType($type);
		$response = $this->listCurrentAccounts();
		$account 	= $response['response']['account'];
		$need 		= array_flip($customFields);
			if(isset($account['custom_fields'],$account['custom_fields'][$type]))
				do
			{
			foreach($account['custom_fields'][$type] as $field)
				if(is_array($field) && isset($field['id']))
				{
					if(isset($field['code']) && isset($need[$field['code']]))
						$fields[$field['code']]=(int)$field['id'];
					$diff=array_diff_key($need,$fields);
					if(empty($diff))
						break 2;
				}
				if(isset($diff))
					die('В amoCRM отсутствуют следующие поля'.': '.join(', ',$diff));
				else
					die('Невозможно получить дополнительные поля');
			}
			while(false);
			else
				die('Невозможно получить дополнительные поля');
			$custom_fields = isset($fields) ? $fields : false;
			return $custom_fields;
	}

	/**
	*	Инициализируем CURL для последуюющих методов
	*/
	public function initCurl($link, $postFields, $httpHeader = false)
	{
		$curl=curl_init(); #Сохраняем дескриптор сеанса cURL
		#Устанавливаем необходимые опции для сеанса cURL
		curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-API-client/1.0');
		curl_setopt($curl,CURLOPT_URL,$link);
		if($postFields != false){
			curl_setopt($curl,CURLOPT_CUSTOMREQUEST,'POST');
			curl_setopt($curl,CURLOPT_POSTFIELDS, $postFields);
		}
		if ($httpHeader) {
			curl_setopt($curl,CURLOPT_HTTPHEADER,array('Content-Type: application/json'));
		}
		curl_setopt($curl,CURLOPT_HEADER,false);
    curl_setopt($curl,CURLOPT_COOKIEFILE,dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'cookie.ini'); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_COOKIEJAR,dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'cookie.ini'); #PHP>5.3.6 dirname(__FILE__) -> __DIR__
    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
    curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);

    $out=curl_exec($curl); #Инициируем запрос к API и сохраняем ответ в переменную
    $code=curl_getinfo($curl,CURLINFO_HTTP_CODE);
    curl_close($curl); #Заверашем сеанс cURL
    $this->CheckCurlResponse($code);
    return $out;
	}

	/**
	* Типы данных в AmoCRM
	*/
	public function amoType($type)
	{
		switch ($type) {
			case self::TYPE_LEADS:
				return 'leads';
				break;
			case self::TYPE_CONTACTS:
				return 'contacts';
				break;
			case self::TYPE_NOTES:
				return 'notes';
				break;
		}
	}

	/**
	*	Определяем ошибки вовремя POST запроса через CURL
	*/
	public function CheckCurlResponse($code)
	{
		$code=(int)$code;
		$errors=array(
			301=>'Moved permanently',
			400=>'Bad request',
			401=>'Unauthorized',
			403=>'Forbidden',
			404=>'Not found',
			500=>'Internal server error',
			502=>'Bad gateway',
			503=>'Service unavailable'
			);
		try
		{
			#Если код ответа не равен 200 или 204 - возвращаем сообщение об ошибке
			if($code!=200 && $code!=204)
				throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undescribed error',$code);
		}
		catch(Exception $E)
		{
			die('Ошибка: '.$E->getMessage().PHP_EOL.'Код ошибки: '.$E->getCode());
		}
	}

}


?>