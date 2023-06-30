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
 * This file is responsible for define all the functions for display report and export report
 *
 * @package   report_students_achievements
 * @copyright 2023 Esteban BIRET-TOSCANO
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

//all require files
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/cohort/lib.php');
require_once($CFG->libdir . '/tablelib.php');

/**
 * Recursively scans the provided conditions and retrieves the corresponding dates based on conditions of the same level
 *
 * @param array $conditions : An array of objects representing conditions
 * @param int $studentId : The student ID
 *
 * @return array $dates : Contains the corresponding dates and conditions.
 */
function processConditions($conditions, $studentId) {
    $dates = [];

    foreach ($conditions as $condition) {
        if (isset($condition->type) && $condition->type === 'date' && isset($condition->d) && $condition->d === '>=') {
            $dates[] = [
                'date' => $condition->t,
                'conditions' => $conditions
            ];
        } elseif (isset($condition->c)) {
            $subDates = processConditions($condition->c, $studentId);
            $dates = array_merge($dates, $subDates);
        }
    }

    return $dates;
}

/**
 * Allows to obtain the corresponding date according to the conditions of the same level
 *
 * @param array $dates : An array with dates and conditions associated (same level on the JSON file)
 * @param int $studentId : The student ID
 *
 * @return The first corresponding date or '-' if no conditions are met.
 */
function getMatchingDate($dates, $studentId) {
    foreach ($dates as $date) {

        $matchingCondition = null;

        foreach ($date['conditions'] as $condition) {

            if ($condition->type !== 'date') {
                if (evaluateCondition($condition, $studentId)) {
                    $matchingCondition = $condition;

                    //the first valid condition containing a date
                    break;
                }
            }
        }

        if ($matchingCondition !== null) {
	        //the date corresponding to the validated condition
            return date('d/m/Y', $date['date']);
        }
    }

    //no date to display on the html_table object
    return '-';
}

/**
 * Checks if the profile condition is met for a given student.
 *
 * @param int $studentId : The student ID
 * @param string $profileField : The profile field
 * @param string $operator : The comparison operator to use (here, it is by default equalto)
 * @param string $value : The value of the field to be tested
 *
 * @return boolean True if the condition is met, false otherwise.
 */
function checkProfileCondition($studentId, $profileField, $operator, $value) {

    global $DB;

    $sqlTest = "SELECT uif.*
    FROM {user_info_field} uif
    WHERE uif.shortname = :shortname;";

    $sqlTestParams = array('shortname' => $profileField);

    $test = $DB->get_records_sql($sqlTest, $sqlTestParams);

    //see if the profile field is in the user table or user_infos_xx tables
    $resultTest = (count($test) > 0) ? true : false;

    if ($resultTest) { //user_infos_xx tables

        $sqlUserInfos = "SELECT uid.*
        FROM {user_info_data} uid
        JOIN {user_info_field} uif ON uif.id = uid.fieldid
        WHERE uif.shortname = :shortname
        AND uid.userid = :userid
        AND uid.data = :data;";

        $sqlUserInfosParams = array('shortname' => $profileField, 'userid' => $studentId, 'data' => $value);

        $userInfos = $DB->get_records_sql($sqlUserInfos, $sqlUserInfosParams);

        //see if there is a match between the profile field and the student (at least one record)
        $result = (count($userInfos) > 0) ? true : false;
    }

    else { //user table

        $sqlUser = " SELECT u.*
        FROM {user} u
        WHERE u.id = :id
        AND u." . $profileField . " = :value;";

        $sqlUserParams = array('id' => $studentId, 'value' => $value);

        $user = $DB->get_records_sql($sqlUser, $sqlUserParams);

        //see if there is a match between the profile field and the student (at least one record)
        $result = (count($user) > 0) ? true : false;

    }

    return $result;
}

/**
 * Checks if the group condition is met for a given student.
 *
 * @param int $studentId : The student ID
 * @param int $groupId : The group field
 *
 * @return boolean True if the condition is met, false otherwise.
 */
function checkGroupCondition($studentId, $groupId) {

    global $DB;

    $sql = "SELECT gm.*
    FROM {groups_members} gm
    WHERE gm.groupid = :groupid
    AND gm.userid = :userid;";

    $sqlParams = array('groupid' => $groupId, 'userid' => $studentId);

    $resultGroup = $DB->get_records_sql($sql, $sqlParams);

    //see if there is a match between the group and the student (at least one record)
    $result = (count($resultGroup) > 0) ? true : false;

    return $result;
}

/**
 * Checks if the individual condition is met for a given student, using the functions checkProfileCondition and checkGroupCondition
 *
 * @param object $condition : The condition
 * @param int $studentId : The student ID
 *
 * @return boolean True if the condition is met, false otherwise.
 */
function evaluateCondition($condition, $studentId) {

    if (isset($condition->op) && isset($condition->c)) {
        $operator = $condition->op;
        $subConditions = $condition->c;

        if ($operator === '|') {

            foreach ($subConditions as $subCondition) {
                if (evaluateCondition($subCondition, $studentId)) {
                    //at least one sub condition is satisfied ('|' operator)
                    return true;
                }
            }
            return false; //no sub conditions are met

        } elseif ($operator === '&') {

            foreach ($subConditions as $subCondition) {
                if (!evaluateCondition($subCondition, $studentId)) {
                    //a sub condition is not met ('&' operator)
                    return false;
                }
            }
            return true; //all the sub conditions are met
        }
    }

    //checks of individual conditions
    if (isset($condition->type)) {
        switch ($condition->type) {

            case 'profile':
                $profileField = '';

                //---------------------------- cf or sf
                if (isset($condition->cf)) {
                    $profileField = $condition->cf;
                } elseif (isset($condition->sf)) {
                    $profileField = $condition->sf;
                }
                //---------------------------

                $operator = isset($condition->op) ? $condition->op : '';
                $value = isset($condition->v) ? $condition->v : '';
                return checkProfileCondition($studentId, $profileField, $operator, $value);

            case 'group':
                $groupId = isset($condition->id) ? $condition->id : '';
                return checkGroupCondition($studentId, $groupId);

            case 'date': //do nothing

            default:
                //type of condition not yet considered
                return true;
        }
    }
    //if no types
    return true;
}

/**
 * Checks whether an activity can be added, according to its conditions
 * @param object $activity : The activity to check
 * @param int $studentId : The student ID
 *
 * @return boolean True if the conditions are met, false otherwise.
 */
function checkConditions($activity, $studentId) {

    if ($activity->availability == NULL) {
        //no conditions
        return true;
    }

    $availability = json_decode($activity->availability);

    if (isset($availability->op) && isset($availability->c) && count($availability->c) != 0) {
        $operator = $availability->op;
        $conditions = $availability->c;

        if ($operator === '|') {

            foreach ($conditions as $condition) {
                if (evaluateCondition($condition, $studentId)) {
                    //at least one sub condition is satisfied ('|' operator)
                    return true;
                }
            }
            //no sub conditions are met
            return false;

        } elseif ($operator === '&') {

            foreach ($conditions as $condition) {
                if (!evaluateCondition($condition, $studentId)) {
                    //a sub condition is not met ('&' operator)
                    return false;
                }
            }
            //all the sub conditions are met
        return true;
        }
    }

    //add the activity if its conditions JSON is empty or does not specify a logical operator
    return true;
    
}
