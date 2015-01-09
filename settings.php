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
 * Config settings
 *
 * @package    regcourseapproval
 * @category   local
 * @copyright  2015, Oxford Brookes University {@link http://www.brookes.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $settings = new admin_settingpage(get_string('pluginname', 'local_regcourseapproval'), get_string('title', 'local_regcourseapproval'));
    $ADMIN->add('localplugins', $settings);
    $settings->add(new admin_setting_configtext('regcourseapproval/teacher_role', get_string('teacherroleid', 'local_regcourseapproval'),
                                                get_string('teacherroleiddesc', 'local_regcourseapproval'), '12',
                                                 PARAM_INT));
    $settings->add(new admin_setting_configtext('regcourseapproval/cohort_id',  get_string('externalcohort', 'local_regcourseapproval'),
                                                get_string('externalcohortdesc', 'local_regcourseapproval'), '1',
                                                 PARAM_INT));
    
}
    