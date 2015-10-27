<?php
/**
 * Smarty util class extends from main smarty class
 * provides extra features for ngs
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @package ngs.framework.templater
 * @version 2.1.1
 * @year 2010-2015
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
    public function __construct() {
    }

    public function smartyInitialize() {
      require_once (realpath(NGS()->getFrameworkDir()."/lib/smarty/Smarty.class.php"));
      $this->smarty = new \Smarty();
      $this->smarty->template_dir = NGS()->getTemplateDir();
      $this->smarty->setCompileDir($this->getSmartyCompileDir());
      $this->smarty->config_dir = $this->getSmartyConfigDir();
      $this->smarty->cache_dir = $this->getSmartyCacheDir();
      $this->smarty->compile_check = true;

      $this->smarty->assign("TEMPLATE_DIR", NGS()->getTemplateDir());
      $this->smarty->assign("pm", NGS()->getLoadMapper());

      $protocol = "//";

      // register the outputfilter
      $this->smarty->registerFilter("output", array($this, "add_dyn_header"));

      $staticPath = $protocol.$_SERVER["HTTP_HOST"];
      if (isset(NGS()->getConfig()->static_path) && NGS()->getConfig()->static_path != null) {
        $staticPath = $protocol.NGS()->getConfig()->static_path;
      }
      $this->assign("SITE_URL", NGS()->getHttpUtils()->getHttpHost());
      $this->assign("SITE_PATH", NGS()->getHttpUtils()->getHttpHost(true));
      $this->assign("STATIC_PATH", $staticPath);
      foreach ($this->smartyParams as $key => $value) {
        $this->smarty->assign($key, $value);
      }
    }

    public function assign($key, $value) {
      $this->smartyParams[$key] = $value;
    }

    public function assignJson($key, $value) {
      $this->params[$key] = $value;
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
     * @return String $template|null
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

    public function display() {
      if ($this->getTemplate() == null) {
        $this->diplayJSONResuls();
        return;
      }
      $this->smartyInitialize();
      if (NGS()->getHttpUtils()->isAjaxRequest() && NGS()->isJsFrameworkEnable()) {
        $this->assignJson("html", $this->smarty->fetch($this->getTemplate()));
        $this->assignJson("nl", NGS()->getLoadMapper()->getNestedLoads());
        $this->assignJson("pl", $this->getPermalink());
        $this->diplayJSONResuls();
        return true;
      } elseif ($this->getTemplate() != null) {
        $this->smarty->display($this->getTemplate());
      }
    }

    /**
     * Return a thingie based on $paramie
     * @abstract
     * @access
     * @param boolean $paramie
     * @return integer|babyclass
     */
    private function createJSON($arr) {
      $jsonArr = array();
      if (!isset($arr["status"])) {
        $arr["status"] = "ok";
      }
      if ($arr["status"] == "error") {
        header("HTTP/1.0 400 BAD REQUEST");
        if (isset($arr["code"])) {
          $jsonArr["code"] = $arr["code"];
        }
        if (isset($arr["msg"])) {
          $jsonArr["msg"] = $arr["msg"];
        }
        if (isset($arr["params"])) {
          $jsonArr["params"] = $arr["params"];
        }
      } else {
        if (isset($arr["_params_"])) {
          $jsonArr = array_merge($jsonArr, $arr["_params_"]);
          unset($arr["_params_"]);
        }

        $jsonArr = array_merge($jsonArr, $arr);
      }
      return json_encode($jsonArr);
    }

    /**
     * Return a thingie based on $paramie
     * @abstract
     * @access
     * @param boolean $paramie
     * @return integer|babyclass
     */
    private function diplayJSONResuls() {
      try {
        header('Content-Type: application/json; charset=utf-8');
        echo $this->createJSON($this->params);
      } catch (Exception $ex) {
        echo $ex->getMessage();
      }

    }

    public function add_dyn_header($tpl_output, $template) {
      $jsString = '<meta name="generator" content="Naghashyan Framework '.NGS()->getNGSVersion().'" />';
      if (NGS()->isJsFrameworkEnable() == false) {
        $tpl_output = str_replace('</head>', $jsString, $tpl_output)."\n";
        return $tpl_output;
      }
      $jsString .= '<script type="text/javascript">';
      $jsString .= "NGS.setInitialLoad('".NGS()->getRoutesEngine()->getContentLoad()."', '".json_encode($this->params)."');";
      $jsString .= 'NGS.setModule("'.NGS()->getModulesRoutesEngine()->getModuleNS().'");';
      $jsString .= 'NGS.setTmst("'.time().'");';
      $httpHost = NGS()->getHttpUtils()->getHttpHostByNs("", true);
      if (NGS()->getModulesRoutesEngine()->getModuleType() == "path") {
        $httpHost = NGS()->getHttpUtils()->getNgsStaticPath("", true);
      }
      $jsString .= 'NGS.setHttpHost("'.$httpHost.'");';
      $staticPath = NGS()->getHttpUtils()->getNgsStaticPath("", true);
      if (isset(NGS()->getConfig()->static_path)) {
        $staticPath = NGS()->getConfig()->static_path;
      }
      $jsString .= 'NGS.setStaticPath("'.$staticPath.'");';
      foreach ($this->getCustomJsParams() as $key => $value) {
        $jsString .= $key." = '".$value."';";
      }
      $jsString .= '</script>';
      $jsString .= '</head>';
      $tpl_output = str_replace('</head>', $jsString, $tpl_output);
      if (NGS()->getEnvironment() == "production") {
        $tpl_output = preg_replace('![\t ]*[\r]+[\t ]*!', '', $tpl_output);
      }
      return $tpl_output;
    }

    protected function getCustomJsParams() {
      return array();
    }

    public function getSmartyCompileDir() {
      return NGS()->getTemplateDir()."/".NGS()->getDefinedValue("SMARTY_COMPILE_DIR");
    }

    public function getSmartyCacheDir() {
      return NGS()->getTemplateDir()."/".NGS()->getDefinedValue("SMARTY_CACHE_DIR");
    }

    public function getSmartyConfigDir() {
      $configDir = "config";
      if (defined("SMARTY_CONFIG_DIR")) {
        $configDir = SMARTY_CONFIG_DIR;
      }
      return NGS()->getTemplateDir()."/".$configDir;
    }

  }

}
