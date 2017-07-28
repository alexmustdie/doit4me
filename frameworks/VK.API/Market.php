<?php

require_once("VK.php");

class Market extends VK
{
  protected
    $id,
    $owner_id,
    $title,
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
      $this->title = $items[0]->title;
      $this->price = $items[0]->price;
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

  public function getTitle()
  {
    return $this->title;
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
    $this->title = $data["name"];
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
