<?php
namespace ngs\framework\exceptions {
  class NoAccessException extends \Exception {

    public function __construct($msg="", $code=-1, $params=array()) {
      NGS()->getTemplateEngine()->assignJson("status", "error");
      NGS()->getTemplateEngine()->assignJson("code", $code);
      NGS()->getTemplateEngine()->assignJson("msg", $msg);
      NGS()->getTemplateEngine()->assignJson("params", $params);
      NGS()->getTemplateEngine()->display();exit;
    }
  }
}