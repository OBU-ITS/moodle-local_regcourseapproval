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
 * Authentication with module leader confirmation
 *
 * @package    regcourseapproval
 * @category   local
 * @copyright  2015, Oxford Brookes University {@link http://www.brookes.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir . '/authlib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once('db_update.php');
require_once('lib.php');

/**
 * Email authentication plugin.
 */

class auth_approval extends auth_plugin_base {

    /**
     * Constructor.
     */
    function auth_approval() {
        
        global $COURSE;
        
        // $this->authtype = 'regcourseapproval';
        // Moodle checks this on login and requires it to be currently enabled
        // get around this by pretending this is a manual auth
        $this->authtype = 'manual';
        $this->config = get_config('local/regcourseapproval');
        
        // Used later
        $this->courseid = $COURSE->id;
        $this->coursename = $COURSE->fullname;
                 
    }

    /**
     * Returns true if the username and password work and false if they are
     * wrong or don't exist.
     *
     * @param string $username The username
     * @param string $password The password
     * @return bool Authentication success or failure.
     */
    function user_login ($username, $password) {
        global $CFG, $DB;
        if ($user = $DB->get_record('user', array('username'=>$username, 'mnethostid'=>$CFG->mnet_localhost_id))) {
            return validate_internal_user_password($user, $password);
        }
        return false;
    }

    /**
     * Updates the user's password.
     *
     * @param  object  $user        User table object  (with system magic quotes)
     * @param  string  $newpassword Plaintext password (with system magic quotes)
     * @return boolean result
     *
     */
    function user_update_password($user, $newpassword) {
        $user = get_complete_user_data('id', $user->id);
        return update_internal_user_password($user, $newpassword);
    }

    /**
     * Can signup
     * 
     * @return boolean
     */
    
    function can_signup() {     
        return true;
    }

    /**
     * Sign up a new user ready for confirmation.
     * Password is passed in plaintext.
     *
     * @param object $user new user object
     * @param boolean $notify print notice with link and terminate
     */
    function user_signup($user, $notify=true) {
        
        global $CFG, $DB, $COURSE;
        require_once($CFG->dirroot.'/user/profile/lib.php');

        // check we have a record of inviting this user on this course - if we don't, we don't let em in
        // return a null user and let signup.php deal with this
        if (!exists_invitation($this->courseid, $user->email)) {
            return null;
        }
        
        $user->password = hash_internal_user_password($user->password);
        $user->auth = $this->authtype;
        $user->id = user_create_user($user, false);
		
        // Save any custom profile field information (unneeded step?)
        profile_save_data($user);        
       
        // Reload the user - why? Done in original so copied here
        $user = $DB->get_record('user', array('id'=>$user->id));
        
        return $user;
        
    }    
    
    
    /**
     * Assuming a signed up user, prepare and send email to request enrolment
     *
     * @param object $user new user object
     * @param id of approver, may be blank (defaults to module leader)
     * @param boolean if existing user
     * @param boolean $notify print notice with link and terminate
     */
    
    function request_enrolment($user, $approver_id, $is_existing_user, $notify=true) {

        global $CFG, $DB, $COURSE;        
        
        events_trigger('user_created', $user);

        // Now get the course's module leader ('teacher' in standard moodle - config settings determine the value to use)
        // or fall back on admin if not present
        // The course ID is passed in
        // This returns a single result - which is probably what we want
        // Use of UUID just stops Moodle giving errors if there is more than one result (unique ID)
                  
        $approver_user = null;
        
        // If passed as a param, use that (and if it isn't valid then caveat emptor - will fail on capability check)
        if ($approver_id) {
            $approver_user = $DB->get_record('user', array('id'=>$approver_id));
        }
        // Else get a module leader if possible
        else  {
            
            $results = $DB->get_records_sql("SELECT UUID() as uid, u.id "
                    . " FROM mdl_course c, mdl_context cx, mdl_role_assignments ra, mdl_user u "
                    . " where c.id = cx.instanceid and cx.contextlevel = '50' and cx.id = ra.contextid and ra.component != 'enrol_meta' "
                    . " and ra.userid = u.id and ra.roleid = " . get_config('regcourseapproval', 'teacher_role') . " and c.id = ?", array($this->courseid));     
            
            foreach ($results as $id => $result) {
                $approver_id = $result->id;
                $approver_user = $DB->get_record('user', array('id'=>$approver_id));
            } 
        }
        // If we didn't get anyone, fall back on default email (set from settings if present) 
        // - as the mailing function needs a user, we still need to fetch an admin user, but just override their email address if necessary
        if (!$approver_user) {
            
            $admins = get_admins();
            // Just get the first one
            foreach ($admins as $admin) {
                $approver_user = $admin;
                
                // Skip this check on local - don't want to send tests to live admin email
                $default_email = get_string('defaultemail', 'local_regcourseapproval');
                if ('Local' != $SITE->shortname && $default_email) {
                    $approver_user->email = $default_email;
                }
 
                break;              
            }
  
        }
        
        if (! $this->send_confirmation_email_student($user, $this->coursename, $approver_user, $is_existing_user)) {
            print_error('regapprovalnoemail','local_regcourseapproval');
        }        
        
        if (! $this->send_confirmation_email_leader($user, $this->coursename, $approver_user, $is_existing_user)) {
            print_error('regapprovalnoemail','local_regcourseapproval');
        }

        if ($notify) {
            global $CFG, $PAGE, $OUTPUT;
            $emailconfirm = get_string('emailconfirm');
            $PAGE->navbar->add($emailconfirm);
            $PAGE->set_title($emailconfirm);
            $PAGE->set_heading($PAGE->course->fullname);
            echo $OUTPUT->header();
            notice(get_string('regapprovalconfirmsent', 'local_regcourseapproval', $user->email), "$CFG->wwwroot/index.php");
        } else {
            return true;
        }
    }

    /**
     * Returns true if plugin allows confirming of new users.
     *
     * @return bool
     */
    function can_confirm() {
        return true;
    }

    /**
     * Confirm the new user as registered.
     *
     * @param string $username
     * @param string $confirmsecret
     */
    
    function user_confirm($username, $confirmsecret) {
        global $DB;
        $user = get_complete_user_data('username', $username);
     
        if (!empty($user)) {

            if ($user->confirmed) {              
                return AUTH_CONFIRM_ALREADY;
            } else if ($user->auth != $this->authtype) {
                return AUTH_CONFIRM_ERROR;
            } else if ($user->secret == $confirmsecret) {   // They have provided the secret key to get in 
                $DB->set_field("user", "confirmed", 1, array("id"=>$user->id));
                if ($user->firstaccess == 0) {
                    $DB->set_field("user", "firstaccess", time(), array("id"=>$user->id));
                }              
                return AUTH_CONFIRM_OK;
            }
        } else {          
            return AUTH_CONFIRM_ERROR;
        }
    }

    function prevent_local_passwords() {
        return false;
    }

    /**
     * Returns true if this authentication plugin is 'internal'.
     *
     * @return bool
     */
    
    function is_internal() {
        return true;
    }

    /**
     * Returns true if this authentication plugin can change the user's
     * password.
     *
     * @return bool
     */
    
    function can_change_password() {
        return true;
    }

    /**
     * Returns the URL for changing the user's pw, or empty if the default can
     * be used.
     *
     * @return moodle_url
     */
    
    function change_password_url() {
        return null; // use default internal method
    }

    /**
     * Returns true if plugin allows resetting of internal password.
     *
     * @return bool
     */
    
    function can_reset_password() {
        return true;
    }

    /**
     * Prints a form for configuring this authentication plugin.
     *
     * This function is called from admin/auth.php, and outputs a full page with
     * a form for configuring this plugin.
     *
     * @param array $page An object containing all the data for this page.
     */
    
    function config_form($config, $err, $user_fields) {
        include "config.html";
    }

    /**
     * Processes and stores configuration data for this authentication plugin.
     */
    
    function process_config($config) {
        // set to defaults if undefined
        if (!isset($config->recaptcha)) {
            $config->recaptcha = false;
        }

        // save settings
        set_config('recaptcha', $config->recaptcha, 'local/regcourseapproval');
        return true;
    }

    /**
     * Returns whether or not the captcha element is enabled, and the admin settings fulfil its requirements.
     * @return bool
     */
    
    function is_captcha_enabled() {
        global $CFG;
        return isset($CFG->recaptchapublickey) && isset($CFG->recaptchaprivatekey) && get_config("local/{$this->authtype}", 'recaptcha');
    }

    
    /**
     * Send email to the applicant (student) just to confirm request recevied. This will appear to be referred back to if they try to login before confirmation.
     *
     * @param user $user A {@link $USER} object
     * @param stirng coursename of the course  
     * @param user $approver_user the user we will send the email to
     * @aram boolean $is_existing_user if this is an already registered user (affects messaging)
     * @return bool Returns true if mail was sent OK to *any* admin and false if otherwise.
     */
    
    function send_confirmation_email_student($user, $coursename, $approver_user, $is_existing_user) {
        global $CFG, $DB;
    
        $return = false;
        
        $site = get_site();
        $supportuser = core_user::get_support_user();
    
        $data = new stdClass();
        $data->firstname = fullname($user);
        $data->sitename  = format_string($site->fullname);
        $data->admin     = generate_email_signoff();

        $data->userdata = '';
            
        $subject = get_string('regapprovalconfirmationstudentsubject', 'local_regcourseapproval', format_string($site->fullname));
    
        $username = urlencode($user->username);
        $username = str_replace('.', '%2E', $username); // prevent problems with trailing dots
        $data->course_fullname = $coursename;
        $data->leader_email = $approver_user->email;
        
        $message     = get_string('regapprovalconfirmationstudent', 'local_regcourseapproval', $data);
        $messagehtml = text_to_html(get_string('regapprovalconfirmationstudent', 'local_regcourseapproval', $data), false, false, true);

        $user->mailformat = 1;  // Always send HTML version as well

        $return |= email_to_user($user, $supportuser, $subject, $message, $messagehtml);

        return $return;
    }    
    
    
    /**
     * Send email to admin with confirmation text and activation link for
     * new user.
     *
     * @param user $user A {@link $USER} object
     * @param string coursename of the course  
     * @param user $approver_user the user we will send the email to
     * @aram boolean $is_existing_user if this is an already registered user (affects messaging)
     * @return bool Returns true if mail was sent OK to *any* admin and false if otherwise.
     */
    
    function send_confirmation_email_leader($user, $coursename, $approver_user, $is_existing_user) {
        global $CFG, $DB;
    
        $return = false;
        
        $site = get_site();
        $supportuser = core_user::get_support_user();
    
        $data = new stdClass();
        $data->firstname = fullname($user);
        $data->sitename  = format_string($site->fullname);
        $data->admin     = generate_email_signoff();

        $data->userdata = '';
        // $included_fields = array('id', 'auth', 'policyagreed', 'username', 'firstname', 'lastname', 'email', 'city', 'country');
        $included_fields = array('id', 'username', 'firstname', 'lastname', 'email', 'city', 'country');
        
        foreach(((array) $user) as $dataname => $datavalue) {
            if (in_array($dataname, $included_fields)) {
                $data->userdata	 .= $dataname . ': ' . $datavalue . PHP_EOL;
            }
        }

        // Add custom fields - none needed?
        // $data->userdata .= $this->list_custom_fields($user);
            
        $subject = get_string('regapprovalconfirmationsubject', 'local_regcourseapproval', format_string($site->fullname));
    
        $username = urlencode($user->username);
        $username = str_replace('.', '%2E', $username); // prevent problems with trailing dots
        $data->course_fullname = $coursename;
        $data->link  = $CFG->wwwroot .'/local/regcourseapproval/confirm.php?data='. $user->secret .'/'. $username . '/' . $this->courseid;
        if ($is_existing_user) {
            $message     = get_string('existing_regapprovalconfirmation', 'local_regcourseapproval', $data);
            $messagehtml = text_to_html(get_string('existing_regapprovalconfirmation', 'local_regcourseapproval', $data), false, false, true);
        } else {
            $message     = get_string('regapprovalconfirmation', 'local_regcourseapproval', $data);
            $messagehtml = text_to_html(get_string('regapprovalconfirmation', 'local_regcourseapproval', $data), false, false, true);         
        }
        
        $user->mailformat = 1;  // Always send HTML version as well

        if ($approver_user) {
            
            $return |= email_to_user($approver_user, $supportuser, "Module Leader: " . $subject, $message, $messagehtml);
        }

        return $return;
    }

    /**
     * Return an array with custom user properties. Customised for leader/course registration
     *
     * @param user $user A {@link $USER} object
     */
    
    function list_custom_fields($user) {
        global $CFG, $DB;

        $result = '';
        if ($fields = $DB->get_records('user_info_field')) {
            foreach($fields as $field) { 
                $fieldobj = new profile_field_base($field->id, $user->id);
                $result .= format_string($fieldobj->field->name.':') . ' ' . $fieldobj->display_data() . PHP_EOL;
            }
        }

        return $result;
    }
}
