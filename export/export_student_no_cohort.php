<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file is responsible for producing the downloadable versions of student achievements (by student NOT enrolled in cohort)
 *
 * @package   report_students_achievements
 * @copyright 2023 Esteban BIRET-TOSCANO
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

//require moodle files
require_once ("../../../config.php");
require_once($CFG->dirroot . '/cohort/lib.php');
require_once(dirname(__FILE__) . '/../functions.php');

//get the student id from the GLOBAL variable (set in ajax files)
global $SESSION;
$studentId = $SESSION->studentId;

//all courses of a student
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

        $sqlTypeOfModules = "SELECT DISTINCT m.* 
        FROM {modules} m
        JOIN {course_modules} cm ON m.id = cm.module
        JOIN {course} c ON cm.course = c.id
        WHERE c.id = :id;";

        $paramsTypeOfModules = array('id' => $sequenceId);

        $typesOfModules = $DB->get_records_sql($sqlTypeOfModules, $paramsTypeOfModules);

        //array for the activities of the course
        $sequencesActivities = array();

        //browse each type of modules in this course
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
                                -------------------------------------------------------------*/
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
                                //-------------------------------------------------------------//

                            	//check if the activity can be displayed (depending on access restrictions)
                                if (checkConditions($activity, $studentId)) {
                                        
                                        $completionStatus = ($activity->completionstate == 0) ? get_string('uncompleted', 'report_students_achievements') : get_string('completed', 'report_students_achievements');

                                        //array of activities
                                        $sequencesActivities[] = array(
                                                'name' => $activityName,
                                                'type' => $name,
                                                'completion' => $completionStatus,
                                                'date' => $date
                                        );
                                }
                                //else, move on to the next activity
                        }
                }
        }

                //add the course name & array of activities
                $result[] = array(
                        'sequenceName' => $sequenceName,
                        'activities' => $sequencesActivities
                );
}

//------DOWNLOAD PART------//

//define headers of the column's file
$columns = array(get_string('sequence', 'report_students_achievements'), 
                 get_string('activity', 'report_students_achievements'), 
                 get_string('type', 'report_students_achievements'), 
                 get_string('completion', 'report_students_achievements'), 
                 get_string('opening_date', 'report_students_achievements')
                );

$data = [];

//tab the table to use in the API function
foreach ($result as $sequence) {
    $sequenceName = $sequence['sequenceName'];
    $activities = $sequence['activities'];

    foreach ($activities as $activity) {
        $activityName = $activity['name'];
        $type = $activity['type'];
        $completion = $activity['completion'];
        $date = $activity['date'];

        $data[] = [
            $sequenceName,
            $activityName,
            $type,
            $completion,
            $date
        ];
    }
}

//date of file download
$date_extraction = date('Y-m-d', time());

//format of the file (csv, xlxs, ods, pdf, json)
$dataformat = optional_param('export', '', PARAM_ALPHA);

//get student's informations for the file name
$user = $DB->get_record('user', ['id' => $studentId]);

//download file
\core\dataformat::download_data($user->firstname . '_' . $user->lastname . '_' . $date_extraction, $dataformat, $columns, $data); //lib/classes/dataformat.php
