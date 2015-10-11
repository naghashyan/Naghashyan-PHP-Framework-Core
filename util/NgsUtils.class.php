<?php

/**
 * Helper wrapper class for php curl
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @year 2015
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

  class NgsUtils {

    public function getUniqueId() {
      return md5(uniqid().time().rand(0, 100000).microtime(true));
    }

    public function createObjectFromArray($arr, $trim = false) {

      $stdObj = NGS()->getDynObject();
      foreach ($arr as $key => $value) {
        $last = substr(strrchr($key, '.'), 1);
        if (!$last)
          $last = $key;
        $node = $stdObj;
        foreach (explode('.', $key) as $key2) {
          if (!isset($node->$key2)) {
            $node->$key2 = new \stdclass;
          }
          if ($key2 == $last) {
            if (is_string($value)) {
              if ($trim == true) {
                $node->$key2 = trim(htmlspecialchars(strip_tags($value)));
              } else {
                $node->$key2 = $value;
              }
            } else {
              $node->$key2 = $value;
            }

          } else {
            $node = $node->$key2;
          }
        }
      }
      return $stdObj;
    }

    public function isJson($string) {
      if (is_numeric($string)) {
        return false;
      }
      json_decode($string);
      return (json_last_error() == JSON_ERROR_NONE);
    }

  }

}
