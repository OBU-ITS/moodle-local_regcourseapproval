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
 * User sign-up form for manual authorisation/enrolment - logged in version
 *
 * @package    regcourseapproval
 * @category   local
 * @copyright  2015, Oxford Brookes University {@link http://www.brookes.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');

class loggedin_approval_signup_form extends moodleform {
    function definition() {
        global $USER, $CFG;
              
        $mform = $this->_form;
        
        $data = new stdClass();
        $data->course_fullname = $this->_customdata['course_fullname'];
        $mform->addElement('header', '', $this->_customdata['course_fullname']);

        $mform->addElement('html', '<br>' . get_string('loggedin_formheader', 'local_regcourseapproval', $data) . '<br>');
        
        // Hidden value for approver id - optional
        $mform->addElement('hidden', 'uid');
        $mform->setType('uid', PARAM_NOTAGS);
        $mform->setDefault('uid',$this->_customdata['uid']);        
        
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_NOTAGS);
        $mform->setDefault('courseid',$this->_customdata['course_id']);
        // $mform->addRule('courseid', get_string('missingcourseid', 'local_regcourseapproval'), 'required', null, 'server');        

        $mform->addElement('hidden', 'username');
        $mform->setType('username', PARAM_NOTAGS);
        $mform->setDefault('username',$USER->username);
        // $mform->addRule('username', get_string('missingusername'), 'required', null, 'server');
        
        $mform->addElement('hidden', 'email');
        $mform->setType('email', PARAM_NOTAGS);
        $mform->setDefault('email',$USER->email);
        // $mform->addRule('email', get_string('missingemail'), 'required', null, 'server');        

        $mform->addElement('hidden', 'firstname');
        $mform->setType('firstname', PARAM_NOTAGS);
        $mform->setDefault('firstname',$USER->firstname);
        // $mform->addRule('firstname', get_string('missingfirstname'), 'required', null, 'server');  
        
        $mform->addElement('hidden', 'lastname');
        $mform->setType('lastname', PARAM_NOTAGS);
        $mform->setDefault('lastname',$USER->lastname);
        // $mform->addRule('lastname', get_string('missinglastname'), 'required', null, 'server');  
        
        $mform->addElement('hidden', 'city');
        $mform->setType('city', PARAM_NOTAGS);
        $mform->setDefault('city',$USER->city);
        // $mform->addRule('lastname', get_string('missingcity'), 'required', null, 'server'); 
     
        $mform->addElement('hidden', 'country');
        $mform->setType('country', PARAM_NOTAGS);
        $mform->setDefault('country',$USER->country);
        // $mform->addRule('country', get_string('missingcountry'), 'required', null, 'server'); 
        
        // buttons
        $this->add_action_buttons(true, get_string('requestaccount', 'local_regcourseapproval'));

    }

    function definition_after_data(){
        $mform = $this->_form;
        $mform->applyFilter('username', 'trim');
    }

    function validation($data, $files) {
        global $CFG, $DB;
        $errors = parent::validation($data, $files);

        // Use custom auth, not plugin
        // $authplugin = get_auth_plugin($CFG->registerauth);
        $authplugin = new auth_approval();
        
        // check courseid is present (should be in params of URL, we expect)
        if (empty($data['courseid'])) {
            $errors['courseid'] = get_string('missingemail', 'local_regcourseapproval');
        }

        if (! validate_email($data['email'])) {
            $errors['email'] = get_string('invalidemail');

        } 

        if (!isset($errors['email'])) {
            if ($err = email_is_not_allowed($data['email'])) {
                $errors['email'] = $err;
            }
        }

        $errmsg = '';

        return $errors;

    }

}
