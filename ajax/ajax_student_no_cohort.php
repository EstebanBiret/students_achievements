<?php

/* This file recovers the data to be displayed for a student who does not belong to any cohorts */

//require moodle files
require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->dirroot . '/cohort/lib.php');
require_once($CFG->libdir . '/tablelib.php');
require_once(dirname(__FILE__) . '/../functions.php');

//get the studentId
$studentId = $_POST['studentId'];

//store this variable in a GLOBAL variable
global $SESSION;
$SESSION->studentId = $studentId;

//all courses of the student
$sqlSequence = "SELECT DISTINCT c.*
FROM {user} u
JOIN {user_enrolments} ue ON ue.userid = u.id
JOIN {enrol} e ON e.id = ue.enrolid
JOIN {course} c ON c.id = e.courseid
WHERE u.id = :id;";

$paramsSequence= array('id' => $studentId); //array of parameters

$sequences = $DB->get_records_sql($sqlSequence, $paramsSequence);

$result = array();

//browse all courses
foreach ($sequences as $sequence) {

        $sequenceId = $sequence->id;
        $sequenceName = $sequence->fullname;

        $sequencesActivities = array();

        $sqlTypeOfModules = "SELECT DISTINCT m.*
        FROM {modules} m
        JOIN {course_modules} cm ON m.id = cm.module
        JOIN {course} c ON cm.course = c.id
        WHERE c.id = :id;";

        $paramsTypeOfModules = array('id' => $sequenceId);

        $typesOfModules = $DB->get_records_sql($sqlTypeOfModules, $paramsTypeOfModules);

	//browse each type of module in this course
        foreach ($typesOfModules as $typeOfModule) {

                $name = $typeOfModule->name; //get the activity type, to find out which table to look for at each new activity
                $idModule = $typeOfModule->id;

                $sqlDetailsModules = "SELECT cm.id AS course_modules_id, t.name AS name
                FROM {course_modules} AS cm
                JOIN {". $name ."} AS t ON cm.instance = t.id
                WHERE cm.course = :course
                AND cm.module = :module;";

                $paramsDetailsModules = array('course' => $sequenceId, 'module' => $idModule);

                $detailsModules = $DB->get_records_sql($sqlDetailsModules, $paramsDetailsModules);

                foreach ($detailsModules as $detailsModule) {

                        $idCourse_module = $detailsModule->course_modules_id;

                        $activityName = $detailsModule->name;

                        $sqlActivities = "SELECT cmc.completionstate, cm.availability, cm.id
                        FROM {course_modules} AS cm
                        LEFT JOIN {course_modules_completion} AS cmc ON cm.id = cmc.coursemoduleid AND cmc.userid = :userid
                        WHERE cm.id = :id
                        AND cm.completion > 0;";

                        $paramsActivities = array('userid' => $studentId, 'id' => $idCourse_module);

                        $activities = $DB->get_records_sql($sqlActivities, $paramsActivities);

                        //browse all activities of the course
                        foreach ($activities as $activity) {

                                $activityId = $activity->id;
                                $date = '-';

				//managing the display of dates in the html_table
                                //-------------------------------------------------------------//
                                if (isset($activity->availability)) {

                                        $availability = json_decode($activity->availability);

                                        if (isset($availability->c) && count($availability->c) > 0) {

                                                $dates = processConditions($availability->c, $studentId);

                                                if (count($dates) === 1) {
							//if there is only one date, it is displayed
                                                        $date = date('d/m/Y', $dates[0]['date']);

                                                } elseif (count($dates) > 1) {
							//otherwise, we look at which display according to access restrictions
                                                        $date = getMatchingDate($dates, $studentId);
                                                }
                                        }
                                }
                                //---------------------------------------------------------------

				//check if the activity can be displayed (depending on access restrictions)
                                if (checkConditions($activity, $studentId)) {

                                        $completionStatus = ($activity->completionstate == 0) ? get_string('uncompleted', 'report_students_achievements') : get_string('completed', 'report_students_achievements');

					//link to activity
                                        $activityUrl = ''.$CFG->wwwroot.'/mod/'.$name.'/view.php?id='.$activityId.'';
                                        $link = html_writer::link($activityUrl, $activityName);

					//array of the activity
                                        $sequencesActivities[] = array(
                                                'name' => $activityName,
                                                'link' => $activityUrl,
                                                'type' => $name,
                                                'completion' => $completionStatus,
                                                'date' => $date
                                        );
                                }
				//else, move on to the next activity
                        }
                }
        }

                //add the name of the course and a array containing all its activities in the main array
                $result[] = array(
                        'sequenceName' => $sequenceName,
                        'activities' => $sequencesActivities
                );
}

//sens result as JSON response
header('Content-Type: application/json');
echo json_encode($result);
