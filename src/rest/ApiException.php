<?php

namespace TinyRest\rest;

class ApiException extends \Exception
{
  public function __construct($message = "", $code = 0, Throwable $previous = null) {
    if (!$message) {
      $message = "This root have no elements.";
    }
    if (!$code) {
      $code = 200;
    }
    $this->message = $message;
    $this->code = $code;
  }
}