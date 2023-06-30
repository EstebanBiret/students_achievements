<?php

/* This file allows to know if a given student is enrolled in any, one or more cohorts */

//require moodle files
require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->dirroot . '/cohort/lib.php');
require_once($CFG->libdir . '/tablelib.php');

//get the studentId
$studentId = $_POST['studentId'];

//get all cohorts to which the student is enrolled
$cohorts = cohort_get_user_cohorts($studentId);

$result = array();

//for all student cohorts, the cohort id and its name are stored
foreach ($cohorts as $cohort) {
    $result[] = array (
        'id' => $cohort->id,
        'name' => $cohort->name
    );
}

//send result as JSON response
header('Content-Type: application/json');
echo json_encode($result);