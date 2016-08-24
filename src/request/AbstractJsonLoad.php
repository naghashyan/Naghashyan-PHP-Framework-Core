<?php
/**
 * NGS abstract load all loads that response is json should extends from this class
 * this class extends from AbstractRequest class
 * this class class content base functions that will help to
 * initialize loads
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @year 2014-2016
 * @package ngs.framework
 * @version 3.0.0
 *
 * This file is part of the NGS package.
 *
 * @copyright Naghashyan Solutions LLC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */
namespace ngs\request {

  use ngs\exceptions\NoAccessException;

  abstract class AbstractJsonLoad extends AbstractLoad {

		protected $params = array();

		
		protected function setNestedLoadParams($namespace, $fileNs, $loadObj) {
			$this->params["inc"][$namespace] = $loadObj->getParams();
		}

		public function getNgsLoadType(){
			return "json";
		}
		
	}

}
