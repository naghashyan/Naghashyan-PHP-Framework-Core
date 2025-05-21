<?php
namespace sd\managers;

use ngs\AbstractManager;
use sd\dal\mappers\SocialProfileMapper;
use sd\dal\dto\SocialProfileDto;

class ProfileManager extends AbstractManager
{
    private SocialProfileMapper $mapper;

    public function __construct()
    {
        $this->mapper = new SocialProfileMapper();
    }

    public function addProfile(int $userId, string $platform, string $token): void
    {
        $dto = new SocialProfileDto();
        $dto->setUserId($userId);
        $dto->setPlatform($platform);
        $dto->setAccessToken($token);
        $this->mapper->insertDto($dto);
    }

    public function getProfiles(int $userId): array
    {
        return $this->mapper->getProfilesByUserId($userId);
    }
}
