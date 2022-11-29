<?php
/**
 * AbstractMapper class is a base class for all mapper lasses.
 * It contains the basic functionality and also DBMS pointer.
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site https://naghashyan.com
 * @package ngs.framework.dal.mappers
 * @version 4.0.0
 * @year 2009-2020
 *
 * This file is part of the NGS package.
 *
 * @copyright Naghashyan Solutions LLC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace ngs\dal\mappers;

use Exception;
use JsonException;
use ngs\dal\connectors\MysqlPDO;
use ngs\dal\dto\AbstractDto;
use ngs\exceptions\DebugException;
use PDO;
use Redis;

abstract class AbstractMysqlMapper extends AbstractMapper
{

    public MysqlPDO $dbms;
    private ?Redis $redis = null;

    /**
     * Initializes DBMS pointer.
     */
    public function __construct()
    {
        $host = NGS()->getConfig()->DB->mysql->host;
        $user = NGS()->getConfig()->DB->mysql->user;
        $pass = NGS()->getConfig()->DB->mysql->pass;
        $name = NGS()->getConfig()->DB->mysql->name;
        $this->dbms = NGS()->get('NGS_MYSQL_PDO_DRIVER')::getInstance($host, $user, $pass, $name);
    }

    /**
     *
     * get redis connector
     *
     * @return Redis
     */
    private function getRedis(): Redis
    {
        if ($this->redis === null) {
            $this->redis = new Redis();
            $this->redis->connect(NGS()->getConfig()->DB->redis->host, NGS()->getConfig()->DB->redis->port);
        }
        return $this->redis;
    }


    /**
     *
     * Inserts dto into table.
     *
     * @param AbstractDto $dto
     * @return int|null
     * @throws DebugException
     */
    public function insertDto(AbstractDto $dto): ?int
    {

        //creating query
        $sqlQuery = sprintf(/** @lang text */ 'INSERT INTO `%s` SET ', $this->getTableName());
        $queryArr = $this->createDtoToInsertUpdateQuery($dto);
        $sqlQuery .= $queryArr['query'];
        $params = $queryArr['params'];
        // Execute query.
        $res = $this->dbms->prepare($sqlQuery);

        if ($res) {
            $res->execute($params);
            return $this->dbms->lastInsertId();
        }
        return null;
    }


    /**
     * Inserts dto into table.
     *
     * @param AbstractDto[]
     * @return bool
     * @throws DebugException
     */
    public function insertDtos(array $allDtos): bool
    {
        $allDtos = array_chunk($allDtos, 200);
        foreach ($allDtos as $dtoChunk) {
            $this->insertDtosToDb($dtoChunk);
        }

        return true;
    }


    private function insertDtosToDb($dtos)
    {
        if (count($dtos) === 0) {
            return false;
        }

        $sqlQuery = sprintf(/** @lang text */ 'INSERT INTO `%s` (', $this->getTableName());
        $dbFields = [];
        $dbFieldsValues = [];
        foreach ($dtos as $dto) {
            $queryArr = $this->createDtoToInsertUpdateQuery($dto);
            $dbFieldsValues[] = $queryArr['db_fields'];
            foreach ($queryArr['db_fields'] as $dbField => $value) {
                if (!isset($dbFields[$dbField])) {
                    $dbFields[$dbField] = $dbField;
                }

            }
        }
        $dbColumns = implode(',', $dbFields);
        $sqlValuesQuery = '';
        $params = [];
        foreach ($dbFieldsValues as $dbFieldValue) {
            $sqlValuesQuery .= '(';
            foreach ($dbFields as $dbField) {
                $value = 'NULL';
                if (isset($dbFieldValue[$dbField])) {
                    $params[] = $dbFieldValue[$dbField];
                    $value = '?';
                }
                $sqlValuesQuery .= $value . ',';
            }
            $sqlValuesQuery = substr_replace($sqlValuesQuery, '', -1);
            $sqlValuesQuery .= '),';
        }
        $sqlValuesQuery = substr_replace($sqlValuesQuery, '', -1);

        $sqlQuery .= $dbColumns . ') VALUES ' . $sqlValuesQuery;
        return $this->executeQuery($sqlQuery, $params);
    }

    /**
     * Updates tables text field's value by primary key
     *
     * @param object $id - the unique identifier of table
     * @param string $fieldName - the name of filed which must be updated
     * @param object $fieldValue - the new value of field
     * @return integer rows count or -1 if something goes wrong
     * @throws DebugException
     */
    public function updateField($id, string $fieldName, $fieldValue): ?int
    {
        // Create query.
        $sqlQuery = sprintf('UPDATE `%s` SET `%s` = :%s WHERE `%s` = :id',
            $this->getTableName(), $fieldName, $fieldName, $this->getPKFieldName());
        return $this->executeUpdate($sqlQuery, ['id' => $id, $fieldName => $fieldValue]);
    }

    /**
     *
     * Updates table fields by primary key.
     * DTO must contain primary key value.
     *
     * @param AbstractDto $dto
     * @return int|null
     * @throws DebugException
     */
    public function updateByPK(AbstractDto $dto): ?int
    {

        //creating query
        $sqlQuery = sprintf(/** @lang text */ 'UPDATE `%s` SET ', $this->getTableName());
        $queryArr = $this->createDtoToInsertUpdateQuery($dto, true);
        $sqlQuery .= $queryArr['query'];
        $params = $queryArr['params'];

        //validating input params
        if ($dto->getMapArray()) {
            $pkGetterFunction = $this->getCorrespondingFunctionName($dto->getMapArray(), $this->getPKFieldName(), 'get');
        } else {
            $pkGetterFunction = 'get' . ucfirst($dto->getNgsPropertyAnnotationByName($this->getPKFieldName(), 'orm', true));
        }
        $pk = $dto->$pkGetterFunction();
        if (!isset($pk)) {
            throw new DebugException('The primary key is not set.');
        }
        $comma = ' ';
        foreach ($dto->getNgsNullableFealds() as $feald) {
            if ($queryArr['query'] !== '') {
                $comma = ' ,';
            }
            $sqlQuery .= sprintf($comma . '`%s` = %s', $feald, 'NULL');
            $comma = ' ,';
        }

        $sqlQuery .= sprintf(' WHERE `%s` = ? ', $this->getPKFieldName());
        $additionalCondition = $this->getUpdateAdditionalCondition($dto);
        if ($additionalCondition) {
            $sqlQuery .= 'AND ' . $additionalCondition . ' ';
        }
        $params[] = $dto->$pkGetterFunction();
        $res = $this->dbms->prepare($sqlQuery);
        if ($res) {
            $res->execute($params);
            if ($res->rowCount()) {
                return true;
            }
        }
        return false;
    }


    protected function getUpdateAdditionalCondition(AbstractDto $dto): string
    {
        return "";
    }

    /**
     * generate SET query part
     *
     * @param AbstractDto $dto
     * @param bool $isUpdate
     * @return array|null
     * @throws DebugException
     */
    private function createDtoToInsertUpdateQuery(AbstractDto $dto, bool $isUpdate = false): ?array
    {
        $mapArray = $dto->getMapArray();
        $v1 = false;
        if ($mapArray) {
            $mapArray = array_flip($mapArray);
            $v1 = true;
        } else {
            $mapArray = $dto->getNgsPropertyAnnotationsByName('orm', true);
        }
        if (!$mapArray) {
            throw new DebugException('please add map fields in dto');
        }
        $params = [];
        $dbFields = [];
        $sqlQuery = '';

        foreach ($mapArray as $dtoField => $dbField) {
            $getterFunction = 'get' . ucfirst($dtoField);

            if ($v1 === false && !$dto->ngsCheckIfPropertyInitialized($dtoField)) {
                continue;
            }

            $val = $dto->$getterFunction();
            if ($v1 && !isset($val)) {
                continue;
            }
            if ($isUpdate && $dbField === $this->getPKFieldName()) {
                continue;
            }
            $dbProperty = '?';
            if ($val === 'CURRENT_TIMESTAMP()' || $val === 'NOW()') {
                $dbProperty = $val;
            } else {
                $params[] = $val;
                $dbFields[$dbField] = $val;
            }
            $sqlQuery .= sprintf(' `%s`= %s,', $dbField, $dbProperty);
        }
        $sqlQuery = substr($sqlQuery, 0, -1);
        return [
            'query' => $sqlQuery,
            'params' => $params,
            'db_fields' => $dbFields
        ];
    }

    /**
     *
     * execute Update
     * using query and params
     *
     * @param string $sqlQuery
     * @param array $params
     * @return int|null
     * @throws DebugException
     */
    public function executeUpdate(string $sqlQuery, array $params = []): ?int
    {
        $res = $this->dbms->prepare($sqlQuery);
        if ($res) {
            $res->execute($params);
            return $res->rowCount();
        }
        return null;
    }

    /**
     *
     * execute query
     * using query and params
     *
     * @param string $sqlQuery
     * @param array $params
     * @return int|null
     * @throws DebugException
     */
    public function executeQuery(string $sqlQuery, array $params = []): ?int
    {
        $res = $this->dbms->prepare($sqlQuery);
        if ($res) {
            $res->execute($params);
            $result = $res->rowCount();
            if ($result === 0) {
                return null;
            }
            return $result;
        }
        return null;
    }

    /**
     *
     * execute multiple queries
     * using transactions
     *
     *
     * @param string $sqlQuery
     * @param array $params
     * @return int|null
     */
    public function executeMultipleQueries(string $sqlQuery, array $params = []): ?int
    {
        $this->dbms->beginTransaction();
        try {
            $res = $this->dbms->prepare($sqlQuery);
            if (!$res) {
                return null;
            }
            $res->execute($params);
            $result = $res->rowCount();
            if ($result === 0) {
                $this->dbms->rollBack();
                return null;
            }
            $this->dbms->commit();
            return $result;
        } catch (Exception $e) {
            $this->dbms->rollBack();
        }
        return null;
    }

    /**
     * @param $id
     * @param string $fieldName
     * @return int|null
     * @throws DebugException
     */
    public function setNull($id, string $fieldName): ?int
    {
        // Create query.
        $sqlQuery = sprintf('UPDATE `%s` SET `%s` = NULL WHERE `%s` = :id ', $this->getTableName(), $fieldName, $this->getPKFieldName());
        $res = $this->dbms->prepare($sqlQuery);
        if ($res) {
            $res->execute(['id' => $id]);
            return $res->rowCount();
        }
        return null;
    }

    /**
     * @param mixed $id
     * @return int|null
     * @throws DebugException
     */
    public function deleteByPK($id): ?int
    {
        $sqlQuery = sprintf('DELETE FROM `%s` WHERE `%s` = :id', $this->getTableName(), $this->getPKFieldName());
        return $this->executeQuery($sqlQuery, ['id' => $id]);
    }


    /**
     * delete items by ids
     *
     * @param array $ids
     * @return int|null
     * @throws DebugException
     */
    public function deleteByPKs(array $ids): ?int
    {
        if (!$ids) {
            return true;
        }

        $inQuery = implode(",", $ids);
        $sqlQuery = sprintf('DELETE FROM `%s` WHERE `%s` IN (%s)', $this->getTableName(), $this->getPKFieldName(), $inQuery);
        return $this->executeQuery($sqlQuery, []);
    }


    /**
     *
     * Executes the query and returns an array of corresponding DTOs or raw data in case if $rawData argument is true
     *
     * @param string $sqlQuery
     * @param array $params
     * @param array $cache
     * @param bool $rawData
     *
     * @return AbstractDto[]|null
     *
     * @throws DebugException
     */
    protected function fetchRows(string $sqlQuery, array $params = [], ?array $cache = null, bool $rawData = false): ?array
    {

        if ($cache === null) {
            $cache = ['cache' => false, 'ttl' => 3600, 'force' => false];
        }
        if (!isset($cache['ttl'])) {
            $cache['ttl'] = 3600;
        }
        if (!isset($cache['force'])) {
            $cache['force'] = false;
        }
        $cacheKey = '';
        if ($cache['cache'] === true && $cache['force'] === false) {
            try {
                $cacheKey = md5($sqlQuery . json_encode($params, JSON_THROW_ON_ERROR, 512));
            } catch (JsonException $exception) {
                return null;
            }

            $cachedData = $this->getDataFromRedisCache($cacheKey);
            if ($cachedData) {
                return $cachedData;
            }
        }
        // Execute query.
        $res = $this->dbms->prepare($sqlQuery);
        $results = $res->execute($params);

        if ($results === false) {
            return null;
        }
        if ($cache['cache'] === true) {
            $cacheKey = md5($sqlQuery . json_encode($params, JSON_THROW_ON_ERROR, 512));
            $res->setFetchMode(PDO::FETCH_ASSOC);
            $resultArr = $res->fetchAll();
            $this->setDataToRedisCache($cacheKey, $resultArr, $cache['ttl']);
            return $this->getDataFromRedisCache($cacheKey);
        }
        if ($rawData) {
            $res->setFetchMode(PDO::FETCH_ASSOC);
        } else {
            $res->setFetchMode(PDO::FETCH_CLASS, get_class($this->createDto()));
        }
        $resultArr = $res->fetchAll();

        return $resultArr;
    }

    /**
     *
     * Executes the query and returns an row field of corresponding DTOs
     * if $row isn't false return first elem
     *
     * @param string $sqlQuery
     * @param array $params
     * @param array $cache
     * @return mixed|AbstractDto|null
     * @throws DebugException
     */
    protected function fetchRow(string $sqlQuery, array $params = [], ?array $cache = null): ?object
    {
        $result = $this->fetchRows($sqlQuery, $params, $cache);
        if (isset($result) && is_array($result) && count($result) > 0) {
            return $result[0];
        }
        return null;
    }

    /**
     *
     * Returns table's field value, which was returnd by query.
     * If query matches more than one rows, the first field will be returnd.
     *
     * @param string $sqlQuery
     * @param string $fieldName
     * @param array $params
     * @return string|null
     * @throws DebugException
     */
    protected function fetchField(string $sqlQuery, string $fieldName, array $params = []): ?string
    {
        // Execute query.
        $res = $this->dbms->prepare($sqlQuery);
        $results = $res->execute($params);
        if ($results) {
            $fetchedObject = $res->fetchObject();
            if ($fetchedObject === false) {
                return null;
            }
            return $fetchedObject->$fieldName;
        }
        return null;
    }

    /**
     * Selects all entries from table
     *
     * @return AbstractDto[]
     * @throws DebugException
     */
    public function selectAll(): ?array
    {
        $sqlQuery = sprintf('SELECT * FROM `%s`', $this->getTableName());
        return $this->fetchRows($sqlQuery);
    }

    /**
     *
     * select entries from table
     * by offset and limit
     *
     * @param int $offset
     * @param int $limit
     * @return AbstractDto[]|null
     * @throws DebugException|JsonException
     */
    public function selectByLimit(int $offset, int $limit): ?array
    {
        $sqlQuery = sprintf('SELECT * FROM `%s` LIMIT :offset, :limit', $this->getTableName());
        return $this->fetchRows($sqlQuery, ['offset' => $offset, 'limit' => $limit]);
    }

    /**
     * Selects from table by primary key and returns corresponding DTO
     *
     * @param int $id
     * @param array $cache
     * @return mixed|AbstractDto|null
     * @throws DebugException
     */
    public function selectByPK($id, array $cache = ['cache' => false, 'ttl' => 3600]): ?object
    {
        $sqlQuery = sprintf('SELECT * FROM `%s` WHERE `%s` = :id ', $this->getTableName(), $this->getPKFieldName());
        return $this->fetchRow($sqlQuery, ['id' => $id], $cache);
    }

    /**
     * @param string $sqlQuery
     * @return false|int
     */
    protected function exec(string $sqlQuery)
    {
        return $this->dbms->exec($sqlQuery);
    }

    /**
     * return mysql formated time
     * if time not set then return current server time
     *
     * @param int|null $time
     *
     * @return false|string
     */
    public function getMysqlFormattedTime($time = null): string
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
    public function getMysqlFormattedDate($date = null): string
    {
        if ($date === null) {
            $date = time();
        }
        return date('Y-m-d', $date);
    }

    /**
     * @param string $key
     * @return AbstractDto[]|null
     */
    private function getDataFromRedisCache(string $key): ?array
    {
        $redis = $this->getRedis();
        $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
        $cachedDataArr = $redis->get($key);
        if (!$cachedDataArr) {
            return null;
        }
        return $this->createDtoFromResultArray($cachedDataArr);
    }

    private function setDataToRedisCache(string $key, array $data, int $ttl): ?bool
    {
        $redis = $this->getRedis();
        $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
        return $redis->set($key, $data, ['ex' => $ttl]);
    }
}