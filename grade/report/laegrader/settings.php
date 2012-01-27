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

/// Add settings for this module to the $settings object (it's already defined)

$settings->add(new admin_setting_configcheckbox('grade_report_laegrader_accuratetotals', get_string('accuratetotals', 'gradereport_laegrader'), get_string('configaccuratetotals', 'gradereport_laegrader'), 2, PARAM_INT));
?>
