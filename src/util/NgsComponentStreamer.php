<?php
/**
 * Helper class for getting components templates files
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @year 2022
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

namespace ngs\util;

use ngs\exceptions\DebugException;
use ngs\templater\NgsSmartyTemplater;

class NgsComponentStreamer
{
  /**
   * @param string $module
   * @param string $file
   * @throws DebugException
   */
  public function streamFile(string $module, string $file): void
  {

    try {
      $fullPath = $this->getComponentFile($module, $file);
      if (!$fullPath) {
        $this->sendRealFile($module, $file);
        return;
      }
      NGS()->define('IS_COMPONENT_TEPLATE', true);
      $ngsTemplater = NGS()->getTemplateEngine();
      $smarty = $ngsTemplater->getSmartyTemplater();
      $smarty->left_delimiter = '{{';
      $smarty->right_delimiter = '}}';
      $this->beforeDisplay($smarty);
      $smarty->assign('test', 'test');
      $smarty->display($fullPath);
      exit;
    } catch (\Exception $exception) {
      var_dump($fullPath);
      var_Dump(NGS()->getTemplateDir($module) . '/' . $fileParts['dirname'] . '/' . $fileName, $exception->getMessage());
      exit;
      $this->sendRealFile($module, $file);
    }
  }


  protected function beforeDisplay(NgsSmartyTemplater $smarty): void
  {
  }

  protected function getComponentFile(string $module, string $file): ?string
  {
    $fileParts = pathinfo($file);
    $fileName = $fileParts['filename'] . '.component.tpl';
    $fullPath = realpath(NGS()->getTemplateDir($module) . '/' . $fileParts['dirname'] . '/' . $fileName);
    if (!$fullPath) {
      return null;
    }
    return $fullPath;
  }

  private function sendRealFile(string $module, string $file): void
  {
    $fullPath = realpath(NGS()->getPublicDir($module) . '/' . $module . '/' . $file);
    NGS()->getFileUtils()->sendFile($fullPath, ['mimeType' => $this->getContentType()]);
  }

  private function getContentType(): string
  {
    return 'text/html';
  }


}