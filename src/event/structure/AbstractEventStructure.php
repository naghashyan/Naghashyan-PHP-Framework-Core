<?php
/**
 * AbstractEventStructure manager class
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

abstract class AbstractEventStructure
{
    private array $params = [];
    private array $attachemts = [];

    public function __construct(array $params, array $attachemts = [])
    {
        $this->params = $params;
        $this->attachemts = $attachemts;
    }


    public static abstract function getEmptyInstance() :AbstractEventStructure;

    /**
     * can be added notification from UI
     *
     * @return bool
     */
    public function isVisible() :bool {
        return false;
    }


    public function getParams() {
        return $this->params;
    }

    /**
     * returns display name of the event
     * @return string
     */
    public function getEventName() :string {
        return get_class($this);
    }

    /**
     * returns display name of the event
     * @return string
     */
    public function getEventId() :string {
        $name = $this->getEventName();
        return md5($name);
    }

    /**
     * returns display name of the event
     * @return string
     */
    public function getEventClass() :string {
        $class = get_class($this);
        $classParts = explode("\\", $class);

        return $classParts[count($classParts) - 1];
    }

    /**
     * return title which will be seen in notification as title
     *
     * @return string
     */
    public function getEventTitle() :string {
        return "";
    }

    /**
     * returns list of varialbes which can be used in notification template
     *
     * @return array
     */
    public function getAvailableVariables() :array
    {
        return [];
    }

    /**
     * @return array
     */
    public function getAttachemts(): array
    {
        return $this->attachemts;
    }

    /**
     * @param array $attachemts
     */
    public function setAttachemts(array $attachemts): void
    {
        $this->attachemts = $attachemts;
    }
}