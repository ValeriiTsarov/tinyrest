<?php

namespace TinyRest\request;

class HttpException extends \Exception
{
  public function __construct($message = "", $code = 0, Throwable $previous = null) {
    if (!$message) {
      $message = "Not implemented.";
    }
    if (!$code) {
      $code = 501;
    }
    parent::__construct($message, $code, $previous);
    $this->message = $message;
    $this->code = $code;
  }
}