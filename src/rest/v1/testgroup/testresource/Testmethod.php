<?php

namespace TinyRest\rest\v1\testgroup\testresource;

class Testmethod
{

  public function get()
  {
    $arr = ['methodResourceResult' => 'Test OK!'];

    return $arr;
  }

}