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
 * Redirects the user to either a lesson or to the lesson statistics
 *
 * @package   mod_customlesson
 * @category  grade
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @copyright  2012-2013 Silecs et Institut Mines-Télécom
 * @notice     customlesson is heavily based on the official lesson module and large portions of code are copied from there.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

/**
 * Require config.php
 */
require_once("../../config.php");
require_once($CFG->dirroot.'/mod/customlesson/locallib.php');

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('customlesson', $id, 0, false, MUST_EXIST);;
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$lesson = new customlesson($DB->get_record('customlesson', array('id' => $cm->instance), '*', MUST_EXIST));

require_login($course, false, $cm);

$PAGE->set_url('/mod/customlesson/grade.php', array('id'=>$cm->id));

if (has_capability('mod/customlesson:edit', context_module::instance($cm->id))) {
    redirect('report.php?id='.$cm->id);
} else {
    redirect('view.php?id='.$cm->id);
}
