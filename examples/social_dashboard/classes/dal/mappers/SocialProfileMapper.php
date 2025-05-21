<?php
namespace sd\dal\mappers;

use ngs\dal\mappers\AbstractMysqlMapper;
use sd\dal\dto\SocialProfileDto;

class SocialProfileMapper extends AbstractMysqlMapper
{
    public function getTableName(): string
    {
        return 'social_profiles';
    }

    public function getPKFieldName(): string
    {
        return 'id';
    }

    public function createDto(): SocialProfileDto
    {
        return new SocialProfileDto();
    }

    public function getProfilesByUserId(int $userId): array
    {
        $sql = 'SELECT * FROM `social_profiles` WHERE `user_id`=:uid';
        $res = $this->dbms->prepare($sql);
        $res->execute([':uid' => $userId]);
        $rows = $res->fetchAll(\PDO::FETCH_ASSOC);
        $result = [];
        foreach ($rows as $row) {
            $dto = $this->createDto();
            $dto->fillDtoFromArray($row);
            $result[] = $dto;
        }
        return $result;
    }
}
