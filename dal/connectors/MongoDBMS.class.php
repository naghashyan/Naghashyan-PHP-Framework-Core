<?php
/**
 * ImprovedDBMS class uses MySQL Improved Extension to access DB.
 * This class provides full transaction support instead of DBMS class.
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @package framework.dal.connectors
 * @version 2.0.0
 * @year 2014-2015
 * @copyright Naghashyan Solutions LLC
 */
namespace ngs\framework\dal\connectors {
	use ngs\framework\exceptions\DebugException;
	class MongoDBMS extends \MongoClient {

		/**
		 * Singleton instance of class
		 */
		private static $instance = NULL;

		/**
		 * Object which represents the connection to a MySQL Server
		 */
		private $dbName = null;
		private $stmt = null;
		/**
		 * Tries to connect to a MySQL Server
		 */
		public function __construct($db_host, $db_user, $db_pass, $db_name) {
			$this->dbName = $db_name;
			if($db_user != "" || $db_pass != ""){
				$mongoAuth = $uri = $db_user.":".$db_pass."@";
			}
			$uri = "mongodb://".$mongoAuth.$db_host."/".$db_name;
			parent::__construct($uri);
		}

		/**
		 * Returns an singleton instance of class.
		 *
		 * @return
		 */
		public static function getInstance($db_host, $db_user, $db_pass, $db_name) {
			if (is_null(self::$instance)) {
				self::$instance = new MongoDBMS($db_host, $db_user, $db_pass, $db_name);
				self::$instance = self::$instance->$db_name;
			}
			return self::$instance;
		}
		
		public function insert($params){
			
		}

	}

}
