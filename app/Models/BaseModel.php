<?php

namespace App\Models;

use CodeIgniter\Model;
use Config\Database;

class BaseModel extends Model
{
    public $db;

    public function __construct()
    {
        // Connect to the database and assign it to the class property
        $this->db = Database::connect();
    }


    function getPaginationResult($query, $paginationData)
	{
		return $this->getPaginationResult_proccess($query, $paginationData->limit, $paginationData->offset, $paginationData->keyword, $paginationData->ordersBy, $paginationData->ordersType, $paginationData->keywordColumnList, $paginationData->ordersColumnList, $paginationData->mappingColumnNameFEandBE);
	}

    function getPaginationResult_proccess($query, $limit = 10, $offset = 0, $keyword = null, $ordersBy = [], $ordersType = [], $keywordColumnList = [], $ordersColumnList = [], $mappingColumnNameFEandBE = [])
	{
		$query = $this->keyword($query, $keyword, $keywordColumnList);

		$countQuery = clone $query;
		$total_count = $countQuery->countAllResults();


		$query = $this->pagination($query, $limit, $offset,  $this->mapValuesInverse($ordersBy, $mappingColumnNameFEandBE), $ordersType);
        
        $data = $query->get()->getResult();
		return (object) array(
			'total_count' => $total_count,
			'data' => $data,
			'ordersColumnList' => count($mappingColumnNameFEandBE) > 0 ? $this->mapValues($ordersColumnList, $mappingColumnNameFEandBE) : [],
			'searchColumnList' => count($mappingColumnNameFEandBE) > 0 ? $this->mapValues($keywordColumnList, $mappingColumnNameFEandBE) : []
		);
	}

    function keyword($query, $keyword, $keywordColumnList)
    {
        if ($keyword) {
            // Start a group for the query
            $query->groupStart();

            // Check if keyword contains a comma (indicating multiple keywords)
            if (strpos($keyword, ',') !== false) {
                // Convert to an array and trim spaces around each word
                $keywords = array_map('trim', explode(',', $keyword));

                // Loop through each word
                foreach ($keywords as $word) {
                    // Start a group for each keyword
                    $query->orGroupStart();
                    foreach ($keywordColumnList as $column) {
                        // Apply the 'like' condition for each column
                        $query->orLike('LOWER(' . $column . ')', strtolower($word));
                    }
                    // End the group for this keyword
                    $query->groupEnd();
                }
            } else {
                // Normal LIKE search if no comma is found
                foreach ($keywordColumnList as $column) {
                    $query->orLike('LOWER(' . $column . ')', strtolower($keyword));
                }
            }

            // End the main group
            $query->groupEnd();
        }

        return $query;
    }


    function pagination($query, $limit = 0, $offset = 0, $ordersBy = [], $ordersType = [])
	{
		if ($limit > 0) {
			$query->limit($limit, $offset);
		}

		if ($ordersBy == null) {
			return $query;
		}

		if (count($ordersBy) > 0 && count($ordersType) > 0 && count($ordersBy) != count($ordersType)) {
			return $query;
		}

		if (count($ordersBy) > 0 && count($ordersType) > 0 && count($ordersBy) == count($ordersType)) {
			$hasNull = $this->hasNullValue($ordersBy);

			if ($hasNull) {
				return $query;
			}
			for ($i = 0; $i < count($ordersBy); $i++) {
				$query->order_by($ordersBy[$i], $ordersType[$i]);
			}
		}

        return $query;
	}
    
	function hasNullValue($list)
	{
		return in_array(null, $list, true);
	}

    function mapValuesInverse($list, $mapping)
	{
		$inverseMapping = array_flip($mapping);
		return array_map(function ($value) use ($inverseMapping) {
			return isset($inverseMapping[$value]) ? $inverseMapping[$value] : null;
		}, $list);
	}

    function mapValues($list, $mapping)
	{
		return array_map(function ($key) use ($mapping) {
			return isset($mapping[$key]) ? $mapping[$key] : null;
		}, $list);
	}
}
