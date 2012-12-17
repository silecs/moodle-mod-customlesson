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
 * Multichoice
 *
 * @package    mod
 * @subpackage customlesson
 * @copyright  2012 Silecs et Institut Telecom
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

defined('MOODLE_INTERNAL') || die();

/** Multichoice question type */
define("LESSON_PAGE_MULTICHOICE",   "3");

class customlesson_page_type_multichoice extends customlesson_page {

    protected $type = lesson_page::TYPE_QUESTION;
    protected $typeidstring = 'multichoice';
    protected $typeid = LESSON_PAGE_MULTICHOICE;
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

    /**
     * Gets an array of the jumps used by the answers of this page
     *
     * @return array
     */
    public function get_jumps() {
        global $DB;
        $jumps = array();
        if ($answers = $this->get_answers()) {
            foreach ($answers as $answer) {
                if ($answer->answer === '') {
                    // show only jumps for real branches (==have description)
                    continue;
                }
                $jumps[] = $this->get_jump_name($answer->jumpto);
            }
        } else {
            // We get here is the lesson was created on a Moodle 1.9 site and
            // the lesson contains question pages without any answers.
            $jumps[] = $this->get_jump_name($this->properties->nextpageid);
        }
        return $jumps;
    }

    public function get_used_answers() {
        $answers = $this->get_answers();
        foreach ($answers as $key=>$answer) {
            if ($answer->answer === '') {
                unset($answers[$key]);
            }
        }
        return $answers;
    }

    public function display($renderer, $attempt) {
        global $CFG, $PAGE;
        $answers = $this->get_used_answers();
        shuffle($answers);
        $action = $CFG->wwwroot.'/mod/customlesson/continue.php';
        $params = array('answers'=>$answers, 'lessonid'=>$this->lesson->id, 'contents'=>$this->get_contents(), 'attempt'=>$attempt);
        if ($this->properties->qoption) {
            $mform = new customlesson_display_answer_form_multichoice_multianswer($action, $params);
        } else {
            $mform = new customlesson_display_answer_form_multichoice_singleanswer($action, $params);
        }
        $data = new stdClass;
        $data->id = $PAGE->cm->id;
        $data->pageid = $this->properties->id;
        $mform->set_data($data);
        return $mform->display();
    }

    public function check_answer() {
        global $DB, $CFG, $PAGE;
        $result = parent::check_answer();

        $formattextdefoptions = new stdClass();
        $formattextdefoptions->noclean = true;
        $formattextdefoptions->para = false;

        $answers = $this->get_used_answers();
        shuffle($answers);
        $action = $CFG->wwwroot.'/mod/customlesson/continue.php';
        $params = array('answers'=>$answers, 'lessonid'=>$this->lesson->id, 'contents'=>$this->get_contents());
        if ($this->properties->qoption) {
            $mform = new customlesson_display_answer_form_multichoice_multianswer($action, $params);
        } else {
            $mform = new customlesson_display_answer_form_multichoice_singleanswer($action, $params);
        }
        $data = $mform->get_data();
        require_sesskey();

        if (!$data) {
            redirect(new moodle_url('/mod/customlesson/view.php', array('id'=>$PAGE->cm->id, 'pageid'=>$this->properties->id)));
        }

        if ($this->properties->qoption) {
            // Multianswer allowed, user's answer is an array

            if (empty($data->answer) || !is_array($data->answer)) {
                $result->noanswer = true;
                return $result;
            }

            $studentanswers = array();
            foreach ($data->answer as $key=>$value) {
                $studentanswers[] = (int)$key;
            }

            // get what the user answered
            $result->userresponse = implode(",", $studentanswers);

            // get the answers in a set order, the id order
            $answers = $this->get_used_answers();
            $ncorrect = 0;
            $nhits = 0;
            $responses = array();
            $correctanswerid = 0;
            $wronganswerid = 0;
            // store student's answers for displaying on feedback page
            $result->studentanswer = '';
            foreach ($answers as $answer) {
                foreach ($studentanswers as $answerid) {
                    if ($answerid == $answer->id) {
                        $result->studentanswer .= '<br />'.format_text($answer->answer, $answer->answerformat, $formattextdefoptions);
                        if (trim(strip_tags($answer->response))) {
                            $responses[$answerid] = format_text($answer->response, $answer->responseformat, $formattextdefoptions);
                        }
                    }
                }
            }
            $correctpageid = null;
            $wrongpageid = null;
            // this is for custom scores.  If score on answer is positive, it is correct
            if ($this->lesson->custom) {
                $ncorrect = 0;
                $nhits = 0;
                foreach ($answers as $answer) {
                    if ($answer->score > 0) {
                        $ncorrect++;

                        foreach ($studentanswers as $answerid) {
                            if ($answerid == $answer->id) {
                               $nhits++;
                            }
                        }
                        // save the first jumpto page id, may be needed!...
                        if (!isset($correctpageid)) {
                            // leave in its "raw" state - will converted into a proper page id later
                            $correctpageid = $answer->jumpto;
                        }
                        // save the answer id for scoring
                        if ($correctanswerid == 0) {
                            $correctanswerid = $answer->id;
                        }
                    } else {
                        // save the first jumpto page id, may be needed!...
                        if (!isset($wrongpageid)) {
                            // leave in its "raw" state - will converted into a proper page id later
                            $wrongpageid = $answer->jumpto;
                        }
                        // save the answer id for scoring
                        if ($wronganswerid == 0) {
                            $wronganswerid = $answer->id;
                        }
                    }
                }
            } else {
                foreach ($answers as $answer) {
                    if ($this->lesson->jumpto_is_correct($this->properties->id, $answer->jumpto)) {
                        $ncorrect++;
                        foreach ($studentanswers as $answerid) {
                            if ($answerid == $answer->id) {
                                $nhits++;
                            }
                        }
                        // save the first jumpto page id, may be needed!...
                        if (!isset($correctpageid)) {
                            // leave in its "raw" state - will converted into a proper page id later
                            $correctpageid = $answer->jumpto;
                        }
                        // save the answer id for scoring
                        if ($correctanswerid == 0) {
                            $correctanswerid = $answer->id;
                        }
                    } else {
                        // save the first jumpto page id, may be needed!...
                        if (!isset($wrongpageid)) {
                            // leave in its "raw" state - will converted into a proper page id later
                            $wrongpageid = $answer->jumpto;
                        }
                        // save the answer id for scoring
                        if ($wronganswerid == 0) {
                            $wronganswerid = $answer->id;
                        }
                    }
                }
            }
            if ((count($studentanswers) == $ncorrect) and ($nhits == $ncorrect)) {
                $result->correctanswer = true;
                $result->response  = implode('<br />', $responses);
                $result->newpageid = $correctpageid;
                $result->answerid  = $correctanswerid;
            } else {
                $result->response  = implode('<br />', $responses);
                $result->newpageid = $wrongpageid;
                $result->answerid  = $wronganswerid;
            }
        } else {
            // only one answer allowed
            if (!isset($data->answerid) || (empty($data->answerid) && !is_int($data->answerid))) {
                $result->noanswer = true;
                return $result;
            }
            $result->answerid = $data->answerid;
            if (!$answer = $DB->get_record("customlesson_answers", array("id" => $result->answerid))) {
                print_error("Continue: answer record not found");
            }
            if ($this->lesson->jumpto_is_correct($this->properties->id, $answer->jumpto)) {
                $result->correctanswer = true;
            }
            if ($this->lesson->custom) {
                if ($answer->score > 0) {
                    $result->correctanswer = true;
                } else {
                    $result->correctanswer = false;
                }
            }
            $result->newpageid = $answer->jumpto;
            $result->response  = format_text($answer->response, $answer->responseformat, $formattextdefoptions);
            $result->userresponse = format_text($answer->answer, $answer->answerformat, $formattextdefoptions);
            $result->studentanswer = $result->userresponse;
        }
        return $result;
    }

    public function option_description_string() {
        if ($this->properties->qoption) {
            return " - ".get_string("multianswer", "customlesson");
        }
        return parent::option_description_string();
    }

    public function display_answers(html_table $table) {
        $answers = $this->get_used_answers();
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
        if ($this->properties->qoption) {
            $userresponse = explode(",", $temp->useranswer);
            foreach ($userresponse as $response) {
                if (isset($pagestats[$temp->pageid][$response])) {
                    $pagestats[$temp->pageid][$response]++;
                } else {
                    $pagestats[$temp->pageid][$response] = 1;
                }
            }
        } else {
            if (isset($pagestats[$temp->pageid][$temp->answerid])) {
                $pagestats[$temp->pageid][$temp->answerid]++;
            } else {
                $pagestats[$temp->pageid][$temp->answerid] = 1;
            }
        }
        if (isset($pagestats[$temp->pageid]["total"])) {
            $pagestats[$temp->pageid]["total"]++;
        } else {
            $pagestats[$temp->pageid]["total"] = 1;
        }
        return true;
    }

    public function report_answers($answerpage, $answerdata, $useranswer, $pagestats, &$i, &$n) {
        $answers = $this->get_used_answers();
        $formattextdefoptions = new stdClass;
        $formattextdefoptions->para = false;  //I'll use it widely in this page

        foreach ($answers as $answer) {
            if ($this->properties->qoption) {
                if ($useranswer == NULL) {
                    $userresponse = array();
                } else {
                    $userresponse = explode(",", $useranswer->useranswer);
                }
                if (in_array($answer->id, $userresponse)) {
                    // make checked
                    $data = "<input  readonly=\"readonly\" disabled=\"disabled\" name=\"answer[$i]\" checked=\"checked\" type=\"checkbox\" value=\"1\" />";
                    if (!isset($answerdata->response)) {
                        if ($answer->response == NULL) {
                            if ($useranswer->correct) {
                                $answerdata->response = get_string("thatsthecorrectanswer", "customlesson");
                            } else {
                                $answerdata->response = get_string("thatsthewronganswer", "customlesson");
                            }
                        } else {
                            $answerdata->response = $answer->response;
                        }
                    }
                    if (!isset($answerdata->score)) {
                        if ($this->lesson->custom) {
                            $answerdata->score = get_string("pointsearned", "customlesson").": ".$answer->score;
                        } elseif ($useranswer->correct) {
                            $answerdata->score = get_string("receivedcredit", "customlesson");
                        } else {
                            $answerdata->score = get_string("didnotreceivecredit", "customlesson");
                        }
                    }
                } else {
                    // unchecked
                    $data = "<input type=\"checkbox\" readonly=\"readonly\" name=\"answer[$i]\" value=\"0\" disabled=\"disabled\" />";
                }
                if (($answer->score > 0 && $this->lesson->custom) || ($this->lesson->jumpto_is_correct($this->properties->id, $answer->jumpto) && !$this->lesson->custom)) {
                    $data = "<div class=highlight>".$data.' '.format_text($answer->answer,$answer->answerformat,$formattextdefoptions)."</div>";
                } else {
                    $data .= format_text($answer->answer,$answer->answerformat,$formattextdefoptions);
                }
            } else {
                if ($useranswer != NULL and $answer->id == $useranswer->answerid) {
                    // make checked
                    $data = "<input  readonly=\"readonly\" disabled=\"disabled\" name=\"answer[$i]\" checked=\"checked\" type=\"checkbox\" value=\"1\" />";
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
                    // unchecked
                    $data = "<input type=\"checkbox\" readonly=\"readonly\" name=\"answer[$i]\" value=\"0\" disabled=\"disabled\" />";
                }
                if (($answer->score > 0 && $this->lesson->custom) || ($this->lesson->jumpto_is_correct($this->properties->id, $answer->jumpto) && !$this->lesson->custom)) {
                    $data = "<div class=\"highlight\">".$data.' '.format_text($answer->answer,FORMAT_MOODLE,$formattextdefoptions)."</div>";
                } else {
                    $data .= format_text($answer->answer,$answer->answerformat,$formattextdefoptions);
                }
            }
            if (isset($pagestats[$this->properties->id][$answer->id])) {
                $percent = $pagestats[$this->properties->id][$answer->id] / $pagestats[$this->properties->id]["total"] * 100;
                $percent = round($percent, 2);
                $percent .= "% ".get_string("checkedthisone", "customlesson");
            } else {
                $percent = get_string("noonecheckedthis", "customlesson");
            }

            $answerdata->answers[] = array($data, $percent);
            $answerpage->answerdata = $answerdata;
        }
        return $answerpage;
    }
}


class customlesson_add_page_form_multichoice extends customlesson_add_page_form_base {

    public $qtype = 'multichoice';
    public $qtypestring = 'multichoice';

    public function custom_definition() {

        $this->_form->addElement('checkbox', 'qoption', get_string('options', 'customlesson'), get_string('multianswer', 'customlesson'));
        $this->_form->setDefault('qoption', 0);
        $this->_form->addHelpButton('qoption', 'multianswer', 'customlesson');

        for ($i = 0; $i < $this->_customdata['lesson']->maxanswers; $i++) {
            $this->_form->addElement('header', 'answertitle'.$i, get_string('answer').' '.($i+1));
            $this->add_answer($i, NULL, ($i<2));
            $this->add_response($i);
            $this->add_jumpto($i, NULL, ($i == 0 ? LESSON_NEXTPAGE : LESSON_THISPAGE));
            $this->add_score($i, null, ($i===0)?1:0);
        }
    }
}

class customlesson_display_answer_form_multichoice_singleanswer extends moodleform {

    public function definition() {
        global $USER, $OUTPUT;
        $mform = $this->_form;
        $answers = $this->_customdata['answers'];
        $lessonid = $this->_customdata['lessonid'];
        $contents = $this->_customdata['contents'];
        if (array_key_exists('attempt', $this->_customdata)) {
            $attempt = $this->_customdata['attempt'];
        } else {
            $attempt = new stdClass();
            $attempt->answerid = null;
        }

        $mform->addElement('header', 'pageheader');

        $mform->addElement('html', $OUTPUT->container($contents, 'contents'));

        $hasattempt = false;
        $disabled = '';
        if (isset($USER->modattempts[$lessonid]) && !empty($USER->modattempts[$lessonid])) {
            $hasattempt = true;
            $disabled = array('disabled' => 'disabled');
        }

        $options = new stdClass;
        $options->para = false;
        $options->noclean = true;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'pageid');
        $mform->setType('pageid', PARAM_INT);

        $i = 0;
        foreach ($answers as $answer) {
            $mform->addElement('html', '<div class="answeroption">');
            $mform->addElement('radio','answerid',null,format_text($answer->answer, $answer->answerformat, $options),$answer->id, $disabled);
            $mform->setType('answer'.$i, PARAM_INT);
            if ($hasattempt && $answer->id == $USER->modattempts[$lessonid]->answerid) {
                $mform->setDefault('answerid', $USER->modattempts[$lessonid]->answerid);
            }
            $mform->addElement('html', '</div>');
            $i++;
        }

        if ($hasattempt) {
            $this->add_action_buttons(null, get_string("nextpage", "customlesson"));
        } else {
            $this->add_action_buttons(null, get_string("submit", "customlesson"));
        }
    }

}

class customlesson_display_answer_form_multichoice_multianswer extends moodleform {

    public function definition() {
        global $USER, $OUTPUT;
        $mform = $this->_form;
        $answers = $this->_customdata['answers'];

        $lessonid = $this->_customdata['lessonid'];
        $contents = $this->_customdata['contents'];

        $mform->addElement('header', 'pageheader');

        $mform->addElement('html', $OUTPUT->container($contents, 'contents'));

        $hasattempt = false;
        $disabled = '';
        $useranswers = array();
        if (isset($USER->modattempts[$lessonid]) && !empty($USER->modattempts[$lessonid])) {
            $hasattempt = true;
            $disabled = array('disabled' => 'disabled');
            $useranswers = explode(',', $USER->modattempts[$lessonid]->useranswer);
        }

        $options = new stdClass;
        $options->para = false;
        $options->noclean = true;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'pageid');
        $mform->setType('pageid', PARAM_INT);

        foreach ($answers as $answer) {
            $mform->addElement('html', '<div class="answeroption">');
            $answerid = 'answer['.$answer->id.']';
            if ($hasattempt && in_array($answer->id, $useranswers)) {
                $answerid = 'answer_'.$answer->id;
                $mform->addElement('hidden', 'answer['.$answer->id.']', $answer->answer);
                $mform->setType('answer['.$answer->id.']', PARAM_TEXT);
                $mform->setDefault($answerid, true);
                $mform->setDefault('answer['.$answer->id.']', true);
            }
            // NOTE: our silly checkbox supports only value '1' - we can not use it like the radiobox above!!!!!!
            $mform->addElement('checkbox', $answerid, null, format_text($answer->answer, $answer->answerformat, $options), $disabled);
            $mform->setType($answerid, PARAM_INT);

            $mform->addElement('html', '</div>');
        }

        if ($hasattempt) {
            $this->add_action_buttons(null, get_string("nextpage", "customlesson"));
        } else {
            $this->add_action_buttons(null, get_string("submit", "customlesson"));
        }
    }

}
