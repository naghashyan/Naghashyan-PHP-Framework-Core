<?php
namespace sd\dal\mappers;

use ngs\dal\mappers\AbstractMysqlMapper;
use sd\dal\dto\UserDto;

class UserMapper extends AbstractMysqlMapper
{
    public function getTableName(): string
    {
        return 'users';
    }

    public function getPKFieldName(): string
    {
        return 'id';
    }

    public function createDto(): UserDto
    {
        return new UserDto();
    }

    public function getUserByUsername(string $username): ?UserDto
    {
        $sql = 'SELECT * FROM `users` WHERE `username`=:username LIMIT 1';
        $res = $this->dbms->prepare($sql);
        $res->execute([':username' => $username]);
        $data = $res->fetch(\PDO::FETCH_ASSOC);
        if (!$data) {
            return null;
        }
        $dto = $this->createDto();
        $dto->fillDtoFromArray($data);
        return $dto;
    }
}
