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
 * Tests for the executor.
 *
 * @package     tool_klenzer
 * @category    test
 * @copyright   2026 by IDEF https://idef21.com
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_klenzer\local;

/**
 * Tests for {@see executor}, {@see notifier} and {@see logger}.
 */
#[\PHPUnit\Framework\Attributes\CoversClass(executor::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(notifier::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(logger::class)]
final class executor_test extends \advanced_testcase {
    /** @var \stdClass User to be suspended. */
    protected $inactive;

    /** @var \stdClass User to be deleted. */
    protected $neverused;

    /**
     * Creates one user to suspend and one to delete.
     */
    protected function setUp(): void {
        global $DB;

        parent::setUp();
        $this->resetAfterTest();
        set_config('suspendyears', 1, 'tool_klenzer');
        set_config('deletemonths', 1, 'tool_klenzer');
        set_config('notifyemails', 'one@example.com, not-an-email, two@example.com', 'tool_klenzer');
        $old = strtotime('-2 years');

        $generator = $this->getDataGenerator();
        $this->inactive = $generator->create_user();
        $DB->update_record('user', (object) ['id' => $this->inactive->id, 'firstaccess' => $old, 'lastaccess' => $old]);
        $this->neverused = $generator->create_user();
        $DB->update_record('user', (object) ['id' => $this->neverused->id, 'timecreated' => $old]);
    }

    /**
     * Simulation mode (default) records the users but never changes any account.
     */
    public function test_process_dry_run(): void {
        global $DB;

        $this->assertTrue(executor::is_dry_run());
        $sink = $this->redirectEmails();

        $outcome = executor::process();

        $this->assertSame(['suspended' => 1, 'deleted' => 1], $outcome['counts']);
        $this->assertEquals(0, $DB->get_field('user', 'suspended', ['id' => $this->inactive->id]));
        $this->assertEquals(0, $DB->get_field('user', 'deleted', ['id' => $this->neverused->id]));
        $this->assertTrue($DB->record_exists('tool_klenzer', ['userid' => $this->inactive->id, 'state' => 's']));
        $this->assertTrue($DB->record_exists('tool_klenzer', ['userid' => $this->neverused->id, 'state' => 'd']));

        // Only the two valid addresses receive the summary.
        $this->assertSame(['one@example.com' => true, 'two@example.com' => true], $outcome['results']);
        $this->assertSame(2, $sink->count());
        $sink->close();

        // The summary is appended to the log, including the simulation notice.
        $this->assertTrue($outcome['logged']);
        $log = file_get_contents(logger::get_path());
        $this->assertStringContainsString($this->inactive->email, $log);
        $this->assertStringContainsString($this->neverused->email, $log);
        $this->assertStringContainsString(get_string('dryrun', 'tool_klenzer'), $log);
    }

    /**
     * Live mode suspends and deletes the accounts.
     */
    public function test_process_live_mode(): void {
        global $DB;

        set_config('livemode', 1, 'tool_klenzer');
        $this->assertFalse(executor::is_dry_run());
        $sink = $this->redirectEmails();

        $outcome = executor::process();
        $sink->close();

        $this->assertSame(['suspended' => 1, 'deleted' => 1], $outcome['counts']);
        $this->assertEquals(1, $DB->get_field('user', 'suspended', ['id' => $this->inactive->id]));
        $this->assertEquals(1, $DB->get_field('user', 'deleted', ['id' => $this->neverused->id]));
        $this->assertSame(2, $DB->count_records('tool_klenzer'));

        // Once processed, the users are no longer listed.
        $this->assertSame(0, inactive_users::get_users_to_suspend()[0]);
        $this->assertSame(0, inactive_users::get_users_to_delete()[0]);
    }

    /**
     * Without valid recipients no email is sent, but the log is still written.
     */
    public function test_process_without_recipients(): void {
        set_config('notifyemails', '', 'tool_klenzer');
        $sink = $this->redirectEmails();

        $outcome = executor::process();

        $this->assertSame([], $outcome['results']);
        $this->assertSame(0, $sink->count());
        $this->assertTrue($outcome['logged']);
        $sink->close();
    }
}
