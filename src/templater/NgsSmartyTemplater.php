<?php
/**
 * NGS predefined templater class
 * handle smarty and json responses
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @package ngs.framework.templater
 * @version 4.0.0
 * @year 2010-2020
 *
 * This file is part of the NGS package.
 *
 * @copyright Naghashyan Solutions LLC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ngs\templater {

    use ngs\exceptions\DebugException;
    use Smarty;

    class NgsSmartyTemplater extends Smarty {

        private bool $isHtml = true;
        private string $customJsHeader = '';
        /**
         * constructor
         * reading Smarty config and setting up smarty environment accordingly
         */
        private $params = array();

        /**
         * NgsSmartyTemplater constructor.
         * @param bool $isHtml
         * @throws \SmartyException
         * @throws \ngs\exceptions\DebugException
         */
        public function __construct(bool $isHtml = true) {
            parent::__construct();
            $this->assign('NGS_CMS_DIR', NGS()->getTemplateDir(NGS()->get('NGS_CMS_NS')));
            $this->assign('ADMIN_DIR', NGS()->getTemplateDir('admin'));
            $this->isHtml = $isHtml;
            //register NGS plugins
            $this->registerPlugin('function', 'nest', [$this, 'nest']);
            $this->registerPlugin('function', 'nestLoad', [$this, 'nestLoad']);
            $this->registerPlugin('function', 'ngs', [$this, 'NGS']);
            $moduleList = NGS()->getModulesRoutesEngine()->getAllModules();
            $tmpTplArr = [];
            foreach ($moduleList as $value){
                $tmpTplArr[$value] = NGS()->getTemplateDir($value);
            }
            $this->setTemplateDir($tmpTplArr);
            $this->setCompileDir($this->getSmartyCompileDir());
            $this->setConfigDir($this->getSmartyConfigDir());
            $this->setCacheDir($this->getSmartyCacheDir());
            $this->compile_check = true;
            if ($isHtml){
                // register the outputfilter
                $this->registerFilter('output', array($this, 'addScripts'));
            }
        }

        /**
         * Smarty {nestLoad} function plugin
         *
         * Type:     function<br>
         * Name:     nest<br>
         * Purpose:  nest NGS load from template
         * <br>
         * @param array $params parameters
         * @param object $template template object
         * @return string html
         */
        public function nestLoad($params, $template) {
            if (!isset($params['action'])){
                trigger_error('nest: missing action parameter');
                return;
            }
            $parent = NGS()->getLoadMapper()->getGlobalParentLoad();
            if (isset($params['parent'])){
                $parent = $params['parent'];
            }
            $actionArr = NGS()->getRoutesEngine()->getLoadORActionByAction($params['action']);
            $action = $actionArr['action'];
            if ($actionArr['type'] !== 'load'){
                throw new DebugException($action . ' Load Not found');
            }
            if (class_exists($action) == false){
                throw new DebugException($action . ' Load Not found');
            }
            if (isset($params['args']) && is_array($params['args'])){
                NgsArgs::getInstance()->setArgs($params['args']);
            }
            $loadObj = new $action;
            $loadObj->setIsNestedLoad(true);
            $loadObj->setLoadName($action);
            $loadObj->initialize();
            if (NGS()->getSessionManager()->validateRequest($loadObj) === false){
                $loadObj->onNoAccess();
            }
            $loadObj->service();
            NGS()->getLoadMapper()->setNestedLoads($parent, $params['action'], $loadObj->getJsonParams());
            $template->tpl_vars['ns']->value['inc'][$action]['filename'] = $loadObj->getTemplate();
            $template->tpl_vars['ns']->value['inc'][$action]['params'] = $loadObj->getParams();
            $template->tpl_vars['ns']->value['inc'][$action]['namespace'] = $params['action'];
            $template->tpl_vars['ns']->value['inc'][$action]['action'] = $params['action'];
            $template->tpl_vars['ns']->value['inc'][$action]['parent'] = $parent;
            $template->tpl_vars['ns']->value['inc'][$action]['jsonParam'] = $loadObj->getJsonParams();
            $template->tpl_vars['ns']->value['inc'][$action]['permalink'] = $loadObj->getPermalink();
            return $this->nest(['ns' => $action], $template);
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
         * @throws DebugException
         */
        public function nest($params, $template) {
            if (!isset($params['ns'])){
                throw new DebugException('missing tpl nest parameter');
            }

            if (!isset($template->tpl_vars['ns'])){
                throw new DebugException('missing "' . $params['ns'] . '" nest load');
            }

            $nsValue = $template->tpl_vars['ns']->value;
            if (!isset($nsValue['inc'][$params['ns']])){
                return '';
            }
            $namespace = $nsValue['inc'][$params['ns']]['namespace'];

            $include_file = $nsValue['inc'][$params['ns']]['filename'];
            if (!file_exists($include_file)){
                throw new DebugException('nest: missing file' . $include_file);
            }
            $_tpl = $template->createTemplate($include_file, null, null, $nsValue['inc'][$params['ns']]['params']);
            foreach ($template->tpl_vars as $key => $tplVars){
                $_tpl->assign($key, $tplVars);
            }
            $_tpl->assign('ns', $nsValue['inc'][$params['ns']]['params']);
            if ($_tpl->mustCompile()){
                $_tpl->compileTemplateSource();
            }
            //$_tpl->renderTemplate();
            $_output = $_tpl->display();
            if (NGS()->isJsFrameworkEnable() && !NGS()->getHttpUtils()->isAjaxRequest()){
                $jsonParams = $nsValue['inc'][$params['ns']]['jsonParam'];
                $parentLoad = $nsValue['inc'][$params['ns']]['parent'];
                $jsString = '<script type="text/javascript">';
                $jsString .= '_setNgsDefaults(function(){';
                if ($parentLoad){
                    $jsString .= 'NGS.setNestedLoad("' . $parentLoad . '", "' . $namespace . '", ' . json_encode($jsonParams, JSON_THROW_ON_ERROR, 512) . ')';
                } elseif (isset($nsValue["inc"][$params["ns"]]["action"])){
                    $jsString .= 'NGS.nestLoad("' . $nsValue["inc"][$params["ns"]]["action"] . '", ' . json_encode($jsonParams, JSON_THROW_ON_ERROR, 512) . ', "")';
                }
                $jsString .= '});';
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
                case 'get_js_out_path' :
                    $protocol = false;
                    if (isset($params['protocol']) && $params['protocol'] == true){
                        $protocol = true;
                    }
                    return NGS()->getPublicJsOutputHost($ns, $protocol);
                    break;
                case 'get_js_out_dir' :
                    $protocol = false;
                    if (isset($params['protocol']) && $params['protocol'] == true){
                        $protocol = true;
                    }
                    return NGS()->getPublicOutputHost($ns, $protocol) . '/js';
                    break;
                case 'get_libs_out_dir' :
                    $protocol = false;
                    if (isset($params['protocol']) && $params['protocol'] == true){
                        $protocol = true;
                    }
                    return NGS()->getPublicHost($ns, $protocol) . '/libs';
                    break;
                case 'get_css_out_dir' :
                    $protocol = false;
                    if (isset($params['protocol']) && $params['protocol'] == true){
                        $protocol = true;
                    }
                    return NGS()->getPublicOutputHost($ns, $protocol) . '/css';
                    break;
                case 'get_less_out_dir' :
                    $protocol = false;
                    if (isset($params['protocol']) && $params['protocol'] == true){
                        $protocol = true;
                    }
                    return NGS()->getPublicOutputHost($ns, $protocol) . '/less';
                    break;
                case 'get_sass_out_dir' :
                    $protocol = false;
                    if (isset($params['protocol']) && $params['protocol'] == true){
                        $protocol = true;
                    }
                    return NGS()->getPublicOutputHost($ns, $protocol) . '/sass';
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
                case 'get_environment' :
                    return NGS()->getEnvironment();
                    break;
                case 'get_static_path' :
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
                        return '' . NGS()->getConfig()->API->params->media_url;
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
                $jsString .= '</head>';
                $tpl_output = str_replace('</head>', $jsString, $tpl_output) . "\n";
                return $tpl_output;
            }
            $jsString .= '<script type="text/javascript">';
            $jsString .= 'var _ngs_defaults = [];';
            $jsString .= 'function _setNgsDefaults(calback){_ngs_defaults.push(calback)};';
            $jsString .= 'function _initNgsDefaults(){for(var i=0; i<_ngs_defaults.length;i++){_ngs_defaults[i]();}};';
            $jsString .= '_setNgsDefaults(function(){';
            $jsString .= "NGS.setInitialLoad('" . NGS()->getRoutesEngine()->getContentLoad() . "', '" . json_encode($this->params) . "');";
            $jsModule = '';
            if (!NGS()->getModulesRoutesEngine()->isDefaultModule()){
                $jsModule = NGS()->getModulesRoutesEngine()->getModuleNS() . '/';
            }

            $jsString .= 'NGS.setJsPublicDir("' . $jsModule . NGS()->getPublicJsOutputDir() . '");';
            $jsString .= 'NGS.setModule("' . NGS()->getModulesRoutesEngine()->getModuleNS() . '");';
            $jsString .= 'NGS.setTmst("' . time() . '");';
            $jsString .= 'NGS.setHttpHost("' . NGS()->getHttpUtils()->getHttpHostByNs("", true, false, true) . '");';
            if (!NGS()->getModulesRoutesEngine()->isDefaultModule()){
                $jsString .= 'NGS.setModuleHttpHost("' . NGS()->getHttpUtils()->getHttpHostByNs(NGS()->getModulesRoutesEngine()->getModuleNS(), true, false, true) . '");';
            }
            $staticPath = NGS()->getHttpUtils()->getHttpHost(true);
            if (isset(NGS()->getConfig()->static_path)){
                $staticPath = NGS()->getHttpUtils()->getHttpHostByNs("", true, false, true);
            }
            $jsString .= 'NGS.setStaticPath("' . NGS()->getHttpUtils()->getHttpHostByNs("", true, false, true) . '");';
            foreach ($this->getCustomJsParams() as $key => $value){
                $jsString .= $key . " = '" . $value . "';";
            }
            $jsString .= $this->getCustomHeader();
            $jsString .= '});';
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
