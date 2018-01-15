<?php

namespace TinyRest;

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

  protected $oValidator;
  protected $oResource;
  protected $oConfig;

  protected $pathSegments;
  protected $version;
  protected $classGroup;
  protected $resource;
  protected $method;

  public function __construct(ApiConfig $oConfig)
  {
    $this->oConfig = $oConfig;
    $this->pathSegments = $this->oConfig->getPathSegments();
    $this->oValidator = new ApiValidator();
  }

  /**
   * @param array $dirs
   * @return bool
   */
  protected function isDir(array $dirs = [])
  {
    $dirs = array_merge([$this->oConfig->getRootPath()], $dirs);
    $result = true;
    if (!is_dir(implode(DIRECTORY_SEPARATOR, $dirs))) {
      $result = false;
    }

    return $result;
  }

  /**
   * @param array $dirs
   * @param bool $isFolder
   *
   * @return array
   */
  protected function getDirList(array $dirs = [], $isFolder = true) {
    $dir = implode(DIRECTORY_SEPARATOR, array_merge([$this->oConfig->getRootPath()], $dirs));
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
      $className = $this->classNameByNameSpace([$this->version, $this->classGroup, ucfirst($this->resource)]);
      $className = $this->oConfig->getNameSpace().$className;
      if (!class_exists($className)) {
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
   * @throws \TinyRest\ApiException
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
   * @throws \TinyRest\ApiException
   */
  public function getParam($name, $type = '', $isSet = 0, $notEmpty = 0)
  {
    $error = false;
    $result = null;
    $qp = $this->oConfig->getQueryParams();

    if (isset($qp[$name])) {
      if (empty($qp[$name])) {
        if ($notEmpty) {
          $error = true;
        }
      } else {
        $result = $this->oValidator->check($qp[$name], $type);
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
   * The last element of the namespace is a class file.
   * Example: ['v1', 'game', 'rules', ]
   * @param array $nameSpace
   * @return string
   */
  public function classNameByNameSpace(array $nameSpace)
  {
    $classNameArr = [];
    $theLast = count($nameSpace)-1;
    foreach ($nameSpace as $key => $value) {
      $value = strtolower($value);
      if ($key == $theLast) {
        $value = ucfirst($value);
      }
      $classNameArr[] = $value;
    }

    return implode('\\', $classNameArr);
  }

}