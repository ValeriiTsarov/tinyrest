<?php

namespace TinyRest;

class ApiValidator
{
  const   PARAM_TYPE_INT = 'int'
        , PARAM_TYPE_STRING = 'string'
        , PARAM_TYPE_ARRAY = 'array' //1, 2, 3
        , PARAM_ARRAY_SEPARATOR = ','
  ;

  public function __construct() {
  }

  /**
   * @param $value
   * @param $type
   * @return array|int|null
  */
  public function check($value, $type)
  {
    if (!is_null($value) && $type) {
      switch ($type) {
        case self::PARAM_TYPE_INT:
          $value = (int)$value;
          break;
        case self::PARAM_TYPE_STRING:
          if (!is_string($value)) {
            $value = 0;
          }
          break;
        case self::PARAM_TYPE_ARRAY:
          if (($value = explode(self::PARAM_ARRAY_SEPARATOR, str_replace(' ', '', $value)))) {
            foreach ($value as &$row) {
              $row = trim($row);
            }
          }
          break;
      }
      if (empty($value)) {
        $value = null;
      }
    }

    return $value;
  }

  public function getTypeInt()
  {
    return self::PARAM_TYPE_INT;
  }

  public function getTypeString()
  {
    return self::PARAM_TYPE_STRING;
  }

  public function getTypeArray()
  {
    return self::PARAM_TYPE_ARRAY;
  }

}