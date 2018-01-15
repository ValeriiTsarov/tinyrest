<?php

namespace TinyRest;

class ApiConfig
{

  protected $config = [
    'apiRootPath' => '',
  ];
  protected $dbConnectors = [];

  /**
   * ApiConfig constructor.
   * @param $userResourceNamespace
   * @throws ApiException
   */
  public function __construct($userResourceNamespace) {
    $this->config['nameSpace'] = $userResourceNamespace;
    $nameSpaceArr = explode('\\', $userResourceNamespace);
    if (empty($userResourceNamespace) || empty($nameSpaceArr[0])) {
      throw new ApiException("User resource namespace is empty. Please add to ApiConfig namespace to your resources. Example: '\\App\\resources'.");
    }
    $userNameSpaceRoot = $nameSpaceArr[0] . '\\';
    $rnLen = strlen($userResourceNamespace);
    if ('\\' != $userResourceNamespace[$rnLen - 1]) {
      $this->config['nameSpace'] .= '\\';
    }

    $classes = get_declared_classes();
    $loader = null;
    foreach ($classes as $class) {
      if (false !== strpos($class, 'ComposerAutoloaderInit')) {
        $loader = $class::getLoader();
        $prefixes = $loader->getPrefixesPsr4();
        foreach ($prefixes as $prefix=>$path) {
          if (false !== strpos($prefix, $userNameSpaceRoot)) {
            $arr = $nameSpaceArr;
            unset($arr[0]);
            $this->config['apiRootPath'] = $path[0] . '/' . implode(DIRECTORY_SEPARATOR, $arr);
          }
        }
        break;
      }
    }

    if (!$loader) {
      throw new ApiException("Can't find composer loader.");
    }
    if (!$this->config['apiRootPath']) {
      throw new ApiException("Can't detect resources folder by user resources name space.");
    }

    $this->config['requestMethod'] = $_SERVER['REQUEST_METHOD'];
    $requestUri = '';
    if (!empty($_SERVER['REQUEST_URI'])) {
      $requestUri = $_SERVER['REQUEST_URI'];
    }

    $pathArr = explode('?', $requestUri);
    if (empty($pathArr[1])) {
      $pathArr[1] = '';
    }
    $this->config['pathSegments'] = $this->processPathSegments($pathArr[0]);
    $this->config['queryParams'] = array_merge(
      $this->processQueryParams($pathArr[1])
      , $this->parseRawHttpRequest(@file_get_contents("php://input"))
    );

  }

  protected function processPathSegments($input)
  {
    $data = array_values(array_map(
      'rawurldecode',
      array_filter(explode('/', $input), 'strlen')
    ));

    return $data;
  }

  protected function processQueryParams($input)
  {
    $data = [];
    if ($input && ($tmpArr = explode("&", $input))) {
      foreach ($tmpArr as $pair) {
        $tmp = explode("=", $pair);
        $data[urldecode($tmp[0])] = urldecode($tmp[1]);
      }
    }

    return $data;
  }

  protected function parseRawHttpRequest($input)
  {
    $data = [];
    if (!$input) {
      return $data;
    }

    //form-data
    if (strpos($input, 'Content-Disposition: form-data')) {
      $arr = explode('----', $input);
      foreach ($arr as $row) {
        if (!strpos($row, 'name=')) {
          continue;
        }
        $row = str_replace(["\r", "\""], ["\n", ""], $row);
        $tmpArr = array_values(array_filter(explode("\n", explode('name=', $row)[1])));
        $data[$tmpArr[0]] = $tmpArr[1];
      }
      //x-www-form-urlencoded
    } else {
      parse_str($input, $data);
    }
    //DELETE method
    if (empty($data) && ($tmpArr = $this->processQueryParams($input))) {
      foreach ($tmpArr as $pair) {
        $tmp = explode("=", $pair);
        $data[urldecode($tmp[0])] = urldecode($tmp[1]);
      }
    }

    return $data;
  }

  public function getRootPath()
  {
    return $this->config['apiRootPath'];
  }

  public function getNameSpace()
  {
    return $this->config['nameSpace'];
  }

  public function getRequestMethod()
  {
    return $this->config['requestMethod'];
  }

  public function getPathSegments()
  {
    return $this->config['pathSegments'];
  }

  public function getQueryParams()
  {
    return $this->config['queryParams'];
  }

}