<?php

require_once("config.php");

try {
  $bot = BotMngr::createBot();
  $message = $bot->getMessage();
  //++ debug
  print_r($message->getContent());
  //--
  $answer = $message->makeAnswer();
  //++ debug
  print_r($answer->getContent());
  //--
  //$answer->send();
} catch (BotException $e) {
  echo 'bot can\'t be started, because: '.$e->getMessage()." (".$e->getCode().")\n";
}
//------------------------------------------------------

?>
