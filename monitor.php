<?php

require_once("config.php");

try {
  $bot = BotMngr::createBot();
  $debtors = $bot->getDebtors();
  foreach($debtors as $debtor) {
    // проверяем по таблице monitor отправляли ли текущему пользователю сообщение сегодня
    // если не отправляли, - отправляем
  }
} catch (BotException $e) {
  echo 'bot can\'t be started, because: '.$e->getMessage()." (".$e->getCode().")\n";
}
//------------------------------------------------------

?>
