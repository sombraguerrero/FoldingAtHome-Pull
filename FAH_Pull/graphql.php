<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_GraphQl
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
/**
 * require autoloadeder
 */
require_once 'autoload.php';
require_once 'conn.php';
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
header('Content-Type: application/json');

function getStats($qty)
{
	$boincConn = new PDO(SERVER, USER, PWD);
	$boincQuery = $boincConn->prepare("SELECT HEX(`ID`) as 'ID', `day`, `user_total_credit`, `user_expavg_credit`, `host_total_credit`, `host_expavg_credit`, `projectSource` FROM boinc_data order by ID desc limit " . $qty);
	$boincQuery->execute();
	$stats = $boincQuery->fetchAll(PDO::FETCH_ASSOC);
	return $stats;
}

$boincType = new ObjectType([
        'name' => 'boincType',
        'description' => 'boinc stats from json object',
        'fields' => [
			'ID' => Type::id(),
            'day' => Type::string(),
            'user_total_credit' => Type::float(),
            'user_expavg_credit' => Type::float(),
            'host_total_credit' => Type::float(),
			'host_expavg_credit' => Type::float(),
			'projectSource'=> Type::string() 
			]
        ]);
	
    $queryType = new ObjectType([
        'name' => 'Query',
        'fields' => [
            'stat' => [
                'type' => Type::listOf($boincType),
                'resolve' => function ($root, $args) { return $root; }
            ]
        ]
    ]);
	
	$schema = new Schema([
        "query" => $queryType
    ]);

	#print_r(get_required_files());
	try {
			$boincQty = $_GET['numRows'];
			$query = $_GET['query'];
			$variableValues = [];
			$rootValue = getStats($boincQty);
			$result = GraphQL::executeQuery($schema, $query, $rootValue, null, $variableValues);
			echo json_encode($result);
		}
	 
	catch (\Exception $e) {
		$output = [
			'error' => [
				'message' => $e->getMessage()
			]
		];
	}
?>