<?php
/**
 * MysqlPDO class uses MySQL PHP PDO Extension to access DB.
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site https://naghashyan.com
 * @mail levon@naghashyan.com
 * @package ngs.framework.dal.connectors
 * @version 4.2.1
 * @year 2009-2022
 *
 * This file is part of the NGS package.
 *
 * @copyright Naghashyan Solutions LLC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace ngs\dal\connectors;

use ngs\exceptions\DebugException;
use PDO;
use PDOException;
use PDOStatement;

class MysqlPDO extends PDO
{

    /**
     * Singleton instance of class
     */
    private static array $instances = [];

    /**
     * Tries to connect to a MySQL Server
     * @param string $host
     * @param string $user
     * @param string $password
     * @param string $database
     * @param string $chars
     */
    public function __construct(string $host, string $user, string $password, string $database, string $chars = 'utf8')
    {
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
    public static function getInstance(string $host, string $user, string $password, string $database, string $chars = 'utf8'): MysqlPDO
    {
        $key = md5($host . '_' . $user . '_' . $password . '_' . $database);

        if (!isset(self::$instances[$key])) {
            self::$instances[$key] = new MysqlPDO($host, $user, $password, $database, $chars);
        }
        return self::$instances[$key];
    }

    /**
     * @param string $query
     * @param array $options
     * @return PDOStatement|false
     * @throws DebugException
     */
    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        try {
            return parent::prepare($query, $options);
        } catch (PDOException $ex) {
            throw new DebugException($ex->getMessage(), $ex->getCode());
        }
    }


}
