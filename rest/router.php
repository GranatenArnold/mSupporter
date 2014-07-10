<?php
require 'Slim/Slim.php';
require_once '../../../config.php';
//require_once($CFG->libdir.'/adminlib.php');
//admin_externalpage_setup('reporttoverview', '', null, '', array('pagelayout'=>'report'));

function Rechtepruefung() {
	GLOBAL $CFG;
	require_once($CFG->libdir.'/adminlib.php');
	admin_externalpage_setup('reporttoverview', '', null, '', array('pagelayout'=>'report'));
	$hash = hash('sha256', microtime());
	$_SESSION['tOverview_TOKEN'] = $hash;
	global $DB;
	$record = new stdClass();
	$record->token = $hash;
	$time = 60 * 60 * 5; // 5 Stunden;
	$record->validuntil = time() + $time;
	$DB->insert_record('report_toverview', $record);
}


if(!isset($_SESSION['tOverview_TOKEN'])) {
	Rechtepruefung();
}
else {
	global $DB;
	$sql = "SELECT id FROM {report_toverview} WHERE validuntil < ".time();
	$results = $DB->get_records_sql($sql);
	foreach ($results as $key => $value) {
		$DB->delete_records('report_toverview', array('id' => $value->id));
	}
	
	$sql = "SELECT * FROM {report_toverview} WHERE token LIKE '".$_SESSION['tOverview_TOKEN']."' AND validuntil > ".time();
	if (count($DB->get_records_sql($sql)) < 1) {
		// TOKEN ABGELAUFEN
		unset($_SESSION['tOverview_TOKEN']);
		Rechtepruefung();
	}	
}


\Slim\Slim::registerAutoloader ();

$app = new \Slim\Slim ();

// $app->get('/course', 'course');
// $app->('/course(/:identifier)', 'course');
$app->map ( '/course/(:identifier)', 'course' )->via ( 'GET', 'POST' );
$app->map ( '/course/id/:id', 'courseDetailed' )->via ( 'GET', 'POST' );
$app->map ( '/Dateien', 'Dateien' )->via ( 'GET', 'POST' );
$app->map ( '/Kommunikation', 'Kommunikation' )->via ( 'GET', 'POST' );
$app->map ( '/Tests', 'Tests' )->via ( 'GET', 'POST' );
$app->map ( '/Kooperation', 'Kooperation' )->via ( 'GET', 'POST' );
$app->map ( '/Lehrorganisation', 'Lehrorganisation' )->via ( 'GET', 'POST' );
$app->map ( '/Rueckmeldungen', 'Rueckmeldungen' )->via ( 'GET', 'POST' );
$app->map ( '/user/(:identifier)', 'user' )->via ( 'GET', 'POST' );
$app->map ( '/user/id/:id', 'userDetailed' )->via ( 'GET', 'POST');
$app->map ( '/Category(/:id)', 'Category' )->via ( 'GET', 'POST' );
$app->run ();
function course() {
	$app = \Slim\Slim::getInstance ();
	$query = $app->request->get ( 'query' );
	global $DB;
	
	$sql = "SELECT TOP 30
		{course}.id,
		{course}.fullname,
		{course}.category as fbID,
		(SELECT name FROM {course_categories} WHERE id={course}.category) as fb,
		(SELECT parent FROM {course_categories} WHERE id={course}.category) as semesterID,
		(SELECT name FROM {course_categories} WHERE id=(SELECT parent FROM {course_categories} WHERE id={course}.category)) as semester
	FROM {course} ";
	if($query) {
		$name = str_replace ( ' ', '%', $query );
		$sql .= " WHERE {course}.fullname LIKE '%" . $name . "%' OR {course}.shortname LIKE '%" . $name . "%'";
	}
	$result = $DB->get_records_sql($sql);
	$array = array (
			"Result" => "OK",
			"Records" => $result
	);
	// echo "<pre>".print_r($result, true)."</pre>";
	echo json_encode ( $array );
}
function courseDetailed($id) {
	if($id == "undefined") {
		echo "NOT A COURSE ID!";
		die;
	}
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
function Dateien() {
	$app = \Slim\Slim::getInstance ();
	$jtSorting = $app->request->get('jtSorting');
	$mods = array('resource', 'folder');
	echo GetTableOfCoursesWithAmountOfModules($mods, $jtSorting);
}
function Kommunikation() {
	$app = \Slim\Slim::getInstance ();
	$jtSorting = $app->request->get('jtSorting');
	$mods = array('chat', 'forum');
	echo GetTableOfCoursesWithAmountOfModules($mods, $jtSorting);
}

function Tests() {
	$app = \Slim\Slim::getInstance ();
	$jtSorting = $app->request->get('jtSorting');
	$mods = array('quiz', 'assign', 'hotpot', 'lesson', 'games');
	echo GetTableOfCoursesWithAmountOfModules($mods, $jtSorting);
}

function Kooperation() {
	$app = \Slim\Slim::getInstance ();
	$jtSorting = $app->request->get('jtSorting');
	$mods = array('wiki', 'data', 'glossary', 'workshop');
	echo GetTableOfCoursesWithAmountOfModules($mods, $jtSorting);
}

function Lehrorganisation() {
	$app = \Slim\Slim::getInstance ();
	$jtSorting = $app->request->get('jtSorting');
	$mods = array();
	$additionalRows = "(SELECT COUNT(id) FROM {groups} WHERE courseid = {course}.id) AS gruppen,";
	echo GetTableOfCoursesWithAmountOfModules($mods, $jtSorting, $additionalRows);
}

function Rueckmeldungen() {
	$app = \Slim\Slim::getInstance ();
	$jtSorting = $app->request->get('jtSorting');
	$mods = array('choice', 'feedback', 'hotquestion');
	echo GetTableOfCoursesWithAmountOfModules($mods, $jtSorting);
}

function Category($id = 0) {
	GLOBAL $DB;
	$sql = "SELECT 
				c.id as Sem,
				c.name,
				(
					SELECT COUNT({course}.id) FROM {course} WHERE {course}.category = c.id
				) +
				(
					SELECT COUNT({course}.id) FROM {course} WHERE 
					{course}.category 
					IN
					(SELECT k.id FROM {course_categories} k WHERE k.parent = c.id)
				)	AS kursegesamt,
				
				(
					SELECT COUNT({course}.id) FROM {course} WHERE {course}.category = c.id AND {course}.idnumber != ''
				) +
				(
					SELECT COUNT({course}.id) FROM {course} WHERE 
					{course}.category 
					IN
					(SELECT k.id FROM {course_categories} k WHERE k.parent = c.id) AND {course}.idnumber != ''
				)	AS schnittstelle,
				
				(
					SELECT COUNT({course}.id) FROM {course} WHERE {course}.category = c.id AND {course}.idnumber = ''
				) +
				(
					SELECT COUNT({course}.id) FROM {course} WHERE 
					{course}.category 
					IN
					(SELECT k.id FROM {course}_categories k WHERE k.parent = c.id) AND {course}.idnumber = ''
				)	AS manuell
				
			FROM 
				{course_categories} c
			WHERE
				parent = ".$id." AND
				id != 1
			ORDER BY
				c.timemodified ASC";
	$categories = $DB->get_records_sql($sql);
	$manuell = 0;
	$schnittstelle = 0;
	$gesamt = 0;
	foreach ($categories as $id => $category) {
		$manuell += $category->manuell;
		$schnittstelle += $category->schnittstelle;
		$gesamt += $category->kursegesamt;
	}
	$array['subcategories'] = $categories;
	$array['manuell'] = $manuell;
	$array['schnittstelle'] = $schnittstelle;
	$array['gesamt'] = $gesamt;
	//echo "<pre>".print_r($array, true)."</pre>";
	echo json_encode($array);
}

/**
 * Kibt Kursliste aus, die neben allgemeinen Kursinformationen ausgibt, wie oft die angegebenen Module vorhanden sind. Sortierung nach $sortString
 * 
 * @param array $mods Alle Module, für die die Anzahl ermittelt werden soll. Bsp.: array("chat", "forum")
 * @param string $sortString Sort-String 
 * @param string $additionalRows SQL-Abfrage als String
 * @return json $json Ausgabe-JSON
 */
function GetTableOfCoursesWithAmountOfModules($mods, $sortString = "", $additionalRows = "") {
	global $DB;
	
	$sql = "SELECT
			{course}.id as course,
			{course}.fullname,
			{course}.timecreated as timecreated,
			{course}.timemodified as timemodified,";
	foreach ($mods as $mod) {
		$sql .= "(SELECT COUNT(id) FROM {course_modules} WHERE {course_modules}.course = {course}.id AND module=(SELECT id FROM {modules} WHERE name LIKE '".$mod."')) AS ".$mod.",";
	}
	$sql .= $additionalRows;
	$sql .= "
			(SELECT {course_categories}.name FROM {course_categories} WHERE {course_categories}.id={course}.category) AS fb,
			{course}.category as fbid,
			(SELECT {course_categories}.name FROM {course_categories} WHERE {course_categories}.id=
			(SELECT {course_categories}.parent FROM {course_categories} WHERE {course_categories}.id={course}.category)
			) as semester,
			(SELECT {course_categories}.parent FROM {course_categories} WHERE {course_categories}.id={course}.category) as semesterid,
			(SELECT COUNT(id) FROM {user_enrolments} WHERE enrolid IN (SELECT {enrol}.id FROM {enrol} WHERE {enrol}.courseid={course}.id)) as participants
			FROM {course}
				";
	if($sortString) {
		$sql .= " ORDER BY ".$sortString;
	}
	//echo "<pre>".print_r($sql, true)."</pre>";
	$results = $DB->get_records_sql($sql);

	$array = array();

	foreach ($results as $key => $value) {
		if(!empty($mods)) {
			$sum = 0;
			//echo print_r($value, true);
			foreach($mods as $mod) {
				//echo $mod.": ".(string)$value->$mod;
				$sum = $sum + $value->$mod;
			}
			if($sum > 0) {
				$array[] = $value;
			}
		}
		else {
			$array[] = $value;
		}
	}
	$array = array (
			"Result" => "OK",
			"SQL" => $sql, 
			"Records" => $array
	);
	return json_encode($array);
}

function user() {
	$app = \Slim\Slim::getInstance ();
	$query = $app->request->get ( 'query' );
	global $DB;

	$sql = "SELECT TOP 30
				{user}.id,
				{user}.username,
				{user}.firstname,
				{user}.lastname,
				{user}.email
			FROM {user}
			";
	
	if($query) {
		$name = str_replace ( ' ', '%', $query );
		$sql .= " WHERE ({user}.firstname + {user}.lastname) LIKE '%" . $name . "%' OR {user}.username LIKE '%" . $name . "%' OR {user}.email LIKE '%" . $name . "%'";
	}
	
	$result = $DB->get_records_sql($sql);
	$array = array (
			"Result" => "OK",
			"Records" => $result
	);
	// echo "<pre>".print_r($result, true)."</pre>";
	echo json_encode ( $array );
}

function userDetailed($id) {
	global $DB;
	$result = $DB->get_records ( 'user', array (
			'id' => $id
	) );
	$array = array ();
	foreach ( $result as $key => $value ) {
		$array [] = $value;
	}
	// echo "<pre>".print_r($array, true)."</pre>";
	
	$array = array("Result" => "OK", "Records" => $array ); echo json_encode($array);
}


?>