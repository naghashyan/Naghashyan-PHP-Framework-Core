<?php

/**
 * EventManager manager class
 * used to handle events
 *
 * @author Mikael Mkrtchyan
 * @site http://naghashyan.com
 * @mail mikael.mkrtchyan@naghashyan.com
 * @year 2022
 * @package ngs.event
 * @version 1.0
 *
 */

namespace ngs\event;

use ngs\event\structure\AbstractEventStructure;
use ngs\event\structure\EventDispatchedStructure;
use ngs\event\subscriber\AbstractEventSubscriber;

class EventManager
{

    /**
     * @var EventManager instance of class
     */
    private static $instance = null;

    private array $eventSubscriptions = [];

    private function __construct() {
        
    }

    /**
     * Returns an singleton instance of this class
     *
     * @return EventManager
     */
    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new EventManager();
        }
        return self::$instance;
    }


    /**
     * dispatch event
     * will call all handlers subscribet to this event
     *
     * @param AbstractEventStructure $event
     */
    public function dispatch(AbstractEventStructure $event) {
        if(!$event instanceof EventDispatchedStructure) {
            $eventDispatched = new EventDispatchedStructure([], $event);
            $this->dispatch($eventDispatched);
        }
        
        $handlers = isset($this->eventSubscriptions[get_class($event)]) ? $this->eventSubscriptions[get_class($event)] : [];
        foreach($handlers as $handler) {
            $subscriber = $handler['subscriber'];
            $method = $handler['method'];
            $subscriber->$method($event);
        }
    }


    /**
     * add subscription to event
     *
     * @param $eventName
     * @param AbstractEventSubscriber $subscriber
     * @param string $method
     */
    public function subscribeToEvent($eventName, AbstractEventSubscriber $subscriber, string $method) {
        if(!isset($this->eventSubscriptions[$eventName])) {
            $this->eventSubscriptions[$eventName] = [];
        }

        $this->eventSubscriptions[$eventName][] = ['subscriber' => $subscriber, 'method' => $method];
    }
}