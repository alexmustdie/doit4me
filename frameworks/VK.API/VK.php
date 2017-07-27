<?php

require_once("VKException.php");

abstract class VK
{
  protected
    $access_token;
  
  const
    VERSION = "5.67";
  
  private function __construct() {}

  protected function setAccessToken($access_token)
  {
    $this->access_token = $access_token;
  }

  public function makeRequest($method, $params)
  {
    $curl = new Curl;
    
    $params["access_token"] = $this->access_token;
    $params["v"] = self::VERSION;
    
    try
    {
      $data = $curl->get("https://api.vk.com/method/" . $method . "?" . http_build_query($params));
      $json = json_decode($data);
      
      if ($response = $json->response)
      {
        return $response;
      }
      else
      {
        if ($error = $json->error)
        {
          throw new VKException($error);
        }
        else
        {
          die("Unhandled exception: {$data}\n");
        }
      }
    }
    catch (VKException $e)
    {
      $e->handle();
    }
  }

  public function sendMessage($user_id, $message, $attachments = null, $forward_messages = null, $replacements = null)
  {
    $params = ["user_id" => $user_id];

    if (count($attachments) > 0)
    {
      $params["attachment"] = implode(",", $attachments);
    }

    if (count($forward_messages) > 0)
    {
      $params["forward_messages"] = imlode(",", $forward_messages);
    }

    if (count($replacements) > 0)
    {
      foreach ($replacements as $template => $replacement)
      {
        $message = str_replace("{{$template}}", $replacement, $message);
      }
    }

    $params["message"] = $message;

    $this->makeRequest("messages.send", $params);
  }

  public function getShortLink($url)
  {
    $response = $this->makeRequest("utils.getShortLink", [
      "url" => $url
    ]);

    return $response->short_url;
  }
}

?>
