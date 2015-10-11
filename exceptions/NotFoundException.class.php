<?php
namespace ngs\framework\exceptions {
  class NotFoundException extends \Exception {

    /**
     * Return a thingie based on $paramie
     * @abstract
     * @access
     * @param boolean $paramie
     * @return integer|babyclass
     */
    public function __construct() {
      header("HTTP/1.0 404 Not Found");
      echo "404 page :)";
      exit;
    }

  }

}
