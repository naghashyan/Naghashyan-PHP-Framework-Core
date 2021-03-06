<?php

/**
 * Helper wrapper class for php curl
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @year 2010-2016
 * @package ngs.framework.util
 * @version 3.1.0
 *
 * This file is part of the NGS package.
 *
 * @copyright Naghashyan Solutions LLC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace ngs\util {

  class HttpRequest {

    private $curl = null;
    private $params = null;
    private $reqParams = null;
    private $toFile = false;
    private $optArr = array();
    private $responseStatus;
    private $responseBody;
    private $responseHeaders;
    private $responseCookies;
    private $uploadData = [];
    private $methodList = [
      'get' => 'GET',
      'post' => 'POST',
      'put' => 'PUT',
      'delete' => 'DELETE',
      'patch' => 'PATCH'
    ];

    public function __construct() {

    }

    private function setOpt($key, $value) {
      $this->optArr[$key] = $value;
    }

    private function emptyOpt() {
      $this->optArr = array();
    }

    private function getOpt() {
      return $this->optArr;
    }

    public function setToFile($toFile) {
      $this->toFile = $toFile;
    }

    public function setHeaders($headers) {
      $reqHeaders = array();
      foreach ($headers as $key => $value){
        $reqHeaders[] = $key . ':' . $value;
      }
      $this->setOpt(CURLOPT_HTTPHEADER, $reqHeaders);
      $this->setOpt(CURLOPT_HEADER, TRUE);
    }

    public function setCookies($cookies) {
      $reqCookies = '';
      $delim = '';
      foreach ($cookies as $key => $value){
        $reqCookies .= $delim . $key . '=' . $value;
        $delim = ';';
      }
      $this->setOpt(CURLOPT_COOKIE, $reqCookies);
    }

    public function setPostData($data) {
      $data = array_merge($data, $this->uploadData);
      $this->setOpt(CURLOPT_CUSTOMREQUEST, 'POST');
      $this->setOpt(CURLOPT_POSTFIELDS, $data);
    }

    public function setPutData($data) {
      $this->setOpt(CURLOPT_CUSTOMREQUEST, 'PUT');
      $this->setOpt(CURLOPT_POSTFIELDS, $data);
    }

    public function setPatchData($data) {
      $this->setOpt(CURLOPT_CUSTOMREQUEST, 'PATCH');
      $this->setOpt(CURLOPT_POSTFIELDS, $data);
    }

    public function setDeleteData($data) {
      $this->setOpt(CURLOPT_CUSTOMREQUEST, 'DELETE');
      $this->setOpt(CURLOPT_POSTFIELDS, $data);
    }

    /**
     * @param string $key
     * @param string $file
     */

    public function setUploadFile(string $key, string $file) {
      $this->uploadData[$key] = curl_file_create($file);
    }

    public function useSSL() {
      $this->setOpt(CURLOPT_SSL_VERIFYPEER, FALSE);
    }

    public function setBasicAuth($userName, $password) {
      $this->setOpt(CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
      $this->setOpt(CURLOPT_USERPWD, $userName . ':' . $password);
    }

    public function setParams($params) {
      if (!$this->reqParams){
        return;
      }
      $this->reqParams .= '?';

      foreach ($this->reqParams as $key => $value){
        $this->reqParams .= urlencode($key) . '=' . urlencode($value) . '&';
      }
    }

    public function setTimeout($timeout) {
      $this->setOpt(CURLOPT_TIMEOUT, $timeout);
    }

    public function jsonRequest(string $url, array $params, string $method = 'post') {
      if (!isset($this->methodList[strtolower($method)])){
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
     * @return true if all is OK, false if thereare errors
     */

    public function request($url) {
      // set URL
      if ($this->reqParams != null){
        $url .= $this->reqParams;
      }
      $curl = \curl_init($url);
      if ($this->toFile){
        $fp = fopen($this->toFile, 'w');
        $this->setOpt(CURLOPT_HEADER, 0);
        $this->setOpt(CURLOPT_FILE, $fp);
        curl_setopt_array($curl, $this->getOpt());
        $response = curl_exec($curl);
        fclose($fp);
        return $response;
      }
      $this->setOpt(CURLOPT_HEADER, true);
      // Write result to variable
      $this->setOpt(CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt_array($curl, $this->getOpt());
      // write content in $doc

      $response = curl_exec($curl);
      $this->setResponseStatus(curl_getinfo($curl, CURLINFO_HTTP_CODE));
      $header = curl_getinfo($curl);
      // close connection
      $this->emptyOpt();

      $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
      $this->setBody(substr($response, $header_size));
      curl_close($curl);
      $this->setResponseHeader($header);

      return true;
    }

    private function setRHeaders($headers) {
      $resHeaders = null;
      $resCookies = null;
      for ($i = 0; $i < count($headers); $i++){
        $headerChuncks = array();
        preg_match('/(.+?):\s*(.+)/', trim($headers[0][$i]), $headerChuncks);
        $resHeaders[$headerChuncks[1]] = $headerChuncks[2];

        // Setting cookies
        if ($headerChuncks[1] == 'Set-Cookie'){
          $cookieIndex = strpos($headerChuncks[2], '=');
          $cookieLastIndex = strpos(substr($headerChuncks[2], $cookieIndex + 1), ';');
          $resCookies[substr($headerChuncks[2], 0, $cookieIndex)] = substr($headerChuncks[2], $cookieIndex + 1, $cookieLastIndex);
        }
      }
      $this->setResponseHeader($resHeaders);
      $this->setResponseCookies($resCookies);
    }

    public function setBody($body) {
      $this->responseBody = $body;
    }

    public function setResponseStatus($status) {
      $this->responseStatus = $status;
    }

    public function setResponseHeader($headers) {
      $this->responseHeaders = $headers;
    }

    public function setResponseCookies($cookies) {
      $this->responseCookies = $cookies;
    }

    public function getBody() {
      return $this->responseBody;
    }

    /**
     *
     * get json decoded response
     *
     * @param bool $toArray
     * @return array|null
     */
    public function getJsonResponse(bool $toArray = true): ?array {
      try{
        $response = $this->getBody();
        if (!$response){
          return null;
        }
        return json_decode($response, true, 512, JSON_THROW_ON_ERROR);
      } catch (\Exception $exception){
        return null;
      }
    }

    public function getResponseStatus() {
      return $this->responseStatus;
    }

    public function getResponseHeader() {
      return $this->responseHeaders;
    }

    public function getResponseCookies() {
      return $this->responseCookies;
    }

  }

}
