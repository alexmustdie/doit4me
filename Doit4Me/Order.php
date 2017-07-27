<?php

require_once("Work.php");
require_once("Form.php");

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

  public function addToDB($client_id)
  {
    $db = PostgresDb::getInstance();
    $db->where("id", $this->id);

    if (!$db->getOne("orders"))
    {
      $db->insert("orders", [
        "id" => $this->id,
        "client_id" => $client_id,
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

?>
