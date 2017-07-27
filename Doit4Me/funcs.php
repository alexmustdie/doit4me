<?php

  function getLesson($lesson_id)
  {
    $db = PostgresDb::getInstance();
    $db->where("id", $lesson_id);

    return (object) $db->getOne("lessons");
  }

  function getLessons()
  {
    $lessons = [];

    foreach (PostgresDb::getInstance()->get("lessons") as $lesson)
    {
      $lessons[] = (object) $lesson; 
    }

    return $lessons;
  }

  function isLesson($lesson_id)
  {
    $db = PostgresDb::getInstance();
    $db->where("id", $lesson_id);

    if ($db->getOne("lessons"))
    {
      return true;
    }
    else
    {
      return false;
    }
  }

  function getReply($arg)
  {
    $column_name = is_numeric($arg) ? "id" : "alias";

    $db = PostgresDb::getInstance();
    $db->where($column_name, $arg);

    return (object) $db->getOne("replies");
  }

?>