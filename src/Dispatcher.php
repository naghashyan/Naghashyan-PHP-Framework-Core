<?php
/**
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site https://naghashyan.com
 * @year 2009-2022
 * @package framework
 * @version 4.2.0
 *
 * This file is part of the NGS package.
 *
 * @copyright Naghashyan Solutions LLC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace ngs;

use ngs\exceptions\InvalidUserException;
use ngs\exceptions\DebugException;
use ngs\exceptions\NgsErrorException;
use ngs\exceptions\NoAccessException;
use ngs\exceptions\NotFoundException;
use ngs\exceptions\RedirectException;
use ngs\util\NgsArgs;
use ngs\util\Pusher;

class Dispatcher
{

    private bool $isRedirect = false;

    /**
     * this method manage mathced routes
     *
     * @param array|null $routesArr
     *
     * @return void
     * @throws DebugException
     * @throws \JsonException
     */
    public function dispatch(?array $routesArr = null): void
    {
        try {
            if ($routesArr === null) {
                $routesArr = NGS()->getRoutesEngine()->getDynamicLoad(NGS()->getHttpUtils()->getRequestUri());
            }
            if ($routesArr['matched'] === false) {
                throw new NotFoundException('Load/Action Not found');
            }
            if (isset($routesArr['args'])) {
                NgsArgs::getInstance()->setArgs($routesArr['args']);
            }
            switch ($routesArr['type']) {
                case 'load' :
                    NGS()->getTemplateEngine();
                    if (isset($_GET['ngsValidate']) && $_GET['ngsValidate'] === 'true') {
                        $this->validate($routesArr['action']);
                    } else if (isset(NGS()->args()->args()['ngsValidate']) && NGS()->args()->args()['ngsValidate']) {
                        $this->validate($routesArr['action']);
                    } else {
                        $this->loadPage($routesArr['action']);
                    }
                    break;
                case 'api_load':
                    NGS()->getTemplateEngine();
                    $this->loadApiPage($routesArr);
                case 'action' :
                    NGS()->getTemplateEngine();
                    $this->doAction($routesArr['action']);
                    break;
                case 'api_action':
                    NGS()->getTemplateEngine();
                    $this->doApiAction($routesArr);
                    exit;
                case 'file' :
                    $this->streamStaticFile($routesArr);
                    break;
            }
        } catch (DebugException $ex) {
            if (NGS()->getEnvironment() !== 'production') {
                $ex->display();
                return;
            }
            $routesArr = NGS()->getRoutesEngine()->getNotFoundLoad();
            if ($routesArr === null || $this->isRedirect === true) {
                echo '404';
                exit;
            }
            $this->isRedirect = true;
            $this->dispatch($routesArr);
        } catch (RedirectException $ex) {
            NGS()->getHttpUtils()->redirect($ex->getRedirectTo());
        } catch (NotFoundException $ex) {
            if ($ex->getRedirectUrl() !== '') {
                NGS()->getHttpUtils()->redirect($ex->getRedirectUrl());
                return;
            }
            $routesArr = NGS()->getRoutesEngine()->getNotFoundLoad();
            if ($routesArr == null || $this->isRedirect === true) {
                echo '404';
                exit;
            }
            $this->isRedirect = true;
            $this->dispatch($routesArr);
        } catch (NgsErrorException $ex) {
            NGS()->getTemplateEngine()->setHttpStatusCode($ex->getHttpCode());
            NGS()->getTemplateEngine()->assignJson('code', $ex->getCode());
            NGS()->getTemplateEngine()->assignJson('msg', $ex->getMessage());
            NGS()->getTemplateEngine()->assignJson('params', $ex->getParams());
            NGS()->getTemplateEngine()->display(true);
        } catch (InvalidUserException $ex) {
            $this->handleInvalidUserAndNoAccessException($ex);
        } catch (NoAccessException $ex) {
            $this->handleInvalidUserAndNoAccessException($ex);
        }
    }

    /**
     * manage ngs loads
     * initialize load object
     * verify access
     * display collected output from loads
     *
     * @param string $action
     *
     * @return void
     * @throws DebugException
     * @throws NoAccessException
     */
    public function loadPage(string $action): void
    {
        try {
            $action = str_replace('-', '\\', $action);
            if (class_exists($action) === false) {
                throw new DebugException($action . ' Load Not found');
            }
            $loadObj = new $action;
            $loadObj->initialize();
            if (!$this->validateRequest($loadObj)) {
                $loadObj->onNoAccess();
            }
            $loadObj->setLoadName(NGS()->getRoutesEngine()->getContentLoad());
            $loadObj->service();
            NGS()->getTemplateEngine()->setType($loadObj->getNgsLoadType());
            NGS()->getTemplateEngine()->setTemplate($loadObj->getTemplate());
            NGS()->getTemplateEngine()->setPermalink(NGS()->getLoadMapper()->getNgsPermalink());
            if (NGS()->get('SEND_HTTP_PUSH')) {
                Pusher::getInstance()->push();
            }
            $this->displayResult();

            if (PHP_SAPI === 'fpm-fcgi') {
                session_write_close();
                fastcgi_finish_request();
            }
            $loadObj->afterRequest();
        } catch (NoAccessException $ex) {
            $loadObj->onNoAccess();
            throw $ex;
        } catch (InvalidUserException $ex) {
            $this->handleInvalidUserAndNoAccessException($ex);
        }
    }

    /**
     * load for api load
     *
     * @param array $routesArr
     * @throws DebugException
     * @throws InvalidUserException
     * @throws NoAccessException
     */
    public function loadApiPage(array $routesArr)
    {
        try {
            $action = $routesArr['action'];
            $action = str_replace('-', '\\', $action);
            if (class_exists($action) == false) {
                throw new DebugException($action . ' Load Not found');
            }
            /** @var NgsApiAction $loadObj */
            $loadObj = new $action;
            $loadObj->setAction($routesArr['action_method']);
            $loadObj->setRequestValidators($routesArr['request_params']);
            $loadObj->setResponseValidators($routesArr['response_params']);
            $loadObj->initialize();
            if (!$this->validateRequest($loadObj)) {
                $loadObj->onNoAccess();
            }
            $loadObj->setLoadName(NGS()->getRoutesEngine()->getContentLoad());
            $loadObj->service();
            NGS()->getTemplateEngine()->setType($loadObj->getNgsLoadType());
            NGS()->getTemplateEngine()->setTemplate($loadObj->getTemplate());
            NGS()->getTemplateEngine()->setPermalink(NGS()->getLoadMapper()->getNgsPermalink());
            if (NGS()->get('SEND_HTTP_PUSH')) {
                Pusher::getInstance()->push();
            }
            $this->displayResult();

            if (php_sapi_name() === 'fpm-fcgi') {
                session_write_close();
                fastcgi_finish_request();
            }
            $loadObj->afterRequest();
        } catch (NoAccessException $ex) {
            $loadObj->onNoAccess();

            throw $ex;
        } catch (InvalidUserException $ex) {
            $loadObj->onNoAccess();
            throw $ex;
        }

    }

    public function validate(string $action): void
    {
        try {
            if (class_exists($action) === false) {
                throw new DebugException($action . ' Load Not found');
            }
            $loadObj = new $action;
            $loadObj->initialize();
            if (!$this->validateRequest($loadObj)) {
                $loadObj->onNoAccess();
            }
            $loadObj->setLoadName(NGS()->getRoutesEngine()->getContentLoad());
            $loadObj->validate();
            //passing arguments
            NGS()->getTemplateEngine()->setType('json');
            NGS()->getTemplateEngine()->assignJsonParams($loadObj->getParams());
            $this->displayResult();
            if (PHP_SAPI === 'fpm-fcgi') {
                session_write_close();
                fastcgi_finish_request();
            }
            $loadObj->afterRequest();
        } catch (NoAccessException $ex) {
            $loadObj->onNoAccess();
            throw $ex;
        } catch (InvalidUserException $ex) {
            $loadObj->onNoAccess();
            throw $ex;
        }
    }

    /**
     * manage ngs action
     * initialize action object
     * verify access
     * display action output
     *
     * @param string $action
     *
     * @return void
     *
     */
    private function doAction(string $action)
    {
        try {
            $action = str_replace('-', '\\', $action);
            if (class_exists($action) === false) {
                throw new DebugException($action . ' Action Not found');
            }
            $actionObj = new $action;
            $actionObj->initialize();

            if (!$this->validateRequest($actionObj)) {
                $actionObj->onNoAccess();
            }
            $actionObj->service();
            //passing arguments
            NGS()->getTemplateEngine()->setType('json');
            NGS()->getTemplateEngine()->assignJsonParams($actionObj->getParams());
            $this->displayResult();
            if (php_sapi_name() === 'fpm-fcgi') {
                session_write_close();
                fastcgi_finish_request();
            }
            $actionObj->afterRequest();
        } catch (NoAccessException $ex) {
            $actionObj->onNoAccess();
            throw $ex;
        } catch (InvalidUserException $ex) {
            $this->handleInvalidUserAndNoAccessException($ex);
        }
    }

    /**
     * do action for api action
     *
     * @param array $routesArr
     * @throws DebugException
     * @throws InvalidUserException
     * @throws NoAccessException
     */
    private function doApiAction(array $routesArr)
    {
        try {
            $action = $routesArr['action'];
            $action = str_replace('-', '\\', $action);
            if (class_exists($action) === false) {
                throw new DebugException($action . ' Action Not found');
            }
            $actionObj = new $action;
            $actionObj->setAction($routesArr['action_method']);
            $actionObj->setRequestValidators($routesArr['request_params']);
            $actionObj->setResponseValidators($routesArr['response_params']);
            $actionObj->initialize();

            if (!$this->validateRequest($actionObj)) {
                $actionObj->onNoAccess();
            }
            $actionObj->service();
            //passing arguments
            NGS()->getTemplateEngine()->setType('json');
            NGS()->getTemplateEngine()->assignJsonParams($actionObj->getParams());
            $this->displayResult();
            if (php_sapi_name() === 'fpm-fcgi') {
                session_write_close();
                fastcgi_finish_request();
            }
            $actionObj->afterRequest();
        } catch (NoAccessException $ex) {
            $actionObj->onNoAccess();
            throw $ex;
        } catch (InvalidUserException $ex) {
            $actionObj->onNoAccess();
            throw $ex;
        }

    }

    private function streamStaticFile($fileArr)
    {
        $filePath = realpath(NGS()->getPublicDir($fileArr['module']) . '/' . $fileArr['file_url']);
        if (file_exists($filePath)) {
            $stramer = NGS()->getFileUtils();
        } else {
            $stramer = NGS()->getFileStreamerByType($fileArr['file_type']);
        }
        $stramer->streamFile($fileArr['module'], $fileArr['file_url']);
    }

    /**
     * @param $ex
     * @throws DebugException
     */
    private function handleInvalidUserAndNoAccessException($ex): void
    {

        if (!NGS()->getHttpUtils()->isAjaxRequest() && !NGS()->getDefinedValue('display_json')) {
            NGS()->getHttpUtils()->redirect($ex->getRedirectTo());
            return;
        }
        NGS()->getTemplateEngine()->setHttpStatusCode($ex->getHttpCode());
        NGS()->getTemplateEngine()->assignJson('code', $ex->getCode());
        NGS()->getTemplateEngine()->assignJson('msg', $ex->getMessage());
        if ($ex->getRedirectTo() !== '') {
            NGS()->getTemplateEngine()->assignJson('redirect_to', $ex->getRedirectTo());
        }
        if ($ex->getRedirectToLoad() !== '') {
            NGS()->getTemplateEngine()->assignJson('redirect_to_load', $ex->getRedirectToLoad());
        }
        NGS()->getTemplateEngine()->display(true);
    }

    /**
     * validate request load/action access permissions
     *
     * @param object $request
     *
     * @return boolean
     *
     */
    private function validateRequest($request)
    {

        $user = NGS()->getSessionManager()->getUser();

        if (NGS()->getSessionManager()->validateRequest($request, $user)) {
            return true;
        }
        return false;
    }

    /**
     * display collected output
     * from loads and actions
     *
     *
     * @return void
     */
    private function displayResult()
    {
        NGS()->getTemplateEngine()->display();
    }

    private function notFound($msg)
    {

    }

}
