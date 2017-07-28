<?php

class Form
{
  private
    $lesson_id,
    $subject,
    $requirements,
    $terms,
    $notes;

  public function __construct()
  {
    $this->requirements = new stdClass();

    $this->requirements->text = "";
    $this->requirements->attachment_ids = [];
  }

  public function setLessonId($lesson_id)
  {
    $this->lesson_id = $lesson_id;
  }

  public function setSubject($subject)
  {
    $this->subject = $subject;
  }

  public function setRequirements($text = "См. вложения", $attachment_ids = null)
  {
    if ($text)
    {
      $this->requirements->text = $text;
    }

    if ($attachment_ids)
    {
      $this->requirements->attachment_ids = $attachment_ids;
    }
  }

  public function setTerms($terms)
  {
    $this->terms = $terms;
  }

  public function setNotes($notes)
  {
    $this->notes = $notes;
  }

  public function getData()
  {
    $form = new stdClass();

    $form->lesson_id = $this->lesson_id;
    $form->subject = $this->subject;
    $form->requirements = $this->requirements;
    $form->terms = $this->terms;
    $form->notes = $this->notes;

    return $form;
  }
}

?>
