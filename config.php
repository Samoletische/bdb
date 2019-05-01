<?php

// в файле 'connect.php' должны быть определены следующие переменные:
//	$server			- (string) адрес сервера БД
//	$database		- (string) имя БД
//	$dbUser			- (string) имя пользователя БД
//	$dbPassword		- (string) пароль пользователя БД
//	$debug_mode		- (bool) режим отладки
//	$log_on_db		- (bool) выводить лог в БД или в стандартный вывод;
require_once('connect.php');

class Conf {

	public $secret_mess	= 'ты живой?';

	private $db;
	private $debug_mode;
	private $log_on_db;

	private $access_token	= "2043ff6465a697a2e91dc7a5ec4bc835f6a16385be662efb90ad8d2557e28a5e9e18e038f2aff293b62f8";
	private $group_token	= "e3c415542019a47f3bc495c56ee87d4d8009fa3f7656fc112770e587ecca1dbab151f285cadbd7e5f01fd";
	private $confirm_token	= '12691b63';
	private $nocommand_mess	= 'Ничего не понял...';
	private $group_id		= '174086337';
	private $API_URL		= 'https://api.vk.com/method/';
	private $version		= '5.50';
	private $help_mess		= "Команды, которые я понимаю:\n1 'Показать [параметр]' - показать операции в зависимости от параметра:\nа) 'сегодня' - все операции за сегодня\nб) 'вчера' - все операции за вчера\n2 '[сумма (расход с '-'; доход с '+' или без знака)] [статья (можно уникальную часть статьи)] [комментарий]' - введение расходов/доходов\nПосле ввода суммы будет уточнение статьи, если я не понял какая именно была указана\n3 'Статьи [часть названия]' - вывод списка всех статей (если не указан параметр) или статей, в которых присутствует указанная часть названия\n4 'Баланс' - просмотр баланса за месяц\n5 'Отчет [параметр]' - отчеты в зависимости от параметра:\nа) 'статьи' - по статьям\n6 'Ты живой?' - секретная фраза\n7 'Помощь' - вывод этого сообщения\nПояснения:\n- в круглых скобках пояснения (сумма)\n- в квадратных скобках необязательный параметр [комментарий]";
	//------------------------------------------------------

	function __construct($server, $dbUser, $dbPassword, $database, $debug_mode, $log_on_db, $debug_url='') {
		$this->debug_mode	= $debug_mode;
		$this->log_on_db	= $log_on_db;

		$this->db = mysqli_connect($server, $dbUser, $dbPassword, $database);
		if ($this->db)
			$this->db->query("SET NAMES utf8");

		if ($debug_mode) {
			if ($debug_url != '') $API_URL = $debug_url;
			ini_set('error_reporting', E_ALL);
			ini_set('display_errors', 1);
			ini_set('display_startup_errors', 1);
		}
	}
	//------------------------------------------------------

	function __destruct() {
		if ($this->db)
			$this->db->close();
	}
	//------------------------------------------------------

	public function getDB() {
		return $this->db;
	}
	//------------------------------------------------------

	public function insertLog($mess, $debug=false) {
		if (!($debug && !$this->debug_mode))
			if ($this->log_on_db || !$debug)
				$this->db->query("INSERT INTO logs(message, debug, dateNow) VALUES('".$mess."', '".($debug ? 1 : 0)."', '".date("Y-m-d H:i:s")."')");
			elseif (!$this->log_on_db)
				echo $mess;
	}
	//------------------------------------------------------

	public function getAPIURL() {
		return $this->API_URL;
	}
	//------------------------------------------------------

	public function getVersion() {
		return $this->version;
	}
	//------------------------------------------------------

	public function getAccessToken() {
		return $this->access_token;
	}
	//------------------------------------------------------

	public function getGroupToken() {
		return $this->group_token;
	}
	//------------------------------------------------------

	public function getConfirmToken() {
		return $this->confirm_token;
	}
	//------------------------------------------------------

	public function getGroupID() {
		return $this->group_id;
	}
	//------------------------------------------------------

	public function getNoCommandMessage() {
		return $this->nocommand_mess;
	}
	//------------------------------------------------------

	public function getHelpMessage() {
		return $this->help_mess;
	}
	//------------------------------------------------------

	public function debug() {
		return $this->debug_mode;
	}
	//------------------------------------------------------

}

class Bot {

	private $mess;
	private $messType;
	private $userID;
	private $userName;
	private $conf;
	private $answer;
	private $db;
	//------------------------------------------------------

	function __construct($conf) {
		$this->conf = $conf;
		$this->db = $conf->getDB();
		if (!$this->db)
			$this->insertLog("(001) Не могу подключиться к базе данных");
	}
	//------------------------------------------------------

	private function insertLog($message, $debug=false) {
		$this->conf->insertLog($message, $debug);
	}
	//------------------------------------------------------

	public function getMessage($input) {
		// todo:
		//	вставить проверку на получение содержимого запроса,
		//	а также обработать содержимое запроса на предмет получения корректных данных
		$data = json_decode($input);
		$this->mess = $data->object->body;
		$this->messType = $data->type;
		$this->userID = $data->object->user_id;
		$this->insertLog($data->object->user_id);
		$this->userName = $this->getUserInfo()->response[0]->first_name;

		return $this->mess;
	}
	//------------------------------------------------------

	private function getUserInfo() {
		// todo:
		//	вставить проверку на получение содержимого запроса,
		//	а также обработать содержимое запроса на предмет получения корректных данных
		return json_decode(file_get_contents($this->conf->getAPIURL().
			"users.get?user_id=".$this->userID.
			"&v=".$this->conf->getVersion().
			"&access_token=".$this->conf->getAccessToken()));
	}
	//------------------------------------------------------

	public function makeAnswer() {
		$mess = mb_strtolower($this->mess);
		$this->insertLog($mess);
		$this->answer = array();
		//++ команды, не связанные с получением ответа из базы
		// секретный ответ
		if ($mess == $this->conf->secret_mess) {
			$this->answer[] = "Живее всех живых!!! Спасибо, $this->userName";
			return $this->answer;
		}

		// помощь
		if ($mess == 'помощь') {
			$this->answer = explode("\n", $this->conf->getHelpMessage());
			return $this->answer;
		}
		//-- команды, не связанные с получением ответа из базы

		//++ проверка настроек пользователя в базе
		if (!$this->db) {
			$this->answer[] = 'Ошибка обработки команды';
			return $this->answer;
		}

		// ошибка работы с базой
		$this->insertLog('SELECT purseID FROM users WHERE userID='.$this->userID);
		$query = $this->db->query('SELECT purseID FROM users WHERE userID='.$this->userID);
		if (!$query) {
			$this->insertLog('(002) Не могу выполнить запрос'.$this->db->error);
			$this->answer[] = 'Ошибка обработки команды';
			return $this->answer;
		}
		// неизвестный (для бота) пользователь
		if ($query->num_rows == 0) {
			$this->insertLog('(003) пользователь : '.$userID.' отсутствует в базе данных');
			$this->answer[] = 'Ошибка обработки команды';
			return $this->answer;
		}
		// не настроен кошелёк для пользователя
		$row = $query->fetch_array();
		if ($row["purseID"] == 0) {
			$this->insertLog('(004) для пользователя : '.$userID.' не настроен кошелёк');
			$this->answer[] = 'Ошибка обработки команды';
			return $this->answer;
		}
		$purseID = $row["purseID"];
		//-- проверка настроек пользователя в базе

		// определяем пришедшую команду
		$answ_type = 0;
		$answ_param = 0;

		$parts = explode(" ", $this->mess);

		//++ введение расходов/доходов
		// не указан знак суммы или указан знак слитно с суммой
		if ((float) $parts[0] != "") {
		    $amount = (float) $parts[0];
			$answ_type = 1; // insertAmount
		}

		// указан знак отдельно от суммы
		if (($answ_type == 0)
	        && (($parts[0] == "-") || ($parts[0] == "+"))
	        && (count($parts) > 1)
	        && ((float) ($parts[0].$parts[1]) != "")) {

      $answ_type = 1; // insertAmount
      $mark = array_shift($parts);
      $parts[0] = $mark.$parts[0];

		}
	  //-- введение расходов/доходов

		//++ другие команды, связанные с ответом из базы
		$command = mb_strtolower($parts[0]);
		$param = count($parts) > 1 ? mb_strtolower($parts[1]) : '';
		// 'показать'
		if (($answ_type == 0)
			&& (($command == "показать") || ($command == "показ"))) {

			$answ_type = 2; // showAll
		  if ((count($parts) > 1)	&& ($param == "сегодня"))
				$answ_param = 1; // today
	    if ((count($parts) > 1) && ($param == "вчера"))
				$answ_param = 2; // yesterday

		}

		// 'статьи'
		if (($answ_type == 0) && ($command == "статьи")) {
			$answ_type = 5; // showAccounts
			$answ_param = '';
			if (count($parts) > 1)
      	$answ_param = $param;
		}

		// 'баланс'
		if (($answ_type == 0) && ($command == "баланс")) {
			$answ_type = 6; // showBalance
			$answ_param = '';
		}

		// 'отчет'
		if (($answ_type == 0)
			&& (($command == "отчет") || ($command == "отчёт"))) {

			$answ_type = 7; // report
		  if ((count($parts) > 1)	&& ($param == "статьи"))
				$answ_param = 1; // accounts

		}
  	//-- другие команды, связанные с ответом из базы

		//++ обработка команд, формирование ответа
		switch ($answ_type) {
    	case 1: // insertAmount
        $res = $this->insertAmount($parts, $purseID);
        if ($res['result'] == "ok")
        	$this->answer[] = $res['message'];
        else {
        	$this->insertLog('(005) ошибка обработки прихода/расхода: '.$res['message']);
        	$this->answer[] = "ошибка обработки команды";
        }
        break;
      case 2: // showAll
	      if (($answ_param < 1) || ($answ_param > 2)) {
	      	$this->insertLog('(006) неверные параметры команды "показать": '.$answ_param);
	      	$this->answer[] = "неверные параметры команды";
	      }
	      else {
	        $res = $this->showAll($answ_param, $purseID);
	        if ($res['result'] == "ok")
	        	$this->answer[] = $res['message'];
	        else {
	        	$this->insertLog('(007) ошибка обработки команды "показать": '.$res['message']);
	        	$this->answer[] = "ошибка обработки команды";
	        }
	      }
	      break;
      case 3: // showReceipt
      	break;
      case 4: // showExpense
      	break;
			case 5: // showAccounts
				try {
					$res = $this->getAccount($answ_param, true);
					$this->answer[] = "Перечень статей:\n".$res['message'];
				}
				catch (Exception $e) {
		    	$this->insertLog('(008) ошибка обработки команды "статьи": '.$this->db->error);
					$this->answer[] = "ошибка обработки команды";
		    }
				break;
			case 6: // showBalance
				$res = $this->getBalance($purseID);
				if ($res['result'] == "ok")
				 	$this->answer[] = $res['message'];
				 else {
				 	$this->insertLog('(009) ошибка обработки команды "баланс": '.$res['message']);
				 	$this->answer[] = "ошибка обработки команды";
				 }
				break;
			case 7: // report
				if (($answ_param < 1) || ($answ_param > 2)) {
	      	$this->insertLog('(010) неверные параметры команды "отчёт": '.$answ_param);
	      	$this->answer[] = "неверные параметры команды";
	      }
	      else {
	        $res = $this->getReport($answ_param, $purseID);
	        if ($res['result'] == "ok")
	        	$this->answer = explode("\n", $res['message']);
	        else {
	        	$this->insertLog('(011) ошибка обработки команды "отчёт": '.$res['message']);
	        	$this->answer[] = "ошибка обработки команды";
	        }
	      }
				break;
		    default:
		    	$this->answer[] = $this->conf->getNoCommandMessage();
		}
		//-- обработка команд, формирование ответа

		return $this->answer;
	}
	//------------------------------------------------------

	private function getReport($reportType, $purseID) {
		$result = array( 'result' => 'ok', 'message' => '' );

		if (!$this->db) {
			$result['message'] = 'Отсутствует подключение к БД';
			return $result;
		}

		try {
			$periodStart = date('Y-m-1 00:00:00');
			$periodEnd = date('Y-m-t 23:59:59');
			$queryStr = "SELECT IFNULL(accounts.title, '') AS account, SUM(purse.amount) AS amount FROM purse INNER JOIN accounts ON accounts.id=purse.account WHERE purseID = $purseID GROUP BY accounts.title ORDER BY amount";
			$this->insertLog($queryStr."<br/>", true);
			$query = $this->db->query($queryStr);
			if (!$query)
				throw new Exception('');
			$result['message'] = "Отчёт по статьям за текущий месяц:";
			while ($row = $query->fetch_array()) {
				$result['message'] .= "\n".$row['account'].": ".$row['amount'];
			}
		}	catch (Exception $e) {
			$result['result'] = "error";
			$result['message'] = $this->db->error;
			$this->insertLog("balance error: ".$e."<br/>", true);
		}

		return $result;
	}
	//------------------------------------------------------

	private function getBalance($purseID) {
		$result = array( 'result' => 'ok', 'message' => '' );

		if (!$this->db) {
			$result['message'] = 'Отсутствует подключение к БД';
			return $result;
		}

		try {
			$periodStart = date('Y-m-1 00:00:00');
			$periodEnd = date('Y-m-t 23:59:59');

			$queryStr = "SELECT SUM(IFNULL(amount, 0)) AS startBalance FROM purse WHERE purseID = $purseID AND period < '".$periodStart."';
				SELECT SUM(amount) AS income FROM purse WHERE purseID = $purseID AND moition = 1 AND period >= '".$periodStart."' AND period <= '".$periodEnd."';
				SELECT SUM(amount) AS expense FROM purse WHERE purseID = $purseID AND moition = 0 AND period >= '".$periodStart."' AND period <= '".$periodEnd."'";
			//$this->insertLog($queryStr."<br/>", true);
			$query = $this->db->multi_query($queryStr);
			if (!$query)
				throw new Exception('');

			$this->insertLog('query passed<br/>', true);

			// startBalance
			$res = $this->db->store_result();
			if (!$res)
				throw new Exception('');

			$this->insertLog('store_result passed<br/>', true);

			$row = $res->fetch_array();
			$this->insertLog("start balance =".$row['startBalance']."<br/>", true);
			$startBalance = $row['startBalance'];
			$result['message'] = "Баланс на начало месяца: ".($startBalance ? $startBalance : 0);

			// income
			if (!$this->db->next_result())
				throw new Exception('');

			$res = $this->db->store_result();
			if (!$res)
				throw new Exception('');

			$row = $res->fetch_array(); // startBalance
			$income = $row['income'];
			$this->insertLog("income =".$row['income']."<br/>", true);
			$result['message'] .= "\nДоход за месяц: ".$income;

			// expense
			if (!$this->db->next_result())
				throw new Exception('');

			$res = $this->db->store_result();
			if (!$res)
				throw new Exception('');

			$row = $res->fetch_array(); // startBalance
			$expense = $row['expense'];
			$this->insertLog("expense =".$row['expense']."<br/>", true);
			$result['message'] .= "\nРасход за месяц: ".(-$expense);

			$result['message'] .= "\nБаланс на конец месяца: ".($startBalance + $income + $expense);
		}	catch (Exception $e) {
			$result['result'] = "error";
			$result['message'] = $this->db->error;
			$this->insertLog("balance error: ".$e."<br/>", true);
		}

		return $result;
	} // getBalance
	//------------------------------------------------------

	private function getAccount($acc, $mayGetAll = false) {
		$result = array( 'result' => 'error', 'message' => '', 'id' => '' );
		$this->insertLog("acc=".$acc, true);
		if (($acc == '') && !$mayGetAll)
			return $result;

		$query = $this->db->query("SELECT id, title FROM accounts WHERE title LIKE '%$acc%' ORDER BY title");
		if (!$query)
			throw new Exception('');

		switch ($query->num_rows) {
			case 0:
			 	$result['result'] = 'no';
				break;
			case 1:
				$result['result'] = 'ok';
				break;
			default:
				$result['result'] = 'many';
				break;
		}

		while ($row = $query->fetch_array()) {
			$result['id'] = $query->num_rows == 1 ? $row['id'] : '';
			$result['message'] .= ($result['message'] == '' ? '' : ', ').$row['title'];
		}
		$this->insertLog("accMessage=".$result['message'], true);

		return $result;
	}
	//------------------------------------------------------

	private function insertAmount($parts, $purseID) {
		$result = array( 'result' => 'ok', 'message' => '');
		$moition = "1";

		$amount = (float) $parts[0];
		$amount = (int) ($amount * 100);
		$amount = (float) ($amount / 100);

		try {
			$account = $this->getAccount(count($parts) > 1 ? $parts[1] : '');
			$this->insertLog("accountResult=".$account['result'], true);

			$start = strlen($parts[0]) + ($account['result'] == 'ok' ? strlen($parts[1]) + 2 : 1);
			$this->insertLog("start=".$start, true);
			$comment = substr(implode(" ", $parts), $start);
			$comment = strlen($comment) <= 300 ? $comment : substr($comment, 300);

			if (substr($parts[0], 0, 1) == "-")
				$moition = "0";
			$query = $this->db->query("
				INSERT INTO
					purse(period
						,moition
						,amount
						,account
						,comment
						,userID
						,purseID
					)
				VALUES('".date("Y-m-d H:i:s")."'
					,".$moition."
					,".$amount."
					,'".$account['id']."'
					,'".$comment."'
					,".$this->userID."
					,".$purseID.")");
			if (!$query)
				throw new Exception('');

			if ($moition == 0) {
				$result['message'] = 'расход';
				$amount = 0 - $amount;
			}
			else
				$result['message'] = 'приход';

			$result['message'] .= " на сумму $amount руб. принят ";

			if ($account['result'] == 'ok')
				$result['message'] .= "по статье '".$account['message']."' ";
			else
				$result['message'] .= "без статьи ";

			if ($comment == '')
				$result['message'] .= "без комментария";
			else
				$result['message'] .= "с комментарием \"$comment\"";

			if ($account['result'] == "many")
				$result['message'] .= "\nБез статьи, т. к. нашлось несколько: ".$account['message'];
		}
	    catch (Exception $e) {
	    	$result['result'] = "error";
	    	$result['message ']= $this->db->error;
	    }

	    return $result;

	}
	//------------------------------------------------------

	private function showAll($periodType, $purseID) {

	    $result = array( 'result' => 'ok', 'message' => '' );

	    try {
	        if ($periodType == 1) {
	            $periodStart = date('Y-m-d 00:00:00');
	            $periodEnd = date('Y-m-d 23:59:59');
	        }

	        if ($periodType == 2) {
	            $periodStart = date('Y-m-d 00:00:00', strtotime('yesterday'));
	            $periodEnd = date('Y-m-d 23:59:59', strtotime('yesterday'));
	        }

	        $query = $this->db->query("SELECT purse.period AS period
	                                ,users.name AS name,
	                                purse.amount AS amount,
	                                purse.comment AS comment
	                            FROM purse
	                                INNER JOIN users
	                                ON users.purseID=purse.purseID
	                                    AND users.userID=purse.userID
	                            WHERE purse.purseID=".$purseID."
	                                AND purse.period>='".$periodStart."'
	                                AND purse.period<='".$periodEnd."'
	                            ORDER BY purse.period");
	        if (!$query)
	            throw new Exception('');

	        $count = 1;
	        $periodTimeStamp = strtotime($periodStart);
	        $result['message'] = "Все операции за: ".date('d', $periodTimeStamp).".".date('m', $periodTimeStamp).".".date('Y', $periodTimeStamp);
	        $message = '';
	        $summ = 0;
	        while ($row = $query->fetch_array()) {
	            $time_date = explode(" ", $row["period"]);
	            $date = explode("-", $time_date[0]);
	            $time = explode(":", $time_date[1]);

			$message .= "\n".$count.") ".$time[0].":".$time[1]." (".$row["name"].") ".$row["amount"]." (".$row["comment"].")";
			$summ += (float) $row["amount"];

	            $count++;
	        }
	        $result['message'] .= $message == '' ? "\nнет записей" : $message."\nИтого: ".$summ;
	    }
	    catch (Exception $e) {
	        $result['result'] = "error";
	        $result['message'] = $this->db->error;
	    }

	    return $result;

	}
	//------------------------------------------------------

	public function sendAnswer($answer) {
		if ($answer == '')
			$answer = $this->answer;

		if ($this->messType == 'confirmation')
			return $this->conf->getConfirmToken();

		if ($this->messType == 'message_new') {

				$mess_url = $this->conf->getAPIURL().
					"messages.send?user_id=".$this->userID.
					"&group_id=".$this->conf->getGroupID().
					"&message=".urlencode($answer).
					"&v=".$this->conf->getVersion().
					"&access_token=".$this->conf->getGroupToken();

				$request_params = array(
					'message' => $this->answer,
					'user_id' => $this->userID,
					'access_token' => $this->conf->getAccessToken(),
					'v' => $this->conf->getVersion()
				);

				$get_params = http_build_query($request_params);

				$request = file_get_contents($mess_url);
				$this->insertLog("send request= ".$request, true);

				return 'ok';
		}
	}
	//------------------------------------------------------

}
//------------------------------------------------------

?>
