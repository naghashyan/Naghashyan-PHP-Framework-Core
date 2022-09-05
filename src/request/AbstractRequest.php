<?php
/**
 * parent class for all ngs requests (loads/action)
 *
 * @author Zaven Naghashyan <zaven@naghashyan.com>, Levon Naghashyan <levon@naghashyan.com>
 * @site https://naghashyan.com
 * @year 2009-2022
 * @version 4.2.0
 * @package ngs.framework
 *
 * This file is part of the NGS package.
 *
 * @copyright Naghashyan Solutions LLC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace ngs\request;

use ngs\exceptions\NoAccessException;
use ngs\util\NgsArgs;
use ngs\util\Pusher;

abstract class AbstractRequest
{

    protected $requestGroup;
    protected array $params = [];
    protected int $ngsStatusCode = 200;
    private array $ngsPushParams = ['link' => [], 'script' => [], 'img' => []];
    private ?NgsArgs $ngsArgs = null;
    private ?string $ngsRequestUIID = null;

    abstract public function initialize(): void;

    /**
     * default http status code
     * for OK response
     *
     *
     * @return integer 200
     */
    public function setStatusCode(int $statusCode): void
    {
        $this->ngsStatusCode = $statusCode;
    }

    /**
     * default http status code
     * for OK response
     *
     *
     * @return integer 200
     */
    public function getStatusCode(): int
    {
        return $this->ngsStatusCode;
    }

    /**
     * default http status code
     * for ERROR response
     *
     *
     * @return integer 403
     */
    public function getErrorStatusCode(): int
    {
        return 403;
    }

    public function setRequestGroup($requestGroup)
    {
        $this->requestGroup = $requestGroup;
    }


    public function getRequestGroup()
    {
        return $this->requestGroup;
    }

    /**
     * @throws NoAccessException
     * @throws \ngs\exceptions\DebugException
     */
    public function redirectToLoad(string $load, array $args, int $statusCode = 200): void
    {
        $this->setStatusCode($statusCode);
        if (isset($args)) {
            NgsArgs::getInstance()->setArgs($args);
        }
        $actionArr = NGS()->getRoutesEngine()->getLoadORActionByAction($load);
        NGS()->getDispatcher()->loadPage($actionArr['action']);
    }

    /**
     * add multiple parameters
     *
     * @access public
     * @param array $paramsArr
     *
     * @return void
     */
    public final function addParams($paramsArr)
    {
        if (!is_array($paramsArr)) {
            $paramsArr = [$paramsArr];
        }
        $this->params = array_merge($this->params, $paramsArr);
    }

    /**
     * add single parameter
     *
     * @access public
     *
     * @param String $name
     * @param mixed $value
     *
     * @return void
     */
    protected final function addParam(string $name, mixed $value)
    {
        $this->params[$name] = $value;
    }

    /**
     * this method return
     * assigned parameters
     *
     * @access public
     *
     * @return array
     *
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * do cancel load or actions
     *
     * @access public
     *
     * @throw NoAccessException
     */
    protected function cancel(): void
    {
    }


    protected abstract function onNoAccess(): void;

    // public abstract function getValidator(): void;

    protected function getValidator(): void
    {
        // TODO: Implement getValidator() method.
    }

    /**
     * public method helper method for do http redirect
     *
     * @access public
     *
     * @param string $url
     *
     * @return void
     * @throws \ngs\exceptions\DebugException
     */
    protected function redirect(string $url): void
    {
        NGS()->getHttpUtils()->redirect($url);
    }

    /**
     * set http2 push params
     * it will add in response header
     * suppored types img, script and link
     *
     * @param string $type
     * @param string $value
     * @return bool
     */
    protected function setHttpPushParam(string $type, string $value): bool
    {
        if (isset($this->ngsPushParams[$type])) {
            $this->ngsPushParams[$type] = $value;
            return true;
        }
        return false;
    }

    /**
     * set http2 push params
     * it will add in response header
     * suppored types img, script and link
     *
     * @return void
     */
    protected function insertHttpPushParams(): void
    {
        foreach ($this->ngsPushParams['script'] as $script) {
            Pusher::getInstance()->src($script);
        }
        foreach ($this->ngsPushParams['link'] as $link) {
            Pusher::getInstance()->link($link);
        }
        foreach ($this->ngsPushParams['img'] as $img) {
            Pusher::getInstance()->img($img);
        }
    }

    protected function getNgsRequestUUID(): string
    {
        if (!$this->ngsRequestUIID) {
            $this->ngsRequestUIID = uniqid('ngs_', true);
        }
        return $this->ngsRequestUIID;
    }

    final public function args(): NgsArgs
    {
        return NgsArgs::getInstance($this->getNgsRequestUUID());
    }

    protected abstract function afterRequest();


}
