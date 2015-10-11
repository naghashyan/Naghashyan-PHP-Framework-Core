<?php

/**
 * Helper wrapper class for php curl
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @year 2014-2015
 * @package ngs.framework.util
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

namespace ngs\framework\util {

  class HttpUtils {
    /**
     * detect if request call from ajax or not
     * @static
     * @access
     * @return bool|true|false
     */
    public function isAjaxRequest() {
      if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest") {
        return true;
      }
      return false;
    }
    
    public function getRequestProtocol(){
      return stripos($_SERVER['SERVER_PROTOCOL'], 'https') === true ? 'https:' : 'http:';
    }

    public function getHost($main = false) {
      $httpHost = $this->_getHttpHost($main);
      if($httpHost == null){
        return null;
      }
      $array = explode(".", $httpHost);
      return (array_key_exists(count($array) - 2, $array) ? $array[count($array) - 2] : "").".".$array[count($array) - 1];
    }

    public function getHttpHost($withPath = false, $withProtacol = false, $main = false) {
      $httpHost = $this->_getHttpHost($main);
      if($httpHost == null){
        return null;
      }
      if ($withPath) {
        $httpHost = "//".$httpHost;
        if ($withProtacol) {
          $httpHost = $this->getRequestProtocol().$httpHost;
        }
      }
      return $httpHost;

      $array = explode(".", $httpHost);
      return (array_key_exists(count($array) - 2, $array) ? $array[count($array) - 2] : "").".".$array[count($array) - 1];
    }

    public function getRequestUri($full = false) {
      $uri = isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : "";
      if (strpos($uri, "?") !== false) {
        $uri = substr($uri, 0, strpos($uri, "?"));
      }
      if($full === false && NGS()->getModulesRoutesEngine()->getModuleType() == "path"){
        $uri = str_replace(NGS()->getModulesRoutesEngine()->getModuleUri()."/", "", $uri);
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
    public function redirect($url) {
      header("location: ".$this->getHttpHost(true)."/".$url);
    }

    public function getMainDomain() {
      $pieces = parse_url($this->_getHttpHost(true));
      $domain = isset($pieces['path']) ? $pieces['path'] : '';
      if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs)) {
        return $regs['domain'];
      }
      return false;
    }

    public function _getHttpHost($main = false) {
      $ngsHost = null;
      if (NGS()->getDefinedValue("HTTP_HOST")) {
        $ngsHost = NGS()->getDefinedValue("HTTP_HOST");
      } elseif (isset($_SERVER["HTTP_HOST"])) {
        $ngsHost = $_SERVER["HTTP_HOST"];
      } 
      if($ngsHost != null && $main==false && NGS()->getModulesRoutesEngine()->getModuleType() == "path"){
        $ngsHost = $ngsHost."/".NGS()->getModulesRoutesEngine()->getModuleUri();
      }
      return $ngsHost;
    }

  }

}
