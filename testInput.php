<?php
//$message = ''; // пусто
//$message = 'привет'; // неизвестная команда
//$message = 'Ты живой?'; // стандартная команда
//$message = 'Помощь'; // стандартная команда
//$message = '5 пит кофе'; // известная команда
//$message = '-92 дата 2019-06-11 абри 0,5 кг'; // известная команда
$message = 'пользователь паша -'; // известная команда
echo json_encode(array('type' => 'message_new', 'object' => array('user_id' => 432201510, 'body' => $message)));
//echo json_encode(array('type' => 'confirmation', 'group_id' => '184068927'));
?>
