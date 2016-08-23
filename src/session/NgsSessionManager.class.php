<?php
/**
 * default ngs SessionManager class
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @year 2009-2015
 * @package framework.session
 * @version 2.1.0
 * 
 * This file is part of the NGS package.
 *
 * @copyright Naghashyan Solutions LLC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */
namespace ngs\framework\session {
  class NgsSessionManager extends \ngs\framework\session\AbstractSessionManager {

    private $requestSessionHeadersArr = array();
    


    /**
     * set user info into cookie and session
     *
     * @param mixed user Object| $user
     * @param bool $remember | true
     * @param bool $useDomain | true
     *
     * @return
     */
    public function setUser($user, $remember = false, $useDomain = true, $useSubdomain = false) {
      $sessionTimeout = $remember ? 2078842581 : null;
      $domain = false;
      if ($useDomain) {

        if ($useSubdomain) {
          $domain = ".".NGS()->getHttpUtils()->getHost();
        } else {
          $domain = NGS()->getHttpUtils()->getHost();
        }
      }
      $cookieParams = $user->getCookieParams();
      foreach ($cookieParams as $key => $value) {
        setcookie($key, $value, $sessionTimeout, "/", false);
      }
      $sessionParams = $user->getSessionParams();
      foreach ($sessionParams as $key => $value) {
        $_SESSION[$key] = $value;
      }
    }

    /**
     * delete user from cookie and session
     *
     * @param mixed user Object| $user
     * @param bool $useDomain | true
     *
     * @return
     */
    public function deleteUser($user, $useDomain = true, $useSubdomain = false) {
      $sessionTimeout = time() - 42000;
      $domain = false;
      if ($useDomain) {
        if ($useSubdomain) {
          $domain = ".".HTTP_HOST;
        } else {
          $domain = ".".NGS()->getHost();
        }
      }
      $cookieParams = $user->getCookieParams();
      foreach ($cookieParams as $key => $value) {
        setcookie($key, '', $sessionTimeout, "/", $domain);
      }
      $sessionParams = $user->getSessionParams();
      foreach ($sessionParams as $key => $value) {
        if (isset($_SESSION[$key])) {
          unset($_SESSION[$key]);
        }
      }
    }

    /**
     * Update user hash code
     *
     * @param mixed user Object| $user
     *
     * @return
     */
    public function updateUserUniqueId($user, $useSubdomain = false) {
      $domain = NGS()->getHttpUtils()->getHttpHost();
      if ($useSubdomain) {
        $domain = ".".$domain;
      }
      $cookieParams = $user->getCookieParams();
      setcookie("uh", $cookieParams["uh"], null, "/", $domain);
    }

    /**
     * this method for delete user from cookies,
     * Children of the NgsSessionManager class should override this method
     *
     * @abstract
     * @param load|action Object $request
     * @param Object $user | null
     * @return boolean
     */
    public function validateRequest($request, $user = null) {
      return null;
    }
    
    
    public function setSessionParam($key, $value) {
      if (session_status() == PHP_SESSION_NONE) {
        session_start();
        $_SESSION["ngs"] = array();
      }
      $_SESSION["ngs"][$key] = $value;
    }
    
    public function getSessionParam($key) {
      if (session_status() == PHP_SESSION_NONE) {
        session_start();
      }
      if(isset($_SESSION["ngs"]) && is_array($_SESSION["ngs"])){
        if(isset($_SESSION["ngs"][$key])){
          return $_SESSION["ngs"][$key];
        }
      }
      return null;
    }

    public function setNoCache() {
      header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
      header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
      header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
      header("Cache-Control: post-check=0, pre-check=0", false);
      header("Pragma: no-cache");
    }

    public function setRequestHeader($name, $value = "") {
      $this->requestSessionHeadersArr[$name] = $value;
    }

    public function getRequestHeader() {
      return $this->requestSessionHeadersArr;
    }

  }

}
