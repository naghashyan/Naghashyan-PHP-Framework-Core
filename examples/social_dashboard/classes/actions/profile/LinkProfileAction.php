<?php
namespace sd\actions\profile;

use ngs\request\AbstractAction;
use sd\managers\ProfileManager;

class LinkProfileAction extends AbstractAction
{
    public function service(): void
    {
        $userId = (int)($_POST['user_id'] ?? 0);
        $platform = $_POST['platform'] ?? '';
        $token = $_POST['token'] ?? '';

        if (!$userId || !$platform || !$token) {
            $this->addParam('status', 'failure');
            return;
        }

        $manager = new ProfileManager();
        $manager->addProfile($userId, $platform, $token);
        $this->addParam('status', 'linked');
    }
}
