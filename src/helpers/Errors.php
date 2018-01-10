<?php

namespace TinyRest\helpers;

/**
 * Class Errors
 * @package TinyRest\helpers
 */
class Errors
{
  protected $errors = [];

  public function addError($text)
  {
    $this->errors[] = $text;
  }

  /**
   * @param string $glue
   * @return string
   */
  public function getErrorsAsString($glue = "\n")
  {
    $result = implode($glue, $this->errors);
    return $result;
  }

}