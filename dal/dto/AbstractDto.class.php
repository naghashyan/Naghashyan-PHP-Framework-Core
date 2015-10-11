<?php
/**
 *
 * AbstractDto parent class for all
 * ngs dtos
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @year 2009-2015
 * @package ngs.framework.dal.dto
 * @version 2.0.0
 *
 * This file is part of the NGS package.
 *
 * @copyright Naghashyan Solutions LLC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */
namespace ngs\framework\dal\dto {
	use ngs\framework\dal\mappers\StandartArgs;
	abstract class AbstractDto {
		public function __construct() {
		}

		public abstract function getMapArray();

		/*
		 The first letter of input string changes to Lower case
		 */
		public static function lowerFirstLetter($str) {
			$first = substr($str, 0, 1);
			$asciiValue = ord($first);
			if ($asciiValue >= 65 && $asciiValue <= 90) {
				$asciiValue += 32;
				return chr($asciiValue) . substr($str, 1);
			}
			return $str;
		}

		/*
		 Overloads getter and setter methods
		 */
		public function __call($m, $a) {
			// retrieving the method type (setter or getter)
			$type = substr($m, 0, 3);

			// retrieving the field name
			$fieldName = preg_replace_callback('/[A-Z]/', function($m) {
				return "_" . strtolower($m[0]);
			}, self::lowerFirstLetter(substr($m, 3)));
			if ($type == 'set') {
				$this->$fieldName = $a[0];
			} else if ($type == 'get') {
				if (isset($this->$fieldName)) {
					return $this->$fieldName;
				}
				return null;
			}
		}

		public function getFieldByName($name) {
			$mapArr = array_flip($this->getMapArray());
			if (isset($mapArr[$name])) {
				return $mapArr[$name];
			}
			return false;
		}

		public function isExsistField($key) {
			if ($table = $this->fieldMapArray[$key]) {
				return $table;
			}
			return false;
		}

		public function getStandartArgs() {
			return new StandartArgs($this);
		}

	}
}
?>