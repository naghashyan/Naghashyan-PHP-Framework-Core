<?php
/**
 * default ngs routing class
 * this class by default used from dispacher
 * for matching url with routes
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site https://naghashyan.com
 * @year 2014-2022
 * @package ngs.framework.routes
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

namespace ngs\routes;

use ngs\exceptions\DebugException;
use ngs\exceptions\NotFoundException;

class NgsRoutes
{

    private ?array $routes = null;
    private ?string $package = null;
    private $nestedRoutes = null;
    private $contentLoad = null;
    private string $dynContainer = 'dyn';
    private $currentRoute = null;

    /**
     * return url dynamic part
     * this method can be overrided from other users
     * if they don't want to use 'dyn' container
     * but on that way maybe cause conflicts with routs
     *
     * @return String
     */
    protected function getDynContainer(): string
    {
        return $this->dynContainer;
    }

    /**
     * read from file json routes
     * and set in private property for cache
     *
     * @return Object Array
     */
    protected function getRouteConfig(): ?array
    {
        if ($this->routes == null) {
            $routFile = NGS()->getConfigDir() . '/' . NGS()->getDefinedValue('NGS_ROUTS');
            if (file_exists($routFile)) {
                $this->routes = json_decode(file_get_contents($routFile), true);
                if (NGS()->getDefinedValue('NGS_MODULE_ROUTS')) {
                    $routFile = NGS()->getConfigDir() . '/' . NGS()->getDefinedValue('NGS_MODULE_ROUTS');
                    $moduleRoutFile = json_decode(file_get_contents($routFile), true);
                    $this->routes = array_merge($this->routes, $moduleRoutFile);
                }
            }
        }
        return $this->routes;
    }

    /**
     * set url package
     *
     * @return void
     */
    private function setPackage(string $package): void
    {
        $this->package = $package;
    }

    /**
     * return url package
     *
     * @return String $package
     */
    public function getPackage(): ?string
    {
        return $this->package;
    }

    /**
     *
     * this method return pakcage and command from url
     * check url if set dynamic container return manage using standart routing
     * if not manage url using routes file if matched succsess return array if not false
     * this method can be overrided from users for they custom routing scenarios
     *
     * @param string $url
     * @param bool $is404
     * @return array|null
     * @throws DebugException
     * @throws NotFoundException
     */
    public function getDynamicLoad(string $url, bool $is404 = false): ?array
    {
        $loadsArr = ['matched' => false];
        //do check if uri exist if not get default route
        if ($url[0] === '/') {
            $url = substr($url, 1);
        }
        $urlMatches = explode('/', $url);
        if ($urlMatches && $urlMatches[0] === '') {
            unset($urlMatches[0]);
        }
        $matches = $urlMatches;
        $staticFile = false;
        $package = '';
        if (!$is404) {
            $package = array_shift($matches);
            $fileUrl = $url;
            if (strpos($fileUrl, '/') === 0) {
                $fileUrl = substr($fileUrl, 1);
            }
        } else {
            $package = '404';
        }

        $urlPartsArr = $matches;
        if ($package === $this->getDynContainer()) {
            $package = array_shift($urlPartsArr);
            if ($package === NGS()->getModulesRoutesEngine()->getModuleNS()) {
                $package = array_shift($urlPartsArr);
            }
            $loadsArr = $this->getStandartRoutes($package, $urlPartsArr);
        } else {
            if ($package === null) {
                $package = 'default';
            }
            $loadsArr = $this->getDynRoutesLoad($url, $package, $urlPartsArr, $is404, $staticFile);
        }
        if ($loadsArr['matched']) {
            $actionArr = $this->getLoadORActionByAction($loadsArr['action']);
            $loadsArr['type'] = $actionArr['type'];
            $loadsArr['action'] = $actionArr['action'];
        }
        if ((strrpos(end($matches), '.')) !== false) {
            $staticFile = true;
        }
        //if static file
        if ($loadsArr['matched'] == false && $staticFile == true) {
            if ($urlMatches[0] == strtolower(NGS()->getModulesRoutesEngine()->getDefaultNS())) {
                array_shift($urlMatches);
                $fileUrl = substr($fileUrl, strpos($fileUrl, '/') + 1);
            }
            $loadsArr = $this->getStaticFileRoute($matches, $urlMatches, $fileUrl);
            $package = $loadsArr['module'];
        }
        $this->setPackage($package);
        return $loadsArr;
    }

    /**
     * this method returd file path and namsepace form action
     * @static
     * @access
     * @param string|null $action
     * @return array|null
     */
    public function getLoadORActionByAction(?string $action = null): ?array
    {
        if (!$action) {
            return null;
        }
        $pathArr = explode('.', $action);
        $action = array_splice($pathArr, count($pathArr) - 1);
        $action = $action[0];
        $module = array_splice($pathArr, 0, 1);
        $module = $module[0];
        $actionType = '';
        foreach ($pathArr as $i => $v) {
            switch ($v) {
                case NGS()->getActionPackage() :
                    $actionType = 'action';
                    $classPrefix = 'Action';
                    break;
                case NGS()->getLoadsPackage() :
                    $actionType = 'load';
                    $classPrefix = 'Load';
                    break;
            }
            if ($actionType !== '') {
                break;
            }
        }
        if (strrpos($action, 'do_') !== false) {
            $action = str_replace('do_', '', $action);
        }
        $action = preg_replace_callback('/_(\w)/', function ($m) {
                return strtoupper($m[1]);
            }, ucfirst($action)) . $classPrefix;
        return ['action' => $module . '\\' . implode('\\', $pathArr) . '\\' . $action, 'type' => $actionType];
    }

    /**
     * returns matched route data
     *
     * @param string $action
     * @param array $route
     * @return array
     */
    protected function getMatchedRouteData(string $action, array $route): array
    {
        return [
            'action' => $action,
            'args' => $route['args'],
            'matched' => true
        ];
    }

    /**
     * NGS standart routing url first part using for package
     * second part for command and others parts for args
     *
     * @param String $package
     * @param array $urlPartsArr
     *
     * @return array|false
     */
    private function getStandartRoutes($ns, $urlPartsArr)
    {
        $command = array_shift($urlPartsArr);
        if ($command == null) {
            $command = 'default';
        }
        if (strpos($ns, '_') !== false) {
            $ns = str_replace('_', '.', $ns);
        }
        $module = NGS()->getModulesRoutesEngine()->getModuleNS();
        $actionPackage = NGS()->getLoadsPackage();
        if (strrpos($command, 'do_') !== false) {
            $actionPackage = NGS()->getActionPackage();
        }
        $action = $module . '.' . $actionPackage . '.';
        if ($ns) {
            $action .= $ns . '.';
        }
        $action .= $command;
        $this->setContentLoad($action);
        return array('action' => $action, 'args' => $urlPartsArr, 'matched' => true);
    }

    /**
     *
     * NGS dynamic routing using routes json file for url match
     * first url part use for json array key match
     *
     * @param string $url
     * @param string $package
     * @param array $urlPartsArr
     * @param bool $is404
     * @param bool $staticFile
     * @return array|false
     * @throws DebugException
     * @throws NotFoundException
     */
    private function getDynRoutesLoad(string $url, string $package, array $urlPartsArr, bool $is404 = false, bool $staticFile = false)
    {
        $routes = $this->getRouteConfig();
        if (!isset($routes[$package])) {
            if (isset($routes['default']['action'], $routes['default']['404']) && $is404 === true) {
                $package = '404';
            } else {
                return ['matched' => false];
            }
        }

        $matchedRoutesArr = [];

        if ($package === '404') {
            $matchedRoutesArr[] = $routes['default'][$package];
        } else if ($package === 'default') {
            $matchedRoutesArr[][$package] = $routes[$package];
        } else {
            $matchedRoutesArr = $routes[$package];
        }
        $dynRoute = false;
        $args = false;
        $foundRoute = [];

        foreach ($matchedRoutesArr as $route) {
            $foundRoute = [];
            if (isset($route['default'])) {
                if ($route['default'] === 'dyn') {
                    $dynRoute = true;
                    continue;
                }
                if (isset($route['default']['action'], $route['default']['404']) && $is404 === true) {
                    $foundRoute = $route['default']['404'];
                } else {
                    $foundRoute = $route['default'];
                    break;
                }
            }

            if (isset($route['method']) && strtolower($route['method']) !== strtolower($this->getRequestHttpMethod())) {
                continue;
            }

            $foundRoute = $route;
            $args = $this->getMatchedRoute($urlPartsArr, $foundRoute);
            if (!isset($foundRoute['args'])) {
                $foundRoute['args'] = array();
            }
            if (is_array($args)) {
                $foundRoute['args'] = array_merge($foundRoute['args'], $args);
                break;
            }
            if (isset($foundRoute['action'])) {
                unset($foundRoute['action']);
            }
        }

        if ($args === null && !isset($foundRoute['action'])) {
            if ($dynRoute === true) {
                return $this->getStandartRoutes($package, $urlPartsArr);
            }
            if ($staticFile) {
                return array('matched' => false);
            }

            if (NGS()->getEnvironment() === 'development') {
                $this->onNoMatchedRoutes();
            }
            throw new NotFoundException();
        }

        $actionType = substr($foundRoute['action'], 0, strpos($foundRoute['action'], '.'));
        if (NGS()->getModulesRoutesEngine()->checkModulByNS($actionType)) {
            $actionNS = $actionType;
            $foundRoute['action'] = substr($foundRoute['action'], strpos($foundRoute['action'], '.') + 1);
        } else if (isset($foundRoute['namespace'])) {
            $actionNS = $foundRoute['namespace'];
        } else {
            $actionNS = NGS()->getModulesRoutesEngine()->getModuleNS();
        }

        $_action = $actionNS . '.' . $foundRoute['action'];
        $this->setContentLoad($_action);
        if (isset($foundRoute['nestedLoad'])) {
            $this->setNestedRoutes($foundRoute['nestedLoad'], $foundRoute['action']);
        }
        $this->setCurrentRoute($foundRoute);
        if (!isset($foundRoute['args'])) {
            $foundRoute['args'] = array();
        }
        return $this->getMatchedRouteData($_action, $foundRoute);
    }


    /**
     * @throws DebugException
     */
    protected function onNoMatchedRoutes()
    {
        throw new DebugException('No Matched Routes');
    }

    /**
     * this method do manage constraints from url parts
     * if in routes rule found constraints
     * using url others part of url for matching
     *
     * @param array $uriParams
     * @param array $routeArr
     *
     * @return array|false
     * @throws DebugException
     */
    private function getMatchedRoute(array $uriParams, array $routeArr): ?array
    {
        $route = '';
        if (!isset($routeArr['route'])) {
            $routeArr['route'] = '';
        }
        $route = $routeArr['route'];
        if (strpos($route, '[:') === false && strpos($route, '[/:') === false) {
            $fullUri = implode('/', $uriParams);
            if (isset($route[0]) && strpos($route, '/') === 0) {
                $route = substr($route, 1);
            }
            $route = str_replace('/', '\/', $route) . '\/';

            $newUri = preg_replace('/^' . $route . '$/', '', $fullUri . '/', -1, $count);
            if ($count === 0) {
                return null;
            }
            preg_match_all('/([^\/\?]+)/', $newUri, $matches);
            return $matches[1];
        }
        $routeUrlExp = $routeArr['route'];
        $originalUrl = '/' . implode('/', $uriParams);
        foreach ((array)$routeArr['constraints'] as $item => $constraint) {

            if (strpos($routeUrlExp, ':' . $item) === false) {
                throw new \ngs\exceptions\DebugException('constraints and routs params note matched, please check in ' . NGS()->get('NGS_ROUTS') . 'in this rout section ' . $route);
            }
            // $replaceValue = '(' . $constraint . ')';
            //is  Necessary
            if (strpos($routeUrlExp, '/:' . $item) === false) {
                $routeUrlExp = str_replace('[:' . $item . ']', '(?<' . $item . '>' . $constraint . ')', $routeUrlExp);
            } else {
                $routeUrlExp = str_replace('[/:' . $item . ']', '/?(?<' . $item . '>' . $constraint . ')?', $routeUrlExp);
            }
        }
        $routeUrlExp = str_replace('/', '\/', $routeUrlExp);
        preg_match('/^\/' . $routeUrlExp . '$/', $originalUrl, $matches);
        if (!$matches) {
            return null;
        }
        $urlMatchArgs = [];
        foreach ((array)$routeArr['constraints'] as $item => $constraint) {
            if (isset($matches[$item])) {
                $urlMatchArgs[$item] = $matches[$item];
            }
        }
        return $urlMatchArgs;
    }


    public function getStaticFileRoute($matches, $urlMatches, $fileUrl)
    {
        $loadsArr = array();
        $loadsArr['type'] = 'file';
        $loadsArr['file_type'] = pathinfo(end($matches), PATHINFO_EXTENSION);
        $filePices = $urlMatches;
        if (NGS()->getModulesRoutesEngine()->checkModuleByNS($filePices[0])) {
            $package = array_shift($filePices);
            $fileUrl = implode('/', $filePices);
            //NGS()->getModulesRoutesEngine()->setModuleNS($filePices[0]);
        } else {
            $package = array_shift($filePices);
        }
        //checking if css loaded from less
        $filePeaceIndex = 0;
        if (!NGS()->getModulesRoutesEngine()->isDefaultModule() && NGS()->getModulesRoutesEngine()->getModuleType() != 'path') {
            $filePeaceIndex = 1;

        }
        if (isset($filePices[$filePeaceIndex]) && $filePices[$filePeaceIndex] == 'less') {
            $loadsArr['file_type'] = 'less';
        }
        if (isset($filePices[$filePeaceIndex]) && $filePices[$filePeaceIndex] == 'sass') {
            $loadsArr['file_type'] = 'sass';
        }
        if (!NGS()->getModulesRoutesEngine()->checkModuleByNS($package)) {
            $package = NGS()->getModulesRoutesEngine()->getDefaultNS();
        }
        if (NGS()->getModulesRoutesEngine()->getModuleType() == 'path') {
            $package = NGS()->getModulesRoutesEngine()->getModuleNS();
        }
        $loadsArr['module'] = $package;
        $loadsArr['file_url'] = $fileUrl;
        $loadsArr['matched'] = true;
        return $loadsArr;
    }

    /**
     * set url nestedLoads
     *
     * @return void
     */

    private function setNestedRoutes(array $nestedLoads, string $package): void
    {
        foreach ($nestedLoads as $key => $value) {
            if (isset($value['namespace'])) {
                $actionNS = $value['namespace'];
            } else {
                $actionNS = NGS()->getModulesRoutesEngine()->getModuleNS();
            }
            $value['package'] = $value['action'];
            $value['action'] = $actionNS . '.' . $value['action'];
            $nestedLoads[$key]['action'] = $value['action'];
            if (isset($value['nestedLoad']) && is_array($value['nestedLoad'])) {
                $this->setNestedRoutes($value['nestedLoad'], $value['package']);
                unset($nestedLoads[$key]['nestedLoad']);
            }
        }
        $this->nestedRoutes[$package] = $nestedLoads;
    }

    public function getNestedRoutes($ns): array
    {
        if ($this->nestedRoutes === null || !isset($this->nestedRoutes[$ns])) {
            return [];
        }
        return $this->nestedRoutes[$ns];
    }

    private function setContentLoad($contentLoad)
    {
        $this->contentLoad = $contentLoad;
    }

    public function getContentLoad()
    {
        return $this->contentLoad;
    }

    private function setCurrentRoute($currentRoute)
    {
        $this->currentRoute = $currentRoute;
    }

    public function getCurrentRoute()
    {
        return $this->currentRoute;
    }

    public function getNotFoundLoad()
    {
        return $this->getDynamicLoad(NGS()->getHttpUtils()->getRequestUri(), true);
    }

    protected function getRequestHttpMethod()
    {
        if (isset($_SERVER['REQUEST_METHOD'])) {
            return strtolower($_SERVER['REQUEST_METHOD']);
        }
        return 'get';
    }

}