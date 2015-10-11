<?php /**
 * Helper class that works with files
 * have 3 general function
 * 1. send file to user using remote or local file
 * 2. read local file dirs
 * 3. upload files
 *
 * @author Levon Naghashyan
 <levon@naghashyan.com>
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
  class FileUtils {
    
    
    public function streamFile($module, $file){
      $filePath = realpath(NGS()->getPublicDir($module)."/".$file);
      if ($filePath == false) {
        throw NGS()->getNotFoundException();
      }
      $options = array();
      if(NGS()->getEnvironment() != "production"){
        $options["cache"] = false;
      }
      $this->sendFile($filePath, $options);
    }
    
    //-----------------------------Streamer Part---------//
    /**
     * send file to user
     * set correct headers and stream file to user
     * check if file from local or remote
     * if local using 3 streamer option
     * standart - php file open and read
     * xAccelRedirect - nginx streamer
     * xSendfile - apache file streamer module
     * if remote
     * read remote file and stream to user
     *
     * @param string $file - full file path
     * @param array $options - (filename-custom file name, mimeType-custom mime type of file, contentLength-custom file size,
     * cache true|false, remoteFile-is file remote, streamer - for local files,headers-addition headers)
     *
     * @return files bytes
     */
    public function sendFile($file, $options = array()) {
      if (!is_string($file)) {
        throw NGS()->getNotFoundException();
      }

      $defaultOptions = array("filename" => null, "mimeType" => null, "contentLength" => null, "cache" => true, "remoteFile" => false, "streamer" => "standart", "headers" => array());
      $options = array_merge($defaultOptions, $options);
      if ($options["remoteFile"] == false) {
        if (strpos($file, "https://") !== false || strpos($file, "http://") !== false || strpos($file, "ftp://") !== false) {
          $options["remoteFile"] = true;
        }
      }
      if ($options["remoteFile"] == true) {
        $options["remoteFileData"] = get_headers($file, true);
      }else{
        $fileSize = filesize($file);
        $fileSizeInMb = round($fileSize/1024/1024);
        //check if file size greater then 20mb then send via file open streamer
        if($options["streamer"] == "standart" && $fileSizeInMb > 20){
          $options["streamer"] = "large_file";
        }
      }
      //check if user set file name than send user's filename if not get from file
      if ($options["filename"] != null) {
        header('Content-Disposition: '.$options["filename"]);
      }
      //check if user set mimetype than send user if not get from file
      if ($options["mimeType"] == null) {
        if ($options["remoteFile"] === false) {
          $fileInfo = pathinfo($file);
          header('Content-type: '.MimeTypeUtils::getMimeTypeByExt($fileInfo["extension"]));
        } else {
          header('Content-type: '.$options["remoteFileData"]["Content-Type"]);
        }
      } else {
        header('Content-type: '.$options["mimeType"]);
      }
      //check if content lengh if null and check if we
      //should use php file stream than we should add
      //file size in headers else use user defined file size
      if ($options["contentLength"] == null) {
        if ($options["streamer"] == "standart" && $options["remoteFile"] === false) {
          header('Content-Length: '.$fileSize);
        } else {
          header('Content-Length: '.$options["remoteFileData"]["Content-Length"]);
        }
      } else {
        header('Content-Length: '.$options["contentLength"]);
      }

      //send cache headers
      $this->sendCacheHeaders($file, $options);
      header('X-Pad: avoid browser bug');
      header("X-Powered-By: ngs");
      foreach ($options["headers"] as $key => $value) {
        header($value);
      }
      if ($options["remoteFile"] === true) {
        $this->doStreamFromUrl($file);
        return;
      }
      $this->doStreamFile(realpath($file), $options["streamer"]);
    }

    /**
     * send cache headers
     *
     *
     * @param string $file - full file path
     * @param array $options - (filename-custom file name, mimeType-custom mime type of file, contentLength-custom file size,
     * cache true|false, remoteFile-is file remote, streamer - for local files,headers-addition headers)
     *
     * @return files bytes
     */
    protected function sendCacheHeaders($file, $options) {
      //if cache is true that check if browser have that file.
      if ($options["cache"]) {
        $etag = md5_file($file);
        if ($options["remoteFile"] == true) {
          $lastModifiedTime = $options["remoteFileData"]["Last-Modified"];
          header("Last-Modified: ".$lastModifiedTime);
          if ($options["remoteFileData"]["Etag"]) {
            $etag = $options["remoteFileData"]["Etag"];
          }
        } else {
          $lastModifiedTime = filemtime($file);
          header("Last-Modified: ".gmdate("D, d M Y H:i:s", $lastModifiedTime)." GMT");
        }

        header("Etag: ".$etag);
        if (@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $lastModifiedTime || (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag)) {
          header("HTTP/1.1 304 Not Modified");
          return true;
        }

        header("Cache-Control: private, max-age=10800, pre-check=10800");
        header("Pragma: private");
      } else {
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
      }

    }

    /**
     * read and send file bytes
     *
     *
     * @param string $streamFile - full file path
     * @param string $streamer - streamer mode
     *
     * @return files bytes
     */
    protected function doStreamFile($streamFile, $streamer) {
      switch ($streamer) {
        case 'xAccelRedirect' :
          header('X-Accel-Redirect: '.$streamFile);
          break;
        case 'xSendfile' :
          header('X-Sendfile: '.$streamFile);
          break;
        case 'standart' :
          readfile($streamFile);exit;
          break;  
        case 'large_file' :
          $this->_sendFile($streamFile);
          break;   
        default :
          $this->_sendFile($streamFile);
          break;
      }

      exit ;
    }

    protected function _sendFile($filePath) {
      //turn off output buffering to decrease cpu usage
      $this->cleanAll();

      // required for IE, otherwise Content-Disposition may be ignored
      if (ini_get('zlib.output_compression')) {
        ini_set('zlib.output_compression', 'Off');
      }
      /*
       if ($withDisposition) {
       header('Content-Disposition: attachment; filename="'.$this->disposition.'"');
       }*/
      header('Accept-Ranges: bytes');

      // multipart-download and download resuming support
      if (isset($_SERVER['HTTP_RANGE'])) {
        list($a, $range) = explode("=", $_SERVER['HTTP_RANGE'], 2);
        list($range) = explode(",", $range, 2);
        list($range, $range_end) = explode("-", $range);
        $range = intval($range);
        if (!$range_end) {
          $range_end = $size - 1;
        } else {
          $range_end = intval($range_end);
        }

        $new_length = $range_end - $range + 1;
        header("HTTP/1.1 206 Partial Content");
        header("Content-Length: $new_length");
        header("Content-Range: bytes $range-$range_end/$size");
      } else {
        $new_length = filesize($filePath);
      }
      $chunksize = 40960;
      $sec = 0.01;
      $bytes_send = 0;
      $file = @fopen($filePath, 'r');
      if ($file) {
        if (isset($_SERVER['HTTP_RANGE'])) {
          fseek($file, $range);
        }

        while (!feof($file) && (!connection_aborted()) && ($bytes_send < $new_length)) {
          $buffer = fread($file, $chunksize);
          echo($buffer);
          //echo($buffer); // is also possible
          flush();
          usleep($sec * 1000000);
          $bytes_send += strlen($buffer);
        }
        fclose($file);
      } else {
        throw new \Exception('Error - can not open file.');
      }
      die();
    }

    /**
     * read and send remote file bytes
     *
     *
     * @param string $url
     *
     * @return files bytes
     */
    protected function doStreamFromUrl($url) {
      $file = @fopen($url, "rb");
      if ($file) {
        while (!feof($file)) {
          print(fread($file, 2048 * 8));
          flush();
          if (connection_status() != 0) {
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
    private function cleanAll() {
      while (ob_get_level()) {
        ob_end_clean();
      }
    }

  }

}
