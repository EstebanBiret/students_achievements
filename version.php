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
 * Students Achievements report version details
 *
 * @package    report_students_achievements
 * @copyright  2023 Esteban BIRET-TOSCANO
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//display errors in browser
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('MOODLE_INTERNAL') || die;

$plugin->version   = 2023063000; //don't forget to increment the last digit when editing in db/access.php (2023-06-30 version 00)
$plugin->requires  = 2020061500;
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = '4.1.2';
$plugin->component = 'report_students_achievements';


