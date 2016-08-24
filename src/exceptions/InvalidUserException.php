<?php
namespace ngs\exceptions {
  class InvalidUserException extends \Exception {

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
      NGS()->getTemplateEngine()->setHttpStatusCode(401);
      NGS()->getTemplateEngine()->assignJson("msg", $msg);
      if ($redirectTo != ""){
        NGS()->getTemplateEngine()->assignJson("redirect_to", $redirectTo);
      }
      if ($redirectToLoad != ""){
        NGS()->getTemplateEngine()->assignJson("redirect_to_load", $redirectToLoad);
      }
      NGS()->getTemplateEngine()->display();
    }

  }

}
