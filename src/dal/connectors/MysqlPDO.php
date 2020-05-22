<?php
/**
 * MysqlPDO class uses MySQL PHP PDO Extension to access DB.
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site https://naghashyan.com
 * @mail levon@naghashyan.com
 * @package ngs.framework.dal.connectors
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

namespace ngs\dal\connectors {

  use ngs\exceptions\DebugException;
  use PDO;
  use PDOException;
  use PDOStatement;

  class MysqlPDO extends PDO {

    /**
     * Singleton instance of class
     */
    private static ?MysqlPDO $instance = null;

    /**
     * Tries to connect to a MySQL Server
     * @param string $host
     * @param string $user
     * @param string $password
     * @param string $database
     * @param string $chars
     */
    public function __construct(string $host, string $user, string $password, string $database, string $chars = 'utf8') {
      parent::__construct('mysql:dbname=' . $database . ';host=' . $host . ';charset=' . $chars, $user, $password);
      $this->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
      $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, [MysqlDBStatement::class]);

    }

    /**
     * Returns an singleton instance of class.
     *
     * @param string $host
     * @param string $user
     * @param string $password
     * @param string $database
     * @param string $chars
     * @return MysqlPDO
     */
    public static function getInstance(string $host, string $user, string $password, string $database, string $chars = 'utf8'): MysqlPDO {
      if (self::$instance === null){
        self::$instance = new MysqlPDO($host, $user, $password, $database, $chars);
      }
      return self::$instance;
    }

    /**
     * @param string $statement
     * @param array $driverOptions
     * @return bool|PDOStatement
     * @throws DebugException
     */
    public function prepare($statement, $driverOptions = []) {
      try{
        return parent::prepare($statement, $driverOptions);
      } catch (PDOException $ex){
        throw new DebugException($ex->getMessage(), $ex->getCode());
      }
    }

  }

}
