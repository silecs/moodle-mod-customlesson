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
 * Generic forms used for page selection
 *
 * @package    mod
 * @subpackage customlesson
 * @copyright  2009 Sam Hemelryk
 * @copyright  2012-2013 Silecs et Institut Mines-Télécom
 * @notice     customlesson is heavily based on the official lesson module and large portions of code are copied from there.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

defined('MOODLE_INTERNAL') || die();

/**
 * Question selection form
 *
 * @copyright  2009 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
class customlesson_add_page_form_selection extends customlesson_add_page_form_base {

    public $qtype = 'questiontype';
    public $qtypestring = 'selectaqtype';
    protected $standard = false;
    protected $manager = null;

    public function __construct($arg1, $arg2) {
        $this->manager = customlesson_page_type_manager::get($arg2['lesson']);
        parent::__construct($arg1, $arg2);
    }

    public function custom_definition() {
        $mform = $this->_form;
        $types = $this->manager->get_page_type_strings(customlesson_page::TYPE_QUESTION);
        asort($types);
        $mform->addElement('select', 'qtype', get_string('selectaqtype', 'customlesson'), $types);
        $mform->setDefault('qtype', CUSTOMLESSON_PAGE_MULTICHOICE); // preselect the most common type
    }
}

/**
 * Dummy class to represent an unknown question type and direct to the selection
 * form.
 */
final class customlesson_add_page_form_unknown extends customlesson_add_page_form_base {}
