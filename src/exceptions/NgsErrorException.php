<?php
/**
 * NgsErrorException exceptions class extends from Exception
 * handle ngs errors
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @year 2009-2016
 * @package framework
 * @version 3.1.0
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
  class NgsErrorException extends \Exception {

    private $httpCode = 400;
    private $params = [];

    public function __construct($msg = "", $code = -1, $params = []) {
      parent::__construct($msg, $code);
      $this->setParams($params);
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
     * @return array
     */
    public function getParams(): array {
      return $this->params;
    }

    /**
     * @param array $params
     */
    public function setParams(array $params): void {
      $this->params = $params;
    }

  }
}