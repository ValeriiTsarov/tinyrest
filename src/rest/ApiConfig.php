<?php

namespace TinyRest\rest;

use \PDO;

class ApiConfig
{
  const   DB_MASTER = 'master'
        , DB_REPLICA = 'replica'
  ;
  
  protected $config;
  protected $dbConnectors = [];
  
  public function __construct($apiRoot) {
    $this->config = require_once(__DIR__ . '/../config/config.php');
    $this->config['apiRootPath'] = $apiRoot;
    $this->config['nameSpace'] = str_replace('ApiConfig', '', __CLASS__);
  }

  /**
   * This method returns PDO connector to selected db
   * @param string $type
   * @return \PDO
   */
  protected function getDB($type)
  {
    if (!isset($this->dbConnectors[$type])) {
      $dbConfig = $this->config['db'][$type];
      $PDO_connector = "mysql:host={$dbConfig['hostName']};dbname={$dbConfig['dataBase']};charset=utf8";
      $this->dbConnectors[$type] = new PDO($PDO_connector, $dbConfig['userName'], $dbConfig['password']);
      $this->dbConnectors[$type]->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    
    return $this->dbConnectors[$type];
  }
  
  public function getMasterDB()
  {
    return $this->getDB( self::DB_MASTER);
  }
  
  public function getReplicaDB()
  {
    return $this->getDB( self::DB_REPLICA);
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