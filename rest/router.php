<?php
require 'Slim/Slim.php';
require_once '../../../config.php';
\Slim\Slim::registerAutoloader ();

$app = new \Slim\Slim ();

// $app->get('/course', 'course');
// $app->('/course(/:identifier)', 'course');
$app->map ( '/course/(:identifier)', 'course' )->via ( 'GET', 'POST' );
$app->map ( '/course/id/:id', 'courseDetailed' )->via ( 'GET', 'POST' );
$app->map ( '/Dateien', 'Dateien' )->via ( 'GET', 'POST' );
$app->map ( '/Kommunikation', 'Kommunikation' )->via ( 'GET', 'POST' );
$app->map ( '/Tests', 'Tests' )->via ( 'GET', 'POST' );
$app->run ();
function course() {
	$app = \Slim\Slim::getInstance ();
	global $DB;
	$fields = '{course}.id,{course}.category,{course}.fullname,{course}.shortname,{course}.idnumber,{course}.format,{course}.visible,{course}.timecreated,{course}.timemodified';
	$sql = "SELECT " . $fields . " FROM {course}";
	$query = $app->request->get ( 'query' );
	if (! empty ( $query )) {
		$name = str_replace ( ' ', '%', $query );
		$sql .= " WHERE {course}.fullname LIKE '%" . $name . "%' OR {course}.shortname LIKE '%" . $name . "%'";
	}
	$result = $DB->get_records_sql ( $sql );
	
	$categories = $DB->get_records ( 'course_categories', array (), null, 'id,name,parent' );
	// echo "<pre>".print_r($categories, true)."</pre>";
	$array = array ();
	foreach ( $result as $key => $value ) {
		if ($value->category == 0) {
			$value->fbID = false;
			$value->semesterID = false;
		} else {
			$value->fbID = $value->category;
			$value->fb = $categories [$value->fbID]->name;
			$value->semesterID = $categories [$value->fbID]->parent;
		}
		
		if ($value->semesterID == 0) {
			$value->semester = false;
		} else {
			$value->semester = $categories [$value->semesterID]->name;
		}
		$array [] = $value;
	}
	$sum = count ( $array );
	$array = array (
			"Result" => "OK",
			"Records" => $array 
	);
	// echo "<pre>".print_r($result, true)."</pre>";
	echo json_encode ( $array );
}
function courseDetailed($id) {
	global $DB;
	$result = $DB->get_records ( 'course', array (
			'id' => $id 
	) );
	$array = array ();
	foreach ( $result as $key => $value ) {
		$array [] = $value;
	}
	// echo "<pre>".print_r($array, true)."</pre>";
	
	$categories = $DB->get_records ( 'course_categories', array (), null, 'id,name,parent' );
	if ($array [0]->category == 0) {
		$array [0]->fbID = false;
		$array [0]->semesterID = false;
	} else {
		$array [0]->fbID = $array [0]->category;
		$array [0]->fb = $categories [$array [0]->fbID]->name;
		$array [0]->semesterID = $categories [$array [0]->fbID]->parent;
	}
	
	if ($array [0]->semesterID == 0) {
		$array [0]->semester = false;
	} else {
		$array [0]->semester = $categories [$array [0]->semesterID]->name;
	}
	$array [0]->Lehrende = getPersonsInCourse ( "Lehrende", $id );
	$array [0]->Assistenz = getPersonsInCourse ( "Assistenz", $id );
	
	// echo "<pre>" . print_r ( $array, true ) . "</pre>";
	
	$array = array (
			"Result" => "OK",
			"Records" => $array 
	);
	echo json_encode ( $array );
}
function Dateien() {
	global $DB;
	$app = \Slim\Slim::getInstance ();
	$sql = "SELECT
			mdl_course.id as course,
			mdl_course.fullname,
			mdl_course.timecreated as timecreated,
			mdl_course.timemodified as timemodified,
			(SELECT COUNT(mdl_folder.id) FROM mdl_folder WHERE mdl_folder.course=mdl_course.id) AS folders,
			(SELECT COUNT(mdl_resource.id) FROM mdl_resource WHERE mdl_resource.course=mdl_course.id) AS files,
			(SELECT mdl_course_categories.name FROM mdl_course_categories WHERE mdl_course_categories.id=mdl_course.category) AS fb,
			mdl_course.category as fbid,
			(SELECT mdl_course_categories.name FROM mdl_course_categories WHERE mdl_course_categories.id=
			(SELECT mdl_course_categories.parent FROM mdl_course_categories WHERE mdl_course_categories.id=mdl_course.category)
			) as semester,
			(SELECT mdl_course_categories.parent FROM mdl_course_categories WHERE mdl_course_categories.id=mdl_course.category) as semesterid,
			(SELECT COUNT(id) FROM mdl_user_enrolments WHERE enrolid IN (SELECT mdl_enrol.id FROM mdl_enrol WHERE mdl_enrol.courseid=mdl_course.id)) as participants
			FROM mdl_course";
	$jtSorting = $app->request->get('jtSorting');
	if($jtSorting) {
		$sql .= " ORDER BY ".$jtSorting;
	}
	
	$results = $DB->get_records_sql($sql);
	
	$array = array();
	foreach ($results as $key => $value) {
		if(!($value->files == 0 AND $value->folders == 0)) {
			$array[] = $value;
		}
	}
	$array = array (
			"Result" => "OK",
			"Records" => $array
	);
	echo json_encode($array);
}
function Kommunikation() {
	global $DB;
	$app = \Slim\Slim::getInstance ();
	$sql = "SELECT
			mdl_course.id as course,
			mdl_course.fullname,
			mdl_course.timecreated as timecreated,
			mdl_course.timemodified as timemodified,
			(SELECT COUNT(id) FROM mdl_course_modules WHERE mdl_course_modules.course = mdl_course.id AND module=(SELECT id FROM mdl_modules WHERE name LIKE 'forum')) AS forums,
			(SELECT COUNT(id) FROM mdl_course_modules WHERE mdl_course_modules.course = mdl_course.id AND module=(SELECT id FROM mdl_modules WHERE name LIKE 'chat')) AS chats,
			(SELECT mdl_course_categories.name FROM mdl_course_categories WHERE mdl_course_categories.id=mdl_course.category) AS fb,
			mdl_course.category as fbid,
			(SELECT mdl_course_categories.name FROM mdl_course_categories WHERE mdl_course_categories.id=
			(SELECT mdl_course_categories.parent FROM mdl_course_categories WHERE mdl_course_categories.id=mdl_course.category)
			) as semester,
			(SELECT mdl_course_categories.parent FROM mdl_course_categories WHERE mdl_course_categories.id=mdl_course.category) as semesterid,
			(SELECT COUNT(id) FROM mdl_user_enrolments WHERE enrolid IN (SELECT mdl_enrol.id FROM mdl_enrol WHERE mdl_enrol.courseid=mdl_course.id)) as participants
			FROM mdl_course";
	$jtSorting = $app->request->get('jtSorting');
	if($jtSorting) {
		$sql .= " ORDER BY ".$jtSorting;
	}

	$results = $DB->get_records_sql($sql);

	$array = array();
	foreach ($results as $key => $value) {
		if(!($value->chats == 0 AND $value->forums == 0)) {
			$array[] = $value;
		}
	}
	$array = array (
			"Result" => "OK",
			"Records" => $array
	);
	echo json_encode($array);
}

function Tests() {
	$app = \Slim\Slim::getInstance ();
	$jtSorting = $app->request->get('jtSorting');
	$mods = array('quiz', 'assign', 'hotpot', 'lesson', 'games');
	echo GetTableOfCoursesWithAmountOfModules($mods, $jtSorting);
}

/**
 * Kibt Kursliste aus, die neben allgemeinen Kursinformationen ausgibt, wie oft die angegebenen Module vorhanden sind. Sortierung nach $sortString
 * 
 * @param array $mods Alle Module, für die die Anzahl ermittelt werden soll. Bsp.: array("chat", "forum")
 * @param string $sortString Sort-String 
 * @return json $json Ausgabe-JSON
 */
function GetTableOfCoursesWithAmountOfModules($mods, $sortString = "") {
	global $DB;
	
	$sql = "SELECT
			mdl_course.id as course,
			mdl_course.fullname,
			mdl_course.timecreated as timecreated,
			mdl_course.timemodified as timemodified,";
	foreach ($mods as $mod) {
		$sql .= "(SELECT COUNT(id) FROM mdl_course_modules WHERE mdl_course_modules.course = mdl_course.id AND module=(SELECT id FROM mdl_modules WHERE name LIKE '".$mod."')) AS ".$mod.",";
	}
	$sql .= "
			(SELECT mdl_course_categories.name FROM mdl_course_categories WHERE mdl_course_categories.id=mdl_course.category) AS fb,
			mdl_course.category as fbid,
			(SELECT mdl_course_categories.name FROM mdl_course_categories WHERE mdl_course_categories.id=
			(SELECT mdl_course_categories.parent FROM mdl_course_categories WHERE mdl_course_categories.id=mdl_course.category)
			) as semester,
			(SELECT mdl_course_categories.parent FROM mdl_course_categories WHERE mdl_course_categories.id=mdl_course.category) as semesterid,
			(SELECT COUNT(id) FROM mdl_user_enrolments WHERE enrolid IN (SELECT mdl_enrol.id FROM mdl_enrol WHERE mdl_enrol.courseid=mdl_course.id)) as participants
			FROM mdl_course
			WHERE 
				";
	if($sortString) {
		$sql .= " ORDER BY ".$sortString;
	}

	$results = $DB->get_records_sql($sql);

	$array = array();
	foreach ($results as $key => $value) {
		$array[] = $value;
		/*if(!($value->etests == 0 AND $value->aufgaben == 0 AND $value->hotpot == 0 AND $value->lektion == 0 AND $value->spiele == 0)) {
			$array[] = $value;
		}*/
	}
	$array = array (
			"Result" => "OK",
			"SQL" => $sql, 
			"Records" => $array
	);
	return json_encode($array);
}

?>