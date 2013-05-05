<?php

/**
 * @package    mod
 * @subpackage customlesson
 * @copyright  2012-2013 Silecs et Institut Mines-Télécom
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

require_once($CFG->libdir.'/formslib.php');

class customlesson_importindividual_form extends moodleform {

    function definition() {
        $mform =& $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('filepicker', 'csvfile', get_string('individualfile', 'customlesson'));

        $mform->addElement('text', 'separator', get_string('separator', 'customlesson'));
        $mform->setDefault('separator', ';');

        $submit_string = get_string('submit');
        $this->add_action_buttons(false, $submit_string);
    }
}
