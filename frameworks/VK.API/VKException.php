<?php

class VKException extends Exception
{
  private
    $error_code,
    $error_msg,
    $request_params,
    $captcha;
  
  public function __construct($error)
  {
    $this->error_code = $error->error_code;
    $this->error_msg = $error->error_msg;
    $this->request_params = $error->request_params;
    
    if ($captcha_sid = $error->captcha_sid)
    {
      $this->captcha->sid = $captcha_sid;
    }

    if ($captcha_img = $error->captcha_img)
    {
      $this->captcha->img = $captcha_img;
    }
  }

  public function handle()
  {
    switch ($this->error_code)
    {
      case 6:
        echo "Wait 5 seconds\n";
        sleep(5);
        break;

      case 14:
        echo "Captcha error";
        break;

      default:
        var_dump($this->request_params);
        die("Unhandled error! " . $this->error_msg . "\n");
    }
  }
}

?>
