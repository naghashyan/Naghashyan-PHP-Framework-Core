<?php
/**
 * EventDispatchedStructure class, can call when event dispatched
 *
 * @author Mikael Mkrtchyan
 * @site https://naghashyan.com
 * @mail miakel.mkrtchyan@naghashyan.com
 * @year 2022
 * @package ngs.event.structure
 * @version 2.0.0
 *
 */

namespace ngs\event\structure;

use ngs\event\structure\AbstractEventStructure;

class EventDispatchedStructure extends AbstractEventStructure
{
    private ?AbstractEventStructure $event;

    public function __construct(array $params, ?AbstractEventStructure $event)
    {
        parent::__construct($params);
        $this->event = $event;
    }

    public static function getEmptyInstance() :AbstractEventStructure {
        return new EventDispatchedStructure([], null);
    }

    /**
     * @return AbstractEventStructure
     */
    public function getEvent(): AbstractEventStructure
    {
        return $this->event;
    }

    /**
     * @param AbstractEventStructure $event
     */
    public function setEvent(AbstractEventStructure $event): void
    {
        $this->event = $event;
    }
}