<?php

require_once("Reply.php");
require_once("Order.php");

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

          case "CLIENT_FIRST_NAME":
            $client = new User($this->id);
            $replacements[$template] = $client->getName()[0];
            break;

          case "EXECUTOR_FIRST_NAME":
            $executor = new User($this->order->getExecutorId());
            $replacements[$template] = $executor->getName()[0];
            break;

          case "ORDER_ID":
            $replacements[$template] = $this->order->getId();
            break;

          case "LESSONS":
            $lessons_list = "";
            foreach (getLessons() as $lesson)
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
      . "Предмет: " . getLesson($form_data->lesson_id)->title . "\n"
      . "Тип работы: {$work->getTitle()}\n"
      . "Тема: {$form_data->subject}\n"
      . "Требования: {$form_data->requirements->text}\n"
      . "Сроки: {$form_data->terms}\n"
      . "Дополнительно: {$form_data->notes}";

    $super_user->sendMessage($order->getExecutorId(), $message, $form_data->requirements->attachment_ids);
    $this->bot_chatting = false;
  }
}

?>
