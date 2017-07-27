<?php

class Form
{
  private
    $lesson_id,
    $subject,
    $requirements,
    $terms,
    $notes;

  public function __construct() {}

  public function setLessonId($lesson_id)
  {
    $this->lesson_id = $lesson_id;
  }

  public function setSubject($subject)
  {
    $this->subject = $subject;
  }

  public function setRequirements($requirements)
  {
    $this->requirements = $requirements;
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
