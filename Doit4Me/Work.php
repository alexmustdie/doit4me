<?php

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
    $this->title = $work_data["title"];
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
}

?>
