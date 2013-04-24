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
 * Cluster
 *
 * @package    mod
 * @subpackage customlesson
 * @copyright  2009 Sam Hemelryk
 * @notice     customlesson is heavily based on the official lesson module and large portions of code are copied from there.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

defined('MOODLE_INTERNAL') || die();

 /** Start of Cluster page */
define("CUSTOMLESSON_PAGE_CLUSTER",   "30");

class customlesson_page_type_cluster extends customlesson_page {

    protected $type = customlesson_page::TYPE_STRUCTURE;
    protected $typeidstring = 'cluster';
    protected $typeid = CUSTOMLESSON_PAGE_CLUSTER;
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
    public function get_grayout() {
        return 1;
    }
    public function callback_on_view($canmanage) {
        global $USER;
        if (!$canmanage) {
            // Get the next page in the lesson cluster jump
            return $this->lesson->cluster_jump($this->properties->id);
        } else {
            // get the next page
            return $this->properties->nextpageid;
        }
    }
    public function override_next_page() {
        global $USER;
        return $this->lesson->cluster_jump($this->properties->id);
    }
    public function add_page_link($previd) {
        global $PAGE, $CFG;
        $addurl = new moodle_url('/mod/customlesson/editpage.php', array('id'=>$PAGE->cm->id, 'pageid'=>$previd, 'sesskey'=>sesskey(), 'qtype'=>CUSTOMLESSON_PAGE_CLUSTER));
        return array('addurl'=>$addurl, 'type'=>CUSTOMLESSON_PAGE_CLUSTER, 'name'=>get_string('addcluster', 'customlesson'));
    }
    public function valid_page_and_view(&$validpages, &$pageviews) {
        $validpages[$this->properties->id] = 1;  // add the cluster page as a valid page
        foreach ($this->lesson->get_sub_pages_of($this->properties->id, array(CUSTOMLESSON_PAGE_ENDOFCLUSTER)) as $subpage) {
            if (in_array($subpage->id, $pageviews)) {
                unset($pageviews[array_search($subpage->id, $pageviews)]);  // remove it
                // since the user did see one page in the cluster, add the cluster pageid to the viewedpageids
                if (!in_array($this->properties->id, $pageviews)) {
                    $pageviews[] = $this->properties->id;
                }
            }
        }
        return $this->properties->nextpageid;
    }
}

class customlesson_add_page_form_cluster extends customlesson_add_page_form_base {

    public $qtype = CUSTOMLESSON_PAGE_CLUSTER;
    public $qtypestring = 'cluster';
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
        global $PAGE, $CFG, $DB;
        require_sesskey();

        $timenow = time();

        if ($pageid == 0) {
            if ($lesson->has_pages()) {
                if (!$page = $DB->get_record("customlesson_pages", array("prevpageid" => 0, "lessonid" => $lesson->id))) {
                    print_error('cannotfindpagerecord', 'customlesson');
                }
            } else {
                // This is the ONLY page
                $page = new stdClass;
                $page->id = 0;
            }
        } else {
            if (!$page = $DB->get_record("customlesson_pages", array("id" => $pageid))) {
                print_error('cannotfindpagerecord', 'customlesson');
            }
        }
        $newpage = new stdClass;
        $newpage->lessonid = $lesson->id;
        $newpage->prevpageid = $pageid;
        if ($pageid != 0) {
            $newpage->nextpageid = $page->nextpageid;
        } else {
            $newpage->nextpageid = $page->id;
        }
        $newpage->qtype = $this->qtype;
        $newpage->timecreated = $timenow;
        $newpage->title = get_string("clustertitle", "customlesson");
        $newpage->contents = get_string("clustertitle", "customlesson");
        $newpageid = $DB->insert_record("customlesson_pages", $newpage);
        // update the linked list...
        if ($pageid != 0) {
            $DB->set_field("customlesson_pages", "nextpageid", $newpageid, array("id" => $pageid));
        }

        if ($pageid == 0) {
            $page->nextpageid = $page->id;
        }
        if ($page->nextpageid) {
            // the new page is not the last page
            $DB->set_field("customlesson_pages", "prevpageid", $newpageid, array("id" => $page->nextpageid));
        }
        // ..and the single "answer"
        $newanswer = new stdClass;
        $newanswer->lessonid = $lesson->id;
        $newanswer->pageid = $newpageid;
        $newanswer->timecreated = $timenow;
        $newanswer->jumpto = CUSTOMLESSON_CLUSTERJUMP;
        $newanswerid = $DB->insert_record("customlesson_answers", $newanswer);
        $lesson->add_message(get_string('addedcluster', 'customlesson'), 'notifysuccess');
        redirect($CFG->wwwroot.'/mod/customlesson/edit.php?id='.$PAGE->cm->id);
    }
}