<?php
/**
 * parent class for all ngs requests (loads/action)
 *
 * @author Zaven Naghashyan <zaven@naghashyan.com>, Levon Naghashyan <levon@naghashyan.com>
 * @year 2009-2016
 * @version 2.2.0
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

  use ngs\framework\exceptions\NoAccessException;

  abstract class AbstractRequest {

    protected $requestGroup;
    protected $params = array();


    public function initialize() {
    }

    /**
     * default http status code
     * for OK response
     *
     *
     * @return integer 200
     */
    public function getStatusCode(){
      return 400;
    }

    /**
     * default http status code
     * for ERROR response
     *
     *
     * @return integer 403
     */
    public function getErrorStatusCode(){
      return 403;
    }

    public function setRequestGroup($requestGroup) {
      $this->requestGroup = $requestGroup;
    }


    public function getRequestGroup() {
      return $this->requestGroup;
    }

//TODO
    /*
    public function redirectToLoad($package, $load, $args, $statusCode = 200) {
      if ($statusCode > 200 && $statusCode < 300){
        header("HTTP/1.1 $statusCode Exception");
      }
      $this->dispatcher->loadPage($package, $load, $args);
      exit();
    }*/

    /**
     * add multiple parameters
     *
     * @access public
     * @param array $paramsArr
     *
     * @return void
     */
    public final function addParams($paramsArr) {
      if(!is_array($paramsArr)){
        $paramsArr = [$paramsArr];
      }
      $this->params = array_merge($this->params, $paramsArr);
    }

    /**
     * add single parameter
     *
     * @access public
     *
     * @param String $name
     * @param mixed $value
     *
     * @return void
     */
    protected final function addParam($name, $value) {
      $this->params[$name] = $value;
    }

    /**
     * this method return
     * assigned parameters
     *
     * @access public
     *
     * @return array
     *
     */
    public function getParams() {
      return $this->params;
    }

    /**
     * do cancel load or actions
     *
     * @access public
     *
     * @throw NoAccessException
     */
    protected function cancel() {
      throw new NoAccessException("Load canceled request ");
    }


    public function onNoAccess() {
      return false;
    }

    /**
     * public method helper method for do http redirect
     *
     * @access public
     *
     * @param string $url
     *
     * @return void
     */
    protected function redirect($url) {
      NGS()->getHttpUtils()->redirect($url);
      exit;
    }

  }

}
