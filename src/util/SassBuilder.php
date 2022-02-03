<?php
/**
 * Helper class for getting SASS files
 * have 3 general options connected with site mode (production/development)
 * 1. compress css files
 * 2. merge in one
 * 3. stream seperatly
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @year 2017-2020
 * @package ngs.framework.util
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

namespace ngs\util {

  use ngs\exceptions\DebugException;
  use ScssPhp\ScssPhp\Compiler;
  use ScssPhp\ScssPhp\Formatter\Crunched;

  class SassBuilder extends AbstractBuilder {

    private $sassParser;

        public function streamFile(string $module, string $file): void
        {
            if ($this->getEnvironment() === "production") {
                $filePath = realpath(NGS()->getPublicDir() . "/" . $file);
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
          if (count($files) == 0){
              throw new DebugException("Please add sass files in builder");
          }
          $this->sassParser = new Compiler();
          $this->sassParser->addImportPath(function ($path) {
              if (strpos($path, '@ngs-cms') !== false){
                  return NGS()->getSassDir('ngs-cms') . '/' . str_replace('@ngs-cms/', '', $path) . '.scss';
              }

              if (strpos($path, '@'.NGS()->get('NGS_CMS_NS')) !== false){
                  return NGS()->getSassDir(NGS()->get('NGS_CMS_NS')) . '/' . str_replace('@'.NGS()->get('NGS_CMS_NS').'/', '', $path) . '.scss';
              }
              return NGS()->getSassDir() . '/' . $path. '.scss';
          });

          if ($mode){
              $this->sassParser->setFormatter(Crunched::class);
          }
          $this->sassParser->setVariables(array(
              'NGS_PATH' => NGS()->getHttpUtils()->getHttpHost(true),
              'NGS_MODULE_PATH' => NGS()->getPublicHostByNS()
          ));
          if ($mode){
              $outFileName = $files["output_file"];
              if ($this->getOutputFileName() != null){
                  $outFileName = $this->getOutputFileName();
              }
              $outFile = $this->getOutputDir() . "/" . $outFileName;
              touch($outFile, fileatime($this->getBuilderFile()));
              file_put_contents($outFile, $this->getCss($files));
              return true;
          }
          header('Content-type: ' . $this->getContentType());
          echo $this->getCss($files);
          exit;

      }

    private function getCss($files) {
      $importDirs = array();
      $sassFiles = array();
      $sassStream = "";
      foreach ($files["files"] as $value){
        $modulePath = "";
        $module = "ngs";
        if ($value["module"] != null){
          $modulePath = $value["module"];
          $module = $value["module"];
        }
        $sassHost = NGS()->getHttpUtils()->getHttpHostByNs($modulePath) . "/sass/";
        $sassFilePath = realpath(NGS()->getSassDir($module) . "/" . $value["file"]);
        if ($sassFilePath == false){
          throw new DebugException("Please add or check if correct sass file in builder under section " . $value["file"]);
        }
        $sassStream .= file_get_contents($sassFilePath);

      }
      return $this->sassParser->compile($sassStream);
    }

    public function getOutputDir() {
      $_outDir = NGS()->getPublicOutputDir() . "/" . NGS()->getDefinedValue("SASS_DIR");
      $outDir = realpath($_outDir);
      if ($outDir == false){
        mkdir($_outDir, 0755, true);
        $outDir = realpath($_outDir);
      }
      return $outDir;
    }

    protected function getOutputFileName() {
      return null;
    }

    public function doDevOutput() {
      return true;
    }

    protected function getItemDir($module) {
      return NGS()->getCssDir($module);
    }

    protected function getBuilderFile() {
      return realpath(NGS()->getSassDir() . "/builder.json");
    }

    protected function getEnvironment() {
      return NGS()->get("SASS_BUILD_MODE");
    }

    protected function getContentType() {
      return "text/css";
    }

  }

}
