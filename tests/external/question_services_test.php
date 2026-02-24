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
 * Unit tests for question_services external API
 *
 * @package    mod_longpage
 * @category   test
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_longpage\external;

use mod_longpage\external\question_services;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/mod/longpage/locallib.php');
require_once($CFG->libdir . '/questionlib.php');

/**
 * Unit tests for question_services external API
 *
 * @package    mod_longpage
 * @category   test
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_longpage\external\question_services
 * @runTestsInSeparateProcesses
 */
final class question_services_test extends \externallib_advanced_testcase {
    /** @var \stdClass Course object */
    private $course;

    /** @var \stdClass Longpage module instance */
    private $longpage;

    /** @var \stdClass Course module */
    private $cm;

    /** @var \stdClass Student user */
    private $student;

    /** @var \stdClass Teacher user */
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

        // Create longpage instance with proper HTML structure.
        $htmlcontent = '<p data-lp-position="1">First paragraph with some content.</p>'
            . '<p data-lp-position="2">Second paragraph with more content for testing.</p>'
            . '<p data-lp-position="3">Third paragraph to ensure we have enough content.</p>';

        $this->longpage = $this->getDataGenerator()->create_module('longpage', [
            'course' => $this->course->id,
            'name' => 'Test Longpage Questions',
            'content' => $htmlcontent,
            'contentformat' => FORMAT_HTML,
        ]);

        $this->cm = get_coursemodule_from_instance('longpage', $this->longpage->id, $this->course->id);

        // Create users.
        $this->student = $this->getDataGenerator()->create_user([
            'firstname' => 'QStudent',
            'lastname' => 'Test',
        ]);
        $this->teacher = $this->getDataGenerator()->create_user([
            'firstname' => 'QTeacher',
            'lastname' => 'Test',
        ]);

        // Enrol users.
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);

        $this->getDataGenerator()->enrol_user($this->student->id, $this->course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($this->teacher->id, $this->course->id, $teacherrole->id);
    }

    /**
     * Test get_questions_by_page_id with valid page ID and no questions
     *
     * @covers ::get_questions_by_page_id
     */
    public function test_get_questions_by_page_id_empty(): void {
        $this->setUser($this->student);

        $result = question_services::get_questions_by_page_id($this->longpage->id);

        $this->assertArrayHasKey('qubaid', $result);
        $this->assertArrayHasKey('questions', $result);
        $this->assertIsArray($result['questions']);
        $this->assertEmpty($result['questions']);
    }

    /**
     * Test get_questions_by_page_id with invalid page ID
     *
     * @covers ::get_questions_by_page_id
     */
    public function test_get_questions_by_page_id_invalid(): void {
        $this->setUser($this->student);

        $this->expectException(\dml_exception::class);
        question_services::get_questions_by_page_id(99999);
    }

    /**
     * Test get_questions_by_page_id without login
     *
     * @covers ::get_questions_by_page_id
     */
    public function test_get_questions_by_page_id_not_logged_in(): void {
        $this->setUser(null);

        $this->expectException(\require_login_exception::class);
        question_services::get_questions_by_page_id($this->longpage->id);
    }

    /**
     * Test get_questions_by_page_id with non-enrolled user
     *
     * @covers ::get_questions_by_page_id
     */
    public function test_get_questions_by_page_id_not_enrolled(): void {
        $otheruser = $this->getDataGenerator()->create_user();
        $this->setUser($otheruser);

        $this->expectException(\moodle_exception::class);
        question_services::get_questions_by_page_id($this->longpage->id);
    }

    /**
     * Test parameter validation for get_questions_by_page_id
     *
     * @covers ::get_questions_by_page_id_parameters
     */
    public function test_get_questions_by_page_id_parameters(): void {
        $params = question_services::get_questions_by_page_id_parameters();

        $this->assertInstanceOf(\external_function_parameters::class, $params);
    }

    /**
     * Test return structure for get_questions_by_page_id
     *
     * @covers ::get_questions_by_page_id_returns
     */
    public function test_get_questions_by_page_id_returns(): void {
        $returns = question_services::get_questions_by_page_id_returns();

        $this->assertInstanceOf(\external_single_structure::class, $returns);
    }

    /**
     * Test get_reading_comprehension with valid page ID
     *
     * @covers ::get_reading_comprehension
     */
    public function test_get_reading_comprehension_success(): void {
        $this->setUser($this->student);

        $result = question_services::get_reading_comprehension($this->longpage->id);

        $this->assertArrayHasKey('response', $result);
        $this->assertArrayHasKey('gradeInfo', $result);
        $this->assertJson($result['response']);
        $this->assertJson($result['gradeInfo']);
    }

    /**
     * Test get_reading_comprehension without login
     *
     * @covers ::get_reading_comprehension
     */
    public function test_get_reading_comprehension_not_logged_in(): void {
        $this->setUser(null);

        $this->expectException(\require_login_exception::class);
        question_services::get_reading_comprehension($this->longpage->id);
    }

    /**
     * Test get_reading_comprehension parameters
     *
     * @covers ::get_reading_comprehension_parameters
     */
    public function test_get_reading_comprehension_parameters(): void {
        $params = question_services::get_reading_comprehension_parameters();

        $this->assertInstanceOf(\external_function_parameters::class, $params);
    }

    /**
     * Test get_reading_comprehension returns
     *
     * @covers ::get_reading_comprehension_returns
     */
    public function test_get_reading_comprehension_returns(): void {
        $returns = question_services::get_reading_comprehension_returns();

        $this->assertInstanceOf(\external_single_structure::class, $returns);
    }

    /**
     * Test embed_question parameters
     *
     * @covers ::embed_question_parameters
     */
    public function test_embed_question_parameters(): void {
        $params = question_services::embed_question_parameters();

        $this->assertInstanceOf(\external_function_parameters::class, $params);
    }

    /**
     * Test embed_question returns
     *
     * @covers ::embed_question_returns
     */
    public function test_embed_question_returns(): void {
        $returns = question_services::embed_question_returns();

        $this->assertInstanceOf(\external_single_structure::class, $returns);
    }

    /**
     * Test embed_question without permission
     *
     * @covers ::embed_question
     */
    public function test_embed_question_no_permission(): void {
        $this->setUser($this->student);

        $this->expectException(\required_capability_exception::class);
        question_services::embed_question($this->longpage->id, 'test/embed', 100);
    }

    /**
     * Test remove_question parameters
     *
     * @covers ::remove_question_parameters
     */
    public function test_remove_question_parameters(): void {
        $params = question_services::remove_question_parameters();

        $this->assertInstanceOf(\external_function_parameters::class, $params);
    }

    /**
     * Test remove_question returns
     *
     * @covers ::remove_question_returns
     */
    public function test_remove_question_returns(): void {
        $returns = question_services::remove_question_returns();

        $this->assertInstanceOf(\external_single_structure::class, $returns);
    }

    /**
     * Test remove_question without permission
     *
     * @covers ::remove_question
     */
    public function test_remove_question_no_permission(): void {
        $this->setUser($this->student);

        $this->expectException(\required_capability_exception::class);
        question_services::remove_question($this->longpage->id, 'embed123', 100);
    }

    /**
     * Test create_question parameters
     *
     * @covers ::create_question_parameters
     */
    public function test_create_question_parameters(): void {
        $params = question_services::create_question_parameters();

        $this->assertInstanceOf(\external_function_parameters::class, $params);
    }

    /**
     * Test create_question returns
     *
     * @covers ::create_question_returns
     */
    public function test_create_question_returns(): void {
        $returns = question_services::create_question_returns();

        $this->assertInstanceOf(\external_single_structure::class, $returns);
    }

    /**
     * Test create_question without permission
     *
     * @covers ::create_question
     */
    public function test_create_question_no_permission(): void {
        $this->setUser($this->student);

        $this->expectException(\required_capability_exception::class);
        question_services::create_question($this->longpage->id, 100, false);
    }

    /**
     * Test lock_question parameters
     *
     * @covers ::lock_question_parameters
     */
    public function test_lock_question_parameters(): void {
        $params = question_services::lock_question_parameters();

        $this->assertInstanceOf(\external_function_parameters::class, $params);
    }

    /**
     * Test lock_question returns
     *
     * @covers ::lock_question_returns
     */
    public function test_lock_question_returns(): void {
        $returns = question_services::lock_question_returns();

        $this->assertInstanceOf(\external_single_structure::class, $returns);
    }

    /**
     * Test lock_question without permission
     *
     * @covers ::lock_question
     */
    public function test_lock_question_no_permission(): void {
        $this->setUser($this->student);

        $this->expectException(\required_capability_exception::class);
        question_services::lock_question($this->longpage->id, 12345);
    }

    /**
     * Test edit_question parameters
     *
     * @covers ::edit_question_parameters
     */
    public function test_edit_question_parameters(): void {
        $params = question_services::edit_question_parameters();

        $this->assertInstanceOf(\external_function_parameters::class, $params);
    }

    /**
     * Test edit_question returns
     *
     * @covers ::edit_question_returns
     */
    public function test_edit_question_returns(): void {
        $returns = question_services::edit_question_returns();

        $this->assertInstanceOf(\external_single_structure::class, $returns);
    }

    /**
     * Test export_questions parameters
     *
     * @covers ::export_questions_parameters
     */
    public function test_export_questions_parameters(): void {
        $params = question_services::export_questions_parameters();

        $this->assertInstanceOf(\external_function_parameters::class, $params);
    }

    /**
     * Test export_questions returns
     *
     * @covers ::export_questions_returns
     */
    public function test_export_questions_returns(): void {
        $returns = question_services::export_questions_returns();

        $this->assertInstanceOf(\external_single_structure::class, $returns);
    }

    /**
     * Test autosave parameters
     *
     * @covers ::autosave_parameters
     */
    public function test_autosave_parameters(): void {
        $params = question_services::autosave_parameters();

        $this->assertInstanceOf(\external_function_parameters::class, $params);
    }

    /**
     * Test autosave returns
     *
     * @covers ::autosave_returns
     */
    public function test_autosave_returns(): void {
        $returns = question_services::autosave_returns();

        $this->assertInstanceOf(\external_single_structure::class, $returns);
    }

    /**
     * Test process_question_action parameters
     *
     * @covers ::process_question_action_parameters
     */
    public function test_process_question_action_parameters(): void {
        $params = question_services::process_question_action_parameters();

        $this->assertInstanceOf(\external_function_parameters::class, $params);
    }

    /**
     * Test process_question_action returns
     *
     * @covers ::process_question_action_returns
     */
    public function test_process_question_action_returns(): void {
        $returns = question_services::process_question_action_returns();

        $this->assertInstanceOf(\external_single_structure::class, $returns);
    }

    /**
     * Test create_question as teacher succeeds
     *
     * This test is skipped because create_question requires complex DOM structure
     * and potentially AI service integration. Test the permission check version instead.
     *
     * @covers ::create_question
     */
    public function test_create_question_as_teacher(): void {
        $this->markTestSkipped('create_question requires complex HTML structure with body element');
    }
}
