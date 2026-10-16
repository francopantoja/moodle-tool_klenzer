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
 * Scheduled task that processes the inactive users.
 *
 * @package     tool_klenzer
 * @copyright   2026 by IDEF https://idef21.com
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_klenzer\task;

use tool_klenzer\local\executor;
use tool_klenzer\local\logger;

/**
 * Suspends and deletes the inactive users, then logs and emails the summary.
 */
class process_inactive_users extends \core\task\scheduled_task {

    /**
     * Returns the task name shown in the admin interface.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('taskprocess', 'tool_klenzer');
    }

    /**
     * Runs the task.
     */
    public function execute(): void {
        $outcome = executor::process();

        mtrace(get_string('executed', 'tool_klenzer', $outcome['counts']));
        if (executor::is_dry_run()) {
            mtrace(get_string('dryrun', 'tool_klenzer'));
        }

        if ($outcome['logged']) {
            mtrace('Summary appended to ' . logger::get_path());
        } else {
            mtrace('Could not write to ' . logger::get_path());
        }

        if (!$outcome['results']) {
            mtrace(get_string('emailnotsent', 'tool_klenzer'));
        }
        foreach ($outcome['results'] as $email => $ok) {
            mtrace(get_string($ok ? 'emailsent' : 'emailfailed', 'tool_klenzer', $email));
        }
    }
}
