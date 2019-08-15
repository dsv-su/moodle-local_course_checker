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
 * Settings file for the local_course_checker.
 * 
 * @package   local_course_checker
 * @copyright 2019 Pavel Sokolov <pavel.m.sokolov@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_course_checker', new lang_string('pluginname', 'local_course_checker'));
    $ADMIN->add('localplugins', $settings);

    $ADMIN->add('courses', new admin_externalpage('local_course_checker_fetch',
            get_string('pluginmenuname', 'local_course_checker'),
            new moodle_url('/local/course_checker/index.php')));

    $settings->add(new admin_setting_heading('local_course_checker', '', get_string('local_course_checkerdescription', 'local_course_checker')));
    $settings->add(new admin_setting_configtext('local_course_checker/restapiurl', get_string('restapiurl', 'local_course_checker'), '', '')); 
    $settings->add(new admin_setting_configtext('local_course_checker/username', get_string('username'), '', ''));
    $settings->add(new admin_setting_configtext('local_course_checker/password', get_string('password'), '', ''));
    $settings->add(new admin_setting_configtext('local_course_checker/personresource', get_string('personresource', 'local_course_checker'), '', ''));
    $settings->add(new admin_setting_configtext('local_course_checker/coursesegmentresource', get_string('coursesegmentresource', 'local_course_checker'), '', ''));
    $settings->add(new admin_setting_configtext('local_course_checker/participantsresource', get_string('participantsresource', 'local_course_checker'), '', ''));
}
