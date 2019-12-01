<?php

require_once("config.php");

try {
  $bot = BotMngr::createBot();
  $conf = $bot->getConf();

  $message = $bot->getMessage();
  if (!$conf['testMode'])
    $bot->insertLog($message->getContentStr()."\n", DEBUG);
  $answer = $message->makeAnswer();
  if (!$conf['testMode'])
    $bot->insertLog($answer->getContentStr()."\n", DEBUG);
  $answer->send();
} catch (BotException $e) {
  echo 'bot can\'t be started, because: '.$e->getMessage()." (".$e->getCode().")\n";
}
//------------------------------------------------------

?>
