<?php

defined('MOODLE_INTERNAL') || die();

//require moodle files and js
require_once("$CFG->libdir/formslib.php");
require_once('/var/www/html/moodle/config.php');
$PAGE->requires->js('/report/students_achievements/amd/src/cohorts_of_student.js');

//the last form. Hidden by default, containing all the cohorts of a selected student by the 2nd form (if this student have more than 1 cohort)
class cohorts_of_student_form extends moodleform {

    public function definition() {
        global $CFG, $DB;

        // A reference to the form is stored in $this->form.
        $mform = $this->_form; // Don't forget the underscore!

	    //it is in the javascript files that we will add the options of the form (cohorts of the student), according to the selected student
        $mform->setAttributes(['id' => 'cohort-student']);

        //add the field to the form
        $mform->addElement('select', 'cohortsStudent', get_string('cohort_of_this_student', 'report_students_achievements'), '');
        $mform->setType('cohort', PARAM_INT);
        $mform->addElement('html', '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>');
    }

    // Custom validation should be added here.
    function validation($data, $files) {
        return [];
    }
}