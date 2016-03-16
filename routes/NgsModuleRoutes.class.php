<?php
/**
 * default ngs modules routing class
 * this class by default used from dispacher
 * for matching url with modules routes
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @year 2015
 * @package ngs.framework.routes
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
namespace ngs\framework\routes {
  class NgsModuleRoutes {

    private $routes = array();
    private $shuffledRoutes = array();
    private $defaultNS = null;
    private $moduleArr = null;
    private $package = null;
    private $nestedRoutes = null;
    private $jsonParams = array();
    private $contentLoad = null;
    private $dynContainer = "dyn";
    private $type = null;
    private $dir = null;
    private $ns = null;
    private $uri = null;

    public function __construct() {
      $moduleArr = $this->getModule();
      if ($moduleArr == null){
        //TODO debug excaptoin
        return;
      }
      $this->setModuleNS($moduleArr["ns"]);
      $this->setModuleUri($moduleArr["uri"]);
      $this->setModuleType($moduleArr["type"]);
    }

    public function initialize() {
      return true;
    }

    /**
     * return url dynamic part
     * this method can be overrided from other users
     * if they don't want to use "dyn" container
     * but on that way maybe cause conflicts with routs
     *
     * @return String
     */
    protected function getDynContainer() {
      return $this->dynContainer;
    }

    /**
     * read from file json routes
     * and set in private property for cache
     *
     * @return json Array
     */
    private function getRouteConfig() {
      if (count($this->routes) == 0){
        $moduleRouteDile = realpath($this->getRootDir("ngs")."/".NGS()->getDefinedValue("CONF_DIR")."/".NGS()->getDefinedValue("NGS_MODULS_ROUTS"));
        $this->routes = json_decode(file_get_contents($moduleRouteDile), true);
      }
      return $this->routes;
    }

    /**
     * get shuffled routes json
     * key=>dir value=namespace
     * and set in private shuffledRoutes property for cache
     *
     * @return json Array
     */
    public function getShuffledRoutes() {
      if (count($this->shuffledRoutes) > 0){
        return $this->shuffledRoutes;
      }
      $routes = $this->getRouteConfig();
      $this->shuffledRoutes = array();
      foreach ($routes as $domain => $route){
        foreach ($route as $type => $routeItem){
          if($type == "default"){
            $this->shuffledRoutes[$routeItem["dir"]] = array("path" => $routeItem["dir"], "type" => $type, "domain" => $domain);
            continue;
          }
          foreach ($routeItem as $item){
            if (isset($item["dir"])){
              $this->shuffledRoutes[$item["dir"]] = array("path" => $item["dir"], "type" => $type, "domain" => $domain);
            } elseif (isset($value["extend"])){
              $this->shuffledRoutes[$item["extend"]] = array("path" => $item["extend"], "type" => $type, "domain" => $domain);
            }
          }
        }
      }
      return $this->shuffledRoutes;
    }

    public function getDefaultNS() {
      if ($this->defaultNS != null){
        return $this->defaultNS;
      }
      $routes = $this->getRouteConfig();
      if (isset($routes["default"]["default"])){
        $defaultModule = $routes["default"]["default"];
        $defaultMatched = $this->getMatchedModule($defaultModule, "", "default");
        $this->defaultNS = $defaultMatched["ns"];
      } else{
        $this->defaultNS = NGS()->getDefinedValue("DEFAULT_NS");
      }
      return $this->defaultNS;
    }

    /**
     * check module by name
     *
     *
     * @param String $name
     *
     * @return true|false
     */
    public function checkModuleByUri($name) {
      $routes = $this->getRouteConfig();
      if (isset($routes["subdomain"][$name])){
        return true;
      } elseif (isset($routes["domain"][$name])){
        return true;
      } elseif (isset($routes["path"][$name])){
        return true;
      } elseif ($name == $this->getDefaultNS()){
        return true;
      }
      return false;
    }

    /**
     * check module by name
     *
     *
     * @param String $name
     *
     * @return true|false
     */
    public function checkModuleByNS($ns) {
      $routes = $this->getShuffledRoutes();
      if (isset($routes[$ns])){
        return true;
      }
      return false;
    }

    /**
     * this method return pakcage and command from url
     * check url if set dynamic container return manage using standart routing
     * if not manage url using routes file if matched succsess return array if not false
     * this method can be overrided from users for they custom routing scenarios
     *
     * @param String $url
     *
     * @return array|false
     */
    protected function getModule() {
      if ($this->moduleArr != null){
        //FIXME
        //return $this->moduleArr;
      }
      $module = $this->getDefaultNS();
      $domain = NGS()->getHttpUtils()->_getHttpHost(true);
      $parsedUrl = parse_url($domain);
      $mainDomain = NGS()->getHttpUtils()->getMainDomain();
      $modulePart = $this->getModulePartByDomain($mainDomain);
      $host = explode('.', $parsedUrl['path']);
      $subdomain = null;
      if (count($host) >= 3){
        if ($this->moduleArr = $this->getModuleBySubDomain($modulePart, $host[0])){
          return $this->moduleArr;
        }
      }
      $uri = NGS()->getHttpUtils()->getRequestUri(true);
      if ($this->moduleArr = $this->getModuleByURI($modulePart, $uri)){
        return $this->moduleArr;
      }
      $this->moduleArr = $this->getMatchedModule($modulePart["default"], $uri, "default");
      return $this->moduleArr;
    }

    private function getModulePartByDomain($domain) {
      $routes = $this->getRouteConfig();
      if (isset($routes[$domain])){
        return $routes[$domain];
      }
      if (isset($routes["default"])){
        return $routes["default"];
      }
      throw NGS()->getDebugException("PLEASE ADD DEFAULT SECTION IN module.json");
    }

    /**
     * return module by domain
     *
     * @param String $domain
     *
     * @return string
     */
    private function getModuleByDomain($domain) {
      $routes = $this->getRouteConfig();
      if (isset($routes["domain"][$domain])){
        return $this->getMatchedModule($routes["domain"][$domain], $domain, "domain");
      }
      return null;
    }

    /**
     * return module by subdomain
     *
     * @param String $domain
     *
     * @return string
     */
    private function getModuleBySubDomain($modulePart, $domain) {
      $routes = $modulePart;
      if (isset($routes["subdomain"][$domain])){
        return $this->getMatchedModule($routes["subdomain"][$domain], $domain, "subdomain");
      }
      return null;
    }

    /**
     * return module by uri
     *
     * @param String $domain
     *
     * @return string
     */
    private function getModuleByURI($modulePart, $uri) {
      $matches = array();
      preg_match_all("/(\/([^\/\?]+))/", $uri, $matches);

      if (is_array($matches[2]) && isset($matches[2][0])){
        if ($matches[2][0] == $this->getDynContainer()){
          array_shift($matches[2]);
        }
        if (isset($modulePart["path"][$matches[2][0]])){
          return $this->getMatchedModule($modulePart["path"][$matches[2][0]], $matches[2][0], "path");
        } else if ($matches[2][0] == $this->getDefaultNS()){
          return array("ns" => $this->getDefaultNS(), "uri" => NGS()->getDefaultNS(), "type" => "path");
        }
      }

      return null;
    }

    protected function getMatchedModule($matchedArr, $uri, $type) {
      $ns = null;
      $module = null;
      $extended = false;
      if (isset($matchedArr["dir"])){
        $ns = $matchedArr["dir"];
      } elseif (isset($matchedArr["namespace"])){
        $ns = $matchedArr["namespace"];
      } elseif (isset($matchedArr["extend"])){
        $ns = $matchedArr["extend"];
      } else{
        throw NGS()->getDebugException("PLEASE ADD DIR OR NAMESPACE SECTION IN module.json");
      }
      /*TODO add global extend
       if (isset($matchedArr["extend"])) {
       $ns = $matchedArr["extend"];
       $module = $matchedArr["module"];
       $extended = true;
       }*/
      return array("ns" => $ns, "uri" => $uri, "type" => $type);
    }

    //Module interface implementation
    /**
     * set module type if is domain or subdomain or path
     *
     * @param String $type
     *
     * @return void
     */
    private function setModuleType($type) {
      $this->type = $type;
    }

    /**
     * return defined module type
     *
     * @return String
     */
    public function getModuleType() {
      return $this->type;
    }

    /**
     * set module namespace if is domain or subdomain or path
     *
     * @param String $ns
     *
     * @return void
     */
    private function setModuleNS($ns) {
      $this->ns = $ns;
    }

    /**
     * return current namespace
     *
     * @return String
     */
    public function getModuleNS() {
      return $this->ns;
    }

    /**
     * return module dir connedted with namespace
     *
     * @return String
     */
    public function getModuleNsByUri($uri) {
      $routes = $this->getRouteConfig();
      if (isset($routes["subdomain"][$uri])){
        return $routes["subdomain"][$uri];
      } elseif (isset($routes["domain"][$uri])){
        return $routes["domain"][$uri];
      } elseif (isset($routes["path"][$uri])){
        return $routes["path"][$uri];
      }
      return null;
    }

    //module function for working with modules urls
    public function setModuleUri($uri) {
      $this->uri = $uri;
    }

    public function getModuleUri() {
      return $this->uri;
    }

    public function getModuleUriByNS($ns) {
      $routes = $this->getShuffledRoutes();
      if (isset($routes["subdomain"][$ns])){
        return $routes["subdomain"][$ns];
      } elseif (isset($routes["domain"][$ns])){
        return $routes["domain"][$ns];
      } elseif (isset($routes["path"][$ns])){
        return $routes["path"][$ns];
      }
      return null;
    }

    /**
     * detect if current module is default module
     *
     * @return Boolean
     */
    public function isDefaultModule() {
      if ($this->getModuleNS() == $this->getDefaultNS()){
        return true;
      }
      return false;
    }

    /**
     * detect if $ns is current module
     *
     * @return Boolean
     */
    public function isCurrentModule($ns) {
      if ($this->getModuleNS() == $ns){
        return true;
      }
      return false;
    }

    /**
     * this method calculate dir conencted with module
     *
     * @return String rootDir
     */
    public function getRootDir($ns = "") {

      if ($ns == NGS()->getDefinedValue("FRAMEWORK_NS") || ($ns == "" && $this->getDefaultNS() == $this->getModuleNS()) || $this->getDefaultNS() == $ns){
        return NGS()->getDefinedValue("NGS_ROOT");
      }
      if ($ns == ""){
        return realpath(NGS()->getDefinedValue("NGS_ROOT")."/".NGS()->getDefinedValue("MODULES_DIR")."/".$this->getModuleNS());
      }
      return realpath(NGS()->getDefinedValue("NGS_ROOT")."/".NGS()->getDefinedValue("MODULES_DIR")."/".$ns);

    }

  }

}