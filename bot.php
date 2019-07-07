<?php

require_once("config.php");

try {
  $bot = BotMngr::createBot();
  $conf = $bot->getConf();

  $message = $bot->getMessage();
  $bot->insertLog($message->getContentStr()."\n", DEBUG);
  $answer = $message->makeAnswer();
  $bot->insertLog($answer->getContentStr()."\n", DEBUG);
  if (!$conf['testMode'])
    $answer->send();
} catch (BotException $e) {
  echo 'bot can\'t be started, because: '.$e->getMessage()." (".$e->getCode().")\n";
}
//------------------------------------------------------

?>
