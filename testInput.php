<?php
//$message = ''; // пусто
//$message = 'привет'; // неизвестная команда
//$message = 'Ты живой?'; // стандартная команда
//$message = 'Помощь'; // стандартная команда
//$message = '5 пит кофе'; // известная команда
$message = '-10 прод яблоки'; // известная команда
echo json_encode(array('type' => 'message_new', 'object' => array('user_id' => 432201510, 'body' => $message)));
?>
