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
 * Unit tests for highlight_services external API
 *
 * @package    mod_longpage
 * @category   test
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_longpage\external;

use mod_longpage\external\highlight_services;
use mod_longpage\local\constants\annotation_type;
use mod_longpage\local\constants\selector;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/mod/longpage/locallib.php');

/**
 * Unit tests for highlight_services external API
 *
 * @package    mod_longpage
 * @category   test
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_longpage\external\highlight_services
 */
final class highlight_services_test extends \externallib_advanced_testcase {
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
            'content' => '<p>Test content for highlights</p>',
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
     * Create a highlight annotation for testing
     *
     * @param int $userid User ID who creates the highlight
     * @param string $styleclass Style class for the highlight
     * @return int Annotation ID
     */
    private function create_test_highlight($userid, $styleclass = 'highlight-yellow') {
        global $DB;

        $annotationid = $DB->insert_record('longpage_annotations', [
            'longpageid' => $this->longpage->id,
            'type' => annotation_type::HIGHLIGHT,
            'creatorid' => $userid,
            'ispublic' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $targetid = $DB->insert_record('longpage_annotation_targets', [
            'annotationid' => $annotationid,
            'styleclass' => $styleclass,
        ]);

        $selectorid = $DB->insert_record('longpage_selectors', [
            'annotationtargetid' => $targetid,
            'type' => selector::TYPE_TEXT_QUOTE_SELECTOR,
        ]);

        $DB->insert_record('longpage_text_quote_selectrs', [
            'selectorid' => $selectorid,
            'exact' => 'Test content',
            'prefix' => '<p>',
            'suffix' => ' for',
        ]);

        return $annotationid;
    }

    /**
     * Test deleting own highlight successfully
     *
     * @covers \mod_longpage\external\highlight_services::delete_highlight
     */
    public function test_delete_highlight_success(): void {
        global $DB;

        $this->setUser($this->student);
        $highlightid = $this->create_test_highlight($this->student->id);

        // Verify highlight exists.
        $this->assertTrue($DB->record_exists('longpage_annotations', ['id' => $highlightid]));

        // Delete highlight.
        highlight_services::delete_highlight($highlightid);

        // Verify highlight is deleted.
        $this->assertFalse($DB->record_exists('longpage_annotations', ['id' => $highlightid]));
    }

    /**
     * Test deleting highlight also deletes annotation targets
     *
     * @covers \mod_longpage\external\highlight_services::delete_highlight
     */
    public function test_delete_highlight_deletes_targets(): void {
        global $DB;

        $this->setUser($this->student);
        $highlightid = $this->create_test_highlight($this->student->id);

        // Verify target exists.
        $target = $DB->get_record('longpage_annotation_targets', ['annotationid' => $highlightid]);
        $this->assertNotFalse($target);

        // Delete highlight.
        highlight_services::delete_highlight($highlightid);

        // Verify target is deleted.
        $this->assertFalse($DB->record_exists('longpage_annotation_targets', ['annotationid' => $highlightid]));
    }

    /**
     * Test deleting highlight also deletes selectors
     *
     * @covers \mod_longpage\external\highlight_services::delete_highlight
     */
    public function test_delete_highlight_deletes_selectors(): void {
        global $DB;

        $this->setUser($this->student);
        $highlightid = $this->create_test_highlight($this->student->id);

        // Get target and selector IDs.
        $target = $DB->get_record('longpage_annotation_targets', ['annotationid' => $highlightid]);
        $selector = $DB->get_record('longpage_selectors', ['annotationtargetid' => $target->id]);
        $this->assertNotFalse($selector);

        // Delete highlight.
        highlight_services::delete_highlight($highlightid);

        // Verify selector is deleted.
        $this->assertFalse($DB->record_exists('longpage_selectors', ['id' => $selector->id]));
    }

    /**
     * Test deleting highlight also deletes text quote selector data
     *
     * @covers \mod_longpage\external\highlight_services::delete_highlight
     */
    public function test_delete_highlight_cascade_deletion(): void {
        global $DB;

        $this->setUser($this->student);
        $highlightid = $this->create_test_highlight($this->student->id);

        // Get selector ID.
        $target = $DB->get_record('longpage_annotation_targets', ['annotationid' => $highlightid]);
        $selector = $DB->get_record('longpage_selectors', ['annotationtargetid' => $target->id]);

        // Verify text quote selector exists.
        $this->assertTrue($DB->record_exists('longpage_text_quote_selectrs', ['selectorid' => $selector->id]));

        // Delete highlight.
        highlight_services::delete_highlight($highlightid);

        // Verify text quote selector data is deleted.
        $this->assertFalse($DB->record_exists('longpage_text_quote_selectrs', ['selectorid' => $selector->id]));
    }

    /**
     * Test deleting highlight with invalid ID fails
     *
     * @covers \mod_longpage\external\highlight_services::delete_highlight
     */
    public function test_delete_highlight_invalid_id(): void {
        $this->setUser($this->student);

        $this->expectException(\dml_missing_record_exception::class);
        highlight_services::delete_highlight(99999);
    }

    /**
     * Test updating highlight color successfully
     *
     * @covers \mod_longpage\external\highlight_services::update_highlight
     */
    public function test_update_highlight_color_success(): void {
        global $DB;

        $this->setUser($this->student);
        $highlightid = $this->create_test_highlight($this->student->id, 'highlight-yellow');

        // Update highlight to blue.
        $result = highlight_services::update_highlight($highlightid, 'highlight-blue');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('annotation', $result);

        // Verify database was updated.
        $target = $DB->get_record('longpage_annotation_targets', ['annotationid' => $highlightid]);
        $this->assertEquals('highlight-blue', $target->styleclass);
    }

    /**
     * Test updating highlight updates modification time
     *
     * @covers \mod_longpage\external\highlight_services::update_highlight
     */
    public function test_update_highlight_updates_timemodified(): void {
        global $DB;

        $this->setUser($this->student);
        $highlightid = $this->create_test_highlight($this->student->id);

        // Get original modification time.
        $originaltimemodified = $DB->get_field('longpage_annotations', 'timemodified', ['id' => $highlightid]);

        // Wait a second and update.
        sleep(1);
        highlight_services::update_highlight($highlightid, 'highlight-green');

        // Verify time was updated.
        $newtimemodified = $DB->get_field('longpage_annotations', 'timemodified', ['id' => $highlightid]);
        $this->assertGreaterThan($originaltimemodified, $newtimemodified);
    }

    /**
     * Test updating highlight with different style classes
     *
     * @covers \mod_longpage\external\highlight_services::update_highlight
     */
    public function test_update_highlight_different_styles(): void {
        global $DB;

        $this->setUser($this->student);
        $highlightid = $this->create_test_highlight($this->student->id, 'highlight-yellow');

        $styles = ['highlight-red', 'highlight-green', 'highlight-blue', 'highlight-purple'];

        foreach ($styles as $style) {
            highlight_services::update_highlight($highlightid, $style);

            $target = $DB->get_record('longpage_annotation_targets', ['annotationid' => $highlightid]);
            $this->assertEquals($style, $target->styleclass);
        }
    }

    /**
     * Test user cannot update another user's highlight
     *
     * @covers \mod_longpage\external\highlight_services::update_highlight
     */
    public function test_update_highlight_permission_denied(): void {
        $this->setUser($this->student);
        $highlightid = $this->create_test_highlight($this->student->id);

        // Switch to different student.
        $this->setUser($this->student2);

        $this->expectException(\invalid_parameter_exception::class);
        $this->expectExceptionMessage('Highlight cannot be updated by user other than its creator');
        highlight_services::update_highlight($highlightid, 'highlight-blue');
    }

    /**
     * Test teacher cannot update student's highlight
     *
     * @covers \mod_longpage\external\highlight_services::update_highlight
     */
    public function test_update_highlight_teacher_cannot_update_student_highlight(): void {
        $this->setUser($this->student);
        $highlightid = $this->create_test_highlight($this->student->id);

        // Switch to teacher.
        $this->setUser($this->teacher);

        $this->expectException(\invalid_parameter_exception::class);
        $this->expectExceptionMessage('Highlight cannot be updated by user other than its creator');
        highlight_services::update_highlight($highlightid, 'highlight-red');
    }

    /**
     * Test updating highlight with invalid ID fails
     *
     * @covers \mod_longpage\external\highlight_services::update_highlight
     */
    public function test_update_highlight_invalid_id(): void {
        $this->setUser($this->student);

        $this->expectException(\dml_missing_record_exception::class);
        highlight_services::update_highlight(99999, 'highlight-blue');
    }

    /**
     * Test updating non-highlight annotation fails
     *
     * @covers \mod_longpage\external\highlight_services::update_highlight
     */
    public function test_update_highlight_wrong_type(): void {
        global $DB;

        $this->setUser($this->student);

        // Create a POST annotation instead of HIGHLIGHT.
        $annotationid = $DB->insert_record('longpage_annotations', [
            'longpageid' => $this->longpage->id,
            'type' => annotation_type::POST,
            'creatorid' => $this->student->id,
            'ispublic' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $targetid = $DB->insert_record('longpage_annotation_targets', [
            'annotationid' => $annotationid,
            'styleclass' => 'post-marker',
        ]);

        $this->expectException(\invalid_parameter_exception::class);
        $this->expectExceptionMessage('Annotation is no highlight');
        highlight_services::update_highlight($annotationid, 'highlight-blue');
    }

    /**
     * Test updating highlight returns complete annotation structure
     *
     * @covers \mod_longpage\external\highlight_services::update_highlight
     */
    public function test_update_highlight_returns_annotation(): void {
        $this->setUser($this->student);
        $highlightid = $this->create_test_highlight($this->student->id);

        $result = highlight_services::update_highlight($highlightid, 'highlight-purple');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('annotation', $result);
        $this->assertIsArray($result['annotation']);
        $this->assertArrayHasKey('id', $result['annotation']);
        $this->assertEquals($highlightid, $result['annotation']['id']);
        $this->assertArrayHasKey('target', $result['annotation']);
        $this->assertEquals('highlight-purple', $result['annotation']['target']['styleclass']);
    }

    /**
     * Test creating and deleting multiple highlights
     *
     * @covers \mod_longpage\external\highlight_services::delete_highlight
     */
    public function test_delete_multiple_highlights(): void {
        global $DB;

        $this->setUser($this->student);

        $highlight1 = $this->create_test_highlight($this->student->id, 'highlight-yellow');
        $highlight2 = $this->create_test_highlight($this->student->id, 'highlight-blue');
        $highlight3 = $this->create_test_highlight($this->student->id, 'highlight-green');

        // Verify all exist.
        $this->assertEquals(3, $DB->count_records('longpage_annotations', [
            'longpageid' => $this->longpage->id,
            'creatorid' => $this->student->id,
        ]));

        // Delete one highlight.
        highlight_services::delete_highlight($highlight2);

        // Verify only two remain.
        $this->assertEquals(2, $DB->count_records('longpage_annotations', [
            'longpageid' => $this->longpage->id,
            'creatorid' => $this->student->id,
        ]));

        $this->assertTrue($DB->record_exists('longpage_annotations', ['id' => $highlight1]));
        $this->assertFalse($DB->record_exists('longpage_annotations', ['id' => $highlight2]));
        $this->assertTrue($DB->record_exists('longpage_annotations', ['id' => $highlight3]));
    }
}
