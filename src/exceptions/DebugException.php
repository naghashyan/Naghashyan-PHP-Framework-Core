<?php
/**
 * DebugException exceptions class extends from Exception
 * handle ngs debug errors
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @year 2009-2022
 * @package framework
 * @version 4.5.0
 *
 * This file is part of the NGS package.
 *
 * @copyright Naghashyan Solutions LLC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace ngs\exceptions;
class DebugException extends \Exception
{

    private array $params = [];

    public function __construct(string $msg = '', $code = 1, array $params = [])
    {
        $this->message = $msg;
        $this->params = $params;
        $this->code = $code;
    }

    public function display()
    {
        $trace = $this->getTrace();
        header('Content-Type: application/json; charset=utf-8');
        header('HTTP/1.0 403 Forbidden');
        $debugArr = [
            'NGSDEBUGMSG' => $this->getMessage(),
            'code' => $this->code,
            'params' => $this->params,
            '_tmst_' => time(),
            'trace' => $trace

        ];
        echo json_encode($debugArr);
    }

}
