<?php

namespace TinyRest\rest\v1\testgroup\testresource;

class Testmethod
{

  public function get()
  {
    $arr = ['methodSubClass' => 'OK'];

    return $arr;
  }

}