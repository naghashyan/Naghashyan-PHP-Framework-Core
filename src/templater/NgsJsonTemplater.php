<?php
/**
 * NGS predefined templater class
 * handle smarty and json responses
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @package ngs.framework.templater
 * @version 2.5.0
 * @year 2010-2016
 *
 * This file is part of the NGS package.
 *
 * @copyright Naghashyan Solutions LLC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ngs\templater {


  class NgsJsonTemplater {

    /**
     * constructor
     * reading Smarty config and setting up smarty environment accordingly
     */
    private $smarty = null;
    private $template = null;
    private $params = array();
    private $permalink = null;
    private $smartyParams = array();
    private $httpStatusCode = 200;


    /**
     * assign single json parameter
     *
     * @access public
     *
     * @param String $name
     * @param mixed $value
     *
     * @return void
     */
    public function assign($key, $value) {
      $this->params[$key] = $value;
    }

    /**
     * add multiple json parameters
     *
     * @access public
     * @param array $paramsArr
     *
     * @return void
     */
    public function assignParams($paramsArr) {
      if (!is_array($paramsArr)){
        $paramsArr = [$paramsArr];
      }
      $this->params = array_merge($this->params, $paramsArr);
    }

    /**
     * set template
     *
     * @param String $template
     *
     */
    public function setTemplate($template) {
      $this->template = $template;
    }

    /**
     * Return a template
     *
     * @return String $template
     */
    public function getTemplate() {
      return $this->template;
    }


    public function fetch() {
      $this->renderTemplate();
      var_dump($this->params);exit;

    }

    private function renderTemplate(){
      $jsonTemplate = json_decode(file_get_contents($this->getTemplate()), true);
      foreach ($jsonTemplate as $key=>$value){
        if(strpos($value, '{$') === false){
          //todo
        }
        preg_match_all('/\{\$(.*)(->|\[])(.*)\}/', $value, $matches);
        if(isset($matches[1][0]) && isset($this->params[$matches[1][0]])){

        }
        var_dump($value);
        var_dump($matches[1][0]);exit;

      }
      $results = print_r($jsonTemplate, true);
      var_dump($results);exit;
     // eval($results);exit;

    }


  }

}
