<?php
/**
 * default ngs modules routing class
 * this class by default used from dispacher
 * for matching url with modules routes
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site https://naghashyan.com
 * @year 2015-2023
 * @package ngs.framework.routes
 * @version 5.0.0
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

class NgsModuleRoutes
{

    private array $routes = [];
    private array $shuffledRoutes = [];
    private ?string $defaultNS = null;
    private array $moduleArr = [];
    private array $modulesLists = [];
    private string $dynContainer = 'dyn';
    private string $type = 'domain';
    private string $ns = 'ngs';
    private string $name = '';
    private ?array $parentModule = null;
    private ?string $uri = null;

    public function __construct()
    {
        $moduleArr = $this->getModule();
        if (!$moduleArr) {
            throw new DebugException('module.json not found please add file json into config folder');
        }
        $this->setModuleNS($moduleArr['ns']);
        $this->setModuleUri($moduleArr['uri']);
        $this->setModuleType($moduleArr['type']);
    }

    public function initialize(): void
    {
    }

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
     * @return json Array
     */
    private function getRouteConfig(): array
    {
        if (count($this->routes) == 0) {
            $moduleConfigFilePath = NGS()->get('NGS_ROOT') . '/' . NGS()->get('CONF_DIR') . '/' . NGS()->get('NGS_MODULS_ROUTS');
            try {
                $moduleRouteFile = realpath($moduleConfigFilePath);
                $this->routes = json_decode(file_get_contents($moduleRouteFile), true);
            } catch (\Exception $exception) {
                throw new DebugException('module.json not found please add file json into ' . $moduleConfigFilePath);
            }

        }
        return $this->routes;
    }

    /**
     * get shuffled routes json
     * key=>dir value=namespace
     * and set in private shuffledRoutes property for cache
     *
     * @return array
     */
    public function getShuffledRoutes(): array
    {
        if (count($this->shuffledRoutes) > 0) {
            return $this->shuffledRoutes;
        }
        $routes = $this->getRouteConfig();
        $this->shuffledRoutes = array();
        foreach ($routes as $domain => $route) {
            foreach ($route as $type => $routeItem) {
                if ($type === 'default') {
                    $this->shuffledRoutes[$routeItem['dir']] = array('path' => $routeItem['dir'], 'type' => $type, 'domain' => $domain);
                    continue;
                }
                foreach ($routeItem as $item) {
                    if (isset($item['dir'])) {
                        $this->shuffledRoutes[$item['dir']] = array('path' => $item['dir'], 'type' => $type, 'domain' => $domain);
                    } elseif (isset($item['extend'])) {
                        $this->shuffledRoutes[$item['extend']] = array('path' => $item['extend'], 'type' => $type, 'domain' => $domain);
                    }
                }
            }
        }
        return $this->shuffledRoutes;
    }

    public function getDefaultNS(): string
    {
        if ($this->defaultNS !== null) {
            return $this->defaultNS;
        }
        $routes = $this->getRouteConfig();
        if (isset($routes['default']['default'])) {
            $defaultModule = $routes['default']['default'];
            $defaultMatched = $this->getMatchedModule($defaultModule, '', 'default');
            $this->defaultNS = $defaultMatched['ns'];
        } else {
            $this->defaultNS = NGS()->getDefinedValue('DEFAULT_NS');
        }
        return $this->defaultNS;
    }

    /**
     * check module by name
     *
     *
     * @param String $name
     *
     * @return true|false
     */
    public function checkModuleByUri(string $name): bool
    {
        $routes = $this->getRouteConfig();
        if (isset($routes['subdomain'][$name])) {
            return true;
        }

        if (isset($routes['domain'][$name])) {
            return true;
        }

        if (isset($routes['path'][$name])) {
            return true;
        }

        if ($name === $this->getDefaultNS()) {
            return true;
        }
        return false;
    }

    /**
     * check module by name
     *
     *
     * @param String $name
     *
     * @return true|false
     */
    public function checkModuleByNS(string $ns): bool
    {
        $routes = $this->getShuffledRoutes();
        if (isset($routes[$ns])) {
            return true;
        }
        return false;
    }

    /**
     * this method return pakcage and command from url
     * check url if set dynamic container return manage using standart routing
     * if not manage url using routes file if matched succsess return array if not false
     * this method can be overrided from users for they custom routing scenarios
     *
     * @param String $url
     *
     * @return array
     */
    protected function getModule(): array
    {
        if ($this->moduleArr) {
            return $this->moduleArr;
        }
        $module = $this->getDefaultNS();
        $domain = NGS()->getHttpUtils()->_getHttpHost(true);

        if (!$domain) {
            $uri = '';
            $moduleConfigArray = $this->getModulePartByDomain(null);
            $this->setModuleName('default');
            $this->moduleArr = $this->getMatchedModule($moduleConfigArray['default'], $uri, 'default');
            return $this->moduleArr;
        }
        $parsedUrl = parse_url($domain);
        $mainDomain = NGS()->getHttpUtils()->getMainDomain();
        $moduleConfigArray = $this->getModulePartByDomain($mainDomain);
        $host = explode('.', $parsedUrl['path']);
        $uri = NGS()->getHttpUtils()->getRequestUri(true);

        if ($this->moduleArr = $this->getModuleByURI($moduleConfigArray, $uri)) {
            $this->setModuleName($this->moduleArr['uri']);
            if (count($host) >= 3) {
                $parentModule = $this->getModuleBySubDomain($moduleConfigArray, $host[0]);
                if ($parentModule) {
                    $this->setParentModule($parentModule);
                }
            }
            return $this->moduleArr;
        }
        if (count($host) >= 3) {
            if ($moduleArr = $this->getModuleBySubDomain($moduleConfigArray, $host[0])) {
                $this->moduleArr = $moduleArr;
                $this->setModuleName($this->moduleArr['uri']);
                return $this->moduleArr;
            }
        }
        $this->setModuleName('default');
        $this->moduleArr = $this->getMatchedModule($moduleConfigArray['default'], $uri, 'default');
        return $this->moduleArr;
    }

    private function getModulePartByDomain(?string $domain = null): array
    {
        $routes = $this->getRouteConfig();
        if (isset($routes[$domain])) {
            return $routes[$domain];
        }
        if (isset($routes['default'])) {
            return $routes['default'];
        }
        throw new DebugException('PLEASE ADD DEFAULT SECTION IN module.json');
    }

    /**
     * return module by subdomain
     *
     * @param String $domain
     *
     * @return array|null
     */
    private function getModuleBySubDomain(array $modulePart, string $domain): ?array
    {
        $routes = $modulePart;
        if (isset($routes['subdomain'][$domain])) {
            return $this->getMatchedModule($routes['subdomain'][$domain], $domain, 'subdomain');
        }
        return null;
    }

    /**
     * return module by uri
     *
     * @param String $domain
     *
     * @return string
     */
    private function getModuleByURI(array $modulePart, string $uri): array
    {
        $matches = [];
        preg_match_all('/(\/([^\/\?]+))/', $uri, $matches);

        if (is_array($matches[2]) && isset($matches[2][0])) {
            if ($matches[2][0] == $this->getDynContainer()) {
                array_shift($matches[2]);
            }
            if (isset($modulePart['path'][$matches[2][0]])) {
                return $this->getMatchedModule($modulePart['path'][$matches[2][0]], $matches[2][0], 'path');
            } else if ($matches[2][0] == $this->getDefaultNS()) {
                return ['ns' => $this->getDefaultNS(), 'uri' => $this->getDefaultNS(), 'type' => 'path'];
            }
        }

        return [];
    }

    /**
     * @param $matchedArr
     * @param $uri
     * @param $type
     * @return array
     * @throws DebugException
     */
    protected function getMatchedModule(array $matchedArr, string $uri, string $type): array
    {
        $ns = null;
        $module = null;
        $extended = false;
        if (isset($matchedArr['dir'])) {
            $ns = $matchedArr['dir'];
        } elseif (isset($matchedArr['namespace'])) {
            $ns = $matchedArr['namespace'];
        } elseif (isset($matchedArr['extend'])) {
            $ns = $matchedArr['extend'];
            if (isset($matchedArr['route_file'])) {
                NGS()->define('NGS_MODULE_ROUTS', $matchedArr['route_file']);
            }
        } else {
            throw new DebugException('PLEASE ADD DIR OR NAMESPACE SECTION IN module.json');
        }
        /*TODO add global extend
         if (isset($matchedArr['extend'])) {
         $ns = $matchedArr['extend'];
         $module = $matchedArr['module'];
         $extended = true;
         }*/
        return ['ns' => $ns, 'uri' => $uri, 'type' => $type];
    }

    //Module interface implementation

    /**
     * set module type if is domain or subdomain or path
     *
     * @param String $type
     *
     * @return void
     */
    private function setModuleType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * return defined module type
     *
     * @return String
     */
    public function getModuleType(): string
    {
        return $this->type;
    }

    /**
     * set module namespace if is domain or subdomain or path
     *
     * @param String $ns
     *
     * @return void
     */
    private function setModuleNS(string $ns): void
    {
        $this->ns = $ns;
    }

    /**
     * return current namespace
     *
     * @return String
     */
    public function getModuleNS(): string
    {
        return $this->ns;
    }

    /**
     * set module name domain or subdomain or path
     *
     * @param $name String
     *
     * @return void
     */
    private function setModuleName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return array
     */
    public function getParentModule(): ?array
    {
        return $this->parentModule;
    }

    /**
     * @param array $parentModule
     */
    public function setParentModule(array $parentModule): void
    {
        $this->parentModule = $parentModule;
    }

    /**
     * return current name
     *
     * @return String
     */
    public function getModuleName(): string
    {
        return $this->name;
    }

    /**
     * return all modules list
     *
     * @return array
     */
    public function getAllModules(): array
    {
        if ($this->modulesLists != null) {
            return $this->modulesLists;
        }
        $this->modulesLists = [];
        $routes = $this->getRouteConfig();
        foreach ($routes as $key => $value) {
            $this->modulesLists = array_merge($this->modulesLists, $this->getModulesByType($routes, 'subdomain'));
            $this->modulesLists = array_merge($this->modulesLists, $this->getModulesByType($routes, 'domain'));
            $this->modulesLists = array_merge($this->modulesLists, $this->getModulesByType($routes, 'path'));
            if (isset($value['default']) && isset($value['default']['dir'])) {
                $this->modulesLists[] = $value['default']['dir'];
            }

        }
        return $this->modulesLists;

    }

    /**
     * return module list by type
     *
     * @return array
     */
    private function getModulesByType(array $routes, string $type): array
    {
        $tmpArr = [];
        $routes = $this->getRouteConfig();
        if (isset($routes[$type])) {
            foreach ($routes[$type] as $value) {
                if (isset($value['dir'])) {
                    $tmpArr[] = $value['dir'];
                }
            }
        }
        return $tmpArr;
    }

    /**
     * return module dir connedted with namespace
     *
     * @return String
     */
    public function getModuleNsByUri(string $uri): ?string
    {
        $routes = $this->getRouteConfig();
        if (isset($routes['subdomain'][$uri])) {
            return $routes['subdomain'][$uri];
        } elseif (isset($routes['domain'][$uri])) {
            return $routes['domain'][$uri];
        } elseif (isset($routes['path'][$uri])) {
            return $routes['path'][$uri];
        }
        return null;
    }

    //module function for working with modules urls
    public function setModuleUri(string $uri): void
    {
        $this->uri = $uri;
    }

    public function getModuleUri(): string
    {
        return $this->uri;
    }

    /**
     * @param $ns
     *
     * @return null
     */
    public function getModuleUriByNS(string $ns): ?array
    {
        $routes = $this->getShuffledRoutes();
        if (isset($routes[$ns])) {
            return $routes[$ns];
        }
        return null;
    }


    /**
     * @param $ns
     *
     * @return bool
     */
    public function checkModulByNS(string $ns): bool
    {
        $routes = $this->getShuffledRoutes();
        if (isset($routes[$ns])) {
            return true;
        }
        return false;
    }

    /**
     * detect if current module is default module
     *
     * @return Boolean
     */
    public function isDefaultModule(): bool
    {
        if ($this->getModuleNS() == $this->getDefaultNS()) {
            return true;
        }
        return false;
    }

    /**
     * detect if $ns is current module
     *
     * @param string $namespace
     *
     * @return Boolean
     */
    public function isCurrentModule(string $namespace): bool
    {
        if ($this->getModuleNS() == $namespace) {
            return true;
        }
        return false;
    }

    /**
     * this method calculate dir conencted with module
     *
     * @param string $namespace
     *
     * @return string|null rootDir
     */
    public function getRootDir(string $namespace = ''): ?string
    {
        if (($namespace === '' && $this->getDefaultNS() == $this->getModuleNS()) || $this->getDefaultNS() == $namespace) {
            return NGS()->get('NGS_ROOT');
        }
        if ($namespace === NGS()->get('FRAMEWORK_NS')) {
            return NGS()->getFrameworkDir();
        }
        if ($namespace === NGS()->get('NGS_CMS_NS')) {
            if ($cmsPath = NGS()->getNgsCmsDir()) {
                return $cmsPath;
            }
        }
        if ($namespace === '' && $this->getModuleNS() === NGS()->get('NGS_CMS_NS')) {
            if ($cmsPath = NGS()->getNgsCmsDir()) {
                return $cmsPath;
            }
        }
        if ($namespace === '') {
            return realpath(NGS()->get('NGS_ROOT') . '/' . NGS()->get('MODULES_DIR') . '/' . $this->getModuleNS());
        }
        return realpath(NGS()->get('NGS_ROOT') . '/' . NGS()->get('MODULES_DIR') . '/' . $namespace);

    }


}