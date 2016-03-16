<?php
namespace ngs\framework\exceptions {
  class InvalidUserException extends \Exception {

    /**
     * Return a thingie based on $paramie
     * @abstract
     * @access
     * @param boolean $paramie
     * @return integer|babyclass
     */
    public function __construct($msg, $type = false) {
      if ($msg != ""){
        $this->message = $msg;
      }
    }

  }

}
