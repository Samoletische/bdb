<?php

require_once("config.php");

$conf = new Conf($server, $dbUser, $dbPassword, $database, $debug_mode);
$bot = new Bot($conf);
$mess = $bot->getMessage('php://input');
$conf->insertLog('Сообщение: '.$mess, true);
$answer = $bot->makeAnswer();
$conf->insertLog('Ответ: '.$answer, true);
echo $bot->sendAnswer($answer);
//------------------------------------------------------

?>
