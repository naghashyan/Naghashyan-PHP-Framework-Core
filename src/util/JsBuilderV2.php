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
 * @year 2019-2020
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
    use ngs\exceptions\NotFoundException;

    class JsBuilderV2 extends AbstractBuilder
    {
        /**
         * @param string $module
         * @param string $file
         * @throws DebugException
         */
        public function streamFile(string $module, string $file): void
        {
            if ($this->getEnvironment() === 'development') {
                $this->streamDevFile($module, $file);
                return;
            }
            parent::streamFile($module, $file);
        }

        public function streamDevFile(string $module, string $file): void
        {

            $jsFile = substr($file, stripos($file, NGS()->get('JS_DIR')) + strlen(NGS()->get('JS_DIR')) + 1);
            $realFile = realpath(NGS()->getJsDir($module) . '/' . $jsFile);
            if (file_exists($realFile)) {
                NGS()->getFileUtils()->sendFile($realFile, array('mimeType' => $this->getContentType(), 'cache' => false));
                return;
            }
            $matches = explode('/', $jsFile);
            $moduleJsDir = NGS()->getJsDir($matches[0]);
            if (!$moduleJsDir) {
                throw new DebugException($jsFile . " File not found");
            }
            unset($matches[0]);
            $jsFile = implode('/', $matches);
            $realFile = realpath($moduleJsDir . '/' . $jsFile);
            if ($realFile === false) {
                throw new DebugException($jsFile . " File not found");
            }
            NGS()->getFileUtils()->sendFile($realFile, array('mimeType' => $this->getContentType(), 'cache' => false));
        }

        /**
         * @param string $module
         * @param string $file
         * @return false|string
         */
        public function getOutputDir(string $module, string $file): string
        {
            $_outDir = NGS()->getPublicOutputDir() . '/' . NGS()->getDefinedValue('JS_DIR');
            $outDir = realpath($_outDir);
            if ($outDir === false) {
                if (!mkdir($_outDir, 0755, true) && !is_dir($_outDir)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $_outDir));
                }
                $outDir = realpath($_outDir);
            }
            return $outDir;
        }

        protected function doCompress($buf): string
        {
            return \ngs\lib\minify\ClosureCompiler::minify($buf);
        }

        protected function doDevOutput($files)
        {
            header('Content-type: text/javascript');
            foreach ($files['files'] as $value) {
                $module = '';
                if ($value['module'] !== null) {
                    $module = $value['module'];
                }
                $inputFile = NGS()->getHttpUtils()->getHttpHostByNs($module) . '/js/' . trim(str_replace('\\', '/', $value['file']));
            }
        }

        protected function getItemDir($module)
        {
            return NGS()->getJsDir($module);
        }

        protected function getBuilderFile()
        {
            return realpath(NGS()->getJsDir() . ' / builder.json');
        }

        protected function getEnvironment()
        {
            return NGS()->get('JS_BUILD_MODE');
        }

        protected function getContentType()
        {
            return 'text/javascript';
        }

    }

}
