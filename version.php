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
 * Version information
 *
 * @package    mod
 * @subpackage customlesson
 * @copyright  2012 Silecs et Institut Mines-Télécom
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  2012 Silecs et Institut Mines-Télécom
 * @notice     customlesson is heavily based on the official lesson module and large portions of code are copied from there.
 */

defined('MOODLE_INTERNAL') || die();

$module->version   = 2013042400;       // The current module version (Date: YYYYMMDDXX)
$module->requires  = 2012062500;    // Requires this Moodle version
$module->component = 'mod_customlesson'; // Full name of the plugin (used for diagnostics)
$module->cron      = 0;
$module->maturity = MATURITY_STABLE;
$module->release = '1.0.1 (Build: 2013042400)';
$module->dependencies = array();
