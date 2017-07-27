<?php

require_once("User.php");

class SuperUser extends User
{
  private static
    $super_user = null;

  const
    ACCESS_TOKEN = "e760fb05fe9965c037175f72fc74461e7f61840eef8f2a7733c94ec47855649b5a0b283b4655014ad8350";

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
    $this->name["first"] = $response[0]->first_name;
    $this->name["last"] = $response[0]->last_name;
    $this->photo = $response[0]->photo_200;
  }

  public function getId()
  {
    return $this->id;
  }
}

?>
