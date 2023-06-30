<?php

/* This file recovers the data to be displayed for a student belonging to a cohort that is NOT enrolled in blocks */

//require moodle files
require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->dirroot . '/cohort/lib.php');
require_once($CFG->libdir . '/tablelib.php');
require_once(dirname(__FILE__) . '/../functions.php');

//get the cohortId AND the studentId
$value = $_POST['value'];
$ids = explode('-', $value);
$cohortId = $ids[0];
$studentId = $ids[1];

//store these variables in GLOBAL variables
global $SESSION;
$SESSION->studentId = $studentId;
$SESSION->cohortId = $cohortId;

//all courses of the cohort
$sqlSequence = "SELECT DISTINCT c.*
FROM {course} c
JOIN {enrol} e ON e.courseid = c.id
WHERE e.customint1 = :customint1;";

$paramsSequence= array('customint1' => $cohortId); //array of parameters

$sequences = $DB->get_records_sql($sqlSequence, $paramsSequence);

$result = array();

//browse all courses
foreach ($sequences as $sequence) {

        $sequenceId = $sequence->id;
        $sequenceName = $sequence->fullname;

        $sqlTypeOfModules = "SELECT DISTINCT m.*
        FROM {modules} m
        JOIN {course_modules} cm ON m.id = cm.module
        JOIN {course} c ON cm.course = c.id
        WHERE c.id = :id;";

        $paramsTypeOfModules = array('id' => $sequenceId);

        $typesOfModules = $DB->get_records_sql($sqlTypeOfModules, $paramsTypeOfModules);

	    //array of the activities of the course
        $sequencesActivities = array();

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

                            /*managing the display of dates in the html_table
                            //---------------------------------------------------*/
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
                            //------------------------------------------------------------//

			                //check if the activity can be displayed (depending on access restrictions)
                            if (checkConditions($activity, $studentId)) {

                                $completionStatus = ($activity->completionstate == 0) ? get_string('uncompleted', 'report_students_achievements') : get_string('completed', 'report_students_achievements');

                                //link to activity
                                $activityUrl = ''.$CFG->wwwroot.'/mod/'.$name.'/view.php?id='.$activityId.'';
                                $link = html_writer::link($activityUrl, $activityName);

                                //array of the activity
                                $sequencesActivities[] = array(
                                    'name' => $activityName,
                                    'link' =>$activityUrl,
                                    'type' => $name,
                                    'completion' => $completionStatus,
                                    'date' => $date
                                );
                            }
			                //else, move on to the next activity
                        }
                }
        }

                //add course name and array of activities
                $result[] = array(
                        'sequenceName' => $sequenceName,
                        'activities' => $sequencesActivities
                );
}

//send result as JSON response
header('Content-Type: application/json');
echo json_encode($result);
