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
 * Unit tests for page_services external API
 *
 * @package    mod_longpage
 * @category   test
 * @copyright  2024 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_longpage\external;

use mod_longpage\external\page_services;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/mod/longpage/locallib.php');

/**
 * Unit tests for page_services external API
 *
 * @package    mod_longpage
 * @category   test
 * @copyright  2024 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_longpage\external\page_services
 * @runTestsInSeparateProcesses
 */
final class page_services_test extends \externallib_advanced_testcase {
    /** @var stdClass Course 1 */
    private $course1;

    /** @var stdClass Course 2 */
    private $course2;

    /** @var stdClass Longpage instance in course 1 */
    private $longpage1;

    /** @var stdClass Longpage instance in course 2 */
    private $longpage2;

    /** @var stdClass Another longpage instance in course 1 */
    private $longpage3;

    /** @var stdClass Course module for longpage1 */
    private $cm1;

    /** @var stdClass Course module for longpage2 */
    private $cm2;

    /** @var stdClass Course module for longpage3 */
    private $cm3;

    /** @var stdClass Student user */
    private $student;

    /** @var stdClass Teacher user */
    private $teacher;

    /**
     * Set up test data
     */
    protected function setUp(): void {
        global $DB;
        parent::setUp();

        $this->resetAfterTest(true);

        // Create courses.
        $this->course1 = $this->getDataGenerator()->create_course();
        $this->course2 = $this->getDataGenerator()->create_course();

        // Create longpage instances.
        $this->longpage1 = $this->getDataGenerator()->create_module('longpage', [
            'course' => $this->course1->id,
            'name' => 'Test Longpage 1',
            'intro' => 'Introduction for page 1',
            'content' => 'Content for page 1',
        ]);

        $this->longpage2 = $this->getDataGenerator()->create_module('longpage', [
            'course' => $this->course2->id,
            'name' => 'Test Longpage 2',
            'intro' => 'Introduction for page 2',
            'content' => 'Content for page 2',
        ]);

        $this->longpage3 = $this->getDataGenerator()->create_module('longpage', [
            'course' => $this->course1->id,
            'name' => 'Test Longpage 3',
            'intro' => 'Introduction for page 3',
            'content' => 'Content for page 3',
        ]);

        // Get course modules.
        $this->cm1 = get_coursemodule_from_instance('longpage', $this->longpage1->id, $this->course1->id);
        $this->cm2 = get_coursemodule_from_instance('longpage', $this->longpage2->id, $this->course2->id);
        $this->cm3 = get_coursemodule_from_instance('longpage', $this->longpage3->id, $this->course1->id);

        // Create users.
        $this->student = $this->getDataGenerator()->create_user();
        $this->teacher = $this->getDataGenerator()->create_user();

        // Enrol users.
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);

        $this->getDataGenerator()->enrol_user($this->student->id, $this->course1->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($this->student->id, $this->course2->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($this->teacher->id, $this->course1->id, $teacherrole->id);
        $this->getDataGenerator()->enrol_user($this->teacher->id, $this->course2->id, $teacherrole->id);
    }

    /**
     * Test getting pages for a single course
     *
     * @covers \mod_longpage\external\page_services::get_pages_by_courses
     */
    public function test_get_pages_by_courses_single_course(): void {
        $this->setUser($this->student);

        $result = page_services::get_pages_by_courses([$this->course1->id]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('pages', $result);
        $this->assertArrayHasKey('warnings', $result);
        $this->assertCount(2, $result['pages']); // Two pages in course1.
        $this->assertEmpty($result['warnings']);

        // Verify page data.
        $pagenamesincourse1 = array_column($result['pages'], 'name');
        $this->assertContains('Test Longpage 1', $pagenamesincourse1);
        $this->assertContains('Test Longpage 3', $pagenamesincourse1);
    }

    /**
     * Test getting pages for multiple courses
     *
     * @covers \mod_longpage\external\page_services::get_pages_by_courses
     */
    public function test_get_pages_by_courses_multiple_courses(): void {
        $this->setUser($this->student);

        $result = page_services::get_pages_by_courses([$this->course1->id, $this->course2->id]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('pages', $result);
        $this->assertCount(3, $result['pages']); // Total three pages across both courses.

        // Verify all pages are returned.
        $pagenames = array_column($result['pages'], 'name');
        $this->assertContains('Test Longpage 1', $pagenames);
        $this->assertContains('Test Longpage 2', $pagenames);
        $this->assertContains('Test Longpage 3', $pagenames);
    }

    /**
     * Test getting all viewable pages (empty course list)
     *
     * @covers \mod_longpage\external\page_services::get_pages_by_courses
     */
    public function test_get_pages_by_courses_all_courses(): void {
        $this->setUser($this->student);

        // Empty array should return all pages the user can view.
        $result = page_services::get_pages_by_courses([]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('pages', $result);
        $this->assertCount(3, $result['pages']); // Student is enrolled in both courses.

        $pagenames = array_column($result['pages'], 'name');
        $this->assertContains('Test Longpage 1', $pagenames);
        $this->assertContains('Test Longpage 2', $pagenames);
        $this->assertContains('Test Longpage 3', $pagenames);
    }

    /**
     * Test getting pages with invalid course ID
     *
     * @covers \mod_longpage\external\page_services::get_pages_by_courses
     */
    public function test_get_pages_by_courses_invalid_course(): void {
        $this->setUser($this->student);

        $invalidcourseid = 99999;
        $result = page_services::get_pages_by_courses([$invalidcourseid]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('warnings', $result);
        $this->assertNotEmpty($result['warnings']);
        $this->assertEmpty($result['pages']);
    }

    /**
     * Test getting pages for course without enrollment
     *
     * @covers \mod_longpage\external\page_services::get_pages_by_courses
     */
    public function test_get_pages_by_courses_not_enrolled(): void {
        // Create a third course and page.
        $course3 = $this->getDataGenerator()->create_course();
        $longpage4 = $this->getDataGenerator()->create_module('longpage', [
            'course' => $course3->id,
            'name' => 'Test Longpage 4',
        ]);

        $this->setUser($this->student);

        // Student is not enrolled in course3.
        $result = page_services::get_pages_by_courses([$course3->id]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('warnings', $result);
        $this->assertNotEmpty($result['warnings']);
    }

    /**
     * Test page data structure and formatting
     *
     * @covers \mod_longpage\external\page_services::get_pages_by_courses
     */
    public function test_get_pages_by_courses_data_structure(): void {
        $this->setUser($this->student);

        $result = page_services::get_pages_by_courses([$this->course1->id]);

        $this->assertCount(2, $result['pages']);

        $page = $result['pages'][0];
        $this->assertObjectHasProperty('id', $page);
        $this->assertObjectHasProperty('coursemodule', $page);
        $this->assertObjectHasProperty('course', $page);
        $this->assertObjectHasProperty('name', $page);
        $this->assertObjectHasProperty('intro', $page);
        $this->assertObjectHasProperty('introformat', $page);
        $this->assertObjectHasProperty('content', $page);
        $this->assertObjectHasProperty('contentformat', $page);
        $this->assertObjectHasProperty('timemodified', $page);
    }

    /**
     * Test viewing a page
     *
     * @covers \mod_longpage\external\page_services::view_page
     */
    public function test_view_page(): void {
        global $DB;

        $this->setUser($this->student);

        $result = page_services::view_page($this->longpage1->id);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('warnings', $result);
        $this->assertTrue($result['status']);
        $this->assertEmpty($result['warnings']);
    }

    /**
     * Test viewing page triggers event
     *
     * @covers \mod_longpage\external\page_services::view_page
     */
    public function test_view_page_triggers_event(): void {
        $this->setUser($this->student);

        $sink = $this->redirectEvents();

        page_services::view_page($this->longpage1->id);

        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events);
        $event = reset($events);

        $this->assertInstanceOf('\mod_longpage\event\course_module_viewed', $event);
        $this->assertEquals($this->longpage1->id, $event->objectid);
        $this->assertEquals($this->student->id, $event->userid);
        $this->assertEquals($this->course1->id, $event->courseid);
    }

    /**
     * Test viewing page with completion enabled
     *
     * @covers \mod_longpage\external\page_services::view_page
     */
    public function test_view_page_updates_completion(): void {
        global $CFG, $DB;

        require_once($CFG->libdir . '/completionlib.php');

        // Enable completion for the course.
        $DB->set_field('course', 'enablecompletion', COMPLETION_ENABLED, ['id' => $this->course1->id]);

        // Set completion for the longpage module - must set completionview.
        $DB->set_field('course_modules', 'completion', COMPLETION_TRACKING_AUTOMATIC, ['id' => $this->cm1->id]);
        $DB->set_field('course_modules', 'completionview', 1, ['id' => $this->cm1->id]);

        // Rebuild cache.
        rebuild_course_cache($this->course1->id, true);
        $this->course1 = $DB->get_record('course', ['id' => $this->course1->id]);

        $this->setUser($this->student);

        // View the page.
        page_services::view_page($this->longpage1->id);

        // Check completion.
        $completion = new \completion_info($this->course1);
        $cm = get_coursemodule_from_instance('longpage', $this->longpage1->id, $this->course1->id, true);
        $completiondata = $completion->get_data($cm, false, $this->student->id);

        $this->assertEquals(COMPLETION_COMPLETE, $completiondata->completionstate);
    }

    /**
     * Test viewing page without permission
     *
     * @covers \mod_longpage\external\page_services::view_page
     */
    public function test_view_page_without_permission(): void {
        // Create a user not enrolled in any course.
        $unenrolleduser = $this->getDataGenerator()->create_user();
        $this->setUser($unenrolleduser);

        $this->expectException(\require_login_exception::class);
        page_services::view_page($this->longpage1->id);
    }

    /**
     * Test viewing page with invalid page ID
     *
     * @covers \mod_longpage\external\page_services::view_page
     */
    public function test_view_page_invalid_id(): void {
        $this->setUser($this->student);

        $invalidpageid = 99999;

        $this->expectException(\dml_missing_record_exception::class);
        page_services::view_page($invalidpageid);
    }

    /**
     * Test viewing page as teacher
     *
     * @covers \mod_longpage\external\page_services::view_page
     */
    public function test_view_page_as_teacher(): void {
        $this->setUser($this->teacher);

        $result = page_services::view_page($this->longpage1->id);

        $this->assertIsArray($result);
        $this->assertTrue($result['status']);
        $this->assertEmpty($result['warnings']);
    }

    /**
     * Test getting pages respects visibility
     *
     * @covers \mod_longpage\external\page_services::get_pages_by_courses
     */
    public function test_get_pages_respects_visibility(): void {
        global $DB;

        // Hide longpage1.
        set_coursemodule_visible($this->cm1->id, 0);

        $this->setUser($this->student);

        $result = page_services::get_pages_by_courses([$this->course1->id]);

        // Student should only see visible page (longpage3).
        $this->assertCount(1, $result['pages']);
        $this->assertEquals('Test Longpage 3', $result['pages'][0]->name);

        // Teacher should see both pages.
        $this->setUser($this->teacher);
        $result = page_services::get_pages_by_courses([$this->course1->id]);
        $this->assertCount(2, $result['pages']);
    }

    /**
     * Test getting pages with guest user
     *
     * @covers \mod_longpage\external\page_services::get_pages_by_courses
     */
    public function test_get_pages_as_guest(): void {
        $this->setGuestUser();

        $result = page_services::get_pages_by_courses([]);

        // Guest should not see any pages in private courses.
        $this->assertEmpty($result['pages']);
    }
}
