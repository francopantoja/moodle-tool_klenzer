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
 * Privacy Subsystem implementation.
 *
 * @package     tool_klenzer
 * @copyright   2026 by IDEF https://idef21.com
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_klenzer\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider: describes, exports and deletes the data stored in the tool_klenzer table.
 *
 * All records belong to the system context.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Describes the personal data stored or sent by the plugin.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('tool_klenzer', [
            'userid' => 'privacy:metadata:tool_klenzer:userid',
            'state' => 'privacy:metadata:tool_klenzer:state',
            'timecreated' => 'privacy:metadata:tool_klenzer:timecreated',
        ], 'privacy:metadata:tool_klenzer');

        // Summary emailed to the notification recipients and appended to the log file.
        $collection->add_external_location_link('notifyemails', [
            'fullname' => 'privacy:metadata:notifyemails:fullname',
            'email' => 'privacy:metadata:notifyemails:email',
            'lastaccess' => 'privacy:metadata:notifyemails:lastaccess',
        ], 'privacy:metadata:notifyemails');

        return $collection;
    }

    /**
     * Returns the contexts holding data for the user: the system context if they have records.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                 WHERE ctx.contextlevel = :contextlevel
                   AND EXISTS (SELECT 1 FROM {tool_klenzer} k WHERE k.userid = :userid)";
        $contextlist->add_from_sql($sql, ['contextlevel' => CONTEXT_SYSTEM, 'userid' => $userid]);
        return $contextlist;
    }

    /**
     * Returns the users with data in the given context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist): void {
        if (!$userlist->get_context() instanceof \context_system) {
            return;
        }
        $userlist->add_from_sql('userid', 'SELECT userid FROM {tool_klenzer}', []);
    }

    /**
     * Exports the records of the user.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $context = \context_system::instance();
        if (!in_array($context->id, $contextlist->get_contextids())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        $records = $DB->get_records('tool_klenzer', ['userid' => $userid], 'timecreated ASC, id ASC');
        if (!$records) {
            return;
        }

        $data = [];
        foreach ($records as $record) {
            $data[] = (object) [
                'state' => $record->state,
                'timecreated' => transform::datetime($record->timecreated),
            ];
        }
        writer::with_context($context)->export_data(
            [get_string('pluginname', 'tool_klenzer')],
            (object) ['records' => $data]
        );
    }

    /**
     * Deletes all the data in the given context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if ($context instanceof \context_system) {
            $DB->delete_records('tool_klenzer');
        }
    }

    /**
     * Deletes the data of one user in the approved contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $context = \context_system::instance();
        if (!in_array($context->id, $contextlist->get_contextids())) {
            return;
        }
        $DB->delete_records('tool_klenzer', ['userid' => $contextlist->get_user()->id]);
    }

    /**
     * Deletes the data of several users in the given context.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        if (!$userlist->get_context() instanceof \context_system) {
            return;
        }
        $userids = $userlist->get_userids();
        if (!$userids) {
            return;
        }
        [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $DB->delete_records_select('tool_klenzer', "userid {$insql}", $params);
    }
}
