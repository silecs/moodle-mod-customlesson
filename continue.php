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
 * Action for processing page answers by users
 *
 * @package    mod
 * @subpackage customlesson
 * @copyright  2009 Sam Hemelryk
 * @copyright  2012-2013 Silecs et Institut Mines-Télécom
 * @notice     customlesson is heavily based on the official lesson module and large portions of code are copied from there.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

/** Require the specific libraries */
require_once("../../config.php");
require_once($CFG->dirroot.'/mod/customlesson/locallib.php');

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('customlesson', $id, 0, false, MUST_EXIST);;
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$lesson = new customlesson($DB->get_record('customlesson', array('id' => $cm->instance), '*', MUST_EXIST));

require_login($course, false, $cm);
require_sesskey();

$context = context_module::instance($cm->id);
$canmanage = has_capability('mod/customlesson:manage', $context);
$lessonoutput = $PAGE->get_renderer('mod_customlesson');

$url = new moodle_url('/mod/customlesson/continue.php', array('id'=>$cm->id));
$PAGE->set_url($url);
$PAGE->navbar->add(get_string('continue', 'customlesson'));

// This is the code updates the lesson time for a timed test
// get time information for this user
if (!$canmanage) {
    $lesson->displayleft = customlesson_displayleftif($lesson);
    $timer = $lesson->update_timer();
    if ($lesson->timed) {
        $timeleft = ($timer->starttime + $lesson->maxtime * 60) - time();
        if ($timeleft <= 0) {
            // Out of time
            $lesson->add_message(get_string('eolstudentoutoftime', 'customlesson'));
            redirect(new moodle_url('/mod/customlesson/view.php', array('id'=>$cm->id,'pageid'=>CUSTOMLESSON_EOL, 'outoftime'=>'normal')));
        } else if ($timeleft < 60) {
            // One minute warning
            $lesson->add_message(get_string("studentoneminwarning", "customlesson"));
        }
    }
} else {
    $timer = new stdClass;
}

// record answer (if necessary) and show response (if none say if answer is correct or not)
$page = $lesson->load_page(required_param('pageid', PARAM_INT));

$userhasgrade = $DB->count_records("customlesson_grades", array("lessonid"=>$lesson->id, "userid"=>$USER->id));
$reviewmode = false;
if ($userhasgrade && !$lesson->retake) {
    $reviewmode = true;
}

// Check the page has answers [MDL-25632]
if (count($page->answers) > 0) {
    $result = $page->record_attempt($context);
} else {
    // The page has no answers so we will just progress to the next page in the
    // sequence (as set by newpageid).
    $result = new stdClass;
    $result->newpageid       = optional_param('newpageid', $page->nextpageid, PARAM_INT);
    $result->nodefaultresponse  = true;
}

if (isset($USER->modattempts[$lesson->id])) {
    // make sure if the student is reviewing, that he/she sees the same pages/page path that he/she saw the first time
    if ($USER->modattempts[$lesson->id]->pageid == $page->id && $page->nextpageid == 0) {  // remember, this session variable holds the pageid of the last page that the user saw
        $result->newpageid = CUSTOMLESSON_EOL;
    } else {
        $nretakes = $DB->count_records("customlesson_grades", array("lessonid"=>$lesson->id, "userid"=>$USER->id));
        $nretakes--; // make sure we are looking at the right try.
        $attempts = $DB->get_records("customlesson_attempts", array("lessonid"=>$lesson->id, "userid"=>$USER->id, "retry"=>$nretakes), "timeseen", "id, pageid");
        $found = false;
        $temppageid = 0;
        foreach($attempts as $attempt) {
            if ($found && $temppageid != $attempt->pageid) { // now try to find the next page, make sure next few attempts do no belong to current page
                $result->newpageid = $attempt->pageid;
                break;
            }
            if ($attempt->pageid == $page->id) {
                $found = true; // if found current page
                $temppageid = $attempt->pageid;
            }
        }
    }
} elseif ($result->newpageid != CUSTOMLESSON_CLUSTERJUMP && $page->id != 0 && $result->newpageid > 0) {
    // going to check to see if the page that the user is going to view next, is a cluster page.
    // If so, dont display, go into the cluster.  The $result->newpageid > 0 is used to filter out all of the negative code jumps.
    $newpage = $lesson->load_page($result->newpageid);
    if ($newpageid = $newpage->override_next_page($result->newpageid)) {
        $result->newpageid = $newpageid;
    }
} elseif ($result->newpageid == CUSTOMLESSON_UNSEENBRANCHPAGE) {
    if ($canmanage) {
        if ($page->nextpageid == 0) {
            $result->newpageid = CUSTOMLESSON_EOL;
        } else {
            $result->newpageid = $page->nextpageid;
        }
    } else {
        $result->newpageid = customlesson_unseen_question_jump($lesson, $USER->id, $page->id);
    }
} elseif ($result->newpageid == CUSTOMLESSON_PREVIOUSPAGE) {
    $result->newpageid = $page->prevpageid;
} elseif ($result->newpageid == CUSTOMLESSON_RANDOMPAGE) {
    $result->newpageid = customlesson_random_question_jump($lesson, $page->id);
} elseif ($result->newpageid == CUSTOMLESSON_CLUSTERJUMP) {
    if ($canmanage) {
        if ($page->nextpageid == 0) {  // if teacher, go to next page
            $result->newpageid = CUSTOMLESSON_EOL;
        } else {
            $result->newpageid = $page->nextpageid;
        }
    } else {
        $result->newpageid = $lesson->cluster_jump($page->id);
    }
}

if ($result->nodefaultresponse) {
    // Don't display feedback
    redirect(new moodle_url('/mod/customlesson/view.php', array('id'=>$cm->id,'pageid'=>$result->newpageid)));
}

/// Set Messages

if ($canmanage) {
    // This is the warning msg for teachers to inform them that cluster and unseen does not work while logged in as a teacher
    if(customlesson_display_teacher_warning($lesson)) {
        $warningvars = new stdClass();
        $warningvars->cluster = get_string("clusterjump", "customlesson");
        $warningvars->unseen = get_string("unseenpageinbranch", "customlesson");
        $lesson->add_message(get_string("teacherjumpwarning", "customlesson", $warningvars));
    }
    // Inform teacher that s/he will not see the timer
    if ($lesson->timed) {
        $lesson->add_message(get_string("teachertimerwarning", "customlesson"));
    }
}
// Report attempts remaining
if ($result->attemptsremaining != 0 && !$lesson->review && !$reviewmode) {
    $lesson->add_message(get_string('attemptsremaining', 'customlesson', $result->attemptsremaining));
}
// Report if max attempts reached
if ($result->maxattemptsreached != 0 && !$lesson->review && !$reviewmode) {
    $lesson->add_message('('.get_string("maximumnumberofattemptsreached", "customlesson").')');
}

$PAGE->set_url('/mod/customlesson/view.php', array('id' => $cm->id, 'pageid' => $page->id));
$PAGE->set_subpage($page->id);

/// Print the header, heading and tabs
customlesson_add_fake_blocks($PAGE, $cm, $lesson, $timer);
echo $lessonoutput->header($lesson, $cm, 'view', true, $page->id, get_string('continue', 'customlesson'));

if ($lesson->displayleft) {
    echo '<a name="maincontent" id="maincontent" title="'.get_string('anchortitle', 'customlesson').'"></a>';
}
// This calculates and prints the ongoing score message
if ($lesson->ongoing && !$reviewmode) {
    echo $lessonoutput->ongoing_score($lesson);
}
echo $result->feedback;

// User is modifying attempts - save button and some instructions
if (isset($USER->modattempts[$lesson->id])) {
    $url = $CFG->wwwroot.'/mod/customlesson/view.php';
    $content = $OUTPUT->box(get_string("gotoendoflesson", "customlesson"), 'center');
    $content .= $OUTPUT->box(get_string("or", "customlesson"), 'center');
    $content .= $OUTPUT->box(get_string("continuetonextpage", "customlesson"), 'center');
    $content .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'id', 'value'=>$cm->id));
    $content .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'pageid', 'value'=>CUSTOMLESSON_EOL));
    $content .= html_writer::empty_tag('input', array('type'=>'submit', 'name'=>'submit', 'value'=>get_string('finish', 'customlesson')));
    echo html_writer::tag('form', "<div>$content</div>", array('method'=>'post', 'action'=>$url));
}

// Review button back
if (!$result->correctanswer && !$result->noanswer && !$result->isessayquestion && !$reviewmode && $lesson->review) {
    $url = $CFG->wwwroot.'/mod/customlesson/view.php';
    $content = html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'id', 'value'=>$cm->id));
    $content .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'pageid', 'value'=>$page->id));
    $content .= html_writer::empty_tag('input', array('type'=>'submit', 'name'=>'submit', 'value'=>get_string('reviewquestionback', 'customlesson')));
    echo html_writer::tag('form', "<div class=\"singlebutton\">$content</div>", array('method'=>'post', 'action'=>$url));
}

$url = new moodle_url('/mod/customlesson/view.php', array('id'=>$cm->id, 'pageid'=>$result->newpageid));
if ($lesson->review && !$result->correctanswer && !$result->noanswer && !$result->isessayquestion) {
    // Review button continue
    echo $OUTPUT->single_button($url, get_string('reviewquestioncontinue', 'customlesson'));
} else {
    // Normal continue button
    echo $OUTPUT->single_button($url, get_string('continue', 'customlesson'));
}

echo $lessonoutput->footer();