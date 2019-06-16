<?php

require_once("config.php");

try {
  $bot = BotMngr::createBot();
  $message = $bot->getMessage();
  //++ debug
  $content = $message->getContent()[0];
  print_r('content: '.$content['message']."\n");
  print_r('type: '.$content['type']."\n");
  //--
  $answer = $message->makeAnswer();
  $answer->send();
} catch (BotException $e) {
  echo 'bot can\'t be started, because: '.$e->getMessage()."\n";
}
//------------------------------------------------------

?>
