<?php

/**
 * Helper wrapper class for php curl
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @year 2014-2023
 * @package ngs.framework.util
 * @version 5.0.0
 *
 * This file is part of the NGS package.
 *
 * @copyright Naghashyan Solutions LLC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace ngs\util;

class HttpUtils
{
    /**
     * detect if request call from ajax or not
     * @static
     * @access
     * @return bool|true|false
     */
    public function isAjaxRequest(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }

    public function getRequestProtocol(): string
    {
        if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || $_SERVER['SERVER_PORT'] === 443) {
            $protocol = 'https:';
        } else {
            $protocol = 'http:';
        }

        return $protocol;
    }

    public function getHost(bool $main = false): ?string
    {
        $httpHost = $this->_getHttpHost($main);
        if (!$httpHost) {
            return null;
        }
        $array = explode('.', $httpHost);
        return (array_key_exists(count($array) - 2, $array) ? $array[count($array) - 2] : '') . '.' . $array[count($array) - 1];
    }

    public function getHttpHost(bool $withPath = false, bool $withProtacol = false, bool $main = false): ?string
    {
        $httpHost = $this->_getHttpHost($main);
        if ($httpHost == null) {
            return null;
        }
        if ($withPath) {
            $httpHost = '//' . $httpHost;
            if ($withProtacol) {
                $httpHost = $this->getRequestProtocol() . $httpHost;
            }
        }
        return $httpHost;
    }

    public function getHttpHostByNs(string $ns = '', bool $withProtocol = false): ?string
    {
        $httpHost = $this->getHttpHost(true, $withProtocol);
        if (!$httpHost) {
            return null;
        }
        if (NGS()->getModulesRoutesEngine()->getModuleType() === 'path') {
            if ($ns == '') {
                return $httpHost;
            }
            return $httpHost . '/' . NGS()->getModulesRoutesEngine()->getModuleUri();
        }
        if ($ns == '') {
            return $httpHost;
        }
        return $this->getHttpHost(true, $withProtocol) . '/' . $ns;
    }

    public function getNgsStaticPath(string $ns = '', bool $withProtocol = false): ?string
    {
        return $this->getHttpHostByNs($ns, $withProtocol);
    }

    public function getRequestUri(bool $full = false): ?string
    {
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        if (!$uri) {
            return null;
        }
        if (strpos($uri, '?') !== false) {
            $uri = substr($uri, 0, strpos($uri, '?'));
        }
        if ($full === false && NGS()->getModulesRoutesEngine()->getModuleType() == 'path') {
            $delim = '';
            if (strpos($uri, NGS()->getModulesRoutesEngine()->getModuleUri() . '/') !== false) {
                $delim = '/';
            }
            $uri = str_replace(NGS()->getModulesRoutesEngine()->getModuleUri() . $delim, '', $uri);
        }
        return $uri;
    }

    /**
     * Return a thingie based on $paramie
     * @abstract
     * @access
     * @param boolean $paramie
     * @return integer|babyclass
     */
    public function redirect(string $url, string $module = ''): void
    {
        header('location: ' . $this->getHttpHostByNs($module, true) . '/' . $url);
    }

    public function getMainDomain(): ?string
    {
        $httpHost = $this->_getHttpHost(true);
        if (!$httpHost) {
            return null;
        }
        $pieces = parse_url($httpHost);
        $domain = isset($pieces['path']) ? $pieces['path'] : '';
        if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs)) {
            return $regs['domain'];
        }
        return false;
    }


    public function getHostPath(): ?string
    {
        $httpHost = $this->_getHttpHost(true);
        if (!$httpHost) {
            return null;
        }
        $pieces = parse_url($httpHost);
        return $pieces['path'];
    }

    public function _getHttpHost(bool $main = false): ?string
    {
        $ngsHost = null;
        if (NGS()->get('HTTP_HOST')) {
            $ngsHost = NGS()->get('HTTP_HOST');
        } elseif (isset($_SERVER['HTTP_HOST'])) {
            $ngsHost = $_SERVER['HTTP_HOST'];
        }
        return $ngsHost;
    }

    public function getSubdomain(): ?string
    {
        $domain = $this->_getHttpHost(true);
        if (!$domain) {
            return null;
        }
        $parsedUrl = parse_url($domain);
        if (!isset($parsedUrl['path'])) {
            return null;
        }
        $host = explode('.', $parsedUrl['path']);
        if (count($host) >= 3) {
            return $host[0];
        }
        return null;
    }


}