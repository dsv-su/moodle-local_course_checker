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
 * Course checker functions.
 *
 * @package   local_course_checker
 * @copyright 2019 Pavel Sokolov <pavel.m.sokolov@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function api_call($url) {
    $username = get_config('local_course_checker', 'username');
    $password = get_config('local_course_checker', 'password');
    $apiurl = get_config('local_course_checker', 'restapiurl');

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERPWD, $username.':'.$password);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
    curl_setopt($ch, CURLOPT_URL, $apiurl.$url);
    $contents = curl_exec($ch);
    $headers  = curl_getinfo($ch);
    curl_close($ch);
    if ($headers['http_code'] == 200) {
        if (isset($contents)) {
            $result = json_decode($contents);
            if (isset($result)) {
               return($result);
            }
        }
    }
}

function extract_shortname($role) {
    global $DB;
    return $role->shortname;
}

function check_courses() {
    global $DB, $OUTPUT;

    $select = "name LIKE '%VT20%' or name LIKE '%HT20%'";
    $terms = $DB->get_records_select('course_categories', $select, null, 'id DESC');
    if (!isset($_POST['term'])) {
        $_POST['term'] = array_values($terms)[0]->id;
    }

    echo "<form name='add' method='post'>Select term: <select name='term'>";
    foreach ($terms as $key => $term) {
        $selected = '';
        if ($_POST['term'] == $term->id) { $selected = 'selected'; }
        echo "<option value='$term->id' $selected>$term->name</option>";
    }
    echo "<input type='submit' name='submit'/></form>";

    $category = $DB->get_record('course_categories', array('id' => $_POST['term']));
    if (strpos($category->name, 'VT') !== false) {
        $semester = substr($category->name, 2).'1';
    } else {
        $semester = substr($category->name, 2).'2';
    }

    $coursesegmentresource = get_config('local_course_checker', 'coursesegmentresource');
    $courses = api_call($coursesegmentresource . '?semester='.$semester);
  
    // Sort by the start date ASC, then by idnumber ASC.
    usort($courses, function($a, $b) {
        if ($a->startDate == $b->startDate) {
            return $a->id - $b->id;
        } else {
            return ($a->startDate > $b->startDate) ? 1 : -1;;
        }
    });

    echo $OUTPUT->heading($category->name . ' category courses overview');

    $table = new html_table();
    $table->head = array('Course code', 'Course ID', 'Responsible', 'Teacher', 'REST enrolled', 'Start', 'End');

    foreach ($courses as $course) {
        $row = array();
        $course_ilearn = null;

        if (is_numeric($course->id)) {
            if ($DB->record_exists('course', array('idnumber' => $course->id))) {
                $course_ilearn = $DB->get_record('course', array('idnumber' => $course->id));
            } else {
                // try to find by the name schema
                $course_ilearn = $DB->get_record('course', array('shortname' => $course->designation.' '.$category->name));
            }
        }

        $code = $course->designation;

        if (!$course_ilearn) {
            $row = array($code, $OUTPUT->error_text('Missing iLearn2 instance!'), '', '', '',
                date("Y-m-d H:i", strtotime($course->startDate)),
                date("Y-m-d H:i", strtotime($course->endDate)));
        } else {
            $courseurl = course_get_url($course_ilearn->id);
            // List course responsibles and teachers
            $responsibles = array();
            $teachers = array();
            foreach ($course->contributors as $contributor) {
                $personresource = get_config('local_course_checker', 'personresource');
                $c = api_call($personresource.'/'.$contributor->personId);
                $u = $DB->get_record('user', array('firstname' => $c->firstName, 'lastname' => $c->lastName, 'deleted' => 0, 'idnumber' => $contributor->personId));
                if ($u) {
                    $roleshortnames = array_map('extract_shortname', get_user_roles(context_course::instance($course_ilearn->id), $u->id));
                }
                if ($contributor->responsible) {
                    if ($u && array_search('manager', $roleshortnames) !== false) {
                        $responsibles[]=$c->firstName . ' ' .$c->lastName . ' ' .
                            $OUTPUT->pix_icon('t/approve', '');
                    } elseif ($u && array_search('editingteacher', $roleshortnames) !== false) {
                        $responsibles[]=$c->firstName . ' ' .$c->lastName . ' (teacher) ' .
                            $OUTPUT->pix_icon('i/completion-auto-enabled', '');
                    } elseif ($u && array_search('teacher', $roleshortnames) !== false) {
                        $responsibles[]=$c->firstName . ' ' .$c->lastName . ' (non-editing teacher) ' .
                            $OUTPUT->pix_icon('i/completion-auto-enabled', '');
                    } else {
                        $responsibles[]=$c->firstName . ' ' .$c->lastName . ' ' .
                            $OUTPUT->pix_icon('t/delete', '');
                    }
                } else {
                    if ($u && array_search('editingteacher', $roleshortnames) !== false) {
                        $teachers[]=$c->firstName . ' ' .$c->lastName . ' ' .
                            $OUTPUT->pix_icon('t/approve', '');
                    } elseif ($u && array_search('teacher', $roleshortnames) !== false) {
                        $teachers[]=$c->firstName . ' ' .$c->lastName . ' (non-editing teacher) ' .
                            $OUTPUT->pix_icon('i/completion-auto-enabled', '');
                    } else {
                        $teachers[]=$c->firstName . ' ' .$c->lastName . ' ' .
                            $OUTPUT->pix_icon('t/delete', '');
                    }
                }
            }

            // Compare course start date
            $d1 = date("Y-m-d H:i", $course_ilearn->startdate);
            if ($course_ilearn->startdate <> strtotime($course->startDate)) {
                $d2 = date("Y-m-d H:i", strtotime($course->startDate));
                $startdate = "ilearn2: $d1, <br/> daisy: $d2";
            } else {
                $startdate = "$d1 ".$OUTPUT->pix_icon('t/approve', '');
            }

            // Update course enddate when it's empty
            if ($course_ilearn->enddate == 0) {
                $course_ilearn->enddate = strtotime($course->endDate);
                update_course($course_ilearn);
            }

            // Compare course end date
            $d1 = $course_ilearn->enddate>0 ? date("Y-m-d H:i", $course_ilearn->enddate): 'none';
            if ($course_ilearn->enddate <> strtotime($course->endDate)) {
                $d2 = date("Y-m-d H:i", strtotime($course->endDate));
                $enddate = "ilearn2: $d1, <br/> daisy: $d2";
            } else {
                $enddate = "$d1 ".$OUTPUT->pix_icon('t/approve', '');
            }

            // Compare participants
            $coursesegmentresource = get_config('local_course_checker', 'coursesegmentresource');
            $participantsresource = get_config('local_course_checker', 'participantsresource');
            $participants = count(api_call($coursesegmentresource.'/'.$course->id.'/'.$participantsresource));
            $enrolid = $DB->get_record('enrol', array('courseid' => $course_ilearn->id, 'enrol' => 'rest'))->id;
            $enrolled = $DB->count_records('user_enrolments', array('enrolid' => $enrolid));

            $row = array(
                html_writer::link($courseurl, $code, array('target' => '_blank')),
                $course->id,
                implode('<br>', $responsibles),
                implode('<br>', $teachers),
                $participants == $enrolled ? $participants . ' ' . $OUTPUT->pix_icon('t/approve', '') : "ilearn2: $enrolled, <br/> daisy: $participants",
                $startdate,
                $enddate,
            );
        }

        $table->data[] = $row;
    }

    echo html_writer::table($table);

}


