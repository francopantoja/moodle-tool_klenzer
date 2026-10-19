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
 * Hook callbacks.
 *
 * @package     tool_klenzer
 * @copyright   2026 by IDEF https://idef21.com
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_klenzer;

/**
 * Hook callbacks for tool_klenzer.
 */
class hook_callbacks {
    /**
     * Shows a site admin a notice linking to the welcome page once, right after the plugin is installed.
     *
     * @param \core\hook\output\before_http_headers $hook
     */
    public static function before_http_headers(\core\hook\output\before_http_headers $hook): void {
        global $CFG, $PAGE;

        if (during_initial_install() || !empty($CFG->upgraderunning)) {
            return;
        }
        if (!get_config('tool_klenzer', 'welcomepending')) {
            return;
        }
        // Let the admin finish the upgrade and new settings screens first.
        if (in_array($PAGE->pagetype, ['admin-index', 'admin-upgradesettings'])) {
            return;
        }
        if (!is_siteadmin()) {
            return;
        }

        // Clear the flag so the notice is shown only once.
        unset_config('welcomepending', 'tool_klenzer');
        $link = \html_writer::link(
            new \moodle_url('/admin/tool/klenzer/welcome.php'),
            get_string('welcomenotice_link', 'tool_klenzer')
        );
        \core\notification::info(get_string('welcomenotice', 'tool_klenzer', $link));
    }
}
