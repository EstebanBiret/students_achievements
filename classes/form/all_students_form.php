<?php

defined('MOODLE_INTERNAL') || die();

//require moodle files and js
require_once("$CFG->libdir/formslib.php");
require_once('/var/www/html/moodle/config.php');
$PAGE->requires->js('/report/students_achievements/amd/src/all_students.js');

//the second form, containing all the students
class all_students_form extends moodleform {

    public function definition() {
        global $CFG, $DB;

        // A reference to the form is stored in $this->form.
        $mform = $this->_form; // Don't forget the underscore!

        $sqlStudents = "SELECT DISTINCT u.*
        FROM {user} u
        INNER JOIN {role_assignments} ra ON (ra.userid = u.id)
        WHERE ra.roleid = :roleid
        ORDER BY u.lastname ASC;";

        $params = array('roleid' => 5); //array of parameters, 5 is the student role

        //all the students
        $students = $DB->get_records_sql($sqlStudents, $params);

        $allStudents = array();

	    //first value, to avoid the first student being selected by default
        $allStudents[] = '';

        //for each student, add an option in the autocomplete, containing the student ID and displaying their first and lastname
	    foreach ($students as $student) {
            $allStudents[$student->id] = $student->firstname . ' ' . $student->lastname;
        }

        //array of options
        $options = array(
            'multiple' => false, //only one student can be selected
            'placeholder' => get_string('select_student', 'report_students_achievements'),
        );

	    //add the field to the form
        $mform->addElement('autocomplete', 'name', get_string('students_name', 'report_students_achievements'), $allStudents, $options);
        $mform->addElement('html', '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>');
    }

    // Custom validation should be added here.
    function validation($data, $files) {
        return [];
    }
}
