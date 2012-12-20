<?php

/**
 * Imports individual data into lessons
 *
 * @package    mod
 * @subpackage customlesson
 * @copyright  2012 Silecs et Institut Telecom
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

require_once("../../config.php");
require_once($CFG->dirroot.'/mod/customlesson/locallib.php');
require_once($CFG->dirroot.'/mod/customlesson/importindividual_form.php');
require_once($CFG->dirroot.'/mod/customlesson/libindividual.php');

$id = required_param('id', PARAM_INT);         // Course Module ID

$PAGE->set_url('/mod/customlesson/importindividual.php', array('id'=>$id));

$cm = get_coursemodule_from_id('customlesson', $id, 0, false, MUST_EXIST);;
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$lesson = new customlesson($DB->get_record('customlesson', array('id' => $cm->instance), '*', MUST_EXIST));

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/customlesson:edit', $context);

$strimportindividualdata = get_string("importindividualdata", "customlesson");

$PAGE->navbar->add($strimportindividualdata);
$PAGE->set_title($strimportindividualdata);
$PAGE->set_heading($strimportindividualdata);
echo $OUTPUT->header();

//echo $OUTPUT->heading_with_help($strimportindividualdata, 'importindividualdata', 'customlesson' );

$mform = new customlesson_importindividual_form();
$mform->set_data((object) array('id' => $id));
$data = $mform->get_data();
if ($data) {
    require_sesskey();

    $realfilename = $mform->get_new_filename('csvfile');
    $importfile = "{$CFG->tempdir}/importindividual/".$realfilename;
    make_temp_directory('importindividual');
    if (!$result = $mform->save_file('csvfile', $importfile, true)) {
        throw new moodle_exception('uploadproblem');
    }

    // Process the uploaded file
    $import = new import_individual($lesson->id);
    $import->setCsvFile($importfile, $data->separator);
    if (!$import->checkColumns() || !$import->importContent()) {
        print_error('processerror', 'customlesson');
        echo $OUTPUT->box($import->getErrors(true), 'errorbox');
    } else {
        echo $OUTPUT->box(get_string('success'));
        if ($import->getErrors()) {
            echo $OUTPUT->box(get_string('warning') . ' ' . $import->getErrors(true), 'errorbox');
        }
    }

    echo $OUTPUT->continue_button('view.php?id='.$id);
} else {
    $mform->display();
}

echo $OUTPUT->footer();