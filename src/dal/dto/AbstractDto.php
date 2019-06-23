<?php
/**
 *
 * AbstractDto parent class for all
 * ngs dtos
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site https://naghashyan.com
 * @year 2009-2019
 * @package ngs.framework.dal.dto
 * @version 3.9.0
 *
 * This file is part of the NGS package.
 *
 * @copyright Naghashyan Solutions LLC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace ngs\dal\dto {


  abstract class AbstractDto {

    private $ngs_nullableFields = [];

    public function __construct() {
    }

    public abstract function getMapArray();

    /*
     The first letter of input string changes to Lower case
     */
    public static function lowerFirstLetter($str) {
      $first = substr($str, 0, 1);
      $asciiValue = ord($first);
      if ($asciiValue >= 65 && $asciiValue <= 90){
        $asciiValue += 32;
        return chr($asciiValue) . substr($str, 1);
      }
      return $str;
    }

    /*
     The first letter of input string changes to Lower case
     */
    public static function upperFirstLetter($str) {
      return ucfirst($str);
    }

    /*
     Overloads getter and setter methods
     */
    public function __call($m, $a) {
      // retrieving the method type (setter or getter)
      $type = substr($m, 0, 3);

      // retrieving the field name
      $fieldName = preg_replace_callback('/[A-Z]/', function ($m) {
        return '_' . strtolower($m[0]);
      }, self::lowerFirstLetter(substr($m, 3)));
      if ($type == 'set'){
        $this->$fieldName = $a[0];
      } else if ($type == 'get'){
        if (isset($this->$fieldName)){
          return $this->$fieldName;
        }
        return null;
      }
    }

    public function __set($property, $value) {
      $fieldName = 'set' . preg_replace_callback('/_([a-z])/', function ($property) {
          if (isset($property[1])){
            return ucfirst($property[1]);
          }
        }, self::upperFirstLetter(($property)));

      $this->$fieldName($value);
    }


    public function setNull(string $fieldName): bool {
      if (!$this->isExsistField($fieldName)){
        return false;
      }
      $this->ngs_nullableFields[] = $fieldName;
      return true;
    }

    public function getNgsNullableFealds(): array {
      return $this->ngs_nullableFields;
    }

    public function getFieldByName($name): ?string {
      $mapArr = array_flip($this->getMapArray());
      if (isset($mapArr[$name])){
        return $mapArr[$name];
      }
      return null;
    }

    public function isExsistField(string $key): bool {
      $mapArr = $this->getMapArray();
      if (isset($mapArr[$key])){
        return true;
      }
      return false;
    }

    public function fillDtoFromArray($mapArray = [], $mapArr = null) {
      if (is_null($mapArr)){
        $mapArr = $this->getMapArray();
      }
      foreach ($mapArray as $key => $value){
        if (!isset($mapArr[$key])){
          continue;
        }
        if (is_null($value) || $value === 'NULL'){
          $this->setNull($key);
          continue;
        }
        $functionName = 'set' . '' . ucfirst($mapArr[$key]);
        $this->$functionName($value);
      }
    }

    public function toArray() {
      $resultArr = [];
      $mapArray = $this->getMapArray();
      foreach ($mapArray as $key => $value){
        if (!isset($mapArray[$key]) || is_null($value)){
          continue;
        }

        $functionName = 'get' . '' . ucfirst($mapArray[$key]);
        $resultArr[$key] = $this->$functionName();
      }
      return $resultArr;
    }

    /**
     * return mysql formated time
     * if time not set then return current server time
     *
     * @param int|null $time
     *
     * @return false|string
     */
    public function getMysqlFormatedTime($time = null) {
      if ($time == null){
        $time = time();
      }
      return date('Y-m-d H:i:s', $time);
    }

    /**
     * return mysql formated date
     * if time not set then return current server date
     *
     * @param int|null $date
     *
     * @return false|string
     */
    public function getMysqlFormatedDate($date = null) {
      if ($date == null){
        $date = time();
      }
      return date('Y-m-d', $date);
    }

  }
}