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
			$fieldName = NGS()->getNgsUtils()->lowerFirstLetter(substr($m, 3));

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
