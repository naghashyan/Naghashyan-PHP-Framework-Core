<?php
namespace sd\actions\auth;

use ngs\request\AbstractAction;
use sd\managers\UserManager;

class LoginAction extends AbstractAction
{
    public function service(): void
    {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        $userManager = new UserManager();

        if ($userManager->verifyCredentials($username, $password)) {
            $this->addParam('status', 'success');
        } else {
            $this->addParam('status', 'failure');
        }
    }
}
