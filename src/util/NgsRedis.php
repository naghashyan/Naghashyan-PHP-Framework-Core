<?php
/**
 * Redis client util helper class
 *
 * @author Levon Naghashyan
 * @site https://naghashyan.com
 * @mail levon@naghashyan.com
 * @package IM.util
 * @version 1.0.0
 * @year 2020
 */

namespace ngs\util {


    use ngs\exceptions\DebugException;
    use Redis;
    use RedisCluster;
    use RedisClusterException;

    class NgsRedis
    {

        private static ?NgsRedis $instance = null;
        private ?string $host = null;
        private ?int $port;
        private ?string $type = 'single';

        public function __construct(?string $host = null, ?int $port = 6379, string $type = 'single')
        {
            if ($host === null) {
                $this->host = NGS()->getConfig()->DB->redis->host;
                $this->port = NGS()->getConfig()->DB->redis->port;
                if (isset(NGS()->getConfig()->DB->redis->type)) {
                    $this->type = NGS()->getConfig()->DB->redis->type;
                }
                return;
            }
            $this->host = $host;
            $this->port = $port;
            $this->type = $type;
        }

        /**
         * @param string|null $host
         * @param int|null $port
         * @param string $type
         * @return NgsRedis
         */
        public static function getInstance(?string $host = null, ?int $port = 6379, string $type = 'single'): NgsRedis
        {
            if (self::$instance === null) {
                self::$instance = new self($host, $port, $type);
            }
            return self::$instance;
        }

        /**
         * @return Redis|RedisCluster
         * @throws RedisClusterException
         */
        public function getClient()
        {
            if ($this->type && $this->type === 'cluster') {
                return $this->createRedisClusterConnection();
            }
            return $this->createRedisSingleConnection();
        }

        /**
         * @return Redis
         */
        private function createRedisSingleConnection(): Redis
        {
            $redisClient = new Redis();
            $redisClient->connect($this->host, $this->port);
            return $redisClient;
        }

        /**
         * @return RedisCluster
         * @throws RedisClusterException
         */
        private function createRedisClusterConnection(): RedisCluster
        {
            return new RedisCluster(NULL, [$this->host], 0.1, 0.1);
        }


    }

}
