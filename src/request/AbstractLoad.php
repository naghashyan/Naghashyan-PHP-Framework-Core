<?php
/**
 * NGS abstract load all loads should extends from this class
 * this class extends from AbstractRequest class
 * this class class content base functions that will help to
 * initialize loads
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
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

abstract class AbstractLoad extends AbstractRequest
{

    protected array $parentParams = array();
    private array $jsonParam = array();
    private string $loadName = '';
    private string $parentLoadName = '';
    private bool $isNestedLoad = false;
    private $ngsWrappingLoad = null;
    private $ngsLoadType = null;
    private array $ngsRequestParams = [];
    private string $loadClassName = '';
    private array $ngsQueryParams = [];

    /**
     * this method use for initialize
     * load and AbstractRequest initialize function
     *
     * @abstract
     * @access public
     *
     * @return void;
     */
    public function initialize()
    {
        parent::initialize();
    }

    /**
     * run load object
     *
     * @abstract
     * @access
     * @return void
     * @throws \ngs\exceptions\DebugException
     */
    final public function service(): void
    {
        $this->load();
        $this->loadClassName = get_class($this);
        $ns = $this->loadClassName;
        //initialize template engine pass load params to templater
        NGS()->getTemplateEngine()->setType($this->getNgsLoadType());
        if (!$this->isNestedLoad() && $this->getLoadName()) {
            NGS()->getLoadMapper()->setGlobalParentLoad($this->getLoadName());
        }
        $ns = get_class($this);
        $moduleNS = NGS()->getModulesRoutesEngine()->getModuleNS();
        $ns = substr($ns, strpos($ns, $moduleNS) + strlen($moduleNS) + 1);
        $ns = str_replace(['Load', '\\'], ['', '.'], $ns);
        $className = lcfirst(substr($ns, strrpos($ns, '.') + 1));
        $nameSpace = substr($ns, 0, strrpos($ns, '.'));
        $className = preg_replace_callback('/[A-Z]/', function ($m) {
            return '_' . strtolower($m[0]);
        }, $className);
        $nameSpace = str_replace('._', '.', $nameSpace);

        $nestedLoads = NGS()->getRoutesEngine()->getNestedRoutes($nameSpace . '.' . $className);
        $loadDefaultLoads = $this->getDefaultLoads();
        $defaultLoads = [];
        if (isset($loadDefaultLoads) && is_array($loadDefaultLoads)) {
            $defaultLoads = array_merge($loadDefaultLoads, $nestedLoads);
        } else if (is_array($nestedLoads)) {
            $defaultLoads = $nestedLoads;
        }
        //set nested loads for each load
        foreach ($defaultLoads as $key => $value) {
            $this->nest($key, $value);
        }
        NGS()->getLoadMapper()->setPermalink($this->getPermalink());
        NGS()->getLoadMapper()->setNgsQueryParams($this->getNgsQueryParams());
        $this->ngsInitializeTemplateEngine();
    }


    public function validate(): void
    {

    }

    /**
     * @throws \ngs\exceptions\DebugException
     */
    final public function ngsInitializeTemplateEngine(): void
    {
        if ($this->getNgsLoadType() === 'json') {
            NGS()->define('JS_FRAMEWORK_ENABLE', false);
            NGS()->getTemplateEngine()->assign('ns', $this->getParams());
        } else if ($this->getNgsLoadType() === 'smarty') {
            NGS()->getTemplateEngine()->assignJsonParams($this->getJsonParams());
            NGS()->getTemplateEngine()->assign('ns', $this->getParams());
        }
    }

    /**
     * in this method implemented
     * nested load functional
     *
     * @abstract
     * @access public
     * @param String $namespace
     * @param array $loadArr
     *
     * @return void
     * @throws NoAccessException
     * @throws \JsonException
     *
     */
    final public function nest(string $namespace, array $loadArr): void
    {

        $actionArr = NGS()->getRoutesEngine()->getLoadORActionByAction($loadArr['action']);

        $loadObj = new $actionArr['action'];
        //set that this load is nested
        $loadObj->setIsNestedLoad(true);
        $loadObj->setNgsParenLoadName($this->loadClassName);
        $loadObj->setNgsWrappingLoad($this);
        if (isset($loadArr['args'])) {
            NgsArgs::getInstance($loadObj->getNgsRequestUUID(), $loadArr['args']);
        }
        $loadObj->setLoadName($loadArr['action']);
        $loadObj->initialize();

        if (NGS()->getSessionManager()->validateRequest($loadObj) === false) {
            $loadObj->onNoAccess();
        }

        $loadObj->service();

        if (NGS()->isJsFrameworkEnable() && NGS()->getHttpUtils()->isAjaxRequest()) {
            NGS()->getLoadMapper()->setNestedLoads($this->getLoadName(), $loadArr['action'], $loadObj->getJsonParams());
        }
        if (!isset($this->params['inc'])) {
            $this->params['inc'] = array();
        }
        $this->setNestedLoadParams($namespace, $loadArr['action'], $loadObj);
        $this->params = array_merge($this->getParams(), $loadObj->getParentParams());

    }

    /**
     * @param string $namespace
     * @param string $fileNs
     * @param AbstractLoad $loadObj
     */
    protected function setNestedLoadParams(string $namespace, string $fileNs, AbstractLoad $loadObj): void
    {
        $this->params['inc'][$namespace]['filename'] = $loadObj->getTemplate();
        $this->params['inc'][$namespace]['params'] = $loadObj->getParams();
        $this->params['inc'][$namespace]['namespace'] = $fileNs;
        $this->params['inc'][$namespace]['jsonParam'] = $loadObj->getJsonParams();
        $this->params['inc'][$namespace]['parent'] = $this->getLoadName();
        $this->params['inc'][$namespace]['permalink'] = $loadObj->getPermalink();
    }

    private function setNgsParenLoadName(string $load): void
    {
        $this->parentLoadName = $load;
    }

    public function getNgsParentLoadName(): string
    {
        return $this->parentLoadName;
    }

    /**
     * this method add template varialble
     *
     * @abstract
     * @access public
     * @param String $name
     * @param mixed $value
     *
     * @return void
     */
    protected final function addParentParam(string $name, $value): void
    {
        $this->parentParams[$name] = $value;

    }

    /**
     * this method add json varialble
     *
     * @abstract
     * @access public
     * @param String $name
     * @param mixed $value
     *
     * @return void
     */
    public function addJsonParam(string $name, $value): void
    {
        $this->jsonParam[$name] = $value;
    }


    /**
     * Return params array
     * @abstract
     * @access public
     *
     * @return array
     */
    protected function getParentParams(): array
    {
        return $this->parentParams;

    }

    /**
     * Return json params array
     * @abstract
     * @access public
     *
     * @return array
     */
    public function getJsonParams(): array
    {
        return $this->jsonParam;
    }

    /**
     * this abstract method should be replaced in childs load
     * for add nest laod
     * @abstract
     * @access public
     *
     * @return array
     */
    public function getDefaultLoads(): array
    {
        return [];
    }

    /**
     * this abstract method should be replaced in childs load
     * for set load template
     * @abstract
     * @access public
     *
     * @return string
     */
    public function getTemplate(): ?string
    {
        return null;
    }

    /**
     * check if load can be nested
     * @abstract
     * @access
     * @param string $namespace
     * @param AbstractLoad $load
     *
     * @return bool
     */
    public function isNestable($namespace, $load)
    {
        return true;
    }

    /**
     * set true if load called from parent (if load is nested)
     *
     * @param boolean $isNestedLoad
     *
     * @return void
     */
    public final function setIsNestedLoad($isNestedLoad)
    {
        $this->isNestedLoad = $isNestedLoad;
    }

    /**
     * get true if load is nested
     *
     * @return boolean|$isNestedLoad
     */
    public final function isNestedLoad(): bool
    {
        return $this->isNestedLoad;
    }

    protected function setNgsLoadType($ngsLoadType)
    {
        $this->ngsLoadType = $ngsLoadType;
    }

    /**
     * set load type default it is smarty
     *
     *
     * @return string $type
     */
    public function getNgsLoadType(): string
    {
        if ($this->ngsLoadType !== null) {
            return $this->ngsLoadType;
        }
        //todo add additional header ngs framework checker
        if ($_SERVER['HTTP_ACCEPT'] === 'application/json' || $this->getTemplate() === null
            || strpos($this->getTemplate(), '.json')) {
            $this->ngsLoadType = 'json';
        } else {
            $this->ngsLoadType = 'smarty';
        }
        return $this->ngsLoadType;
    }

    /**
     * set load name
     *
     * @param string $name
     *
     * @return void
     */
    public function setLoadName(string $name): void
    {
        $this->load_name = $name;
    }

    /**
     * get load name
     *
     *
     * @return string load_name
     */
    public function getLoadName(): string
    {
        return $this->load_name;
    }

    /**
     * set wrapping load object(if load is nested)
     *
     * @param AbstractLoad $loadObj
     *
     * @return void
     */
    protected function setNgsWrappingLoad(AbstractLoad $loadObj): void
    {
        $this->ngsWrappingLoad = $loadObj;
    }

    /**
     * get wrapping load if load is nested
     *
     * @return AbstractLoad $ngsWrappingLoad
     */
    protected function getWrappingLoad(): ?AbstractLoad
    {
        return $this->ngsWrappingLoad;
    }

    protected function setNgsQueryParams(array $queryParamsArr): void
    {
        $this->ngsQueryParams = array_merge($queryParamsArr, $this->ngsQueryParams);
    }

    protected function getNgsQueryParams(): array
    {
        return $this->ngsQueryParams;
    }

    /**
     * get permalink
     *
     * @return string
     */
    public function getPermalink(): string
    {
        return '';
    }

    /**
     * @return array
     */
    public function getNgsRequestParams(): array
    {
        return $this->ngsRequestParams;
    }

    /**
     * @param array $ngsRequestParams
     */
    public function setNgsRequestParams(array $ngsRequestParams): void
    {
        $this->ngsRequestParams = $ngsRequestParams;
    }


    /**
     * this function invoked when user hasn't permistion
     *
     * @abstract
     * @access
     * @return void
     */
    public function onNoAccess(): void
    {
    }

    /**
     * main load function for ngs loads
     *
     * @return void
     */
    public abstract function load();

    public function afterRequest()
    {
        $this->afterLoad();
    }

    public function afterLoad()
    {
        return null;
    }


}
