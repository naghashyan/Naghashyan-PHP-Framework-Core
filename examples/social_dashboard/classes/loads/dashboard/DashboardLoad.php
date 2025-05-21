<?php
namespace sd\loads\dashboard;

use ngs\request\AbstractJsonLoad;

class DashboardLoad extends AbstractJsonLoad
{
    public function load(): void
    {
        $this->addParam('message', 'Welcome to the Social Dashboard');
    }
}
