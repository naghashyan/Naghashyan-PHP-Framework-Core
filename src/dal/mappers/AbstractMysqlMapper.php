<?php
/**
 * AbstractMapper class is a base class for all mapper lasses.
 * It contains the basic functionality and also DBMS pointer.
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @package ngs.framework.dal.mappers
 * @version 3.6.0
 * @year 2009-2018
 *
 * This file is part of the NGS package.
 *
 * @copyright Naghashyan Solutions LLC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace ngs\dal\mappers {

  use ngs\exceptions\DebugException;

  abstract class AbstractMysqlMapper extends AbstractMapper {

    public $dbms;
    private $bulkUpdateQuery;

    /**
     * Initializes DBMS pointer.
     */
    function __construct() {
      $config = NGS()->getConfig();
      if (!isset($config->DB->mysql)){
        $config = NGS()->getNgsConfig();
      }
      $usePdo = true;
      if (isset(NGS()->getConfig()->DB->mysql->driver) && NGS()->getConfig()->DB->mysql->driver == "mysqli"){
        $usePdo = false;
      }
      if ($usePdo){
        $host = NGS()->getConfig()->DB->mysql->host;
        $user = NGS()->getConfig()->DB->mysql->user;
        $pass = NGS()->getConfig()->DB->mysql->pass;
        $name = NGS()->getConfig()->DB->mysql->name;
        $this->dbms = \ngs\dal\connectors\MysqlPDO::getInstance($host, $user, $pass, $name);
      }
    }

    /**
     * Inserts dto into table.
     *
     * @param object $dto
     * @param object $esc [optional] - shows if the textual values must be escaped before setting to DB
     * @return integer id or -1 if something goes wrong
     */
    public function insertDto($dto) {

      //validating input params
      if ($dto == null){
        throw new DebugException("The input param can not be NULL.");
      }

      $dto_fields = array_values($dto->getMapArray());
      $db_fields = array_keys($dto->getMapArray());
      //creating query
      $sqlQuery = sprintf("INSERT INTO `%s` SET ", $this->getTableName());
      $params = array();
      for ($i = 0; $i < count($dto_fields); $i++){
        $functionName = "get" . ucfirst($dto_fields[$i]);
        $val = $dto->$functionName();
        if (isset($val)){
          if ($val == "CURRENT_TIMESTAMP()" || $val == "NOW()" || $val === "NULL"){
            $sqlQuery .= sprintf(" `%s` = %s,", $db_fields[$i], $val);
          } else{
            $params[$db_fields[$i]] = $val;
            $sqlQuery .= sprintf(" `%s` = :%s,", $db_fields[$i], $db_fields[$i]);
          }
        }
      }
      $sqlQuery = substr($sqlQuery, 0, -1);
      // Execute query.
      $res = $this->dbms->prepare($sqlQuery);

      if ($res){
        $res->execute($params);
        return $this->dbms->lastInsertId();
      }
      return null;
    }

    /**
     * Updates tables text field's value by primary key
     *
     * @param object $id - the unique identifier of table
     * @param object $fieldName - the name of filed which must be updated
     * @param object $fieldValue - the new value of field
     * @return integer rows count or -1 if something goes wrong
     */
    public function updateField($id, $fieldName, $fieldValue) {
      // Create query.
      $sqlQuery = sprintf("UPDATE `%s` SET `%s` = :%s WHERE `%s` = :id", $this->getTableName(), $fieldName, $fieldName, $this->getPKFieldName());
      $res = $this->dbms->prepare($sqlQuery);
      if ($res){
        $res->execute(array("id" => $id, $fieldName => $fieldValue));
        return $res->rowCount();
      }
      return null;
    }

    /**
     * Updates table fields by primary key.
     * DTO must contain primary key value.
     *
     * @param object $dto
     * @param object $esc [optional] shows if the textual values must be escaped before setting to DB
     * @return integer rows count or -1 if something goes wrong
     */
    public function updateByPK($dto, $esc = true, $returnQuery = false) {

      //validating input params
      if ($dto == null){
        throw new DebugException("The input param can not be NULL.");
      }
      $getPKFunc = $this->getCorrespondingFunctionName($dto->getMapArray(), $this->getPKFieldName(), "get");
      $pk = $dto->$getPKFunc();
      if (!isset($pk)){
        throw new DebugException("The primary key is not set.");
      }

      $dto_fields = array_values($dto->getMapArray());
      $db_fields = array_keys($dto->getMapArray());
      //creating query
      $sqlQuery = sprintf("UPDATE `%s` SET ", $this->getTableName());
      $params = array();
      for ($i = 0; $i < count($dto_fields); $i++){
        if ($dto_fields[$i] == $this->getPKFieldName()){
          continue;
        }
        $functionName = "get" . ucfirst($dto_fields[$i]);
        $val = $dto->$functionName();
        if (isset($val)){
          if ($val == "CURRENT_TIMESTAMP()" || $val == "NOW()" || $val === "NULL"){
            $sqlQuery .= sprintf(" `%s` = %s,", $db_fields[$i], $val);
          } else{
            $params[$db_fields[$i]] = $val;
            $sqlQuery .= sprintf(" `%s` = :%s,", $db_fields[$i], $db_fields[$i]);
          }
        }
      }
      $sqlQuery = substr($sqlQuery, 0, -1);
      $sqlQuery .= sprintf(" WHERE `%s` = :PKField ", $this->getPKFieldName());
      $params["PKField"] = $dto->$getPKFunc();
      $res = $this->dbms->prepare($sqlQuery);
      if ($res){
        $res->execute($params);
        if ($res->rowCount()){
          return true;
        }
        return false;
      }
    }

    /**
     * execute Update
     * using query and params
     *
     * @param String $sqlQuery
     * @param array $params
     * @return int|null
     */
    public function executeUpdate($sqlQuery, $params = array()) {
      $res = $this->dbms->prepare($sqlQuery);
      if ($res){
        $res->execute($params);
        return $res->rowCount();
      }
      return null;
    }

    /**
     * execute query
     * using query and params
     *
     * @param String $sqlQuery
     * @param array $params
     * @return int|null
     */
    public function executeQuery($sqlQuery, $params = array()) {
      $res = $this->dbms->prepare($sqlQuery);
      if ($res){
        return $res->execute($params);
      }
      return null;
    }

    /**
     * Sets field value NULL.
     *
     * @param object $id
     * @param object $fieldName
     * @return
     */
    public function setNull($id, $fieldName) {
      // Create query.
      $sqlQuery = sprintf("UPDATE `%s` SET `%s` = NULL WHERE `%s` = :id ", $this->getTableName(), $fieldName, $this->getPKFieldName());
      $res = $this->dbms->prepare($sqlQuery);
      if ($res){
        $res->execute(array("id" => $id));
        return $res->rowCount();
      }
    }

    /**
     * Deletes the row by primary key
     *
     * @param string $id - the unique identifier of table
     * @return int rows count or -1 if something goes wrong
     */
    public function deleteByPK($id) {

      $sqlQuery = sprintf("DELETE FROM `%s` WHERE `%s` = :id", $this->getTableName(), $this->getPKFieldName());
      $res = $this->dbms->prepare($sqlQuery);
      if ($res){
        $res->execute(array("id" => $id));
        return $res->rowCount();
      }
      return null;
    }

    /**
     * Multi Updates table fields by primary key.
     * DTO must contain primary key value.
     *
     * @param object $dto
     * @param string $esc [optional] shows if the textual values must be escaped before setting to DB
     * @return affected rows count or -1 if something goes wrong
     */
    public function bulkUpdateByPK($dto, $esc = true) {
      $this->bulkUpdateQuery .= $this->updateByPK($dto, $esc, true) . ";";
      return true;
    }

    public function bulkUpdateByPKCommit() {
      if ($this->bulkUpdateQuery){
        $result = $this->dbms->multiQuery($this->bulkUpdateQuery);
        $this->bulkUpdateQuery = "";
        return $result;
      }
      return false;
    }

    /**
     * Executes the query and returns an array of corresponding DTOs
     *
     * @param string $sqlQuery
     * @param array $params
     * @return
     */
    protected function fetchRows($sqlQuery, $params = array()) {
      // Execute query.
      $res = $this->dbms->prepare($sqlQuery);
      $results = $res->execute($params);
      if ($results == false){
        return null;
      }
      $resultArr = array();
      $res->setFetchMode(\PDO::FETCH_CLASS, get_class($this->createDto()));
      $resultArr = $res->fetchAll();
      if (count($resultArr) > 0){
        return $resultArr;
      }
      return array();
    }

    /**
     * Executes the query and returns an row field of corresponding DTOs
     * if $row isn't false return first elem
     *
     * @param string $sqlQuery
     * @return
     */
    protected function fetchRow($sqlQuery, $params = array(), $standartArgs = false) {
      $result = $this->fetchRows($sqlQuery, $params);
      if (isset($result) && is_array($result) && count($result) > 0){
        return $result[0];
      }
      return null;
    }

    /**
     * Returns table's field value, which was returnd by query.
     * If query matches more than one rows, the first field will be returnd.
     *
     * @param string $sqlQuery - select query
     * @param string $fieldName - the field name, which was returnd by query
     * @param array $params
     * @return string fieldValue or NULL, if the query doesn't return such field
     */
    protected function fetchField($sqlQuery, $fieldName, $params = array()) {
      // Execute query.
      $res = $this->dbms->prepare($sqlQuery);
      $results = $res->execute($params);
      if ($results){
        $fetchedObject = $res->fetchObject();
        if ($fetchedObject === false){
          return "";
        }
        return $fetchedObject->$fieldName;
      }
      return null;
    }

    /**
     * Selects all entries from table
     * @return
     */
    public function selectAll() {
      $sqlQuery = sprintf("SELECT * FROM `%s`", $this->getTableName());
      return $this->fetchRows($sqlQuery);
    }

    /**
     * Selects all entries from table
     * @return
     */
    public function selectByLimit($offset, $limit) {
      $sqlQuery = sprintf("SELECT * FROM `%s` LIMIT :offset, :limit", $this->getTableName());
      return $this->fetchRows($sqlQuery, array("offset" => $offset, "limit" => $limit));
    }

    /**
     * Selects from table by primary key and returns corresponding DTO
     *
     * @param int $id
     * @return object
     */
    public function selectByPK($id) {
      $sqlQuery = sprintf("SELECT * FROM `%s` WHERE `%s` = :id ", $this->getTableName(), $this->getPKFieldName());
      return $this->fetchRow($sqlQuery, array("id" => $id));
    }

    protected function exec($sqlQuery) {
      $this->dbms->exec($sqlQuery);
    }
  }
}