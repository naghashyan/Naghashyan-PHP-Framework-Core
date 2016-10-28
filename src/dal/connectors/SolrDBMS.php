<?php
/**
 *
 * Solr server connector wrapper for NGS framework
 * using Solarium
 *
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @mail levon@naghashyan.com
 * @package framework.dal.connectors
 * @version 3.1.0
 * @year 2016
 * @copyright Naghashyan Solutions LLC
 */
namespace ngs\dal\connectors {
  class SolrDBMS extends \Solarium\Client {

    /**
     * Singleton instance of class
     */
    private static $instance = NULL;

    /**
     * Tries to connect to a Solr Server
     * @param string $db_host
     * @param string $db_port
     * @param string $db_path
     *
     */
    public function __construct($db_host, $db_port, $db_path) {
      $config = array(
        'endpoint' => array(
          'localhost' => array(
            'host' => $db_host,
            'port' => $db_port,
            'path' => $db_path,
          )
        )
      );
      parent::__construct($config);

    }

    /**
     * Returns an singleton instance of class.
     *
     * @param string $db_host
     * @param string $db_port
     * @param string $db_path
     *
     * @return SolrDBMS $instance
     */
    public static function getInstance($db_host, $db_port, $db_path) {
      if (is_null(self::$instance)){
        self::$instance = new SolrDBMS($db_host, $db_port, $db_path);
      }
      return self::$instance;
    }
  }

}