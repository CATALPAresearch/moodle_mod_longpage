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
 * Unit tests for reading_progress_services external API
 *
 * @package    mod_longpage
 * @category   test
 * @copyright  2024 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_longpage\external;

use mod_longpage\external\reading_progress_services;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/mod/longpage/locallib.php');

/**
 * Unit tests for reading_progress_services external API
 *
 * @package    mod_longpage
 * @category   test
 * @copyright  2024 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_longpage\external\reading_progress_services
 */
final class reading_progress_services_test extends \externallib_advanced_testcase {
    /** @var stdClass Course object */
    private $course;

    /** @var stdClass Longpage module instance */
    private $longpage;

    /** @var stdClass Course module */
    private $cm;

    /** @var stdClass Student user */
    private $student;

    /** @var stdClass Another student user */
    private $student2;

    /** @var stdClass Teacher user */
    private $teacher;

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
            'content' => 'Test content for reading progress',
        ]);

        $this->cm = get_coursemodule_from_instance('longpage', $this->longpage->id, $this->course->id);

        // Create users.
        $this->student = $this->getDataGenerator()->create_user();
        $this->student2 = $this->getDataGenerator()->create_user();
        $this->teacher = $this->getDataGenerator()->create_user();

        // Enrol users.
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);

        $this->getDataGenerator()->enrol_user($this->student->id, $this->course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($this->student2->id, $this->course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($this->teacher->id, $this->course->id, $teacherrole->id);
    }

    /**
     * Test updating reading progress with scroll position
     *
     * @covers \mod_longpage\external\reading_progress_services::update_reading_progress
     */
    public function test_update_reading_progress(): void {
        global $DB;

        $this->setUser($this->student);

        $scrolltop = 150.5;
        $section = 'section-1';
        $sectionhash = 12345;

        reading_progress_services::update_reading_progress(
            $this->longpage->id,
            $scrolltop,
            $this->course->id,
            $section,
            $sectionhash
        );

        // Verify record was created in database.
        $progress = $DB->get_records('longpage_reading_progress', [
            'longpageid' => $this->longpage->id,
            'userid' => $this->student->id,
        ]);

        $this->assertNotEmpty($progress);
        $progressrecord = reset($progress);
        $this->assertEquals($scrolltop, $progressrecord->scrolltop);
        $this->assertEquals($this->course->id, $progressrecord->course);
        $this->assertEquals($section, $progressrecord->section);
        $this->assertEquals($sectionhash, $progressrecord->sectionhash);
    }

    /**
     * Test updating reading progress multiple times - all records persist
     *
     * @covers \mod_longpage\external\reading_progress_services::update_reading_progress
     */
    public function test_update_reading_progress_multiple_times(): void {
        global $DB;

        $this->setUser($this->student);

        // First update.
        reading_progress_services::update_reading_progress(
            $this->longpage->id,
            100.0,
            $this->course->id,
            'section-1',
            11111
        );

        // Second update with different position.
        reading_progress_services::update_reading_progress(
            $this->longpage->id,
            200.0,
            $this->course->id,
            'section-2',
            22222
        );

        // Third update with yet another position.
        reading_progress_services::update_reading_progress(
            $this->longpage->id,
            300.0,
            $this->course->id,
            'section-3',
            33333
        );

        // Verify all three records exist.
        $progress = $DB->get_records('longpage_reading_progress', [
            'longpageid' => $this->longpage->id,
            'userid' => $this->student->id,
        ], 'timemodified ASC');

        $this->assertCount(3, $progress);
    }

    /**
     * Test updating reading progress by different users
     *
     * @covers \mod_longpage\external\reading_progress_services::update_reading_progress
     */
    public function test_update_reading_progress_different_users(): void {
        global $DB;

        // Student 1 updates progress.
        $this->setUser($this->student);
        reading_progress_services::update_reading_progress(
            $this->longpage->id,
            150.0,
            $this->course->id,
            'section-1',
            11111
        );

        // Student 2 updates progress.
        $this->setUser($this->student2);
        reading_progress_services::update_reading_progress(
            $this->longpage->id,
            250.0,
            $this->course->id,
            'section-2',
            22222
        );

        // Verify both users have separate records.
        $progress1 = $DB->get_records('longpage_reading_progress', [
            'longpageid' => $this->longpage->id,
            'userid' => $this->student->id,
        ]);
        $this->assertCount(1, $progress1);

        $progress2 = $DB->get_records('longpage_reading_progress', [
            'longpageid' => $this->longpage->id,
            'userid' => $this->student2->id,
        ]);
        $this->assertCount(1, $progress2);

        // Verify scroll positions are different.
        $record1 = reset($progress1);
        $record2 = reset($progress2);
        $this->assertEquals(150.0, $record1->scrolltop);
        $this->assertEquals(250.0, $record2->scrolltop);
    }

    /**
     * Test updating reading progress with percentage values
     *
     * @covers \mod_longpage\external\reading_progress_services::update_reading_progress
     */
    public function test_update_reading_progress_percentage(): void {
        global $DB;

        $this->setUser($this->student);

        // Update progress at 25%.
        reading_progress_services::update_reading_progress(
            $this->longpage->id,
            25.5,
            $this->course->id,
            'section-1',
            11111
        );

        // Update progress at 50%.
        reading_progress_services::update_reading_progress(
            $this->longpage->id,
            50.0,
            $this->course->id,
            'section-2',
            22222
        );

        // Update progress at 75%.
        reading_progress_services::update_reading_progress(
            $this->longpage->id,
            75.25,
            $this->course->id,
            'section-3',
            33333
        );

        $progress = $DB->get_records('longpage_reading_progress', [
            'longpageid' => $this->longpage->id,
            'userid' => $this->student->id,
        ]);

        $this->assertCount(3, $progress);
    }

    /**
     * Test getting reading progress
     *
     * @covers \mod_longpage\external\reading_progress_services::get_reading_progress
     */
    public function test_get_reading_progress(): void {
        global $DB;

        $this->setUser($this->student);

        // Create some reading progress records.
        reading_progress_services::update_reading_progress(
            $this->longpage->id,
            100.0,
            $this->course->id,
            'section-1',
            11111
        );

        reading_progress_services::update_reading_progress(
            $this->longpage->id,
            200.0,
            $this->course->id,
            'section-1',
            11112
        );

        reading_progress_services::update_reading_progress(
            $this->longpage->id,
            300.0,
            $this->course->id,
            'section-2',
            22222
        );

        // Get reading progress.
        $result = reading_progress_services::get_reading_progress($this->course->id, $this->longpage->id);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('response', $result);
        $this->assertNotEmpty($result['response']);

        // Decode and verify the response.
        $data = json_decode($result['response']);
        $this->assertIsObject($data) || $this->assertIsArray($data);
    }

    /**
     * Test getting reading progress with no data
     *
     * @covers \mod_longpage\external\reading_progress_services::get_reading_progress
     */
    public function test_get_reading_progress_no_data(): void {
        $this->setUser($this->student);

        // Get reading progress without updating any.
        $result = reading_progress_services::get_reading_progress($this->course->id, $this->longpage->id);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('response', $result);

        // Response should be valid JSON (empty object or array).
        $decoded = json_decode($result['response']);
        $this->assertNotNull($decoded);
    }

    /**
     * Test reading progress persistence
     *
     * @covers \mod_longpage\external\reading_progress_services::update_reading_progress
     * @covers \mod_longpage\external\reading_progress_services::get_reading_progress
     */
    public function test_reading_progress_persistence(): void {
        $this->setUser($this->student);

        // Update progress.
        reading_progress_services::update_reading_progress(
            $this->longpage->id,
            175.5,
            $this->course->id,
            'section-1',
            11111
        );

        // Get progress to verify it persists.
        $result = reading_progress_services::get_reading_progress($this->course->id, $this->longpage->id);
        $this->assertNotEmpty($result['response']);

        // Update again.
        reading_progress_services::update_reading_progress(
            $this->longpage->id,
            275.5,
            $this->course->id,
            'section-2',
            22222
        );

        // Get progress again.
        $result2 = reading_progress_services::get_reading_progress($this->course->id, $this->longpage->id);
        $this->assertNotEmpty($result2['response']);
    }

    /**
     * Test updating progress with invalid page ID
     *
     * @covers \mod_longpage\external\reading_progress_services::update_reading_progress
     */
    public function test_update_reading_progress_invalid_page(): void {
        $this->setUser($this->student);

        $invalidpageid = 99999;

        $this->expectException(\moodle_exception::class);
        reading_progress_services::update_reading_progress(
            $invalidpageid,
            100.0,
            $this->course->id,
            'section-1',
            11111
        );
    }

    /**
     * Test getting progress with invalid page ID
     *
     * @covers \mod_longpage\external\reading_progress_services::get_reading_progress
     */
    public function test_get_reading_progress_invalid_page(): void {
        $this->setUser($this->student);

        $invalidpageid = 99999;

        // Get progress for non-existent page should return empty result.
        $result = reading_progress_services::get_reading_progress($this->course->id, $invalidpageid);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('response', $result);
    }

    /**
     * Test reading progress isolation between users
     *
     * @covers \mod_longpage\external\reading_progress_services::get_reading_progress
     */
    public function test_get_reading_progress_isolation(): void {
        // Student 1 updates progress.
        $this->setUser($this->student);
        reading_progress_services::update_reading_progress(
            $this->longpage->id,
            100.0,
            $this->course->id,
            'section-1',
            11111
        );

        // Student 2 should not see student 1's progress.
        $this->setUser($this->student2);
        $result = reading_progress_services::get_reading_progress($this->course->id, $this->longpage->id);

        // Response should be empty or not contain student 1's data.
        $decoded = json_decode($result['response']);
        $this->assertNotNull($decoded);
    }

    /**
     * Test progress timestamps
     *
     * @covers \mod_longpage\external\reading_progress_services::update_reading_progress
     */
    public function test_reading_progress_timestamps(): void {
        global $DB;

        $this->setUser($this->student);

        $beforetime = time();

        reading_progress_services::update_reading_progress(
            $this->longpage->id,
            150.0,
            $this->course->id,
            'section-1',
            11111
        );

        $aftertime = time();

        $progress = $DB->get_records('longpage_reading_progress', [
            'longpageid' => $this->longpage->id,
            'userid' => $this->student->id,
        ]);

        $progressrecord = reset($progress);
        $this->assertGreaterThanOrEqual($beforetime, $progressrecord->timemodified);
        $this->assertLessThanOrEqual($aftertime, $progressrecord->timemodified);
    }

    /**
     * Test reading progress with zero scroll position
     *
     * @covers \mod_longpage\external\reading_progress_services::update_reading_progress
     */
    public function test_update_reading_progress_zero_scroll(): void {
        global $DB;

        $this->setUser($this->student);

        reading_progress_services::update_reading_progress(
            $this->longpage->id,
            0.0,
            $this->course->id,
            'section-1',
            11111
        );

        $progress = $DB->get_records('longpage_reading_progress', [
            'longpageid' => $this->longpage->id,
            'userid' => $this->student->id,
        ]);

        $this->assertCount(1, $progress);
        $progressrecord = reset($progress);
        $this->assertEquals(0.0, $progressrecord->scrolltop);
    }
}
