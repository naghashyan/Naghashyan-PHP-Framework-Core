<?php
/**
 * Helper class for getting js files
 * have 3 general options connected with site mode (production/development)
 * 1. compress css files
 * 2. merge in one
 * 3. stream seperatly
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @year 2014-2015
 * @package ngs.framework.util
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
namespace ngs\framework\util {
  use ngs\framework\exception\NotFoundException;
  class CssBuilder extends AbstractBuilder {

    protected function doBuild($file) {

      $files = $this->getBuilderArr(json_decode(file_get_contents($this->getBuilderFile())), $file);
      if (!$files) {
        return;
      }
      $outDir = $this->getOutputDir();
      $buf = "";
      foreach ($files["files"] as $value) {
        $module = "";
        if ($value["module"] == null) {
          $module = "ngs";
        }
        $inputFile = realpath(NGS()->getCssDir($module)."/".trim($value["file"]));
        if (!$inputFile) {
          throw NGS()->getNotFoundException($filePath." not found");
        }
        $buf .= file_get_contents($inputFile)."\n\r";
      }

      if ($files["compress"] == true) {
        $buf = $this->doCompress($buf);
      }
      touch($outDir."/".$files["output_file"], fileatime($this->getBuilderFile()));
      file_put_contents($outDir."/".$files["output_file"], $buf);
    }

    protected function customBufferUpdates($buffer) {
      return str_replace(array("@NGS_PATH", "@NGS_MODULE_PATH"), array(NGS()->getHttpUtils()->getHttpHost(true), NGS()->getPublicHostByNS()), $buffer);
    }

    public function getOutputDir() {
      $_outDir = NGS()->getPublicOutputDir()."/".NGS()->getDefinedValue("CSS_DIR");
      $outDir = realpath($_outDir);
      if ($outDir == false) {
        mkdir($_outDir, 0755, true);
        $outDir = realpath($_outDir);
      }
      return $outDir;
    }

    protected function doCompress($buf) {
      return \ngs\framework\lib\minify\CssCompressor::process($buf);
    }

    protected function doDevOutput($files) {
      header('Content-type: text/css');
      foreach ($files["files"] as $value) {
        $module = "";
        if ($value["module"] != null) {
          $module = "/".$value["module"];
        }
        $inputFile = NGS()->getHttpUtils()->getHttpHost(true).$module."/devout/css/".trim($value["file"]);
        echo '@import url("'.$inputFile.'");';
      }
    }

    protected function getItemDir($module) {
      return NGS()->getCssDir($module);
    }

    protected function getBuilderFile() {
      return realpath(NGS()->getCssDir()."/builder.json");
    }

    protected function getContentType() {
      return "text/css";
    }

  }

}
