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
 * Confirm self registered user. 
 * NOTE: based on original 'login/config.php' by Martin Dougiamas and modified by 2012 Felipe Carasso http://carassonet.org
 *
 * @package    regcourseapproval
 * @category   local
 * @copyright  2015, Oxford Brookes University {@link http://www.brookes.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once('../../config.php');
require_once('../../cohort/lib.php'); // for the method for adding user to a cohort
require_once('lib.php');
require_once('auth_approval.php');
require_once($CFG->libdir.'/authlib.php');
require_once('confirm_form.php');
require_once('db_update.php');

global $COURSE;

require_login();
// see also require_capability below

// Get data from original incoming URL (not from the confirmed form submission, though data is the same)
$data = optional_param('data', '', PARAM_RAW);  // Formatted as:  secret/username/course_id

$form_submitted = false;
$valid_data = false;

$PAGE->set_url('/auth/regapproval/confirm.php');
$PAGE->set_context(context_system::instance());

$usersecret = "";
$username = "";
$useremail = "";
$courseid =  "";  
$coursename =  ""; 
$user_confirmed = false;
$user_enrolled = false;
$duration = 365; // number of days for enrolment to last

$response_message = "";

$form_data = null;
$mform_confirm = null;
$user_applicant = null; // this is the user to be enrolled - not called $user so as not to confuse with $USER
    
// Use custom version of user_signup that handles the confirmation
$authplugin = new auth_approval(); 

// Parse and add to form the data from the original incoming URL
if ($data) {
    
    $dataelements = explode('/', $data, 3); // secret / username / courseid
    $usersecret = $dataelements[0];
    $username   = $dataelements[1];
    $courseid  =  $dataelements[2];
	
    if ($courseid) {
        $COURSE = $DB->get_record('course', array('id'=>$courseid));
    }
    if ($username) {
        $user_applicant = get_complete_user_data('username', $username); 
    }
    
    $coursename = $COURSE->fullname;
    $useremail = $user_applicant->email;
    $user_confirmed = $user_applicant->confirmed; 
    
    // $context = get_context_instance(CONTEXT_COURSE, $courseid, MUST_EXIST);
    $context = context_course::instance($COURSE->id, MUST_EXIST);
    $user_enrolled = is_enrolled($context, $user_applicant->id, '', true);
    
    $valid_data = true;
    
}

if ($courseid) {
	$PAGE->set_context(context_course::instance($courseid));
	$PAGE->set_pagelayout('course');
} else {
	$PAGE->set_context(context_system::instance());
	$PAGE->set_pagelayout('standard');
}

// Set up the form - either a real form to show or a dummy one to gather params (which does seem dummy...)
$mform_confirm = new confirm_form(null, array('courseid'=>$courseid, 'coursename'=>$coursename, 'usersecret'=>$usersecret, 'username'=>$username, 'useremail'=>$useremail, 'confirmed'=>$user_confirmed, 'enrolled'=>$user_enrolled), 'post', '', array('autocomplete'=>'on'));


// Or process results from the form (assignment in conditional I don't like but seems to be the style)
if ($form_data = $mform_confirm->get_data()) {
    
    $form_submitted = true;
    $valid_data = true;

    $form_data = $mform_confirm->get_data();
    
    $usersecret = $form_data->usersecret;    
    $username = $form_data->username;
    $courseid = $form_data->courseid;   
    
    $duration = $form_data->duration;   

}

// Not allowed (error)
else if (!$authplugin->can_confirm()) {
    print_error('cannotusepage2');
}

// Form submitted - so gather params and action them (ie enrol the user etc)
// 
if ($form_submitted) {

    // Do this in two stages
    // - register the users
    // - enrol the user on the given course (and cohort)
        
    // Get and remember the course
    $COURSE = $DB->get_record('course', array('id'=>$courseid));
    
    // Require enrolment capability for this course
    $context = context_course::instance($COURSE->id, MUST_EXIST);
    require_capability('enrol/manual:enrol', $context);
    
    // Set the user status to confirmed (and get back a response for messaging purposes)
    // NB that usersecret can be blank if the user is already registered - that's OK as all we need to do is enrol them
    $confirmed = $authplugin->user_confirm($username, $usersecret, $courseid);
    
    if ($confirmed == AUTH_CONFIRM_OK) {
        $response_message = get_string('confirmedok', 'local_regcourseapproval');  
    } 
    
    if ($confirmed == AUTH_CONFIRM_ALREADY) {
        $response_message = get_string('alreadyconfirmed', 'local_regcourseapproval');    
    }    
    
    // (Re)fetch the user (otiose, but double sure - and fail if it fails)
    if (!$user_applicant = get_complete_user_data('username', $username)) {
        $ok = false;
        print_error('cannotfinduser', '', '', s($username));
    }    
    
    // So now (iff we have a course ID, which for now we always will) we also enrol the user in that course
    // we use only manual enrol plugin here, if it is disabled (which it won't be) no enrol is done (so it had better be enabled)
    $manualcache    = array(); 

    if (enrol_is_enabled('manual')) {
        $manual = enrol_get_plugin('manual');
    } else {
        $manual = NULL;
    }        
        
    if (!isset($manualcache[$courseid])) {
        $manualcache[$courseid] = false;
        if ($manual) {
            if ($instances = enrol_get_instances($courseid, false)) {
                foreach ($instances as $instance) {
                    if ($instance->enrol === 'manual') {
                        $manualcache[$courseid] = $instance;
                        break;
                    }
                }
            }
        }
    }
      
   
    
    if ($manual and $manualcache[$courseid]) {

        // use the default role from manual enrol plugin (student presumably)
        $rid = $manualcache[$courseid]->roleid;            

        $today = time();
        $today = make_timestamp(date('Y', $today), date('m', $today), date('d', $today), 0, 0, 0); 
        
        // Use passed in duration, or if no value supplied default to 365
        if (!$duration) {
            $duration = 365;
        }
        
        $duration = $duration*24*60*60; // convert days to seconds
        $timeend = $today + $duration;

        // And do the enrolling - the enrol_user method itself checks for existing enrolment (and only updates the date if necessary)
        $manual->enrol_user($manualcache[$courseid], $user_applicant->id, $rid, $today, $timeend);

        // Now update the invitation record to show this is enrolled and when
        // Two steps, first get the ID then update (inefficient but lets us use Moodle's query functions)
        $invitation = retrieve_invitation($courseid, $user_applicant->id);
        record_enrolment($invitation->id);
        
        // Then also add to a cohort - the ID of the cohort to use is defined in settings
               
        $cohort = $DB->get_record('cohort', array('id'=>get_config('regcourseapproval', 'cohort_id')));
        if (!$DB->record_exists('cohort_members', array('cohortid'=>$cohort->id, 'userid'=>$user_applicant->id))) {
            cohort_add_member($cohort->id, $user_applicant->id);            
        }
        
        $data = new stdClass();
        $data->course_fullname = $COURSE->fullname;
        
        $response_message .= get_string('userenrolled', 'local_regcourseapproval', $data);
    }      
            
    $actioned = true;
    
    // And send a confirmation email to the user
    send_confirmation_email_user($user_applicant);    
         
}



// Output the page
//

$PAGE->navbar->add(get_string("confirm_title", "local_regcourseapproval"));
$PAGE->set_title(get_string("confirm_title", "local_regcourseapproval"));
$PAGE->set_heading($SITE->fullname);
echo $OUTPUT->header();

if ($valid_data) {
    if ($form_submitted) {
        echo $OUTPUT->box_start('generalbox centerpara boxwidthnormal boxaligncenter');
        echo "<h3>User: ".fullname($user_applicant)."</h3>\n";
        echo "<p>".$response_message."</p>\n";
        echo $OUTPUT->box_end();
    } else {
        $mform_confirm->display();
    }
} else {
    echo "<p>".get_string("confirm_nodata", "local_regcourseapproval")."</p>\n";
}
echo $OUTPUT->footer();



/**
 * Send the confirmation email
 * 
 * @global type $CFG
 * @global type $USER
 * @global type $COURSE
 * @param type $user_applicant the user being confirmed 
 * @return void
 */

function send_confirmation_email_user($user_applicant) {
    global $CFG, $USER, $COURSE;

    $site = get_site();
    // $supportuser = generate_email_supportuser();
    $supportuser = core_user::get_support_user();

    $data = new stdClass();
    $data->firstname = fullname($user_applicant);
    $data->sitename  = format_string($site->fullname);
    $data->admin     = generate_email_signoff();

    $subject = get_string('regapprovalconfirmationsubject', 'local_regcourseapproval', format_string($site->fullname));

    $username = urlencode($user_applicant->username);
    $username = str_replace('.', '%2E', $username); // prevent problems with trailing dots
    $data->link  = $CFG->wwwroot;
    $data->username = $username;
    $data->course_fullname = $COURSE->fullname;
    $message     = get_string('regapprovaluserconfirmation', 'local_regcourseapproval', $data);
    $messagehtml = text_to_html(get_string('regapprovaluserconfirmation', 'local_regcourseapproval', $data), false, false, true);

    $user_applicant->mailformat = 1;  // Always send HTML version as well

    //directly email rather than using the messaging system to ensure its not routed to a popup or jabber

    return email_to_user($user_applicant, $supportuser, $subject, $message, $messagehtml, '', '', true, $USER->email);
}
