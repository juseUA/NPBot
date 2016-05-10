<?php


$mysqli = new mysqli('Хост баз данных', 'Пользователь БД', 'Пароль от БД', 'Имя Бд');


/**
* Класс для работы с API новой почты
*/
class NP
{

	private $key; #access key to NP API 2.0;

	private $declaration; #number declaration for tracking;


	function __construct($key)
	{
		$this->key = $key;
	}

	public function tracking($declaration)
	{

		$this->declaration = $declaration;

		// Create array query

		$data = array(
			'apiKey' => $this->key,
			'modelName' => 'InternetDocument',
			'calledMethod' => 'documentsTracking',
			'methodProperties' => array('Documents' => array('item' => $declaration))

		);

		// Reate JSON Array

		$data = json_encode($data);

		// Query URL

		$result = file_get_contents('https://api.novaposhta.ua/v2.0/json/', null, stream_context_create(array(
			'http' => array(
				'method' => 'POST',
				'header' => "Content-type: application/x-www-form-urlencoded;\r\n",
				'content' => $data,
			),
		)));

		// Response

		$result = json_decode($result, true);

		$data = $result['data'];

		// Answer

		if ($data[0]['StateName'] == 'Номер не знайдено') {
			return 'Нам очень жаль, но вы ввели не верный номер декларации 😓';
		}

		$new_row = urlencode("\n");

		return '📦 Посылка отправлена в '.$data[0]['AddressRU'].$new_row.'🗺 С города '.$data[0]['CitySenderRU'].$new_row.'📈 Статус : '.$data[0]['StateName'].$new_row.'💰 Цена за доставку товара : '.$data[0]['Sum'].' грн.'.$new_row.'📆 Дата получения: '.$data[0]['DateReceived'].$new_row.'🙋 Получатель '.$data[0]['RecipientFullName'].$new_row.'Хорошего вам дня 😀';

	}

};

$new_row = urlencode("\n");

$api_url = 'https://api.telegram.org/botAPI Токен телеграм бота/';

//$api_url = 'https://api.telegram.org/botjwior2r:werweiru0295235/'; Пример

$content = file_get_contents("php://input");

$update = json_decode($content, true);

$chatID = $update["message"]["chat"]["id"];

$bot_query = $update["message"]["text"];

$result = explode(' ',$bot_query);

if ($result['0'] == '/find' && trim($result['1']) != '') {

	$temp = $mysqli->query("SELECT * FROM `history` WHERE `chat` = '$chatID' AND `declaration` = '$result[1]'");

	$char_result = $temp->fetch_assoc();

	if ($char_result['declaration'] != $result['1']) {

		$mysqli->query("INSERT INTO `history`(`chat`, `declaration`)VALUES('$chatID', '$result[1]')");

	}


	//Создаем объект класса новой почты

	$novaposhta = new NP('API Токен новой почты');

	$reply = $novaposhta->tracking($result['1']);

	$sendto = $api_url."sendmessage?chat_id=".$chatID."&text=".$reply;

	file_get_contents($sendto);


}elseif ($result['0'] == '/help') {
	$answer = "⚙ Список доступных команд ".$new_row." /find xxxxx - где xxxxx это номер декларации ".$new_row." /history история вашего поиска ".$new_row." /clear очистка истории поиска";

	$sendto = $api_url."sendmessage?chat_id=".$chatID."&text=".$answer;

	file_get_contents($sendto);

}elseif($result['0'] == '/history'){

	$temp = $mysqli->query("SELECT * FROM `history` WHERE `chat` = '$chatID'");

	$response_string;

	$status = 0;

	while ($res = $temp->fetch_assoc()) {

		if ($res['declaration'] == '') {

			$status = 0;

			$sendto = $api_url."sendmessage?chat_id=".$chatID."&text=Кажется вы еще ничего не искали 😯";

			file_get_contents($sendto);

			break;

		}else {

			$status = 1;

			$response_string = $response_string.$new_row.'/find '.$res['declaration'];

		}

	}

	if ($status = 1) {

		$ret = '📃 Ваша история поиска'.$new_row.$response_string;

		$sendto = $api_url."sendmessage?chat_id=".$chatID."&text=".$ret;

		file_get_contents($sendto);

	}


}elseif ($result['0'] == '/clear') {

	$mysqli->query("DELETE `history` FROM `history` WHERE `chat` = '$chatID'");

	$sendto = $api_url."sendmessage?chat_id=".$chatID."&text=Ваша история поиска очищена 🙏";

	file_get_contents($sendto);

}elseif ($result['0'] == '/start') {

	$start_string = 'Добро пожаловать 🙂'.$new_row.'📬 Мы поможем Вам отслеживать ваши посылки на Новой Почте.'.$new_row.'⚙ Для начала давайте посмотрим на список доступных команд.'.$new_row." /find xxxxx - где xxxxx это номер декларации ".$new_row." /history история вашего поиска ".$new_row." /clear Очистка истории поиска";

	$sendto = $api_url."sendmessage?chat_id=".$chatID."&text=".$start_string;

	file_get_contents($sendto);

}
else {

	$sendto = $api_url."sendmessage?chat_id=".$chatID."&text=Что-то пошло не так /help для справки 😴";

	file_get_contents($sendto);
}


?>
