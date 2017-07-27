<?php

class VKException extends Exception
{
  private
    $error_code,
    $error_msg,
    $request_params,
    $captcha;
  
  public function __construct($error)
  {
    $this->error_code = $error->error_code;
    $this->error_msg = $error->error_msg;
    $this->request_params = $error->request_params;
    
    if ($captcha_sid = $error->captcha_sid)
    {
      $this->captcha->sid = $captcha_sid;
    }

    if ($captcha_img = $error->captcha_img)
    {
      $this->captcha->img = $captcha_img;
    }
  }

  public function handle()
  {
    switch ($this->error_code)
    {
      case 6:
        echo "Wait 5 seconds\n";
        sleep(5);
        break;

      case 14:
        echo "Captcha error";
        break;

      default:
        var_dump($this->request_params);
        die("Unhandled error! " . $this->error_msg . "\n");
    }
  }
}

abstract class VK
{
  protected
    $access_token;
  
  const
    VERSION = "5.67";
  
  private function __construct() {}

  protected function setAccessToken($access_token)
  {
    $this->access_token = $access_token;
  }

  public function makeRequest($method, $params)
  {
    $curl = new Curl;
    
    $params["access_token"] = $this->access_token;
    $params["v"] = self::VERSION;
    
    try
    {
      $data = $curl->get("https://api.vk.com/method/" . $method . "?" . http_build_query($params));
      $json = json_decode($data);
      
      if ($response = $json->response)
      {
        return $response;
      }
      else
      {
        if ($error = $json->error)
        {
          throw new VKException($error);
        }
        else
        {
          die("Unhandled exception: {$data}\n");
        }
      }
    }
    catch (VKException $e)
    {
      $e->handle();
    }
  }

  public function sendMessage($user_id, $message, $attachments = null, $forward_messages = null, $replacements = null)
  {
    $params = ["user_id" => $user_id];

    if (count($attachments) > 0)
    {
      $params["attachment"] = implode(",", $attachments);
    }

    if (count($forward_messages) > 0)
    {
      $params["forward_messages"] = imlode(",", $forward_messages);
    }

    if (count($replacements) > 0)
    {
      foreach ($replacements as $template => $replacement)
      {
        $message = str_replace("{{$template}}", $replacement, $message);
      }
    }

    $params["message"] = $message;

    $this->makeRequest("messages.send", $params);
  }

  public function getShortLink($url)
  {
    $response = $this->makeRequest("utils.getShortLink", [
      "url" => $url
    ]);

    return $response->short_url;
  }
}

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

class Group extends VK
{
  protected
    $id,
    $title;
  private
    $type;

  public function __construct($id = null)
  {
    if ($id)
    {
      $response = SuperUser::getInstance()->makeRequest("groups.getById", [
        "group_id" => $id
      ]);

      $this->id = $response[0]->id;
      $this->title = $response[0]->title;
      $this->type = $response[0]->type;
    }
  }

  public function create($data)
  {
    $params = [];

    foreach ($data as $key => $value)
    {
      $params[$key] = $value;
    }

    $response = SuperUser::getInstance()->makeRequest("groups.create", $params);

    $this->id = $response->id;
    $this->title = $response->name;
    $this->type = $response->type;
  }

  public function edit($data)
  {
    $params = ["group_id" => $group_id];

    foreach ($data as $key => $value)
    {
      $params[$key] = $value;
    }

    SuperUser::getInstance()->makeRequest("groups.edit", $params);
  }

  public function uploadPhoto($photo)
  {
    $super_user = SuperUser::getInstance();
    
    $response = $super_user->makeRequest("photos.getOwnerPhotoUploadServer", [
      "owner_id" => -$this->id
    ]);

    if ($upload_url = $response->upload_url)
    {
      $curl = new Curl();

      $data = $curl->post($upload_url, [
        "photo" => $photo,
        "access_token" => $this->access_token
      ]);

      $json = json_decode($data);

      $response = $super_user->makeRequest("photos.saveOwnerPhoto", [
        "photo" => $json->photo,
        "server" => $json->server,
        "hash" => $json->hash
      ]);

      $photo = new stdClass();

      $photo->id = $response[0]->id;
      $photo->owner_id = $response[0]->owner_id;

      return $photo;
    }
  }
}

class Market extends VK
{
  protected
    $id,
    $owner_id,
    $price;

  public function __construct($item_id = null)
  {
    if ($item_id)
    {
      $response = SuperUser::getInstance()->makeRequest("market.getById", [
        "item_ids" => $item_id
      ]);
      $items = $response["items"];

      $this->id = $items[0]->id;
      $this->owner_id = $items[0]->owner_id;
      $this->price = $items[0]->price->price;
    }
  }

  public function getId()
  {
    return $this->id;
  }

  public function getOwnerId()
  {
    return $this->owner_id;
  }

  public function getPrice()
  {
    return $this->price;
  }

  public function create($data)
  {
    $params = [];

    foreach ($data as $key => $value)
    {
      $params[$key] = $value;
    }

    $response = SuperUser::getInstance()->makeRequest("market.add", $params);

    $this->id = $response->market_item_id;
    $this->owner_id = $data["owner_id"];
    $this->price = $data["price"];
  }

  public function uploadPhoto($group_id, $photo_path)
  {
    $super_user = SuperUser::getInstance();

    $response = $super_user->makeRequest("photos.getMarketUploadServer", [
      "group_id" => $group_id,
      "main_photo" => 1,
      "crop_x" => 400,
      "crop_y" => 400
    ]);

    if ($upload_url = $response->upload_url)
    {
      $curl = new Curl();

      $data = $curl->post($upload_url, [
        "photo" => curl_file_create($photo_path, "image/jpeg", "photo.jpg"),
        "access_token" => $this->access_token
      ]);

      $json = json_decode($data);

      $response = $super_user->makeRequest("photos.saveMarketPhoto", [
        "group_id" => $group_id,
        "photo" => $json->photo,
        "server" => $json->server,
        "hash" => $json->hash,
        "crop_data" => $json->crop_data,
        "crop_hash" => $json->crop_hash
      ]);

      $photo = new stdClass();

      $photo->id = $response[0]->id;
      $photo->owner_id = $response[0]->owner_id;

      return $photo;
    }
  }
}

?>
