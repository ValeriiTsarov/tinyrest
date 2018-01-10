<?php
use TinyRest\request\HttpException;
use TinyRest\rest\ApiException;

$loader = require __DIR__ . '/../../vendor/autoload.php';

$resultArray = [];
$oResponse = new TinyRest\request\Response();
if (
  empty($_SERVER['REQUEST_URI'])
  || !($requestUri = $_SERVER['REQUEST_URI'])) {
  $requestUri = '';
}

try {

  //Check auth

  //Get resource by URI. Api will check version/classGroup/resource/method
  $oApi = new \TinyRest\rest\Api($requestUri);
  $oResource = $oApi->getResourceObject();


  //Get data and save result
  $resultArray = $oResource->callMethod($oApi->getMethod());
  $oResponse->operationSuccess();

  //Fill error field in JSON body
} catch (LogicException $e) {
  $oResponse->setCode($e->getCode());
  $oResponse->setError($e->getMessage());
} catch (ApiException $e) {
  $oResponse->setCode($e->getCode());
  $oResponse->setError($e->getMessage());
} catch (HttpException $e) {
  //Generate http code and status text (no body at all).
  $oResponse->setStatus($e->getMessage());
  $oResponse->exitByCode($e->getCode());
} catch (Exception $e) {
  //Add debug info about error if debug mode = 1. Example: uri param debugServer=1
  $oResponse->setDebugError('Operation Failed', $e);
}

$oResponse->setField(['data' => $resultArray]);
//Response always contains array
echo $oResponse->getJSON();
exit;
