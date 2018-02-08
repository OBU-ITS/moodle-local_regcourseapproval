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
 * Display the invitation form
 * 
 * @package    regcourseapproval
 * @category   local
 * @copyright  2015, Oxford Brookes University {@link http://www.brookes.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("{$CFG->libdir}/formslib.php");


class invite_form extends moodleform {

    function definition() {
        $mform =& $this->_form;

        $data = new stdClass();
        $data->coursename = $this->_customdata['coursename'];
        $data->linkplaceholder = get_string('linkplaceholder', 'local_regcourseapproval');
		
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_NOTAGS);        
        $mform->setDefault('id',$this->_customdata['courseid']);
        
        $mform->addElement('header', '', $data->coursename);
        $mform->addElement('html', '<br>' . get_string('invite_formheader', 'local_regcourseapproval', $data) . '<br>');        

        $mform->addElement('text', 'emailsubject', get_string('subject_label', 'local_regcourseapproval'), 'maxsize="250" size="80"');
        $mform->setType('emailsubject', PARAM_NOTAGS);
        $mform->setDefault('emailsubject',get_string('default_invite_email_subject', 'local_regcourseapproval', $data));
        $mform->addRule('emailsubject', get_string('missingemailsubject', 'local_regcourseapproval'), 'required', null, 'server');           
        
        $mform->addElement('textarea', 'emailtext', get_string('text_label', 'local_regcourseapproval'), 'rows="10" cols="80"');
        $mform->setType('emailtext', PARAM_NOTAGS);
        $mform->setDefault('emailtext',get_string('default_invite_email_text', 'local_regcourseapproval', $data));
        $mform->addRule('emailtext', get_string('missingemailtext', 'local_regcourseapproval'), 'required', null, 'server');        
        
        $mform->addElement('text', 'emailapprover', get_string('approver_label', 'local_regcourseapproval'), 'maxsize="250" size="80"');        
        $mform->setType('emailapprover', PARAM_NOTAGS);
		
		$mform->addElement('select', 'auto_confirm', get_string('auto_confirm', 'local_regcourseapproval'), array(0, 1, 2, 3, 4), null);

        $mform->addElement('textarea', 'invitedemails', get_string('invitee_label_1', 'local_regcourseapproval'), 'rows="10" cols="80"');
        $mform->setType('invitedemails', PARAM_NOTAGS);
        // $mform->addRule('invitedemails', get_string('missingemailtext', 'local_regcourseapproval'), 'required', null, 'server');    
        
        $mform->addElement('filepicker', 'invitedemailsfile', get_string('invitee_label_2', 'local_regcourseapproval'));
        // $mform->addRule('invitedemailsfile', null, 'required');

        $this->add_action_buttons(false, get_string('invitation_button', 'local_regcourseapproval'));
    }
}