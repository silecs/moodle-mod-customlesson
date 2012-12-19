<?php

/**
 * Imports individual data into lessons
 *
 * @package    mod
 * @subpackage customlesson
 * @copyright  2012 Silecs et Institut Telecom
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

class import_individual {
    protected $lessonid;

    protected $csvfile;
    protected $separator;

    protected $reserved_columns = array();
    protected $key_columns = array();

    protected $errors = array();

    /**
     * Constructor.
     * @param integer $lessonid
     */
    public function __construct($lessonid) {
        $this->lessonid = (int) $lessonid;
    }

    /**
     * Sets the source file.
     * @param string $file
     * @param string $separator
     */
    public function setCsvFile($file, $separator=';') {
        $this->csvfile = $file;
        $this->separator = $separator;
        $this->errors = array();
    }

    /**
     * Checks the headers on the first line of the CSV file.
     * @return boolean
     */
    public function checkColumns() {
        $this->reserved_columns = array();
        $this->key_columns = array();
        $fh = fopen($this->csvfile, "r");
        if ($fh === false) {
            $this->errors[] = get_string('cannotopencsv', 'error');
            return false;
        }
        $header = fgetcsv($fh, 1000, $this->separator);
        if (!$header) {
            $this->errors[] = get_string('csvemptyfile', 'error');
            return false;
        }
        foreach ($header as $pos => $col) {
            if (strcasecmp(trim($col), "userid") === 0) {
                $this->reserved_columns['userid'] = $pos;
            } else if (strcasecmp(trim($col), "username") === 0) {
                $this->reserved_columns['username'] = $pos;
            } else {
                //! @todo check that the key is used in this lesson
                $this->key_columns[$col] = $pos;
            }
        }
        if (!$this->reserved_columns) {
            return false;
        }
        fclose($fh);
        return true;
    }

    /**
     * Adds the user data from the CSV file to the DB.
     * @global moodle_database $DB
     * @return boolean
     */
    public function importContent() {
        global $DB;

        if (!$this->reserved_columns) {
            return false;
        }

        $this->resetKeys();

        $fh = fopen($this->csvfile, "r");
        if ($fh === false) {
            $this->errors[] = get_string('cannotopencsv');
            return false;
        }
        $header = fgetcsv($fh, 1000, $this->separator);
        $line = 1;
        while (($data = fgetcsv($fh, 1000, $this->separator)) !== FALSE) {
            $record = array();
            $record['lessonid'] = $this->lessonid;
            $record['username'] = '';
            if (isset($this->reserved_columns['username'])) {
                $record['username'] = (int) $data[$this->reserved_columns['username']];
            }
            if (isset($this->reserved_columns['userid'])) {
                $record['userid'] = (int) $data[$this->reserved_columns['userid']];
            } else {
                $record['userid'] = (int) $DB->get_field('user', 'id', array('username' => $record['username']));
            }
            if (!$record['userid']) {
                $this->errors[] = "Could not identify user in CSV file, line " . $line;
            }
            foreach ($this->key_columns as $colname => $colpos) {
                $insert = (object) $record;
                $insert->substkey = $colname;
                $insert->value = $data[$colpos];
                $DB->insert_record('customlesson_keys', $insert, false);
            }
            $line++;
        }
        fclose($fh);
        return true;
    }

    /**
     * Delete user keys for the current lesson.
     * @global moodle_database $DB
     */
    public function resetKeys() {
        global $DB;
        if (!$this->lessonid) {
            throw new Exception('No lesson set');
        }
        $DB->delete_records('customlesson_keys', array('lessonid' => $this->lessonid));
    }

    /**
     * Returns the list of the import errors.
     * @return array
     */
    public function getErrors() {
        return $this->errors;
    }
}
