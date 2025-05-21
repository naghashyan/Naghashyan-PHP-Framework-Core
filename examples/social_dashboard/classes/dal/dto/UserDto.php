<?php
namespace sd\dal\dto;

use ngs\dal\dto\AbstractDto;

class UserDto extends AbstractDto
{
    protected ?int $id = null;
    protected ?string $username = null;
    protected ?string $password_hash = null;
    protected ?string $role = null;

    public function getMapArray(): ?array
    {
        return [
            'id' => 'id',
            'username' => 'username',
            'password_hash' => 'password_hash',
            'role' => 'role'
        ];
    }

    public function getId(): ?int { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }

    public function getUsername(): ?string { return $this->username; }
    public function setUsername(string $username): void { $this->username = $username; }

    public function getPasswordHash(): ?string { return $this->password_hash; }
    public function setPasswordHash(string $hash): void { $this->password_hash = $hash; }

    public function getRole(): ?string { return $this->role; }
    public function setRole(string $role): void { $this->role = $role; }
}
