<?php
namespace sd\loads\auth;

use ngs\request\AbstractJsonLoad;

class LoginLoad extends AbstractJsonLoad
{
    public function load(): void
    {
        $this->addParam('message', 'Please log in');
    }
}
