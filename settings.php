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
 * Settings used by the lesson module, were moved from mod_edit
 *
 * @package    mod
 * @subpackage customlesson
 * @copyright  2009 Sam Hemelryk
 * @copyright  2012-2013 Silecs et Institut Mines-Télécom
 * @notice     customlesson is heavily based on the official lesson module and large portions of code are copied from there.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 **/

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once($CFG->dirroot.'/mod/customlesson/locallib.php');

    /** Slideshow settings */
    $settings->add(new admin_setting_configtext('customlesson_slideshowwidth', get_string('slideshowwidth', 'customlesson'),
            get_string('configslideshowwidth', 'customlesson'), 640, PARAM_INT));

    $settings->add(new admin_setting_configtext('customlesson_slideshowheight', get_string('slideshowheight', 'customlesson'),
            get_string('configslideshowheight', 'customlesson'), 480, PARAM_INT));

    $settings->add(new admin_setting_configtext('customlesson_slideshowbgcolor', get_string('slideshowbgcolor', 'customlesson'),
            get_string('configslideshowbgcolor', 'customlesson'), '#FFFFFF', PARAM_TEXT));

    /** Media file popup settings */
    $settings->add(new admin_setting_configtext('customlesson_mediawidth', get_string('mediawidth', 'customlesson'),
            get_string('configmediawidth', 'customlesson'), 640, PARAM_INT));

    $settings->add(new admin_setting_configtext('customlesson_mediaheight', get_string('mediaheight', 'customlesson'),
            get_string('configmediaheight', 'customlesson'), 480, PARAM_INT));

    $settings->add(new admin_setting_configcheckbox('customlesson_mediaclose', get_string('mediaclose', 'customlesson'),
            get_string('configmediaclose', 'customlesson'), false, PARAM_TEXT));

    /** Misc lesson settings */
    $settings->add(new admin_setting_configtext('customlesson_maxhighscores', get_string('maxhighscores', 'customlesson'),
            get_string('configmaxhighscores', 'customlesson'), 10, PARAM_INT));

    /** Default lesson settings */
    $numbers = array();
    for ($i=20; $i>1; $i--) {
        $numbers[$i] = $i;
    }
    $settings->add(new admin_setting_configselect('customlesson_maxanswers', get_string('maximumnumberofanswersbranches', 'customlesson'),
            get_string('configmaxanswers', 'customlesson'), 4, $numbers));

    $defaultnextpages = array();
    $defaultnextpages[0] = get_string("normal", "customlesson");
    $defaultnextpages[CUSTOMLESSON_UNSEENPAGE] = get_string("showanunseenpage", "customlesson");
    $defaultnextpages[CUSTOMLESSON_UNANSWEREDPAGE] = get_string("showanunansweredpage", "customlesson");
    $settings->add(new admin_setting_configselect('customlesson_defaultnextpage', get_string('actionaftercorrectanswer', 'customlesson'),
            get_string('configactionaftercorrectanswer', 'customlesson'), 0, $defaultnextpages));
}