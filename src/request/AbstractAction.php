<?php
/**
 * parent class of all ngs actions
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @year 2009-2019
 * @package ngs.framework
 * @version 3.8.0
 *
 * This file is part of the NGS package.
 *
 * @copyright Naghashyan Solutions LLC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace ngs\request;

abstract class AbstractAction extends \ngs\request\AbstractRequest
{

    /**
     * @return void
     */
    public function initialize(): void
    {

    }

    public function afterRequest(): void
    {
        $this->afterAction();
    }

    /**
     * this function invoked when user hasn't permistion
     *
     * @abstract
     * @access
     * @return void
     */
    public function onNoAccess(): void
    {
    }

    public function afterAction(): void
    {
        return;
    }


}
