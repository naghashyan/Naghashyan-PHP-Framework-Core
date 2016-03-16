<?php
/**
 * Base ngs class
 * for static function that will
 * vissible from any classes
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @year 2014-2016
 * @package ngs.framework
 * @version 2.3.0
 *
 *
 * This file is part of the NGS package.
 *
 * @copyright Naghashyan Solutions LLC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 *
 */
use ngs\framework\exceptions\DebugException;
use ngs\framework\util\NgsArgs;

require_once("routes/NgsModuleRoutes.class.php");
require_once("util/HttpUtils.class.php");

class NGS {

  private static $instance = null;
  private $ngsConfig = null;
  private $config = array();
  private $loadMapper = null;
  private $routesEngine = null;
  private $moduleRoutesEngine = null;
  private $sessionManager = null;
  private $tplEngine = null;
  private $fileUtils = null;
  private $httpUtils = null;
  private $ngsUtils = null;
  private $jsBuilder = null;
  private $cssBuilder = null;
  private $lessBuilder = null;
  private $isModuleEnable = false;
  private $define = array();

  public function initialize() {
    $this->registerAutoload();
    $moduleConstatPath = realpath(NGS()->getConfigDir()."/constants.php");
    if ($moduleConstatPath){
      require_once $moduleConstatPath;
    }
    $this->getModulesRoutesEngine(true)->initialize();
  }

  /**
   * Returns an singleton instance of this class
   *
   * @return object NGS
   */
  public static function getInstance() {
    if (self::$instance == null){
      self::$instance = new NGS();
    }
    return self::$instance;
  }

  /*
   |--------------------------------------------------------------------------
   | DEFINNING NGS MODULES
   |--------------------------------------------------------------------------
   */
  public function getDefinedValue($key) {
    if (isset($this->define[$key])){
      return $this->define[$key];
    }
    return null;
  }

  public function define($key, $value) {
    $this->define[$key] = $value;
    return true;
  }

  public function defined($key) {
    if (isset($this->define[$key])){
      return true;
    }
    return false;
  }

  public function isModuleEnable() {
    return $this->isModuleEnable;
  }

  /**
   * this method return global ngs root config file
   *
   *
   * @return array config
   */
  public function getNgsConfig() {
    if (isset($this->ngsConfig)){
      return $this->ngsConfig;
    }
    return $this->ngsConfig = json_decode(file_get_contents($this->getConfigDir("ngs")."/config_".$this->getShortEnvironment().".json"));
  }

  /**
   * static function that return ngs
   * global config
   *
   * @params $prefix
   *
   * @return array config
   */
  public function getConfig($prefix = null) {
    if (NGS()->getModulesRoutesEngine()->getModuleNS() == null){
      return $this->getNgsConfig();
    }
    if ($prefix == null){
      $_prefix = NGS()->getModulesRoutesEngine()->getModuleNS();
    } else{
      $_prefix = $prefix;
    }
    if (isset($this->config[$_prefix])){
      return $this->config[$_prefix];
    }
    return $this->config[$_prefix] = json_decode(file_get_contents($this->getConfigDir($_prefix)."/config_".$this->getShortEnvironment().".json"));
  }

  public function args(){
    return NgsArgs::getInstance();
  }
  /*
   |--------------------------------------------------------------------------
   | DIR FUNCTIONS SECTION
   |--------------------------------------------------------------------------
   */
  /**
   * this method do calculate
   * and  return module root dir by namespace
   *
   * @param String $ns
   *
   * @return String config dir path
   */
  public function getModuleDirByNS($ns = "") {
    return NGS()->getModulesRoutesEngine()->getRootDir($ns);
  }

  /**
   * this method do calculate and return NGS Framework
   * dir path by namespace
   *
   *
   * @return String config dir path
   */
  public function getFrameworkDir() {
    return realpath($this->getClassesDir($this->getDefinedValue("FRAMEWORK_NS"))."/framework");
  }

  /**
   * this method do calculate and return config
   * dir path by namespace
   *
   * @param String $ns
   *
   * @return String config dir path
   */
  public function getConfigDir($ns = "") {
    return realpath($this->getModuleDirByNS($ns)."/".$this->getDefinedValue("CONF_DIR"));
  }

  /**
   * this method do calculate and return template
   * dir path by namespace
   *
   *
   * @param String $ns
   *
   * @return String template dir path
   */
  public function getTemplateDir($ns = "") {
    return realpath($this->getModuleDirByNS($ns)."/".$this->getDefinedValue("TEMPLATES_DIR"));
  }

  /**
   * this method do calculate and return temp
   * dir path by namespace
   *
   *
   * @param String $ns
   *
   * @return String temp dir path
   */
  public function getTempDir($ns = "") {
    return realpath($this->getModuleDirByNS($ns)."/".$this->getDefinedValue("TEMP_DIR"));
  }

  /**
   * this method do calculate and return data
   * dir path by namespace
   *
   *
   * @param String $ns
   *
   * @return String data dir path
   */
  public function getDataDir($ns = "") {
    return realpath($this->getModuleDirByNS($ns)."/".$this->getDefinedValue("DATA_DIR"));
  }

  /**
   * this method do calculate and return public
   * dir path by namespace
   *
   *
   * @param String $ns
   *
   * @return String public dir path
   */
  public function getPublicDir($ns = "") {
    return realpath($this->getModuleDirByNS($ns)."/".$this->getDefinedValue("PUBLIC_DIR"));
  }

  /**
   * this method do calculate and return public output
   * dir path by namespace
   *
   *
   * @param String $ns
   *
   * @return String public output dir path
   */
  public function getPublicOutputDir($ns = "") {
    $outDir = realpath($this->getPublicDir($ns)."/".$this->getDefinedValue("PUBLIC_OUTPUT_DIR"));
    if ($outDir == false){
      mkdir($this->getPublicDir($ns)."/".$this->getDefinedValue("PUBLIC_OUTPUT_DIR"), 0755, true);
    } else{
      return $outDir;
    }
    return realpath($this->getPublicDir($ns)."/".$this->getDefinedValue("PUBLIC_OUTPUT_DIR"));
  }

  /**
   * this method do calculate and return public output
   * dir path by namespace
   *
   *
   * @param String $ns
   *
   * @return String public output dir path
   */
  public function getCssDir($ns = "") {
    $cssDir = realpath($this->getPublicDir($ns)."/".$this->getDefinedValue("CSS_DIR"));
    if ($cssDir == false){
      mkdir($this->getPublicDir($ns)."/".$this->getDefinedValue("CSS_DIR"), 0755, true);
    } else{
      return $cssDir;
    }
    return realpath($this->getPublicDir($ns)."/".$this->getDefinedValue("CSS_DIR"));
  }

  /**
   * this method do calculate and return public output
   * dir path by namespace
   *
   *
   * @param String $ns
   *
   * @return String public less dir path
   */
  public function getLessDir($ns = "") {
    $lessDir = realpath($this->getPublicDir($ns)."/".$this->getDefinedValue("LESS_DIR"));
    if ($lessDir == false){
      mkdir($this->getPublicDir($ns)."/".$this->getDefinedValue("LESS_DIR"), 0755, true);
    } else{
      return $lessDir;
    }
    return realpath($this->getPublicDir($ns)."/".$this->getDefinedValue("LESS_DIR"));
  }

  /**
   * this method do calculate and return public output
   * dir path by namespace
   *
   *
   * @param String $ns
   *
   * @return String public output dir path
   */
  public function getJsDir($ns = "") {
    $jsDir = realpath($this->getPublicDir($ns)."/".$this->getDefinedValue("JS_DIR"));
    if ($jsDir == false){
      mkdir($this->getPublicDir($ns)."/".$this->getDefinedValue("JS_DIR"), 0755, true);
    } else{
      return $jsDir;
    }
    return realpath($this->getPublicDir($ns)."/".$this->getDefinedValue("JS_DIR"));
  }

  /**
   * this method do calculate and return Classes
   * dir path by namespace
   *
   *
   * @param String $ns
   *
   * @return String classes dir path
   */
  public function getClassesDir($ns = "") {
    return realpath($this->getModuleDirByNS($ns)."/".$this->getDefinedValue("CLASSES_DIR"));
  }

  /**
   * this method return loads namespace
   *
   * @return String loads namespace
   */
  public function getLoadsPackage() {
    return $this->getDefinedValue("LOADS_DIR");
  }

  /**
   * this method return actions namespace
   *
   * @return String actions namespace
   */
  public function getActionPackage() {
    return $this->getDefinedValue("ACTIONS_DIR");
  }

  /*
   |--------------------------------------------------------------------------
   | HOST FUNCTIONS SECTION
   |--------------------------------------------------------------------------
   */


  public function getPublicHostByNS($ns = "", $withProtocol = false) {
    if ($ns == ""){
      if ($this->getModulesRoutesEngine()->isDefaultModule()){
        return $this->getHttpUtils()->getHttpHost(true, $withProtocol);
      }
      $ns = $this->getModulesRoutesEngine()->getModuleNS();
    }
    return $this->getHttpUtils()->getHttpHost(true, $withProtocol)."/".$ns;
  }

  public function getPublicOutputHost($ns = "", $withProtocol = false) {
    return $this->getHttpUtils()->getNgsStaticPath($ns, $withProtocol)."/".$this->getDefinedValue("PUBLIC_OUTPUT_DIR");
  }

  /**
   * this method  return imusic.am
   * sessiomanager if defined by user it return it if not
   * return imusic.am default sessiomanager
   *
   * @throws DebugException if SESSION_MANAGER Not found
   *
   * @return Object loadMapper
   */

  public function getSessionManager() {
    if ($this->sessionManager != null){
      return $this->sessionManager;
    }
    try{
      $ns = $this->getDefinedValue("SESSION_MANAGER");
      $this->sessionManager = new $ns();
    } catch (Exception $e){
      throw new DebugException("SESSION MANAGER NOT FOUND, please check in constants.php SESSION_MANAGER variable", 1);
    }
    return $this->sessionManager;
  }

  /**
   * static function that return imusic.am
   * fileutils if defined by user it return it if not
   * return imusic.am default fileutils
   *
   * @throws DebugException if ROUTES_ENGINE Not found
   *
   * @return Object fileUtils
   */
  public function getRoutesEngine() {
    if ($this->routesEngine != null){
      return $this->routesEngine;
    }
    try{
      $ns = $this->getDefinedValue("ROUTES_ENGINE");
      $this->routesEngine = new $ns();
    } catch (Exception $e){
      throw new DebugException("ROUTES ENGINE NOT FOUND, please check in constants.php ROUTES_ENGINE variable", 1);
    }
    return $this->routesEngine;
  }

  /**
   * this function that return imusic.am
   * fileutils if defined by user it return it if not
   * return imusic.am default fileutils
   *
   * @params $force string
   *
   * @throws DebugException if MODULES_ROUTES_ENGINE Not found
   *
   * @return Object fileUtils
   */
  public function getModulesRoutesEngine($force = false) {
    if ($this->moduleRoutesEngine != null && $force == false){
      return $this->moduleRoutesEngine;
    }
    try{
      $ns = $this->getDefinedValue("MODULES_ROUTES_ENGINE");
      $this->moduleRoutesEngine = new $ns();
    } catch (Exception $e){
      throw new DebugException("ROUTES ENGINE NOT FOUND, please check in constants.php ROUTES_ENGINE variable", 1);
    }
    return $this->moduleRoutesEngine;
  }

  /**
   * static function that return imusic.am
   * loadmapper if defined by user it return it if not
   * return imusic.am default loadmapper
   *
   * @throws DebugException if MAPPER Not found
   *
   * @return Object loadMapper
   */
  public function getLoadMapper() {
    if ($this->loadMapper != null){
      return $this->loadMapper;
    }
    try{
      $ns = $this->getDefinedValue("LOAD_MAPPER");
      $this->loadMapper = new $ns;
    } catch (Exception $e){
      throw new DebugException("LOAD MAPPER NOT FOUND, please check in constants.php LOAD_MAPPER variable", 1);
    }
    return $this->loadMapper;
  }

  /**
   * static function that return imusic.am
   * loadmapper if defined by user it return it if not
   * return imusic.am default loadmapper
   *
   * @throws DebugException if TEMPLATE_ENGINE Not found
   *
   * @return Object loadMapper
   */
  public function getTemplateEngine() {
    if ($this->tplEngine != null){
      return $this->tplEngine;
    }
    try{
      $ns = $this->getDefinedValue("TEMPLATE_ENGINE");
      $this->tplEngine = new $ns;
    } catch (Exception $e){
      throw new DebugException("TEMPLATE ENGINE NOT FOUND, please check in constants.php TEMPLATE_ENGINE variable", 1);
    }
    return $this->tplEngine;
  }

  /**
   * static function that return ngs
   * fileutils if defined by user it return it if not
   * return ngs default fileutils
   *
   * @throws DebugException if NGS_UTILS Not found
   *
   * @return Object fileUtils
   */
  public function getNgsUtils() {
    if ($this->ngsUtils != null){
      return $this->ngsUtils;
    }
    try{
      $ns = "\\".$this->getDefinedValue("NGS_UTILS");
      $this->ngsUtils = new $ns;
    } catch (Exception $e){
      throw new DebugException("NGS UTILS NOT FOUND, please check in constants.php NGS_UTILS variable");
    }
    return $this->ngsUtils;
  }

  /**
   * static function that return ngs
   * fileutils if defined by user it return it if not
   * return ngs default fileutils
   *
   * @throws DebugException if FILE_UTILS Not found
   *
   * @return Object fileUtils
   */
  public function getFileUtils() {
    if ($this->fileUtils != null){
      return $this->fileUtils;
    }
    try{
      $ns = "\\".$this->getDefinedValue("FILE_UTILS");
      $this->fileUtils = new $ns;
    } catch (Exception $e){
      throw new DebugException("FILE UTILS NOT FOUND, please check in constants.php FILE_UTILS variable");
    }
    return $this->fileUtils;
  }

  /**
   * static function that return ngs
   * fileutils if defined by user it return it if not
   * return ngs default fileutils
   *
   * @throws DebugException if HTTP_UTILS Not found
   *
   * @return Object fileUtils
   */
  public function getHttpUtils() {
    if ($this->httpUtils != null){
      return $this->httpUtils;
    }
    try{
      $ns = $this->getDefinedValue("HTTP_UTILS");
      $this->httpUtils = new $ns;
    } catch (Exception $e){
      throw new DebugException("HTTP UTILS NOT FOUND, please check in constants.php HTTP_UTILS variable");
    }
    return $this->httpUtils;
  }

  /**
   * this method return ngs or user defined jsBuilder object
   *
   * @throws DebugException if JS_BUILDER Not found
   *
   * @return Object JsBuilder
   */
  public function getJsBuilder() {
    if ($this->jsBuilder != null){
      return $this->jsBuilder;
    }
    try{
      $classPath = $this->getDefinedValue("JS_BUILDER");
      $this->jsBuilder = new $classPath();
    } catch (Exception $e){
      throw new DebugException("JS UTILS NOT FOUND, please check in constants.php JS_BUILDER variable");
    }
    return $this->jsBuilder;
  }

  /**
   * this method return ngs or user defined cssBuilder object
   *
   * @throws DebugException if CSS_BUILDER Not found
   *
   * @return Object CssBuilder
   */
  public function getCssBuilder() {
    if ($this->cssBuilder != null){
      return $this->cssBuilder;
    }
    try{
      $classPath = $this->getDefinedValue("CSS_BUILDER");
      $this->cssBuilder = new $classPath();
    } catch (Exception $e){
      throw new DebugException("CSS UTILS NOT FOUND, please check in constants.php CSS_BUILDER variable");
    }
    return $this->cssBuilder;
  }

  /**
   * this method return ngs or user defined lessBuilder object
   *
   * @throws DebugException if LESS_BUILDER Not found
   *
   * @return Object fileUtils
   */
  public function getLessBuilder() {
    if ($this->lessBuilder != null){
      return $this->lessBuilder;
    }
    try{
      $classPath = $this->getDefinedValue("LESS_BUILDER");
      $this->lessBuilder = new $classPath();
    } catch (Exception $e){
      throw new DebugException("LESS UTILS NOT FOUND, please check in constants.php LESS_BUILDER variable");
    }
    return $this->lessBuilder;
  }

  public function getFileStreamerByType($fileType) {
    switch ($fileType){
      case 'js' :
        return $this->getJsBuilder();
        break;
      case 'css' :
        return $this->getCssBuilder();
        break;
      case 'less' :
        return $this->getLessBuilder();
        break;
      default :
        return $this->getFileUtils();
    }
  }

  /**
   * return project prefix
   * @static
   * @access
   * @return String $namespace
   */
  public function getEnvironment() {
    return $this->getDefinedValue("ENVIRONMENT");
  }

  /**
   * return short env prefix
   * @static
   * @access
   * @return String $env
   */
  public function getShortEnvironment() {
    $env = "prod";
    if ($this->getEnvironment() == "development"){
      $env = "dev";
    }
    return $env;
  }

  public function getVersion() {
    return $this->getDefinedValue("VERSION");
  }

  public function getNGSVersion() {
    return $this->getDefinedValue("NGSVERSION");
  }

  /**
   * check if ngs js framework enable
   *
   * @return bool $_status
   */
  public function isJsFrameworkEnable() {
    return $this->getDefinedValue("JS_FRAMEWORK_ENABLE");
  }

  public function getDynObject() {
    return new \ngs\framework\util\NgsDynamic();
  }

  /*
   |--------------------------------------------------------------------------
   | AUTOLOAD SECTION
   |--------------------------------------------------------------------------
   */

  /**
   * register file autload event for namespace use include
   */
  public function registerAutoload() {
    spl_autoload_register(function ($class) {
      $class = str_replace('\\', '/', $class);
      $ns = substr($class, 0, strpos($class, "/"));
      $class = substr($class, strpos($class, "/") + 1);
      $classPath = NGS()->getClassesDir($ns);
      $filePath = realpath($classPath.'/'.$class.'.class.php');
      if (file_exists($filePath)){
        require_once($filePath);
      }
    });
  }

}

function NGS() {
  return NGS::getInstance();
}

require_once("system/NgsDefaultConstants.class.php");
NGS()->initialize();
