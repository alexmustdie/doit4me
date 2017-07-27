<?php

require_once("VK.php");

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

?>
