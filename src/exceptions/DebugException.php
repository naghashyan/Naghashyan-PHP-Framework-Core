<?php
/**
 * DebugException exceptions class extends from Exception
 * handle ngs debug errors
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @year 2009-2018
 * @package framework
 * @version 3.6.0
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
  class DebugException extends \Exception {

    /**
     * Return a thingie based on $paramie
     * @abstract
     * @access
     * @param boolean $paramie
     * @return integer|babyclass
     */

    private $params = [];
    protected $code = 1;

    public function __construct($msg = "", $code = 1, $params = []) {
      $this->message = $msg;
      $this->params = $params;
      $this->code = $code;
    }

    public function display() {
      $trace = $this->getTrace();
      header('Content-Type: application/json; charset=utf-8');
      header("HTTP/1.0 403 Forbidden");
      $debugArr = [
        "NGSDEBUGMSG" => $this->getMessage(),
        "code" => $this->code,
        "params" => $this->params,
        "file" => $trace[0]["file"],
        "line" => $trace[0]["line"],
        "_tmst_" => time()
      ];
      echo json_encode($debugArr);
    }

  }

}
