<?php

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

?>
