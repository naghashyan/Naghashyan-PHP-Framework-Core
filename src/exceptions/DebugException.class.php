<?php
/**
 * DebugException exceptions class extends from Exception
 * handle ngs debug errors
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
  class DebugException extends \Exception {

    /**
     * Return a thingie based on $paramie
     * @abstract
     * @access
     * @param boolean $paramie
     * @return integer|babyclass
     */
    public function __construct($msg, $code=1, $params=array()) {
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
