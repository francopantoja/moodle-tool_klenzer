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
 * Tests for the inactive users finder.
 *
 * @package     tool_klenzer
 * @category    test
 * @copyright   2026 by IDEF https://idef21.com
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_klenzer\local;

/**
 * Tests for {@see inactive_users}.
 */
#[\PHPUnit\Framework\Attributes\CoversClass(inactive_users::class)]
final class inactive_users_test extends \advanced_testcase {

    /**
     * Creates a user and forces the given access and creation times.
     *
     * @param array $fields
     * @return \stdClass
     */
    protected function create_user_with(array $fields): \stdClass {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $DB->update_record('user', (object) (['id' => $user->id] + $fields));
        return $DB->get_record('user', ['id' => $user->id]);
    }

    /**
     * With both settings set to "Never", no user is returned.
     */
    public function test_never_returns_nothing(): void {
        $this->resetAfterTest();
        set_config('suspendyears', 0, 'tool_klenzer');
        set_config('deletemonths', 0, 'tool_klenzer');
        $old = strtotime('-5 years');
        $this->create_user_with(['firstaccess' => $old, 'lastaccess' => $old]);
        $this->create_user_with(['firstaccess' => 0, 'lastaccess' => 0, 'timecreated' => $old]);

        $this->assertNull(inactive_users::get_suspend_cutoff());
        $this->assertNull(inactive_users::get_delete_cutoff());
        $this->assertSame([0, []], inactive_users::get_users_to_suspend());
        $this->assertSame([0, []], inactive_users::get_users_to_delete());
    }

    /**
     * Only users who accessed at least once, but not since the cutoff, are suspended.
     */
    public function test_get_users_to_suspend(): void {
        global $DB;

        $this->resetAfterTest();
        set_config('suspendyears', 2, 'tool_klenzer');
        $old = strtotime('-3 years');
        $recent = strtotime('-1 year');

        $inactive = $this->create_user_with(['firstaccess' => $old, 'lastaccess' => $old]);
        $this->create_user_with(['firstaccess' => $old, 'lastaccess' => $recent]);
        $this->create_user_with(['firstaccess' => 0, 'lastaccess' => 0]);
        $this->create_user_with(['firstaccess' => $old, 'lastaccess' => $old, 'suspended' => 1]);
        $deleted = $this->create_user_with(['firstaccess' => $old, 'lastaccess' => $old]);
        delete_user($deleted);

        // Admin and guest are never affected, even if inactive.
        $DB->set_field('user', 'lastaccess', $old, ['id' => get_admin()->id]);
        $DB->set_field('user', 'lastaccess', $old, ['id' => guest_user()->id]);

        [$total, $users] = inactive_users::get_users_to_suspend();
        $this->assertSame(1, $total);
        $this->assertSame([(int) $inactive->id], array_map('intval', array_keys($users)));
    }

    /**
     * Only users who never accessed and were created before the cutoff are deleted.
     */
    public function test_get_users_to_delete(): void {
        $this->resetAfterTest();
        set_config('deletemonths', 6, 'tool_klenzer');
        $old = strtotime('-1 year');
        $recent = strtotime('-1 month');

        $neverused = $this->create_user_with(['firstaccess' => 0, 'lastaccess' => 0, 'timecreated' => $old]);
        $this->create_user_with(['firstaccess' => 0, 'lastaccess' => 0, 'timecreated' => $recent]);
        $this->create_user_with(['firstaccess' => $old, 'lastaccess' => $old, 'timecreated' => $old]);

        [$total, $users] = inactive_users::get_users_to_delete();
        $this->assertSame(1, $total);
        $this->assertSame([(int) $neverused->id], array_map('intval', array_keys($users)));
    }

    /**
     * Paging limits the records but not the total count.
     */
    public function test_paging(): void {
        $this->resetAfterTest();
        set_config('suspendyears', 1, 'tool_klenzer');
        $old = strtotime('-2 years');
        for ($i = 0; $i < 3; $i++) {
            $this->create_user_with(['firstaccess' => $old, 'lastaccess' => $old - $i]);
        }

        [$total, $users] = inactive_users::get_users_to_suspend(0, 2);
        $this->assertSame(3, $total);
        $this->assertCount(2, $users);
    }
}
