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
 * Plain text log stored in moodledata.
 *
 * @package     tool_klenzer
 * @copyright   2026 by IDEF https://idef21.com
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_klenzer\local;

/**
 * Appends each summary to moodledata/tool_klenzer/klenzerlog.txt.
 */
class logger {

    /** Log file name. */
    const FILENAME = 'klenzerlog.txt';

    /**
     * Returns the full path of the log file, creating its directory if needed.
     *
     * @return string
     */
    public static function get_path(): string {
        return make_upload_directory('tool_klenzer') . '/' . self::FILENAME;
    }

    /**
     * Appends a block of text to the log, followed by a blank line.
     *
     * @param string $text
     * @return bool True on success.
     */
    public static function append(string $text): bool {
        return file_put_contents(self::get_path(), $text . "\n\n", FILE_APPEND | LOCK_EX) !== false;
    }
}
