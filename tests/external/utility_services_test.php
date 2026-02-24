<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Unit tests for utility_services external API
 *
 * @package    mod_longpage
 * @category   test
 * @copyright  2024 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_longpage\external;

use mod_longpage\external\utility_services;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/mod/longpage/locallib.php');

/**
 * Unit tests for utility_services external API
 *
 * @package    mod_longpage
 * @category   test
 * @copyright  2024 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_longpage\external\utility_services
 * @runTestsInSeparateProcesses
 */
final class utility_services_test extends \externallib_advanced_testcase {
    /** @var stdClass Course object */
    private $course;

    /** @var stdClass Longpage module instance */
    private $longpage;

    /** @var stdClass Course module */
    private $cm;

    /** @var stdClass Student user */
    private $student;

    /** @var stdClass Teacher user */
    private $teacher;

    /** @var context_module Module context */
    private $context;

    /**
     * Set up test data
     */
    protected function setUp(): void {
        global $DB;
        parent::setUp();

        $this->resetAfterTest(true);

        // Create course.
        $this->course = $this->getDataGenerator()->create_course();

        // Create longpage instance.
        $this->longpage = $this->getDataGenerator()->create_module('longpage', [
            'course' => $this->course->id,
            'name' => 'Test Longpage',
            'content' => 'Test content',
        ]);

        $this->cm = get_coursemodule_from_instance('longpage', $this->longpage->id, $this->course->id);
        $this->context = \context_module::instance($this->cm->id);

        // Create users.
        $this->student = $this->getDataGenerator()->create_user();
        $this->teacher = $this->getDataGenerator()->create_user();

        // Enrol users.
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);

        $this->getDataGenerator()->enrol_user($this->student->id, $this->course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($this->teacher->id, $this->course->id, $teacherrole->id);
    }

    /**
     * Test logging a view event
     *
     * @covers \mod_longpage\external\utility_services::log
     */
    public function test_log_view_event(): void {
        global $DB;

        $this->setUser($this->student);

        $logdata = [
            'courseid' => $this->course->id,
            'utc' => time(),
            'action' => 'viewed',
            'entry' => json_encode(['page' => $this->longpage->id]),
        ];

        $result = utility_services::log($logdata);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('response', $result);

        // Verify log entry was created.
        $logs = $DB->get_records('logstore_standard_log', [
            'userid' => $this->student->id,
            'courseid' => $this->course->id,
            'action' => 'viewed',
        ]);

        $this->assertNotEmpty($logs);
    }

    /**
     * Test logging a scroll event
     *
     * @covers \mod_longpage\external\utility_services::log
     */
    public function test_log_scroll_event(): void {
        global $DB;

        $this->setUser($this->student);

        $scrolldata = [
            'targetID' => 'section-1',
            'longpageid' => $this->longpage->id,
            'utc' => time(),
            'scrolltop' => 150,
        ];

        $logdata = [
            'courseid' => $this->course->id,
            'utc' => time(),
            'action' => 'scroll',
            'entry' => json_encode($scrolldata),
        ];

        $result = utility_services::log($logdata);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('response', $result);

        // Verify log entry was created.
        $logs = $DB->get_records('logstore_standard_log', [
            'userid' => $this->student->id,
            'courseid' => $this->course->id,
            'action' => 'scroll',
        ]);

        $this->assertNotEmpty($logs);

        // Verify reading progress was also recorded.
        $sql = "SELECT * FROM {longpage_reading_progress}
                WHERE userid = :userid AND longpageid = :longpageid
                AND " . $DB->sql_compare_text('section') . " = " . $DB->sql_compare_text(':section');
        $progress = $DB->get_records_sql($sql, [
            'userid' => $this->student->id,
            'longpageid' => $this->longpage->id,
            'section' => 'section-1',
        ]);

        $this->assertNotEmpty($progress);
        $progressrecord = reset($progress);
        $this->assertEquals(150, $progressrecord->scrolltop);
    }

    /**
     * Test logging with progress tracking
     *
     * @covers \mod_longpage\external\utility_services::log
     */
    public function test_log_scroll_with_progress(): void {
        global $DB;

        $this->setUser($this->student);

        $scrolldata = [
            'targetID' => 'section-2',
            'longpageid' => $this->longpage->id,
            'utc' => time(),
            'scrolltop' => 275,
        ];

        $logdata = [
            'courseid' => $this->course->id,
            'utc' => time(),
            'action' => 'scroll',
            'entry' => json_encode($scrolldata),
        ];

        utility_services::log($logdata);

        // Verify reading progress was recorded.
        $progress = $DB->get_records('longpage_reading_progress', [
            'userid' => $this->student->id,
            'longpageid' => $this->longpage->id,
        ]);

        $this->assertNotEmpty($progress);
        $progressrecord = reset($progress);
        $this->assertEquals('section-2', $progressrecord->section);
        $this->assertEquals(275, $progressrecord->scrolltop);
    }

    /**
     * Test logging triggers database entries
     *
     * @covers \mod_longpage\external\utility_services::log
     */
    public function test_log_creates_database_entry(): void {
        global $DB;

        $this->setUser($this->student);

        $logdata = [
            'courseid' => $this->course->id,
            'utc' => time(),
            'action' => 'clicked',
            'entry' => json_encode(['element' => 'button-1']),
        ];

        $countbefore = $DB->count_records('logstore_standard_log', [
            'userid' => $this->student->id,
            'courseid' => $this->course->id,
        ]);

        utility_services::log($logdata);

        $countafter = $DB->count_records('logstore_standard_log', [
            'userid' => $this->student->id,
            'courseid' => $this->course->id,
        ]);

        $this->assertEquals($countbefore + 1, $countafter);
    }

    /**
     * Test can_madify_annotations with teacher role
     *
     * @covers \mod_longpage\external\utility_services::can_madify_annotations
     */
    public function test_can_madify_annotations_as_teacher(): void {
        global $DB;

        $this->setUser($this->teacher);

        // Give teacher capability to modify annotations.
        $teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);
        assign_capability('mod/longpage:modannotations', CAP_ALLOW, $teacherrole->id, $this->context->id);
        $this->context->mark_dirty();

        $result = utility_services::can_madify_annotations($this->longpage->id);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('canmodannotations', $result);
        $this->assertTrue($result['canmodannotations']);
    }

    /**
     * Test can_madify_annotations with student role
     *
     * @covers \mod_longpage\external\utility_services::can_madify_annotations
     */
    public function test_can_madify_annotations_as_student(): void {
        $this->setUser($this->student);

        $result = utility_services::can_madify_annotations($this->longpage->id);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('canmodannotations', $result);
        $this->assertFalse($result['canmodannotations']);
    }

    /**
     * Test can_madify_annotations with modify_others capability
     *
     * @covers \mod_longpage\external\utility_services::can_madify_annotations
     */
    public function test_can_madify_annotations_with_capability(): void {
        global $DB;

        $this->setUser($this->student);

        // Give student capability to modify annotations.
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        assign_capability('mod/longpage:modannotations', CAP_ALLOW, $studentrole->id, $this->context->id);
        $this->context->mark_dirty();

        $result = utility_services::can_madify_annotations($this->longpage->id);

        $this->assertIsArray($result);
        $this->assertTrue($result['canmodannotations']);
    }

    /**
     * Test can_madify_annotations without capability
     *
     * @covers \mod_longpage\external\utility_services::can_madify_annotations
     */
    public function test_can_madify_annotations_without_capability(): void {
        global $DB;

        $this->setUser($this->student);

        // Explicitly prevent capability.
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        assign_capability('mod/longpage:modannotations', CAP_PROHIBIT, $studentrole->id, $this->context->id);
        $this->context->mark_dirty();

        $result = utility_services::can_madify_annotations($this->longpage->id);

        $this->assertIsArray($result);
        $this->assertFalse($result['canmodannotations']);
    }

    /**
     * Test can_madify_annotations with invalid page ID
     *
     * @covers \mod_longpage\external\utility_services::can_madify_annotations
     */
    public function test_can_madify_annotations_invalid_page(): void {
        $this->setUser($this->student);

        $invalidpageid = 99999;

        $this->expectException(\dml_missing_record_exception::class);
        utility_services::can_madify_annotations($invalidpageid);
    }

    /**
     * Test logging multiple events
     *
     * @covers \mod_longpage\external\utility_services::log
     */
    public function test_log_multiple_events(): void {
        global $DB;

        $this->setUser($this->student);

        // Log multiple different events.
        $events = ['viewed', 'clicked', 'navigated'];

        foreach ($events as $action) {
            $logdata = [
                'courseid' => $this->course->id,
                'utc' => time(),
                'action' => $action,
                'entry' => json_encode(['test' => 'data']),
            ];

            utility_services::log($logdata);
        }

        // Verify all events were logged.
        $logs = $DB->get_records('logstore_standard_log', [
            'userid' => $this->student->id,
            'courseid' => $this->course->id,
        ]);

        $this->assertGreaterThanOrEqual(3, count($logs));
    }

    /**
     * Test logging with empty entry data
     *
     * @covers \mod_longpage\external\utility_services::log
     */
    public function test_log_with_empty_entry(): void {
        global $DB;

        $this->setUser($this->student);

        $logdata = [
            'courseid' => $this->course->id,
            'utc' => time(),
            'action' => 'viewed',
            'entry' => '',
        ];

        $result = utility_services::log($logdata);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('response', $result);
    }

    /**
     * Test logging records correct user IP
     *
     * @covers \mod_longpage\external\utility_services::log
     */
    public function test_log_records_user_ip(): void {
        global $DB;

        $this->setUser($this->student);

        $logdata = [
            'courseid' => $this->course->id,
            'utc' => time(),
            'action' => 'viewed',
            'entry' => json_encode(['test' => 'data']),
        ];

        utility_services::log($logdata);

        // Get the log entry.
        $logs = $DB->get_records('logstore_standard_log', [
            'userid' => $this->student->id,
            'courseid' => $this->course->id,
            'action' => 'viewed',
        ]);

        $this->assertNotEmpty($logs);
        $log = reset($logs);
        $this->assertObjectHasProperty('ip', $log);
    }

    /**
     * Test permissions for can_madify_annotations
     *
     * @covers \mod_longpage\external\utility_services::can_madify_annotations
     */
    public function test_can_madify_annotations_permissions(): void {
        // Unenrolled user should not have permission.
        $unenrolleduser = $this->getDataGenerator()->create_user();
        $this->setUser($unenrolleduser);

        $this->expectException(\require_login_exception::class);
        utility_services::can_madify_annotations($this->longpage->id);
    }

    /**
     * Test logging with different action types
     *
     * @covers \mod_longpage\external\utility_services::log
     */
    public function test_log_various_action_types(): void {
        global $DB;

        $this->setUser($this->student);

        $actions = [
            'viewed' => ['page' => $this->longpage->id],
            'clicked' => ['element' => 'button-1'],
            'navigated' => ['from' => 'section-1', 'to' => 'section-2'],
            'annotated' => ['annotation' => 'test annotation'],
        ];

        foreach ($actions as $action => $entry) {
            $logdata = [
                'courseid' => $this->course->id,
                'utc' => time(),
                'action' => $action,
                'entry' => json_encode($entry),
            ];

            $result = utility_services::log($logdata);
            $this->assertIsArray($result);
        }

        // Verify all actions were logged.
        $logs = $DB->get_records('logstore_standard_log', [
            'userid' => $this->student->id,
            'courseid' => $this->course->id,
        ]);

        $this->assertGreaterThanOrEqual(4, count($logs));
    }
}
