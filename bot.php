<?php

require_once("config.php");

try {
  $bot = BotMngr::createBot();
  $message = $bot->getMessage();
  $answer = $message->makeAnswer();
  $answer->send();
} catch (BotException $e) {
  echo 'bot can\'t be started, because: '.$e->getMessage()."\n";
}
//------------------------------------------------------

?>
