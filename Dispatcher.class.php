<?php
/**
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @year 2009-2015
 * @package framework
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
namespace ngs\framework {
  use \ngs\framework\exceptions\ClientException;
  use \ngs\framework\exceptions\RedirectException;
  use \ngs\framework\exceptions\NoAccessException;
  use \ngs\framework\exceptions\NotFoundException;

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
     * Dispacher constructor
     * this for manage modules and get routes from uri
     *
     * @param array $loadArr
     *
     * @return void
     */
    private function dispatch($routesArr) {
      
      try {
        if ($routesArr["matched"] === false) {
          throw NGS()->getNotFoundException("Load/Action Not found");
        }
        if (isset($routesArr["args"])) {
          NGS()->setArgs($routesArr["args"]);
        }
        switch ($routesArr["type"]) {
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
      } catch(exception\ClientException $ex) {
        throw NGS()->getNotFoundException("Load/Action Not found");
      } catch(JsonException $ex) {
        $this->diplayJSONResuls($ex->getMsg());
      } catch(exception\RedirectException $ex) {
        $this->redirect($ex->getRedirectTo());
      } catch(Exception $ex) {
        throw NGS()->getNotFoundException("Load/Action Not found");
      }
    }

    /**
     * Return a thingie based on $paramie
     * @abstract
     * @access
     * @param boolean $paramie
     * @return integer|babyclass
     */
    public function loadPage($action) {

      try {
        if (class_exists($action) == false) {
          throw NGS()->getNotFoundException();
        }
        $loadObj = new $action;
        $loadObj->initialize();
        if (!$this->validateRequest($loadObj)) {
          if ($loadObj->onNoAccess()) {
            return;
          }
        }
        $loadObj->setLoadName(NGS()->getRoutesEngine()->getContentLoad());
        $loadObj->service();
        NGS()->getTemplateEngine()->setTemplate($loadObj->getTemplate());
        NGS()->getTemplateEngine()->setPermalink($loadObj->getPermalink());
        if ($loadObj->getLoadType() == "smarty") {
          //passing arguments
          NGS()->getTemplateEngine()->assign("ns", $loadObj->getParams());
          NGS()->getTemplateEngine()->assignJson("params", $loadObj->getJsonParams());
        } else if ($loadObj->getLoadType() == "json") {
          NGS()->getTemplateEngine()->assignJson("_params_", $loadObj->getParams());
        }
        $this->displayResult();
        return;

      } catch(NoAccessException $ex) {
        $loadObj->onNoAccess();
      }
      throw NGS()->getNotFoundException();
    }

    /**
     * Return a thingie based on $paramie
     * @abstract
     * @access
     * @param boolean $paramie
     * @return integer|babyclass
     */
    private function doAction($action) {

      try {
        if (class_exists($action) == false) {
          throw NGS()->getNotFoundException();
        }
        $actionObj = new $action;
        $actionObj->initialize();
        if ($this->validateRequest($actionObj)) {
          $actionObj->service();
          //passing arguments
          NGS()->getTemplateEngine()->assignJson("params", $actionObj->getParams());
          $this->displayResult();
          return;
        }

        if ($loadObj->onNoAccess()) {
          return;
        }

      } catch(exception\NoAccessException $ex) {
        $loadObj->onNoAccess();
      }
      throw NGS()->getNotFoundException();

    }

    private function streamStaticFile($fileArr) {
      $stramer = NGS()->getFileStreamerByType($fileArr["file_type"]);
      $stramer->streamFile($fileArr["module"], $fileArr["file_url"]);
    }

    /**
     * Return a thingie based on $paramie
     * @abstract
     * @access
     * @param boolean $paramie
     * @return integer|babyclass
     */
    private function validateRequest($request) {
      $user = NGS()->getSessionManager()->getUser();
      if ($user->validate()) {
        if (NGS()->getSessionManager()->validateRequest($request, $user)) {
          return true;
        }
      }
      return true;
    }

    /**
     * Return a thingie based on $paramie
     * @abstract
     * @access
     * @param boolean $paramie
     * @return integer|babyclass
     */
    private function displayResult() {
      foreach (NGS()->getSessionManager()->getRequestHeader() as $key => $value) {
        $headerStr = $key;
        if ($value != "") {
          $headerStr .= " : ".$value;
        }
        header($headerStr);
      }
      NGS()->getTemplateEngine()->display();

    }

  }

}
