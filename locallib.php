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
 * locallib, shared functions
 * 
 * @package    regcourseapproval
 * @category   local
 * @copyright  2018, Oxford Brookes University {@link http://www.brookes.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once('../../config.php');
require_once('../../cohort/lib.php'); // for the method for adding user to a cohort
require_once('db_update.php');

function confirm_user($username) {
	global $DB;

	$user = get_complete_user_data('username', $username);
     
	if (!empty($user)) {
		if (!$user->confirmed) { 
			$DB->set_field("user", "confirmed", 1, array("id" => $user->id));
			if ($user->firstaccess == 0) {
				$DB->set_field("user", "firstaccess", time(), array("id" => $user->id));
			}              
		}
	}
	return;
}

function enrol_user($username, $course_id, $duration = 365) {
	global $DB;

	// (Re)fetch the user (otiose, but double sure - and fail if it fails)
	if (!$user = get_complete_user_data('username', $username)) {
		$ok = false;
		print_error('cannotfinduser', '', '', s($username));
	}    

	// We use only manual enrol plugin here, if it is disabled (which it won't be) no enrol is done (so it had better be enabled)
	$manualcache = array(); 

	if (enrol_is_enabled('manual')) {
		$manual = enrol_get_plugin('manual');
	} else {
		$manual = NULL;
	}        

	if (!isset($manualcache[$course_id])) {
		$manualcache[$course_id] = false;
		if ($manual) {
			if ($instances = enrol_get_instances($course_id, false)) {
				foreach ($instances as $instance) {
					if ($instance->enrol === 'manual') {
						$manualcache[$course_id] = $instance;
						break;
					}
				}
			}
		}
	}

	if ($manual and $manualcache[$course_id]) {

		// use the default role from manual enrol plugin (student presumably)
		$rid = $manualcache[$course_id]->roleid;            

		$today = time();
		$today = make_timestamp(date('Y', $today), date('m', $today), date('d', $today), 0, 0, 0); 

		$timeend = $today + ($duration * 86400); // Convert days to seconds

		// And do the enrolling - the enrol_user method itself checks for existing enrolment (and only updates the date if necessary)
		$manual->enrol_user($manualcache[$course_id], $user->id, $rid, $today, $timeend);

		// Now update the invitation record to show this is enrolled and when
		// Two steps, first get the ID then update (inefficient but lets us use Moodle's query functions)
		$invitation = retrieve_invitation($course_id, $user->id);
		record_enrolment($invitation->id);

		// Then also add to a cohort - the ID of the cohort to use is defined in settings

		$cohort = $DB->get_record('cohort', array('id' => get_config('regcourseapproval', 'cohort_id')));
		if (!$DB->record_exists('cohort_members', array('cohortid' => $cohort->id, 'userid' => $user->id))) {
			cohort_add_member($cohort->id, $user->id);            
		}

		$course = $DB->get_record('course', array('id' => $course_id));
		$data = new stdClass();
		$data->course_fullname = $course->fullname;
	}      

	// And send a confirmation email to the user
	email_user($user, $course->fullname);
}

function email_user($user, $course_fullname) {
	global $CFG;

	$site = get_site();
	$supportuser = core_user::get_support_user();

	$data = new stdClass();
	$data->firstname = fullname($user);
	$data->sitename = format_string($site->fullname);
	$data->admin = generate_email_signoff();

	$subject = get_string('regapprovalconfirmationsubject', 'local_regcourseapproval', format_string($site->fullname));

	$username = str_replace('.', '%2E', urlencode($user->username)); // prevent problems with trailing dots
	$data->link  = $CFG->wwwroot;
	$data->username = $username;
	$data->course_fullname = $course_fullname;
	$message = get_string('regapprovaluserconfirmation', 'local_regcourseapproval', $data);
	$messagehtml = text_to_html(get_string('regapprovaluserconfirmation', 'local_regcourseapproval', $data), false, false, true);

	$user->mailformat = 1;  // Always send HTML version as well

	//directly email rather than using the messaging system to ensure its not routed to a popup or jabber

	return email_to_user($user, $supportuser, $subject, $message, $messagehtml, '', '', true, $user->email);
}
