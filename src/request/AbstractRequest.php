<?php
/**
 * parent class for all ngs requests (loads/action)
 *
 * @author Zaven Naghashyan <zaven@naghashyan.com>, Levon Naghashyan <levon@naghashyan.com>
 * @site https://naghashyan.com
 * @year 2009-2019
 * @version 4.0.0
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

namespace ngs\request {

  use ngs\exceptions\NoAccessException;
  use ngs\util\NgsArgs;
  use ngs\util\Pusher;

  abstract class AbstractRequest {

    protected $requestGroup;
    protected $params = array();
    protected $ngsStatusCode = 200;
    private $ngsPushParams = ['link' => [], 'script' => [], 'img' => []];
    private ?NgsArgs $ngsArgs = null;

    public function initialize() {
    }

    /**
     * default http status code
     * for OK response
     *
     *
     * @return integer 200
     */
    public function setStatusCode($statusCode) {
      return $this->ngsStatusCode = $statusCode;
    }

    /**
     * default http status code
     * for OK response
     *
     *
     * @return integer 200
     */
    public function getStatusCode() {
      return $this->ngsStatusCode;
    }

    /**
     * default http status code
     * for ERROR response
     *
     *
     * @return integer 403
     */
    public function getErrorStatusCode() {
      return 403;
    }

    public function setRequestGroup($requestGroup) {
      $this->requestGroup = $requestGroup;
    }


    public function getRequestGroup() {
      return $this->requestGroup;
    }

    public function redirectToLoad($load, $args, $statusCode = 200) {
      $this->setStatusCode($statusCode);
      if (isset($args)){
        NgsArgs::getInstance()->setArgs($args);
      }
      $actionArr = NGS()->getRoutesEngine()->getLoadORActionByAction($load);
      NGS()->getDispatcher()->loadPage($actionArr["action"]);
    }

    /**
     * add multiple parameters
     *
     * @access public
     * @param array $paramsArr
     *
     * @return void
     */
    public final function addParams($paramsArr) {
      if (!is_array($paramsArr)){
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
    public function getParams(): array {
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
    }


    protected abstract function onNoAccess(): void;

    /**
     * public method helper method for do http redirect
     *
     * @access public
     *
     * @param string $url
     *
     * @return void
     */
    protected function redirect(string $url): void {
      NGS()->getHttpUtils()->redirect($url);
    }

    /**
     * set http2 push params
     * it will add in response header
     * suppored types img, script and link
     *
     * @param string $type
     * @param string $value
     * @return bool
     */
    protected function setHttpPushParam(string $type, string $value): bool {
      if (isset($this->ngsPushParams[$type])){
        $this->ngsPushParams[$type] = $value;
        return true;
      }
      return false;
    }

    /**
     * set http2 push params
     * it will add in response header
     * suppored types img, script and link
     *
     * @param string $type
     * @param string $value
     * @return bool
     */
    protected function insertHttpPushParams(): void {
      foreach ($this->ngsPushParams['script'] as $script){
        Pusher::getInstance()->src($script);
      }
      foreach ($this->ngsPushParams['link'] as $link){
        Pusher::getInstance()->link($link);
      }
      foreach ($this->ngsPushParams['img'] as $img){
        Pusher::getInstance()->img($img);
      }
    }


    public final function args(): NgsArgs {
      return NgsArgs::getInstance(get_class($this));
    }

    protected abstract function afterRequest();

  }

}
