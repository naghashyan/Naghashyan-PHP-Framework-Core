<?php
/**
 *
 * AbstractDto parent class for all
 * ngs dtos
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site https://naghashyan.com
 * @year 2009-2020
 * @package ngs.framework.dal.dto
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

namespace ngs\dal\dto {


    use ngs\util\AnnotationParser;

    abstract class AbstractDto
    {

        private $ngs_nullableFields = [];


        public function getMapArray(): ?array
        {
            return null;
        }

        /*
         The first letter of input string changes to Lower case
         */
        public static function lowerFirstLetter(string $str)
        {
            $first = $str[0];
            $asciiValue = ord($first);
            if ($asciiValue >= 65 && $asciiValue <= 90) {
                $asciiValue += 32;
                return chr($asciiValue) . substr($str, 1);
            }
            return $str;
        }

        /*
         The first letter of input string changes to Lower case
         */
        public static function upperFirstLetter($str)
        {
            return ucfirst($str);
        }

        /*
         Overloads getter and setter methods
         */
        public function __call($m, $a)
        {
            // retrieving the method type (setter or getter)
            $type = substr($m, 0, 3);
            // retrieving the field name
            $fieldName = preg_replace_callback('/[A-Z]/', function ($m) {
                return '_' . strtolower($m[0]);
            }, self::lowerFirstLetter(substr($m, 3)));
            if ($type === 'set') {
                $this->$fieldName = $a[0];
            } else if ($type === 'get') {
                if (isset($this->$fieldName)) {
                    return $this->$fieldName;
                }
                return null;
            }
        }

        public function __set($property, $value)
        {
            $fieldName = 'set' . preg_replace_callback('/_([a-z])/', function ($property) {
                    if (isset($property[1])) {
                        return ucfirst($property[1]);
                    }
                }, self::upperFirstLetter(($property)));

            $this->$fieldName($value);
        }


        public function setNull(string $fieldName): bool
        {
            if (!$this->isExsistField($fieldName)) {
                return false;
            }
            $this->ngs_nullableFields[] = $fieldName;
            return true;
        }

        public function getNgsNullableFealds(): array
        {
            return $this->ngs_nullableFields;
        }

        public function getFieldByName($name): ?string
        {
            $mapArr = array_flip($this->getMapArray());
            if (isset($mapArr[$name])) {
                return $mapArr[$name];
            }
            return null;
        }

        public function isExsistField(string $key): bool
        {
            $mapArr = $this->getMapArray();
            if (isset($mapArr[$key])) {
                return true;
            }
            return false;
        }

        /**
         *
         * fill dto from data array
         *
         * @param array $dataArr
         */
        public function fillDtoFromArray(array $dataArr = []): void
        {
            foreach ($dataArr as $key => $data) {
                $setterFunction = 'set' . preg_replace_callback('/_(\w)/', function ($m) {
                        return strtoupper($m[1]);
                    }, ucfirst($key));
                if(method_exists($this, $setterFunction)) {
                    if($data === null) {
                        $this->setNull($key);
                    }
                    else {
                        $this->$setterFunction($data);
                    }

                }
            }
        }

        /**
         *
         * convert dto to array
         *
         * @return array
         */
        public function toArray(): array
        {
            $resultArr = [];
            try {
                $reflectionClass = new \ReflectionClass($this);
                $properties = $reflectionClass->getProperties();
                foreach ($properties as $property) {
                    $property->setAccessible(true);
                    if (!$property->isInitialized($this)) {
                        continue;
                    }
                    $getterFunction = 'get' . '' . ucfirst($property->getName());
                    $resultArr[] = $this->$getterFunction();
                }
            } catch (\ReflectionException $exception) {
                return [];
            }
            return $resultArr;
        }

        /**
         *
         * return dto properties annotation values
         * by annotation name
         *
         * @param string $name
         * @param bool $isInitialized
         *
         * @return array|null
         */
        public function getNgsPropertyAnnotationsByName(string $name, bool $isInitialized = false): ?array
        {
            $name = strtolower($name);
            try {
                $reflectionClass = new \ReflectionClass($this);
                $properties = $reflectionClass->getProperties();
                $annotationsArr = [];
                foreach ($properties as $property) {
                    if ($isInitialized) {
                        $property->setAccessible(true);
                        if (!$property->isInitialized($this)) {
                            continue;
                        }
                    }
                    preg_match_all('/@(.*) +(.*)/', $property->getDocComment(), $annotations, PREG_SET_ORDER);
                    foreach ($annotations as $annotation) {
                        if ($name === strtolower($annotation[1])) {
                            $annotationsArr[trim($property->getName())] = trim($annotation[2]);
                            break;
                        }
                    }
                }
                return $annotationsArr;
            } catch (\ReflectionException $exception) {
                return null;
            }
        }

        /**
         *
         * return dto property annotation values
         * by property and annotation name
         *
         * @param string $property
         * @param string $name
         * @param bool $isInitialized
         *
         * @return string|null
         */
        public function getNgsPropertyAnnotationByName(string $property, string $name, bool $isInitialized = false): ?string
        {
            $name = strtolower($name);
            try {
                $reflectionProperty = new \ReflectionProperty($this, $property);
                if ($isInitialized) {
                    $reflectionProperty->setAccessible(true);
                    if (!$reflectionProperty->isInitialized($this)) {
                        return null;
                    }
                }
                preg_match_all('/@(.*) +(.*)/', $reflectionProperty->getDocComment(), $annotations, PREG_SET_ORDER);
                foreach ($annotations as $annotation) {
                    if ($name === strtolower($annotation[1])) {
                        return trim($annotation[2]);
                    }
                }
            } catch (\ReflectionException $exception) {
                return null;
            }
            return null;
        }

        public function ngsCheckIfPropertyInitialized(string $property): bool
        {
            return isset($this->$property);
        }

        /**
         * return mysql formated time
         * if time not set then return current server time
         *
         * @param int|null $time
         *
         * @return false|string
         */
        public function getMysqlFormatedTime($time = null)
        {
            if ($time === null) {
                $time = time();
            }
            return date('Y-m-d H:i:s', $time);
        }

        /**
         * return mysql formated date
         * if time not set then return current server date
         *
         * @param int|null $date
         *
         * @return false|string
         */
        public function getMysqlFormatedDate($date = null)
        {
            if ($date === null) {
                $date = time();
            }
            return date('Y-m-d', $date);
        }

    }
}