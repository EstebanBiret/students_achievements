<?php

/* This file retrieves the first and lastname of a given student*/

//require moodle files
require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->dirroot . '/cohort/lib.php');
require_once($CFG->libdir . '/tablelib.php');

//get the studentId
$studentId = $_POST['studentId'];

$sqlusers = "SELECT DISTINCT u.*
        FROM {user} u
        WHERE u.id = :id;";

$params = array('id' => $studentId); //array of parameters

$users= $DB->get_records_sql($sqlusers, $params);

$result = array();

//store the first and lastname of the student
foreach ($users as $user) {
    $row = array(
        $user->firstname,
        $user->lastname
        );

	$result[] = $row;
}

//send result as JSON response
header('Content-Type: application/json');
echo json_encode($result);
