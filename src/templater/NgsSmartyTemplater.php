<?php
/**
 * NGS predefined templater class
 * handle smarty and json responses
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @package ngs.framework.templater
 * @version 2.5.0
 * @year 2010-2016
 *
 * This file is part of the NGS package.
 *
 * @copyright Naghashyan Solutions LLC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ngs\templater {

  use Smarty;

  class NgsSmartyTemplater extends Smarty {

    private $isHtml = true;
    private $customJsHeader = "";
    /**
     * constructor
     * reading Smarty config and setting up smarty environment accordingly
     */
    private $params = array();

    public function __construct($isHtml = true) {
      parent::__construct();
      $this->isHtml = $isHtml;
      //register NGS plugins
      $this->registerPlugin("function", "nest", array($this, 'nest'));
      $this->registerPlugin("function", "ngs", array($this, 'NGS'));

      $this->setTemplateDir(NGS()->getTemplateDir());
      $this->setCompileDir($this->getSmartyCompileDir());
      $this->setConfigDir($this->getSmartyConfigDir());
      $this->setCacheDir($this->getSmartyCacheDir());
      $this->compile_check = true;
      if ($isHtml){
        // register the outputfilter
        $this->registerFilter("output", array($this, "addScripts"));
      }
    }

    /**
     * Smarty {nest} function plugin
     *
     * Type:     function<br>
     * Name:     nest<br>
     * Purpose:  handle math computations in template
     * <br>
     * @param array $params parameters
     * @param object $template template object
     * @return string html
     */
    public function nest($params, $template) {
      if (!isset($params['ns'])){
        trigger_error("nest: missing 'ns' parameter");
        return;
      }

      if (!$template->tpl_vars["ns"]){
        $template->tpl_vars["ns"] = $template->tpl_vars["ns"];
      }

      $nsValue = $template->tpl_vars["ns"]->value;
      $namespace = $nsValue["inc"][$params["ns"]]["namespace"];

      $include_file = $nsValue["inc"][$params["ns"]]["filename"];
      if (!file_exists($include_file)){
        trigger_error("nest: missing 'file' " . $include_file);
        return;
      }
      $_tpl = $template->createTemplate($include_file, null, null, $nsValue["inc"][$params["ns"]]["params"]);
      foreach ($template->tpl_vars as $key => $tplVars){
        $_tpl->assign($key, $tplVars);
      }
      $_tpl->assign("ns", $nsValue["inc"][$params["ns"]]["params"]);
      if ($_tpl->mustCompile()){
        $_tpl->compileTemplateSource();
      }
      //$_tpl->renderTemplate();
      $_output = $_tpl->display();
      if (NGS()->isJsFrameworkEnable() && !NGS()->getHttpUtils()->isAjaxRequest()){
        $jsonParams = $nsValue["inc"][$params["ns"]]["jsonParam"];
        $parentLoad = $nsValue["inc"][$params["ns"]]["parent"];
        $jsString = '<script type="text/javascript">';
        $jsString .= 'NGS.setNestedLoad("' . $parentLoad . '", "' . $namespace . '", ' . json_encode($jsonParams) . ')';
        $jsString .= '</script>';
        $_output = $jsString . $_output;
      }

      return $_output;
    }


    /**
     * Smarty plugin
     *
     * This plugin is only for Smarty3
     * @package Smarty
     * @subpackage PluginsFunction
     */

    /**
     * Smarty {NGS} function plugin
     *
     * Type:     function<br>
     * Name:     NGS<br>
     * Purpose:  helper function gor access global NGS Object
     * <br>
     *
     * @param array $params parameters
     * @param object $template template object
     * @return  string url
     */
    public function NGS($params, $template) {
      if (!isset($params['cmd'])){
        trigger_error("NGS: missing 'cmd' parameter");
        return;
      }
      $ns = "";
      if (isset($params['ns'])){
        $ns = $params['ns'];
      }
      switch ($params['cmd']){
        case 'get_js_out_dir' :
          $protocol = false;
          if (isset($params['protocol']) && $params['protocol'] == true){
            $protocol = true;
          }
          return NGS()->getPublicOutputHost($ns, $protocol) . "/js";
          break;
        case 'get_css_out_dir' :
          $protocol = false;
          if (isset($params['protocol']) && $params['protocol'] == true){
            $protocol = true;
          }
          return NGS()->getPublicOutputHost($ns, $protocol) . "/css";
          break;
        case 'get_less_out_dir' :
          $protocol = false;
          if (isset($params['protocol']) && $params['protocol'] == true){
            $protocol = true;
          }
          return NGS()->getPublicOutputHost($ns, $protocol) . "/less";
          break;
        case 'get_template_dir' :
          return NGS()->getTemplateDir($ns);
          break;
        case 'get_http_host' :
          $protocol = false;
          if (isset($params['protocol']) && $params['protocol'] == true){
            $protocol = true;
          }
          return NGS()->getHttpUtils()->getHttpHostByNs($ns, $protocol);
          break;
        case 'get_host' :
          $protocol = false;
          if (isset($params['protocol']) && $params['protocol'] == true){
            $protocol = true;
          }
          return NGS()->getHttpUtils()->getHost();
          break;
        case 'get_static_path' :
          if (isset(NGS()->getConfig()->static_path)){
            return "//" . NGS()->getConfig()->static_path;
          }
          $protocol = false;
          if (isset($params['protocol']) && $params['protocol'] == true){
            $protocol = true;
          }
          return NGS()->getHttpUtils()->getNgsStaticPath($ns, $protocol);
          break;
        case 'get_version' :
          return NGS()->getVersion();
          break;
        case 'get_media_url' :
          if (isset(NGS()->getConfig()->API->params->media_url)){
            return "" . NGS()->getConfig()->API->params->media_url;
          }
          break;
        default :
          break;
      }
    }

    /**
     * set custom smarty headers
     *
     */
    public function addScripts($tpl_output, $template) {
      $jsString = '<meta name="generator" content="Naghashyan Framework ' . NGS()->getNGSVersion() . '" />';
      if (NGS()->isJsFrameworkEnable() == false){
        $tpl_output = str_replace('</head>', $jsString, $tpl_output) . "\n";
        return $tpl_output;
      }
      $jsString .= '<script type="text/javascript">';
      $jsString .= "NGS.setInitialLoad('" . NGS()->getRoutesEngine()->getContentLoad() . "', '" . json_encode($this->params) . "');";
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


    public function fetchTemplate($templatePath) {
      return $this->fetch($templatePath);
    }

    protected function getCustomJsParams() {
      return array();
    }


    public function setCustomHeader($customJsHeader) {
      $this->customJsHeader = $customJsHeader;
    }

    protected function getCustomHeader() {
      return $this->customJsHeader;
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
