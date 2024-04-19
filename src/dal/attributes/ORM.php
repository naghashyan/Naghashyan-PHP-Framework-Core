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
class ORM
{
    public string $propertyName;
    public string $columnName;
    public null|string $type = null;
    public null|string $format = null;
    public ?string $analyzer = null;
    public ?string $searchAnalyzer = null;
    /**
     * @var ORM[]
     */
    public array $fields = [];
    /**
     * @var ORM[]
     */
    public array $properties = [];

    public function __construct(
        string $columnName,
        string $type = null,
        ?string $format = null,
        ?string $analyzer = null,
        ?string $searchAnalyzer = null
    ) {
        $this->columnName = $columnName;
        $this->type = $type;
        $this->format = $format;
        $this->analyzer = $analyzer;
        $this->searchAnalyzer = $searchAnalyzer;
    }

    /**
     * @return string
     */
    public function getPropertyName(): string
    {
        return $this->propertyName;
    }

    /**
     * @param string $propertyName
     */
    public function setPropertyName(string $propertyName): void
    {
        $this->propertyName = $propertyName;
    }

    /**
     * @return string
     */
    public function getColumnName(): string
    {
        return $this->columnName;
    }

    /**
     * @param string $columnName
     */
    public function setColumnName(string $columnName): void
    {
        $this->columnName = $columnName;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string|null
     */
    public function getFormat(): ?string
    {
        return $this->format;
    }

    /**
     * @param string|null $format
     */
    public function setFormat(?string $format): void
    {
        $this->format = $format;
    }

    /**
     * @return string|null
     */
    public function getAnalyzer(): ?string
    {
        return $this->analyzer;
    }

    /**
     * @param string|null $analyzer
     */
    public function setAnalyzer(?string $analyzer): void
    {
        $this->analyzer = $analyzer;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function setFields(array $fields): void
    {
        $this->fields = $fields;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function setProperties(array $properties): void
    {
        $this->properties = $properties;
    }

    public function getSearchAnalyzer(): ?string
    {
        return $this->searchAnalyzer;
    }

    public function setSearchAnalyzer(?string $searchAnalyzer): void
    {
        $this->searchAnalyzer = $searchAnalyzer;
    }

}