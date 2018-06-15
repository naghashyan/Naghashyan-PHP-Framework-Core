<?php
/**
 * default ngs routing class
 * this class by default used from dispacher
 * for matching url with routes
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @year 2014-2016
 * @package ngs.framework.routes
 * @version 3.6.0
 *
 * This file is part of the NGS package.
 *
 * @copyright Naghashyan Solutions LLC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace ngs\routes {

  class NgsLoadMapper {

    private $nestedLoad = array();
    private $globalParentLoad;

    public function setNestedLoads($parent, $nl, $params) {
      if (!isset($parent)){
        return;
      }
      $this->nestedLoad[$parent][] = array("action" => $nl, "params" => $params);
    }

    public function getNestedLoads() {
      return $this->nestedLoad;
    }

    public function setGlobalParentLoad($globalParentLoad="") {
      $this->globalParentLoad = $globalParentLoad;
    }

    public function getGlobalParentLoad() {
      return $this->globalParentLoad;
    }
  }
}
