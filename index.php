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
 * Run the DSV organization from the Web.
 *
 * @package    local_course_checker
 * @copyright  сн2019 Pavel Sokolov <pavel.m.sokolov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

set_time_limit(500);

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot .'/course/lib.php');
require_once($CFG->libdir .'/enrollib.php');
require_once($CFG->dirroot . '/local/course_checker/locallib.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

if (!is_siteadmin($USER->id)) {
    die();
}

admin_externalpage_setup('local_course_checker_fetch', '');

echo $OUTPUT->header();

check_courses();

echo $OUTPUT->footer();
