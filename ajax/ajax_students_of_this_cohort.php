<?php

/* This file retrieves the data to be entered in the html_table (lastname, firstname and link for each student in the selected cohort */

//require moodle files
require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/tablelib.php');

//get the cohortId
$cohortId = $_POST['cohortId'];

$sqlusers = "SELECT DISTINCT u.*
	FROM {user} u
	INNER JOIN {role_assignments} ra ON (ra.userid = u.id)
	INNER JOIN {cohort_members} cm ON (cm.userid = u.id)
	WHERE ra.roleid = :roleid
	AND cm.cohortid = :cohortid
	ORDER BY u.lastname ASC;";

$params = array('roleid' => 5, 'cohortid' => $cohortId); //array of parameters (5 corresponds to the student role)

$users= $DB->get_records_sql($sqlusers, $params);

$count_students = 0;

$result = array();

//to display the number of students in the cohort
foreach ($users as $user) {
    $count_students ++;
}

$result[0][0] = $count_students;

//display in the html_table (defined in index.php) the students of the cohort
foreach ($users as $user) {
    $row = array(
        $user->firstname,
        $user->lastname,
        html_writer::tag('span', get_string('viewdetails', 'report_students_achievements'), array('class' => 'student-details', 'studentId' => $user->id, 'cohortId' => $cohortId))
        );

        $result[] = $row;
}

//send result as JSON response
header('Content-Type: application/json');
echo json_encode($result);
