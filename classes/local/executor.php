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
 * Executes the suspension and deletion of inactive accounts.
 *
 * @package     tool_klenzer
 * @copyright   2026 by IDEF https://idef21.com
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_klenzer\local;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/user/lib.php');

/**
 * Suspends and deletes accounts through the Moodle user API and records each action.
 */
class executor {

    /** State stored for suspended users. */
    const STATE_SUSPENDED = 's';

    /** State stored for deleted users. */
    const STATE_DELETED = 'd';

    /**
     * Simulation mode: users are recorded but never suspended or deleted.
     *
     * Active unless the "Live mode" setting is enabled.
     *
     * @return bool
     */
    public static function is_dry_run(): bool {
        return !get_config('tool_klenzer', 'livemode');
    }

    /**
     * Full process: builds the summary, runs both actions, then logs and emails the summary.
     *
     * Used by the scheduled task and by the Run button of the index page.
     *
     * @return array ['counts' => run() result, 'logged' => bool, 'results' => [email => bool]]
     */
    public static function process(): array {
        // Built before running, so it lists the users about to be processed.
        $summary = notifier::build_summary();

        $counts = self::run();
        $summary .= "\n" . notifier::SEPARATOR . "\n" . get_string('executed', 'tool_klenzer', $counts);
        if (self::is_dry_run()) {
            $summary .= "\n" . get_string('dryrun', 'tool_klenzer');
        }

        return [
            'counts' => $counts,
            'logged' => logger::append($summary),
            'results' => notifier::send_summary($summary),
        ];
    }

    /**
     * Runs both actions with the current settings.
     *
     * @return array ['suspended' => int, 'deleted' => int]
     */
    public static function run(): array {
        return [
            'suspended' => self::suspend_users(),
            'deleted' => self::delete_users(),
        ];
    }

    /**
     * Suspends the inactive users and closes their sessions.
     *
     * @return int Number of suspended users.
     */
    public static function suspend_users(): int {
        [, $users] = inactive_users::get_users_to_suspend();
        $count = 0;
        foreach ($users as $user) {
            if (!self::is_dry_run()) {
                user_update_user((object) ['id' => $user->id, 'suspended' => 1], false, true);
                \core\session\manager::destroy_user_sessions($user->id);
            }
            self::record($user->id, self::STATE_SUSPENDED);
            $count++;
        }
        return $count;
    }

    /**
     * Deletes the users who never accessed the site.
     *
     * @return int Number of deleted users.
     */
    public static function delete_users(): int {
        global $DB;

        [, $users] = inactive_users::get_users_to_delete();
        $count = 0;
        foreach ($users as $user) {
            if (self::is_dry_run()) {
                self::record($user->id, self::STATE_DELETED);
                $count++;
                continue;
            }
            // delete_user() needs the full user record.
            $fulluser = $DB->get_record('user', ['id' => $user->id]);
            if ($fulluser && delete_user($fulluser)) {
                self::record($user->id, self::STATE_DELETED);
                $count++;
            }
        }
        return $count;
    }

    /**
     * Stores one action in the plugin table.
     *
     * @param int $userid
     * @param string $state One of the STATE_* constants.
     */
    protected static function record(int $userid, string $state): void {
        global $DB;

        $DB->insert_record('tool_klenzer', (object) [
            'userid' => $userid,
            'state' => $state,
            'timecreated' => time(),
        ]);
    }
}
