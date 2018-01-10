<?php


namespace TinyRest\rest\v1\testgroup;

use TinyRest\rest\v1\ResourceBase;

class Testresource extends ResourceBase
{

  protected $availableMethods = ['testmethod', 'testmethodinside'];

  public function TestMethod()
  {

    $oClass = $this->getSubClassFromMethod(__METHOD__);

    return ['resourceResult' => $oClass->get()];
  }

  public function TestMethodInside()
  {

    return ['resourceResult' => 'TestMethodInside'];
  }

  public function TestMethodUnsupported()
  {

    return ['resourceResult' => 'TestMethodInside'];
  }

}