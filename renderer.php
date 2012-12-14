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
 * Moodle renderer used to display special elements of the lesson module
 *
 * @package    mod
 * @subpackage customlesson
 * @copyright  2012 Silecs et Institut Telecom
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

defined('MOODLE_INTERNAL') || die();

class mod_customlesson_renderer extends plugin_renderer_base {
    /**
     * Returns the header for the lesson module
     *
     * @param lesson $lesson a lesson object.
     * @param string $currenttab current tab that is shown.
     * @param bool   $extraeditbuttons if extra edit buttons should be displayed.
     * @param int    $lessonpageid id of the lesson page that needs to be displayed.
     * @param string $extrapagetitle String to appent to the page title.
     * @return string
     */
    public function header($lesson, $cm, $currenttab = '', $extraeditbuttons = false, $lessonpageid = null, $extrapagetitle = null) {
        global $CFG;

        $activityname = format_string($lesson->name, true, $lesson->course);
        if (empty($extrapagetitle)) {
            $title = $this->page->course->shortname.": ".$activityname;
        } else {
            $title = $this->page->course->shortname.": ".$activityname.": ".$extrapagetitle;
        }

        // Build the buttons
        $context = context_module::instance($cm->id);

    /// Header setup
        $this->page->set_title($title);
        $this->page->set_heading($this->page->course->fullname);
        lesson_add_header_buttons($cm, $context, $extraeditbuttons, $lessonpageid);
        $output = $this->output->header();

        if (has_capability('mod/customlesson:manage', $context)) {
            $output .= $this->output->heading_with_help($activityname, 'overview', 'lesson');

            if (!empty($currenttab)) {
                ob_start();
                include($CFG->dirroot.'/mod/customlesson/tabs.php');
                $output .= ob_get_contents();
                ob_end_clean();
            }
        } else {
            $output .= $this->output->heading($activityname);
        }

        foreach ($lesson->messages as $message) {
            $output .= $this->output->notification($message[0], $message[1], $message[2]);
        }

        return $output;
    }

    /**
     * Returns the footer
     * @return string
     */
    public function footer() {
        return $this->output->footer();
    }

    /**
     * Returns HTML for a lesson inaccessible message
     *
     * @param string $message
     * @return <type>
     */
    public function lesson_inaccessible($message) {
        global $CFG;
        $output  =  $this->output->box_start('generalbox boxaligncenter');
        $output .=  $this->output->box_start('center');
        $output .=  $message;
        $output .=  $this->output->box('<a href="'.$CFG->wwwroot.'/course/view.php?id='. $this->page->course->id .'">'. get_string('returnto', 'customlesson', format_string($this->page->course->fullname, true)) .'</a>', 'lessonbutton standardbutton');
        $output .=  $this->output->box_end();
        $output .=  $this->output->box_end();
        return $output;
    }

    /**
     * Returns HTML to prompt the user to log in
     * @param lesson $lesson
     * @param bool $failedattempt
     * @return string
     */
    public function login_prompt(lesson $lesson, $failedattempt = false) {
        global $CFG;
        $output  = $this->output->box_start('password-form');
        $output .= $this->output->box_start('generalbox boxaligncenter');
        $output .=  '<form id="password" method="post" action="'.$CFG->wwwroot.'/mod/customlesson/view.php" autocomplete="off">';
        $output .=  '<fieldset class="invisiblefieldset center">';
        $output .=  '<input type="hidden" name="id" value="'. $this->page->cm->id .'" />';
        if ($failedattempt) {
            $output .=  $this->output->notification(get_string('loginfail', 'customlesson'));
        }
        $output .= get_string('passwordprotectedlesson', 'customlesson', format_string($lesson->name)).'<br /><br />';
        $output .= get_string('enterpassword', 'customlesson')." <input type=\"password\" name=\"userpassword\" /><br /><br />";
        $output .= "<div class='lessonbutton standardbutton submitbutton'><input type='submit' value='".get_string('continue', 'customlesson')."' /></div>";
        $output .= " <div class='lessonbutton standardbutton submitbutton'><input type='submit' name='backtocourse' value='".get_string('cancel', 'customlesson')."' /></div>";
        $output .=  '</fieldset></form>';
        $output .=  $this->output->box_end();
        $output .=  $this->output->box_end();
        return $output;
    }

    /**
     * Returns HTML to display dependancy errors
     *
     * @param object $dependentlesson
     * @param array $errors
     * @return string
     */
    public function dependancy_errors($dependentlesson, $errors) {
        $output  = $this->output->box_start('generalbox boxaligncenter');
        $output .= get_string('completethefollowingconditions', 'customlesson', $dependentlesson->name);
        $output .= $this->output->box(implode('<br />'.get_string('and', 'customlesson').'<br />', $errors),'center');
        $output .= $this->output->box_end();
        return $output;
    }

    /**
     * Returns HTML to display a message
     * @param string $message
     * @param single_button $button
     * @return string
     */
    public function message($message, single_button $button = null) {
        $output  = $this->output->box_start('generalbox boxaligncenter');
        $output .= $message;
        if ($button !== null) {
            $output .= $this->output->box($this->output->render($button), 'lessonbutton standardbutton');
        }
        $output .= $this->output->box_end();
        return $output;
    }

    /**
     * Returns HTML to display a continue button
     * @param lesson $lesson
     * @param int $lastpageseen
     * @return string
     */
    public function continue_links(lesson $lesson, $lastpageseenid) {
        global $CFG;
        $output = $this->output->box(get_string('youhaveseen','lesson'), 'generalbox boxaligncenter');
        $output .= $this->output->box_start('center');

        $yeslink = html_writer::link(new moodle_url('/mod/customlesson/view.php', array('id'=>$this->page->cm->id, 'pageid'=>$lastpageseenid, 'startlastseen'=>'yes')), get_string('yes'));
        $output .= html_writer::tag('span', $yeslink, array('class'=>'lessonbutton standardbutton'));
        $output .= '&nbsp;';

        $nolink = html_writer::link(new moodle_url('/mod/customlesson/view.php', array('id'=>$this->page->cm->id, 'pageid'=>$lesson->firstpageid, 'startlastseen'=>'no')), get_string('no'));
        $output .= html_writer::tag('span', $nolink, array('class'=>'lessonbutton standardbutton'));

        $output .= $this->output->box_end();
        return $output;
    }

    /**
     * Returns HTML to display a page to the user
     * @param lesson $lesson
     * @param lesson_page $page
     * @param object $attempt
     * @return string
     */
    public function display_page(lesson $lesson, lesson_page $page, $attempt) {
        // We need to buffer here as there is an mforms display call
        ob_start();
        echo $page->display($this, $attempt);
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }

    /**
     * Returns HTML to display a collapsed edit form
     *
     * @param lesson $lesson
     * @param int $pageid
     * @return string
     */
    public function display_edit_collapsed(lesson $lesson, $pageid) {
        global $DB, $CFG;

        $manager = lesson_page_type_manager::get($lesson);
        $qtypes = $manager->get_page_type_strings();
        $npages = count($lesson->load_all_pages());

        $table = new html_table();
        $table->head = array(get_string('pagetitle', 'customlesson'), get_string('qtype', 'lesson'), get_string('jumps', 'lesson'), get_string('actions', 'lesson'));
        $table->align = array('left', 'left', 'left', 'center');
        $table->wrap = array('', 'nowrap', '', 'nowrap');
        $table->tablealign = 'center';
        $table->cellspacing = 0;
        $table->cellpadding = '2px';
        $table->width = '80%';
        $table->data = array();

        $canedit = has_capability('mod/customlesson:edit', context_module::instance($this->page->cm->id));

        while ($pageid != 0) {
            $page = $lesson->load_page($pageid);
            $data = array();
            $data[] = "<a href=\"$CFG->wwwroot/mod/customlesson/edit.php?id=".$this->page->cm->id."&amp;mode=single&amp;pageid=".$page->id."\">".format_string($page->title,true).'</a>';
            $data[] = $qtypes[$page->qtype];
            $data[] = implode("<br />\n", $page->jumps);
            if ($canedit) {
                $data[] = $this->page_action_links($page, $npages, true);
            } else {
                $data[] = '';
            }
            $table->data[] = $data;
            $pageid = $page->nextpageid;
        }

        return html_writer::table($table);
    }

    /**
     * Returns HTML to display the full edit page
     *
     * @param lesson $lesson
     * @param int $pageid
     * @param int $prevpageid
     * @param bool $single
     * @return string
     */
    public function display_edit_full(lesson $lesson, $pageid, $prevpageid, $single=false) {
        global $DB, $CFG;

        $manager = lesson_page_type_manager::get($lesson);
        $qtypes = $manager->get_page_type_strings();
        $npages = count($lesson->load_all_pages());
        $canedit = has_capability('mod/customlesson:edit', context_module::instance($this->page->cm->id));

        $content = '';
        if ($canedit) {
            $content = $this->add_page_links($lesson, $prevpageid);
        }

        $options = new stdClass;
        $options->noclean = true;

        while ($pageid != 0 && $single!=='stop') {
            $page = $lesson->load_page($pageid);

            $pagetable = new html_table();
            $pagetable->align = array('right','left');
            $pagetable->width = '100%';
            $pagetable->tablealign = 'center';
            $pagetable->cellspacing = 0;
            $pagetable->cellpadding = '5px';
            $pagetable->data = array();

            $pageheading = new html_table_cell();

            $pageheading->text = format_string($page->title);
            if ($canedit) {
                $pageheading->text .= ' '.$this->page_action_links($page, $npages);
            }
            $pageheading->style = 'text-align:center';
            $pageheading->colspan = 2;
            $pageheading->scope = 'col';
            $pagetable->head = array($pageheading);

            $cell = new html_table_cell();
            $cell->colspan = 2;
            $cell->style = 'text-align:left';
            $cell->text = $page->contents;
            $pagetable->data[] = new html_table_row(array($cell));

            $cell = new html_table_cell();
            $cell->colspan = 2;
            $cell->style = 'text-align:center';
            $cell->text = '<strong>'.$qtypes[$page->qtype] . $page->option_description_string().'</strong>';
            $pagetable->data[] = new html_table_row(array($cell));

            $pagetable = $page->display_answers($pagetable);

            $content .= html_writer::table($pagetable);

            if ($canedit) {
                $content .= $this->add_page_links($lesson, $pageid);
            }

            // check the prev links - fix (silently) if necessary - there was a bug in
            // versions 1 and 2 when add new pages. Not serious then as the backwards
            // links were not used in those versions
            if ($page->prevpageid != $prevpageid) {
                // fix it
                $DB->set_field("customlesson_pages", "prevpageid", $prevpageid, array("id" => $page->id));
                debugging("<p>***prevpageid of page $page->id set to $prevpageid***");
            }

            $prevpageid = $page->id;
            $pageid = $page->nextpageid;

            if ($single === true) {
                $single = 'stop';
            }

        }

        return $this->output->box($content, 'edit_pages_box');
    }

    /**
     * Returns HTML to display the add page links
     *
     * @param lesson $lesson
     * @param int $prevpageid
     * @return string
     */
    public function add_page_links(lesson $lesson, $prevpageid=false) {
        global $CFG;

        $links = array();

        $importquestionsurl = new moodle_url('/mod/customlesson/import.php',array('id'=>$this->page->cm->id, 'pageid'=>$prevpageid));
        $links[] = html_writer::link($importquestionsurl, get_string('importquestions', 'customlesson'));

        $manager = lesson_page_type_manager::get($lesson);
        foreach($manager->get_add_page_type_links($prevpageid) as $link) {
            $links[] = html_writer::link($link['addurl'], $link['name']);
        }

        $addquestionurl = new moodle_url('/mod/customlesson/editpage.php', array('id'=>$this->page->cm->id, 'pageid'=>$prevpageid));
        $links[] = html_writer::link($addquestionurl, get_string('addaquestionpagehere', 'customlesson'));

        return $this->output->box(implode(" | \n", $links), 'addlinks');
    }

    /**
     * Return HTML to display add first page links
     * @param lesson $lesson
     * @return string
     */
    public function add_first_page_links(lesson $lesson) {
        global $CFG;
        $prevpageid = 0;

        $output = $this->output->heading(get_string("whatdofirst", "customlesson"), 3);
        $links = array();

        $importquestionsurl = new moodle_url('/mod/customlesson/import.php',array('id'=>$this->page->cm->id, 'pageid'=>$prevpageid));
        $links[] = html_writer::link($importquestionsurl, get_string('importquestions', 'customlesson'));

        $manager = lesson_page_type_manager::get($lesson);
        foreach ($manager->get_add_page_type_links($prevpageid) as $link) {
            $link['addurl']->param('firstpage', 1);
            $links[] = html_writer::link($link['addurl'], $link['name']);
        }

        $addquestionurl = new moodle_url('/mod/customlesson/editpage.php', array('id'=>$this->page->cm->id, 'pageid'=>$prevpageid, 'firstpage'=>1));
        $links[] = html_writer::link($addquestionurl, get_string('addaquestionpage', 'customlesson'));

        return $this->output->box($output.'<p>'.implode('</p><p>', $links).'</p>', 'generalbox firstpageoptions');
    }

    /**
     * Returns HTML to display action links for a page
     *
     * @param lesson_page $page
     * @param bool $printmove
     * @param bool $printaddpage
     * @return string
     */
    public function page_action_links(lesson_page $page, $printmove, $printaddpage=false) {
        global $CFG;

        $actions = array();

        if ($printmove) {
            $printmovehtml = new moodle_url('/mod/customlesson/lesson.php', array('id'=>$this->page->cm->id, 'action'=>'move', 'pageid'=>$page->id, 'sesskey'=>sesskey()));
            $actions[] = html_writer::link($printmovehtml, '<img src="'.$this->output->pix_url('t/move').'" class="iconsmall" alt="'.get_string('move').'" />');
        }
        $url = new moodle_url('/mod/customlesson/editpage.php', array('id'=>$this->page->cm->id, 'pageid'=>$page->id, 'edit'=>1));
        $actions[] = html_writer::link($url, '<img src="'.$this->output->pix_url('t/edit').'" class="iconsmall" alt="'.get_string('update').'" />');

        $url = new moodle_url('/mod/customlesson/view.php', array('id'=>$this->page->cm->id, 'pageid'=>$page->id));
        $actions[] = html_writer::link($url, '<img src="'.$this->output->pix_url('t/preview').'" class="iconsmall" alt="'.get_string('preview').'" />');

        $url = new moodle_url('/mod/customlesson/lesson.php', array('id'=>$this->page->cm->id, 'action'=>'confirmdelete', 'pageid'=>$page->id, 'sesskey'=>sesskey()));
        $actions[] = html_writer::link($url, '<img src="'.$this->output->pix_url('t/delete').'" class="iconsmall" alt="'.get_string('delete').'" />');

        if ($printaddpage) {
            $options = array();
            $manager = lesson_page_type_manager::get($page->lesson);
            $links = $manager->get_add_page_type_links($page->id);
            foreach ($links as $link) {
                $options[$link['type']] = $link['name'];
            }
            $options[0] = get_string('question', 'customlesson');

            $addpageurl = new moodle_url('/mod/customlesson/editpage.php', array('id'=>$this->page->cm->id, 'pageid'=>$page->id, 'sesskey'=>sesskey()));
            $addpageselect = new single_select($addpageurl, 'qtype', $options, null, array(''=>get_string('addanewpage', 'customlesson').'...'), 'addpageafter'.$page->id);
            $addpageselector = $this->output->render($addpageselect);
        }

        if (isset($addpageselector)) {
            $actions[] = $addpageselector;
        }

        return implode(' ', $actions);
    }

    /**
     * Prints the on going message to the user.
     *
     * With custom grading On, displays points
     * earned out of total points possible thus far.
     * With custom grading Off, displays number of correct
     * answers out of total attempted.
     *
     * @param object $lesson The lesson that the user is taking.
     * @return void
     **/

     /**
      * Prints the on going message to the user.
      *
      * With custom grading On, displays points
      * earned out of total points possible thus far.
      * With custom grading Off, displays number of correct
      * answers out of total attempted.
      *
      * @param lesson $lesson
      * @return string
      */
    public function ongoing_score(lesson $lesson) {
        global $USER, $DB;

        $context = context_module::instance($this->page->cm->id);
        if (has_capability('mod/customlesson:manage', $context)) {
            return $this->output->box(get_string('teacherongoingwarning', 'customlesson'), "ongoing center");
        } else {
            $ntries = $DB->count_records("customlesson_grades", array("lessonid"=>$lesson->id, "userid"=>$USER->id));
            if (isset($USER->modattempts[$lesson->id])) {
                $ntries--;
            }
            $gradeinfo = lesson_grade($lesson, $ntries);
            $a = new stdClass;
            if ($lesson->custom) {
                $a->score = $gradeinfo->earned;
                $a->currenthigh = $gradeinfo->total;
                return $this->output->box(get_string("ongoingcustom", "customlesson", $a), "ongoing center");
            } else {
                $a->correct = $gradeinfo->earned;
                $a->viewed = $gradeinfo->attempts;
                return $this->output->box(get_string("ongoingnormal", "customlesson", $a), "ongoing center");
            }
        }
    }

    /**
     * Returns HTML to display a progress bar of progression through a lesson
     *
     * @param lesson $lesson
     * @return string
     */
    public function progress_bar(lesson $lesson) {
        global $CFG, $USER, $DB;

        $context = context_module::instance($this->page->cm->id);

        // lesson setting to turn progress bar on or off
        if (!$lesson->progressbar) {
            return '';
        }

        // catch teachers
        if (has_capability('mod/customlesson:manage', $context)) {
            return $this->output->notification(get_string('progressbarteacherwarning2', 'customlesson'));
        }

        if (!isset($USER->modattempts[$lesson->id])) {
            // all of the lesson pages
            $pages = $lesson->load_all_pages();
            foreach ($pages as $page) {
                if ($page->prevpageid == 0) {
                    $pageid = $page->id;  // find the first page id
                    break;
                }
            }

            // current attempt number
            if (!$ntries = $DB->count_records("customlesson_grades", array("lessonid"=>$lesson->id, "userid"=>$USER->id))) {
                $ntries = 0;  // may not be necessary
            }

            $viewedpageids = array();
            if ($attempts = $lesson->get_attempts($ntries, false)) {
                foreach($attempts as $attempt) {
                    $viewedpageids[$attempt->pageid] = $attempt;
                }
            }

            $viewedbranches = array();
            // collect all of the branch tables viewed
            if ($branches = $DB->get_records("customlesson_branch", array ("lessonid"=>$lesson->id, "userid"=>$USER->id, "retry"=>$ntries), 'timeseen ASC', 'id, pageid')) {
                foreach($branches as $branch) {
                    $viewedbranches[$branch->pageid] = $branch;
                }
                $viewedpageids = array_merge($viewedpageids, $viewedbranches);
            }

            // Filter out the following pages:
            //      End of Cluster
            //      End of Branch
            //      Pages found inside of Clusters
            // Do not filter out Cluster Page(s) because we count a cluster as one.
            // By keeping the cluster page, we get our 1
            $validpages = array();
            while ($pageid != 0) {
                $pageid = $pages[$pageid]->valid_page_and_view($validpages, $viewedpageids);
            }

            // progress calculation as a percent
            $progress = round(count($viewedpageids)/count($validpages), 2) * 100;
        } else {
            $progress = 100;
        }

        // print out the Progress Bar.  Attempted to put as much as possible in the style sheets.
        $content = '<br />' . html_writer::tag('div', $progress . '%', array('class' => 'progress_bar_completed', 'style' => 'width: '. $progress . '%;'));
        $printprogress = html_writer::tag('div', get_string('progresscompleted', 'customlesson', $progress) . $content, array('class' => 'progress_bar'));

        return $this->output->box($printprogress, 'progress_bar');
    }

    /**
     * Returns HTML to show the start of a slideshow
     * @param lesson $lesson
     */
    public function slideshow_start(lesson $lesson) {
        $attributes = array();
        $attributes['class'] = 'slideshow';
        $attributes['style'] = 'background-color:'.$lesson->bgcolor.';height:'.$lesson->height.'px;width:'.$lesson->width.'px;';
        $output = html_writer::start_tag('div', $attributes);
    }
    /**
     * Returns HTML to show the end of a slideshow
     */
    public function slideshow_end() {
        $output = html_writer::end_tag('div');
    }
    /**
     * Returns a P tag containing contents
     * @param string $contents
     * @param string $class
     */
    public function paragraph($contents, $class='') {
        $attributes = array();
        if ($class !== '') {
            $attributes['class'] = $class;
        }
        $output = html_writer::tag('p', $contents, $attributes);
    }
    /**
     * Returns HTML to display add_highscores_form
     * @param lesson $lesson
     * @return string
     */
    public function add_highscores_form(lesson $lesson) {
        global $CFG;
        $output  = $this->output->box_start('generalbox boxaligncenter');
        $output .= $this->output->box_start('mdl-align');
        $output .= '<form id="nickname" method ="post" action="'.$CFG->wwwroot.'/mod/customlesson/highscores.php" autocomplete="off">
             <input type="hidden" name="id" value="'.$this->page->cm->id.'" />
             <input type="hidden" name="mode" value="save" />
             <input type="hidden" name="sesskey" value="'.sesskey().'" />';
        $output .= get_string("entername", "customlesson").": <input type=\"text\" name=\"name\" size=\"7\" maxlength=\"5\" />";
        $output .= $this->output->box("<input type='submit' value='".get_string('submitname', 'customlesson')."' />", 'lessonbutton center');
        $output .= "</form>";
        $output .= $this->output->box_end();
        $output .= $this->output->box_end();
        return $output;
    }
}
