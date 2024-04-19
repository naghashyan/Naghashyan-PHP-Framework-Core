<?php
/**
 * AbstractElasticsearchMapper class is a base class for all mapper lasses.
 * It contains the basic functionality and also DBMS pointer.
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site https://naghashyan.com
 * @package framework.dal.mappers
 * @version 4.5.0
 * @year 2020-2023
 * @copyright Naghashyan Solutions LLC
 */

namespace ngs\dal\mappers;


use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Common\Exceptions\Missing404Exception;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use ngs\dal\attributes\ORM;
use ngs\dal\attributes\ORMFields;
use ngs\dal\attributes\ORMNestedFields;
use ngs\dal\connectors\ElasticsearchDBMS;
use ngs\dal\dto\AbstractDto;
use ngs\exceptions\DebugException;
use ngs\exceptions\NgsErrorException;

abstract class AbstractElasticsearchMapper extends AbstractMapper
{
    public ElasticsearchDBMS $dbms;
    public Client $client;

    /**
     * Initializes DBMS pointer.
     */
    function __construct()
    {
        $this->dbms = ElasticsearchDBMS::getInstance($this->getHostConfig());
        $this->client = $this->dbms->getClient();
    }

    public function getHostConfig()
    {
        return [
            NGS()->getConfig()->DB->elasticsearch->host . ':' . NGS()->getConfig()->DB->elasticsearch->port
        ];
    }

    public function getContentType(): string|null
    {
        return null;
    }

    /**
     * @return bool
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function addMappingTypes(): bool
    {
        try {
            if ($this->client->indices()->exists(['index' => $this->getTableName()])) {
                $response = $this->client->indices()->delete([
                    'index' => $this->getTableName(),
                ]);
            }
        } catch (ClientResponseException $e) {
        }


        $params = $this->getMappingTypes();


        //var_dump($params);exit;
        try {
            $response = $this->client->indices()->create($params);
            return true;
        } catch (ClientResponseException|MissingParameterException|ServerResponseException $e) {
            var_dump($e->getMessage());
            exit;
            return false;
        }
    }

    /**
     * @return bool
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function updateMappingType(): bool
    {
        if (!$this->client->indices()->exists(['index' => $this->getTableName()])) {
            return $this->addMappingTypes();
        }
        $params = $this->getMappingTypes(true);
        $currentMapping = $this->getIndexMapping();
        if (isset($params['body']) && isset($params['body']['settings'])) {
            unset($params['body']['settings']);
        }
        if (
            isset($currentMapping[$this->getTableName()]) &&
            isset($currentMapping[$this->getTableName()]['mappings']) &&
            isset($currentMapping[$this->getTableName()]['mappings']['properties']) &&
            isset($params['body']) &&
            isset($params['body']['properties'])
        ) {
            $properties = $currentMapping[$this->getTableName()]['mappings']['properties'];
            foreach ($params['body']['properties'] as $index => $param) {
                if (isset($properties[$index])) {
                    unset($params['body']['properties'][$index]);
                }
            }
        }

        try {
            $response = $this->client->indices()->putMapping($params);
            return true;
        } catch (ClientResponseException|MissingParameterException|ServerResponseException $e) {
            var_dump($e->getMessage());
            exit;
            return false;
        }
    }


    /**
     * @return bool
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function deleteMappingType(): bool
    {
        try {
            if ($this->client->indices()->exists(['index' => '$this->getTableName()'])) {
                $response = $this->client->indices()->delete([
                    'index' => $this->getTableName(),
                ]);
                return true;
            }
            return false;
        } catch (ClientResponseException $e) {
            return false;
        }
    }

    /**
     * @param string $filter
     * @param array $synonyms array of strings
     * @return void
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function updateSynonyms(string $filter, array $synonyms): void
    {
        try {
            $closeParams = ['index' => $this->getTableName()];
            $response = $this->client->indices()->close($closeParams);
            $params = [
                'index' => $this->getTableName(),
                'body' => [
                    'settings' => [
                        'analysis' => [
                            'filter' => [
                                $filter => [
                                    'type' => 'synonym',
                                    'synonyms' => $synonyms
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            $response = $this->client->indices()->putSettings($params);
            $closeParams = ['index' => $this->getTableName()];
            $response = $this->client->indices()->open($closeParams);
        } catch (ClientResponseException $e) {
            $closeParams = ['index' => $this->getTableName()];
            $response = $this->client->indices()->open($closeParams);
        }
    }

    /**
     * @param bool $forUpdate
     * @return array
     */
    private function getMappingTypes(bool $forUpdate = false): array
    {
        $mappingArray = $this->getOrmMapping($this->createDto());
        $params = ['index' => $this->getTableName(), 'body' => []];
        $properties = [];
        foreach ($mappingArray as $mapping) {
            if (!$mapping->getType()) {
                continue;
            }
            $typingMapping = [
                'type' => $mapping->getType()
            ];
            if ($mapping->getFormat()) {
                $typingMapping['format'] = $mapping->getFormat();
            }
            if ($mapping->getAnalyzer()) {
                $typingMapping['analyzer'] = $mapping->getAnalyzer();
            }
            if ($mapping->getSearchAnalyzer()) {
                $typingMapping['search_analyzer'] = $mapping->getSearchAnalyzer();
            }
            if ($mapping->getFields() || $mapping->getProperties()) {
                $type = 'fields';
                if ($mapping->getProperties()) {
                    $type = 'properties';
                    $mapping->setFields($mapping->getProperties());
                }
                $typingMapping[$type] = [];
                foreach ($mapping->getFields() as $field) {
                    $mappingField = [
                        'type' => $field->getType()
                    ];
                    if ($field->getAnalyzer()) {
                        $mappingField['analyzer'] = $field->getAnalyzer();
                    }
                    if ($mapping->getFormat()) {
                        $mappingField['format'] = $field->getFormat();
                    }
                    $typingMapping[$type][$field->getColumnName()] = $mappingField;
                }
            }
            $properties[$mapping->getColumnName()] = $typingMapping;
        }
        $params['body']['settings'] = $this->getMappingSettings();
        if ($forUpdate) {
            $params['body']['properties'] = $properties;
        } else {
            $params['body']['mappings']['properties'] = $properties;
        }
        return $params;
    }

    protected function getMappingSettings(): array
    {
        return [];
    }

    /**
     * @param AbstractDto $dto
     * @return ORM[]
     */
    private function getOrmMapping(AbstractDto $dto): array
    {
        $reflectionClass = new \ReflectionClass($dto::class);

        $properties = $reflectionClass->getProperties();
        $mappingArray = [];
        foreach ($properties as $property) {
            $fields = $property->getAttributes(ORMFields::class)[0] ?? null;
            $nested = $property->getAttributes(ORMNestedFields::class)[0] ?? null;
            $attribute = $property->getAttributes(ORM::class)[0] ?? null;
            if ($nested) {
                $nestedInstance = $nested->newInstance();
                $ormInstance = new ORM($nestedInstance->getPropertyName(), $nestedInstance->getType());
                $ormInstance->setPropertyName($nestedInstance->getPropertyName());
                $ormInstance->setProperties($nestedInstance->getFields());
                $mappingArray[] = $ormInstance;
                continue;
            }
            if (!$attribute) {
                continue;
            }

            $ormInstance = $attribute->newInstance();
            $ormInstance->setPropertyName($property->getName());
            if ($fields) {
                $ormInstance->setFields($fields->newInstance()->getFields());
            }
            $mappingArray[] = $ormInstance;
        }
        return $mappingArray;
    }


    /**
     * generate SET query part
     *
     * @param AbstractDto $dto
     * @return array|null
     * @throws DebugException
     */
    private function getMappingFromDto(AbstractDto $dto): ?array
    {
        $ormMapping = $this->getOrmMapping($dto);
        $mappingArray = [];

        foreach ($ormMapping as $orm) {
            if (!$dto->isset($orm->getPropertyName())) {
                continue;
            }

            $getterFunction = 'get' . ucfirst($orm->getPropertyName());
            $mappingArray[$orm->getColumnName()] = $dto->$getterFunction();
        }
        return $mappingArray;
    }

    public function getIndexSettings(): null|array
    {
        try {
            $params = [
                'index' => $this->getTableName(),
            ];
            $response = $this->client->indices()->getSettings($params);
            $data = (string)$response->getBody();
            return json_decode($data, true);
        } catch (Missing404Exception $e) {
            return null;
        } catch (ClientResponseException $e) {
            return null;
        } catch (\JsonException $e) {
            return null;
        }
    }

    public function getIndexMapping(): null|array
    {
        try {
            $params = [
                'index' => $this->getTableName(),
            ];
            $response = $this->client->indices()->getMapping($params);
            $data = (string)$response->getBody();
            return json_decode($data, true);
        } catch (Missing404Exception $e) {
            return null;
        } catch (ClientResponseException $e) {
            return null;
        } catch (\JsonException $e) {
            return null;
        }
    }

    /**
     * Inserts dto into table.
     *
     * @param AbstractDto
     *
     * @return boolean
     */
    public function insertDto(AbstractDto $dto): bool
    {
        $mappingArray = $this->getMappingFromDto($dto);
        if (!$mappingArray) {
            return false;
        }
        $params = ['index' => $this->getTableName(), 'body' => $mappingArray];
        $response = $this->client->index($params);
        if ($response && $response['result'] === 'created') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Inserts dtos into table.
     *
     * @param AbstractDto[]
     *
     * @return bool
     */
    public function insertDtos(array $dtos): bool
    {
        //validating input params
        if ($dtos === null) {
            throw new DebugException('The input param can not be NULL.');
        }
        $addDocsArr = [];
        $commitStatus = true;
        foreach ($dtos as $key => $dto) {
            $pkFieldGetter = 'get' . ucfirst($this->getPKFieldName());
            $addDocsArr[] = [
                'index' =>
                    [
                        '_index' => $this->getTableName()
                    ]
            ];
            $addDocsArr[] = $this->getMappingFromDto($dto);
            if ($key % NGS()->get('ELASTIC_BULK_LIMIT') === 0) {
                $start_time = microtime(true);
                $response = $this->client->bulk(['body' => $addDocsArr]);
                $end_time = microtime(true);

// Calculate the difference and print it
                $execution_time = $end_time - $start_time;
                var_Dump($execution_time);
                if ($response['errors']) {
                    throw new NgsErrorException('Error while inserting data into elastic');
                }
                $addDocsArr = [];
            }
        }
        if (count($addDocsArr) > 0) {
            $response = $this->client->bulk(['body' => $addDocsArr]);
            if ($response['errors']) {
                var_dump($response);
                exit;
                throw new NgsErrorException('Error while inserting data into elastic');
            }
        }
        return true;
    }

    /**
     * Updates table fields by primary key.
     * DTO must contain primary key value.
     *
     * @param int $id
     * @return boolean
     * @throws DebugException
     */
    public function updateByPK($dto)
    {
        //validating input params
        if ($dto === null) {
            throw new DebugException('The input param can not be NULL.');
        }
        $getPKFunc = $this->getCorrespondingFunctionName($dto->getMapArray(), $this->getPKFieldName(), 'get');
        $pk = $dto->$getPKFunc();
        if (!isset($pk)) {
            throw new DebugException('The primary key is not set.');
        }

        $dto_fields = array_values($dto->getMapArray());
        $db_fields = array_keys($dto->getMapArray());

        $query = $this->getUpdateQuery();
        $doc = $query->createDocument();

        for ($i = 0, $iMax = count($dto_fields); $i < $iMax; $i++) {
            if ($dto_fields[$i] === $this->getPKFieldName()) {
                continue;
            }
            $functionName = 'get' . ucfirst($dto_fields[$i]);
            $val = $dto->$functionName();
            if (isset($val)) {
                $doc->setFieldModifier($db_fields[$i], 'set');
                $doc->setField($db_fields[$i], $val);
            }
        }
        $doc->setKey($this->getPKFieldName(), $pk);

        //add document and commit
        $query->addDocument($doc)->addCommit(true, true, false);

        // this executes the query and returns the result
        $result = $this->dbms->update($query);
        if ($result->getStatus() === 0) {
            return true;
        }
        return false;
    }

    /**
     * Selects from table by primary key and returns corresponding DTO
     *
     * @param object $id
     * @return
     */
    public function selectByPK($id)
    {
    }

    /**
     * Delete the row by primary key
     *
     * @param int $id - the unique identifier of table
     * @return bool
     */
    public function deleteByPK($id): ?bool
    {
        if (is_numeric($id)) {
            return $this->deleteByPKeys(array($id));
        }
        return null;
    }

    /**
     * Delete the rows by primary keys
     *
     * @param array $id - the unique identifier of table
     * @return bool
     */
    public function deleteByPKeys($ids): bool
    {
        if ($ids == null || !is_array($ids)) {
            throw new DebugException('The input param can not be NULL.');
        }
        $query = $this->getUpdateQuery();
        $query->addDeleteByIds($ids);
        $query->addCommit(true, true, false);

        return $this->dbms->update($query)->getStatus() === 0;
    }

    /**
     * Executes the query and returns an array of corresponding DTOs
     *
     * @param array $query
     * @return
     */
    public function fetchRows(array $query, null|float $minScore = null, bool $shouldPrint = false): array
    {
        $params = [
            'index' => $this->getTableName(),
            'body' => [
                'query' => $query
            ]
        ];
        if ($minScore !== null) {
            $params['body']['min_score'] = $minScore;
        }
        if($shouldPrint){
            var_dump($params);exit;
        }
        $response = $this->client->search($params);
        if (!isset($response['hits']) && !isset($response['hits']['hits'])) {
            return [];
        }
        return $this->createDtoFromResultArray($response['hits']['hits']);
    }

    /**
     * @param string $id
     * @param array $query
     * @return array
     * @throws ClientResponseException
     * @throws MissingParameterException
     * @throws ServerResponseException
     */
    public function explainQuery(string $id, array $query): array
    {
        $params = [
            'index' => $this->getTableName(),
            'id' => 'bnk4HY0BnR6nvfWBxNk3',
            'body' => [
                'query' => $query
            ]
        ];
        $explanation = $this->client->explain($params);
        $responseBody = $explanation->asArray();
        if (isset($responseBody['explanation'])) {
            return $responseBody['explanation'];
        }
        return [];
    }

    /**
     * create dtos array from mysql fethed reuslt array
     *
     * @param array $results
     * @return array array
     */
    protected function createDtoFromResultArray($results): array
    {
        $resultArr = array();
        foreach ($results as $result) {
            $dto = $this->createDto();
            $dto->fillDtoFromArray($result['_source']);
            $resultArr[] = $dto;
        }
        return $resultArr;
    }

}