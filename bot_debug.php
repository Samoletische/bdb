<?php

require_once("config.php");

if (isset($_GET['mes'])) {
	
	$input = array( 'object' => array ( 'body' => $_GET['mes'], 'user_id' => '152772513'), 'type' => 'message_new' );
	
	$conf = new Conf($server, $dbUser, $dbPassword, $database, $debug_mode, $log_on_db);
	$bot = new Bot($conf);
	$mess = $bot->getMessage(json_encode($input));
	echo "message=".$mess."<br/>";
	$answer = $bot->makeAnswer();
	echo "answer=".$answer."<br/>";
}
else
	echo "no message";
//------------------------------------------------------

?>
