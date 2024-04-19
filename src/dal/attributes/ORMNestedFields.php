<?php
/**
 * AbstractMapper class is a base class for all mapper lasses.
 * It contains the basic functionality and also DBMS pointer.
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site https://naghashyan.com
 * @package ngs.framework.dal.mappers
 * @version 4.0.0
 * @year 2009-2020
 *
 * This file is part of the NGS package.
 *
 * @copyright Naghashyan Solutions LLC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace ngs\dal\attributes;

#[\Attribute]
class ORMNestedFields
{
    /**
     * @var ORM[]
     */
    public array $fields;
    public string $propertyName;
    public string $type = 'nested';

    /**
     * @param ORM[] $fields
     */
    public function __construct(string $propertyName, array $fields)
    {
        $this->fields = $fields;
        $this->propertyName = $propertyName;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function getPropertyName(): string
    {
        return $this->propertyName;
    }

    public function getType(): string
    {
        return $this->type;
    }
}