<?php

/* This file recovers the data to be displayed for a student belonging to a cohort enrolled in blocks */

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

//sotre these variables in GLOBAL variables
global $SESSION;
$SESSION->studentId = $studentId;
$SESSION->cohortId = $cohortId;

//all blocks of the cohort
$sqlBlock = "SELECT DISTINCT l2.*
FROM {local_training_level1} l2
JOIN {local_training_to_l1} l1 ON l1.l1_id = l2.id
JOIN {local_training_to_cohort} l0 ON l0.trainingid = l1.training_id
WHERE l0.cohortid = :cohortid;";

$paramsBlock = array('cohortid' => $cohortId); //array of parameters

$blocks = $DB->get_records_sql($sqlBlock, $paramsBlock);

$result = array();

//browse all blocks
foreach ($blocks as $block) {

    $blockId = $block->id;
    $blockName = $block->fullname;

    $sqlModules = "SELECT DISTINCT l4.*
    FROM {local_training_level2} l4
    JOIN {local_training_l1_to_l2} l3 ON l3.level2id = l4.id
    WHERE l3.level1id = :level1id;";

    $paramsModules = array('level1id' => $blockId);

    $modules = $DB->get_records_sql($sqlModules, $paramsModules);

    //array for the modules of the block
    $modulesArray = [];

    //browse all modules
    foreach ($modules as $module) {

        $moduleId = $module->id;
        $moduleName = $module->fullname;

        $sqlCourses = "SELECT c.*
        FROM {course} c
        JOIN {local_training_l2_to_course} l5 ON l5.courseid = c.id
        WHERE l5.level2id = :level2id;";

        $paramsCourses = array('level2id' => $moduleId);

        $courses = $DB->get_records_sql($sqlCourses, $paramsCourses);

        //array for the courses of the module
        $coursesArray = [];

        //browse all courses
        foreach ($courses as $course) {

            $courseId = $course->id;
            $courseName = $course->fullname;

            $sqlTypeOfModules = "SELECT DISTINCT m.*
            FROM {modules} m
            JOIN {course_modules} cm ON m.id = cm.module
            JOIN {course} c ON cm.course = c.id
            WHERE c.id = :id;";

            $paramsTypeOfModules = array('id' => $courseId);

            $typesOfModules = $DB->get_records_sql($sqlTypeOfModules, $paramsTypeOfModules);

            //array for the activities of the course
            $activitiesArray = [];

            //browse each type of modules in this course
            foreach ($typesOfModules as $typeOfModule) {

                $name = $typeOfModule->name; //get the activity type, to find out which table to look for at each new activity
                $idModule = $typeOfModule->id;

                $sqlDetailsModules = "SELECT cm.id AS course_modules_id, t.name AS name
                FROM {course_modules} AS cm
                JOIN {". $name ."} AS t ON cm.instance = t.id
                WHERE cm.course = :course
                AND cm.module = :module;";

                $paramsDetailsModules = array('course' => $courseId, 'module' => $idModule);

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
                        ------------------------------------------------*/
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
                        //---------------------------------------------------------------//

                        //check if the activity can be displayed (depending on access restrictions)
                        if (checkConditions($activity, $studentId)) {

                            $completionStatus = ($activity->completionstate == 0) ? get_string('uncompleted', 'report_students_achievements') : get_string('completed', 'report_students_achievements');

                            //link to activity
                            $activityUrl = ''.$CFG->wwwroot.'/mod/'.$name.'/view.php?id='.$activityId.'';
                            $link = html_writer::link($activityUrl, $activityName);

                            //add activity informations to the array
                            $activitiesArray[] = array(
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

            //add course name and array of activities
            $coursesArray[] = array(
                'courseName' => $courseName,
                'activities' => $activitiesArray
            );
        }

        //add module name and array of courses
        $modulesArray[] = array(
            'moduleName' => $moduleName,
            'courses' => $coursesArray
        );
    }

    //add block name and array of modules
    $result[] = array(
        'blockName' => $blockName,
        'modules' => $modulesArray
    );
}

//send result as JSON response
header('Content-Type: application/json');
echo json_encode($result);
