<?php
/**
 * FormValidator class contains utility functions for working with html form value validation.
 *
 * @author Levon Naghashyan
 * <levon@naghashyan.com>
 * @site https://naghashyan.com
 * @year 2013-2023
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

use ngs\exceptions\NgsErrorException;

class FormValidator
{

    /**
     * Validate email adress
     *
     * @param string $str
     * @param string $msg
     *
     * @return string|bool
     *
     */
    public static function validateEmail($str, $msg = "Please enter valid email")
    {
        $email = FormValidator::secure($str);
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return true;
        }
        throw new NgsErrorException($msg);
    }

    /**
     * Validate string
     *
     * @param string $str
     * @param array $options
     *
     * @return string|bool
     */
    public static function validateString($str, $options = [])
    {
        $defaultOptions = array("len" => 4, "allowChars" => "/^[A-Za-z0-9\_\-\.]*$/", "msg" => "");
        $options = array_merge($defaultOptions, $options);
        $str = FormValidator::secure($str);
        $params = isset($options["params"]) ? $options["params"] : [];
        if (empty($str)) {
            if ($options["msg"] == "") {
                $options["msg"] = "You can't leave this empty.";
            }
            throw new NgsErrorException($options["msg"], -1, $params);
        }
        if ($options["len"]) {
            if (strlen($str) < $options["len"] || strlen($str) > 30) {
                if ($options["msg"] == "") {
                    $options["msg"] = "Please fill correct data";
                }
                throw new NgsErrorException($options["msg"], -1, $params);
            }
        }

        if ($options["allowChars"]) {
            if (!preg_match($options["allowChars"], $str)) {
                if ($options["msg"] == "") {
                    $options["msg"] = "Please use only letters (a-z), numbers, and periods.";
                }
                throw new NgsErrorException($options["msg"], -1, $params);
            }
        }
        return true;
    }

    /**
     * Validate string
     *
     * @param string $pass
     * @param string $rePass

     * @return string|bool
     */
    public static function validatePasswords($pass, $rePass)
    {
        $pass = FormValidator::secure($pass);
        $rePass = FormValidator::secure($rePass);
        if ($pass !== $rePass) {
            $msg = "These passwords don't match. Try again?";
            throw new NgsErrorException($msg);
        }
        return true;
    }

    /**
     * do secure string
     * trim
     * htmlspecialchars
     * strip_tags
     *
     * @param string $str
     *
     * @return string
     */
    public static function secure($str)
    {
        return trim(htmlspecialchars(strip_tags($str)));
    }

}