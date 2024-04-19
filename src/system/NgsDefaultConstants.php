<?php
/**
 * Base ngs class
 * for static function that will
 * vissible from any classes
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site https://naghashyan.com
 * @year 2015-2019
 * @package ngs.framework.system
 * @version 4.0.0
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
 | DEFINING VARIABLES IF SCRIPT RUNNING FROM COMMAND LINE
 |--------------------------------------------------------------------------
 */
if (php_sapi_name() == 'cli' && NGS()->get('CMD_SCRIPT')){
  $args = null;
  if (isset($argv) && isset($argv[1])){
    $args = substr($argv[1], strpos($argv[1], '?') + 1);
    $uri = substr($argv[1], 0, strpos($argv[1], '?'));
    $_SERVER['REQUEST_URI'] = $uri;
  }
  if ($args != null){
    $queryArgsArr = explode('&', $args);
    foreach ($queryArgsArr as $value){
      $_arg = explode('=', $value);
      if (isset($_REQUEST[$_arg[0]])){
        if (is_array($_REQUEST[$_arg[0]])){
          $tmp = $_REQUEST[$_arg[0]];

        } else{
          $tmp = [];
          $tmp[] = $_REQUEST[$_arg[0]];
        }
        $tmp[] = $_arg[1];
        $_REQUEST[$_arg[0]] = $tmp;

      } else{
        $_REQUEST[$_arg[0]] = $_arg[1];
      }
    }
  }
  if (isset($argv[2]) && !isset($_SERVER['ENVIRONMENT'])){
    $_SERVER['ENVIRONMENT'] = $argv[2];
  }
  $_SERVER['HTTP_HOST'] = '';
}

/*
|--------------------------------------------------------------------------
| DEFINING DEFAULT VARIABLES
|--------------------------------------------------------------------------
*/

NGS()->define('VERSION', '1.0.0');
NGS()->define('NGSVERSION', '4.0.0');
NGS()->define('FRAMEWORK_NS', 'ngs');
NGS()->define('DEFAULT_NS', 'ngs');
NGS()->define('NGS_CMS_NS', 'ngs-AdminTools');
NGS()->define('NGS_PROJECT_OWNER', 'www-data');
NGS()->define('IM_TOKEN_COOKIE_KEY', '_im_token');

/*
|--------------------------------------------------------------------------
| DEFINING ENVIRONMENT VARIABLES
|--------------------------------------------------------------------------
*/

$environment = 'production';
if (isset($_SERVER['ENVIRONMENT'])){
  if ($_SERVER['ENVIRONMENT'] == 'development' || $_SERVER['ENVIRONMENT'] == 'dev'){
    $environment = 'development';
  } else if ($_SERVER['ENVIRONMENT'] == 'staging'){
    $environment = 'staging';
  }
}
NGS()->define('ENVIRONMENT', $environment);

NGS()->define('JS_FRAMEWORK_ENABLE', true);

NGS()->define('SEND_HTTP_PUSH', true);

/*
|--------------------------------------------------------------------------
| DEFINING DEFAULT DIRS
|--------------------------------------------------------------------------
*/
//---defining document root
if (strpos(getcwd(), '/htdocs') == false && strpos(getcwd(), '\htdocs') == false){
  throw new Exception('please change document root to htdocs');
}
//---defining ngs root
if (strpos(getcwd(), '/htdocs') !== false){
  $ngsRoot = substr(getcwd(), 0, strrpos(getcwd(), '/htdocs'));
} else{
  $ngsRoot = substr(getcwd(), 0, strrpos(getcwd(), '\htdocs'));
}
NGS()->define('NGS_ROOT', $ngsRoot);

/*
|--------------------------------------------------------------------------
| DEFINING DEFAULTS PACKAGES DIRS
|--------------------------------------------------------------------------
*/

//---defining classes dir
NGS()->define('CLASSES_DIR', 'classes');
//---defining public dir
NGS()->define('PUBLIC_DIR', 'htdocs');
//---defining public output dir for css/js compiles files
NGS()->define('PUBLIC_OUTPUT_DIR', 'out');
//---defining web dir in public folder
NGS()->define('WEB_DIR', 'web');
//---defining css dir in public folder
NGS()->define('CSS_DIR', 'css');
//---defining less dir in public folder
NGS()->define('LESS_DIR', 'less');
//---defining sass dir in public folder
NGS()->define('SASS_DIR', 'sass');
//---defining js dir in public folder
NGS()->define('JS_DIR', 'js');
//---defining config dir
NGS()->define('CONF_DIR', 'conf');
//---defining data dir
NGS()->define('DATA_DIR', 'data');
//---defining temp dir
NGS()->define('TEMP_DIR', 'temp');
//---defining bin dir
NGS()->define('BIN_DIR', 'bin');
//---defining templates dir
NGS()->define('TEMPLATES_DIR', 'templates');
//defining load and action directories
NGS()->define('LOADS_DIR', 'loads');
NGS()->define('ACTIONS_DIR', 'actions');
//defining routs file path
NGS()->define('NGS_ROUT_DIR', 'conf');
NGS()->define('NGS_ROUTS', 'routes.json');
NGS()->define('NGS_ROUTS_ARRAY', 'routes.json');

//defining database connector class path

//defining load mapper path
NGS()->define('LOAD_MAPPER', 'ngs\routes\NgsLoadMapper');
//defining session manager path
NGS()->define('SESSION_MANAGER', 'ngs\session\NgsSessionManager');
//defining session manager path
NGS()->define('TEMPLATE_ENGINE', 'ngs\templater\NgsTemplater');
//---defining modules routing file
NGS()->define('FILE_UTILS', 'ngs\util\FileUtils');
//---defining modules routing file
NGS()->define('HTTP_UTILS', 'ngs\util\HttpUtils');
//---defining modules routing file
NGS()->define('MODULES_ROUTES_ENGINE', 'ngs\routes\NgsModuleRoutes');
//---defining routing file
NGS()->define('ROUTES_ENGINE', 'ngs\routes\NgsRoutes');
//---defining js builder file
NGS()->define('JS_BUILDER', 'ngs\util\JsBuilderV2');
//---defining js build env
NGS()->define('JS_BUILD_MODE', $environment);
//---defining css builder file
NGS()->define('CSS_BUILDER', 'ngs\util\CssBuilder');
//---defining less builder file
NGS()->define('LESS_BUILDER', 'ngs\util\LessBuilder');
//---defining less build env
NGS()->define('LESS_BUILD_MODE', $environment);
//---defining sass builder file
NGS()->define('SASS_BUILDER', 'ngs\util\SassBuilder');
//---defining sass build env
NGS()->define('SASS_BUILD_MODE', $environment);

//---defining component streamer class file
NGS()->define('NGS_COMPONENT_STREAMER', 'ngs\util\NgsComponentStreamer');

//---defining ngs utils file
NGS()->define('NGS_UTILS', 'ngs\util\NgsUtils');
//---defining ngs MySql Pdo file
NGS()->define('NGS_MYSQL_PDO_DRIVER', '\ngs\dal\connectors\MysqlPDO');

/*
|--------------------------------------------------------------------------
| DEFINING NGS DEFAULT EXCEPTIONS HANDLERS (loads/actions)
|--------------------------------------------------------------------------
*/
//---defining debug exception
NGS()->define('NGS_EXCEPTION_DEBUG', 'ngs\exceptions\DebugException');
//---defining Invalid User exception
NGS()->define('NGS_EXCEPTION_INVALID_USER', 'ngs\exceptions\InvalidUserException');
//---defining Invalid User exception
NGS()->define('NGS_EXCEPTION_NGS_ERROR', 'ngs\exceptions\NgsErrorException');
//---defining Invalid User exception
NGS()->define('NGS_EXCEPTION_NO_ACCESS', 'ngs\exceptions\NoAccessException');
//---defining Invalid User exception
NGS()->define('NGS_EXCEPTION_NOT_FOUND', 'ngs\exceptions\NotFoundException');
/*
|--------------------------------------------------------------------------
| DEFINING NGS MODULES
|--------------------------------------------------------------------------
*/
//---defining if modules enabled
NGS()->define('MODULES_ENABLE', TRUE);
//---defining modules dir
NGS()->define('MODULES_DIR', 'modules');
//---defining modules routing file
NGS()->define('NGS_MODULS_ROUTS', 'modules.json');

/*
|--------------------------------------------------------------------------
/*
|--------------------------------------------------------------------------
| DEFINING NGS DEFAULT ENGINES
|--------------------------------------------------------------------------
*/
NGS()->define('LESS_ENGINE', 'lib/less.php/Less.php');

/*
|--------------------------------------------------------------------------
| DEFINING SMARTY DIRS
|--------------------------------------------------------------------------
*/
NGS()->define('USE_SMARTY', TRUE);
//---defining smarty paths
NGS()->define('SMARTY_CACHE_DIR', 'cache');
NGS()->define('SMARTY_COMPILE_DIR', 'compile');


/*
|--------------------------------------------------------------------------
| DEFINING COMPONENTS
|--------------------------------------------------------------------------
*/
NGS()->define('IS_NGS_COMPONENT_MOD', false);
NGS()->define('IS_COMPONENT_TEMPLATE', false);
/*
|--------------------------------------------------------------------------
| DEFINING SOLR DEFAULT PARAMS
|--------------------------------------------------------------------------
*/

NGS()->define('BULK_UPDATE_LIMIT', 50);

NGS()->define('ELASTIC_BULK_LIMIT', 7500);