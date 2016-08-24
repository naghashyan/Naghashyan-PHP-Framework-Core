<?php
/**
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @year 2009-2016
 * @package framework
 * @version 2.5.0
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
  class NoAccessException extends \Exception {

    public function __construct($redirectTo = "", $redirectToLoad = "") {
      NGS()->getSessionManager()->deleteUser();
      if (NGS()->getHttpUtils()->isAjaxRequest() || NGS()->getDefinedValue("display_json")){
        $this->displayJsonInvalidError("access denied", $redirectTo, $redirectToLoad);
        exit;
      }
      NGS()->getHttpUtils()->redirect($redirectTo);
      exit;
    }

    public function displayJsonInvalidError($msg, $redirectTo, $redirectToLoad) {
      NGS()->getTemplateEngine()->setHttpStatusCode(403);
      NGS()->getTemplateEngine()->assignJson("msg", $msg);
      if($redirectTo != ""){
        NGS()->getTemplateEngine()->assignJson("redirect_to", $redirectTo);
      }
      if($redirectToLoad != ""){
        NGS()->getTemplateEngine()->assignJson("redirect_to_load", $redirectToLoad);
      }
      NGS()->getTemplateEngine()->display();
    }

  }
}