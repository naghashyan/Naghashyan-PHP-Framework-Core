<?php
/**
 * Base NGS class
 * for static function that will
 * vissible from any classes
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site https://naghashyan.com
 * @year 2014-2020
 * @package ngs.framework
 * @version 4.0.0
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

use ngs\exceptions\DebugException;
use ngs\routes\NgsModuleRoutes;
use ngs\util\HttpUtils;
use ngs\util\NgsArgs;

require_once('routes/NgsModuleRoutes.php');
require_once('util/HttpUtils.php');

class NGS {

  private static $instance = null;
  private $ngsConfig = null;
  private $config = array();
  private $dispatcher = null;
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
  private $sassBuilder = null;
  private $isModuleEnable = false;
  private $define = [];

  public function initialize() {
    $moduleConstatPath = realpath(NGS()->getConfigDir() . '/constants.php');
    if ($moduleConstatPath){
      require_once $moduleConstatPath;
    }
    $envConstantFile = realpath(NGS()->getConfigDir() . '/constants_' . $this->getShortEnvironment() . '.php');
    if ($envConstantFile){
      require_once $envConstantFile;
    }
    $this->getModulesRoutesEngine(true)->initialize();
  }

  /**
   * Returns an singleton instance of this class
   *
   * @return object NGS
   */
  public static function getInstance(): NGS {
    if (self::$instance === null){
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

  public function get($key) {
    return $this->getDefinedValue($key);
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
    return $this->ngsConfig = json_decode(file_get_contents($this->get('NGS_ROOT') . '/config_' . $this->getShortEnvironment() . '.json'));
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
    return $this->config[$_prefix] = json_decode(file_get_contents($this->getConfigDir($_prefix) . '/config_' . $this->getShortEnvironment() . '.json'));
  }

  public function args() {
    return NgsArgs::getInstance();
  }


  public function setDispatcher($dispatcher) {
    $this->dispatcher = $dispatcher;
  }

  public function getDispatcher() {
    return $this->dispatcher;
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
  public function getModuleDirByNS(string $ns = '') {
    return NGS()->getModulesRoutesEngine()->getRootDir($ns);
  }

  /**
   * this method do calculate and return NGS Framework
   * dir path by namespace
   *
   *
   * @return String config dir path
   */
  public function getFrameworkDir(): string {
    return __DIR__;
  }

  /**
   * this method do calculate and return NGS CMS
   * dir path
   *
   *
   * @return String config dir path
   */
  public function getNgsCmsDir(): string {
    return dirname(__DIR__, 2) . '/ngs-php-cms/src';
  }

  /**
   * this method do calculate and return config
   * dir path by namespace
   *
   * @param String $ns
   *
   * @return String config dir path
   */
  public function getConfigDir(string $ns = ''): ?string {
    return realpath($this->getModuleDirByNS($ns) . '/' . $this->get('CONF_DIR'));
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
  public function getTemplateDir($ns = ''): string {
    return realpath($this->getModuleDirByNS($ns) . '/' . $this->get('TEMPLATES_DIR'));
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
  public function getTempDir($ns = ''): string {
    return realpath($this->getModuleDirByNS($ns) . '/' . $this->get('TEMP_DIR'));
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
  public function getDataDir($ns = ''): string {
    return realpath($this->getModuleDirByNS($ns) . '/' . $this->get('DATA_DIR'));
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
  public function getPublicDir($ns = ''): string {
    return realpath($this->getModuleDirByNS($ns) . '/' . $this->get('PUBLIC_DIR'));
  }

  /**
   * this method do calculate and return web
   * dir path by namespace
   *
   *
   * @param String $ns
   *
   * @return String public dir path
   */
  public function getWEbDir($ns = ''): string {
    return realpath($this->getModuleDirByNS($ns) . '/' . $this->get('WEB_DIR'));
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
  public function getPublicOutputDir($ns = '') {
    $outDir = realpath($this->getPublicDir($ns) . '/' . $this->get('PUBLIC_OUTPUT_DIR'));
    if ($outDir === false){
      if (!mkdir($concurrentDirectory = $this->getPublicDir($ns) . '/' . $this->get('PUBLIC_OUTPUT_DIR'), 0755, true)
        && !is_dir($concurrentDirectory)){
        throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
      }
    } else{
      return $outDir;
    }
    return realpath($this->getPublicDir($ns) . '/' . $this->get('PUBLIC_OUTPUT_DIR'));
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
  public function getCssDir($ns = '') {
    if ($this->get('WEB_DIR')){
      $webDir = $this->getWEbDir($ns);
    } else{
      $webDir = $this->getPublicDir($ns);
    }
    $cssDir = realpath($webDir . '/' . $this->get('CSS_DIR'));
    if ($cssDir === false){
      if (!mkdir($concurrentDirectory = $webDir . '/' . $this->get('CSS_DIR'), 0755, true) &&
        !is_dir($concurrentDirectory)){
        throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
      }
    } else{
      return $cssDir;
    }
    return realpath($webDir . '/' . $this->get('CSS_DIR'));
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
  public function getSassDir($ns = '') {
    if ($this->get('WEB_DIR')){
      $webDir = $this->getWEbDir($ns);
    } else{
      $webDir = $this->getPublicDir($ns);
    }
    $lessDir = realpath($webDir . '/' . $this->get('SASS_DIR'));
    if ($lessDir === false){
      if (!mkdir($concurrentDirectory = $webDir . '/' . $this->get('SASS_DIR'), 0755, true) && !is_dir($concurrentDirectory)){
        throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
      }
    } else{
      return $lessDir;
    }
    return realpath($webDir . '/' . $this->getDefinedValue('SASS_DIR'));
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
  public function getLessDir(string $ns = '') {
    if ($this->get('WEB_DIR')){
      $webDir = $this->getWEbDir($ns);
    } else{
      $webDir = $this->getPublicDir($ns);
    }
    $lessDir = realpath($webDir . '/' . $this->get('LESS_DIR'));
    if ($lessDir === false){
      if (!mkdir($concurrentDirectory = $webDir . '/' . $this->get('LESS_DIR'), 0755, true) && !is_dir($concurrentDirectory)){
        throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
      }
    } else{
      return $lessDir;
    }
    return realpath($webDir . '/' . $this->get('LESS_DIR'));
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
  public function getJsDir($ns = '') {
    if ($this->get('WEB_DIR')){
      $webDir = $this->getWEbDir($ns);
    } else{
      $webDir = $this->getPublicDir($ns);
    }
    $jsDir = realpath($webDir . '/' . $this->get('JS_DIR'));
    if ($jsDir === false){
      if (!mkdir($concurrentDirectory = $webDir . '/' . $this->get('JS_DIR'), 0755, true) && !is_dir($concurrentDirectory)){
        throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
      }
    } else{
      return $jsDir;
    }
    return realpath($webDir . '/' . $this->get('JS_DIR'));
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
  public function getClassesDir($ns = '') {
    return realpath($this->getModuleDirByNS($ns) . '/' . $this->get('CLASSES_DIR'));
  }

  /**
   * this method return loads namespace
   *
   * @return String loads namespace
   */
  public function getLoadsPackage() {
    return $this->get('LOADS_DIR');
  }

  /**
   * this method return actions namespace
   *
   * @return String actions namespace
   */
  public function getActionPackage() {
    return $this->get('ACTIONS_DIR');
  }

  /*
   |--------------------------------------------------------------------------
   | HOST FUNCTIONS SECTION
   |--------------------------------------------------------------------------
   */

  /**
   * @param string $ns
   * @param bool $withProtocol
   * @return mixed|string|null
   * @throws DebugException
   */
  public function getPublicHostByNS(string $ns = '', bool $withProtocol = false) {
    if ($ns === ''){
      if ($this->getModulesRoutesEngine()->isDefaultModule()){
        return $this->getHttpUtils()->getHttpHost(true, $withProtocol);
      }
      $ns = $this->getModulesRoutesEngine()->getModuleNS();
    }
    return $this->getHttpUtils()->getHttpHost(true, $withProtocol) . '/' . $ns;
  }

  public function getPublicOutputHost($ns = '', $withProtocol = false) {
    return $this->getHttpUtils()->getNgsStaticPath($ns, $withProtocol) . '/' . $this->get('PUBLIC_OUTPUT_DIR');
  }

  public function getPublicJsOutputHost($ns = '', $withProtocol = false) {
    return $this->getHttpUtils()->getNgsStaticPath($ns, $withProtocol) . '/' . $this->getPublicJsOutputDir();
  }

  public function getPublicJsOutputDir() {
    if ($this->get('JS_BUILD_MODE') === 'development'){
      return $this->get('WEB_DIR') . '/' . $this->get('JS_DIR');
    }
    return $this->get('PUBLIC_OUTPUT_DIR') . '/' . $this->get('JS_DIR');
  }

  /**
   * this method  return imusic.am
   * sessiomanager if defined by user it return it if not
   * return imusic.am default sessiomanager
   *
   * @return \IM\managers\SessionManager
   * @throws DebugException if SESSION_MANAGER Not found
   *
   */

  public function getSessionManager() {
    if ($this->sessionManager !== null){
      return $this->sessionManager;
    }
    try{
      $ns = $this->getDefinedValue('SESSION_MANAGER');
      $this->sessionManager = new $ns();
    } catch (Exception $e){
      throw new DebugException('SESSION MANAGER NOT FOUND, please check in constants.php SESSION_MANAGER variable', 1);
    }
    return $this->sessionManager;
  }

  /**
   * static function that return imusic.am
   * fileutils if defined by user it return it if not
   * return imusic.am default fileutils
   *
   * @return \ngs\routes\NgsRoutes
   * @throws DebugException if ROUTES_ENGINE Not found
   *
   */
  public function getRoutesEngine() {
    if ($this->routesEngine !== null){
      return $this->routesEngine;
    }
    try{
      $ns = $this->getDefinedValue('ROUTES_ENGINE');
      $this->routesEngine = new $ns();
    } catch (Exception $e){
      throw new DebugException('ROUTES ENGINE NOT FOUND, please check in constants.php ROUTES_ENGINE variable', 1);
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
   * @param bool $force
   * @return NgsModuleRoutes
   * @throws DebugException if MODULES_ROUTES_ENGINE Not found
   */
  public function getModulesRoutesEngine(bool $force = false): NgsModuleRoutes {
    if ($this->moduleRoutesEngine !== null && $force === false){
      return $this->moduleRoutesEngine;
    }
    try{
      $ns = $this->getDefinedValue('MODULES_ROUTES_ENGINE');
      $this->moduleRoutesEngine = new $ns();
    } catch (Exception $e){
      throw new DebugException('ROUTES ENGINE NOT FOUND, please check in constants.php ROUTES_ENGINE variable', 1);
    }
    return $this->moduleRoutesEngine;
  }

  /**
   * static function that return imusic.am
   * loadmapper if defined by user it return it if not
   * return imusic.am default loadmapper
   *
   * @return \ngs\routes\NgsLoadMapper
   * @throws DebugException if MAPPER Not found
   *
   */
  public function getLoadMapper() {
    if ($this->loadMapper != null){
      return $this->loadMapper;
    }
    try{
      $ns = $this->getDefinedValue('LOAD_MAPPER');
      $this->loadMapper = new $ns;
    } catch (Exception $e){
      throw new DebugException('LOAD MAPPER NOT FOUND, please check in constants.php LOAD_MAPPER variable', 1);
    }
    return $this->loadMapper;
  }

  /**
   * static function that return imusic.am
   * loadmapper if defined by user it return it if not
   * return imusic.am default loadmapper
   *
   * @return \ngs\templater\NgsTemplater
   * @throws DebugException if TEMPLATE_ENGINE Not found
   *
   */
  public function getTemplateEngine() {
    if ($this->tplEngine != null){
      return $this->tplEngine;
    }
    try{
      $ns = $this->getDefinedValue('TEMPLATE_ENGINE');
      $this->tplEngine = new $ns;
    } catch (Exception $e){
      throw new DebugException('TEMPLATE ENGINE NOT FOUND, please check in constants.php TEMPLATE_ENGINE variable', 1);
    }
    return $this->tplEngine;
  }

  /**
   * static function that return ngs
   * fileutils if defined by user it return it if not
   * return ngs default fileutils
   *
   * @return \ngs\util\NgsUtils
   * @throws DebugException if NGS_UTILS Not found
   *
   */
  public function getNgsUtils() {
    if ($this->ngsUtils != null){
      return $this->ngsUtils;
    }
    try{
      $ns = '\\' . $this->getDefinedValue('NGS_UTILS');
      $this->ngsUtils = new $ns;
    } catch (Exception $e){
      throw new DebugException('NGS UTILS NOT FOUND, please check in constants.php NGS_UTILS variable');
    }
    return $this->ngsUtils;
  }

  /**
   * static function that return ngs
   * fileutils if defined by user it return it if not
   * return ngs default fileutils
   *
   * @return \ngs\util\FileUtils
   * @throws DebugException if FILE_UTILS Not found
   *
   */
  public function getFileUtils() {
    if ($this->fileUtils != null){
      return $this->fileUtils;
    }
    try{
      $ns = '\\' . $this->getDefinedValue('FILE_UTILS');
      $this->fileUtils = new $ns;
    } catch (Exception $e){
      throw new DebugException('FILE UTILS NOT FOUND, please check in constants.php FILE_UTILS variable');
    }
    return $this->fileUtils;
  }

  /**
   * static function that return ngs
   * fileutils if defined by user it return it if not
   * return ngs default fileutils
   *
   * @return HttpUtils
   * @throws DebugException if HTTP_UTILS Not found
   *
   */
  public function getHttpUtils(): HttpUtils {
    if ($this->httpUtils !== null){
      return $this->httpUtils;
    }
    try{
      $ns = $this->getDefinedValue('HTTP_UTILS');
      $this->httpUtils = new $ns;
    } catch (Exception $e){
      throw new DebugException('HTTP UTILS NOT FOUND, please check in constants.php HTTP_UTILS variable');
    }
    return $this->httpUtils;
  }

  /**
   * this method return ngs or user defined jsBuilder object
   *
   * @return \ngs\util\JsBuilder
   * @throws DebugException if JS_BUILDER Not found
   *
   */
  public function getJsBuilder() {
    if ($this->jsBuilder != null){
      return $this->jsBuilder;
    }
    try{
      $classPath = $this->getDefinedValue('JS_BUILDER');
      $this->jsBuilder = new $classPath();
    } catch (Exception $e){
      throw new DebugException('JS UTILS NOT FOUND, please check in constants.php JS_BUILDER variable');
    }
    return $this->jsBuilder;
  }

  /**
   * this method return ngs or user defined cssBuilder object
   *
   * @return \ngs\util\CssBuilder
   * @throws DebugException if CSS_BUILDER Not found
   *
   */
  public function getCssBuilder() {
    if ($this->cssBuilder != null){
      return $this->cssBuilder;
    }
    try{
      $classPath = $this->getDefinedValue('CSS_BUILDER');
      $this->cssBuilder = new $classPath();
    } catch (Exception $e){
      throw new DebugException('CSS UTILS NOT FOUND, please check in constants.php CSS_BUILDER variable');
    }
    return $this->cssBuilder;
  }

  /**
   * this method return ngs or user defined lessBuilder object
   *
   * @return Object fileUtils
   * @throws DebugException if LESS_BUILDER Not found
   *
   */
  public function getLessBuilder() {
    if ($this->lessBuilder != null){
      return $this->lessBuilder;
    }
    try{
      $classPath = $this->getDefinedValue('LESS_BUILDER');
      $this->lessBuilder = new $classPath();
    } catch (Exception $e){
      throw new DebugException('LESS UTILS NOT FOUND, please check in constants.php LESS_BUILDER variable');
    }
    return $this->lessBuilder;
  }

  /**
   * this method return ngs or user defined sassBuilder object
   *
   * @return \ngs\util\SassBuilder
   * @throws DebugException if SASS_BUILDER Not found
   *
   */
  public function getSassBuilder() {
    if ($this->sassBuilder != null){
      return $this->sassBuilder;
    }
    try{
      $classPath = $this->getDefinedValue('SASS_BUILDER');
      $this->sassBuilder = new $classPath();
    } catch (Exception $e){
      throw new DebugException('SASS UTILS NOT FOUND, please check in constants.php SASS_BUILDER variable');
    }
    return $this->sassBuilder;
  }

  /**
   * @param string $fileType
   *
   * @return \ngs\util\CssBuilder|\ngs\util\FileUtils|\ngs\util\JsBuilder|\ngs\util\SassBuilder|Object
   *
   * @throws DebugException
   */
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
      case 'sass' :
        return $this->getSassBuilder();
        break;
      default :
        return $this->getFileUtils();
    }
  }

  /**
   * return project prefix
   * @return String $namespace
   */
  public function getEnvironment() {
    $definedValue = $this->getDefinedValue('ENVIRONMENT');
    switch ($definedValue){
      case 'development':
      case 'staging':
        return $definedValue;
        break;
      default:
        return 'production';
    }
  }

  /**
   * return short env prefix
   * @static
   * @access
   * @return String $env
   */
  public function getShortEnvironment() {
    $env = 'prod';
    if ($this->getEnvironment() == 'development'){
      $env = 'dev';
    } elseif ($this->getEnvironment() == 'staging'){
      return 'stage';
    }
    return $env;
  }

  /**
   * @return mixed|null
   */
  public function getVersion() {
    return $this->getDefinedValue('VERSION');
  }

  /**
   * @return mixed|null
   */
  public function getNGSVersion() {
    return $this->getDefinedValue('NGSVERSION');
  }

  /**
   * check if ngs js framework enable
   *
   * @return bool
   */
  public function isJsFrameworkEnable() {
    return $this->getDefinedValue('JS_FRAMEWORK_ENABLE');
  }

  public function getDynObject() {
    return new \ngs\util\NgsDynamic();
  }


  public function cliLog($log, $color = 'white', $bold = false) {
    $colorArr = ['black' => '0;30', 'blue' => '0;34', 'green' => '0;32', 'cyan' => '0;36',
      'red' => '0;31', 'purple' => '0;35', 'prown' => '0;33', 'light_gray' => '0;37 ',
      'gark_gray' => '1;30', 'light_blue' => '1;34', 'light_green' => '1;32', 'light_cyan' => '1;36',
      'light_red' => '1;31', 'light_purple' => '1;35', 'yellow' => '1;33', 'white' => '1;37'];
    $colorCode = $colorArr['white'];
    if ($colorArr[$color]){
      $colorCode = $colorArr[$color];
    }
    $colorCode .= '0m';
    echo '\033[' . $colorCode . $log . '  \033[' . $colorArr['white'] . '0m \n';
  }
}

/**
 * return NGS instance
 *
 * @return NGS NGS
 */
function NGS() {
  return NGS::getInstance();
}

require_once('system/NgsDefaultConstants.php');
NGS()->initialize();
