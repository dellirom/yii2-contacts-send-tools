<?php
//Done
namespace dellirom\com\components;

/**
* 	Интеграция с Amo CRM через REST API
*/
class AmoCRM extends CurlHelper
{

	protected $subdomain;
	protected $user;
	protected $data;

	const HTTP_HEADER 		= true;
	const TYPE_CONTACTS 	= 1;
	const TYPE_LEADS 			= 2;
	const TYPE_NOTES 			= 3;

	public function __construct()
	{
		$config 					= Config::get();
		$this->user 			= $config->user;
		$this->subdomain 	= $config->subdomain;

		// Авторизация с AmoCRM
		$link				= 'https://'.$this->subdomain . '.amocrm.ru/private/api/auth.php?type=json';#Формируем ссылку для запроса
		$postFields = http_build_query($this->user);
		$result			= $this->useCurl($link, $postFields);
		$response 	= json_decode($result, true);
		$response 	= $response['response'];
		if(!isset($response['auth'])) #Флаг авторизации доступен в свойстве "auth"
		return 'Авторизация не удалась';
	}

	/**
	* Добавляем данные в AmoCRM в зависимости от типов данных. (contacts, leads, notes)
	*/
	public function addData($data, $type = self::TYPE_NOTES)
	{
		$type 			= $this->amoType($type);
		$link				='https://' . $this->subdomain . '.amocrm.ru/private/api/v2/json/' . $type . '/set';
		$postFields = json_encode($data);
		$result 		= $this->useCurl($link, $postFields, self::HTTP_HEADER);
		return $result;
	}

	/**
	* Выборка данных из AmoCRM в зависимости от типов данных. (contacts, leads, notes)
	* @return Object
	*/
	public function listData($query = false, $type = self::TYPE_LEADS)
	{
		$type 		= $this->amoType($type);
		if ($query != false) {
			$query = '?' . http_build_query($query);
		}
		$link			= 'https://' . $this->subdomain . '.amocrm.ru/private/api/v2/json/' . $type . '/list'. $query;
		$postFields = false;
		$result 		= $this->useCurl($link, $postFields, self::HTTP_HEADER);
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
	* Добавление контакта в AmoCRM/Данные передаются ввиде массива
	* @return Array
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

	/**
	* Возвращает данные акаунта
	* @return Array
	*/
	public function listCurrentAccounts()
	{
		$link			= 'https://' . $this->subdomain . '.amocrm.ru/private/api/v2/json/accounts/current'; #$this->subdomain уже объявляли выше
		$out 			= $this->useCurl($link, false);
		$response = json_decode($out,true);
		return $response;
	}

	/**
	*	Проверяет есть ли поле PHONE в контактах AmoCRM
	* @return Array
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
				if(isset($diff)){
					die('В amoCRM отсутствуют следующие поля'.': '.join(', ',$diff));
				}
				else{
					die('Невозможно получить дополнительные поля');
				}
			}
			while(false);
			else
				die('Невозможно получить дополнительные поля');
			$custom_fields = isset($fields) ? $fields : false;
			return $custom_fields;
	}

	/**
	* Типы данных в AmoCRM
	* @return String
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

}


?>