<?php
/**
 * AbstractEventSubscriber class
 *
 * @author Mikael Mkrtchyan
 * @site https://naghashyan.com
 * @mail miakel.mkrtchyan@naghashyan.com
 * @year 2022
 * @package ngs.event.subscriber
 * @version 2.0.0
 *
 */

namespace ngs\event\subscriber;


abstract class AbstractEventSubscriber
{
    public function __construct()
    {
    }

    /**
     * should return arrak,
     * key => eventStructClass
     * value => public method of this class, which will be called when (key) event will be triggered
     *
     * @return array
     */
    abstract public function getSubscriptions() :array;
}