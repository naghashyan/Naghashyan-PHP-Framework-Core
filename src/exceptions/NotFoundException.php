<?php
/**
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @year 2009-2016
 * @package framework
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
namespace ngs\exceptions {
  class NotFoundException extends \Exception {

    private $url = "";
    private $action = "";

    /**
     * @param mixed $url
     */
    public function setRedirectUrl($url) {
      $this->url = $url;
    }

    public function getRedirectUrl(){
      return $this->url;
    }

    /**
     * @param mixed $action
     */
    public function setRedirectAction($action) {
      $this->action = $action;
    }

    public function getRedirectAction(){
      return $this->action;
    }
  }

}
