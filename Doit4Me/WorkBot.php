<?php

require_once("funcs.php");
require_once("Peer.php");

class WorkBot extends Bot
{
  const
    ACCESS_TOKEN = "your access token";

  public function __construct()
  {
    $this->setAccessToken(self::ACCESS_TOKEN);
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
            $text && getDaysLeft($text) > 0 ? $order->getForm()->setTerms($text) : $case = 0;
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
              && $attachments[0]->amount / 100 == $order->getAmount()))
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
            $reply_text = $last_reply->getText();
            $this->sendMessage($peer_id, $reply_text, null, null, $peer->getReplacements($reply_text));
            return false;

          case "REQUIREMENTS_REQUEST":
            $this->sendMessage($peer_id, getReply("REQUIREMENTS_REQUEST_NOTE")->text);
            break;

          case "TERMS_REQUEST";
            $this->sendMessage($peer_id, getReply("TERMS_REQUEST_NOTE")->text);
            break;
          
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
        $order->setAmount($work->getPrice());
        
        $peer = new Peer($peer_id);
        $peer->setOrder($order);

        $reply = new Reply();
        $peer->setLastReply($reply);

        $reply_text = $reply->getText();

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
