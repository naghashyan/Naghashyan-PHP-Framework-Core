<?php
/**
 * parent class for all ngs requests (loads/action)
 *
 * @author Zaven Naghashyan <zaven@naghashyan.com>, Levon Naghashyan <levon@naghashyan.com>
 * @year 2009-2015
 * @version 2.0.0
 * @package ngs.framework
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

	abstract class AbstractRequest {

		protected $sessionManager;
		protected $requestGroup;

		/**
		 * Return a thingie based on $paramie
		 * @abstract
		 * @access
		 * @param boolean $paramie
		 * @return integer|babyclass
		 */
		public function initialize() {
		}

		/**
		 * Return a thingie based on $paramie
		 * @abstract
		 * @access
		 * @param boolean $paramie
		 * @return integer|babyclass
		 */
		public function setRequestGroup($requestGroup) {
			$this->requestGroup = $requestGroup;
		}

		/**
		 * Return a thingie based on $paramie
		 * @abstract
		 * @access
		 * @param boolean $paramie
		 * @return integer|babyclass
		 */
		public function getRequestGroup() {
			return $this->requestGroup;
		}

		/**
		 * Return a thingie based on $paramie
		 * @abstract
		 * @access
		 * @param boolean $paramie
		 * @return integer|babyclass
		 */
		public function setDispatcher($dispatcher) {
			$this->dispatcher = $dispatcher;
		}

		public function redirectToLoad($package, $load, $args, $statusCode = 200) {
			if ($statusCode > 200 && $statusCode < 300) {
				header("HTTP/1.1 $statusCode Exception");
			}
			$this->dispatcher->loadPage($package, $load, $args);
			exit();
		}

		public function toCache() {
			return false;
		}

		/**
		 * Return a thingie based on $paramie
		 * @abstract
		 * @access
		 * @param boolean $paramie
		 * @return integer|babyclass
		 */
		protected function cancel() {
			throw new NoAccessException("Load canceled request ");
		}

		/**
		 * Return a thingie based on $paramie
		 * @abstract
		 * @access
		 * @param boolean $paramie
		 * @return integer|babyclass
		 */
		public function onNoAccess() {
			return false;
		}

		/**
		 * Return a thingie based on $paramie
		 * @abstract
		 * @access
		 * @param boolean $paramie
		 * @return integer|babyclass
		 */
		protected function redirect($url) {
			NGS()->getHttpUtils()->redirect($url);exit;
		}

	}

}
