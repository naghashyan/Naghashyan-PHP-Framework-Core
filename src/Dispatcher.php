<?php
/**
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @year 2009-2018
 * @package framework
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

namespace ngs {

  use ngs\exceptions\InvalidUserException;
  use ngs\exceptions\ClientException;
  use ngs\exceptions\DebugException;
  use ngs\exceptions\NgsErrorException;
  use ngs\exceptions\NoAccessException;
  use ngs\exceptions\NotFoundException;
  use ngs\exceptions\RedirectException;
  use ngs\util\NgsArgs;

  class Dispatcher {

    /**
     * this method manage mathced routes
     *
     * @param array $routesArr
     *
     * @return void
     */
    public function dispatch() {

      try{
        $routesArr = NGS()->getRoutesEngine()->getDynamicLoad(NGS()->getHttpUtils()->getRequestUri());
        if ($routesArr["matched"] === false){
          throw new NotFoundException("Load/Action Not found");
        }
        if (isset($routesArr["args"])){
          NgsArgs::getInstance()->setArgs($routesArr["args"]);
        }
        switch ($routesArr["type"]){
          case 'load' :
            NGS()->getTemplateEngine();
            $this->loadPage($routesArr["action"]);
            break;
          case 'action' :
            NGS()->getTemplateEngine();
            $this->doAction($routesArr["action"]);
            break;
          case 'file' :
            $this->streamStaticFile($routesArr);
            break;
        }
      } catch (DebugException $ex){
        if (NGS()->getEnvironment() != "production"){
          $ex->display();
          return;
        }
        $routesArr = NGS()->getRoutesEngine()->getNotFoundLoad();
        if ($routesArr == null){
          echo "404 :)";
          exit;
        }
        $this->loadPage($routesArr["action"]);
      } catch (RedirectException $ex){
        NGS()->getHttpUtils()->redirect($ex->getRedirectTo());
      } catch (NotFoundException $ex){
        if ($ex->getRedirectUrl() != ""){
          NGS()->getHttpUtils()->redirect($ex->getRedirectUrl());
          return;
        }
        $routesArr = NGS()->getRoutesEngine()->getNotFoundLoad();
        if ($routesArr == null){
          echo "404 :)";
          exit;
        }
        $this->loadPage($routesArr["action"]);
      } catch (NgsErrorException $ex){
        NGS()->getTemplateEngine()->setHttpStatusCode($ex->getHttpCode());
        NGS()->getTemplateEngine()->assignJson("code", $ex->getCode());
        NGS()->getTemplateEngine()->assignJson("msg", $ex->getMessage());
        NGS()->getTemplateEngine()->assignJson("params", $ex->getParams());
        NGS()->getTemplateEngine()->display();
      } catch (InvalidUserException $ex){
        if (!NGS()->getHttpUtils()->isAjaxRequest() && !NGS()->getDefinedValue("display_json")){
          NGS()->getHttpUtils()->redirect($ex->getRedirectTo());
          return;
        }
        NGS()->getTemplateEngine()->setHttpStatusCode($ex->getHttpCode());
        NGS()->getTemplateEngine()->assignJson("code", $ex->getCode());
        NGS()->getTemplateEngine()->assignJson("msg", $ex->getMessage());
        if ($ex->getRedirectTo() != ""){
          NGS()->getTemplateEngine()->assignJson("redirect_to", $ex->getRedirectTo());
        }
        if ($ex->getRedirectToLoad() != ""){
          NGS()->getTemplateEngine()->assignJson("redirect_to_load", $ex->getRedirectToLoad());
        }
        NGS()->getTemplateEngine()->display();
      } catch (NoAccessException $ex){
        if (!NGS()->getHttpUtils()->isAjaxRequest() && !NGS()->getDefinedValue("display_json")){
          NGS()->getHttpUtils()->redirect($ex->getRedirectTo());
          return;
        }
        NGS()->getTemplateEngine()->setHttpStatusCode($ex->getHttpCode());
        NGS()->getTemplateEngine()->assignJson("code", $ex->getCode());
        NGS()->getTemplateEngine()->assignJson("msg", $ex->getMessage());
        if ($ex->getRedirectTo() != ""){
          NGS()->getTemplateEngine()->assignJson("redirect_to", $ex->getRedirectTo());
        }
        if ($ex->getRedirectToLoad() != ""){
          NGS()->getTemplateEngine()->assignJson("redirect_to_load", $ex->getRedirectToLoad());
        }
        NGS()->getTemplateEngine()->display();
      }
    }

    /**
     * manage ngs loads
     * initialize load object
     * verify access
     * display collected output from loads
     *
     * @param array $action
     *
     * @return void
     */
    public function loadPage($action) {
      try{
        if (class_exists($action) == false){
          throw new DebugException($action . " Load Not found");
        }
        $loadObj = new $action;
        $loadObj->initialize();
        if (!$this->validateRequest($loadObj)){
          if ($loadObj->onNoAccess()){
            return;
          }
        }
        $loadObj->setLoadName(NGS()->getRoutesEngine()->getContentLoad());
        $loadObj->service();
        NGS()->getTemplateEngine()->setType($loadObj->getNgsLoadType());
        NGS()->getTemplateEngine()->setTemplate($loadObj->getTemplate());
        NGS()->getTemplateEngine()->setPermalink($loadObj->getPermalink());
        $this->displayResult();
      } catch (NoAccessException $ex){
        $loadObj->onNoAccess();
      } catch (InvalidUserException $ex){
        $loadObj->onNoAccess();
      }
    }

    /**
     * manage ngs action
     * initialize action object
     * verify access
     * display action output
     *
     * @param array $action
     *
     * @return void
     *
     */
    private function doAction($action) {
      try{
        if (class_exists($action) == false){
          throw new DebugException($action . " Action Not found");
        }
        $actionObj = new $action;
        $actionObj->initialize();
        if (!$this->validateRequest($actionObj)){
          $actionObj->onNoAccess();
        }
        $actionObj->service();
        //passing arguments
        NGS()->getTemplateEngine()->setType("json");
        NGS()->getTemplateEngine()->assignJsonParams($actionObj->getParams());
        $this->displayResult();

      } catch (NoAccessException $ex){
        $actionObj->onNoAccess();
      } catch (InvalidUserException $ex){
        $actionObj->onNoAccess();
      }
    }

    private function streamStaticFile($fileArr) {
      $stramer = NGS()->getFileStreamerByType($fileArr["file_type"]);
      $stramer->streamFile($fileArr["module"], $fileArr["file_url"]);
    }

    /**
     * validate request load/action access permissions
     *
     * @param object $request
     *
     * @return boolean
     *
     */
    private function validateRequest($request) {
      $user = NGS()->getSessionManager()->getUser();
      if ($user->validate()){
        if (NGS()->getSessionManager()->validateRequest($request, $user)){
          return true;
        }
      }
      return false;
    }

    /**
     * display collected output
     * from loads and actions
     *
     *
     * @return void
     */
    private function displayResult() {
      NGS()->getTemplateEngine()->display();
    }

    private function notFound($msg) {

    }

  }

}
