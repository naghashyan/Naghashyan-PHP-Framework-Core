<?php
/**
 * AbstractMapper class is a base class for all mapper lasses.
 * It contains the basic functionality and also DBMS pointer.
 *
 * @author Levon Naghashyan <levon@naghashyan.com>
 * @site http://naghashyan.com
 * @package ngs.framework.dal.mappers
 * @version 2.0.0
 * @year 2009-2015
 * 
 * This file is part of the NGS package.
 *
 * @copyright Naghashyan Solutions LLC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */
namespace ngs\dal\mappers {

	abstract class AbstractMapper {

		/**
		 * The child class must implemet this method to return table name.
		 *
		 * @return
		 */
		public abstract function getTableName();

		/**
		 * The child class must implemet this method to return primary key field name.
		 *
		 * @return
		 */
		public abstract function getPKFieldName();

		/**
		 * The child class must implement this method
		 * to return an instance of corresponding DTO class.
		 *
		 * @return
		 */
		public abstract function createDto();

		/**
		 *
		 * @param object $dto
		 * @param object $dbData
		 * @return
		 */
		protected function initializeDto($dto, $dbData) {
			if(method_exists($dto, "getExtendedMapArray")){
				$mapArray = $dto->getExtendedMapArray();
			}else{
				$mapArray = $dto->getMapArray();
			}
			
			if ($dbData == null) {
				return;
			}
			// Get keys for dbData.
			$dbDataKeys = array_keys($dbData);
			foreach ($dbDataKeys as $keyName) {
				// Create function based on key name.
				$functionName = $this->getCorrespondingFunctionName($mapArray, $keyName);
				if (strlen($functionName) != 0) {
					// Call function and initialize item based on data.
					$data = $dbData[$keyName];
					$dto->$functionName($dbData[$keyName]);
				}
			}
		}

		protected function getCorrespondingFunctionName($mapArray, $itemName, $prefix = "set") {
			// Get keys.
			$mapKeys = array_keys($mapArray);
			// Read map items and create correseponding functions.
			foreach ($mapKeys as $itemnameFromMap) {
				//echo("$itemnameFromMap -- $itemName </br>");
				if ($itemnameFromMap == $itemName) {
					// Get value for this item.
					$valueOfMap = $mapArray[$itemnameFromMap];
					// Make first letter uppercase, and add "set".
					$functionName = $prefix."".ucfirst($valueOfMap);
					return $functionName;
				}
			}
		}

		public function getFieldValue($dto, $fieldName) {
			$func = $this->getCorrespondingFunctionName($dto->getMapArray(), $fieldName, "get");
			return $dto->$func();
		}

		/**
		 * create dtos array from mysql fethed reuslt array
		 *
		 * @param array $results
		 * @return dtos array
		 */
		protected function createDtoFromResultArray($results) {
			$resultArr = array();
			foreach ($results as $result) {
				$dto = $this->createDto();
				$this->initializeDto($dto, $result);
				$resultArr[] = $dto;
			}
			return $resultArr;
		}

		/**
		 * encode dto to json
		 *
		 * @param object $dto
		 *
		 * @return json object
		 */
		public function dtoToJson($dto) {
			return json_encode($this->dtoToArray($dto));
		}

		/**
		 * encode dto to array
		 *
		 * @param object $dto
		 *
		 * @return json object
		 */
		public function dtoToArray($dto) {
			$dto_fields = array_values($dto->getMapArray());
			$db_fields = array_keys($dto->getMapArray());

			for ($i = 0; $i < count($dto_fields); $i++) {
				$functionName = "get".ucfirst($dto_fields[$i]);
				$val = $dto->$functionName();
				if ($val != null) {
					if (NGS()->isJson($val)) {
						$params[$db_fields[$i]] = json_decode($val, true);
						continue;
					}
					$params[$db_fields[$i]] = $val;
				}
			}
			return ($params);
		}

		/**
		 * encode dto to array
		 *
		 * @param object $dto
		 *
		 * @return json object
		 */
		public function jsonToDto($json, $dto = null) {
			if($dto == null){
				$dto = $this->createDto();
			}
			$db_fields = $dto->getMapArray();
			foreach ($json as $key => $value) {
				if(isset($db_fields[$key])){
					$functionName = "set".ucfirst($db_fields[$key]);
					$dto->$functionName($value);
				}
			}
			return $dto;
		}

		/**
		 * Inserts dto into table.
		 *
		 * @param object $dto
		 * @param object $esc [optional] - shows if the textual values must be escaped before setting to DB
		 * @return autogenerated id or -1 if something goes wrong
		 */
		public abstract function insertDto($dto);

		/**
		 * Updates table fields by primary key.
		 * DTO must contain primary key value.
		 *
		 * @param object $dto
		 * @param object $esc [optional] shows if the textual values must be escaped before setting to DB
		 * @return affected rows count or -1 if something goes wrong
		 */
		public abstract function updateByPK($dto);

		/**
		 * Selects from table by primary key and returns corresponding DTO
		 *
		 * @param object $id
		 * @return
		 */
		public abstract function selectByPK($id);

		/**
		 * Deletes the row by primary key
		 *
		 * @param object $id - the unique identifier of table
		 * @return affacted rows count or -1 if something goes wrong
		 */
		public abstract function deleteByPK($id);

	}

}
?>