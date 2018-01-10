<?php

namespace TinyRest\rest\v1;

use TinyRest\rest\Api;
use TinyRest\rest\ApiException;
use TinyRest\helpers\Files;

/**
 * Class ResourceBase
 * @package TinyRest\rest\v1
 */
class ResourceBase
{
  private $oDB;

  protected $availableMethods = [];
  protected $oApi;
  protected $params = [];
  /* Params example
      [
        'driverId' => [ //Get data from URI
          'type' => Api::PARAM_TYPE_INT,
          'require' => 1,
        ]
        'accessLevels' => [ //Get data from $_SESSION
          'type' => Api::PARAM_TYPE_ARRAY,
          'require' => 1,
          'session' => 'accessible_levels',
        ],
      ],
  */
  protected $paramValues = []; //Array for values of params

  /**
   * Connector to DB
   *
   * @return \PDO
   */
  protected function getDB()
  {
    if (is_null($this->oDB)) {
      $this->oDB = $this->oApi->getConfig()->getMasterDB();
    }

    return $this->oDB;
  }

  public function __construct(Api $oApi)
  {
    $this->oApi = $oApi;
  }

  /**
   * @return array
   */
  public function getAvailableMethods() {

    return $this->availableMethods;
  }

  /**
   * It's possible to disable any method inside a child class
   * @param $method
   *
   * @return bool
   */
  public function methodIsAvailable($method) {
    $result = true;
    if (!in_array($method, $this->getAvailableMethods())) {
      $result = false;
    }

    return $result;
  }

  /**
   * Call method from child resource
   *
   * @param $method
   * @return mixed
   * @throws ApiException
   */
  public function callMethod($method)
  {
    $this->fillRequiredParams();

    return call_user_func([$this, $method]);
  }

  public function fillRequiredParams()
  {
    $fail = false;
    $failText = "Undefined or empty parameter. Required params: ";
    foreach ($this->params as $param => $value) {
      $this->paramValues[$param] = null;
      if (!empty($value['session'])) {
        if (isset($_SESSION[$value['session']])) {
          $this->paramValues[$param] = $_SESSION[$value['session']];
        }
      } else {
        $this->paramValues[$param] = $this->oApi->getUriParam($param, $value['type']);
      }
      if (
        is_null($this->paramValues[$param])
        && !empty($value['require'])
      ) {
        $fail = true;
      }
      $failText .= "{$param}={$value['type']}";
    }

    if ($fail) {
      throw new ApiException($failText, 400);
    }
  }

  protected function getSubClass($subClass)
  {
    $path = explode('\\', get_class($this));
    $myClass = array_pop($path);

    $className = Files::classNameByNameSpace([
      $this->oApi->getVersion(),
      $this->oApi->getGroup(),
      strtolower($myClass),
      $subClass
    ]);
    $classPath = Files::getClassPathByClassName($this->oApi->getConfig()->getRootPath(), $className);

    if (!file_exists($classPath)) {
      $errorMessage = "Sub resource [{$subClass}] is not found. ";
      $errorCode = 400.0001;
      throw new ApiException($errorMessage, $errorCode);
    }

    $className = $this->oApi->getConfig()->getNameSpace().$className;
    $oClass = new $className($this);

    return $oClass;
  }

  protected function getSubClassFromMethod($path)
  {
    $method = explode('::', $path);

    return $this->getSubClass($method[1]);
  }

}