<?php
/**
 * default ngs routing class
 * this class by default used from dispacher
 * for matching url with routes
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @year 2014-2015
 * @package ngs.framework.routes
 * @version 2.1.1
 * 
 * This file is part of the NGS package.
 *
 * @copyright Naghashyan Solutions LLC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */
namespace ngs\framework\routes {

	class NgsLoadMapper{

		private $nestedLoad = array();

		public function setNestedLoads($parent, $nl, $params) {
			if (!isset($parent)) {
				return;
			}
			$this->nestedLoad[$parent][] = array("load" => $nl, "params" => $params);
		}

		public function getNestedLoads() {
			return $this->nestedLoad;
		}

	}
}
