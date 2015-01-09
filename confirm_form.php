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
 * Show form for user registration confirmation
 *
 * @package    regcourseapproval
 * @category   local
 * @copyright  2015, Oxford Brookes University {@link http://www.brookes.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("{$CFG->libdir}/formslib.php");


class confirm_form extends moodleform {

    function definition() {
        $mform =& $this->_form;

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_NOTAGS);        
        $mform->setDefault('courseid',$this->_customdata['courseid']);        
        
        $mform->addElement('hidden', 'username');
        $mform->setType('username', PARAM_NOTAGS);        
        $mform->setDefault('username',$this->_customdata['username']);     

        $mform->addElement('hidden', 'usersecret');
        $mform->setType('usersecret', PARAM_NOTAGS);        
        $mform->setDefault('usersecret',$this->_customdata['usersecret']);
        
        $mform->addElement('html', 'Course: ' . $this->_customdata['coursename'] . '<br>');         
        $mform->addElement('html', 'Username: ' . $this->_customdata['username'] . '<br>'); 
        $mform->addElement('html', 'Email: ' . $this->_customdata['useremail'] . '<br>');
        
        if ($this->_customdata['confirmed'] && $this->_customdata['enrolled']) {
             $mform->addElement('html', 'User is already registered and enrolled on this course (no further action necessary).' . '<br>');
        }        
        else if ($this->_customdata['confirmed']) {
            $mform->addElement('html', 'User is already registered (but not yet enrolled).' . '<br>');
        }
        
        if (!($this->_customdata['confirmed'] && $this->_customdata['enrolled'])) {
            // A field to allow admin to set the duration of the enrolment
            $mform->addElement('text', 'duration', get_string('duration_label', 'local_regcourseapproval'), 'maxlength="4" size="4"');
            $mform->setType('duration', PARAM_NOTAGS);            
            // And the button to make it so
            $this->add_action_buttons(true, get_string('confirm_button', 'local_regcourseapproval'));
        }

    }

}