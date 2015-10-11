<?php
/**
 * parent class of all ngs actions
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @year 2009-2015
 * @package ngs.framework
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
namespace ngs\framework {
	abstract class AbstractAction extends \ngs\framework\AbstractRequest {
		private $params = array();

		public function initialize() {
			parent::initialize();
		}

		public function service() {
//			$this->service();
		}

		/**
		 * Return a thingie based on $paramie
		 * @abstract
		 * @access
		 * @param boolean $paramie
		 * @return integer|babyclass
		 */
		public function addParam($name, $value) {
			$this->params[$name] = $value;
		}
		
		/**
		 * Return a thingie based on $paramie
		 * @abstract
		 * @access
		 * @param boolean $paramie
		 * @return integer|babyclass
		 */
		public function addParams($paramsArr) {
			foreach($paramsArr as $name=>$value){
				$this->params[$name] = $value;
			}
		}

		/**
		 * Return a thingie based on $paramie
		 * @abstract
		 * @access
		 * @param boolean $paramie
		 * @return integer|babyclass
		 */
		public function getParams() {
			return $this->params;
		}

	}

}
