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
 * Tests for the privacy provider.
 *
 * @package     tool_klenzer
 * @category    test
 * @copyright   2026 by IDEF https://idef21.com
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_klenzer\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Tests for {@see provider}.
 */
#[\PHPUnit\Framework\Attributes\CoversClass(provider::class)]
final class provider_test extends \core_privacy\tests\provider_testcase {

    /** @var \stdClass First user with records. */
    protected $user1;

    /** @var \stdClass Second user with records. */
    protected $user2;

    /** @var \stdClass User without records. */
    protected $user3;

    /**
     * Creates three users, two of them with records in tool_klenzer.
     */
    protected function setUp(): void {
        global $DB;

        parent::setUp();
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $this->user1 = $generator->create_user();
        $this->user2 = $generator->create_user();
        $this->user3 = $generator->create_user();

        $DB->insert_record('tool_klenzer', ['userid' => $this->user1->id, 'state' => 's', 'timecreated' => time()]);
        $DB->insert_record('tool_klenzer', ['userid' => $this->user1->id, 'state' => 'd', 'timecreated' => time()]);
        $DB->insert_record('tool_klenzer', ['userid' => $this->user2->id, 'state' => 's', 'timecreated' => time()]);
    }

    /**
     * The metadata describes the table and the external location.
     */
    public function test_get_metadata(): void {
        $items = provider::get_metadata(new collection('tool_klenzer'))->get_collection();
        $names = array_map(fn($item) => $item->get_name(), $items);
        $this->assertEqualsCanonicalizing(['tool_klenzer', 'notifyemails'], $names);
    }

    /**
     * Only users with records have the system context.
     */
    public function test_get_contexts_for_userid(): void {
        $syscontext = \context_system::instance();
        $this->assertSame([$syscontext->id],
            array_map('intval', provider::get_contexts_for_userid($this->user1->id)->get_contextids()));
        $this->assertEmpty(provider::get_contexts_for_userid($this->user3->id)->get_contextids());
    }

    /**
     * The users in the system context are those with records.
     */
    public function test_get_users_in_context(): void {
        $userlist = new userlist(\context_system::instance(), 'tool_klenzer');
        provider::get_users_in_context($userlist);
        $this->assertEqualsCanonicalizing([$this->user1->id, $this->user2->id],
            array_map('intval', $userlist->get_userids()));

        // Other contexts hold no data.
        $userlist = new userlist(\context_user::instance($this->user1->id), 'tool_klenzer');
        provider::get_users_in_context($userlist);
        $this->assertEmpty($userlist->get_userids());
    }

    /**
     * Exports the records of one user.
     */
    public function test_export_user_data(): void {
        $syscontext = \context_system::instance();
        $contextlist = new approved_contextlist($this->user1, 'tool_klenzer', [$syscontext->id]);
        provider::export_user_data($contextlist);

        $writer = writer::with_context($syscontext);
        $this->assertTrue($writer->has_any_data());
        $data = $writer->get_data([get_string('pluginname', 'tool_klenzer')]);
        $this->assertCount(2, $data->records);
    }

    /**
     * Deletes all the data in the system context.
     */
    public function test_delete_data_for_all_users_in_context(): void {
        global $DB;

        provider::delete_data_for_all_users_in_context(\context_user::instance($this->user1->id));
        $this->assertSame(3, $DB->count_records('tool_klenzer'));

        provider::delete_data_for_all_users_in_context(\context_system::instance());
        $this->assertSame(0, $DB->count_records('tool_klenzer'));
    }

    /**
     * Deletes the data of one user only.
     */
    public function test_delete_data_for_user(): void {
        global $DB;

        $contextlist = new approved_contextlist($this->user1, 'tool_klenzer', [\context_system::instance()->id]);
        provider::delete_data_for_user($contextlist);

        $this->assertSame(0, $DB->count_records('tool_klenzer', ['userid' => $this->user1->id]));
        $this->assertSame(1, $DB->count_records('tool_klenzer', ['userid' => $this->user2->id]));
    }

    /**
     * Deletes the data of several users.
     */
    public function test_delete_data_for_users(): void {
        global $DB;

        $userlist = new approved_userlist(\context_system::instance(), 'tool_klenzer',
            [$this->user1->id, $this->user2->id]);
        provider::delete_data_for_users($userlist);

        $this->assertSame(0, $DB->count_records('tool_klenzer'));
    }
}
