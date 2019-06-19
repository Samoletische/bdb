<?php
//$message = 'привет';
$message = '5 пит кофе';
echo json_encode(array('type' => 'message_new', 'object' => array('user_id' => 432201510, 'body' => $message)));
?>
