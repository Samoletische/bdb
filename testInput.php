<?php
//$message = ''; // пусто
//$message = 'привет'; // неизвестная команда
//$message = 'Ты живой?'; // стандартная команда
//$message = 'Помощь'; // стандартная команда
//$message = '5 пит кофе'; // известная команда
//$message = '-92 дата 2019-06-11 абри 0,5 кг'; // известная команда
//$message = 'статьи абри'; // известная команда
$message = 'пользователь димаф депозит -100'; // известная команда
echo json_encode(array('type' => 'message_new', 'object' => array('user_id' => 432201510, 'body' => $message)));
//echo json_encode(array('type' => 'confirmation', 'group_id' => '184068927'));

$datePrevStart = date('Y-m-01 00:00:00', mktime(0, 0, 0, date('m')-1, date('d'), date('Y')));
$dateStart = date('Y-m').'-01 00:00:00';
$dateEnd = date('Y-m').'-'.date('d H:i:s', mktime(23, 59, 59, date('m'), date('t'), date('Y')));
echo "\n".$datePrevStart.' - '.$dateStart.' - '.$dateEnd."\n";
?>
