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
 * Database upgrade
 *
 * @package    regcourseapproval
 * @category   local
 * @copyright  2015, Oxford Brookes University {@link http://www.brookes.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function xmldb_local_regcourseapproval_upgrade($oldversion = 0) {
    global $DB;
    $dbman = $DB->get_manager();

    $result = true;

    if ($oldversion < 2014070711) {

        // Define table local_regcourseapproval to be created
        $table = new xmldb_table('local_regcourseapproval');

        // Adding fields to table local_regcourseapproval
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('course', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('enrolled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('invitationdate', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('enrolleddate', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('unenrolledate', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('approver', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('email', XMLDB_TYPE_CHAR, '255', null, null, null, null);

        // Adding keys to table local_regcourseapproval
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table local_regcourseapproval
        $table->add_index('course', XMLDB_INDEX_NOTUNIQUE, array('course'));
        $table->add_index('userid', XMLDB_INDEX_NOTUNIQUE, array('userid'));
        $table->add_index('email', XMLDB_INDEX_NOTUNIQUE, array('email'));

        // Conditionally launch create table for local_regcourseapproval
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // regcourseapproval savepoint reached
        upgrade_plugin_savepoint(true, 2014070711, 'local', 'regcourseapproval');
    }
    
	if ($oldversion < 2017120700) {

		// Define the additional field to be added to local_regcourseapproval
		$table = new xmldb_table('local_regcourseapproval');
		$field = new xmldb_field('autoconfirm', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'email');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		// regcourseapproval savepoint reached
		upgrade_plugin_savepoint(true, 2017120700, 'local', 'regcourseapproval');
    }
    
    return $result;
}





