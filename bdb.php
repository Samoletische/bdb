<?php

$debug_mode = true;

if ($debug_mode) {
	ini_set('error_reporting', E_ALL);
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
}

$res = "";

require("config.php");

$conf = new Conf($server, $dbUser, $dbPassword, $database, $debug_mode);
$db = $conf->getDB();
//insertLog("connection");

if ($res == "") {
	if (isset($_POST["command"])) {
		switch ($_POST["command"]) {
			case "getPurseSummaryData":
				$res = getPurseSummaryData($_POST["period"], $_POST["userID"]);
				break;
			default :
				$res = "unknown command";
		}
	}
}

echo $res;
//------------------------------------------------------

function getPurseSummaryData($period = "", $userID) {

    global $db;
    $data = Array();

    try {

		// get user info
		$query = $db->query("SELECT purseID FROM users WHERE userID=".$userID);
		if (!$query) {
			//insertLog("error: (006) Не могу работать с базой");
			throw new Exception("(006) Не могу работать с базой");
		}
		if ($query->num_rows == 0) {
			//insertLog("income message from unknown userID: ".$userID);
			throw new Exception("(007) Пожалуйста авторизуйтесь");
		}
		$row = $query->fetch_array();
		if ($row["purseID"] == 0) {
			//insertLog("income message from haven\'t purse userID: ".$userID);
			throw new Exception("(008) У вас нет кошелька");
		}

        // get goal
        if ($period == "")
            $period = date("Y-m-d H:i:s");

        //throw new Exception($period);

        $query = $db->query("SELECT *
        					FROM goals
        					WHERE periodStart<='".$period."'
        						AND periodEnd >='".$period."'
        						AND purseID=".$row["purseID"]);
        if (!$query)
            throw new Exception("(002) Не могу работать с базой");
        /*if ($query->num_rows == 0)
        	insertLog("No goals for purseID: ".$row["purseID"]." and userID: ".$userID);*/
        $row_goal = $query->fetch_array();

		//throw new Exception($row_goal["periodStart"]);

        // get summ
        $query = $db->multi_query("SELECT SUM(amount) AS amount
        						FROM purse
        						WHERE period <'".$row_goal["periodStart"]."'
        							AND purseID=".$row["purseID"].";
                                SELECT SUM(amount) AS amount
                                FROM purse
                                WHERE period>='".$row_goal["periodStart"]."'
                                	AND period <='".$row_goal["periodEnd"]."'
                                	AND purseID=".$row["purseID"].";
                                SELECT SUM(amount) AS amount
                                FROM purse
                                WHERE period>='".$row_goal["periodStart"]."'
                                	AND period <='".$row_goal["periodEnd"]."'
                                	AND moition=1
                                	AND purseID=".$row["purseID"].";");
        if (!$query)
            throw new Exception("(003) Не могу работать с базой");
        $result = $db->store_result(); // summ before
        $row_summ_before = $result->fetch_array();
        $db->next_result();
        $result = $db->store_result();
        $row_profit = $result->fetch_array();
        $db->next_result();
        $result = $db->store_result();
        $row_income = $result->fetch_array();
        $currDates = (int) ((strtotime($period) - strtotime($row_goal["periodStart"])) / (60 * 60 * 24) + 1);
        $allDates = (int) ((strtotime($row_goal["periodEnd"]) - strtotime($row_goal["periodStart"])) / (60 * 60 * 24) + 1);

        // make result data
        $data[] = (int) ($row_summ_before["amount"] + $row_profit["amount"]); // balance
        $data[] = $currDates; // period
        $data[] = (int) $row_income["amount"]; // income
        $data[] = (int) $row_profit["amount"]; // profit
        // throw new Exception($currDates." - ".$allDates);
        //throw new Exception((int) ((strtotime($row_goal["periodEnd"]) - strtotime($row_goal["periodStart"])) / (60 * 60 * 24) + 1));
        $data[] = (int) ($currDates / $allDates * 100); // period_percent
        $data[] = (int) ($row_income["amount"] / $row_goal["income"] * 100); // income_percent
        $data[] = (int) ($row_profit["amount"] / $row_goal["profit"] * 100); // profit_percent

    }
    catch (Exception $e) {
        return ".-=-".$e;
    }

    return "ok-=-".implode("-=-", $data);

}
//------------------------------------------------------

?>
