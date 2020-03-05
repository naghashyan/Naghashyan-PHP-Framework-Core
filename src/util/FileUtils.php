<?php
/**
 * Helper class that works with files
 * have 3 general function
 * 1. send file to user using remote or local file
 * 2. read local file dirs
 * 3. upload files
 *
 * @author Levon Naghashyan
 * <levon@naghashyan.com>
 * @site https://naghashyan.com
 * @year 2014-2020
 * @package ngs.framework.util
 * @version 3.8.0
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

  use Exception;
  use ngs\exceptions\DebugException;

  class FileUtils {

    /**
     * @param string $module
     * @param string $file
     * @throws DebugException
     */
    public function streamFile(string $module, string $file): void {
      $filePath = realpath(NGS()->getPublicDir($module) . '/' . $file);
      if ($filePath === false){
        throw new DebugException('File Not Found');
      }
      $options = array();
      if (NGS()->getEnvironment() !== 'production'){
        $options['cache'] = false;
      }
      try{
        $this->sendFile($filePath, $options);
      } catch (DebugException $e){
      }
    }

    //-----------------------------Streamer Part---------//

    /**
     * send file to user
     * set correct headers and stream file to user
     * check if file from local or remote
     * if local using 3 streamer option
     * php - php file open and read
     * xAccelRedirect - nginx streamer
     * xSendfile - apache file streamer module
     * if remote
     * read remote file and stream to user
     *
     * @param string $file - full file path
     * @param array $options - (filename-custom file name, mimeType-custom mime type of file, contentLength-custom file size,
     * cache true|false, remoteFile-is file remote, streamer - for local files,headers-addition headers)
     *
     * @throws DebugException
     * @throws Exception
     */
    public function sendFile(string $file, array $options = array()): void {
      $defaultOptions = ['filename' => null, 'mimeType' => null, 'contentLength' => null,
        'cache' => true, 'remoteFile' => false,
        'streamer' => 'php', 'realPath' => null, 'headers' => []];
      $options = array_merge($defaultOptions, $options);
      $streamPath = $file;
      $realPath = $file;
      if ($options['realPath'] !== null){
        $realPath = $options['realPath'];
      }

      if (!is_string($realPath) || !is_file($realPath)){
        throw new DebugException($realPath . 'File Not Found');
      }
      if ($options['remoteFile'] === false){
        if (strpos($realPath, 'https://') !== false || strpos($realPath, 'http://') !== false
          || strpos($realPath, 'ftp://') !== false){
          $options['remoteFile'] = true;
        }
      }
      $fileSize = 0;
      if ($options['remoteFile'] === true){
        $options['remoteFileData'] = get_headers($realPath, true);
      } else{
        $fileSize = filesize($realPath);
        $fileSizeInMb = round($fileSize / 1024 / 1024);
        //check if file size greater then 20mb then send via file open streamer
        if ($options['streamer'] === 'php' && $fileSizeInMb > 20){
          $options['streamer'] = 'large_file';
        }
      }
      //check if user set file name than send user's filename if not get from file
      if ($options['filename'] !== null){
        header('Content-Disposition: ' . $options['filename']);
      }
      //check if user set mimetype than send user if not get from file
      if ($options['mimeType'] === null){
        if ($options['remoteFile'] === false){
          $fileInfo = pathinfo($realPath);
          header('Content-type: ' . MimeTypeUtils::getMimeTypeByExt($fileInfo['extension']));
        } else{
          header('Content-type: ' . $options['remoteFileData']['Content-Type']);
        }
      } else{
        header('Content-type: ' . $options['mimeType']);
      }
      //check if content lengh if null and check if we
      //should use php file stream than we should add
      //file size in headers else use user defined file size
      if ($options['contentLength'] === null){
        if ($options['remoteFile'] === false){
          header('Content-Length: ' . $fileSize);
        } else{
          header('Content-Length: ' . $options['remoteFileData']['Content-Length']);
        }
      } else{
        header('Content-Length: ' . $options['contentLength']);
      }

      //send cache headers
      $this->sendCacheHeaders($realPath, $options);
      header('X-Pad: avoid browser bug');
      header('X-Powered-By: ngs');
      foreach ($options['headers'] as $key => $value){
        header($value);
      }
      if ($options['remoteFile'] === true){
        $this->doStreamFromUrl($realPath);
        return;
      }
      $this->doStreamFile($streamPath, $options['streamer']);
    }

    /**
     * send cache headers
     *
     *
     * @param string $file - full file path
     * @param array $options - (filename-custom file name, mimeType-custom mime type of file, contentLength-custom file size,
     * cache true|false, remoteFile-is file remote, streamer - for local files,headers-addition headers)
     *
     */
    protected function sendCacheHeaders(string $file, array $options): void {
      //if cache is true that check if browser have that file.
      if ($options['cache']){
        $etag = md5_file($file);
        if ($options['remoteFile'] === true){
          $lastModifiedTime = $options['remoteFileData']['Last-Modified'];
          header('Last-Modified: ' . $lastModifiedTime);
          if ($options['remoteFileData']['Etag']){
            $etag = $options['remoteFileData']['Etag'];
          }
        } else{
          $lastModifiedTime = filemtime($file);
          header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModifiedTime) . ' GMT');
        }

        header('Etag: ' . $etag);
        if ((isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && @strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) === $lastModifiedTime)
          || (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag)){
          header('HTTP/1.1 304 Not Modified');
          return;
        }

        header('Cache-Control: private, max-age=10800, pre-check=10800');
        header('Pragma: private');
      } else{
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
      }

    }

    /**
     * read and send file bytes
     *
     *
     * @param string $streamFile - full file path
     * @param string $streamer - streamer mode
     *
     * @throws Exception
     */
    protected function doStreamFile($streamFile, $streamer): void {
      switch ($streamer){
        case 'xAccelRedirect' :
          header('X-Accel-Redirect: ' . $streamFile);
          break;
        case 'xSendfile' :
          header('X-Sendfile: ' . $streamFile);
          break;
        case 'php' :
          readfile($streamFile);
          break;
        default :
          $this->_sendFile($streamFile);
          break;
      }
    }

    /**
     * @param $filePath
     * @throws Exception
     */
    protected function _sendFile($filePath): void {
      $this->cleanAll();

      if (ini_get('zlib.output_compression')){
        ini_set('zlib.output_compression', 'Off');
      }
      header('Accept-Ranges: bytes');
      $size = filesize($filePath);
      if (isset($_SERVER['HTTP_RANGE'])){
        [$a, $range] = explode('=', $_SERVER['HTTP_RANGE'], 2);
        [$range] = explode(',', $range, 2);
        [$range, $range_end] = explode('-', $range);
        $range = (int)$range;
        if (!$range_end){
          $range_end = $size - 1;
        } else{
          $range_end = (int)$range_end;
        }

        $new_length = $range_end - $range + 1;
        header('HTTP/1.1 206 Partial Content');
        header('Content-Length: $new_length');
        header('Content-Range: bytes ' . ($range - $range_end) . '/' . $size);
      } else{
        $new_length = filesize($filePath);
      }
      $chunksize = 40960;
      $sec = 0.01;
      $bytes_send = 0;
      $file = @fopen($filePath, 'rb');
      if ($file){
        if (isset($_SERVER['HTTP_RANGE'])){
          fseek($file, $range);
        }

        while (!feof($file) && (!connection_aborted()) && ($bytes_send < $new_length)){
          $buffer = fread($file, $chunksize);
          echo($buffer);
          flush();
          usleep($sec * 1000000);
          $bytes_send += strlen($buffer);
        }
        fclose($file);
      } else{
        throw new Exception('Error - can not open file.');
      }
      die();
    }

    /**
     * read and send remote file bytes
     *
     *
     * @param string $url
     *
     */
    protected function doStreamFromUrl(string $url): void {
      $file = @fopen($url, 'rb');
      if ($file){
        while (!feof($file)){
          print(fread($file, 2048 * 8));
          flush();
          if (connection_status() !== 0){
            @fclose($file);
            die();
          }
        }
        @fclose($file);
      }
    }

    /**
     * clean all buffers
     */
    private function cleanAll(): void {
      while (ob_get_level()){
        ob_end_clean();
      }
    }

  }

}
