<?php

namespace TinyRest;

/**
 * Class ResourceBase. User must extend this class for its resources.
 * @package TinyRest\v1
 */
abstract class ApiResourceBase
{

  protected $availableMethods = []; //It will be filled with child resource public methods
  protected $unavailableMethods = []; //Method's black list. Attention: This array is case sensitive
  protected $oApi;
  protected $params = [];
  /* Params example
      [
        'UserId' => [ //Get data from URI
          'type' => Api::PARAM_TYPE_INT,
          'require' => 1,
        ]
        'AccessLevels' => [ //Get data from $_SESSION['AccessLevels']
          'type' => Api::PARAM_TYPE_ARRAY,
          'require' => 1,
          'session' => 'AccessLevels',
        ],
      ],
  */
  protected $paramValues = []; //Array for values of params

  public function __construct(Api $oApi)
  {
    $this->oApi = $oApi;
  }

  /**
   * Fill $this->availableMethods with child resource public methods
   *
   * @return array
   */
  public function getAvailableMethods() {
    if (empty($this->availableMethods)) {
      $childRc = new \ReflectionClass($this);
      $childMethods = $childRc->getMethods(\ReflectionMethod::IS_PUBLIC);
      $thisName = get_class($this);

      foreach ($childMethods as $f) {
        if ($f->class === $thisName && !in_array($f->name, $this->unavailableMethods)) {
          $this->availableMethods[] = strtolower($f->name);
        };
      }
    }

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

  protected function fillRequiredParams()
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
        $this->paramValues[$param] = $this->oApi->getParam($param, $value['type']);
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

    $className = $this->oApi->classNameByNameSpace([
      $this->oApi->getVersion(),
      $this->oApi->getGroup(),
      strtolower($myClass),
      $subClass
    ]);
    $className = $this->oApi->getConfig()->getNameSpace().$className;

    if (!class_exists($className)) {
      $errorMessage = "Sub resource [{$subClass}] is not found. ";
      $errorCode = 400.0001;
      throw new ApiException($errorMessage, $errorCode);
    }

    $oClass = new $className($this);

    return $oClass;
  }

  protected function getSubClassFromMethod($path)
  {
    $method = explode('::', $path);

    return $this->getSubClass($method[1]);
  }

}