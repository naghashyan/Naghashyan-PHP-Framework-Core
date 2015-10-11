<?php
/**
 *
 * This class is a template for all authorized user classes.
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @mail levon@naghashyan.com
 * @year 2015
 * @package ngs.framework.security.users
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
namespace ngs\framework\security\users {
  abstract class NgsUser {

    private $sessionParams = array();
    private $cookieParams = array();

    /**
     * Return a thingie based on $paramie
     * @abstract
     * @access
     * @param boolean $paramie
     * @return integer|babyclass
     */
    public function __construct() {
      $this->cookieParams = $_COOKIE;
    }

    /**
     * Set unique identifier
     *
     * @param object $uniqueId
     * @return
     */
    protected function setUniqueId($uniqueId) {
      $this->setCookieParam("uh", $uniqueId);
    }

    /**
     * Set permanent identifier
     *
     * @param object $id
     * @return
     */
    protected function setId($id) {
      $this->setCookieParam("ud", $id);
    }

    /**
     * Returns unique identifier
     *
     * @return
     */
    protected function getUniqueId() {
      return $this->getCookieParam("uh");
    }

    /**
     * Returns permanent identifier
     *
     * @return
     */
    protected function getId() {
      return $this->getCookieParam("ud");
    }

    /**
     * Return a thingie based on $paramie
     * @abstract
     * @access
     * @param boolean $paramie
     * @return integer|babyclass
     */
    public function setSessionParam($name, $value) {
      $this->sessionParams[$name] = $value;
    }

    /**
     * Return a thingie based on $paramie
     * @abstract
     * @access
     * @param boolean $paramie
     * @return integer|babyclass
     */
    public function getSessionParam($name) {
      return $this->sessionParams[$name];
    }

    /**
     * Return a thingie based on $paramie
     * @abstract
     * @access
     * @param boolean $paramie
     * @return integer|babyclass
     */
    public function getSessionParams() {
      return $this->sessionParams;
    }

    /**
     * Return a thingie based on $paramie
     * @abstract
     * @access
     * @param boolean $paramie
     * @return integer|babyclass
     */
    public function setCookieParam($name, $value) {
      $this->cookieParams[$name] = $value;
    }

    /**
     * Return a thingie based on $paramie
     * @abstract
     * @access
     * @param boolean $paramie
     * @return integer|babyclass
     */
    public function getCookieParam($name) {
      if (isset($this->cookieParams[$name])) {
        return $this->cookieParams[$name];
      }
      return null;
    }

    /**
     * Return a thingie based on $paramie
     * @abstract
     * @access
     * @param boolean $paramie
     * @return integer|babyclass
     */
    public function getCookieParams() {
      return $this->cookieParams;
    }

    /**
     * Return a thingie based on $paramie
     * @abstract
     * @access
     * @param boolean $paramie
     * @return integer|babyclass
     */
    public abstract function validate();

    /**
     * Return a thingie based on $paramie
     * @abstract
     * @access
     * @param boolean $paramie
     * @return integer|babyclass
     */
    public abstract function getLevel();

  }

}
