<?php
/**
 * default ngs routing class
 * this class by default used from dispacher
 * for matching url with routes
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site https://naghashyan.com
 * @year 2014-2019
 * @package ngs.framework.routes
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

namespace ngs\routes {

  class NgsLoadMapper {

    private $nestedLoad = [];
    private $globalParentLoad;
    private $permalink = '';
    private $ngsQueryParams = [];

    public function setNestedLoads($parent, $nl, $params) {
      if (!isset($parent)){
        return;
      }
      $this->nestedLoad[$parent][] = ['action' => $nl, 'params' => $params];
    }

    public function getNestedLoads(): array {
      return $this->nestedLoad;
    }

    public function setGlobalParentLoad($globalParentLoad = '') {
      $this->globalParentLoad = $globalParentLoad;
    }

    public function getGlobalParentLoad() {
      return $this->globalParentLoad;
    }

    /**
     * @return string
     */
    public function getPermalink(): string {
      return $this->permalink;
    }

    /**
     * @param string $permalink
     */
    public function setPermalink(string $permalink): void {
      if ($permalink === ''){
        return;
      }
      $this->permalink = $permalink . '/' . $this->permalink;
    }

    /**
     * @param array $queryParamsArr
     */
    public function setNgsQueryParams(array $queryParamsArr): void {
      $this->ngsQueryParams = array_merge($queryParamsArr, $this->ngsQueryParams);
    }

    /**
     * @return string
     */
    public function getNgsQueryParams(): string {
      if (count($this->ngsQueryParams) === 0){
        return '';
      }
      return '?' . http_build_query($this->ngsQueryParams, '', '&');
    }

    /**
     * @return string
     */
    public function getNgsPermalink(): string {
      $permalink = '';
      if ($this->getPermalink() !== ''){
        $permalink = $this->getPermalink();
        if (strrpos($permalink, '/') + 1 === strlen($permalink)){
          $permalink = substr($permalink, 0, strlen($permalink) - 1);
        }
      }
      if ($this->getNgsQueryParams() !== ''){
        $permalink .= $this->getNgsQueryParams();
      }

      return $permalink;
    }


  }
}