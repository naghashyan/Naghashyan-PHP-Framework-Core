<?php
/**
 * default ngs routing class
 * this class by default used from dispacher
 * for matching url with routes
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @year 2014-2015
 * @package ngs.framework.routes
 * @version 2.1.1
 *
 * This file is part of the NGS package.
 *
 * @copyright Naghashyan Solutions LLC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */
namespace ngs\framework\routes {
    class NgsRoutes {

        private $routes = null;
        private $package = null;
        private $nestedRoutes = null;
        private $jsonParams = array();
        private $contentLoad = null;
        private $dynContainer = "dyn";

        /**
         * return url dynamic part
         * this method can be overrided from other users
         * if they don't want to use "dyn" container
         * but on that way maybe cause conflicts with routs
         *
         * @return String
         */
        protected function getDynContainer() {
            return $this->dynContainer;
        }

        /**
         * read from file json routes
         * and set in private property for cache
         *
         * @return json Array
         */
        protected function getRouteConfig() {
            if ($this->routes == null) {
                $routFile = NGS()->getConfigDir() . "/routes.json";
                if (file_exists($routFile)) {
                    $this->routes = json_decode(file_get_contents($routFile), true);
                }
            }
            return $this->routes;
        }

        /**
         * set url package
         *
         * @return void
         */
        private function setPackage($package) {
            $this->package = $package;
        }

        /**
         * return url package
         *
         * @return String $package
         */
        public function getPackage() {
            return $this->package;
        }

        /**
         * this method return pakcage and command from url
         * check url if set dynamic container return manage using standart routing
         * if not manage url using routes file if matched succsess return array if not false
         * this method can be overrided from users for they custom routing scenarios
         *
         * @param String $url
         *
         * @return array|false
         */
        public function getDynamicLoad($url) {

            $urlPartsArr = array();
            $loadsArr = array("matched" => false);
            //do check if uri exist if not get default route
            preg_match_all("/(\/([^\/\?]+))/", $url, $matches);
            $staticFile = false;
            if (($staticfilePos = strrpos(end($matches[2]), ".")) !== false) {
                $staticFile = true;
            }
            $package = array_shift($matches[2]);
            $fileUrl = $url;
            if ($fileUrl[0] == "/") {
                $fileUrl = substr($fileUrl, 1);
            }
            if (isset($matches[2][0]) && !NGS()->getModulesRoutesEngine()->isDefaultModule() && NGS()->getModulesRoutesEngine()->getModuleType() != "path") {
                $fileUrl = implode("/", $matches[2]);
            }
            $urlPartsArr = $matches[2];
            if ($package == $this->getDynContainer()) {
                $package = array_shift($urlPartsArr);
                if ($package == NGS()->getModulesRoutesEngine()->getModuleNS()) {
                    $package = array_shift($urlPartsArr);
                }
                $loadsArr = $this->getStandartRoutes($package, $urlPartsArr);
            } else {
                if ($package == null) {
                    $package = "default";
                }
                $loadsArr = $this->getDynRoutesLoad($url, $package, $urlPartsArr);
            }
            if ($loadsArr["matched"]) {
                $actionArr = $this->getLoadORActionByAction($loadsArr["action"]);
                $loadsArr["type"] = $actionArr["type"];
                $loadsArr["action"] = $actionArr["action"];
            }
            //if static file
            if ($loadsArr["matched"] == false && $staticFile == true) {
                $loadsArr["type"] = "file";
                $loadsArr["file_type"] = pathinfo(end($matches[2]), PATHINFO_EXTENSION);
                $filePices = explode("/", $fileUrl);
                //checking if css loaded from less
                if (isset($filePices[1]) && $filePices[1] == "less") {
                    $loadsArr["file_type"] = "less";
                }
                $package = NGS()->getModulesRoutesEngine()->getModuleNS();
                $loadsArr["module"] = $package;
                $loadsArr["file_url"] = $fileUrl;
                $loadsArr["matched"] = true;
            }
            $this->setPackage($package);
            return $loadsArr;
        }

        /**
         * this method returd file path and namsepace form action
         * @static
         * @access
         * @return String $namespace
         */
        public function getLoadORActionByAction($action) {
            if (!isset($action)) {
                return false;
            }
            $pathArr = explode(".", $action);
            $action = array_splice($pathArr, count($pathArr) - 1);
            $action = $action[0];
            $module = array_splice($pathArr, 0, 1);
            $module = $module[0];
            $actionType = "";
            foreach ($pathArr as $i => $v) {
                switch ($v) {
                    case NGS()->getActionPackage() :
                        $actionType = "action";
                        $classPrefix = "Action";
                        break;
                    case NGS()->getLoadsPackage() :
                        $actionType = "load";
                        $classPrefix = "Load";
                        break;
                }
                if ($actionType != "") {
                    break;
                }
            }
            if (strrpos($action, "do_") !== false) {
                $action = str_replace("do_", "", $action);
            }
            $action = preg_replace_callback("/_(\w)/", function ($m) {
                    return strtoupper($m[1]);
                }, ucfirst($action)) . $classPrefix;
            return array("action" => $module . "\\" . implode("\\", $pathArr) . "\\" . $action, "type" => $actionType);

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
        private function getStandartRoutes($ns, $urlPartsArr) {
            $package = "default";
            $command = array_shift($urlPartsArr);
            if ($command == null) {
                $command = "default";
            }
            if (strpos($ns, "_") !== false) {
                $ns = str_replace("_", ".", $ns);
            }
            $module = NGS()->getModulesRoutesEngine()->getModuleNS();
            $actionPackage = NGS()->getLoadsPackage();
            if (strrpos($command, "do_") !== false) {
                $actionPackage = NGS()->getActionPackage();
            }
            $this->setContentLoad($module . "." . $actionPackage . "." . $ns . "." . $command);
            return array("action" => $module . "." . $actionPackage . "." . $ns . "." . $command, "args" => $urlPartsArr, "matched" => true);
        }

        /**
         * NGS dynamic routing using routes json file for url match
         * first url part use for json array key match
         *
         * @param String $package
         * @param array $urlPartsArr
         *
         * @return array|false
         */
        private function getDynRoutesLoad($url, $package, $urlPartsArr) {
            $routes = $this->getRouteConfig();
            if (!isset($routes[$package])) {
                return array("matched" => false);
            }
            $matchedRoutesArr = array();
            if ($package == "default") {
                $matchedRoutesArr[] = $routes[$package];
            } else {
                $matchedRoutesArr = $routes[$package];
            }
            $dynRoute = false;
            $args = false;
            foreach ($matchedRoutesArr as $route) {
                if (isset($route["default"])) {
                    if ($route["default"] == "dyn") {
                        $dynRoute = true;
                        continue;
                    }
                    if (isset($route["default"]["action"])) {
                        $route = $route["default"];
                    }
                }
                $args = $this->getMatchedRoute($urlPartsArr, $route);

                $route["args"] = array();
                if ($args !== false && is_array($args)) {
                    $route["args"] = $args;
                    break;
                }
                if (isset($route["action"])) {
                    unset($route["action"]);
                }
            }
            if ($args == false && !isset($route["action"])) {
                if ($dynRoute == true) {
                    return $this->getStandartRoutes($package, $urlPartsArr);
                }
                throw NGS()->getNotFoundException();
            }
            $_action = NGS()->getModulesRoutesEngine()->getModuleNS() . "." . $route["action"];
            $this->setContentLoad($_action);
            if (isset($route["nestedLoad"])) {
                $this->setNestedRoutes($route["nestedLoad"], $route["action"]);
            }
            return array("action" => $_action, "args" => $route["args"], "matched" => true);
        }

        /**
         * this method do manage constraints from url parts
         * if in routes rule found constraints
         * using url others part of url for matching
         *
         * @param String $uriParams
         * @param String $route
         * @param array $constraints
         *
         * @return array|false
         */
        private function getMatchedRoute($uriParams, $routeArr) {
            $route = "";
            if (!isset($routeArr["route"])) {
                $routeArr["route"] = "";
            }
            $route = $routeArr["route"];
            if (strpos($route, "[:") === false && strpos($route, "[/:") === false) {
                $fullUri = implode("/", $uriParams);
                if (isset($route[0]) && $route[0] == "/") {
                    $route = substr($route, 1);
                }
                $route = str_replace("/", "\/", $route) . "\/";
                $newUri = preg_replace("/" . $route . "/", "", $fullUri . "/", -1, $count);
                if ($count == 0) {
                    return false;
                }
                preg_match_all("/([^\/\?]+)/", $newUri, $matches);
                return $matches[1];
            }
            preg_match_all("/\[([0-9A-Za-z\:\/]+)\]|([0-9A-Za-z])+/", $routeArr["route"], $matches, PREG_SET_ORDER);
            $urlMatchArgs = array();
            foreach ((array)$matches as $matchKey => $matchedValues) {
                $value = $matchedValues[1];
                if ($value == "") {
                    $value = $matchedValues[0];
                }
                $isAddParam = true;
                if (strpos($value, ":") === false) {
                    $value = str_replace("/", "", $value);
                    $key = $value;
                    $routeArr["constraints"][$key] = $value;
                    $isAddParam = false;
                } else {
                    $key = substr($value, strpos($value, ":") + 1);
                }
                $isNecessary = true;
                if (strpos($value, "/") !== false) {
                    $isNecessary = false;
                }

                if (!isset($routeArr["constraints"][$key])) {
                    throw new \ngs\framework\exceptions\DebugException("constraints and routs params note matched, please check in " . NGS_ROUTS . "in this rout section " . $route);
                }

                if (isset($uriParams[0])) {
                    preg_match("/" . $routeArr["constraints"][$key] . "+/", $uriParams[0], $args);
                } else {
                    if ($isNecessary) {
                        return false;
                    }
                    break;
                }
                if (count($args) == 0 && $isNecessary) {
                    return false;
                } else if (!isset($args[0])) {
                    $urlMatchArgs[$key] = null;
                } else {
                    if ($isAddParam) {
                        $urlMatchArgs[$key] = $args[0];
                    }
                }
                array_shift($uriParams);

            }
            return array_merge($urlMatchArgs, $uriParams);
        }

        /**
         * set url nestedLoads
         *
         * @return void
         */
        private function setNestedRoutes($nestedLoads, $package) {

            foreach ($nestedLoads as $key => $value) {
                $value["action"] = NGS()->getModulesRoutesEngine()->getModuleNS() . "." . $value["action"];
                $nestedLoads[$key]["action"] = $value["action"];
                if (isset($value["nestedLoad"]) && is_array($value["nestedLoad"])) {
                    $this->setNestedRoutes($value["nestedLoad"], NGS()->getModulesRoutesEngine()->getModuleNS() . "." . $value["action"]);
                    unset($nestedLoads[$key]["nestedLoad"]);
                }
            }
            $this->nestedRoutes[$package] = $nestedLoads;
        }

        public function getNestedRoutes($ns) {
            if ($this->nestedRoutes == null || !isset($this->nestedRoutes[$ns])) {
                return array();
            }
            return $this->nestedRoutes[$ns];
        }

        private function setContentLoad($contentLoad) {
            $this->contentLoad = $contentLoad;
        }

        public function getContentLoad() {
            return $this->contentLoad;
        }

    }

}
