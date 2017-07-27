<?php

require_once("LongPollException.php");

class LongPoll
{
  private
    $key,
    $server,
    $ts;

  const
    WAIT = 90,
    MODE = 2,
    VERSION = 2;

  public function __construct()
  {
    $this->getServer();
  }

  private function getServer()
  {
    $response = Bot::getInstance()->makeRequest("messages.getLongPollServer", [
      "need_pts" => 1,
      "lp_version" => self::VERSION
    ]);

    print_r($response);
    echo "\n\n";
    
    $this->key = $response->key;
    $this->server = $response->server;
    $this->ts = $response->ts;
  }

  private function updateTs($new_ts)
  {
    $this->ts = $new_ts;
  }

  public function getUpdates()
  {
    $curl = new Curl();

    try
    {
      $params = [
        "act" => "a_check",
        "key" => $this->key,
        "ts" => $this->ts,
        "wait" => self::WAIT,
        "mode" => self::MODE,
        "version" => self::VERSION
      ];

      $data = $curl->get("https://" . $this->server . "?" . http_build_query($params));
      $json = json_decode($data);

      if (!$json->failed)
      {
        if (count($json->updates) > 0)
        {
          foreach ($json->updates as $update)
          {
            $this->handleUpdate($update);
          }
        }
        
        $this->updateTs($json->ts);
        $this->getUpdates();
      }
      else
      {
        throw new LongPollException($json);
      }
    }
    catch (LongPollException $e)
    {
      $e->handle($this);
    }
  }

  private function handleUpdate($update)
  {
    switch ($update[0])
    {
      case 4:
        $message = new stdClass();

        $message->id = $update[1];
        $message->flags = $update[2];
        $message->peer_id = $update[3];
        $message->time = $update[4];
        $message->text = $update[5];

        if ($attachments = $update[6])
        {
          foreach ($attachments as $key => $attachment)
          {
            $message->$key = $attachment;
          }
        }

        if (!($message->flags & 2))
        {
          var_dump($message);
          Bot::getInstance()->replyMessage($message);
        }

        break;

      default:
        // echo "Unknown update.\n";
        break;
    }
  }
}

?>
