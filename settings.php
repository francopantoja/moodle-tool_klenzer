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
 * Plugin administration pages are defined here.
 *
 * @package     tool_klenzer
 * @copyright   2026 by IDEF https://idef21.com
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Main tool page under "Site administration > Users > Accounts".
    $ADMIN->add('accounts', new admin_externalpage(
        'tool_klenzer_index',
        get_string('pluginname', 'tool_klenzer'),
        new moodle_url('/admin/tool/klenzer/index.php'),
        'tool/klenzer:view'
    ));

    // Plugin settings page.
    $settings = new admin_settingpage(
        'tool_klenzer_settings',
        get_string('settings', 'tool_klenzer')
    );

    if ($ADMIN->fulltree) {
        // Inactive users section.
        $settings->add(new admin_setting_heading(
            'tool_klenzer/inactiveusers',
            get_string('inactiveusers', 'tool_klenzer'),
            get_string('inactiveusers_desc', 'tool_klenzer')
        ));

        // Suspend accounts with no access during this number of years (0 = never).
        $options = [0 => get_string('never')];
        for ($i = 1; $i <= 10; $i++) {
            $options[$i] = get_string('numyears', 'moodle', $i);
        }
        $settings->add(new admin_setting_configselect(
            'tool_klenzer/suspendyears',
            get_string('suspendyears', 'tool_klenzer'),
            get_string('suspendyears_desc', 'tool_klenzer'),
            0,
            $options
        ));

        // Delete never-used accounts this number of months after creation (0 = never).
        $options = [0 => get_string('never')];
        for ($i = 1; $i <= 24; $i++) {
            $options[$i] = get_string('nummonths', 'moodle', $i);
        }
        $settings->add(new admin_setting_configselect(
            'tool_klenzer/deletemonths',
            get_string('deletemonths', 'tool_klenzer'),
            get_string('deletemonths_desc', 'tool_klenzer'),
            0,
            $options
        ));

        // Comma-separated list of email addresses to notify.
        $settings->add(new admin_setting_configtext(
            'tool_klenzer/notifyemails',
            get_string('notifyemails', 'tool_klenzer'),
            get_string('notifyemails_desc', 'tool_klenzer'),
            '',
            PARAM_TEXT
        ));

        // Live mode: when disabled (default), the process only simulates and never changes any account.
        $settings->add(new admin_setting_configcheckbox(
            'tool_klenzer/livemode',
            get_string('livemode', 'tool_klenzer'),
            get_string('livemode_desc', 'tool_klenzer'),
            0
        ));

        // Link to the index page, to launch the summary manually if the scheduled task fails.
        $settings->add(new admin_setting_description(
            'tool_klenzer/manuallaunch',
            get_string('manuallaunch', 'tool_klenzer'),
            html_writer::link(new moodle_url('/admin/tool/klenzer/index.php'), get_string('pluginname', 'tool_klenzer')) .
                '<br>' . get_string('manuallaunch_desc', 'tool_klenzer')
        ));

        // Link to download the log file stored in moodledata.
        $settings->add(new admin_setting_description(
            'tool_klenzer/logfile',
            get_string('logfile', 'tool_klenzer'),
            html_writer::link(new moodle_url('/admin/tool/klenzer/log.php'), 'klenzerlog.txt') .
                '<br>' . get_string('logfile_desc', 'tool_klenzer')
        ));
    }

    // Link the settings page under "Site administration > Users > Accounts".
    $ADMIN->add('accounts', $settings);
}
