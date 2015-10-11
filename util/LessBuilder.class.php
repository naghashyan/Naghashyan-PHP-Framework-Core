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
namespace ngs\framework\util {
  use ngs\framework\exception\NotFoundException;
  require_once (NGS()->getFrameworkDir()."/lib/less.php/Less.php");
  class LessBuilder extends AbstractBuilder {
      
    private $lessParser;  
      
    public function streamFile($module, $file) {

      if (NGS()->getEnvironment() == "production") {
        $filePath = realpath(NGS()->getPublicDir()."/".$file);
        if (file_exists($filePath) == false || fileatime($filePath) != fileatime($this->getBuilderFile())) {
          $this->build($file, true);
        }
        NGS()->getFileUtils()->sendFile($filePath, array("mimeType" => $this->getContentType(), "cache" => true));
        return;
      }
      $this->build($file, false);
    }

    public function build($file, $mode = false) {
      $files = $this->getBuilderArr($this->getBuilderJsonArr(), $file);
      if (count($files) == 0) {
        throw NGS()->getDebugException("Please add less files in builder");
      }
      $options = array();
      if ($mode) {
        $options["compress"] = true;
      }
      $this->lessParser = new \Less_Parser($options);
      $this->lessParser->parse('@NGS_PATH: "'.NGS()->getHttpUtils()->getHttpHost(true).'";');
      $this->lessParser->parse('@NGS_MODULE_PATH: "'.NGS()->getPublicHostByNS().'";');
      $this->setLessFiles($files);
      if($mode){
        $outFile = $this->getOutputDir()."/".$files["output_file"];
        touch($outFile, fileatime($this->getBuilderFile()));
        file_put_contents($outFile, $this->lessParser->getCss());exit;
        return true;
      }
      header('Content-type: '.$this->getContentType());
      echo $this->lessParser->getCss();
      exit ;

    }

    private function setLessFiles($files) {
      $importDirs = array();
      $lessFiles = array();
      foreach ($files["files"] as $value) {
        $modulePath = "";
        $module = "ngs";
        if ($value["module"] != null) {
          $modulePath = "/".$value["module"];
          $module = $value["module"];
        }
        $lessHost = NGS()->getHttpUtils()->getHttpHost(true).$modulePath."/less/";
        $lessDir = realpath(NGS()->getPublicDir($module)."/".NGS()->getDefinedValue("LESS_DIR"));
        $lessFilePath = realpath($lessDir."/".$value["file"]);
        if ($lessFilePath == false) {
          throw NGS()->getDebugException("Please add or check if correct less file in builder under section ".$value["file"]);
        }
        $importDirs[$lessFilePath] = $lessDir;
        $this->lessParser->parseFile($lessFilePath);
      }
      $this->lessParser->SetImportDirs($importDirs);
      return true;
    }

    public function getOutputDir() {
      $_outDir = NGS()->getPublicOutputDir()."/".NGS()->getDefinedValue("LESS_DIR");
      $outDir = realpath($_outDir);
      if ($outDir == false) {
        mkdir($_outDir, 0755, true);
        $outDir = realpath($_outDir);
      }
      return $outDir;
    }

    public function doDevOutput() {
      return true;
    }

    protected function getItemDir($module) {
      return NGS()->getCssDir($module);
    }

    protected function getBuilderFile() {
      return realpath(NGS()->getLessDir()."/builder.json");
    }

    protected function getContentType() {
      return "text/css";
    }

  }

}
