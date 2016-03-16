<?php
/**
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @year 2009-2016
 * @package framework
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
namespace ngs\framework {

  use \ngs\framework\exceptions\ClientException;
  use ngs\framework\exceptions\DebugException;
  use \ngs\framework\exceptions\RedirectException;
  use \ngs\framework\exceptions\NoAccessException;
  use \ngs\framework\exceptions\NotFoundException;
  use \ngs\framework\exceptions\NgsErrorException;
  use ngs\framework\util\NgsArgs;

  class Dispatcher {
    /**
     * Dispacher constructor
     * this for manage modules and get routes from uri
     *
     * @return void
     */
    public function __construct() {
      $routesArr = NGS()->getRoutesEngine()->getDynamicLoad(NGS()->getHttpUtils()->getRequestUri());
      $this->dispatch($routesArr);
    }

    /**
     * this method manage mathced routes
     *
     * @param array $routesArr
     *
     * @return void
     */
    private function dispatch($routesArr) {

      try{
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
      } catch (ClientException $ex){
        throw new NotFoundException($ex->getMsg());
      } catch (NgsErrorException $ex){
        //$this->diplayJSONResuls($ex->getMsg());
      } catch (RedirectException $ex){
        $this->redirect($ex->getRedirectTo());
      } catch (\Exception $ex){
        throw new NotFoundException("Load/Action Not found");
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
        NGS()->getTemplateEngine()->setTemplate($loadObj->getTemplate());
        NGS()->getTemplateEngine()->setPermalink($loadObj->getPermalink());
        if ($loadObj->getLoadType() == "smarty"){
          //passing arguments
          NGS()->getTemplateEngine()->assign("ns", $loadObj->getParams());
          NGS()->getTemplateEngine()->assignJson("params", $loadObj->getJsonParams());
        } else if ($loadObj->getLoadType() == "json"){
          NGS()->getTemplateEngine()->assignJsonParams($loadObj->getParams());
        }
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
      return true;
    }

    /**
     * display collected output
     * from loads and actions
     *
     *
     * @return void
     */
    private function displayResult() {
      /*
      foreach (NGS()->getSessionManager()->getRequestHeader() as $key => $value){
        $headerStr = $key;
        if ($value != ""){
          $headerStr .= " : ".$value;
        }
        header($headerStr);
      }*/
      NGS()->getTemplateEngine()->display();

    }

    private function notFound($msg) {

    }

  }

}
