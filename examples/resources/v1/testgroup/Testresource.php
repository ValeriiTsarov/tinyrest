<?php

namespace App\resources\v1\testgroup;


use TinyRest\ApiResourceBase;

class Testresource extends ApiResourceBase
{

  protected $unavailableMethods = ['testMethodUnsupported'];

  public function testMethod()
  {

    $oClass = $this->getSubClassFromMethod(__METHOD__);

    return ['resourceResult' => $oClass->get()];
  }

  public function testMethodInside()
  {

    return ['resourceResult' => 'TestMethodInside'];
  }

  public function testMethodUnsupported()
  {

    return ['resourceResult' => 'TestMethodInside'];
  }

  public function testSubClass()
  {

    $subClass = $this->oApi->getParam(
      'subClass'
      , $this->oApi->getValidator()->getTypeString()
      , 1
      , 1
    );
    $oClass = $this->getSubClass($subClass);

    return ['resourceResult' => $oClass->get()];
  }

  protected function onlyPublicSupport()
  {
    //User can't get access to protected method
  }
}