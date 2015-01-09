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
 * Various db update utilities acting on the local_regcourseapproval table
 * 
 * @package    regcourseapproval
 * @category   local
 * @copyright  2015, Oxford Brookes University {@link http://www.brookes.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/** Insert a record of a course invitation
 * 
 * @param type $cid  the course id
 * @param type $user_email the email of the invitee
 * @param type $email_approver the id of the course approver (usually the leader)
 */

function insert_invitation($cid, $user_email, $email_approver) {
   
    global $DB;
    
    $record = new stdClass();
    $record->course         = $cid;
    $record->email          = $user_email;
    $record->approver       = $email_approver;
    $record->invitationdate = time();
    
    $DB->insert_record('local_regcourseapproval', $record, false);    
    
}

/** check that a given email/course combination have been invited to apply - if not we can reject them
 * 
 * @param type $cid
 * @param type $user_email
 * @param type $email_approver
 */

function exists_invitation($cid, $user_email, $approver_id=0) {
    
    global $DB;
    
    if ($approver_id) {
        $result = $DB->record_exists('local_regcourseapproval',array('course' => $cid, 'email' => $user_email, 'approver' => $approver_id));
    }
    else {
        $result = $DB->record_exists('local_regcourseapproval',array('course' => $cid, 'email' => $user_email));
    }
    

     
    return $result;
}



/** Retrieve a single invitation record given course id and user email
 *  Conceivably there may be more than one result (if someone has been invited twice, say) so make sure we ask for one(s) not enrolled yet
 * and if still more than one just take the latest one - can ignore earlier
 * 
 * @param type $course_id
 * @param type $user_email
 */

function retrieve_invitation_by_email($course_id, $user_email) {
    
    global $DB;
      
    $results = $DB->get_records('local_regcourseapproval',array('course' => $course_id, 'email' => $user_email, 'enrolled' => false));
   
    foreach ($results as $this_result) {
        $result = $this_result;
    }
    
    return $result;
       
}


/** Retrieve a single invitation record given course id and user id - allows us to get the id of the record
 * 
 * @param type $course_id
 * @param type $user_id
 */

function retrieve_invitation($course_id, $user_id) {
    
    global $DB;
      
    $result = $DB->get_record('local_regcourseapproval',array('course' => $course_id, 'userid' => $user_id));
       
    return $result;
       
}


/** Update a record with a real user id (ie after the user as logged in or registered)
 * 
 * @param type $inv_id the id of the invitation
 */

function record_user($inv_id, $user_id) {
   
    global $DB; 
    
    $record = new stdClass();
    $record->id             = $inv_id;
    $record->userid         = $user_id;
       
    $result = $DB->update_record('local_regcourseapproval', $record);
      
}


/** Update a record to reflect an enrolment having occurred (including the date)
 * 
 * @param type $inv_id the id of the invitation
 */

function record_enrolment($inv_id) {
   
    global $DB; 
    
    $record = new stdClass();
    $record->id             = $inv_id;
    $record->enrolled       = 1;
    $record->enrolleddate   = time();    
    
    $result = $DB->update_record('local_regcourseapproval', $record);
      
}

















