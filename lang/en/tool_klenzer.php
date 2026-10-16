<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * English language strings.
 *
 * @package     tool_klenzer
 * @copyright   2026 by IDEF https://idef21.com
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['accept'] = 'I accept';
$string['byidef'] = 'by IDEF Moodle Partner';
$string['confirmexecute'] = 'Deletion may be irreversible and is the sole responsibility of this Moodle site. It is recommended to have the corresponding backups available.';
$string['cutoffdate'] = 'Cutoff date: {$a}';
$string['deletemonths'] = 'Delete after';
$string['deletemonths_desc'] = 'Delete accounts that have never accessed the site once this time has passed since they were created.';
$string['dryrun'] = 'Simulation mode: no user has actually been suspended or deleted.';
$string['emailfailed'] = 'Email could not be sent to {$a}. Check the outgoing mail configuration.';
$string['emailnotsent'] = 'Summary email not sent: there are no valid notification recipients.';
$string['emailsent'] = 'Email sent to {$a}';
$string['emailsubject'] = '{$a}: Klenzer inactive users summary';
$string['execute'] = 'Run';
$string['executed'] = 'Process completed: {$a->suspended} user(s) suspended and {$a->deleted} user(s) deleted.';
$string['inactiveusers'] = 'Inactive users';
$string['inactiveusers_desc'] = 'Automatically suspend accounts that are no longer used and delete accounts that have never been used.';
$string['klenzer:view'] = 'View Klenzer tool';
$string['livemode'] = 'Live mode';
$string['livemode_desc'] = '<strong>Warning:</strong> when enabled, accounts are actually suspended and deleted. Deletion may be irreversible and is the sole responsibility of this Moodle site. It is recommended to have the corresponding backups available. When disabled, Klenzer only simulates: it records, logs and emails the summary without changing any account.';
$string['logfile'] = 'Log file';
$string['logfile_desc'] = 'Each summary is appended to this file in moodledata (tool_klenzer/klenzerlog.txt).';
$string['lognotwritten'] = 'The summary could not be written to the log file. Check the permissions of the site data directory.';
$string['manuallaunch'] = 'Manual launch';
$string['manuallaunch_desc'] = 'A daily scheduled task processes the inactive users, then logs and emails the summary. If the task fails, it can be launched manually with the Run button of the Klenzer page.';
$string['nolog'] ='The log file does not exist yet.';
$string['norecords'] = 'No users have been processed yet.';
$string['notifyemails'] = 'Notification recipients';
$string['notifyemails_desc'] = 'Email addresses to notify when accounts are suspended or deleted. Separate multiple addresses with commas.';
$string['pluginname'] = 'Klenzer';
$string['previewonly'] = 'Preview only: these accounts would be affected with the current settings. No changes have been made.';
$string['privacy:metadata:notifyemails'] = 'A summary of the affected accounts is emailed to the configured notification recipients and appended to a log file in the site data directory.';
$string['privacy:metadata:notifyemails:email'] = 'The email address of the affected user.';
$string['privacy:metadata:notifyemails:fullname'] = 'The full name of the affected user.';
$string['privacy:metadata:notifyemails:lastaccess'] = 'The last time the affected user accessed the site.';
$string['privacy:metadata:tool_klenzer'] = 'Records of the accounts suspended or deleted by Klenzer.';
$string['privacy:metadata:tool_klenzer:state'] = 'The action applied to the account: s = suspended, d = deleted.';
$string['privacy:metadata:tool_klenzer:timecreated'] = 'The time the action was applied.';
$string['privacy:metadata:tool_klenzer:userid'] = 'The ID of the affected user.';
$string['records'] = 'Processed users';
$string['settingnever'] ='Set to "Never": no accounts will be affected.';
$string['settings'] = 'Klenzer settings';
$string['showingxofy'] = 'Showing {$a->shown} of {$a->total} users.';
$string['state'] = 'State';
$string['statedeleted'] = 'Deleted';
$string['statesuspended'] = 'Suspended';
$string['summarydate'] = 'Date and time: {$a}';
$string['suspendyears'] = 'Suspend after';
$string['suspendyears_desc'] = 'Suspend accounts that have not accessed the site during this time.';
$string['taskprocess'] = 'Process inactive users';
$string['userstodelete'] = 'Users to delete (never accessed)';
$string['userstosuspend'] = 'Users to suspend (inactive)';
$string['welcome'] = 'Welcome to Klenzer';
$string['welcome_desc'] = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.';
$string['welcomenotice'] = 'Klenzer has been installed. {$a}';
$string['welcomenotice_link'] = 'View the welcome page';
