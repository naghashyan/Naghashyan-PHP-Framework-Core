<?php
namespace sd\dal\dto;

use ngs\dal\dto\AbstractDto;

class SocialProfileDto extends AbstractDto
{
    protected ?int $id = null;
    protected ?int $user_id = null;
    protected ?string $platform = null;
    protected ?string $access_token = null;

    public function getMapArray(): ?array
    {
        return [
            'id' => 'id',
            'user_id' => 'user_id',
            'platform' => 'platform',
            'access_token' => 'access_token'
        ];
    }

    public function getId(): ?int { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }

    public function getUserId(): ?int { return $this->user_id; }
    public function setUserId(int $userId): void { $this->user_id = $userId; }

    public function getPlatform(): ?string { return $this->platform; }
    public function setPlatform(string $platform): void { $this->platform = $platform; }

    public function getAccessToken(): ?string { return $this->access_token; }
    public function setAccessToken(string $token): void { $this->access_token = $token; }
}
