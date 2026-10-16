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
 * Lists the users processed by Klenzer, as stored in the tool_klenzer table.
 *
 * @package     tool_klenzer
 * @copyright   2026 by IDEF https://idef21.com
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');

use tool_klenzer\local\executor;

$page = optional_param('page', 0, PARAM_INT);
$perpage = 50;

require_login();
$context = context_system::instance();
require_capability('tool/klenzer:view', $context);

$url = new moodle_url('/admin/tool/klenzer/records.php');
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('records', 'tool_klenzer'));
$PAGE->set_heading(get_string('pluginname', 'tool_klenzer'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('records', 'tool_klenzer'));

$total = $DB->count_records('tool_klenzer');
$namefields = \core_user\fields::for_name()->get_sql('u')->selects;
$sql = "SELECT k.id, k.userid, k.state, k.timecreated, u.email {$namefields}
          FROM {tool_klenzer} k
     LEFT JOIN {user} u ON u.id = k.userid
      ORDER BY k.timecreated DESC, k.id DESC";
$records = $DB->get_records_sql($sql, [], $page * $perpage, $perpage);

if (!$records) {
    echo $OUTPUT->notification(get_string('norecords', 'tool_klenzer'), 'info', false);
} else {
    $states = [
        executor::STATE_SUSPENDED => get_string('statesuspended', 'tool_klenzer'),
        executor::STATE_DELETED => get_string('statedeleted', 'tool_klenzer'),
    ];
    $table = new html_table();
    $table->head = [
        get_string('fullnameuser'),
        get_string('email'),
        get_string('state', 'tool_klenzer'),
        get_string('date'),
    ];
    foreach ($records as $record) {
        $table->data[] = [
            fullname($record),
            s($record->email),
            $states[$record->state] ?? s($record->state),
            userdate($record->timecreated),
        ];
    }
    echo html_writer::table($table);
    echo $OUTPUT->paging_bar($total, $page, $perpage, $url);
}

echo $OUTPUT->single_button(new moodle_url('/admin/tool/klenzer/index.php'), get_string('back'), 'get');

echo $OUTPUT->footer();
