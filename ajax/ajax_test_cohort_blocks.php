<?php 

/* This file allows you to know if the selected cohort is in blocks or not */

//require moodle file
require_once(dirname(__FILE__) . '/../../../config.php');

//get the cohortId
$cohortId = $_POST['cohortId'];

//store this variable in a GLOBAL variable (for export)
global $SESSION;
$SESSION->cohortId = $cohortId;

$result = array();
$result[0] = 1; //by default, cohort in blocks

$sqlBlock = "SELECT DISTINCT l2.*
FROM {local_training_level1} l2
JOIN {local_training_to_l1} l1 ON l1.l1_id = l2.id
JOIN {local_training_to_cohort} l0 ON l0.trainingid = l1.training_id
WHERE l0.cohortid = :cohortid;";

$paramsBlock = array('cohortid' => $cohortId); //array of parameters

$blocks = $DB->get_records_sql($sqlBlock, $paramsBlock);

if (empty($blocks)) { //cohort not in blocks
    $result[0] = 0;
}

//send result as JSON response
header('Content-Type: application/json');
echo json_encode($result);
