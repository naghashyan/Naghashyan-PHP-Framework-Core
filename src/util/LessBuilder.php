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
 * @year 2014-2016
 * @package ngs.framework.util
 * @version 3.1.0
 * 
 * This file is part of the NGS package.
 *
 * @copyright Naghashyan Solutions LLC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */
namespace ngs\util {

  use ngs\exceptions\DebugException;

  class LessBuilder extends AbstractBuilder {
      
    private $lessParser;  
      
    public function streamFile($module, $file) {
      if ($this->getEnvironment() == "production") {
        $filePath = realpath(NGS()->getPublicDir()."/".$file);
        if (!$filePath) {
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
        throw new DebugException("Please add less files in builder");
      }
      $options = array();
      if ($mode) {
        $options["compress"] = true;
      }
      $this->lessParser = new \Less_Parser($options);
      $this->lessParser->parse('@NGS_PATH: "'.NGS()->getHttpUtils()->getHttpHost(true).'";@NGS_MODULE_PATH: "'.NGS()->getPublicHostByNS().'";');
      $this->setLessFiles($files);
      if($mode){
        $outFileName = $files["output_file"];
        if($this->getOutputFileName() != null){
          $outFileName = $this->getOutputFileName();
        }
        $outFile = $this->getOutputDir()."/".$outFileName;
        touch($outFile, fileatime($this->getBuilderFile()));
        file_put_contents($outFile, $this->lessParser->getCss());
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
          $modulePath = $value["module"];
          $module = $value["module"];
        }
        $lessHost = NGS()->getHttpUtils()->getHttpHostByNs($modulePath)."/less/";
        $lessDir = realpath(NGS()->getPublicDir($module)."/".NGS()->getDefinedValue("LESS_DIR"));
        $lessFilePath = realpath($lessDir."/".$value["file"]);
        if ($lessFilePath == false) {
          throw new DebugException("Please add or check if correct less file in builder under section ".$value["file"]);
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
    
    protected function getOutputFileName(){
      return null;
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
    protected function getEnvironment(){
      return NGS()->get("LESS_BUILD_MODE");
    }

    protected function getContentType() {
      return "text/css";
    }

  }

}
