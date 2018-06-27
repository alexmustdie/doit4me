<?php

require_once("User.php");

class SuperUser extends User
{
  private static
    $super_user = null;

  const
    ACCESS_TOKEN = "your access token";

  public static function getInstance()
  {
    if (self::$super_user == null)
    {
      self::$super_user = new self();
    }
    
    return self::$super_user;
  }

  private function __construct()
  {
    $this->setAccessToken(self::ACCESS_TOKEN);
    
    $response = $this->makeRequest("users.get", ["fields" => "photo_200"]);

    $this->id = $response[0]->id;
    $this->name[0] = $response[0]->first_name;
    $this->name[1] = $response[0]->last_name;
    $this->photo = $response[0]->photo_200;
  }

  public function getId()
  {
    return $this->id;
  }
}

?>
