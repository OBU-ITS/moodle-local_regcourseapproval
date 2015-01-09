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
 * User signup form for self registration with approval and course enrolment
 * (accessed from link in email sent out by admin/module leader)
 *
 * @package    regcourseapproval
 * @category   local
 * @copyright  2015, Oxford Brookes University {@link http://www.brookes.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once('../../config.php');
require_once('approval_signup_form.php');
require_once('loggedin_approval_signup_form.php');
require_once('auth_approval.php');
require_once('db_update.php');

global $USER;

$error_message = "";

// Get courseid from incoming parameter (for now this MUST be present since this is by invitation from special URL such as
// /local/regcourseapproval/signup.php?courseid=42
$course_id = required_param('courseid',PARAM_INT);

// and get optional approver user id from incoming request - optional and if not present we look up the module leader in request_enrolment
$approver_id = optional_param('uid', 0, PARAM_INT);

if (!$approver_id) {
    $approver_id = 0;
}

// And set this as the course
$COURSE = $DB->get_record('course', array('id'=>$course_id));

if (!$course_id || !$COURSE) {
    print_error('invalidcoursemodule');
}

// Call custom version of user_signup that handles the emailing (as if it was an authorisation plugin - but not site wide)
// and looks up the module leader to send to (if none, send to admin)
$authplugin = new auth_approval();

if (!$authplugin->can_signup()) {
    print_error('notlocalisederrormessage', 'error', '', 'Sorry, you may not use this page.');
}


$PAGE->set_url('/local/regcourseapproval/signup.php');
$PAGE->set_context(context_system::instance());

// If the user is already logged on (so already registered and confirmed) then we can just let them submit (hidden fields only)
// and send the email to the admin
// If the user is not logged in, then they will need to register (or log in) - so present the full form
// This is handled within approval_signup_form 

$logged_in_user = isloggedin();

if ($logged_in_user) {
    $mform_signup = new loggedin_approval_signup_form(null, array('course_id'=>$course_id, 'course_fullname'=>$COURSE->fullname, 'uid'=>$approver_id), 'post', '', array('autocomplete'=>'on'));    
} else {
    $mform_signup = new approval_signup_form(null, array('course_id'=>$course_id, 'course_fullname'=>$COURSE->fullname, 'uid'=>$approver_id), 'post', '', array('autocomplete'=>'on'));
}

if ($mform_signup->is_cancelled()) {
    redirect(get_login_url());
} 

else if (!$logged_in_user && $user_from_form = $mform_signup->get_data()) {
    $user_from_form->confirmed   = 0;
    $user_from_form->lang        = current_language();
    $user_from_form->firstaccess = time();
    $user_from_form->timecreated = time();
    $user_from_form->mnethostid  = $CFG->mnet_localhost_id;
    $user_from_form->secret      = random_string(15);
    $user_from_form->auth        = $CFG->registerauth;
   
    // registers the new user - checking in the db that we have a record of inviting them
    // put into an interim object so that if attempt fails (returns null), the error message page still has a valid (albeit guest) user
    $interim_user = $authplugin->user_signup($user_from_form, true);
    
    if ($interim_user) {
        
        $USER = $interim_user;
        
        // Update the invitation now we have the real user id
        $invitation = retrieve_invitation_by_email($course_id, $USER->email);
        record_user($invitation->id, $USER->id);        
        
        $authplugin->request_enrolment($USER, $approver_id, false, true); // sends enrolment request email
        exit; // won't do this if all well
    }
    else { 
        $error_message = get_string('unrecogniseduser', 'local_regcourseapproval');
    }

}
else if ($logged_in_user && $user_from_form = $mform_signup->get_data()) {

    // Update the invitation now we have the real user id - for which we have to use the actual user (not the one from form - though this is suboptimal...)
    // This checks the course id from the URL, and the approver if valid, to ensure the URL isn't tampered with
    $valid_invitation = exists_invitation($course_id, $USER->email, $approver_id); 
    
    if ($valid_invitation) {
             
        $invitation = retrieve_invitation_by_email($course_id, $USER->email);
        record_user($invitation->id, $USER->id);         
        
        $authplugin->request_enrolment($USER, $approver_id, true, true); // sends enrolment request email
        exit; // never reached, we hope        
    }
    else {
        $error_message = get_string('unrecogniseduser', 'local_regcourseapproval');
    }

}

$newaccount = get_string('newaccount');
$login      = get_string('login');

$PAGE->navbar->add($login);
$PAGE->navbar->add($newaccount);

$PAGE->set_title($newaccount);
$PAGE->set_heading($SITE->fullname);

echo $OUTPUT->header();

if ($error_message) {
    notice($error_message, "$CFG->wwwroot/index.php");
} else {
    $mform_signup->display();
}
echo $OUTPUT->footer();


