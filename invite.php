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
 * Invite list of users (email addresses) to register for/enrol on a course. Allows customisation of the invitation email.
 * Called from course context so require enrol capability for that course
 * 
 * @package    regcourseapproval
 * @category   local
 * @copyright  2015, Oxford Brookes University {@link http://www.brookes.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once('../../config.php');
require_once('invite_form.php');
require_once('db_update.php');

$course_id = required_param('id',PARAM_INT);

// And set this as the course
$COURSE = $DB->get_record('course', array('id'=>$course_id));

require_login();
$context = context_course::instance($COURSE->id, MUST_EXIST);
require_capability('enrol/manual:enrol', $context);


$PAGE->set_pagelayout('standard');
$PAGE->set_url('/local/regcourseapproval/invite.php');
$PAGE->set_context(context_system::instance());

$message = "";

$mform_invite = new invite_form(null, array('courseid'=>$COURSE->id, 'coursename'=>$COURSE->fullname), 'post', '', array('autocomplete'=>'on'));    

if ($mform_invite->is_cancelled()) {
    redirect(get_login_url());
} 
else if ($form_data = $mform_invite->get_data()) {
     
    // Sets values in form_data for 'id' (course id), 'emailtext', 'invitedemails' and 'invitedemailsfile'
    // So now we need to:
    // - build the email
    // - build the recipient list
    // - loop through recipients sending emails (up to some reasonable max)
    
    $cid = $form_data->id;
    $email_subject = $form_data->emailsubject;
    $email_text = $form_data->emailtext;
    $email_approver = $form_data->emailapprover;
    $auto_confirm = $form_data->auto_confirm;
    $recipients = $mform_invite->get_file_content('invitedemailsfile');
    
    // check that the approver email entered, if any, was valid - if not, report an error 
    // (as it will otherwise unintentionally fall back to admin or ML which might be an unexpected result)
       
    if ($email_approver) {
        $approver = $DB->get_record('user', array('email' => $email_approver)); 
        if (!$approver) {
            $message = get_string('invalidapproveremail', 'local_regcourseapproval');
        }
    }
    
    if (!$recipients) {
        $recipients = $form_data->invitedemails;
    }
    
    if (!$message) {
    
        $recipient_array = split_form_data($recipients);

        // Here is where the emails actually get sent
        $successes = send_invitation_emails($cid, $email_subject, $email_text, $email_approver, $auto_confirm, $recipient_array);
        $numberSent = count($successes);

        $message = "$numberSent email" . ($numberSent == 1 ? "" : "s") . " successfully transmitted.";
        $message .= "<br><br>Successfully sent to these recipients:<br>";

        foreach ($successes as $success) {
            $message .= "<br>$success";
        }
    }
}

$nav = get_string('invite_users_nav', 'local_regcourseapproval');
$title = get_string('invite_users_title', 'local_regcourseapproval');

$PAGE->navbar->add($nav);

$PAGE->set_title($title);
$PAGE->set_heading($SITE->fullname);

echo $OUTPUT->header();

if ($message) {
    notice($message, "$CFG->wwwroot/local/regcourseapproval/invite.php?id=$course_id");    
}
else {
    $mform_invite->display();
}
echo $OUTPUT->footer();


/** 
 * Send out emails to invitees containing the sign up link. Max this to something sensible (eg 250)
 * 
 * @param type $cid
 * @param type $email_subject
 * @param type $email_text
 * @param type $email_approver
 * @param type $auto_confirm
 * @param type $recipients
 * @return int number sent
 */

function send_invitation_emails($cid, $email_subject, $email_text, $email_approver, $auto_confirm, $recipients) {
    global $DB, $USER;
    
    $count = 0;
    $max = 250;
    
    $successes = array();
    
    // If an email approver is supplied (an email address) then get the user id. If not it stays blank, and will look up module leader when link returns   
    $id_approver = null;
    if ($email_approver) {
        $approver = $DB->get_record('user', array('email' => $email_approver)); 
        if ($approver) {
            $id_approver = $approver->id;
        }
    }    
    
    // Insert the appropriate link into the email (replacing the appropriate line)
    $email_text = insert_email_link($email_text, $cid, $id_approver);
    
    $email_text_html = text_to_html($email_text, false, false, true); 
    $supportuser = core_user::get_support_user();
 
    foreach ($recipients as $recipient) {
          
        // Moodle requires a User to send emails to, not an email addresss... So make a phoney user (dumb...)
        $recipientUser = new stdClass();
        $recipientUser->email = $recipient;
        $recipientUser->firstname = '';
        $recipientUser->lastname = '';
        $recipientUser->maildisplay = true;
        $recipientUser->mailformat = 1; // 0 (zero) text-only emails, 1 (one) for HTML/Text emails.
        $recipientUser->id = -99; // Moodle User ID. If it is for someone who is not a Moodle user, use an invalid ID like -99.
        $recipientUser->firstnamephonetic = '';
        $recipientUser->lastnamephonetic = '';
        $recipientUser->middlename = '';
        $recipientUser->alternatename = '';        
        
        if ($count < $max) {
            $ok = email_to_user($recipientUser, $supportuser, $email_subject, $email_text, $email_text_html, '', '', true, $USER->email);
                        
            if ($ok) {
                $successes[$count] = $recipient;
                $count++; 
                
                // and update the database with details of the invitation sent
                insert_invitation($cid, $recipient, $id_approver, $auto_confirm);        
            }
        }    
    } 
    
    return $successes;
}


/** 
 * Insert sign up link into email text
 * 
 * @param string $text
 * @param string courseid
 * @param int email approver id (may be null)
 * @return string
 */

function insert_email_link($text, $cid, $id_approver) {
   
    global $CFG, $DB;

    $url = "$CFG->wwwroot/local/regcourseapproval/signup.php";
    $url = str_replace('http:', 'https:', $url);
    
    // Always append the course id
    $url .= "?courseid=" . $cid;     
    
    // Append the approver if appropriate
    if ($id_approver) {
        $url .= "&uid=" . $id_approver;    
    }
    
    $search = get_string('linkplaceholder', 'local_regcourseapproval');

    // failsafe in case the user deleted the placeholder - make sure it gets into the email somewhere
    if (strpos($text, $search)) {
        $text = str_replace($search, $url, $text);
    }
    else {
        $text = $text . "\n\n" . $url;
    }
    
    return $text;
}


/** 
 * Split incoming data into an array on newline
 * 
 * @param string $rawcsv
 * @return array 
 */

function split_form_data($rawdata) {
    // Remove Windows \r\n new lines
    $rawdata = str_replace("\r\n", "\n", $rawdata);
    $datarows = array();
    $lines = explode("\n", $rawdata);
    foreach ($lines as $line) {
        $line = trim($line);
        if (!empty($line)) {
            $datarows[] = $line;
        }
    }

    return $datarows;
}

