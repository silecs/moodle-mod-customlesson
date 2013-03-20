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
 * @package moodlecore
 * @subpackage backup-moodle2
 * @copyright  2012 Silecs et Institut Telecom
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/customlesson/backup/moodle2/restore_customlesson_stepslib.php'); // Because it exists (must)

/**
 * lesson restore task that provides all the settings and steps to perform one
 * complete restore of the activity
 */
class restore_customlesson_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // lesson only has one structure step
        $this->add_step(new restore_customlesson_activity_structure_step('customlesson_structure', 'customlesson.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    static public function define_decode_contents() {
        $contents = array();

        $contents[] = new restore_decode_content('customlesson_pages', array('contents'), 'customlesson_page');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    static public function define_decode_rules() {
        $rules = array();

        $rules[] = new restore_decode_rule('CUSTOMLESSONEDIT', '/mod/customlesson/edit.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('CUSTOMLESSONESAY', '/mod/customlesson/essay.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('CUSTOMLESSONHIGHSCORES', '/mod/customlesson/highscores.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('CUSTOMLESSONREPORT', '/mod/customlesson/report.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('CUSTOMLESSONMEDIAFILE', '/mod/customlesson/mediafile.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('CUSTOMLESSONVIEWBYID', '/mod/customlesson/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('CUSTOMLESSONINDEX', '/mod/customlesson/index.php?id=$1', 'course');
        $rules[] = new restore_decode_rule('CUSTOMLESSONVIEWPAGE', '/mod/customlesson/view.php?id=$1&pageid=$2', array('course_module', 'customlesson_page'));
        $rules[] = new restore_decode_rule('CUSTOMLESSONEDITPAGE', '/mod/customlesson/edit.php?id=$1&pageid=$2', array('course_module', 'customlesson_page'));

        return $rules;

    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * customlesson logs. It must return one array
     * of {@link restore_log_rule} objects
     */
    static public function define_restore_log_rules() {
        $rules = array();

        $rules[] = new restore_log_rule('customlesson', 'add', 'view.php?id={course_module}', '{customlesson}');
        $rules[] = new restore_log_rule('customlesson', 'update', 'view.php?id={course_module}', '{customlesson}');
        $rules[] = new restore_log_rule('customlesson', 'view', 'view.php?id={course_module}', '{customlesson}');
        $rules[] = new restore_log_rule('customlesson', 'start', 'view.php?id={course_module}', '{customlesson}');
        $rules[] = new restore_log_rule('customlesson', 'end', 'view.php?id={course_module}', '{customlesson}');
        $rules[] = new restore_log_rule('customlesson', 'view grade', 'essay.php?id={course_module}', '[name]');
        $rules[] = new restore_log_rule('customlesson', 'update grade', 'essay.php?id={course_module}', '[name]');
        $rules[] = new restore_log_rule('customlesson', 'update email essay grade', 'essay.php?id={course_module}', '[name]');
        $rules[] = new restore_log_rule('customlesson', 'update highscores', 'highscores.php?id={course_module}', '[name]');
        $rules[] = new restore_log_rule('customlesson', 'view highscores', 'highscores.php?id={course_module}', '[name]');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * course logs. It must return one array
     * of {@link restore_log_rule} objects
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     */
    static public function define_restore_log_rules_for_course() {
        $rules = array();

        $rules[] = new restore_log_rule('customlesson', 'view all', 'index.php?id={course}', null);

        return $rules;
    }
}
