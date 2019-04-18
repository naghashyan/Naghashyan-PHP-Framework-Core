<?php
/**
 * Helper wrapper class for php curl
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site https://naghashyan.com
 * @year 2016-2019
 * @package ngs.framework.util
 * @version 3.8.0
 *
 * This file is part of the NGS package.
 *
 * @copyright Naghashyan Solutions LLC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace ngs\util {

  use ngs\exceptions\DebugException;

  class NgsArgs {

    /**
     * @var $instance
     */
    private static $instance;
    private $args = array();
    private $requestParams = null;
    private $inputArgs = null;
    private $inputParams = null;
    private $headerParams = null;
    private $trim = false;

    public function __construct($trim = false) {
      $this->trim = $trim;
    }

    /**
     * Returns an singleton instance of this class
     *
     * @param bool $trim
     *
     * @return NgsArgs
     */
    public static function getInstance($trim = true) {
      if (self::$instance == null){
        self::$instance = new NgsArgs($trim);
        self::$instance->mergeInputData();
      }
      return self::$instance;
    }


    /**
     * this dynamic method
     * return request args
     * check if set trim do trim
     *
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name) {
      if (isset($this->args()[$name])){
        if ($this->trim){
          if (is_string($this->args()[$name])){
            return trim($this->args()[$name]);
          }
          return $this->args()[$name];
        }
        return $this->args()[$name];
      }
      return null;
    }


    /*
		 Overloads getter and setter methods
		 */
    public function __call($m, $a) {
      // retrieving the method type (setter or getter)
      $type = substr($m, 0, 3);
      // retrieving the field name
      $fieldName = preg_replace_callback('/[A-Z]/', function ($m) {
        return "_" . strtolower($m[0]);
      }, NGS()->getNgsUtils()->lowerFirstLetter(substr($m, 3)));
      if ($type == 'set'){
        if (count($a) == 1){
          $this->$fieldName = $a[0];
        } else{
          $this->$fieldName = $a;
        }
      } else if ($type == 'get'){
        return $this->$fieldName;
      }
      return null;
    }

    /**
     * static function that return ngs
     * global url args
     *
     *
     * @return array config
     */
    public function getArgs() {
      return $this->args;
    }

    /**
     * static function that set ngs
     * url args
     *
     * @param array $args
     *
     * @return bool
     */
    public function setArgs($args) {
      if (!is_array($args)){
        return false;
      }
      $this->requestParams = null;
      $this->args = array_merge($this->args, $args);
      return true;
    }

    public function args() {
      if ($this->requestParams != null){
        return $this->requestParams;
      }
      $this->requestParams = array_merge((array)$this->getArgs(), $_REQUEST);
      return $this->requestParams;
    }

    public function mergeInputData() {
      if (NGS()->getNgsUtils()->isJson($this->inputData())){
        $this->setArgs(json_decode($this->inputData(), true));
      } else{
        parse_str($this->inputData(), $parsedRequestBody);
        if (is_array($parsedRequestBody)){
          $this->setArgs($parsedRequestBody);
        }
      }

    }

    public function input() {
      if ($this->inputArgs == null){
        if (!NGS()->getNgsUtils()->isJson($this->inputData())){
          throw new DebugException("response body is not json");
        }
        $this->inputArgs = new NgsArgs($this->trim);
        $this->inputArgs->setArgs(json_decode($this->inputData(), true));
      }
      return $this->inputArgs;
    }

    public function inputData() {
      if ($this->inputParams == null){
        $this->inputParams = file_get_contents('php://input');
      }
      return $this->inputParams;

    }

    public function headers() {
      if ($this->headerParams == null){
        $this->headerParams = new NgsArgs($this->trim);
        $this->headerParams->setArgs($this->getAllHeaders());
      }
      return $this->headerParams;
    }

    private function getAllHeaders() {
      if (!is_array($_SERVER)){
        return array();
      }
      $headers = array();
      foreach ($_SERVER as $key => $value){
        if (substr($key, 0, 5) == "HTTP_"){
          $key = str_replace(" ", "-", (strtolower(str_replace("_", " ", substr($key, 5)))));
          $headers[$key] = $value;
        } else{
          $headers[$key] = $value;
        }
      }
      return $headers;

    }
  }
}


