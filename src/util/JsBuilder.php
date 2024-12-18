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
 * @year 2014-2023
 * @package ngs.framework.util
 * @version 4.5.0
 *
 * This file is part of the NGS package.
 *
 * @copyright Naghashyan Solutions LLC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace ngs\util;

class JsBuilder extends AbstractBuilder
{

  public function getOutputDir(): string
  {
    $_outDir = NGS()->getPublicOutputDir() . "/" . NGS()->getDefinedValue("JS_DIR");
    $outDir = realpath($_outDir);
    if ($outDir == false) {
      if (!mkdir($_outDir, 0755, true) && !is_dir($_outDir)) {
        throw new \RuntimeException(sprintf('Directory "%s" was not created', $_outDir));
      }
      $outDir = realpath($_outDir);
    }
    return $outDir;
  }

  protected function doCompress(string $buffer): string
  {
    return \ngs\lib\minify\ClosureCompiler::minify($buffer);
  }

  protected function doDevOutput(array $files)
  {
    header("Content-type: text/javascript");
    foreach ($files["files"] as $value) {
      $module = "";
      if ($value["module"] != null) {
        $module = $value["module"];
      }
      $inputFile = NGS()->getHttpUtils()->getHttpHostByNs($module) . "/js/" . trim(str_replace("\\", "/", $value["file"]));
      echo("document.write('<script type=\"text/javascript\" src=\"" . $inputFile . "\"></script>');\n\r");
    }
  }

  protected function getItemDir($module)
  {
    return NGS()->getJsDir($module);
  }

  protected function getBuilderFile()
  {
    return realpath(NGS()->getJsDir() . '/builder.json');
  }

  protected function getEnvironment(): string
  {
    return NGS()->get('JS_BUILD_MODE');
  }

  protected function getContentType()
  {
    return 'text/javascript';
  }

}
