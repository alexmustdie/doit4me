<?php

class Form
{
  private
    $lesson_id,
    $subject,
    $requirements,
    $terms,
    $notes;

  public function __construct() {}

  public function setLessonId($lesson_id)
  {
    $this->lesson_id = $lesson_id;
  }

  public function setSubject($subject)
  {
    $this->subject = $subject;
  }

  public function setRequirements($requirements)
  {
    $this->requirements = $requirements;
  }

  public function setTerms($terms)
  {
    $this->terms = $terms;
  }

  public function setNotes($notes)
  {
    $this->notes = $notes;
  }

  public function getData()
  {
    $form = new stdClass();

    $form->lesson_id = $this->lesson_id;
    $form->subject = $this->subject;
    $form->requirements = $this->requirements;
    $form->terms = $this->terms;
    $form->notes = $this->notes;

    return $form;
  }
}

class Work extends Market
{
  public function __construct($item_id)
  {
    $item_id_exp = explode("_", $item_id);

    $db = PostgresDb::getInstance();
    $db->where("id", $item_id_exp[1]);
    
    $work_data = $db->getOne("works");

    $this->id = $work_data["id"];
    $this->owner_id = $work_data["owner_id"];
    $this->price = $work_data["price"];
  }

  public function addToDB()
  {
    $db = PostgresDb::getInstance();
    $db->where("id", $this->id);

    if (!$db->getOne("works"))
    {
      $db->insert("works", [
        "id" => $this->id,
        "owner_id" => $this->owner_id,
        "price" => $this->price
      ]);
    }
    else
    {
      return false;
    }
  }

  public function getLesson($lesson_id)
  {
    $db = PostgresDb::getInstance();
    $db->where("id", $lesson_id);

    return (object) $db->getOne("lessons");
  }

  public function getLessons()
  {
    $lessons = [];

    foreach (PostgresDb::getInstance()->get("lessons") as $lesson)
    {
      $lessons[] = (object) $lesson; 
    }

    return $lessons;
  }

  public function isLesson($lesson_id)
  {
    $db = PostgresDb::getInstance();
    $db->where("id", $lesson_id);

    if ($db->getOne("lessons"))
    {
      return true;
    }
    else
    {
      return false;
    }
  }
}

class Order
{
  private
    $id,
    $work,
    $executor_id,
    $form;

  public function __construct($work)
  {
    $this->setId();
    $this->setWork($work);
    $this->executor_id = 0;
    $this->form = new Form();
  }

  public function addToDB($user_id)
  {
    $db = PostgresDb::getInstance();
    $db->where("id", $this->id);

    if (!$db->getOne("orders"))
    {
      $db->insert("orders", [
        "id" => $this->id,
        "user_id" => $user_id,
        "executor_id" => $this->executor_id,
        "work_id" => $this->work->getId(),
        "time" => date('Y-m-d H:i:s')
      ]);
    }
    else
    {
      return false;
    }
  }

  public function getId()
  {
    return $this->id;
  }

  public function getWork()
  {
    return $this->work;
  }

  public function getExecutorId()
  {
    return $this->executor_id;
  }

  public function getForm()
  {
    return $this->form;
  }

  private function setId()
  {
    $orders = PostgresDb::getInstance()->get("orders");
    $this->id = count($orders) + 1;
  }

  public function setWork($work)
  {
    $this->work = $work;
  }

  public function setExecutorId()
  {
    $db = PostgresDb::getInstance();
    $db->where("lesson_id", $this->form->getData()->lesson_id);
    
    if ($executor_data = $db->getOne("executors"))
    {
      $this->executor_id = $executor_data["id"];
    }
    else
    {
      $this->executor_id = SuperUser::getInstance()->getId();
    }
  }
}

class Peer
{
  private
    $id,
    $last_reply,
    $order,
    $bot_chatting;

  public function __construct($id)
  {
    $this->id = $id;
    $this->last_reply = new Reply();
    $this->bot_chatting = true;
  }

  public function getId()
  {
    return $this->id;
  }

  public function getReply($arg)
  {
    $column_name = is_numeric($arg) ? "id" : "alias";

    $db = PostgresDb::getInstance();
    $db->where($column_name, $arg);

    return (object) $db->getOne("replies");
  }

  public function getLastReply()
  {
    return $this->last_reply;
  }

  public function getOrder()
  {
    return $this->order;
  }

  public function isBotChatting()
  {
    return $this->bot_chatting;
  }

  public function setLastReply($last_reply)
  {
    if ($last_reply->getRefs())
    {
      $this->last_reply = $last_reply;
    }
    else
    {
      return false;
    }
  }

  public function setOrder($order)
  {
    $this->order = $order;
  }

  public function getReplacements($text)
  {
    preg_match_all("/{(.*?)}/", $text, $result);
    $templates = $result[1];

    $replacements = [];

    if (count($templates) > 0)
    {
      foreach ($templates as $template)
      {
        switch ($template)
        {
          case "LINE_BREAK":
            $replacements[$template] = "\n";
            break;

          case "USER_FIRST_NAME":
            $user = new User($this->id);
            $replacements[$template] = $user->getName()["first"];
            break;

          case "EXECUTOR_FIRST_NAME":
            $executor = new User($this->order->getExecutorId());
            $replacements[$template] = $executor->getName()["first"];
            break;

          case "ORDER_ID":
            $replacements[$template] = $this->order->getId();
            break;

          case "LESSONS":
            $lessons_list = "";
            foreach ($this->order->getWork()->getLessons() as $lesson)
            {
              $lessons_list .= "#{$lesson->id} {$lesson->title}\n";
            }
            $replacements[$template] = $lessons_list;
            break;

          case "WORK_PRICE":
            $replacements[$template] = $this->order->getWork()->getPrice();
            break;
          
          default:
            break;
        }
      }
    }

    return $replacements;
  }

  public function sendOrder()
  {
    $super_user = SuperUser::getInstance();

    $order = $this->getOrder();
    $work = $order->getWork();
    $form_data = $order->getForm()->getData();

    $link = $super_user->getShortLink("https://vk.com/gim" . -$work->getOwnerId() ."?sel=" . $this->getId());
    
    $message = "ID заказа: {$order->getId()}\n"
      . "Перейти: {$link}\n"
      . "Предмет: {$work->getLesson($form_data->lesson_id)->title}\n"
      . "Тема: {$form_data->subject}\n"
      . "Требования: {$form_data->requirements}\n"
      . "Сроки: {$form_data->terms}\n"
      . "Дополнительно: {$form_data->notes}";

    $super_user->sendMessage($order->getExecutorId(), $message, ["market{$work->getOwnerId()}_{$work->getId()}"]);
    $this->bot_chatting = false;
  }
}

class Reply
{
  private
    $id,
    $alias,
    $text,
    $refs;

  public function __construct($last_reply = null, $case = null)
  {
    if ($last_reply)
    {
      $reply_id = $last_reply->refs[$case];
    }
    else
    {
      $reply_id = 0;
    }

    $db = PostgresDb::getInstance();

    $db->where("id", $reply_id);
    $reply_data = $db->getOne("replies");

    $this->id = $reply_data["id"];
    $this->alias = $reply_data["alias"];
    $this->text = $reply_data["text"];
    $this->refs = json_decode($reply_data["refs"]);
  }

  public function getId()
  {
    return $this->id;
  }

  public function getAlias()
  {
    return $this->alias;
  }

  public function getText()
  {
    return $this->text;
  }

  public function getRefs()
  {
    return $this->refs;
  }
}

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

    if ($peer = $this->getPeer($peer_id))
    {
      if ($message->attach1_type == "sticker"
        && $message->attach1_product_id == 1
        && $message->attach1 == 3)
      {
        $reply = $peer->getReply("GOODBYE");
        
        $this->sendMessage($peer_id, $reply->text);
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
            if (is_numeric($text) && $order->getWork()->isLesson($text))
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
            $text ? $order->getForm()->setRequirements($text) : $case = 0;
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
              || ($message->attach1_type == "money_transfer"
              && $message->attach1_amount / 100 == $order->getWork()->getPrice()))
            {
              $reply = $peer->getReply("MONEY_TRANSFER_SUCCESS");
              $this->sendMessage($peer_id, $reply->text);
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
      if ($message->attach1_type == "market")
      {
        $work = new Work($message->attach1);
        $order = new Order($work);
        
        $peer = new Peer($peer_id);
        $peer->setOrder($order);

        $reply_text = $peer->getLastReply()->getText();

        $this->sendMessage($peer_id, $reply_text, null, null, $peer->getReplacements($reply_text));
        $this->addPeer($peer);
      }
      /*else
      {
        $reply = $this->getReply("CREATE_ORDER_FAIL");
        $this->sendMessage($peer_id, $reply->text);
      }*/
    }
  }
}

?>
