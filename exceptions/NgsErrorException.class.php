<?php
/**
 * NgsErrorException exceptions class extends from Exception
 * handle ngs errors
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @year 2009-2016
 * @package framework
 * @version 2.2.0
 *
 * This file is part of the NGS package.
 *
 * @copyright Naghashyan Solutions LLC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */
namespace ngs\framework\exceptions {
  class NgsErrorException extends \Exception {

    public function __construct($msg = "", $code = -1, $params = array()) {
      if (!NGS()->getHttpUtils()->isAjaxRequest()){
        //throw  NGS()->getNotFoundException();
      }
      NGS()->getTemplateEngine()->setHttpStatusCode(400);
      NGS()->getTemplateEngine()->assignJson("status", "error");
      NGS()->getTemplateEngine()->assignJson("code", $code);
      NGS()->getTemplateEngine()->assignJson("msg", $msg);
      NGS()->getTemplateEngine()->assignJson("params", $params);
      NGS()->getTemplateEngine()->display();
      exit;
    }
  }
}