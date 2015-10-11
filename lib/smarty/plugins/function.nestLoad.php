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
 * Name:     nestLoad<br>
 * Purpose:  handle math computations in template
 * <br>
 * 
 * @author   Levon Naghashyan <levon at naghashyan dot com>
 * @param array $params parameters
 * @param object $template template object
 * @return render template|null
 */
function smarty_function_nestLoad($params, $template) {

	if (!isset($params['ns'])) {
		$template->trigger_error("nest: missing 'ns' parameter");
		return;
	}

	if (!isset($params['load'])) {
		$template->trigger_error("nest: missing 'load' parameter");
		return;
	}

	if (!isset($params['args'])) {
		$template->trigger_error("nest: missing 'args' parameter");
		return;
	}


	$loadArr = array();
	$loadArr["load"] = $params['load'];
	$loadArr["args"] = $params['args'];
	$loadArr["loads"] = array();

	$nsValue = $template->tpl_vars["ns"]->value;
	$pmValue = $template->tpl_vars["pm"]->value;
	$loadObj = $nsValue["_cl"];
	$loadObj->nest($params["ns"], $loadArr);
	$loadParams = $loadObj->getParams();
	$loadParams = $loadParams["inc"][$params["ns"]];
	
	$include_file = $loadParams["filename"];
	$_tpl = $template->smarty->createTemplate($include_file, null, null, $loadParams["params"]);
	foreach ($template->smarty->smarty->tpl_vars as $key => $tplVars) {
		$_tpl->assign($key, $tplVars);
	}
	$_tpl->assign("ns", $loadParams["params"]);
	$_tpl->assign("pm", $pmValue);
	
	if ($_tpl->mustCompile()) {
		$_tpl->compileTemplateSource();
	}
	$_output = $_tpl->display();

	return $_output;
}
?>