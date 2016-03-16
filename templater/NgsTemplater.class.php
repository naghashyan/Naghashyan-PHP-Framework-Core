<?php
/**
 * Smarty util class extends from main smarty class
 * provides extra features for ngs
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @package ngs.framework.templater
 * @version 2.2.0
 * @year 2010-2016
 *
 * This file is part of the NGS package.
 *
 * @copyright Naghashyan Solutions LLC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ngs\framework\templater {

  use ngs\framework\templater\AbstractTemplater;

  require_once(realpath(NGS()->getFrameworkDir() . "/lib/smarty/Smarty.class.php"));

  class NgsTemplater extends AbstractTemplater {

    /**
     * constructor
     * reading Smarty config and setting up smarty environment accordingly
     */
    private $smarty = null;
    private $template = null;
    private $params = array();
    private $permalink = null;
    private $smartyParams = array();
    private $httpStatusCode = 200;

    public function __construct() {
    }

    /**
     * Initialize smarty
     * set smarty config params
     *
     * @access public
     *
     * @return void
     */
    public function smartyInitialize() {

      $this->smarty = new \Smarty();
      $this->smarty->setTemplateDir(NGS()->getTemplateDir());
      $this->smarty->setCompileDir($this->getSmartyCompileDir());
      $this->smarty->setConfigDir($this->getSmartyConfigDir());
      $this->smarty->setCacheDir($this->getSmartyCacheDir());
      $this->smarty->compile_check = true;

      // register the outputfilter
      $this->smarty->registerFilter("output", array($this, "add_dyn_header"));

      foreach ($this->smartyParams as $key => $value){
        $this->smarty->assign($key, $value);
      }
    }

    /**
     * assign single smarty parameter
     *
     * @access public
     *
     * @param String $name
     * @param mixed $value
     *
     * @return void
     */
    public function assign($key, $value) {
      $this->smartyParams[$key] = $value;
    }

    /**
     * assign single json parameter
     *
     * @access public
     *
     * @param String $name
     * @param mixed $value
     *
     * @return void
     */
    public function assignJson($key, $value) {
      $this->params[$key] = $value;
    }

    /**
     * add multiple json parameters
     *
     * @access public
     * @param array $paramsArr
     *
     * @return void
     */
    public function assignJsonParams($paramsArr) {
      if (!is_array($paramsArr)){
        $paramsArr = [$paramsArr];
      }
      $this->params = array_merge($this->params, $paramsArr);
    }

    /**
     * set template
     *
     * @param String $template
     *
     */
    public function setTemplate($template) {
      $this->template = $template;
    }

    /**
     * Return a template
     *
     * @return String $template
     */
    public function getTemplate() {
      return $this->template;
    }

    /**
     * set template
     *
     * @param String $template
     *
     */
    public function setPermalink($permalink) {
      $this->permalink = $permalink;
    }

    /**
     * Return a template
     *
     * @return String $template|null
     */
    public function getPermalink() {
      return $this->permalink;
    }

    /**
     * set response http status code
     *
     * @param integer $httpStatusCode
     *
     */
    public function setHttpStatusCode($httpStatusCode) {
      $this->httpStatusCode = $httpStatusCode;
    }

    /**
     * get response http status code
     *
     * @param integer $httpStatusCode
     *
     */
    public function getHttpStatusCode() {
      return $this->httpStatusCode;
    }

    /**
     * display response
     * @access public
     *
     */
    public function display() {
      if ($this->getTemplate() == null){
        $this->diplayJSONResuls();
        return;
      }

      $this->smartyInitialize();
      if (NGS()->getHttpUtils()->isAjaxRequest() && NGS()->isJsFrameworkEnable()){
        $this->assignJson("html", $this->smarty->fetch($this->getTemplate()));
        $this->assignJson("nl", NGS()->getLoadMapper()->getNestedLoads());
        $this->assignJson("pl", $this->getPermalink());
        $this->diplayJSONResuls();
        return true;
      }
      http_response_code($this->getHttpStatusCode());
      $this->smarty->display($this->getTemplate());
    }


    /**
     * display json response
     * @access public
     *
     */
    public function diplayJSONResuls() {
      header('Content-Type: application/json; charset=utf-8');
      http_response_code($this->getHttpStatusCode());
      echo json_encode($this->params);
    }
    /**
     * set custom smarty headers
     *
     */
    public function add_dyn_header($tpl_output, $template) {
      $jsString = '<meta name="generator" content="Naghashyan Framework ' . NGS()->getNGSVersion() . '" />';
      if (NGS()->isJsFrameworkEnable() == false){
        $tpl_output = str_replace('</head>', $jsString, $tpl_output) . "\n";
        return $tpl_output;
      }
      $jsString .= '<script type="text/javascript">';
      $jsString .= "NGS.setInitialLoad('" . NGS()->getRoutesEngine()->getContentLoad() . "', '" . json_encode($this->params["params"]) . "');";
      $jsString .= 'NGS.setModule("' . NGS()->getModulesRoutesEngine()->getModuleNS() . '");';
      $jsString .= 'NGS.setTmst("' . time() . '");';
      $jsString .= 'NGS.setHttpHost("' . NGS()->getHttpUtils()->getHttpHostByNs("", true, false, true) . '");';
      $staticPath = NGS()->getHttpUtils()->getHttpHost(true);
      if (isset(NGS()->getConfig()->static_path)){
        $staticPath = NGS()->getHttpUtils()->getHttpHostByNs("", true, false, true);
      }
      $jsString .= 'NGS.setStaticPath("' . NGS()->getHttpUtils()->getHttpHostByNs("", true, false, true) . '");';
      foreach ($this->getCustomJsParams() as $key => $value){
        $jsString .= $key . " = '" . $value . "';";
      }
      $jsString .= $this->getCustomHeader();
      $jsString .= '</script>';
      $jsString .= '</head>';
      $tpl_output = str_replace('</head>', $jsString, $tpl_output);
      if (NGS()->getEnvironment() == "production"){
        $tpl_output = preg_replace('![\t ]*[\r]+[\t ]*!', '', $tpl_output);
      }
      return $tpl_output;
    }

    protected function getCustomJsParams() {
      return array();
    }

    protected function getCustomHeader() {
      return "";
    }

    public function getSmartyCompileDir() {
      return NGS()->getTemplateDir() . "/" . NGS()->getDefinedValue("SMARTY_COMPILE_DIR");
    }

    public function getSmartyCacheDir() {
      return NGS()->getTemplateDir() . "/" . NGS()->getDefinedValue("SMARTY_CACHE_DIR");
    }

    public function getSmartyConfigDir() {
      return NGS()->getTemplateDir() . "/" . NGS()->getDefinedValue("SMARTY_CONFIG_DIR");
    }

  }

}
