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
require_once 'vendor/autoload.php';
require_once 'conn.php';
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
header('Content-Type: application/json');

class StatObj {
	public $last_team_wu;
	public $rank;
	public $team_score;
	public $team_work_units;
	public $team_name;
	
	function __construct($last_team_wu, $rank, $team_score, $team_work_units, $team_name) {
		$this->last_team_wu = $last_team_wu;
		$this->rank = $rank;
		$this->team_score = $team_score;
		$this->team_work_units = $team_work_units;
		$this->team_name = $team_name;
	}
	
	static function getStats($qty) {
		$fahConn = new PDO(SERVER, USER, PWD);
		$fahQuery = $fahConn->prepare("SELECT HEX(`ID`) as 'ID', `last_team_wu`, `rank`, `team_score`, `team_work_units`, `name` as 'team_name' FROM fah_stats order by `ID` desc limit " . $qty);
		$fahQuery->execute();
		$stats = $fahQuery->fetchAll(PDO::FETCH_ASSOC);
		return $stats;
	}
	
	function insertStat($connection) {
		$fahQuery = $connection->prepare("INSERT INTO fah_stats (`ID`,`last_team_wu`,`rank`,`team_score`,`team_work_units`,`name`) values (ordered_uuid(UUID()),?, ?, ?, ?, ?)");
		$fahQuery->bindParam(1, $this->last_team_wu);
		$fahQuery->bindParam(2, $this->rank);
		$fahQuery->bindParam(3, $this->team_score);
		$fahQuery->bindParam(4, $this->team_work_units);
		$fahQuery->bindParam(5, $this->team_name);
		
		$fahQuery->execute();
	}
	
}

$fahType = new ObjectType([
        'name' => 'FAHType',
        'description' => 'FAH stats from json object',
        'fields' => [
            'ID' => Type::id(),
            'last_team_wu' => Type::string(),
            'rank' => Type::int(),
            'team_score' => Type::int(),
			'team_work_units' => Type::int(),
			'team_name' => Type::string()
			]
        ]);
	
    $queryType = new ObjectType([
        'name' => 'Query',
        'fields' => [
            'stat' => [
                'type' => Type::listOf($fahType),
				'args' => [
					'rows' => Type::int()
				],
                'resolve' => function ($root, $args) { return StatObj::getStats($args['rows']); }
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
					'team_score' => Type::int(),
					'team_work_units' => Type::int(),
					'team_name' => Type::string()
				],
                'resolve' => function ($root, $args) {
					$resolveConn = new PDO(SERVER, USER, PWD);
					$myStat = new StatObj($args['last_team_wu'], $args['rank'], $args['team_score'], $args['team_work_units'], $args['team_name']);
					print_r($myStat);
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
			$query = $_GET['query'];
			$variableValues = [];
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
?>
