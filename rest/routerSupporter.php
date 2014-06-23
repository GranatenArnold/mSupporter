<?php
require 'Slim/Slim.php';
require_once '../../../config.php';
\Slim\Slim::registerAutoloader ();

$app = new \Slim\Slim ();

// $app->get('/course', 'course');
// $app->('/course(/:identifier)', 'course');
$app->map ( '/course/(:identifier)', 'course' )->via ( 'GET', 'POST' );
$app->map ( '/course/id/:id', 'courseDetailed' )->via ( 'GET', 'POST' );
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
	$array[0]->Lehrende = getPersonsInCourse("Lehrende", $id);
	$array[0]->Assistenz =  getPersonsInCourse("Assistenz", $id);
	$array[0]->Tutoren = getPersonsInCourse("Tutoren", $id);
	$array[0]->Studierende = getPersonsInCourse("Studierende", $id);
	
	$array[0]->Module = getModulesInCourse($id);
	
	$array[0]->Einschreibemethoden = getEinschreibemethoden($id);
	
	$array[0]->UserEnrolments = getUserEnrolments($id);
	
	//echo "<pre>" . print_r ( $array, true ) . "</pre>";
	
	$array = array("Result" => "OK", "Records" => $array ); echo json_encode($array);
	 
}

/**
 * Gibt alle Personen zurück, die mit gegebener Rolle in einen Kurs eingetragen sind
 *
 * @param string $role
 *        	alle 'name'-Einträge in {role}, bspw. Manager, Course creator, Lehrende, Tutor, Studierende, Gast, Assistenz
 * @param int $course
 *        	Moodle-Kurs-ID
 */
function getPersonsInCourse($role, $course) {
	global $DB;
	$sql = "SELECT  
				{role_assignments}.id,
				{role_assignments}.roleid,
				{role_assignments}.userid,
				
				{context}.instanceid as course,
				
				{role}.archetype,
				
				{user}.firstname,
				{user}.lastname
			FROM 
				{role_assignments}, {context}, {role}, {user}
			WHERE
				{role_assignments}.contextid = {context}.id AND
				{context}.instanceid = ".$course." AND
				{role}.id = {role_assignments}.roleid AND
				{user}.id = {role_assignments}.userid AND
				{role}.name LIKE '".$role."'";
	return $DB->get_records_sql($sql);
}

function getModulesInCourse($course) {
	global $DB;
	$sql = "SELECT
				{course_modules}.module,
				{modules}.name,
				count({course_modules}.module) as anzahl
			FROM 
				{course_modules}, {modules}
			WHERE
				{course_modules}.course = $course AND
				{modules}.id = {course_modules}.module
			GROUP BY 
				{course_modules}.module, {modules}.name
			";
	return $DB->get_records_sql($sql);
}

function getEinschreibemethoden($course) {
	global $DB;
	$sql = "SELECT
				{enrol}.enrol,
				{enrol}.id,
				{enrol}.status,
				{enrol}.password
			FROM {enrol}
			WHERE
				{enrol}.courseid = $course
			";
	return $DB->get_records_sql($sql);
}

function getUserEnrolments($course) {
	global $DB;
	$sql = "SELECT
				{enrol}.id,
				{enrol}.enrol,
				count({user_enrolments}.userid) as anzahl
			FROM
				{enrol}, {user_enrolments}
			WHERE
				{user_enrolments}.enrolid = {enrol}.id AND
				{enrol}.courseid = $course
			GROUP BY
				enrolid, {enrol}.id,{enrol}.enrol
			";
	return $DB->get_records_sql($sql);
}

?>
