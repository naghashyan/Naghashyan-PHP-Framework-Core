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
    private array $customGroupsToSend = [];
    private string $customLoad = "";
    private string $emailSubject = "";

    public function __construct(array $params, array $attachemts = [], string $emailSubject = "")
    {
        $this->params = $params;
        $this->attachemts = $attachemts;
        $this->emailSubject = $emailSubject;
        $this->customGroupsToSend = [];
        $this->customLoad = "";
    }


    public static abstract function getEmptyInstance(): AbstractEventStructure;

    /**
     * can be added notification from UI
     *
     * @return bool
     */
    public function isVisible(): bool
    {
        return false;
    }


    public function getParams()
    {
        return $this->params;
    }

    /**
     * indicates if bulk supported for this event
     *
     * @return bool
     */
    public function bulkIsAvailable() :bool
    {
        return true;
    }

    /**
     * returns display name of the event
     * @return string
     */
    public function getEventName(): string
    {
        return get_class($this);
    }

    /**
     * returns display name of the event
     * @return string
     */
    public function getEventId(): string
    {
        $name = $this->getEventName();
        return md5($name);
    }

    /**
     * returns display name of the event
     * @return string
     */
    public function getEventClass(): string
    {
        $class = get_class($this);
        $classParts = explode("\\", $class);

        return $classParts[count($classParts) - 1];
    }

    /**
     * return title which will be seen in notification as title
     *
     * @return string
     */
    public function getEventTitle(): string
    {
        return "";
    }

    /**
     * returns list of varialbes which can be used in notification template
     *
     * @return array
     */
    public function getAvailableVariables(): array
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


    /**
     * @return string
     */
    public function getEmailSubject(): string
    {
        return $this->emailSubject;
    }

    /**
     * @param string $emailSubject
     */
    public function setEmailSubject(string $emailSubject): void
    {
        $this->emailSubject = $emailSubject;
    }

    public function getCustomGroupsToSend(): array
    {
        return $this->customGroupsToSend;
    }

    public function setCustomGroupsToSend(array $customGroupsToSend): void
    {
        $this->customGroupsToSend = $customGroupsToSend;
    }

    /**
     * @return string
     */
    public function getCustomLoad(): string
    {
        return $this->customLoad;
    }

    /**
     * @param string $customLoad
     */
    public function setCustomLoad(string $customLoad): void
    {
        $this->customLoad = $customLoad;
    }

}
