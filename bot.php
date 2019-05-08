<?php

require_once("config.php");

$conf = new Conf($server, $dbUser, $dbPassword, $database, $debug_mode , $log_on_db);
$bot = new Bot($conf);
$mess = $bot->getMessage(file_get_contents('php://input'));
$conf->insertLog('Сообщение: '.$mess, true);
$answers = $bot->makeAnswer();
foreach($answers as $key => $answer) {
  $conf->insertLog('Ответ: '.$answer, true);
  $res = $bot->sendAnswer($answer);
  if ($key == 0) echo $res;
}
//------------------------------------------------------

?>
