<?php

/**
 * Helper wrapper class for php curl
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @year 2010-2015
 * @package ngs.framework.util
 * @version 2.0.0
 * 
 * This file is part of the NGS package.
 *
 * @copyright Naghashyan Solutions LLC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace ngs\framework\util {

    class HttpGetRequest {

        private $curl = null;
        private $params = null;
        private $reqParams = null;
        private $toFile = false;
        private $optArr = array();
        private $responseStatus;
        private $responseBody;
        private $responseHeaders;
        private $responseCookies;

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
            foreach ($headers as $key => $value) {
                $reqHeaders[] = "$key: $value";
            }
            $this->setOpt(CURLOPT_HTTPHEADER, $reqHeaders);
            $this->setOpt(CURLOPT_HEADER, TRUE);
        }

        public function setCookies($cookies) {
            $reqCookies = "";
            $delim = "";
            foreach ($cookies as $key => $value) {
                $reqCookies .= $delim . $key . "=" . $value;
                $delim = ";";
            }
            $this->setOpt(CURLOPT_COOKIE, $reqCookies);
        }

        public function setPostData($data) {
            $this->setOpt(CURLOPT_CUSTOMREQUEST, "POST");
            $this->setOpt(CURLOPT_POSTFIELDS, $data);
        }

        public function setPutData($data) {
            $this->setOpt(CURLOPT_CUSTOMREQUEST, "PUT");
            $this->setOpt(CURLOPT_POSTFIELDS, $data);
        }

        public function setPatchData($data) {
            $this->setOpt(CURLOPT_CUSTOMREQUEST, "PATCH");
            $this->setOpt(CURLOPT_POSTFIELDS, $data);
        }

        public function setDeleteData($data) {
            $this->setOpt(CURLOPT_CUSTOMREQUEST, "DELETE");
            $this->setOpt(CURLOPT_POSTFIELDS, $data);
        }

        public function useSSL() {
            $this->setOpt(CURLOPT_SSL_VERIFYPEER, FALSE);
        }

        public function setBasicAuth($userName, $password) {
            $this->setOpt(CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            $this->setOpt(CURLOPT_USERPWD, $userName . ":" . $password);
        }

        public function setParams($params) {
            if(!$this->reqParams){
                return;
            }
            $this->reqParams .= "?";

            foreach ($this->reqParams as $key => $value) {
                $this->reqParams .= urlencode($key) . "=" . urlencode($value) . "&";
            }
        }

        public function setTimeout($timeout) {
            $this->setOpt(CURLOPT_TIMEOUT, $timeout);
        }

        /* @desc for initiating http get request
         * @access public
         * @param url of requesting host
         * @return true if all is OK, false if thereare errors
         */

        public function request($url) {
            // set URL
            if ($this->reqParams != null) {
                $url .= $this->reqParams;
            }
            $curl = \curl_init($url);
            if ($this->toFile) {
                $curl = $this->getCurlConnection();
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

            //  Setting response body
          
//            $this->setBody(substr($response, strpos($response, "\n\r\n")));
            $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
            $this->setBody(substr($response, $header_size));
            curl_close($curl);
            $this->setResponseHeader($header);

            return true;
        }

        private function setRHeaders($headers) {
            $resHeaders = null;
            $resCookies = null;
            for ($i = 0; $i < count($headers); $i++) {
                $headerChuncks = array();
                preg_match('/(.+?):\s*(.+)/', trim($headers[0][$i]), $headerChuncks);
                $resHeaders[$headerChuncks[1]] = $headerChuncks[2];

                // Setting cookies
                if ($headerChuncks[1] == "Set-Cookie") {
                    $cookieIndex = strpos($headerChuncks[2], "=");
                    $cookieLastIndex = strpos(substr($headerChuncks[2], $cookieIndex + 1), ";");
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
