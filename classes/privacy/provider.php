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
 * Course Registration with Approval - Privacy Subsystem implementation
 *
 * @package    regcourseapproval
 * @category   local
 * @copyright  2019, Oxford Brookes University {@link http://www.brookes.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_regcourseapproval\privacy;

use \core_privacy\local\metadata\collection;
use \core_privacy\local\request\contextlist;
use \core_privacy\local\request\approved_contextlist;
use \core_privacy\local\request\userlist;
use \core_privacy\local\request\approved_userlist;
use \core_privacy\local\request\transform;
use \core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

class provider implements \core_privacy\local\metadata\provider, \core_privacy\local\request\plugin\provider, \core_privacy\local\request\core_userlist_provider {

	public static function get_metadata(collection $collection) : collection {
	 
		$collection->add_database_table(
			'local_regcourseapproval',
			[
				'course' => 'privacy:metadata:local_regcourseapproval:course',
				'userid' => 'privacy:metadata:local_regcourseapproval:userid',
				'enrolled' => 'privacy:metadata:local_regcourseapproval:enrolled',
				'enrolleddate' => 'privacy:metadata:local_regcourseapproval:enrolleddate',
				'unenrolledate' => 'privacy:metadata:local_regcourseapproval:unenrolledate',
				'email' => 'privacy:metadata:local_regcourseapproval:email'
			],
			'privacy:metadata:local_regcourseapproval'
		);
	 
		return $collection;
	}

	public static function get_contexts_for_userid(int $userid) : contextlist {

		$sql = "SELECT DISTINCT c.id FROM {context} c
			JOIN {local_regcourseapproval} rca ON rca.userid = c.instanceid
			WHERE (c.contextlevel = :contextlevel) AND (c.instanceid = :userid)";

		$params = [
			'contextlevel' => CONTEXT_USER,
			'userid' => $userid
		];

		$contextlist = new \core_privacy\local\request\contextlist();
		$contextlist->add_from_sql($sql, $params);

		return $contextlist;
	} 

	public static function export_user_data(approved_contextlist $contextlist) {
		global $DB;

		if (empty($contextlist->count())) {
			return;
		}

		$user = $contextlist->get_user();

		foreach ($contextlist->get_contexts() as $context) {

			if ($context->contextlevel != CONTEXT_USER) {
				continue;
			}

			$recs = $DB->get_records('local_regcourseapproval', ['userid' => $user->id]);
			foreach ($recs as $rec) {
				$data = new \stdClass;
				$course = $DB->get_record('course', ['id' => $rec->course]);
				if ($course) {
					$data->course = $course->fullname;
				} else {
					$data->course = '';
				}
				$data->userid = $rec->userid;
				if ($rec->enrolled == 1) {
					$data->enrolled = 'Y';
				} else {
					$data->enrolled = 'N';
				}
				if ($rec->enrolleddate) {
					$data->enrolleddate = transform::datetime($rec->enrolleddate);
				} else {
					$data->enrolleddate = '';
				}
				if ($rec->unenrolledate) {
					$data->unenrolledate = transform::datetime($rec->unenrolledate);
				} else {
					$data->unenrolledate = '';
				}
				$data->email = $rec->email;

				writer::with_context($context)->export_data([get_string('privacy:regcourseapprovals', 'local_regcourseapproval'), get_string('privacy:regcourseapproval', 'local_regcourseapproval', $rec->id)], $data);
			}
		}

		return;
	}

	public static function delete_data_for_all_users_in_context(\context $context) {
		global $DB;

		if ($context->contextlevel == CONTEXT_USER) {
			$DB->delete_records('local_regcourseapproval', ['userid' => $context->instanceid]);
		}
		
		return;
	}

	public static function delete_data_for_user(approved_contextlist $contextlist) {
		global $DB;

		if (empty($contextlist->count())) {
			return;
		}

		$userid = $contextlist->get_user()->id;

		foreach ($contextlist->get_contexts() as $context) {
			if ($context->contextlevel == CONTEXT_USER) {
				$DB->delete_records('local_regcourseapproval', ['userid' => $context->instanceid]);
			}
		}
		
		return;
	}

	public static function get_users_in_context(userlist $userlist) {

		$context = $userlist->get_context();
		if ($context->contextlevel == CONTEXT_USER) {
			$userlist->add_user($context->instanceid);
		}

		return;
	}

	public static function delete_data_for_users(approved_userlist $userlist) {
		global $DB;

		$context = $userlist->get_context();
		if ($context->contextlevel == CONTEXT_USER) {
			$DB->delete_records('local_regcourseapproval', ['userid' => $context->instanceid]);
		}

		return;
	}
}
