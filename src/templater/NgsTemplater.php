<?php
/**
 * NGS predefined templater class
 * handle smarty and json responses
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site https://naghashyan.com
 * @package ngs.framework.templater
 * @version 4.0.0
 * @year 2010-2020
 *
 * This file is part of the NGS package.
 *
 * @copyright Naghashyan Solutions LLC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ngs\templater;

class NgsTemplater extends AbstractTemplater
{

    /**
     * constructor
     * reading Smarty config and setting up smarty environment accordingly
     */
    private ?NgsSmartyTemplater $smarty = null;
    private ?string $template = null;
    private array $components = [];
    private array $params = [];
    private ?string $permalink = null;
    private array $smartyParams = [];
    private int $httpStatusCode = 200;
    private string $type = 'json';
    private bool $ngsFromException = false;

    public function __construct()
    {
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getSmartyTemplater(): NgsSmartyTemplater
    {
        if ($this->smarty) {
            return $this->smarty;
        }
        $this->smarty = new NgsSmartyTemplater();
        return $this->smarty;
    }

    /**
     * @return bool
     */
    public function isNgsFromException(): bool
    {
        return $this->ngsFromException;
    }

    /**
     * @param bool $ngsFromException
     */
    public function setNgsFromException($ngsFromException)
    {
        $this->ngsFromException = $ngsFromException;
    }


    /**
     * assign single smarty parameter
     *
     * @access public
     *
     * @param String $name
     * @param mixed $value
     *
     * @return void
     */
    public function assign(string $key, mixed $value): void
    {
        $this->smartyParams[$key] = $value;
    }

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
    public function assignJson(string $key, mixed $value): void
    {
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
    public function assignJsonParams(array $paramsArr): void
    {
        if (!is_array($paramsArr)) {
            $paramsArr = [$paramsArr];
        }
        $this->params = array_merge($this->params, $paramsArr);
    }

    /**
     * assign single web components
     *
     * @access public
     *
     * @param String $name
     * @param mixed $value
     *
     * @return void
     */
    public function assignComponent(string $name, mixed $value): void
    {
        $this->components[$name] = $value;
    }

    public function getJsonParamByKey(string $key): mixed
    {
        return $this->params[$key] ?? null;
    }

    /**
     * set template
     *
     * @param String $template
     *
     */
    public function setTemplate(string $template): void
    {
        $this->template = $template;
    }

    /**
     * Return a template
     *
     * @return String $template
     */
    public function getTemplate(): null|string
    {
        return $this->template;
    }

    /**
     * set template
     *
     * @param String $template
     *
     */
    public function setPermalink(string $permalink): void
    {
        $this->permalink = $permalink;
    }

    /**
     * Return a template
     *
     * @return String $template|null
     */
    public function getPermalink(): null|string
    {
        return $this->permalink;
    }

    /**
     * set response http status code
     *
     * @param integer $httpStatusCode
     *
     */
    public function setHttpStatusCode(int $httpStatusCode): void
    {
        $this->httpStatusCode = $httpStatusCode;
    }

    /**
     * get response http status code
     *
     * @param integer $httpStatusCode
     *
     */
    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }

    public function fetch(): string
    {
        $this->beforeDisplay();
        $smarty = $this->getSmartyTemplater();

        foreach ($this->smartyParams as $key => $value) {
            $smarty->assign($key, $value);
        }
        $smarty->assignJsonParams($this->params);
        return $smarty->fetch($this->getTemplate());
    }

    /**
     * display response
     * @param bool $fromExaption
     * @access public
     *
     */
    public function display(bool $fromExaption = false)
    {
        $this->setNgsFromException($fromExaption);
        $this->beforeDisplay();
        http_response_code($this->getHttpStatusCode());
        if (!$this->getTemplate()) {
            $this->displayJson($this->params);
            return;
        }
        $smarty = $this->getSmartyTemplater();
        foreach ($this->smartyParams as $key => $value) {
            $smarty->assign($key, $value);
        }
        $smarty->assignJsonParams($this->params);
        if ($this->getType() === 'json') {
            $this->displayJson();
            return;
        }
        if (!NGS()->isJsFrameworkEnable()) {
            $this->displaySmarty($this->getTemplate());
            return;
        }
        $ext = pathinfo($this->getTemplate(), PATHINFO_EXTENSION);
        if ($ext !== 'json' && (NGS()->isJsFrameworkEnable() && !NGS()->getHttpUtils()->isAjaxRequest())) {
            $smarty->setCustomHeader($this->getCustomHeader());
            $this->displaySmarty($this->getTemplate());
            return;
        }
        if (NGS()->isJsFrameworkEnable() && NGS()->getHttpUtils()->isAjaxRequest()) {
            $params = [];
            $params['html'] = $smarty->fetch($this->getTemplate());
            $params['nl'] = NGS()->getLoadMapper()->getNestedLoads();
            $params['pl'] = $this->getPermalink();
            $params['params'] = $this->params;
            $params['components'] = $this->components;
            $this->displayJson($params);
            return;
        }
        $this->displayJson();
    }

    private function displayJson(?array $params = null): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $smarty = $this->getSmartyTemplater();
        if ($params !== null) {
            echo json_encode($params, JSON_THROW_ON_ERROR, 512);
            return;
        }
        foreach ($this->params as $key => $value) {
            $smarty->assign($key, $value);
        }
        if ($this->getTemplate()) {
            echo($smarty->fetch($this->getTemplate()));
        }
        return;
    }

    private function displaySmarty(): void
    {
        echo $this->fetchSmartyTemplate($this->getTemplate());
    }


    public function fetchSmartyTemplate(string $templatePath): string
    {
        return $this->getSmartyTemplater()->fetch($templatePath);
    }


    protected function beforeDisplay()
    {
        return;
    }


    protected function getCustomJsParams()
    {
        return array();
    }

    /**
     * @return string
     */
    protected function getCustomHeader()
    {
        return '';
    }

}