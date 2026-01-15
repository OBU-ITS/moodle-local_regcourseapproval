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
 * Language strings
 *
 * @package    regcourseapproval
 * @category   local
 * @copyright  2015, Oxford Brookes University {@link http://www.brookes.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['privacy:metadata:local_regcourseapproval'] = 'Information about an invitation to enroll on a course. This includes when a user has chosen to enroll or unenroll.';
$string['privacy:metadata:local_regcourseapproval:course'] = 'The course onto which the user has been invited.';
$string['privacy:metadata:local_regcourseapproval:userid'] = 'The invited user.';
$string['privacy:metadata:local_regcourseapproval:enrolled'] = 'Whether user is enrolled on the course or not.';
$string['privacy:metadata:local_regcourseapproval:enrolleddate'] = 'Date of user\'s enrolment (if any).';
$string['privacy:metadata:local_regcourseapproval:unenrolledate'] = 'Date of user\'s unenrolment (if any).';
$string['privacy:metadata:local_regcourseapproval:email'] = 'User\'s email address.';
$string['privacy:regcourseapprovals'] = 'Course registrations with approval';
$string['privacy:regcourseapproval'] = 'Registration {$a}';

$string['pluginname'] = 'regcourseapproval';
$string['title'] = 'Registration with approval';
$string['missingcourseid'] = 'Course ID not provided';
$string['requestaccount'] = 'Request my new account';

$string['defaultemail'] = 'moodle@brookes.ac.uk';

$string['regapprovaldescription'] = '<p>Self-registration with approval enables a user to create their own account via a \'Create new account\' button on the login page. The module leader then receives an email containing a secure link to a page where they can confirm the account. Future logins just check the username and password against the stored values in the Moodle database.</p><p>Note: In addition to enabling the plugin, self-registration with approval must also be selected from the self registration drop-down menu on the \'Manage authentication\' page.</p>';
$string['regapprovalnoemail'] = 'Tried to send you an email but failed!';
$string['regapprovalrecaptcha'] = 'Adds a visual/audio confirmation form element to the signup page for email self-registering users. This protects your site against spammers and contributes to a worthwhile cause. See http://www.google.com/recaptcha/learnmore for more details. <br /><em>PHP cURL extension is required.</em>';
$string['regapprovalrecaptcha_key'] = 'Enable reCAPTCHA element';

$string['formheader'] = 'To request enrolment in the course {$a->course_fullname}:<br> 
<ul>
<li>If you are <strong>already registered</strong> with Oxford Brookes University Moodle - please log in now using the \'Login\' link on the top right of this page.</li>
<li>If you are <strong>NOT already registered</strong> with Oxford Brookes University Moodle - please choose a new username and password and enter your other details below.</li>
</ul>
Then click \'Request my new account\' to submit your request to the course administrator.
';

$string['loggedin_formheader'] = 'To request enrolment in the course {$a->course_fullname} simply click \'Request my new account\' to submit your request to the course administrator.';

$string['default_invite_email_subject'] = 'Course invitation from Oxford Brookes University';

$string['default_invite_email_text'] = '
Hi,

Oxford Brookes University would like to invite you to enrol for the following course:

{$a->coursename}

To enrol/register on our Virtual Learning Environment, please click on the following link (or copy and paste it into your browser) and complete your registration details in the form you will find on that page:

{$a->linkplaceholder}

Thank you,

Oxford Brookes Moodle Admin
';

$string['subject_label'] = 'Subject of the email:';
$string['text_label'] = 'Text of the email:';
$string['approver_label'] = 'Approver\'s email (must be able to enrol students; defaults to module leader if blank):';
$string['auto_confirm'] = 'Auto confirm limit (weeks):';
$string['invitee_label_1'] = '<b>Either</b>: Invitees (enter new line separated list):';
$string['invitee_label_2'] = '<b>Or</b>: Invitees (enter file):';  

$string['duration_label'] = 'Duration of enrolment (days, default 365):'; 

$string['linkplaceholder'] = '[~link - do not edit or delete this line~]';

$string['regapprovaluserconfirmation'] = '
Hello {$a->firstname},

Welcome to Oxford Brookes University! Your account and enrolment has been approved.

Using the username and password you specified in the enrolment process, you will now be able to log in to our learning environment, Moodle, at
https://moodle.brookes.ac.uk/.

We\'re delighted you have joined us.

Thank you,

Oxford Brookes Moodle Admin

';

$string['existing_regapprovalconfirmation'] = '
Hi Module Leader,

An enrolment request has been received for course {$a->course_fullname} at {$a->sitename} for the following user:

{$a->userdata}

This user is already registered with Brookes Moodle. To confirm the enrolment, please first ensure you are logged in to Moodle and then go to this URL:

{$a->link}

Once you are logged in to Moodle you can click on the above link, or copy and paste it into your browser.

';
$string['regapprovalconfirmation'] = '
Hi Module Leader,

A registration and enrolment request has been received for course {$a->course_fullname} at {$a->sitename} for the following user:

{$a->userdata}

To confirm the registration and enrolment, please first ensure you are logged in to Moodle and then go to this URL:

{$a->link}

Once you are logged in to Moodle you can click on the above link, or copy and paste it into your browser.

';

$string['regapprovalconfirmsent'] = '<p>Your request has been received and is pending confirmation by the course administrator.
<br><br>Please expect either to receive a confirmation by email or to be contacted for further clarification in the next few days.
<br><br>You will not be able to access this course until you have received the confirmation email.</p>
';
$string['regapprovalconfirmationstudentsubject'] = '{$a} course enrolment';
$string['regapprovalconfirmationstudent'] = '
Thank you for your request to enrol on course {$a->course_fullname} at {$a->sitename}.

Your request is now pending confirmation by the course administrator, who will either send you a confirmation of your registration by email, or contact you for further clarification. 

You will not be able to login to the site (unless your registration has been previously approved) until you receive confirmation from the course administrator. As enrolment approval is a manual process this may take several days - if you have not heard back after this period, you can contact the administrator directly on {$a->leader_email}

Oxford Brookes Moodle Admin
';


$string['regapprovalconfirmationsubject'] = '{$a}: account confirmation';

$string['confirmedok'] = 'This user account has been confirmed.<br>';
$string['alreadyconfirmed'] = 'This username is already registered.<br>';
$string['userenrolled'] = 'This user has been successfuly enrolled on course {$a->course_fullname}.<br>';


$string['invite_users_nav'] = 'Invite external users';
$string['invite_users_title'] = 'Invite external users';

$string['invalidapproveremail'] = 'Please enter a valid email address for the registration approver (must be a registered Moodle user with sufficient privileges - leave blank to accept default)';
$string['missingemailtext'] = 'Please enter the text for the covering email';
$string['missingemailsubject'] = 'Please enter the subject for the covering email';

$string['invite_formheader'] = 'To invite external users to enrol on the course {$a->coursename} please modify the subject and text of the invitation email below as required (<strong>NB do not alter the \'do not edit\' line</strong>),
and either paste recipient email addresses (new line separated) into the first box, or drop a file of email addresses into the second (max 250 at one go).    

Then click \'Send invitations\' to send out the emails.
';

$string['invitation_button'] = 'Send invitations';
$string['confirm_button'] = 'Action registration/enrolment';

$string['confirm_title'] = 'External user registration/enrolment';
$string['confirm_nodata'] = 'No action to take.<br><br> To authorise users on this course please use the URL in the enrolment request email.';

$string['unrecogniseduser'] = 'Sorry, we do not have a record of a course invitation having been sent to the email address you have entered. If you think you should have received an invitation to this course please contact the course tutor.';


$string['teacherroleid'] = 'Teacher role ID';
$string['teacherroleiddesc'] = 'Role ID of the module leader (teacher-level) role';
$string['externalcohort'] = 'External cohort ID';
$string['externalcohortdesc'] = 'ID of the cohort to which all external users will be enrolled';
