<?php
/**
 * Helper class for getting js files
 * have 3 general options connected with site mode (production/development)
 * 1. compress js files
 * 2. merge in one
 * 3. stream seperatly
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @year 2014-2015
 * @package ngs.framework.util
 * @version 2.1.0
 *
 * This file is part of the NGS package.
 *
 * @copyright Naghashyan Solutions LLC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */
namespace ngs\framework\util {
  use ngs\framework\exceptions\NotFoundException;

  class JsBuilder extends AbstractBuilder {

    public function getOutputDir() {
      $_outDir = NGS()->getPublicOutputDir()."/".NGS()->getDefinedValue("JS_DIR");
      $outDir = realpath($_outDir);
      if ($outDir == false) {
        mkdir($_outDir, 0755, true);
        $outDir = realpath($_outDir);
      }
      return $outDir;
    }

    protected function doCompress($buf) {
      return \ngs\framework\lib\minify\ClosureCompiler::minify($buf);
    }

    protected function doDevOutput($files) {
      header("Content-type: text/javascript");
      foreach ($files["files"] as $value) {
        $module = "";
        if ($value["module"] != null) {
          $module = $value["module"];
        }
        $inputFile = NGS()->getHttpUtils()->getHttpHostByNs($module)."/js/".trim($value["file"]);
        echo("document.write('<script type=\"text/javascript\" src=\"".$inputFile."\"></script>');\n\r");
      }
    }

    protected function getItemDir($module) {
      return NGS()->getJsDir($module);
    }

    protected function getBuilderFile() {
      return realpath(NGS()->getJsDir()."/builder.json");
    }

    protected function getContentType() {
      return "text/javascript";
    }

  }

}
