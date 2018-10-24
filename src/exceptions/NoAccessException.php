<?php
/**
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
  class NoAccessException extends InvalidUserException {

    protected $httpCode = 403;

    public function __construct($msg = "access denied", $code = -5) {
      parent::__construct($msg, $code);
    }
  }
}