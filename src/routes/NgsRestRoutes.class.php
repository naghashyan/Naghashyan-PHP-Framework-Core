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
 * @version 2.2.0
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

  use ngs\framework\exceptions\DebugException;
  use ngs\framework\exceptions\NotFoundException;

  class NgsRestRoutes extends NgsRoutes {

    private $httpMethod = "get";


    /**
     * this method return pakcage and command from url
     * check url if set dynamic container return manage using standart routing
     * if not manage url using routes file if matched succsess return array if not false
     * this method can be overrided from users for they custom routing scenarios
     *
     * @param String $url
     *
     * @return array|false
     */
    public function getDynamicLoad($url) {
      $loadsArr = parent::getDynamicLoad($url);
      if (isset($this->getCurrentRoute()["method"])){
        $this->setRequestHttpMethod($this->getCurrentRoute()["method"]);
      }
      $loadsArr["method"] = $this->getRequestHttpMethod();
      if (strtolower($this->getRequestHttpMethod()) != strtolower($_SERVER["REQUEST_METHOD"])){
        throw new DebugException("HTTP request is " . $_SERVER["REQUEST_METHOD"] . " but in routes set " . $this->getRequestHttpMethod());
      }
      return $loadsArr;
    }

    public function getRequestHttpMethod() {
      return $this->httpMethod;
    }

    protected function setRequestHttpMethod($httpMethod) {
      $this->httpMethod = $httpMethod;
    }


  }

}