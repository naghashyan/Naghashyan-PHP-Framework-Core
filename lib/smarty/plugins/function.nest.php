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
 * Purpose:  handle math computations in template
 * <br>
 *
 * @author   Levon Naghashyan <levon at naghashyan dot com>
 * @site http://naghashyan.com
 * @mail levon@naghashyan.com
 * @year 2012-2015
 * @version 2.0.0
 * @param array $params parameters
 * @param object $template template object
 * @return render template|null
 */
function smarty_function_nest($params, $template) {
    if (!isset($params['ns'])) {
        trigger_error("nest: missing 'ns' parameter");
        return;
    }

    if (!$template->tpl_vars["ns"]) {
        $template->tpl_vars["ns"] = $template->smarty->smarty->tpl_vars["ns"];
    }

    $nsValue = $template->tpl_vars["ns"]->value;
    $pmValue = $template->tpl_vars["pm"]->value;
    $namespace = $nsValue["inc"][$params["ns"]]["namespace"];

    $include_file = $nsValue["inc"][$params["ns"]]["filename"];

    $_tpl = $template->smarty->createTemplate($include_file, null, null, $nsValue["inc"][$params["ns"]]["params"]);
    foreach ($template->tpl_vars as $key => $tplVars) {
        $_tpl->assign($key, $tplVars);
    }
    $_tpl->assign("ns", $nsValue["inc"][$params["ns"]]["params"]);
    $_tpl->assign("pm", $pmValue);
    if ($_tpl->mustCompile()) {
        $_tpl->compileTemplateSource();
    }

    //$_tpl->renderTemplate();
    $_output = $_tpl->display();
    if (NGS()->isJsFrameworkEnable() && !NGS()->getHttpUtils()->isAjaxRequest()) {
        $jsonParams = $nsValue["inc"][$params["ns"]]["jsonParam"];
        $parentLoad = $nsValue["inc"][$params["ns"]]["parent"];
        $jsString = '<script type="text/javascript">';
        $jsString .= 'NGS.setNestedLoad("' . $parentLoad . '", "' . $namespace . '", ' . json_encode($jsonParams) . ')';
        $jsString .= '</script>';
        $_output = $jsString . $_output;
    }

    return $_output;
}

?>