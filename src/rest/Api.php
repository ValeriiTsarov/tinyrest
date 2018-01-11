<?php

namespace TinyRest\rest;

use TinyRest\helpers\Files;
use TinyRest\helpers\Errors;
use TinyRest\rest\ApiException;

/**
 * Class Api is a major router
 * @package TinyRest\request
 */
class Api {

  //Set array position in $this->pathSegments. Example: http://localhost/rest/v1/testgroup/testresource/testmethodinside
  const
      TRACKING_API_NAME_SEGMENT = 0 //rest
    , TRACKING_API_VERSION_SEGMENT = 1 //v1
    , TRACKING_API_GROUP_SEGMENT = 2 //driver
    , TRACKING_API_RESOURCE_SEGMENT = 3 //journeys
    , TRACKING_API_METHOD_SEGMENT = 4 //single

  ;

  protected $oErrors;
  protected $oUri;
  protected $oValidator;
  protected $oResource;
  protected $oConfig;

  protected $apiRoot;
  protected $pathSegments;
  protected $queryParams;
  protected $version;
  protected $classGroup;
  protected $resource;
  protected $method;
  protected $requestMethod;

  public function __construct($requestUri)
  {
    $this->requestMethod = $_SERVER['REQUEST_METHOD'];
    $this->oErrors = new Errors();
    $pathArr = explode('?', $requestUri);
    $this->pathSegments = $this->getPathSegments($pathArr[0]);
    if (empty($pathArr[1])) {
      $pathArr[1] = '';
    }
    $this->queryParams = array_merge(
      $this->getQueryParams($pathArr[1])
      , $this->parseRawHttpRequest(@file_get_contents("php://input"))
    );
    $this->apiRoot = dir(__DIR__)->path;
    $this->oConfig = new ApiConfig($this->apiRoot);
    $this->oValidator = new ApiValidator();
  }

  protected function getQueryParams($input)
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

  protected function getPathSegments($input)
  {
    $data = array_values(array_map(
      'rawurldecode',
      array_filter(explode('/', $input), 'strlen')
    ));

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
    if (empty($data) && ($tmpArr = $this->getQueryParams($input))) {
      foreach ($tmpArr as $pair) {
        $tmp = explode("=", $pair);
        $data[urldecode($tmp[0])] = urldecode($tmp[1]);
      }
    }

    return $data;
  }

  protected function isDebugMode()
  {
    $result = 0;

    return $result;
  }

  /**
   * @param array $dirs
   * @param string $separator DIRECTORY_SEPARATOR
   * @return bool
   */
  protected function isDir(array $dirs = [], $separator = DIRECTORY_SEPARATOR)
  {
    $dirs = array_merge([$this->apiRoot], $dirs);
    $result = true;
    if (!is_dir(implode($separator, $dirs))) {
      $result = false;
    }

    return $result;
  }

  /**
   * @param array $dirs
   * @param string $separator DIRECTORY_SEPARATOR
   *
   * @return array
   */
  protected function getDirList(array $dirs = [], $isFolder = true, $separator = DIRECTORY_SEPARATOR) {
    $dir = implode($separator, array_merge([$this->apiRoot], $dirs));
    $cdir = array_diff(scandir($dir), ['..', '.']);
    $list = [];
    foreach ($cdir as $value)
    {
      if (is_dir($dir . DIRECTORY_SEPARATOR . $value))
      {
        if ( $isFolder ) {
          $list[] = $value;
        }

      } else if (!$isFolder) {
        $list[] = $value;
      }
    }

    return $list;
  }

  protected function setVersion()
  {
    $errorCode = 0;
    $errorMessage = "";
    if (empty($this->pathSegments[self::TRACKING_API_VERSION_SEGMENT])) {
      $errorCode = 400;
    } else {
      $this->version = $this->pathSegments[self::TRACKING_API_VERSION_SEGMENT];
      if (!$this->isDir([$this->version])) {
        $errorMessage = "[{$this->version}] is not supported. ";
        $errorCode = 400.0221;
      }
    }

    if ( $errorCode ) {
      $elements = $this->getDirList();
      $errorMessage .= "Available versions: ".implode(", ", $elements);
      throw new ApiException($errorMessage, $errorCode);
    }

  }

  protected function setClassGroup()
  {
    $errorCode = 0;
    $errorMessage = "";
    if (empty($this->pathSegments[self::TRACKING_API_GROUP_SEGMENT])) {
      $errorCode = 400;
    } else {
      $this->classGroup = $this->pathSegments[self::TRACKING_API_GROUP_SEGMENT];

      if (!$this->isDir([$this->version, $this->classGroup])) {
        $errorMessage = "[{$this->classGroup}] is not supported. ";
        $errorCode = 400.0221;
      }
    }

    if ( $errorCode ) {
      $elements = $this->getDirList([$this->version]);
      $errorMessage .= "Available groups: ".implode(", ", $elements);
      throw new ApiException($errorMessage, $errorCode);
    }

  }

  protected function setResource()
  {
    $errorCode = 0;
    $errorMessage = "";
    $className = "";
    if (empty($this->pathSegments[self::TRACKING_API_RESOURCE_SEGMENT])) {
      $errorCode = 400;
    } else {
      $this->resource = strtolower($this->pathSegments[self::TRACKING_API_RESOURCE_SEGMENT]);
      $className = Files::classNameByNameSpace([$this->version, $this->classGroup, ucfirst($this->resource)]);
      $classPath = Files::getClassPathByClassName($this->apiRoot, $className);

      if (!file_exists($classPath)) {
        $errorMessage = "[{$this->resource}] is not supported. ";
        $errorCode = 400.0001;
      }
    }

    if ( $errorCode ) {
      $elements = $this->getDirList([$this->version, $this->classGroup], false);
      foreach ( $elements as $key => $value ) {
        $elements[$key] = strtolower(str_replace(".php", "", $value));
      }
      $errorMessage .= "Available resources: ".implode(", ", $elements);
      throw new ApiException($errorMessage, $errorCode);
    }

    $className = $this->oConfig->getNameSpace().$className;
    $this->oResource = new $className($this);
    
  }

  protected function setMethod()
  {
    $errorCode = 0;
    $errorMessage = "";
    if (empty($this->pathSegments[self::TRACKING_API_METHOD_SEGMENT])) {
      $errorCode = 400;
    } else {
      $this->method = strtolower($this->pathSegments[self::TRACKING_API_METHOD_SEGMENT]);
      if (!$this->oResource->methodIsAvailable($this->method)) {
        $errorMessage = "[{$this->method}] is not supported. ";
        $errorCode = 400.0221;
      }
    }

    if ( $errorCode ) {
      $errorMessage .= "Methods are supported: ".implode(", ", $this->oResource->getAvailableMethods());
      throw new ApiException($errorMessage, $errorCode);
    }

  }

  public function getVersion()
  {
    return $this->version;
  }

  public function getGroup()
  {
    return $this->classGroup;
  }

  public function getResource()
  {
    return $this->resource;
  }

  public function getMethod()
  {
    return $this->method;
  }

  /**
   * Get resource object if it's possible
   *
   * @return mixed
   * @throws \TinyRest\rest\ApiException
   */
  public function getResourceObject()
  {
    $this->setVersion();
    $this->setClassGroup();
    $this->setResource();
    $this->setMethod();

    return $this->oResource;
  }

  /**
   * Get param
   *
   * @param $name
   * @param string $type
   * @param int $isSet
   * @param int $notEmpty
   * @return mixed
   * @throws \TinyRest\rest\ApiException
   */
  public function getParam($name, $type = '', $isSet = 0, $notEmpty = 0)
  {
    $error = false;
    $result = null;

    if (isset($this->queryParams[$name])) {
      if (empty($this->queryParams[$name])) {
        if ($notEmpty) {
          $error = true;
        }
      } else {
        $result = $this->oValidator->check($this->queryParams[$name], $type);
      }
    } else if ($isSet) {
      $error = true;
    }
    if ($error) {
      throw new ApiException("Undefined or empty param [{$name}]", 400);
    }

    return $result;
  }

  /**
   * Getter for an object of config
   * @return ApiConfig
   */
  public function getConfig()
  {
    return $this->oConfig;
  }

  /**
   * Getter for an object of validator
   * @return ApiValidator
   */
  public function getValidator()
  {
    return $this->oValidator;
  }

  /**
   * Return a type of the request method: GET|POST|PUT etc.
   * @return string
   */
  public function getRequestMethod()
  {
    return $this->requestMethod;
  }

}