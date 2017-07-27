<?php

class LongPollException extends Exception
{
  private
    $failed,
    $ts;

  public function __construct($json)
  {
    $this->failed = $json->failed;

    if ($ts = $json->ts)
    {
      $this->ts = $ts;
    }
  }

  function handle($lp)
  {
    switch ($this->failed)
    {
      case 1:
        echo "failed: 1\n";
        $lp->updateTs($this->ts);
        break;

      case 2:
        echo "failed: 2\n";
        $lp->getServer();
        break;

      case 3:
        echo "failed: 3\n";
        $lp->getServer();
        break;

      case 4:
        die("Version is incorrect!");
        break;
        
      default:
        break;
    }
  }
}

?>
