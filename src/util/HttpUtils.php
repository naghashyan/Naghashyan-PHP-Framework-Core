<?php

/**
 * Helper wrapper class for php curl
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @year 2014-2016
 * @package ngs.framework.util
 * @version 3.1.0
 *
 * This file is part of the NGS package.
 *
 * @copyright Naghashyan Solutions LLC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace ngs\util {

  class HttpUtils {
    /**
     * detect if request call from ajax or not
     * @static
     * @access
     * @return bool|true|false
     */
    public function isAjaxRequest() {
      if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest"){
        return true;
      }
      return false;
    }

    public function getRequestProtocol() {
      if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") || $_SERVER['SERVER_PORT'] == 443){
        $protocol = "https:";
      } else{
        $protocol = "http:";
      }

      return $protocol;
    }

    public function getHost($main = false) {
      $httpHost = $this->_getHttpHost($main);
      if ($httpHost == null){
        return null;
      }
      $array = explode(".", $httpHost);
      return (array_key_exists(count($array) - 2, $array) ? $array[count($array) - 2] : "") . "." . $array[count($array) - 1];
    }

    public function getHttpHost($withPath = false, $withProtacol = false, $main = false) {
      $httpHost = $this->_getHttpHost($main);
      if ($httpHost == null){
        return null;
      }
      if ($withPath){
        $httpHost = "//" . $httpHost;
        if ($withProtacol){
          $httpHost = $this->getRequestProtocol() . $httpHost;
        }
      }
      return $httpHost;
    }

    public function getHttpHostByNs($ns = "", $withProtocol = false) {
      $httpHost = $this->getHttpHost(true, $withProtocol);
      if (NGS()->getModulesRoutesEngine()->getModuleType() == "path"){
        if ($ns == ""){
          return $httpHost;
        }
        if (NGS()->getModulesRoutesEngine()->isDefaultModule($ns)){

        }

        return $httpHost . "/" . NGS()->getModulesRoutesEngine()->getModuleUri();
      }
      if ($ns == ""){
        return $httpHost;
      }
      return $this->getHttpHost(true, $withProtocol) . "/" . $ns;
    }

    public function getNgsStaticPath($ns = "", $withProtocol = false) {
      $httpHost = $this->getHttpHost(true, $withProtocol);
      if (NGS()->getModulesRoutesEngine()->getModuleType() == "path"){
        if ($ns == "" || NGS()->getModulesRoutesEngine()->isCurrentModule($ns)){
          return $httpHost . "/" . NGS()->getModulesRoutesEngine()->getModuleUri();
        }
      }
      if ($ns == ""){
        if (NGS()->getModulesRoutesEngine()->isDefaultModule()){
          return $httpHost;
        }
        $ns = NGS()->getModulesRoutesEngine()->getModuleNS();
      }
      return $this->getHttpHost(true, $withProtocol) . "/" . $ns;
    }

    public function getRequestUri($full = false) {
      $uri = isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : "";
      if (strpos($uri, "?") !== false){
        $uri = substr($uri, 0, strpos($uri, "?"));
      }
      if ($full === false && NGS()->getModulesRoutesEngine()->getModuleType() == "path"){
        $delim = "";
        if (strpos($uri, NGS()->getModulesRoutesEngine()->getModuleUri() . "/") !== false){
          $delim = "/";
        }
        $uri = str_replace(NGS()->getModulesRoutesEngine()->getModuleUri() . $delim, "", $uri);
      }
      return $uri;
    }

    /**
     * Return a thingie based on $paramie
     * @abstract
     * @access
     * @param boolean $paramie
     * @return integer|babyclass
     */
    public function redirect($url, $module = "") {
      header("location: " . $this->getHttpHostByNs($module, true) . "/" . $url);
    }

    public function getMainDomain() {
      $pieces = parse_url($this->_getHttpHost(true));
      $domain = isset($pieces['path']) ? $pieces['path'] : '';
      if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs)){
        return $regs['domain'];
      }
      return false;
    }

    public function _getHttpHost($main = false) {
      $ngsHost = null;
      if (NGS()->getDefinedValue("HTTP_HOST")){
        $ngsHost = NGS()->getDefinedValue("HTTP_HOST");
      } elseif (isset($_SERVER["HTTP_HOST"])){
        $ngsHost = $_SERVER["HTTP_HOST"];
      }
      return $ngsHost;
    }

    public function getSubdomain() {
      $domain = $this->_getHttpHost(true);
      $parsedUrl = parse_url($domain);
      if (!isset($parsedUrl['path'])){
        return null;
      }
      $host = explode('.', $parsedUrl['path']);
      if (count($host) >= 3){
        return $host[0];
      }
      return null;
    }

  }

}
