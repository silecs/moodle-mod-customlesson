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
 * End of branch table
 *
 * @package    mod
 * @subpackage customlesson
 * @copyright  2012 Silecs et Institut Mines-Télécom
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

defined('MOODLE_INTERNAL') || die();

 /** End of Branch page */
define("CUSTOMLESSON_PAGE_ENDOFBRANCH",   "21");

class customlesson_page_type_endofbranch extends customlesson_page {

    protected $type = customlesson_page::TYPE_STRUCTURE;
    protected $typeidstring = 'endofbranch';
    protected $typeid = CUSTOMLESSON_PAGE_ENDOFBRANCH;
    protected $string = null;
    protected $jumpto = null;

    public function display($renderer, $attempt) {
        return '';
    }
    public function get_typeid() {
        return $this->typeid;
    }
    public function get_typestring() {
        if ($this->string===null) {
            $this->string = get_string($this->typeidstring, 'customlesson');
        }
        return $this->string;
    }
    public function get_idstring() {
        return $this->typeidstring;
    }
    public function callback_on_view($canmanage) {
        $this->redirect_to_first_answer($canmanage);
        exit;
    }

    public function redirect_to_first_answer($canmanage) {
        global $USER, $PAGE;
        $answer = array_shift($this->get_answers());
        $jumpto = $answer->jumpto;
        if ($jumpto == CUSTOMLESSON_RANDOMBRANCH) {

            $jumpto = customlesson_unseen_branch_jump($this->lesson, $USER->id);

        } elseif ($jumpto == CUSTOMLESSON_CLUSTERJUMP) {

            if (!$canmanage) {
                $jumpto = $this->lesson->cluster_jump($this->properties->id);
            } else {
                if ($this->properties->nextpageid == 0) {
                    $jumpto = CUSTOMLESSON_EOL;
                } else {
                    $jumpto = $this->properties->nextpageid;
                }
            }

        } else if ($answer->jumpto == CUSTOMLESSON_NEXTPAGE) {

            if ($this->properties->nextpageid == 0) {
                $jumpto = CUSTOMLESSON_EOL;
            } else {
                $jumpto = $this->properties->nextpageid;
            }

        } else if ($jumpto == 0) {

            $jumpto = $this->properties->id;

        } else if ($jumpto == CUSTOMLESSON_PREVIOUSPAGE) {

            $jumpto = $this->properties->prevpageid;

        }
        redirect(new moodle_url('/mod/customlesson/view.php', array('id'=>$PAGE->cm->id,'pageid'=>$jumpto)));
    }
    public function get_grayout() {
        return 1;
    }
    public function update($properties, $context = null, $maxbytes = null) {
        global $DB, $PAGE;

        $properties->id = $this->properties->id;
        $properties->lessonid = $this->lesson->id;
        if (empty($properties->qoption)) {
            $properties->qoption = '0';
        }
        $properties = file_postupdate_standard_editor($properties, 'contents', array('noclean'=>true, 'maxfiles'=>EDITOR_UNLIMITED_FILES, 'maxbytes'=>$PAGE->course->maxbytes), context_module::instance($PAGE->cm->id), 'mod_customlesson', 'page_contents', $properties->id);
        $DB->update_record("customlesson_pages", $properties);

        $answers  = $this->get_answers();
        if (count($answers)>1) {
            $answer = array_shift($answers);
            foreach ($answers as $a) {
                $DB->delete_record('customlesson_answers', array('id'=>$a->id));
            }
        } else if (count($answers)==1) {
            $answer = array_shift($answers);
        } else {
            $answer = new stdClass;
        }

        $answer->timemodified = time();;
        if (isset($properties->jumpto[0])) {
            $answer->jumpto = $properties->jumpto[0];
        }
        if (isset($properties->score[0])) {
            $answer->score = $properties->score[0];
        }
        if (!empty($answer->id)) {
            $DB->update_record("customlesson_answers", $answer->properties());
        } else {
            $DB->insert_record("customlesson_answers", $answer);
        }
        return true;
    }
    public function add_page_link($previd) {
        global $PAGE, $CFG;
        if ($previd != 0) {
            $addurl = new moodle_url('/mod/customlesson/editpage.php', array('id'=>$PAGE->cm->id, 'pageid'=>$previd, 'sesskey'=>sesskey(), 'qtype'=>CUSTOMLESSON_PAGE_ENDOFBRANCH));
            return array('addurl'=>$addurl, 'type'=>CUSTOMLESSON_PAGE_ENDOFBRANCH, 'name'=>get_string('addanendofbranch', 'customlesson'));
        }
        return false;
    }
    public function valid_page_and_view(&$validpages, &$pageviews) {
        return $this->properties->nextpageid;
    }
}

class customlesson_add_page_form_endofbranch extends customlesson_add_page_form_base {

    public $qtype = CUSTOMLESSON_PAGE_ENDOFBRANCH;
    public $qtypestring = 'endofbranch';
    protected $standard = false;

    public function custom_definition() {
        global $PAGE;

        $mform = $this->_form;
        $lesson = $this->_customdata['lesson'];
        $jumptooptions = customlesson_page_type_branchtable::get_jumptooptions(optional_param('firstpage', false, PARAM_BOOL), $lesson);

        $mform->addElement('hidden', 'firstpage');
        $mform->setType('firstpage', PARAM_BOOL);

        $mform->addElement('hidden', 'qtype');
        $mform->setType('qtype', PARAM_TEXT);

        $mform->addElement('text', 'title', get_string("pagetitle", "customlesson"), array('size'=>70));
        $mform->setType('title', PARAM_TEXT);

        $this->editoroptions = array('noclean'=>true, 'maxfiles'=>EDITOR_UNLIMITED_FILES, 'maxbytes'=>$PAGE->course->maxbytes);
        $mform->addElement('editor', 'contents_editor', get_string("pagecontents", "customlesson"), null, $this->editoroptions);
        $mform->setType('contents_editor', PARAM_RAW);

        $this->add_jumpto(0);
    }

    public function construction_override($pageid, customlesson $lesson) {
        global $DB, $CFG, $PAGE;
        require_sesskey();

        // first get the preceeding page

        $timenow = time();

        // the new page is not the first page (end of branch always comes after an existing page)
        if (!$page = $DB->get_record("customlesson_pages", array("id" => $pageid))) {
            print_error('cannotfindpagerecord', 'customlesson');
        }
        // chain back up to find the (nearest branch table)
        $btpage = clone($page);
        $btpageid = $btpage->id;
        while (($btpage->qtype != CUSTOMLESSON_PAGE_BRANCHTABLE) && ($btpage->prevpageid > 0)) {
            $btpageid = $btpage->prevpageid;
            if (!$btpage = $DB->get_record("customlesson_pages", array("id" => $btpageid))) {
                print_error('cannotfindpagerecord', 'customlesson');
            }
        }

        if ($btpage->qtype == CUSTOMLESSON_PAGE_BRANCHTABLE) {
            $newpage = new stdClass;
            $newpage->lessonid = $lesson->id;
            $newpage->prevpageid = $pageid;
            $newpage->nextpageid = $page->nextpageid;
            $newpage->qtype = $this->qtype;
            $newpage->timecreated = $timenow;
            $newpage->title = get_string("endofbranch", "customlesson");
            $newpage->contents = get_string("endofbranch", "customlesson");
            $newpageid = $DB->insert_record("customlesson_pages", $newpage);
            // update the linked list...
            $DB->set_field("customlesson_pages", "nextpageid", $newpageid, array("id" => $pageid));
            if ($page->nextpageid) {
                // the new page is not the last page
                $DB->set_field("customlesson_pages", "prevpageid", $newpageid, array("id" => $page->nextpageid));
            }
            // ..and the single "answer"
            $newanswer = new stdClass;
            $newanswer->lessonid = $lesson->id;
            $newanswer->pageid = $newpageid;
            $newanswer->timecreated = $timenow;
            $newanswer->jumpto = $btpageid;
            $newanswerid = $DB->insert_record("customlesson_answers", $newanswer);
            $lesson->add_message(get_string('addedanendofbranch', 'customlesson'), 'notifysuccess');
        } else {
            $lesson->add_message(get_string('nobranchtablefound', 'customlesson'));
        }

        redirect($CFG->wwwroot."/mod/customlesson/edit.php?id=".$PAGE->cm->id);
    }
}