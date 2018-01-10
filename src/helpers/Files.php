<?php

namespace TinyRest\helpers;


class Files
{

  public function classExists()
  {

  }

  /**
   * The last element of the namespace is a class file.
   * Example: ['v1', 'game', 'rules', ]
   * @param array $nameSpace
   * @return string
   */
  public static function classNameByNameSpace(array $nameSpace)
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

  /**
   * The last element of the namespace is a class file.
   * Example: \v1\game\Rules
   * @param $root projectRoot/classes/rest
   * @param $className
   * @return string
   */
  public static function getClassPathByClassName($root, $className)
  {

    return $root . DIRECTORY_SEPARATOR . str_replace("\\", DIRECTORY_SEPARATOR, $className) . ".php";
  }

}