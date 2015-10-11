<?php
/**
 * Smarty plugin
 *
 * This plugin is only for Smarty3
 * @package Smarty
 * @subpackage PluginsFunction
 */

/**
 * Smarty {get_link} function plugin
 *
 * Type:     function<br>
 * Name:     get link<br>
 * Purpose:  get generated link
 * <br>
 * 
 * @author   Levon Naghashyan <levon at naghashyan dot com>
 * @param array $params parameters
 * @param object $template template object
 * @return render template|null
 */
function smarty_function_get_link($params, $template){
	
		if($params["type"] == "album"){
			$link = "/album/".strtolower(str_replace(" ", "+", $params["artist"]))."/".strtolower(str_replace(" ", "+", $params["album"]));
		}
		
    return $link;
} 

?>