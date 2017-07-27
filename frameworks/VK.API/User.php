<?php

require_once("VK.php");

class User extends VK
{
  protected
    $id,
    $name = [],
    $photo;

  public function __construct($id)
  {
    $response = SuperUser::getInstance()->makeRequest("users.get", [
      "user_ids" => $id,
      "fields" => "photo_200"
    ]);
   
    $this->id = $response[0]->id;
    $this->name["first"] = $response[0]->first_name;
    $this->name["last"] = $response[0]->last_name;
    $this->photo = $response[0]->photo_200;
  }

  public function getId()
  {
    return $this->id;
  }

  public function getName()
  {
    return $this->name;
  }

  public function getPhoto()
  {
    return $this->photo;
  }
}

?>
