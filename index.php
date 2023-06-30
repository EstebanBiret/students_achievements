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
 * The Students Achievements report
 *
 * @package    report_student_achievements
 * @copyright  2023 Esteban BIRET-TOSCANO
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//display errors in browser
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('NO_OUTPUT_BUFFERING', true);

//all require files
require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/cohort/lib.php');
require_once('classes/form/all_cohorts_form.php');
require_once('classes/form/all_students_form.php');
require_once('classes/form/cohorts_of_student_form.php');

//get the courseId
$id = required_param('id',PARAM_INT);
$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

//login, check capabilities and set context
require_login($course);
$context = context_course::instance($course->id);
require_capability('report/students_achievements:view',$context);
$PAGE->set_context($context);

//include css and js files
$PAGE->requires->css('/report/students_achievements/styles.css'); 
echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>';
$PAGE->requires->js('/report/students_achievements/amd/src/view_details.js'); 

//set url of the page
$url = new moodle_url('/report/students_achievements/index.php?id=1'); //add the id parameter (course) to avoid errors when purging caches within the plugin
$PAGE->set_url($url);

//add the moodle header
echo $OUTPUT->header();

//title and heading of the page
$PAGE->set_title(get_string('title', 'report_students_achievements'));
echo '<div class="title">';
    echo $OUTPUT->heading(get_string('heading', 'report_students_achievements'));
echo '</div>';

//2 forms (all cohorts & all students)
$form = new all_cohorts_form();
$form2 = new all_students_form();

echo '<div class="form-container">
        <div class="form-column">';
            $form->display();
  echo '</div>
        <div class="form-column">';
            $form2->display();
  echo '</div>
      </div>';

//display the number of students found
echo '<div id="number-student"></div>';

//hidden form at the beggining, cohorts of the student form
$form3 = new cohorts_of_student_form();

echo '<div id="hidden-form">';
    $form3->display();
echo '</div>';

//table that contains the student for the selected cohort
$table = new html_table();
$table->id = 'result-table';
echo html_writer::table($table);

//firstname and lastname of the selected student
echo '<div id = "container"></div>';

//all the data of the student (drop down sections & tables)
echo '<div id = "completion-student"></div>';

/* EXPORT FORMS */

//exports by student
echo '<div class = "export-student-no-cohort">'; //student who do not belong to any cohort
        echo $OUTPUT->download_dataformat_selector('', '/moodle/report/students_achievements/export/export_student_no_cohort.php', 'export');
echo '</div>';

echo '<div class = "export-student-cohort-blocks">'; //student who have one/more cohorts enrolled in blocks
        echo $OUTPUT->download_dataformat_selector('', '/moodle/report/students_achievements/export/export_student_cohort_blocks.php', 'export');
echo '</div>';

echo '<div class = "export-student-cohort-no-blocks">'; //student who have one/more cohorts NOT enrolled in blocks
        echo $OUTPUT->download_dataformat_selector('', '/moodle/report/students_achievements/export/export_student_cohort_no_blocks.php', 'export');
echo '</div>';

//exports by cohort
echo '<div class = "export-cohort-blocks">'; //all students in a cohort enrolled in blocks
        echo $OUTPUT->download_dataformat_selector('', '/moodle/report/students_achievements/export/export_cohort_blocks.php', 'export');
echo '</div>';

echo '<div class = "export-cohort-no-blocks">'; //all students in a cohort NOT enrolled in blocks
        echo $OUTPUT->download_dataformat_selector('', '/moodle/report/students_achievements/export/export_cohort_no_blocks.php', 'export');
echo '</div>';

//blocks and footer
echo $OUTPUT->blocks('side-post');
echo $OUTPUT->footer();
