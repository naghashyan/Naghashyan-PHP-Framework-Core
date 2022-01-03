<?php
/**
 * Helper wrapper class for php curl
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site https://naghashyan.com
 * @year 2016-2021
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

use JetBrains\PhpStorm\Pure;
use ngs\exceptions\DebugException;
use Exception;

class NgsArgs
{

    /**
     * @var NgsArgs[] $instance
     */
    private static array $instance = [];
    private array $args = [];
    private ?NgsArgs $inputArgs = null;
    private ?string $inputParams = null;
    private ?NgsArgs $headerParams = null;

    private function __construct()
    {

    }

    /**
     * Returns an singleton instance of this class
     *
     * @param string $className
     * @param array $args
     * @return NgsArgs
     */
    public static function getInstance(string $className = 'main', ?array $args = null): NgsArgs
    {
        if (!isset(self::$instance[$className])) {
            self::$instance[$className] = new NgsArgs();
            if (isset($_REQUEST) && is_array($_REQUEST)) {
                self::$instance[$className]->args = $_REQUEST;
            }
            self::$instance[$className]->mergeInputData();
            if ($args) {
                self::$instance[$className]->setArgs($args);
            }
        }
        return self::$instance[$className];
    }


    /**
     * this dynamic method
     * return request args
     * check if set trim do trim
     *
     * @param string $name
     *
     * @return mixed
     */
    public function __get(string $name)
    {
        $args = $this->getArgs();
        return $args[$name] ?? null;
    }

    public function __set($name, $value)
    {
        $this->args[$name] = $value;
    }

    #[Pure]
    public function __isset($name)
    {
        $args = $this->getArgs();
        return isset($args[$name]);
    }

    /*
         Overloads getter and setter methods
         */
    /**
     * @throws DebugException
     */
    public function __call(string $m, mixed $a)
    {

        // retrieving the method type (setter or getter)
        $type = substr($m, 0, 3);

        // retrieving the field name
        $fieldName = NGS()->getNgsUtils()->lowerFirstLetter(substr($m, 3));
        if ($type === 'set') {
            if (count($a) === 1) {
                $this->$fieldName = $a[0];
            } else {
                $this->$fieldName = $a;
            }
        } else if ($type === 'get') {
            return $this->$fieldName;
        }
        return null;
    }

    /**
     * static function that return ngs
     * global url args
     *
     *
     * @return array config
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * static function that set ngs
     * url args
     *
     * @param array|null $args
     *
     * @return bool
     */
    public function setArgs(?array $args = null): bool
    {
        if ($args === null) {
            return false;
        }
        $this->args = array_merge($this->args, $args);
        return true;
    }

    /**
     * @return array
     */
    public function args(): array
    {
        return $this->args;
    }

    /**
     * merge php body params into NGS args
     */
    public function mergeInputData(): void
    {
        try {
            if ($this->inputData() === null) {
                return;
            }
            if (NGS()->getNgsUtils()->isJson($this->inputData())) {
                $this->setArgs(json_decode($this->inputData(), true, 512, JSON_THROW_ON_ERROR));
            } else {
                parse_str($this->inputData(), $parsedRequestBody);
                if (is_array($parsedRequestBody)) {
                    $this->setArgs($parsedRequestBody);
                }
            }
        } catch (Exception) {
            return;
        }


    }

    /**
     * @return NgsArgs
     */
    public function input(): NgsArgs
    {
        try {
            if ($this->inputArgs === null) {
                if (!NGS()->getNgsUtils()->isJson($this->inputData())) {
                    throw new DebugException('response body is not json');
                }
                $this->inputArgs = new NgsArgs();
                $this->inputArgs->setArgs(json_decode($this->inputData(), true, 512, JSON_THROW_ON_ERROR));
            }
            return $this->inputArgs;
        } catch (Exception) {
            return new NgsArgs();
        }

    }

    /**
     * @return null|string
     */
    public function inputData(): ?string
    {
        if ($this->inputParams === null) {
            $this->inputParams = file_get_contents('php://input');
        }
        return $this->inputParams;

    }

    /**
     * @return NgsArgs|null
     */
    public function headers(): ?NgsArgs
    {
        if ($this->headerParams === null) {
            $this->headerParams = new NgsArgs();
            $this->headerParams->setArgs($this->getAllHeaders());
        }
        return $this->headerParams;
    }

    /**
     * @return array
     */
    private function getAllHeaders(): array
    {
        if (!is_array($_SERVER)) {
            return array();
        }
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $key = str_replace(' ', '-', (strtolower(str_replace('_', ' ', substr($key, 5)))));
            }
            $headers[$key] = $value;
        }
        return $headers;

    }
}


