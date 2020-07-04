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

class StatObj {
	public $last_team_wu;
	public $rank;
	public $team_credit;
	public $team_work_units;
	
	function __construct($last_team_wu, $rank, $team_credit, $team_work_units) {
		$this->last_team_wu = $last_team_wu;
		$this->rank = $rank;
		$this->team_credit = $team_credit;
		$this->team_work_units = $team_work_units;
	}
	
	static function getStats($qty) {
		$fahConn = new PDO(SERVER, USER, PWD);
		$fahQuery = $fahConn->prepare("SELECT * FROM guru3d_stats order by ID desc limit " . $qty);
		$fahQuery->execute();
		$stats = $fahQuery->fetchAll(PDO::FETCH_ASSOC);
		return $stats;
	}
	
	function insertStat($connection) {
		$fahQuery = $connection->prepare("INSERT INTO guru3d_stats (`last_team_wu`,`rank`,`team_credit`,`team_work_units`) values (?, ?, ?, ?)");
		$fahQuery->bindParam(1, $this->last_team_wu);
		$fahQuery->bindParam(2, $this->rank);
		$fahQuery->bindParam(3, $this->team_credit);
		$fahQuery->bindParam(4, $this->team_work_units);
		$fahQuery->execute();
	}
	
}

$fahType = new ObjectType([
        'name' => 'FAHType',
        'description' => 'FAH stats from json object',
        'fields' => [
            'ID' => Type::int(),
            'last_team_wu' => Type::string(),
            'rank' => Type::int(),
            'team_credit' => Type::string(),
			'team_work_units' => Type::int()
			]
        ]);
	
    $queryType = new ObjectType([
        'name' => 'Query',
        'fields' => [
            'stat' => [
                'type' => Type::listOf($fahType),
                'resolve' => function ($root, $args) { return $root; }
            ]
        ]
    ]);
	
	$mutationType = new ObjectType([
        'name' => 'MutateStat',
        'fields' => [
            'writeStat' => [
                'type' => $fahType,
				'args' => [
					'last_team_wu' => Type::string(),
					'rank' => Type::int(),
					'team_credit' => Type::int(),
					'team_work_units' => Type::int()
				],
                'resolve' => function ($root, $args) {
					$resolveConn = new PDO(SERVER, USER, PWD);
					$myStat = new StatObj($args['last_team_wu'], $args['rank'], $args['team_credit'], $args['team_work_units']);
					$myStat->insertStat($resolveConn);
					return $myStat;
                }
            ]
        ]
    ]);
	
	$schema = new Schema([
        "query" => $queryType,
		"mutation" => $mutationType
    ]);


try {
		if ($_SERVER['REQUEST_METHOD'] == "POST") {
			$rawInput = file_get_contents('php://input');
			$input = json_decode($rawInput, true);
			$variableValues = isset($input['variables']) ? $input['variables'] : null;
			$query = $input['query'];
			$result = GraphQL::executeQuery($schema, $query, null, null, $variableValues);
			$output = $result->toArray();
			echo json_encode($output);
		}
		else {
			$fahQty = $_GET['numRows'];
			$query = $_GET['query'];
			$variableValues = [];
			$rootValue = StatObj::getStats($fahQty);
			$result = GraphQL::executeQuery($schema, $query, $rootValue, null, $variableValues);
			echo json_encode($result);
		}
	} 
catch (\Exception $e) {
    $output = [
        'error' => [
            'message' => $e->getMessage()
        ]
    ];
}