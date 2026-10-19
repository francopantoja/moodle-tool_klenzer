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
 * Welcome page shown once after installing the plugin.
 *
 * @package     tool_klenzer
 * @copyright   2026 by IDEF https://idef21.com
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');

require_admin();

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/admin/tool/klenzer/welcome.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('welcome', 'tool_klenzer'));
$PAGE->set_heading(get_string('pluginname', 'tool_klenzer'));

echo $OUTPUT->header();

// Plugin branding: icon and credits.
$icon = html_writer::img(
    $OUTPUT->image_url('icon', 'tool_klenzer'),
    get_string('pluginname', 'tool_klenzer'),
    ['width' => 64, 'height' => 64, 'class' => 'me-3']
);
$credits = html_writer::link('https://idef21.com', get_string('byidef', 'tool_klenzer'), ['target' => '_blank']);
echo html_writer::div(
    $icon . $OUTPUT->heading(get_string('welcome', 'tool_klenzer'), 2, 'm-0'),
    'd-flex align-items-center mb-3'
);

echo html_writer::tag('p', get_string('welcome_desc', 'tool_klenzer'));
echo html_writer::tag('p', $credits);

// Shortcuts to the plugin settings and main page.
echo html_writer::div(
    $OUTPUT->single_button(
        new moodle_url('/admin/settings.php', ['section' => 'tool_klenzer_settings']),
        get_string('settings', 'tool_klenzer'),
        'get',
        ['type' => 'primary']
    ) .
    $OUTPUT->single_button(
        new moodle_url('/admin/tool/klenzer/index.php'),
        get_string('pluginname', 'tool_klenzer'),
        'get'
    ),
    'd-flex gap-2 mt-4'
);

echo $OUTPUT->footer();
