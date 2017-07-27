<?php
  
  require_once("Curl.php");
  require_once("VK.php");
  require_once("LongPoll.php");
  require_once("Doit4Me.php");
  require_once("PostgresDb.php");

  error_reporting(E_ALL);
  
  Bot::getInstance()->connectLongPoll();

?>
