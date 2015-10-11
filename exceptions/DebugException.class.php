<?php
namespace ngs\framework\exceptions {
  class DebugException extends \Exception {

    /**
     * Return a thingie based on $paramie
     * @abstract
     * @access
     * @param boolean $paramie
     * @return integer|babyclass
     */
    public function __construct($msg, $code, $params) {
      if (NGS()->getEnvironment() == "production") {
        $this->message = $msg;
        return;
      }
      if ($msg != "") {
        $this->message = $msg;
      }
      $trace = $this->getTrace();
      header('Content-Type: application/json; charset=utf-8');
      header("HTTP/1.0 403 Forbidden");
      echo json_encode(array("NGSDEBUGMSG" => $this->getMessage(), "file" => $trace[0]["file"], "line" => $trace[0]["line"], "_tmst_" => time()));
      exit ;
    }

  }

}
