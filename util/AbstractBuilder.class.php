<?php
/**
 * Helper Abstract class for standart ngs builders
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
  abstract class AbstractBuilder {

    private $builderJsonArr = array();

    public function streamFile($module, $file) {
      if (NGS()->getEnvironment() == "production") {
        
        $filePath = realpath(NGS()->getPublicDir()."/".$file);
        if (strpos($file, NGS()->getDefinedValue("PUBLIC_OUTPUT_DIR")) === false) {
          if (file_exists($filePath) == false) {
            throw NGS()->getNotFoundException($filePath + " NOT FOUND");
          }
        } elseif (file_exists($filePath) == false || fileatime($filePath) != fileatime($this->getBuilderFile())) {
          $this->build($file);
        }
        NGS()->getFileUtils()->sendFile($filePath, array("mimeType" => $this->getContentType(), "cache" => true));
        return;
      }
      if (strpos($file, "devout") !== false) {
        $realFile = substr($file, strpos($file, "/") + 1);
        $realFile = realpath(NGS()->getPublicDir($module)."/".$realFile);
        if ($realFile == null) {
          throw NGS()->getDebugException($file." not found");
        }
        $buffer = file_get_contents($realFile);
        $buffer = $this->customBufferUpdates($buffer);
        header('Content-type: '.$this->getContentType());
        echo $buffer;
        return;
      }
      $realFile = realpath(NGS()->getPublicDir($module)."/".$file);
      if (file_exists($realFile)) {
        NGS()->getFileUtils()->sendFile($realFile, array("mimeType" => $this->getContentType(), "cache" => false));
        return;
      }
      
      $files = $this->getBuilderArr($this->getBuilderJsonArr(), $file);
      if (count($files) == 0) {
        throw NGS()->getDebugException("Please add file in builder under section ".$file);
      }
      $this->doDevOutput($files);
    }

    /**
     * get js files from builders json array by filename
     *
     *
     * @param array $builders
     * @param string $file - request file name
     *
     * @return array builder
     */
    protected function getBuilderArr($builders, $file = null) {
      $tmpArr = array();
      foreach ($builders as $key => $value) {
        if (strpos($file, $value->output_file) === false) {
          $builders = null;
          if (isset($value->builders)) {
            $builders = (array)$value->builders;
            $tempArr = $this->getBuilderArr($builders, $file);
            if ($tempArr) {
              return $tempArr;
            } else {
              continue;
            }
          } else {
            continue;
          }
          $tmpArr = array();
          $tmpArr["output_file"] = (string)$value->output_file;
          $tmpArr["debug"] = false;
          if(isset($value->compress)){
            $tmpArr["compress"] = $value->compress;
          }
          if(isset($value->type)){
            $tmpArr["type"] = $value->type;
          }
          $tmpArr["files"] = (array)$value->files;
        } else {
          $tmpArr = array();
          $tmpArr["output_file"] = (string)$value->output_file;
          $tmpArr["debug"] = false;
          if(isset($value->compress)){
            $tmpArr["compress"] = $value->compress;
          }
          if(isset($value->type)){
            $tmpArr["type"] = $value->type;
          }
          $tmpArr["files"] = array();
          if (isset($value->builders) && is_array($value->builders)) {
            foreach ($value->builders as $builder) {
              if (!is_array($builder)) {
                $builder = array($builder);
              }
              $tempArr = $this->getBuilderArr($builder, $builder[0]->output_file);
              if (isset($tempArr["files"])) {
                $tmpArr["files"] = array_merge($tmpArr["files"], $tempArr["files"]);
              }
            }
          } else {
            $module = NGS()->getModulesRoutesEngine()->getModuleNS();
            if (isset($value->module)) {
              $module = $value->module;
            }
            if ($module == NGS()->getModulesRoutesEngine()->getDefaultNS()) {
              $module = null;
            }
            $type = null;
            if (isset($value->type)) {
              $type = $value->type;
            }
            $tmpFileArr = array();
            foreach ((array)$value->files as $file) {
              $_tmpArr = array();
              $_tmpArr["module"] = $module;
              $_tmpArr["file"] = $file;
              $_tmpArr["type"] = $type;
              $tmpFileArr[] = $_tmpArr;
            }
            $tmpArr["files"] = $tmpFileArr;
          }
        }
      }
      return $tmpArr;
    }

    protected function build($file) {
      
      $files = $this->getBuilderArr($this->getBuilderJsonArr(), $file);
      if (!$files) {
        return;
      }
      $outDir = $this->getOutputDir();
      $buffer = "";
      foreach ($files["files"] as $value) {
        $module = "";
        if ($value["module"] == null) {
          $module = "ngs";
        }
        $inputFile = realpath($this->getItemDir($module)."/".trim($value["file"]));
        if (!$inputFile) {
          throw NGS()->getNotFoundException($this->getItemDir($module)."/".trim($value["file"])." not found");
        }
        $buffer .= file_get_contents($inputFile)."\n\r";
      }
      $buffer = $this->customBufferUpdates($buffer);
      if ($files["compress"] == true) {
        $buffer = $this->doCompress($buffer);
      }
      $outFile = $this->getOutputDir()."/".$files["output_file"];
      //set file time same with builder.json
      touch($outFile, fileatime($this->getBuilderFile()));
      file_put_contents($outFile, $buffer);
    }

    protected function customBufferUpdates($buffer) {
      return $buffer;
    }

    abstract protected function getItemDir($module);

    abstract protected function getBuilderFile();

    public function getBuilderJsonArr() {
      if (count($this->builderJsonArr) > 0) {
        return $this->builderJsonArr;
      }
      return $this->builderJsonArr = json_decode(file_get_contents($this->getBuilderFile()));
    }

    abstract protected function getContentType();
    

  }

}
