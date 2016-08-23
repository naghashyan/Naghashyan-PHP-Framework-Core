<?php
/**
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
  class ClientException extends \Exception {

    private $errorParams;

    /**
     * Return a thingie based on $paramie
     * @abstract
     * @access
     * @param boolean $paramie
     * @return integer|babyclass
     */

    public function __construct() {
      $errorParams = array();
      $argv = func_get_args();
      switch (func_num_args()){
        default :
        case 1 :
          self::__construct1($argv[0]);
          break;
        case 3 :
          self::__construct2($argv[0], $argv[1], $argv[2]);
          break;
      }
    }

    public function __construct1($message) {
      parent::__construct($message, 1);
      $autoCounter = -1;
      $this->addErrorParam($autoCounter, $autoCounter, $message);
    }

    public function __construct2($id, $code, $message) {
      parent::__construct($message, $code);
      $this->addErrorParam($id, $code, $message);
    }

    public function addErrorParam($id, $code, $message) {
      $this->errorParams[$id] = array("code" => $code, "message" => $message);
    }

    public function getErrorParams() {
      return $this->errorParams;
    }

  }

}