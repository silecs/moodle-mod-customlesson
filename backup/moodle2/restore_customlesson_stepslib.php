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
 * @copyright  2012 Silecs et Institut Mines-Télécom
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_customlesson_activity_task
 */

/**
 * Structure step to restore one lesson activity
 */
class restore_customlesson_activity_structure_step extends restore_activity_structure_step {
    // Store the answers as they're received but only process them at the
    // end of the lesson
    protected $answers = array();

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('customlesson', '/activity/customlesson');
        $paths[] = new restore_path_element('customlesson_page', '/activity/customlesson/pages/page');
        $paths[] = new restore_path_element('customlesson_answer', '/activity/customlesson/pages/page/answers/answer');
        if ($userinfo) {
            $paths[] = new restore_path_element('customlesson_attempt', '/activity/customlesson/pages/page/answers/answer/attempts/attempt');
            $paths[] = new restore_path_element('customlesson_grade', '/activity/customlesson/grades/grade');
            $paths[] = new restore_path_element('customlesson_key', '/activity/customlesson/keys/key');
            $paths[] = new restore_path_element('customlesson_branch', '/activity/customlesson/pages/page/branches/branch');
            $paths[] = new restore_path_element('customlesson_highscore', '/activity/customlesson/highscores/highscore');
            $paths[] = new restore_path_element('customlesson_timer', '/activity/customlesson/timers/timer');
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_customlesson($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->available = $this->apply_date_offset($data->available);
        $data->deadline = $this->apply_date_offset($data->deadline);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // lesson->highscores can come both in data->highscores and
        // data->showhighscores, handle both. MDL-26229
        if (isset($data->showhighscores)) {
            $data->highscores = $data->showhighscores;
            unset($data->showhighscores);
        }

        // insert the customlesson record
        $newitemid = $DB->insert_record('customlesson', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

    protected function process_customlesson_page($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->lessonid = $this->get_new_parentid('customlesson');

        // We'll remap all the prevpageid and nextpageid at the end, once all pages have been created
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        $data->timecreated = $this->apply_date_offset($data->timecreated);

        $newitemid = $DB->insert_record('customlesson_pages', $data);
        $this->set_mapping('customlesson_page', $oldid, $newitemid, true); // Has related fileareas
    }

    protected function process_customlesson_answer($data) {
        global $DB;

        $data = (object)$data;
        $data->lessonid = $this->get_new_parentid('customlesson');
        $data->pageid = $this->get_new_parentid('customlesson_page');
        $data->answer = $data->answer_text;
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        $data->timecreated = $this->apply_date_offset($data->timecreated);

        // Set a dummy mapping to get the old ID so that it can be used by get_old_parentid when
        // processing attempts. It will be corrected in after_execute
        $this->set_mapping('customlesson_answer', $data->id, 0);

        // Answers need to be processed in order, so we store them in an
        // instance variable and insert them in the after_execute stage
        $this->answers[$data->id] = $data;
    }

    protected function process_customlesson_attempt($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->lessonid = $this->get_new_parentid('customlesson');
        $data->pageid = $this->get_new_parentid('customlesson_page');

        // We use the old answerid here as the answer isn't created until after_execute
        $data->answerid = $this->get_old_parentid('customlesson_answer');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->timeseen = $this->apply_date_offset($data->timeseen);

        $newitemid = $DB->insert_record('customlesson_attempts', $data);
    }

    protected function process_customlesson_grade($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->lessonid = $this->get_new_parentid('customlesson');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->completed = $this->apply_date_offset($data->completed);

        $newitemid = $DB->insert_record('customlesson_grades', $data);
        $this->set_mapping('customlesson_grade', $oldid, $newitemid);
    }

    protected function process_customlesson_key($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->lessonid = $this->get_new_parentid('customlesson');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('customlesson_keys', $data);
        $this->set_mapping('customlesson_key', $oldid, $newitemid);
    }

    protected function process_customlesson_branch($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->lessonid = $this->get_new_parentid('customlesson');
        $data->pageid = $this->get_new_parentid('customlesson_page');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->timeseen = $this->apply_date_offset($data->timeseen);

        $newitemid = $DB->insert_record('customlesson_branch', $data);
    }

    protected function process_customlesson_highscore($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->lessonid = $this->get_new_parentid('customlesson');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->gradeid = $this->get_mappingid('customlesson_grade', $data->gradeid);

        $newitemid = $DB->insert_record('customlesson_high_scores', $data);
    }

    protected function process_customlesson_timer($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->lessonid = $this->get_new_parentid('customlesson');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->starttime = $this->apply_date_offset($data->starttime);
        $data->lessontime = $this->apply_date_offset($data->lessontime);

        $newitemid = $DB->insert_record('customlesson_timer', $data);
    }

    protected function after_execute() {
        global $DB;

        // Answers must be sorted by id to ensure that they're shown correctly
        ksort($this->answers);
        foreach ($this->answers as $answer) {
            $newitemid = $DB->insert_record('customlesson_answers', $answer);
            $this->set_mapping('customlesson_answer', $answer->id, $newitemid);

            // Update the customlesson attempts to use the newly created answerid
            $DB->set_field('customlesson_attempts', 'answerid', $newitemid, array(
                    'lessonid' => $answer->lessonid,
                    'pageid' => $answer->pageid,
                    'answerid' => $answer->id));
        }

        // Add customlesson mediafile, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_customlesson', 'mediafile', null);
        // Add customlesson page files, by customlesson_page itemname
        $this->add_related_files('mod_customlesson', 'page_contents', 'customlesson_page');

        // Remap all the restored prevpageid and nextpageid now that we have all the pages and their mappings
        $rs = $DB->get_recordset('customlesson_pages', array('lessonid' => $this->task->get_activityid()),
                                 '', 'id, prevpageid, nextpageid');
        foreach ($rs as $page) {
            $page->prevpageid = (empty($page->prevpageid)) ? 0 : $this->get_mappingid('customlesson_page', $page->prevpageid);
            $page->nextpageid = (empty($page->nextpageid)) ? 0 : $this->get_mappingid('customlesson_page', $page->nextpageid);
            $DB->update_record('customlesson_pages', $page);
        }
        $rs->close();

        // Remap all the restored 'jumpto' fields now that we have all the pages and their mappings
        $rs = $DB->get_recordset('customlesson_answers', array('lessonid' => $this->task->get_activityid()),
                                 '', 'id, jumpto');
        foreach ($rs as $answer) {
            if ($answer->jumpto > 0) {
                $answer->jumpto = $this->get_mappingid('customlesson_page', $answer->jumpto);
                $DB->update_record('customlesson_answers', $answer);
            }
        }
        $rs->close();

        // Re-map the dependency and activitylink information
        // If a depency or activitylink has no mapping in the backup data then it could either be a duplication of a
        // lesson, or a backup/restore of a single lesson. We have no way to determine which and whether this is the
        // same site and/or course. Therefore we try and retrieve a mapping, but fallback to the original value if one
        // was not found. We then test to see whether the value found is valid for the course being restored into.
        $lesson = $DB->get_record('customlesson', array('id' => $this->task->get_activityid()), 'id, course, dependency, activitylink');
        $updaterequired = false;
        if (!empty($lesson->dependency)) {
            $updaterequired = true;
            $lesson->dependency = $this->get_mappingid('customlesson', $lesson->dependency, $lesson->dependency);
            if (!$DB->record_exists('customlesson', array('id' => $lesson->dependency, 'course' => $lesson->course))) {
                $lesson->dependency = 0;
            }
        }

        if (!empty($lesson->activitylink)) {
            $updaterequired = true;
            $lesson->activitylink = $this->get_mappingid('course_module', $lesson->activitylink, $lesson->activitylink);
            if (!$DB->record_exists('course_modules', array('id' => $lesson->activitylink, 'course' => $lesson->course))) {
                $lesson->activitylink = 0;
            }
        }

        if ($updaterequired) {
            $DB->update_record('customlesson', $lesson);
        }
    }
}
