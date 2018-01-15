<?php

namespace App\request;

/**
 * Class Response is a helper which works with http response
 * @package TinyRest\request
 */
class Response {

  protected $resultArray = [
    'code' => 503,
    'error' => 'Operation failed',
    'status' => 'No changes made',
    'success' => 0,
  ]; // array for response

  protected function isDebugMode()
  {
    $result = 0;

    return $result;
  }

  public function getJSON()
  {
    return json_encode($this->resultArray);
  }

	public function operationSuccess() {
    $this->resultArray['code'] = 0;
    $this->resultArray['error'] = '';
    $this->resultArray['success'] = 1;
    $this->setStatus();
	}

  public function setCode($text)
  {
    $this->resultArray['code'] = $text;
  }

  public function setError($text)
  {
    $this->resultArray['error'] = $text;
  }

  public function setMessage($text = 'success')
  {
    $this->resultArray['message'] = $text;
  }

  public function setStatus($text = 'complete')
  {
    $this->resultArray['status'] = $text;
  }

  public function setField(array $values)
  {
    foreach ($values as $key => $value) {
      $this->resultArray[$key] = $value;
    }
  }

  public function setDebugError($text, \Exception $e)
  {
    if ($this->isDebugMode()) {
      $text = $e->getMessage();
      $this->setCode($e->getCode());
    }
    $this->setError($text);
  }

  public function addDebugValue($value)
  {
    if ($this->isDebugMode()) {
      if (!isset($this->resultArray['debug'])) {
        $this->resultArray['debug'] = [];
      }
      $this->resultArray['debug'][] = $value;
    }
  }

  /**
   * Break script and send http error code to user. Status will be used as text
   * @param int $code
   */
  public function exitByCode($code = 400)
  {
    header("HTTP/1.0 {$code} ".$this->resultArray['status']);
    exit;
  }

  public function setResultArray(array $array)
  {
    $this->resultArray = $array;
  }

  public function needReload()
  {
    $this->resultArray['needReload'] = 1;
  }


}