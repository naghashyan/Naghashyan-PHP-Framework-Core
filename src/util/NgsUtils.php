<?php

/**
 * Helper wrapper class for php curl
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @year 2015
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

namespace ngs\util;

class NgsUtils
{

    /**
     *
     * return unique Id
     *
     * @return string
     */
    public function getUniqueId(): string
    {
        try {
            return md5(uniqid('', true) . time() . random_int(0, 100000) . microtime(true));
        } catch (\Exception $ex) {
            return uniqid('ngs_', true);
        }

    }

    /**
     * The first letter of input string changes to Lower case
     *
     * @param string $str
     * @return string
     */
    public function lowerFirstLetter(string $str): string
    {
        $first = $str[0];
        $asciiValue = ord($first);
        if ($asciiValue >= 65 && $asciiValue <= 90) {
            $asciiValue += 32;
            return chr($asciiValue) . substr($str, 1);
        }
        return $str;
    }

    /**
     * @param array $arr
     * @param bool $trim
     * @return NgsDynamic
     */
    public function createObjectFromArray(array $arr, bool $trim = false): NgsDynamic
    {

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
                if ($key2 === $last) {
                    if (is_string($value)) {
                        if ($trim === true) {
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

    /**
     * @param string $jsonText
     * @return bool
     */
    public function isJson(string $jsonText): bool
    {
        if (is_numeric($jsonText)) {
            return false;
        }
        try {
            json_decode($jsonText, false, 512, JSON_THROW_ON_ERROR);
            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }


}
