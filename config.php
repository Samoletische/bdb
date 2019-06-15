<?php

class BotException extends Exception {} // BotException
//------------------------------------------------------

abstract class BotMngr {

	private requiredFields = array(
		'testMode', 'input', 'accessToken', 'groupToken', 'confirmToken', 'groupID', 'APIURL', 'version',
		'waitCommandTimeOut', 'storageMode', 'storageParams', 'standardCommands'
	);
	//------------------------------------------------------

	static function createBot() {

		// read config
		if (!file_exists('conf.json'))
			throw new BotException('file ''conf.json'' not exists');
		$conf = json_decode(file_get_contents('conf.json'), true);
		if (json_last_error() != JSON_ERROR_NONE)
			throw new BotException('bad JSON in ''conf.json''');

		// check config
		if (!BotMngr::array_keys_exists($this->requiredFields, $conf))
			throw new BotException('not all required fields exists in ''conf.json''');

		// create storage
		try {
			$storage = Storage::createStorage($conf['storageMode'], $conf['storageParams'], $conf['testMode'], $error);
		} catch (BotException $e) {
			throw new BotException('don''t create Storage: '.$e->getMessage());
		}

		// create Bot
		unset($conf['storageMode']);
		unset($conf['storageParams']);
		return new Bot($storage, $conf);

	} // BotManager::createBot

	static function array_keys_exists($keys, $array) {
		foreach($keys as $key)
			if (!array_key_exists($key, $array))
				return false;
		return true;
	} // BotMngr::array_keys_exists

} // BotMngr
//------------------------------------------------------

abstract class Storage {

	private $requiredFieldsDB = array('server', 'database', 'dbUser', 'dbPassword');
	//------------------------------------------------------

	static function createStorage($mode, $params, $testMode) {

		switch ($mode) {
			case 'MYSQL' :
				// check params
				if (!BotMngr::array_keys_exists($this->requiredFieldsDB, $params))
					throw new BotException('not all required fields exists in ''storageParams''');
				// check connect
				$db = mysqli_connect($params['server'], $params['dbUser'], $params['dbPassword'], $params['database']);
				if (!$db)
					throw new BotException('can''t connect to MySQL: '.mysqli_connect_error());
				// create object
				return new StorageMYSQL($db, $testMode);
			case 'FILE' :
				return NULL;
			default:
				return NULL;
		}

	} // Storage::createStorage
	//------------------------------------------------------

	public function insertLog($message, $debug=false);
	public function query($queryStr);
	//------------------------------------------------------

} // Storage
//------------------------------------------------------

class StorageMySQL {

	private $db;
	private $testMode;
	//------------------------------------------------------

	function __construct($db, $testMode=false) {
		$this->db = $db;
		$this->testMode = $testMode;
	} // StorageMySQL::_construct
	//------------------------------------------------------

	function __destruct() {
		$this->db->close();
	} // StorageMySQL::__destruct
	//------------------------------------------------------

	public function insertLog($message, $debug=false) {
		try {
			if (!$debug || $this->testMode)
				$this->db->query("INSERT INTO logs(message, debug, dateNow) VALUES('".$mess."', '".($debug ? 1 : 0)."', '".date('Y-m-d H:i:s')."')");
		}	catch (Exception $ex) {
		} catch (Error $er) {}
	} // StorageMySQL::insertLog
	//------------------------------------------------------

	public function query($queryStr) {
		return true;
	} // StorageMySQL::query
	//------------------------------------------------------

} // StorageMySQL
//------------------------------------------------------

class Bot {

	private $storage;
	private $conf;
	//------------------------------------------------------

	function __construct($storage, $conf) {
		$this->storage = $storage;
		$this->conf = $conf;
	} // Bot::__construct
	//------------------------------------------------------

	function __destruct() {
		unset($this->storage);
	} // Bot::__destruct
	//------------------------------------------------------

	static public function getMessage() {

		// get data from input
		$data = json_decode(file_get_contents($this->conf['input']));
		if (json_last_error != JSON_ERROR_NONE)
			throw new BotException('bad JSON in input');

		// check for nessesary fields (level 1)
		$requiredFields = array('object', 'type');
		if (!BotMngr::array_keys_exists($requiredFields, $$data))
			throw new BotException('not all required fields exists in input data');
		// check for nessesary fields (level 2)
		$requiredFields = array('body', 'user_id');
		if (!BotMngr::array_keys_exists($requiredFields, $$data))
			throw new BotException('not all required fields exists in input subdata');
		// create object
		return new Query($this, $data->type, $data->object->body, $data->object->user_id, $this->conf['groupID']);
	} // Bot::getMessage
	//------------------------------------------------------

	public function getAPIURL() {
		return $this->conf['APIURL'];
	} // Bot::getAPIURL
	//------------------------------------------------------

} // Bot
//------------------------------------------------------

abstract class Message {

	private $bot;
	private $sender;
	private $receiver;
	private $type;
	private $content;
	//------------------------------------------------------

	static protected function send() {

		if (($this->type != 'sendOnly') && (($this->type != 'sendAndWait'))
			throw new BotException('wrong type of sender message');
		foreach($this->content as $content) {
		$messURL = $this->bot->getAPIURL().
			"messages.send?user_id=".$this->receiver->getID().
			"&group_id=".$this->sender->getID().
			"&message=".urlencode($content).
			"&v=".$this->bot->getVersion().
			"&access_token=".$this->bot->getGroupToken();
		$request = file_get_contents($messURL);
		}

	} // Message::send

} // Message
//------------------------------------------------------

class Query extends Message {

	private $bot;
	private $sender;
	private $receiver;
	private $type;
	private $content;
	//------------------------------------------------------

	function __construct($bot, $type, $content, $sender, $receiver) {

		$this->bot = $bot;
		$this->type = $type;
		$this->content = $content;
		$this->sender = $sender;
		$this->receiver = $receiver;

	} // Query::__construct

} // Query
//------------------------------------------------------

class User {

	private $bot;
	private $userID;
	private $userName;
	//------------------------------------------------------

	function __construct($bot) {

	} // User:__construct

} // User
//------------------------------------------------------

// class Bot {
//
// 	private function getUserInfo() {
// 		// todo:
// 		//	вставить проверку на получение содержимого запроса,
// 		//	а также обработать содержимое запроса на предмет получения корректных данных
// 		return json_decode(file_get_contents($this->conf->getAPIURL().
// 			"users.get?user_id=".$this->userID.
// 			"&v=".$this->conf->getVersion().
// 			"&access_token=".$this->conf->getAccessToken()));
// 	}
// 	//------------------------------------------------------
//
// 	public function makeAnswer() {
// 		$mess = mb_strtolower($this->mess);
// 		$this->insertLog($mess);
// 		$this->answer = array();
// 		//++ команды, не связанные с получением ответа из базы
// 		// секретный ответ
// 		if ($mess == $this->conf->secret_mess) {
// 			$this->answer[] = "Живее всех живых!!! Спасибо, $this->userName";
// 			return $this->answer;
// 		}
//
// 		// помощь
// 		if ($mess == 'помощь') {
// 			$this->answer = explode("\n", $this->conf->getHelpMessage());
// 			return $this->answer;
// 		}
// 		//-- команды, не связанные с получением ответа из базы
//
// 		//++ проверка настроек пользователя в базе
// 		if (!$this->db) {
// 			$this->answer[] = 'Ошибка обработки команды';
// 			return $this->answer;
// 		}
//
// 		// ошибка работы с базой
// 		$this->insertLog('SELECT purseID FROM users WHERE userID='.$this->userID);
// 		$query = $this->db->query('SELECT purseID FROM users WHERE userID='.$this->userID);
// 		if (!$query) {
// 			$this->insertLog('(002) Не могу выполнить запрос'.$this->db->error);
// 			$this->answer[] = 'Ошибка обработки команды';
// 			return $this->answer;
// 		}
// 		// неизвестный (для бота) пользователь
// 		if ($query->num_rows == 0) {
// 			$this->insertLog('(003) пользователь : '.$userID.' отсутствует в базе данных');
// 			$this->answer[] = 'Ошибка обработки команды';
// 			return $this->answer;
// 		}
// 		// не настроен кошелёк для пользователя
// 		$row = $query->fetch_array();
// 		if ($row["purseID"] == 0) {
// 			$this->insertLog('(004) для пользователя : '.$userID.' не настроен кошелёк');
// 			$this->answer[] = 'Ошибка обработки команды';
// 			return $this->answer;
// 		}
// 		$purseID = $row["purseID"];
// 		//-- проверка настроек пользователя в базе
//
// 		// определяем пришедшую команду
// 		$answ_type = 0;
// 		$answ_param = 0;
//
// 		$parts = explode(" ", $this->mess);
//
// 		//++ введение расходов/доходов
// 		// не указан знак суммы или указан знак слитно с суммой
// 		if ((float) $parts[0] != "") {
// 		    $amount = (float) $parts[0];
// 			$answ_type = 1; // insertAmount
// 		}
//
// 		// указан знак отдельно от суммы
// 		if (($answ_type == 0)
// 	        && (($parts[0] == "-") || ($parts[0] == "+"))
// 	        && (count($parts) > 1)
// 	        && ((float) ($parts[0].$parts[1]) != "")) {
//
//       $answ_type = 1; // insertAmount
//       $mark = array_shift($parts);
//       $parts[0] = $mark.$parts[0];
//
// 		}
// 	  //-- введение расходов/доходов
//
// 		//++ другие команды, связанные с ответом из базы
// 		$command = mb_strtolower($parts[0]);
// 		$param = count($parts) > 1 ? mb_strtolower($parts[1]) : '';
// 		// 'показать'
// 		if (($answ_type == 0)
// 			&& (($command == "показать") || ($command == "показ"))) {
//
// 			$answ_type = 2; // showAll
// 		  if ((count($parts) > 1)	&& ($param == "сегодня"))
// 				$answ_param = 1; // today
// 	    if ((count($parts) > 1) && ($param == "вчера"))
// 				$answ_param = 2; // yesterday
//
// 		}
//
// 		// 'статьи'
// 		if (($answ_type == 0) && ($command == "статьи")) {
// 			$answ_type = 5; // showAccounts
// 			$answ_param = '';
// 			if (count($parts) > 1)
//       	$answ_param = $param;
// 		}
//
// 		// 'баланс'
// 		if (($answ_type == 0) && ($command == "баланс")) {
// 			$answ_type = 6; // showBalance
// 			$answ_param = '';
// 		}
//
// 		// 'отчет'
// 		if (($answ_type == 0)
// 			&& (($command == "отчет") || ($command == "отчёт"))) {
//
// 			$answ_type = 7; // report
// 		  if ((count($parts) > 1)	&& ($param == "статьи"))
// 				$answ_param = 1; // accounts
//
// 		}
//   	//-- другие команды, связанные с ответом из базы
//
// 		//++ обработка команд, формирование ответа
// 		switch ($answ_type) {
//     	case 1: // insertAmount
//         $res = $this->insertAmount($parts, $purseID);
//         if ($res['result'] == "ok")
//         	$this->answer[] = $res['message'];
//         else {
//         	$this->insertLog('(005) ошибка обработки прихода/расхода: '.$res['message']);
//         	$this->answer[] = "ошибка обработки команды";
//         }
//         break;
//       case 2: // showAll
// 	      if (($answ_param < 1) || ($answ_param > 2)) {
// 	      	$this->insertLog('(006) неверные параметры команды "показать": '.$answ_param);
// 	      	$this->answer[] = "неверные параметры команды";
// 	      }
// 	      else {
// 	        $res = $this->showAll($answ_param, $purseID);
// 	        if ($res['result'] == "ok")
// 	        	$this->answer[] = $res['message'];
// 	        else {
// 	        	$this->insertLog('(007) ошибка обработки команды "показать": '.$res['message']);
// 	        	$this->answer[] = "ошибка обработки команды";
// 	        }
// 	      }
// 	      break;
//       case 3: // showReceipt
//       	break;
//       case 4: // showExpense
//       	break;
// 			case 5: // showAccounts
// 				try {
// 					$res = $this->getAccount($answ_param, true);
// 					$this->answer[] = "Перечень статей:\n".$res['message'];
// 				}
// 				catch (Exception $e) {
// 		    	$this->insertLog('(008) ошибка обработки команды "статьи": '.$this->db->error);
// 					$this->answer[] = "ошибка обработки команды";
// 		    }
// 				break;
// 			case 6: // showBalance
// 				$res = $this->getBalance($purseID);
// 				if ($res['result'] == "ok")
// 				 	$this->answer[] = $res['message'];
// 				 else {
// 				 	$this->insertLog('(009) ошибка обработки команды "баланс": '.$res['message']);
// 				 	$this->answer[] = "ошибка обработки команды";
// 				 }
// 				break;
// 			case 7: // report
// 				if (($answ_param < 1) || ($answ_param > 2)) {
// 	      	$this->insertLog('(010) неверные параметры команды "отчёт": '.$answ_param);
// 	      	$this->answer[] = "неверные параметры команды";
// 	      }
// 	      else {
// 	        $res = $this->getReport($answ_param, $purseID);
// 	        if ($res['result'] == "ok")
// 	        	$this->answer = explode("\n", $res['message']);
// 	        else {
// 	        	$this->insertLog('(011) ошибка обработки команды "отчёт": '.$res['message']);
// 	        	$this->answer[] = "ошибка обработки команды";
// 	        }
// 	      }
// 				break;
// 		    default:
// 		    	$this->answer[] = $this->conf->getNoCommandMessage();
// 		}
// 		//-- обработка команд, формирование ответа
//
// 		return $this->answer;
// 	}
// 	//------------------------------------------------------
//
// 	private function getReport($reportType, $purseID) {
// 		$result = array( 'result' => 'ok', 'message' => '' );
//
// 		if (!$this->db) {
// 			$result['message'] = 'Отсутствует подключение к БД';
// 			return $result;
// 		}
//
// 		try {
// 			$periodStart = date('Y-m-1 00:00:00');
// 			$periodEnd = date('Y-m-t 23:59:59');
// 			$queryStr = "SELECT IFNULL(accounts.title, '') AS account, SUM(purse.amount) AS amount FROM purse INNER JOIN accounts ON accounts.id=purse.account WHERE purseID = $purseID AND period >= '$periodStart' AND period <= '$periodEnd' GROUP BY accounts.title ORDER BY amount";
// 			$this->insertLog($queryStr."<br/>", true);
// 			$query = $this->db->query($queryStr);
// 			if (!$query)
// 				throw new Exception('');
// 			$result['message'] = "Отчёт по статьям за текущий месяц:";
// 			while ($row = $query->fetch_array()) {
// 				$result['message'] .= "\n".$row['account'].": ".$row['amount'];
// 			}
// 		}	catch (Exception $e) {
// 			$result['result'] = "error";
// 			$result['message'] = $this->db->error;
// 			$this->insertLog("balance error: ".$e."<br/>", true);
// 		}
//
// 		return $result;
// 	}
// 	//------------------------------------------------------
//
// 	private function getBalance($purseID) {
// 		$result = array( 'result' => 'ok', 'message' => '' );
//
// 		if (!$this->db) {
// 			$result['message'] = 'Отсутствует подключение к БД';
// 			return $result;
// 		}
//
// 		try {
// 			$periodStart = date('Y-m-1 00:00:00');
// 			$periodEnd = date('Y-m-t 23:59:59');
//
// 			$queryStr = "SELECT SUM(IFNULL(amount, 0)) AS startBalance FROM purse WHERE purseID = $purseID AND period < '".$periodStart."';
// 				SELECT SUM(amount) AS income FROM purse WHERE purseID = $purseID AND moition = 1 AND period >= '".$periodStart."' AND period <= '".$periodEnd."';
// 				SELECT SUM(amount) AS expense FROM purse WHERE purseID = $purseID AND moition = 0 AND period >= '".$periodStart."' AND period <= '".$periodEnd."'";
// 			//$this->insertLog($queryStr."<br/>", true);
// 			$query = $this->db->multi_query($queryStr);
// 			if (!$query)
// 				throw new Exception('');
//
// 			$this->insertLog('query passed<br/>', true);
//
// 			// startBalance
// 			$res = $this->db->store_result();
// 			if (!$res)
// 				throw new Exception('');
//
// 			$this->insertLog('store_result passed<br/>', true);
//
// 			$row = $res->fetch_array();
// 			$this->insertLog("start balance =".$row['startBalance']."<br/>", true);
// 			$startBalance = $row['startBalance'];
// 			$result['message'] = "Баланс на начало месяца: ".($startBalance ? $startBalance : 0);
//
// 			// income
// 			if (!$this->db->next_result())
// 				throw new Exception('');
//
// 			$res = $this->db->store_result();
// 			if (!$res)
// 				throw new Exception('');
//
// 			$row = $res->fetch_array(); // startBalance
// 			$income = $row['income'];
// 			$this->insertLog("income =".$row['income']."<br/>", true);
// 			$result['message'] .= "\nДоход за месяц: ".$income;
//
// 			// expense
// 			if (!$this->db->next_result())
// 				throw new Exception('');
//
// 			$res = $this->db->store_result();
// 			if (!$res)
// 				throw new Exception('');
//
// 			$row = $res->fetch_array(); // startBalance
// 			$expense = $row['expense'];
// 			$this->insertLog("expense =".$row['expense']."<br/>", true);
// 			$result['message'] .= "\nРасход за месяц: ".(-$expense);
//
// 			$result['message'] .= "\nБаланс на конец месяца: ".($startBalance + $income + $expense);
// 		}	catch (Exception $e) {
// 			$result['result'] = "error";
// 			$result['message'] = $this->db->error;
// 			$this->insertLog("balance error: ".$e."<br/>", true);
// 		}
//
// 		return $result;
// 	} // getBalance
// 	//------------------------------------------------------
//
// 	private function getAccount($acc, $mayGetAll = false) {
// 		$result = array( 'result' => 'error', 'message' => '', 'id' => '' );
// 		$this->insertLog("acc=".$acc, true);
// 		if (($acc == '') && !$mayGetAll)
// 			return $result;
//
// 		$query = $this->db->query("SELECT id, title FROM accounts WHERE title LIKE '%$acc%' ORDER BY title");
// 		if (!$query)
// 			throw new Exception('');
//
// 		switch ($query->num_rows) {
// 			case 0:
// 			 	$result['result'] = 'no';
// 				break;
// 			case 1:
// 				$result['result'] = 'ok';
// 				break;
// 			default:
// 				$result['result'] = 'many';
// 				break;
// 		}
//
// 		while ($row = $query->fetch_array()) {
// 			$result['id'] = $query->num_rows == 1 ? $row['id'] : '';
// 			$result['message'] .= ($result['message'] == '' ? '' : ', ').$row['title'];
// 		}
// 		$this->insertLog("accMessage=".$result['message'], true);
//
// 		return $result;
// 	}
// 	//------------------------------------------------------
//
// 	private function insertAmount($parts, $purseID) {
// 		$result = array( 'result' => 'ok', 'message' => '');
// 		$moition = "1";
//
// 		$amount = (float) $parts[0];
// 		$amount = (int) ($amount * 100);
// 		$amount = (float) ($amount / 100);
//
// 		try {
// 			$account = $this->getAccount(count($parts) > 1 ? $parts[1] : '');
// 			$this->insertLog("accountResult=".$account['result'], true);
//
// 			$start = strlen($parts[0]) + ($account['result'] == 'ok' ? strlen($parts[1]) + 2 : 1);
// 			$this->insertLog("start=".$start, true);
// 			$comment = substr(implode(" ", $parts), $start);
// 			$comment = strlen($comment) <= 300 ? $comment : substr($comment, 300);
//
// 			if (substr($parts[0], 0, 1) == "-")
// 				$moition = "0";
// 			$query = $this->db->query("
// 				INSERT INTO
// 					purse(period
// 						,moition
// 						,amount
// 						,account
// 						,comment
// 						,userID
// 						,purseID
// 					)
// 				VALUES('".date("Y-m-d H:i:s")."'
// 					,".$moition."
// 					,".$amount."
// 					,'".$account['id']."'
// 					,'".$comment."'
// 					,".$this->userID."
// 					,".$purseID.")");
// 			if (!$query)
// 				throw new Exception('');
//
// 			if ($moition == 0) {
// 				$result['message'] = 'расход';
// 				$amount = 0 - $amount;
// 			}
// 			else
// 				$result['message'] = 'приход';
//
// 			$result['message'] .= " на сумму $amount руб. принят ";
//
// 			if ($account['result'] == 'ok')
// 				$result['message'] .= "по статье '".$account['message']."' ";
// 			else
// 				$result['message'] .= "без статьи ";
//
// 			if ($comment == '')
// 				$result['message'] .= "без комментария";
// 			else
// 				$result['message'] .= "с комментарием \"$comment\"";
//
// 			if ($account['result'] == "many")
// 				$result['message'] .= "\nБез статьи, т. к. нашлось несколько: ".$account['message'];
// 		}
// 	    catch (Exception $e) {
// 	    	$result['result'] = "error";
// 	    	$result['message ']= $this->db->error;
// 	    }
//
// 	    return $result;
//
// 	}
// 	//------------------------------------------------------
//
// 	private function showAll($periodType, $purseID) {
//
// 	    $result = array( 'result' => 'ok', 'message' => '' );
//
// 	    try {
// 	        if ($periodType == 1) {
// 	            $periodStart = date('Y-m-d 00:00:00');
// 	            $periodEnd = date('Y-m-d 23:59:59');
// 	        }
//
// 	        if ($periodType == 2) {
// 	            $periodStart = date('Y-m-d 00:00:00', strtotime('yesterday'));
// 	            $periodEnd = date('Y-m-d 23:59:59', strtotime('yesterday'));
// 	        }
//
// 	        $query = $this->db->query("SELECT purse.period AS period
// 	                                ,users.name AS name,
// 	                                purse.amount AS amount,
// 	                                purse.comment AS comment
// 	                            FROM purse
// 	                                INNER JOIN users
// 	                                ON users.purseID=purse.purseID
// 	                                    AND users.userID=purse.userID
// 	                            WHERE purse.purseID=".$purseID."
// 	                                AND purse.period>='".$periodStart."'
// 	                                AND purse.period<='".$periodEnd."'
// 	                            ORDER BY purse.period");
// 	        if (!$query)
// 	            throw new Exception('');
//
// 	        $count = 1;
// 	        $periodTimeStamp = strtotime($periodStart);
// 	        $result['message'] = "Все операции за: ".date('d', $periodTimeStamp).".".date('m', $periodTimeStamp).".".date('Y', $periodTimeStamp);
// 	        $message = '';
// 	        $summ = 0;
// 	        while ($row = $query->fetch_array()) {
// 	            $time_date = explode(" ", $row["period"]);
// 	            $date = explode("-", $time_date[0]);
// 	            $time = explode(":", $time_date[1]);
//
// 			$message .= "\n".$count.") ".$time[0].":".$time[1]." (".$row["name"].") ".$row["amount"]." (".$row["comment"].")";
// 			$summ += (float) $row["amount"];
//
// 	            $count++;
// 	        }
// 	        $result['message'] .= $message == '' ? "\nнет записей" : $message."\nИтого: ".$summ;
// 	    }
// 	    catch (Exception $e) {
// 	        $result['result'] = "error";
// 	        $result['message'] = $this->db->error;
// 	    }
//
// 	    return $result;
//
// 	}
// 	//------------------------------------------------------
//
// 	public function sendAnswer($answer) {
// 		if ($answer == '')
// 			$answer = $this->answer;
//
// 		if ($this->messType == 'confirmation')
// 			return $this->conf->getConfirmToken();
//
// 		if ($this->messType == 'message_new') {
//
// 				$mess_url = $this->conf->getAPIURL().
// 					"messages.send?user_id=".$this->userID.
// 					"&group_id=".$this->conf->getGroupID().
// 					"&message=".urlencode($answer).
// 					"&v=".$this->conf->getVersion().
// 					"&access_token=".$this->conf->getGroupToken();
//
// 				$request_params = array(
// 					'message' => $this->answer,
// 					'user_id' => $this->userID,
// 					'access_token' => $this->conf->getAccessToken(),
// 					'v' => $this->conf->getVersion()
// 				);
//
// 				$get_params = http_build_query($request_params);
//
// 				$request = file_get_contents($mess_url);
// 				$this->insertLog("send request= ".$request, true);
//
// 				return 'ok';
// 		}
// 	}
// 	//------------------------------------------------------
//
// }
// //------------------------------------------------------

?>
