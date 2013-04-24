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
 * Numerical
 *
 * @package    mod
 * @subpackage customlesson
 * @copyright  2009 Sam Hemelryk
 * @copyright  2012 Silecs et Institut Mines-Télécom
 * @notice     customlesson is heavily based on the official lesson module and large portions of code are copied from there.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

defined('MOODLE_INTERNAL') || die();

/** Numerical question type */
define("CUSTOMLESSON_PAGE_NUMERICAL",     "8");

class customlesson_page_type_numerical extends customlesson_page {

    protected $type = customlesson_page::TYPE_QUESTION;
    protected $typeidstring = 'numerical';
    protected $typeid = CUSTOMLESSON_PAGE_NUMERICAL;
    protected $string = null;

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
    public function display($renderer, $attempt) {
        global $USER, $CFG, $PAGE;
        $mform = new customlesson_display_answer_form_shortanswer($CFG->wwwroot.'/mod/customlesson/continue.php', array('contents'=>$this->get_contents(), 'lessonid'=>$this->lesson->id));
        $data = new stdClass;
        $data->id = $PAGE->cm->id;
        $data->pageid = $this->properties->id;
        if (isset($USER->modattempts[$this->lesson->id])) {
            $data->answer = s($attempt->useranswer);
        }
        $mform->set_data($data);
        return $mform->display();
    }
    public function check_answer() {
        global $CFG;
        $result = parent::check_answer();

        $mform = new customlesson_display_answer_form_shortanswer($CFG->wwwroot.'/mod/customlesson/continue.php', array('contents'=>$this->get_contents()));
        $data = $mform->get_data();
        require_sesskey();

        // set defaults
        $result->response = '';
        $result->newpageid = 0;

        if (isset($data->answer)) {
            // just doing default PARAM_RAW, not doing PARAM_INT because it could be a float
            $result->useranswer = (float)$data->answer;
        } else {
            $result->noanswer = true;
            return $result;
        }
        $result->studentanswer = $result->userresponse = $result->useranswer;
        $answers = $this->get_answers();
        foreach ($answers as $answer) {
            if (strpos($answer->answer, ':')) {
                // there's a pairs of values
                list($min, $max) = explode(':', $answer->answer);
                $minimum = (float) $min;
                $maximum = (float) $max;
            } else {
                // there's only one value
                $minimum = (float) $answer->answer;
                $maximum = $minimum;
            }
            if (($result->useranswer >= $minimum) && ($result->useranswer <= $maximum)) {
                $result->newpageid = $answer->jumpto;
                $result->response = trim($answer->response);
                if ($this->lesson->jumpto_is_correct($this->properties->id, $result->newpageid)) {
                    $result->correctanswer = true;
                }
                if ($this->lesson->custom) {
                    if ($answer->score > 0) {
                        $result->correctanswer = true;
                    } else {
                        $result->correctanswer = false;
                    }
                }
                $result->answerid = $answer->id;
                return $result;
            }
        }
        return $result;
    }

    public function display_answers(html_table $table) {
        $answers = $this->get_answers();
        $options = new stdClass;
        $options->noclean = true;
        $options->para = false;
        $i = 1;
        foreach ($answers as $answer) {
            $cells = array();
            if ($this->lesson->custom && $answer->score > 0) {
                // if the score is > 0, then it is correct
                $cells[] = '<span class="labelcorrect">'.get_string("answer", "customlesson")." $i</span>: \n";
            } else if ($this->lesson->custom) {
                $cells[] = '<span class="label">'.get_string("answer", "customlesson")." $i</span>: \n";
            } else if ($this->lesson->jumpto_is_correct($this->properties->id, $answer->jumpto)) {
                // underline correct answers
                $cells[] = '<span class="correct">'.get_string("answer", "customlesson")." $i</span>: \n";
            } else {
                $cells[] = '<span class="labelcorrect">'.get_string("answer", "customlesson")." $i</span>: \n";
            }
            $cells[] = format_text($answer->answer, $answer->answerformat, $options);
            $table->data[] = new html_table_row($cells);

            $cells = array();
            $cells[] = "<span class=\"label\">".get_string("response", "customlesson")." $i</span>";
            $cells[] = format_text($answer->response, $answer->responseformat, $options);
            $table->data[] = new html_table_row($cells);

            $cells = array();
            $cells[] = "<span class=\"label\">".get_string("score", "customlesson").'</span>';
            $cells[] = $answer->score;
            $table->data[] = new html_table_row($cells);

            $cells = array();
            $cells[] = "<span class=\"label\">".get_string("jump", "customlesson").'</span>';
            $cells[] = $this->get_jump_name($answer->jumpto);
            $table->data[] = new html_table_row($cells);
            if ($i === 1){
                $table->data[count($table->data)-1]->cells[0]->style = 'width:20%;';
            }
            $i++;
        }
        return $table;
    }
    public function stats(array &$pagestats, $tries) {
        if(count($tries) > $this->lesson->maxattempts) { // if there are more tries than the max that is allowed, grab the last "legal" attempt
            $temp = $tries[$this->lesson->maxattempts - 1];
        } else {
            // else, user attempted the question less than the max, so grab the last one
            $temp = end($tries);
        }
        if (isset($pagestats[$temp->pageid][$temp->useranswer])) {
            $pagestats[$temp->pageid][$temp->useranswer]++;
        } else {
            $pagestats[$temp->pageid][$temp->useranswer] = 1;
        }
        if (isset($pagestats[$temp->pageid]["total"])) {
            $pagestats[$temp->pageid]["total"]++;
        } else {
            $pagestats[$temp->pageid]["total"] = 1;
        }
        return true;
    }

    public function report_answers($answerpage, $answerdata, $useranswer, $pagestats, &$i, &$n) {
        $answers = $this->get_answers();
        $formattextdefoptions = new stdClass;
        $formattextdefoptions->para = false;  //I'll use it widely in this page
        foreach ($answers as $answer) {
            if ($useranswer == null && $i == 0) {
                // I have the $i == 0 because it is easier to blast through it all at once.
                if (isset($pagestats[$this->properties->id])) {
                    $stats = $pagestats[$this->properties->id];
                    $total = $stats["total"];
                    unset($stats["total"]);
                    foreach ($stats as $valentered => $ntimes) {
                        $data = '<input type="text" size="50" disabled="disabled" readonly="readonly" value="'.s($valentered).'" />';
                        $percent = $ntimes / $total * 100;
                        $percent = round($percent, 2);
                        $percent .= "% ".get_string("enteredthis", "customlesson");
                        $answerdata->answers[] = array($data, $percent);
                    }
                } else {
                    $answerdata->answers[] = array(get_string("nooneansweredthisquestion", "customlesson"), " ");
                }
                $i++;
            } else if ($useranswer != null && ($answer->id == $useranswer->answerid || ($answer == end($answers) && empty($answerdata)))) {
                 // get in here when what the user entered is not one of the answers
                $data = '<input type="text" size="50" disabled="disabled" readonly="readonly" value="'.s($useranswer->useranswer).'">';
                if (isset($pagestats[$this->properties->id][$useranswer->useranswer])) {
                    $percent = $pagestats[$this->properties->id][$useranswer->useranswer] / $pagestats[$this->properties->id]["total"] * 100;
                    $percent = round($percent, 2);
                    $percent .= "% ".get_string("enteredthis", "customlesson");
                } else {
                    $percent = get_string("nooneenteredthis", "customlesson");
                }
                $answerdata->answers[] = array($data, $percent);

                if ($answer->id == $useranswer->answerid) {
                    if ($answer->response == NULL) {
                        if ($useranswer->correct) {
                            $answerdata->response = get_string("thatsthecorrectanswer", "customlesson");
                        } else {
                            $answerdata->response = get_string("thatsthewronganswer", "customlesson");
                        }
                    } else {
                        $answerdata->response = $answer->response;
                    }
                    if ($this->lesson->custom) {
                        $answerdata->score = get_string("pointsearned", "customlesson").": ".$answer->score;
                    } elseif ($useranswer->correct) {
                        $answerdata->score = get_string("receivedcredit", "customlesson");
                    } else {
                        $answerdata->score = get_string("didnotreceivecredit", "customlesson");
                    }
                } else {
                    $answerdata->response = get_string("thatsthewronganswer", "customlesson");
                    if ($this->lesson->custom) {
                        $answerdata->score = get_string("pointsearned", "customlesson").": 0";
                    } else {
                        $answerdata->score = get_string("didnotreceivecredit", "customlesson");
                    }
                }
            }
            $answerpage->answerdata = $answerdata;
        }
        return $answerpage;
    }
}

class customlesson_add_page_form_numerical extends customlesson_add_page_form_base {

    public $qtype = 'numerical';
    public $qtypestring = 'numerical';

    public function custom_definition() {
        for ($i = 0; $i < $this->_customdata['lesson']->maxanswers; $i++) {
            $this->_form->addElement('header', 'answertitle'.$i, get_string('answer').' '.($i+1));
            $this->add_answer($i, NULL, ($i < 1));
            $this->add_response($i);
            $this->add_jumpto($i, NULL, ($i == 0 ? CUSTOMLESSON_NEXTPAGE : CUSTOMLESSON_THISPAGE));
            $this->add_score($i, null, ($i===0)?1:0);
        }
    }
}

class customlesson_display_answer_form_numerical extends moodleform {

    public function definition() {
        global $USER, $OUTPUT;
        $mform = $this->_form;
        $contents = $this->_customdata['contents'];

        $mform->addElement('header', 'pageheader');

        $mform->addElement('html', $OUTPUT->container($contents, 'contents'));

        $hasattempt = false;
        $attrs = array('size'=>'50', 'maxlength'=>'200');
        if (isset($this->_customdata['lessonid'])) {
            $lessonid = $this->_customdata['lessonid'];
            if (isset($USER->modattempts[$lessonid]->useranswer)) {
                $attrs['readonly'] = 'readonly';
                $hasattempt = true;
            }
        }
        $options = new stdClass;
        $options->para = false;
        $options->noclean = true;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'pageid');
        $mform->setType('pageid', PARAM_INT);

        $mform->addElement('text', 'answer', get_string('youranswer', 'customlesson'), $attrs);
        $mform->setType('answer', PARAM_FLOAT);

        if ($hasattempt) {
            $this->add_action_buttons(null, get_string("nextpage", "customlesson"));
        } else {
            $this->add_action_buttons(null, get_string("submit", "customlesson"));
        }
    }

}
