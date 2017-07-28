<?php

  error_reporting(E_ALL);
  
  require_once("frameworks/Curl.php");
  require_once("frameworks/PostgresDb.php");
  require_once("frameworks/VK.API/SuperUser.php");
  require_once("frameworks/VK.API/User.php");
  require_once("frameworks/VK.API/Market.php");
  require_once("frameworks/VK.API/LongPoll.php");
  require_once("frameworks/VK.API/Bot.php");
  require_once("Doit4Me/WorkBot.php");
  
  $work_bot = new WorkBot();
  $work_bot->connectLongPoll();

?>
