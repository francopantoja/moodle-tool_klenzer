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
 * Main page of the tool.
 *
 * @package     tool_klenzer
 * @copyright   2026 by IDEF https://idef21.com
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use tool_klenzer\local\executor;
use tool_klenzer\local\inactive_users;

// Sets up the page context, URL and layout, and checks login and the tool/klenzer:view capability.
admin_externalpage_setup('tool_klenzer_index');

$PAGE->set_title(get_string('pluginname', 'tool_klenzer'));
$PAGE->set_heading(get_string('pluginname', 'tool_klenzer'));

$action = optional_param('action', '', PARAM_ALPHA);
$baseurl = new moodle_url('/admin/tool/klenzer/index.php');

// Manual launch of the scheduled task, only after confirmation: run, then log and email the summary.
if ($action === 'run') {
    require_sesskey();
    require_capability('moodle/user:update', context_system::instance());
    require_capability('moodle/user:delete', context_system::instance());
    $outcome = executor::process();

    $message = get_string('executed', 'tool_klenzer', $outcome['counts']);
    if (executor::is_dry_run()) {
        $message .= ' ' . get_string('dryrun', 'tool_klenzer');
    }
    \core\notification::success($message);
    if (!$outcome['logged']) {
        \core\notification::error(get_string('lognotwritten', 'tool_klenzer'));
    }
    if (!$outcome['results']) {
        \core\notification::info(get_string('emailnotsent', 'tool_klenzer'));
    }
    foreach ($outcome['results'] as $email => $ok) {
        if ($ok) {
            \core\notification::success(get_string('emailsent', 'tool_klenzer', s($email)));
        } else {
            \core\notification::error(get_string('emailfailed', 'tool_klenzer', s($email)));
        }
    }
    redirect($baseurl);
}

// Confirmation screen with the irreversibility warning.
if ($action === 'confirm') {
    echo $OUTPUT->header();
    echo $OUTPUT->confirm(
        get_string('confirmexecute', 'tool_klenzer'),
        new moodle_url($baseurl, ['action' => 'run', 'sesskey' => sesskey()]),
        $baseurl,
        [
            'confirmtitle' => get_string('execute', 'tool_klenzer'),
            'continuestr' => get_string('accept', 'tool_klenzer'),
            'cancelstr' => get_string('no'),
            'type' => single_button::BUTTON_DANGER,
        ]
    );
    echo $OUTPUT->footer();
    die;
}

// Maximum number of rows shown per list.
$limit = 100;

/**
 * Renders one preview list of affected users.
 *
 * @param string $heading
 * @param int|null $cutoff Null when the setting is "Never".
 * @param int $total
 * @param array $users
 * @return string HTML
 */
function tool_klenzer_render_list(string $heading, ?int $cutoff, int $total, array $users): string {
    global $OUTPUT;

    $html = $OUTPUT->heading($heading . ($cutoff === null ? '' : " ({$total})"), 3);
    if ($cutoff === null) {
        return $html . $OUTPUT->notification(get_string('settingnever', 'tool_klenzer'), 'info', false);
    }
    $html .= html_writer::tag('p', get_string('cutoffdate', 'tool_klenzer', userdate($cutoff)));
    if (!$users) {
        return $html . $OUTPUT->notification(get_string('nousersfound'), 'info', false);
    }

    $table = new html_table();
    $table->head = [
        get_string('fullnameuser'),
        get_string('email'),
        get_string('timecreated'),
        get_string('lastaccess'),
    ];
    foreach ($users as $user) {
        $profile = new moodle_url('/user/profile.php', ['id' => $user->id]);
        $table->data[] = [
            html_writer::link($profile, fullname($user)),
            s($user->email),
            userdate($user->timecreated),
            $user->lastaccess ? userdate($user->lastaccess) : get_string('never'),
        ];
    }
    $html .= html_writer::table($table);
    if ($total > count($users)) {
        $html .= html_writer::tag('p', get_string(
            'showingxofy',
            'tool_klenzer',
            ['shown' => count($users), 'total' => $total]
        ));
    }
    return $html;
}

echo $OUTPUT->header();

// Plugin branding: icon and credits.
$icon = html_writer::img(
    $OUTPUT->image_url('icon', 'tool_klenzer'),
    get_string('pluginname', 'tool_klenzer'),
    ['width' => 48, 'height' => 48, 'class' => 'me-2']
);
$credits = html_writer::link('https://idef21.com', get_string('byidef', 'tool_klenzer'), ['target' => '_blank']);
echo html_writer::div($icon . $credits, 'd-flex align-items-center mb-3');

// Actions: view the processed users table and run the process.
echo html_writer::div(
    $OUTPUT->single_button(
        new moodle_url('/admin/tool/klenzer/records.php'),
        get_string('records', 'tool_klenzer'),
        'get'
    ) .
    $OUTPUT->single_button(
        new moodle_url($baseurl, ['action' => 'confirm']),
        get_string('execute', 'tool_klenzer'),
        'get',
        ['type' => single_button::BUTTON_DANGER]
    ),
    'd-flex gap-2 mb-3'
);

// Preview only: nothing is changed in the database.
echo $OUTPUT->notification(get_string('previewonly', 'tool_klenzer'), 'warning', false);

[$total, $users] = inactive_users::get_users_to_suspend(0, $limit);
echo tool_klenzer_render_list(
    get_string('userstosuspend', 'tool_klenzer'),
    inactive_users::get_suspend_cutoff(),
    $total,
    $users
);

[$total, $users] = inactive_users::get_users_to_delete(0, $limit);
echo tool_klenzer_render_list(
    get_string('userstodelete', 'tool_klenzer'),
    inactive_users::get_delete_cutoff(),
    $total,
    $users
);

echo $OUTPUT->footer();
