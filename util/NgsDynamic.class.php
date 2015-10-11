<?php

/**
 * Helper wrapper class for php stdClass
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @year 2015
 * @package ngs.framework.util
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
namespace ngs\framework\util {
	class NgsDynamic extends \stdClass {
		public function __get($var) {
		}

		/*
		 The first letter of input string changes to Lower case
		 */
		public static function lowerFirstLetter($str) {
			$first = substr($str, 0, 1);
			$asciiValue = ord($first);
			if ($asciiValue >= 65 && $asciiValue <= 90) {
				$asciiValue += 32;
				return chr($asciiValue).substr($str, 1);
			}
			return $str;
		}

		/*
		 Overloads getter and setter methods
		 */
		public function __call($m, $a) {
			if(!isset($m)){
				 throw new \Exception("Fatal error: Call to undefined method stdObject::{$m}()");
			}
			if (\is_callable($this->{$m})) {
            return \call_user_func_array($this->{$m}, $a);
			}	
			// retrieving the method type (setter or getter)
			$type = substr($m, 0, 3);
			// retrieving the field name
			$fieldName = self::lowerFirstLetter(substr($m, 3));

			if ($type == 'set') {
				if(count($a) == 1){
					$this->$fieldName = $a[0];
				}else{
					$this->$fieldName = $a;
				}
			} else if ($type == 'get') {
				if (isset($this->$fieldName)) {
					return $this->$fieldName;
				}
				return null;
			}
		}

	}

}
