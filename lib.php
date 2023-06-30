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
 * Students Achievements definition (add the link to the plugin in courses report and home report)
 *
 * @package    report_students_achievements
 * @copyright  2023 Esteban BIRET-TOSCANO
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//display errors in the browser
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('MOODLE_INTERNAL') || die();

function report_students_achievements_extend_navigation_course($navigation, $course, $context) {

    //id = 1 if you access the plugin by Home > Reports, otherwise the course id if accessed through course reports
    $url = new moodle_url('/report/students_achievements/index.php', array('id' => $course->id));
    $name = get_string('pluginname', 'report_students_achievements');

    //add the navigation node
    $navigation->add($name, $url, navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
}
