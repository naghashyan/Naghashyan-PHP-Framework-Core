<?php
namespace sd\loads\profile;

use ngs\request\AbstractJsonLoad;
use sd\managers\ProfileManager;

class ProfileListLoad extends AbstractJsonLoad
{
    public function load(): void
    {
        $userId = (int)($_GET['user_id'] ?? 0);
        if (!$userId) {
            $this->addParam('profiles', []);
            return;
        }
        $manager = new ProfileManager();
        $profiles = $manager->getProfiles($userId);
        $result = [];
        foreach ($profiles as $profile) {
            $result[] = $profile->toArray(true);
        }
        $this->addParam('profiles', $result);
    }
}
