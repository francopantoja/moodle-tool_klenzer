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
 * Email summary sender.
 *
 * @package     tool_klenzer
 * @copyright   2026 by IDEF https://idef21.com
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_klenzer\local;

/**
 * Sends the summary of affected users to the configured notification recipients.
 */
class notifier {
    /** Line separating the suspended and deleted users in the email body. */
    const SEPARATOR = '---------------------';

    /**
     * Returns the valid addresses from the notifyemails setting.
     *
     * @return string[]
     */
    public static function get_recipients(): array {
        $setting = (string) get_config('tool_klenzer', 'notifyemails');
        $emails = array_filter(array_map('trim', explode(',', $setting)));
        return array_values(array_filter($emails, 'validate_email'));
    }

    /**
     * Builds the summary text: date on the first line, then one line per affected user.
     *
     * @return string
     */
    public static function build_summary(): string {
        // Full lists, without paging.
        [$suspendtotal, $suspendusers] = inactive_users::get_users_to_suspend();
        [$deletetotal, $deleteusers] = inactive_users::get_users_to_delete();

        $lines = [];
        $lines[] = get_string('summarydate', 'tool_klenzer', userdate(time(), '%Y-%m-%d %H:%M:%S'));
        $lines[] = get_string('userstosuspend', 'tool_klenzer') . " ({$suspendtotal})";
        foreach ($suspendusers as $user) {
            $lines[] = self::format_user($user);
        }
        $lines[] = self::SEPARATOR;
        $lines[] = get_string('userstodelete', 'tool_klenzer') . " ({$deletetotal})";
        foreach ($deleteusers as $user) {
            $lines[] = self::format_user($user);
        }
        return implode("\n", $lines);
    }

    /**
     * Sends the summary email to the notification recipients.
     *
     * @param string $body Summary text, see build_summary().
     * @return array Send result per recipient, as [email => bool].
     */
    public static function send_summary(string $body): array {
        global $SITE;

        $recipients = self::get_recipients();
        if (!$recipients) {
            return [];
        }

        $subject = get_string('emailsubject', 'tool_klenzer', format_string($SITE->shortname));
        $from = \core_user::get_noreply_user();

        $results = [];
        foreach ($recipients as $email) {
            // Dummy user record so email_to_user() can deliver to a plain address.
            $to = clone($from);
            $to->email = $email;
            $to->mailformat = 0;
            $results[$email] = (bool) email_to_user($to, $from, $subject, $body);
        }
        return $results;
    }

    /**
     * Formats one user as a single line of the email body.
     *
     * @param \stdClass $user
     * @return string
     */
    protected static function format_user(\stdClass $user): string {
        $lastaccess = $user->lastaccess ? userdate($user->lastaccess) : get_string('never');
        return fullname($user) . " <{$user->email}> | " . get_string('lastaccess') . ': ' . $lastaccess;
    }
}
