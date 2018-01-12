<?php

namespace TinyRest\rest;

class ApiConfig
{

  protected $config = [];
  protected $dbConnectors = [];

  /**
   * ApiConfig constructor.
   * @param $userResourceNamespace The symbols "\\" at the end of the string is important!
   * @param $userResourceDir
   */
  public function __construct($userResourceNamespace) {
//    $this->config['apiRootPath'] = $userResourceDir;, $userResourceDir
    $this->config['nameSpace'] = $userResourceNamespace;
  }

  public function getRootPath()
  {
    return $this->config['apiRootPath'];
  }

  public function getNameSpace()
  {
    return $this->config['nameSpace'];
  }

}