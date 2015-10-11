<?php
/**
 * NgsErrorException exceptions class extends from Exception
 * handle ngs errors
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @mail levon@naghashyan.com
 * @package framework.exceptions
 * @version 2.0.0
 * @year 2010-2014
 */
namespace ngs\framework\exceptions {
	class NgsErrorException extends \Exception {

		public function __construct($msg="", $code=-1, $params=array()) {
		  if(!NGS()->getHttpUtils()->isAjaxRequest()){
		   throw  NGS()->getNotFoundException();
		  }
			NGS()->getTemplateEngine()->assignJson("status", "error");
			NGS()->getTemplateEngine()->assignJson("code", $code);
			NGS()->getTemplateEngine()->assignJson("msg", $msg);
      NGS()->getTemplateEngine()->assignJson("params", $params);
			NGS()->getTemplateEngine()->display();exit;
		}
	}
}