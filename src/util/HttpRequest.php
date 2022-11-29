<?php

/**
 * Helper wrapper class for php curl
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site https://naghashyan.com
 * @year 2010-2021
 * @package ngs.framework.util
 * @version 4.0.0
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

class HttpRequest
{

    private $curl = null;
    private $params = null;
    private $reqParams = null;
    private $toFile = false;
    private $optArr = [];
    private array $headers = [];
    private $responseStatus;
    private $responseBody;
    private $responseHeaders;
    private $responseCookies;
    private array $uploadData = [];
    private array $methodList = [
        'get' => 'GET',
        'post' => 'POST',
        'put' => 'PUT',
        'delete' => 'DELETE',
        'patch' => 'PATCH'
    ];

    public function __construct()
    {

    }

    public function setOpt($key, $value)
    {
        $this->optArr[$key] = $value;
    }

    private function emptyOpt(): void
    {
        $this->optArr = [];
    }

    private function getOpt()
    {
        return $this->optArr;
    }

    public function setToFile($toFile)
    {
        $this->toFile = $toFile;
    }

    public function addHeader(string $key, string $value): void
    {
        $this->headers[$key] = $value;
    }

    /**
     * set bulk headers
     * @param array $headers
     */
    public function setHeaders(array $headers): void
    {
        $this->headers = array_merge($this->headers, $headers);
    }

    /**
     * set bulk cookie
     *
     * @param array $cookies
     */
    public function setCookies(array $cookies): void
    {
        $reqCookies = '';
        $semi = '';
        foreach ($cookies as $key => $value) {
            $reqCookies .= $semi . $key . '=' . $value;
            $semi = ';';
        }
        $this->setOpt(CURLOPT_COOKIE, $reqCookies);
    }

    /**
     * set single cookie
     * @param string $cookie
     */
    public function setCookie(string $cookie): void
    {
        $this->setOpt(CURLOPT_COOKIE, $cookie);
    }

    public function setProxy(string $proxyHost, int $port, ?string $userName = null, ?string $password = null): void
    {
        $this->setOpt(CURLOPT_PROXY, $proxyHost . ':' . $port);
        if ($userName && $password) {
            $this->setOpt(CURLOPT_PROXYUSERPWD, $userName . ':' . $password);
        }
        $this->setOpt(CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
    }

    public function setPostData($data)
    {
        $data = array_merge($data, $this->uploadData);
        $this->setOpt(CURLOPT_CUSTOMREQUEST, 'POST');
        $this->setOpt(CURLOPT_POSTFIELDS, $data);
    }

    public function setPutData($data)
    {
        $this->setOpt(CURLOPT_CUSTOMREQUEST, 'PUT');
        $this->setOpt(CURLOPT_POSTFIELDS, $data);
    }

    public function setPatchData($data)
    {
        $this->setOpt(CURLOPT_CUSTOMREQUEST, 'PATCH');
        $this->setOpt(CURLOPT_POSTFIELDS, $data);
    }

    public function setDeleteData($data)
    {
        $this->setOpt(CURLOPT_CUSTOMREQUEST, 'DELETE');
        $this->setOpt(CURLOPT_POSTFIELDS, $data);
    }


    public function setUserAgent(string $userAgent)
    {
        $this->setOpt(CURLOPT_USERAGENT, $userAgent);
    }

    /**
     * @param string $key
     * @param string $file
     */

    public function setUploadFile(string $key, string $file)
    {
        $this->uploadData[$key] = curl_file_create($file);
    }

    public function useSSL()
    {
        $this->setOpt(CURLOPT_SSL_VERIFYPEER, FALSE);
    }

    /**
     * @param string $userName
     * @param string $password
     */
    public function setBasicAuth(string $userName, string $password): void
    {
        $this->setOpt(CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        $this->setOpt(CURLOPT_USERPWD, $userName . ':' . $password);
    }

    public function setParams($params)
    {
        if (!$this->reqParams) {
            return;
        }
        $this->reqParams .= '?';

        foreach ($this->reqParams as $key => $value) {
            $this->reqParams .= urlencode($key) . '=' . urlencode($value) . '&';
        }
    }

    public function setTimeout($timeout)
    {
        $this->setOpt(CURLOPT_TIMEOUT, $timeout);
    }

    /**
     * @param string $url
     * @param array $params
     * @param string $method
     * @return bool|true
     * @throws \JsonException
     */
    public function jsonRequest(string $url, array $params, string $method = 'post'): ?string
    {
        if (!isset($this->methodList[strtolower($method)])) {
            return false;
        }
        $data = array_merge($params, $this->uploadData);
        $jsonParams = json_encode($params, JSON_THROW_ON_ERROR, 512);
        $this->setOpt(CURLOPT_CUSTOMREQUEST, $this->methodList[strtolower($method)]);
        $this->setOpt(CURLOPT_POSTFIELDS, $jsonParams);

        $this->setHeaders(['Content-Type' => 'application/json', 'Content-Length' => strlen($jsonParams)]);
        return $this->request($url);
    }

    /* @desc for initiating http get request
     * @access public
     * @param string url of requesting host
     * @return string|null if all is OK, false if thereare errors
     */

    public function request($url): ?string
    {
        // set URL
        if ($this->reqParams !== null) {
            $url .= $this->reqParams;
        }
        $curl = \curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        if ($this->toFile) {
            $fp = fopen($this->toFile, 'w');
            $this->setOpt(CURLOPT_HEADER, 0);
            $this->setOpt(CURLOPT_FILE, $fp);
            curl_setopt_array($curl, $this->getOpt());
            $response = curl_exec($curl);
            fclose($fp);
            return $response;
        }
        $reqHeaders = [];
        foreach ($this->headers as $key => $value) {
            $reqHeaders[] = $key . ':' . $value;
        }
        $this->setOpt(CURLOPT_HTTPHEADER, $reqHeaders);
        $this->setOpt(CURLOPT_HEADER, TRUE);
        // Write result to variable
        $this->setOpt(CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt_array($curl, $this->getOpt());// write content in $doc
        $response = curl_exec($curl);
        $this->setResponseStatus(curl_getinfo($curl, CURLINFO_HTTP_CODE));
        $header = curl_getinfo($curl);
        // close connection
        $this->emptyOpt();
        curl_setopt($curl, CURLINFO_HEADER_OUT, true); // enable tracking


        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $this->setBody(substr($response, $header_size));
        curl_close($curl);
        $this->setRHeaders($header);
        return $response;
    }

    private function setRHeaders($headers): void
    {
        $resCookies = [];
        foreach ($headers as $headerKey => $header) {
            // Setting cookies
            if ($headerKey === 'Set-Cookie') {
                $cookieIndex = strpos($header, '=');
                $cookieLastIndex = strpos(substr($header, $cookieIndex + 1), ';');
                $resCookies[substr($header, 0, $cookieIndex)] = substr($header, $cookieIndex + 1, $cookieLastIndex);
            }
        }
        $this->setResponseHeader($headers);
        $this->setResponseCookies($resCookies);
    }

    public function setBody($body): void
    {
        $this->responseBody = $body;
    }

    private function setResponseStatus(string $status): void
    {
        $this->responseStatus = $status;
    }

    private function setResponseHeader(array $headers): void
    {
        $this->responseHeaders = $headers;
    }

    public function setResponseCookies(array $cookies): void
    {
        $this->responseCookies = $cookies;
    }

    public function getBody()
    {
        return $this->responseBody;
    }

    /**
     *
     * get json decoded response
     *
     * @param bool $toArray
     * @return array|null
     */
    public function getJsonResponse(bool $toArray = true): ?array
    {
        try {
            $response = $this->getBody();
            if (!$response) {
                return null;
            }
            return json_decode($response, true, 512, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (\Exception $exception) {
            return null;
        }
    }

    public function getResponseStatus(): int
    {
        return (int)$this->responseStatus;
    }

    public function getResponseHeader(): ?array
    {
        return $this->responseHeaders;
    }

    public function getResponseCookies(): ?array
    {
        return $this->responseCookies;
    }

}
