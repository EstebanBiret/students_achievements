<?php

defined('MOODLE_INTERNAL') || die();

//require moodle files and js
require_once("$CFG->libdir/formslib.php");
require_once('/var/www/html/moodle/config.php');
$PAGE->requires->js('/report/students_achievements/amd/src/all_cohorts.js');

//the first form, containing all the cohorts
class all_cohorts_form extends moodleform {

    public function definition() {
        global $CFG, $DB;

        // A reference to the form is stored in $this->form.
        $mform = $this->_form; // Don't forget the underscore!

        //get all cohorts
        $cohorts = cohort_get_all_cohorts(0, -1);

        $allCohorts = array();

	    //first value, to avoid the first cohort being selected by default
        $allCohorts[] = get_string('select_cohort', 'report_students_achievements');

	    //for each cohort, add an option in the select, containing the cohort ID and displaying its name 
        foreach ($cohorts['cohorts'] as $cohort) {
            $allCohorts[$cohort->id] = $cohort->name;
        }

	    //array of options
        $options = array(
	    //placeholder does not appear to be working on select field
            'placeholder' => get_string('select_cohort', 'report_students_achievements'),
        );

        //add the field to the form
        $mform->addElement('select', 'cohort', get_string('cohort', 'report_students_achievements'), $allCohorts, $options);
        $mform->setType('cohort', PARAM_INT);
        $mform->addElement('html', '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>');
    }

    // Custom validation should be added here.
    function validation($data, $files) {
        return [];
    }
}
