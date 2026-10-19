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
 * Finder for inactive user accounts.
 *
 * @package     tool_klenzer
 * @copyright   2026 by IDEF https://idef21.com
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_klenzer\local;

/**
 * Finds the accounts that would be suspended or deleted according to the plugin settings.
 *
 * Read only: this class never modifies the database.
 */
class inactive_users {
    /**
     * Returns the cutoff timestamp for suspension, or null when set to "Never".
     *
     * @return int|null
     */
    public static function get_suspend_cutoff(): ?int {
        $years = (int) get_config('tool_klenzer', 'suspendyears');
        if ($years <= 0) {
            return null;
        }
        return (new \DateTimeImmutable())->modify("-{$years} years")->getTimestamp();
    }

    /**
     * Returns the cutoff timestamp for deletion, or null when set to "Never".
     *
     * @return int|null
     */
    public static function get_delete_cutoff(): ?int {
        $months = (int) get_config('tool_klenzer', 'deletemonths');
        if ($months <= 0) {
            return null;
        }
        return (new \DateTimeImmutable())->modify("-{$months} months")->getTimestamp();
    }

    /**
     * Users who accessed the site at least once, but not since the suspension cutoff.
     *
     * @param int $limitfrom
     * @param int $limitnum
     * @return array [total count, records]
     */
    public static function get_users_to_suspend(int $limitfrom = 0, int $limitnum = 0): array {
        $cutoff = self::get_suspend_cutoff();
        if ($cutoff === null) {
            return [0, []];
        }
        [$where, $params] = self::get_base_where();
        $where .= ' AND u.suspended = 0 AND u.lastaccess > 0 AND u.lastaccess < :cutoff';
        $params['cutoff'] = $cutoff;
        return self::fetch($where, $params, 'u.lastaccess ASC', $limitfrom, $limitnum);
    }

    /**
     * Users who never accessed the site and were created before the deletion cutoff.
     *
     * @param int $limitfrom
     * @param int $limitnum
     * @return array [total count, records]
     */
    public static function get_users_to_delete(int $limitfrom = 0, int $limitnum = 0): array {
        $cutoff = self::get_delete_cutoff();
        if ($cutoff === null) {
            return [0, []];
        }
        [$where, $params] = self::get_base_where();
        $where .= ' AND u.firstaccess = 0 AND u.lastaccess = 0 AND u.timecreated < :cutoff';
        $params['cutoff'] = $cutoff;
        return self::fetch($where, $params, 'u.timecreated ASC', $limitfrom, $limitnum);
    }

    /**
     * Conditions shared by both lists: real local accounts, never guest or site admins.
     *
     * @return array [sql, params]
     */
    protected static function get_base_where(): array {
        global $CFG, $DB;

        $excluded = array_filter(array_map('intval', explode(',', $CFG->siteadmins)));
        $excluded[] = (int) $CFG->siteguest;
        [$notin, $params] = $DB->get_in_or_equal($excluded, SQL_PARAMS_NAMED, 'excl', false);

        $where = "u.deleted = 0 AND u.mnethostid = :mnethostid AND u.id {$notin}";
        $params['mnethostid'] = $CFG->mnet_localhost_id;
        return [$where, $params];
    }

    /**
     * Runs the count and the paged select for the given conditions.
     *
     * @param string $where
     * @param array $params
     * @param string $sort
     * @param int $limitfrom
     * @param int $limitnum
     * @return array [total count, records]
     */
    protected static function fetch(string $where, array $params, string $sort, int $limitfrom, int $limitnum): array {
        global $DB;

        $total = $DB->count_records_sql("SELECT COUNT(1) FROM {user} u WHERE {$where}", $params);
        $namefields = \core_user\fields::for_name()->get_sql('u')->selects;
        $sql = "SELECT u.id, u.username, u.email, u.auth, u.firstaccess, u.lastaccess, u.timecreated {$namefields}
                  FROM {user} u
                 WHERE {$where}
              ORDER BY {$sort}";
        $records = $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
        return [$total, $records];
    }
}
