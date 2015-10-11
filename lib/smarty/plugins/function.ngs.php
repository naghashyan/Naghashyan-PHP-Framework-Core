<?php

/**
 * Smarty plugin
 *
 * This plugin is only for Smarty3
 * @package Smarty
 * @subpackage PluginsFunction
 */

/**
 * Smarty {math} function plugin
 *
 * Type:     function<br>
 * Name:     nest<br>
 * Purpose:  helper function gor access global NGS Object
 * <br>
 *
 * @author   Levon Naghashyan <levon at naghashyan dot com>
 * @site http://naghashyan.com
 * @mail levon@naghashyan.com
 * @year 2015
 * @version 2.0.0
 * @param array $params parameters
 * @param object $template template object
 * @return render template|null
 */
function smarty_function_ngs($params, $template) {
  if (!isset($params['cmd'])) {
    trigger_error("NGS: missing 'cmd' parameter");
    return;
  }
  $ns = "";
  if(isset($params['ns'])){
    $ns = $params['ns'];
  }
  switch ($params['cmd']) {
    case 'get_js_out_dir' :
      $protocol = false;
      if(isset($params['protocol']) && $params['protocol'] == true){
        $protocol = true;
      }
      return NGS()->getPublicOutputHost($ns, $protocol)."/js";
      break;
    case 'get_css_out_dir' :
      $protocol = false;
      if(isset($params['protocol']) && $params['protocol'] == true){
        $protocol = true;
      }
      return NGS()->getPublicOutputHost($ns, $protocol)."/css";
      break;  
    case 'get_less_out_dir' :
      $protocol = false;
      if(isset($params['protocol']) && $params['protocol'] == true){
        $protocol = true;
      }
      return NGS()->getPublicOutputHost($ns, $protocol)."/less";
      break;    
    case 'get_template_dir' :
      return NGS()->getTemplateDir($ns);
      break;  
    case 'get_static_path' :
      $protocol = false;
      if(isset($params['protocol']) && $params['protocol'] == true){
        $protocol = true;
      }
      return NGS()->getHttpUtils()->getPublicHost($protocol, $ns);
      break;    
    case 'get_version' :
      return NGS()->getVersion();
      break;    
    default :
      break;
  }
}