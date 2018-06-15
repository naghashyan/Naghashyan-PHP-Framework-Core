<?php
/**
 * MysqlPDO class uses MySQL PHP PDO Extension to access DB.
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @mail levon@naghashyan.com
 * @package ngs.framework.dal.connectors
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
namespace ngs\dal\connectors {

  use ngs\exceptions\DebugException;

  class MysqlPDO extends \PDO {

    /**
     * Singleton instance of class
     */
    private static $instance = NULL;

    /**
     * Object which represents the connection to a MySQL Server
     */
    private $link = null;
    private $stmt = null;

    /**
     * Tries to connect to a MySQL Server
     */
    public function __construct($db_host, $db_user, $db_pass, $db_name) {
      parent::__construct('mysql:dbname=' . $db_name . ';host=' . $db_host . ';charset=utf8', $db_user, $db_pass);
      $this->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
      $this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
      $this->setAttribute(\PDO::ATTR_STATEMENT_CLASS, array('\ngs\dal\connectors\DBStatement'));

    }

    /**
     * Returns an singleton instance of class.
     *
     * @return
     */
    public static function getInstance($db_host, $db_user, $db_pass, $db_name) {
      if (is_null(self::$instance)){
        self::$instance = new MysqlPDO($db_host, $db_user, $db_pass, $db_name);
      }
      return self::$instance;
    }

    public function prepare($statement, $driver_options = array()) {
      try{
        return parent::prepare($statement, $driver_options);
      } catch (\PDOException $ex){
        throw new DebugException($ex->getMessage(), $ex->getCode());
      }
    }

  }

  class DBStatement extends \PDOStatement {
    public function execute($bound_input_params = NULL) {
      try{
        return parent::execute($bound_input_params);
      } catch (\PDOException $ex){
        throw new DebugException($ex->getMessage(), $ex->getCode());
      }
    }
  }

}
