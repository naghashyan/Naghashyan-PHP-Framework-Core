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

namespace ngs\dal\connectors;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;

class ElasticsearchDBMS
{

    /**
     * Singleton instance of class
     */
    private static ?self $instance = null;

    private ?Client $client = null;

    /**
     * Tries to connect to a Solr Server
     * SolrDBMS constructor.
     * @param array $hosts
     */
    public function __construct(array $hosts)
    {
        $this->client = ClientBuilder::create()
            ->setHosts($hosts)
            ->build();
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     *
     * Returns an singleton instance of class.
     *
     * array $hosts
     * @return self
     */
    public static function getInstance(array $hosts): self
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($hosts);
        }
        return self::$instance;
    }


}