<?php
/**
 * Base ngs class
 * for static function that will
 * vissible from any classes
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @year 2015
 * @package ngs.framework.system
 * @version 2.0.0
 *
 * This file is part of the NGS package.
 *
 * @copyright Naghashyan Solutions LLC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

/*
 |--------------------------------------------------------------------------
 | DEFINNING VARIABLES IF SCRIPT RUNNING FROM COMMAND LINE
 |--------------------------------------------------------------------------
 */
if (php_sapi_name() == "cli") {
  $args = substr($argv[1], strpos($argv[1], "?") + 1);
  $uri = substr($argv[1], 0, strpos($argv[1], "?"));
  $_SERVER["REQUEST_URI"] = $uri;
  if (isset($args)) {
    $queryArgsArr = explode("&", $args);
    foreach ($queryArgsArr as $value) {
      $_arg = explode("=", $value);
      $_REQUEST[$_arg[0]] = $_arg[1];
      $_GET[$_arg[0]] = $_arg[1];
    }
  }
  if (isset($argv[2])) {
    $_SERVER["ENVIRONMENT"] = $argv[2];
  }
  $_SERVER["HTTP_HOST"] = "";
}

/*
|--------------------------------------------------------------------------
| DEFINNING DEFAULT VARIABLES
|--------------------------------------------------------------------------
*/

NGS()->define("VERSION", "1.0.0");
NGS()->define("NGSVERSION", "2.1.0");
NGS()->define("FRAMEWORK_NS", "ngs");
NGS()->define("DEFAULT_NS", "ngs");
/*
|--------------------------------------------------------------------------
| DEFINNING ENVIRONMENT VARIABLES
|--------------------------------------------------------------------------
*/
$environment = "development";
if (isset($_SERVER["ENVIRONMENT"])) {
  $environment = $_SERVER["ENVIRONMENT"];
}
NGS()->define("ENVIRONMENT", $environment);

//defaining ngs namespace
NGS()->define("JS_FRAMEWORK_ENABLE", true);

/*
|--------------------------------------------------------------------------
| DEFINNING DEFAULT DIRS
|--------------------------------------------------------------------------
*/
//---defining document root
if(strpos(getcwd(), "/htdocs") == false && strpos(getcwd(), "\htdocs") == false){
  throw new Exception("please change document root to htdocs");
}
//---defining ngs root
if(strpos(getcwd(), "/htdocs") !== false){
  $ngsRoot = substr(getcwd(), 0, strrpos(getcwd(), "/htdocs"));
}else{
  $ngsRoot = substr(getcwd(), 0, strrpos(getcwd(), "\htdocs"));
}
NGS()->define("NGS_ROOT", $ngsRoot);


/*
|--------------------------------------------------------------------------
| DEFINNING DEFAULTS PACKAGES DIRS
|--------------------------------------------------------------------------
*/

//---defining classes dir
NGS()->define("CLASSES_DIR", "classes");
//---defining public dir
NGS()->define("PUBLIC_DIR", "htdocs");
//---defining public output dir for css/js compiles files
NGS()->define("PUBLIC_OUTPUT_DIR", "out");
//---defining css dir in public folder
NGS()->define("CSS_DIR", "css");
//---defining less dir in public folder
NGS()->define("LESS_DIR", "less");
//---defining js dir in public folder
NGS()->define("JS_DIR", "js");
//---defining config dir
NGS()->define("CONF_DIR", "conf");
//---defining data dir
NGS()->define("DATA_DIR", "data");
//---defining temp dir
NGS()->define("TEMP_DIR", "temp");
//---defining bin dir
NGS()->define("BIN_DIR", "bin");
//---defining templates dir
NGS()->define("TEMPLATES_DIR", "templates");
//defining load and action directories
NGS()->define("LOADS_DIR", "loads");
NGS()->define("ACTIONS_DIR", "actions");
//defining routs file path
NGS()->define("NGS_ROUTS", "routes.json");


//defining database connector class path

//defining load mapper path
NGS()->define("LOAD_MAPPER", 'ngs\framework\routes\NgsLoadMapper');
//defining session manager path
NGS()->define("SESSION_MANAGER", 'ngs\framework\session\NgsSessionManager');
//defining session manager path
NGS()->define("TEMPLATE_ENGINE", 'ngs\framework\templater\NgsTemplater');
//---defining modules routing file
NGS()->define("FILE_UTILS", 'ngs\framework\util\FileUtils');
//---defining modules routing file
NGS()->define("HTTP_UTILS", 'ngs\framework\util\HttpUtils');
//---defining modules routing file
NGS()->define("MODULES_ROUTES_ENGINE", 'ngs\framework\routes\NgsModuleRoutes');
//---defining routing file
NGS()->define("ROUTES_ENGINE", 'ngs\framework\routes\NgsRoutes');
//---defining js builder file
NGS()->define("JS_BUILDER", 'ngs\framework\util\JsBuilder');
//---defining js builder file
NGS()->define("CSS_BUILDER", 'ngs\framework\util\CssBuilder');
//---defining js builder file
NGS()->define("LESS_BUILDER", 'ngs\framework\util\LessBuilder');
//---defining ngs utils file
NGS()->define("NGS_UTILS", 'ngs\framework\util\NgsUtils');

/*
|--------------------------------------------------------------------------
| DEFINNING NGS DEFAULT EXCEPTIONS
|--------------------------------------------------------------------------
*/
//---defining debug exception
NGS()->define("NGS_EXCEPTION_DEBUG", 'ngs\framework\exceptions\DebugException');
//---defining Invalid User exception
NGS()->define("NGS_EXCEPTION_INVALID_USER", 'ngs\framework\exceptions\InvalidUserException');
//---defining Invalid User exception
NGS()->define("NGS_EXCEPTION_NGS_ERROR", 'ngs\framework\exceptions\NgsErrorException');
//---defining Invalid User exception
NGS()->define("NGS_EXCEPTION_NO_ACCESS", 'ngs\framework\exceptions\NoAccessException');
//---defining Invalid User exception
NGS()->define("NGS_EXCEPTION_NOT_FOUND", 'ngs\framework\exceptions\NotFoundException');
/*
|--------------------------------------------------------------------------
| DEFINNING NGS MODULES
|--------------------------------------------------------------------------
*/
//---defining if modules enabled
NGS()->define("MODULES_ENABLE", TRUE);
//---defining modules dir
NGS()->define("MODULES_DIR", "modules");
//---defining modules routing file
NGS()->define("NGS_MODULS_ROUTS", "modules.json");

/*
|--------------------------------------------------------------------------
| DEFINNING NGS DEFAULT ENGINES
|--------------------------------------------------------------------------
*/
NGS()->define("LESS_ENGINE", "lib/less.php/Less.php");

/*
|--------------------------------------------------------------------------
| DEFINNING SMARTY DIRS
|--------------------------------------------------------------------------
*/
NGS()->define("USE_SMARTY", TRUE);
//---defining smarty paths
NGS()->define("SMARTY_CACHE_DIR", "cache");
NGS()->define("SMARTY_COMPILE_DIR", "compile");


