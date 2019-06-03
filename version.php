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
 * Version info
 *
 * @package    regcourseapproval
 * @category   local
 * @copyright  2019, Oxford Brookes University {@link http://www.brookes.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version = 2019031800;

$plugin->requires = 2018051701; // Minimum version number of Moodle that this plugin requires (3.5.1)

$plugin->component = 'local_regcourseapproval'; // Full name of the plugin (used for diagnostics): plugintype_pluginname

$plugin->maturity = MATURITY_STABLE;//Optional - how stable the plugin is:
//MATURITY_ALPHA, MATURITY_BETA, MATURITY_RC, MATURITY_STABLE (Moodle 2.0 and above)

$plugin->release = 'v1.1.2';//Optional - Human-readable version name
?>
