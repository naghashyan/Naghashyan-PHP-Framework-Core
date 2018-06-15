<?php

namespace ngs\exceptions {
  class InvalidUserException extends \Exception {

    protected $httpCode = 401;
    private $redirectTo = "";
    private $redirectToLoad = "";

    public function __construct($msg = "access denied", $code = -10) {
      parent::__construct($msg, $code);
    }

    /**
     * @return int
     */
    public function getHttpCode(): int {
      return $this->httpCode;
    }

    /**
     * @param int $httpCode
     */
    public function setHttpCode(int $httpCode): void {
      $this->httpCode = $httpCode;
    }

    /**
     * @return string
     */
    public function getRedirectTo(): string {
      return $this->redirectTo;
    }

    /**
     * @param string $redirectTo
     */
    public function setRedirectTo(string $redirectTo): void {
      $this->redirectTo = $redirectTo;
    }

    /**
     * @return string
     */
    public function getRedirectToLoad(): string {
      return $this->redirectToLoad;
    }

    /**
     * @param string $redirectToLoad
     */
    public function setRedirectToLoad(string $redirectToLoad): void {
      $this->redirectToLoad = $redirectToLoad;
    }


  }

}
