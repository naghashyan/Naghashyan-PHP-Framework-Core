<?php
/**
 * NGS abstract load all loads should extends from this class
 * this class extends from AbstractRequest class
 * this class class content base functions that will help to
 * initialize loads
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
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

  use \ngs\framework\exceptions\NoAccessException;
  use ngs\framework\util\NgsArgs;

  abstract class AbstractLoad extends AbstractRequest {

    protected $parentParams = array();
    private $jsonParam = array();
    private $load_name = "";
    private $isNestedLoad = false;

    /**
     * this method use for initialize
     * load and AbstractRequest initialize function
     *
     * @abstract
     * @access public
     *
     * @return void;
     */
    public function initialize() {
      parent::initialize();
    }

    /**
     * Return a thingie based on $paramie
     * @abstract
     * @access
     * @param boolean $paramie
     * @return integer|babyclass
     */
    public final function service() {

      $this->load();
      $ns = get_class($this);
      $ns = substr($ns, strpos($ns, NGS()->getModulesRoutesEngine()->getModuleNS()) + strlen(NGS()->getModulesRoutesEngine()->getModuleNS()) + 1);

      $ns = str_replace(array("Load", "\\"), array("", "."), $ns);
      $ns = preg_replace_callback('/[A-Z]/', function ($m) {
        return "_" . strtolower($m[0]);
      }, $ns);
      $ns = str_replace("._", ".", $ns);
      $nestedLoads = NGS()->getRoutesEngine()->getNestedRoutes($ns);
      $loadDefaultLoads = $this->getDefaultLoads();
      $defaultLoads = array();
      if (isset($loadDefaultLoads) && is_array($loadDefaultLoads)){
        $defaultLoads = array_merge($nestedLoads, $loadDefaultLoads);
      } else{
        if (is_array($nestedLoads)){
          $defaultLoads = $nestedLoads;
        }
      }
      //set nested loads for each load
      foreach ($defaultLoads as $key => $value){
        $this->nest($key, $value);
      }

    }

    /**
     * in this method implemented
     * nested load functional
     *
     * @abstract
     * @access public
     * @param String $namespace
     * @param array $loadArr
     *
     * @return void
     */
    public final function nest($namespace, $loadArr) {

      $actionArr = NGS()->getRoutesEngine()->getLoadORActionByAction($loadArr["action"]);

      $loadObj = new $actionArr["action"];
      //set that this load is nested
      $loadObj->setIsNestedLoad(true);
      if (isset($loadArr["args"])){
        NgsArgs::getInstance()->setArgs($loadArr["args"]);
      }
      $loadObj->setLoadName($loadArr["action"]);
      $loadObj->initialize();

      if (NGS()->getSessionManager()->validateRequest($loadObj) === false){
        throw NGS()->getNoAccessException("User hasn't access to the load: " . $actionArr["action"], 1);
      }

      $loadObj->service();

      if (NGS()->isJsFrameworkEnable() && NGS()->getHttpUtils()->isAjaxRequest()){
        NGS()->getLoadMapper()->setNestedLoads($this->getLoadName(), $loadArr["action"], $loadObj->getJsonParams());
      }
      if (!isset($this->params["inc"])){
        $this->params["inc"] = array();
      }
      $this->setNestedLoadParams($namespace, $loadArr["action"], $loadObj);
      $this->params = array_merge($this->getParams(), $loadObj->getParentParams());

    }

    protected function setNestedLoadParams($namespace, $fileNs, $loadObj) {
      $this->params["inc"][$namespace]["filename"] = $loadObj->getTemplate();
      $this->params["inc"][$namespace]["params"] = $loadObj->getParams();
      $this->params["inc"][$namespace]["namespace"] = $fileNs;
      $this->params["inc"][$namespace]["jsonParam"] = $loadObj->getJsonParams();
      $this->params["inc"][$namespace]["parent"] = $this->getLoadName();
      $this->params["inc"][$namespace]["permalink"] = $this->getPermalink();

    }


    /**
     * this method add template varialble
     *
     * @abstract
     * @access public
     * @param String $name
     * @param mixed $value
     *
     * @return void
     */
    protected final function addParentParam($name, $value) {
      $this->parentParams[$name] = $value;

    }

    /**
     * this method add json varialble
     *
     * @abstract
     * @access public
     * @param String $name
     * @param mixed $value
     *
     * @return void
     */
    public function addJsonParam($name, $value) {
      $this->jsonParam[$name] = $value;
    }


    /**
     * Return params array
     * @abstract
     * @access public
     *
     * @return array|params
     */
    protected function getParentParams() {
      return $this->parentParams;

    }

    /**
     * Return json params array
     * @abstract
     * @access public
     *
     * @return array|jsonParam
     */
    public function getJsonParams() {
      return $this->jsonParam;
    }

    /**
     * this abstract method should be replaced in childs load
     * for add nest laod
     * @abstract
     * @access public
     *
     * @return array|nestedlaods
     */
    public function getDefaultLoads() {
      return array();
    }

    /**
     * this abstract method should be replaced in childs load
     * for set load template
     * @abstract
     * @access public
     *
     * @return string|templatePath
     */
    public function getTemplate() {
      return null;
    }

    /**
     * Return a thingie based on $paramie
     * @abstract
     * @access
     * @param boolean $paramie
     * @return integer|babyclass
     */
    public function isNestable($namespace, $load) {
      return true;
    }

    /**
     * set true if load called from parent (if load is nested)
     *
     * @param boolean $isNestedLoad
     *
     * @return void
     */
    public final function setIsNestedLoad($isNestedLoad) {
      $this->isNestedLoad = $isNestedLoad;
    }

    /**
     * get true if load is nested
     *
     * @return boolean|$isNestedLoad
     */
    public final function getIsNestedLoad() {
      return $this->isNestedLoad;
    }

    public function getLoadType() {
      return "smarty";
    }

    public function setLoadName($name) {
      $this->load_name = $name;
    }

    public function getLoadName() {
      return $this->load_name;
    }

    public function getPermalink() {
      return null;
    }

    /**
     * Return a thingie based on $paramie
     * @abstract
     * @access
     * @param boolean $paramie
     * @return integer|babyclass
     */
    public function onNoAccess() {
    }

    /**
     * Return a thingie based on $paramie
     * @abstract
     * @access
     * @param boolean $paramie
     * @return integer|babyclass
     */
    public abstract function load();

  }

}
