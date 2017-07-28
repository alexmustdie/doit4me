<?php

require_once("funcs.php");
require_once("Peer.php");

class Bot extends VK
{
  private static
    $bot = null;

  private
    $peers = [];

  const
    ACCESS_TOKEN = "3f6c6e222c03055506c32fe1e12fbf244d2ea2ce7c233691d672e60138796e5aebacdefe6422aee747685";

  public static function getInstance()
  {
    if (self::$bot == null)
    {
      self::$bot = new self();
    }
    
    return self::$bot;
  }

  private function __construct()
  {
    $this->setAccessToken(self::ACCESS_TOKEN);
    // TODO: get peers from db
  }

  public function connectLongPoll()
  {
    $lp = new LongPoll();
    $lp->getUpdates();
  }

  private function inPeers($peer_id)
  {
    foreach ($this->peers as $i => $peer)
    {
      if ($peer_id == $peer->getId())
      {
        return $i;
      }
    }

    return false;
  }

  private function getPeer($peer_id)
  {
    $i = $this->inPeers($peer_id);

    if ($i !== FALSE)
    {
      return $this->peers[$i];
    }

    return false;
  }

  private function addPeer($peer)
  {
    if (!$this->inPeers($peer->getId()))
    {
      $this->peers[] = $peer;
      return true;
    }
 
    return false;
  }

  private function deletePeer($peer_id)
  {
    $i = $this->inPeers($peer_id);

    if ($i !== FALSE)
    {
      unset($this->peers[$i]);
      return true;
    }

    return false;
  }

  public function replyMessage($message)
  {
    $peer_id = $message->peer_id;
    
    $text = $message->text;
    $attachments = $message->attachments;

    if ($peer = $this->getPeer($peer_id))
    {
      if ($attachments && $attachments[0]->type == "sticker"
        && $attachments[0]->product_id == 1
        && $attachments[0]->item_id == 3)
      {
        $this->sendMessage($peer_id, getReply("GOODBYE")->text);
        $this->deletePeer($peer_id);

        return true;
      }

      if ($peer->isBotChatting())
      {
        $last_reply = $peer->getLastReply();
        $order = $peer->getOrder();

        $case = 1;

        var_dump($last_reply);

        switch ($last_reply->getAlias())
        {
          case "LESSON_ID_REQUEST":
            if (is_numeric($text) && isLesson($text))
            {
              $order->getForm()->setLessonId($text);
              $order->setExecutorId();
            }
            else
            {
              $case = 0;
            }
            break;

          case "SUBJECT_REQUEST":
            $text ? $order->getForm()->setSubject($text) : $case = 0;
            break;

          case "REQUIREMENTS_REQUEST":
            if ($attachments)
            {
              $attachment_ids = [];

              foreach ($attachments as $attachment)
              {
                if ($attachment->type == "photo"
                  || $attachment->type == "video"
                  || $attachment->type == "doc")
                {
                  $attachment_ids[] = $attachment->type . $attachment->item_id;
                }
              }
            }
            $text || count($attachment_ids) > 0 ? $order->getForm()->setRequirements($text, $attachment_ids) : $case = 0;
            break;

          case "TERMS_REQUEST":
            $text ? $order->getForm()->setTerms($text) : $case = 0;
            break;

          case "NOTES_REQUEST":
            if ($text)
            {
              $order->getForm()->setNotes($text);
              $order->addToDB($peer_id);
            }
            else
            {
              $case = 0;
            }
            break;

          case "CREATE_ORDER_SUCCESS":
            if ($text == "TEST"
              || ($attachments[0]->type == "money_transfer"
              && $attachments[0]->amount / 100 == $order->getWork()->getPrice()))
            {
              $this->sendMessage($peer_id, getReply("MONEY_TRANSFER_SUCCESS")->text);
              $peer->sendOrder();
            }
            else
            {
              $case = 0;
            }
            break;

          default:
            break;
        }

        $reply = new Reply($last_reply, $case);
        $reply_text = $reply->getText();
        
        $this->sendMessage($peer_id, $reply_text, null, null, $peer->getReplacements($reply_text));

        switch ($reply->getAlias())
        {
          case "SET_FORM_VALUE_FAIL":
            $this->sendMessage($peer_id, $last_reply->getText());
            return false;
          
          default:
            break;
        }

        $peer->setLastReply($reply);
      }
    }
    else
    {
      if ($attachments && $attachments[0]->type == "market")
      {
        $work = new Work($attachments[0]->item_id);
        $order = new Order($work);
        
        $peer = new Peer($peer_id);
        $peer->setOrder($order);

        $reply_text = $peer->getLastReply()->getText();

        $this->sendMessage($peer_id, $reply_text, null, null, $peer->getReplacements($reply_text));
        $this->addPeer($peer);
      }
      /*else
      {
        $this->sendMessage($peer_id, getReply("CREATE_ORDER_FAIL")->text);
      }*/
    }
  }
}

?>
