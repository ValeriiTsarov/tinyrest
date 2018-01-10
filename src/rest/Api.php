<?php

namespace TinyRest\rest;

use TinyRest\helpers\Files;
use TinyRest\rest\ApiException;

/**
 * Class Api is a major router
 * @package TinyRest\request
 */
class Api {

  //Set array position in $this->pathSegments. Example: /rest/v1/driver/journeys/single?driverId=129
  const
      TRACKING_API_NAME_SEGMENT = 0 //rest
    , TRACKING_API_VERSION_SEGMENT = 1 //v1
    , TRACKING_API_GROUP_SEGMENT = 2 //driver
    , TRACKING_API_RESOURCE_SEGMENT = 3 //journeys
    , TRACKING_API_METHOD_SEGMENT = 4 //single

    , PARAM_TYPE_INT = 'int'
    , PARAM_TYPE_STRING = 'string'
    , PARAM_TYPE_ARRAY = 'array' //1, 2, 3
    , PARAM_ARRAY_SEPARATOR = ',' //1, 2, 3
  ;

  protected $oErrors;
  protected $oUri;
  protected $oResource;
  protected $oConfig;

  protected $apiRoot;
  protected $pathSegments;
  protected $queryParams;
  protected $version;
  protected $classGroup;
  protected $resource;
  protected $method;

  public function __construct($requestUri)
  {
    $this->oErrors = new \TinyRest\helpers\Errors();
    $parser = new \Riimu\Kit\UrlParser\UriParser();
    $this->oUri = $parser->parse($requestUri);
    $this->pathSegments = $this->oUri->getPathSegments();
    $this->queryParams = $this->oUri->getQueryParameters();
    $this->apiRoot = dir(__DIR__)->path;
    $this->oConfig = new ApiConfig($this->apiRoot);
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

  public function getResourceObject()
  {
    $this->setVersion();
    $this->setClassGroup();
    $this->setResource();
    $this->setMethod();

    return $this->oResource;
  }

  protected function uriParam($name, $isSet, $notEmpty)
  {
    if (
    (($notEmpty && empty($this->queryParams[$name]))
      || ($isSet && !isset($this->queryParams[$name])))
    ) {
      throw new ApiException("Undefined or empty param [{$name}]", 400);
    }
    $result = null;
    if ( isset( $this->queryParams[$name] ) ) {
      $result = $this->queryParams[$name];
    }

    return $result;
  }

  /**
   * Get required uri param
   *
   * @param $name
   * @param string $type
   * @param int $isSet
   * @param int $notEmpty
   * @return mixed
   * @throws \TinyRest\rest\ApiException
   */
  public function getUriParam($name, $type = '', $isSet = 0, $notEmpty = 0)
  {
    $result = $this->uriParam($name, $isSet, $notEmpty);

    if (!is_null($result) && $type) {
      switch ($type) {
        case self::PARAM_TYPE_INT:
          $result = (int)$result;
          break;
        case self::PARAM_TYPE_STRING:
          if (!is_string($result)) {
            $result = 0;
          }
          break;
        case self::PARAM_TYPE_ARRAY:
          if (($result = explode(self::PARAM_ARRAY_SEPARATOR, $result))) {
            foreach ($result as &$row) {
              $row = trim($row);
            }
          }
          break;
      }
      if (empty($result)) {
        $result = null;
      }
    }

    return $result;
  }

  /**
   * Get uri param. No error will be if it's undefined or empty
   *
   * @param $name
   * @return mixed
   * @throws \TinyRest\rest\ApiException
   */
  public function getUriParamZero($name)
  {
    $result = $this->uriParam($name, false, false);

    return $result;
  }

  /**
   * Getter for an object of ApiConfig
   * @return ApiConfig
   */
  public function getConfig()
  {
    return $this->oConfig;
  }

}