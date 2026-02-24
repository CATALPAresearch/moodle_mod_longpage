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
 * Unit tests for questions_bank_services external API
 *
 * @package    mod_longpage
 * @category   test
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_longpage\external;

use mod_longpage\external\questions_bank_services;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/mod/longpage/locallib.php');
require_once($CFG->libdir . '/questionlib.php');

/**
 * Unit tests for questions_bank_services external API
 *
 * @package    mod_longpage
 * @category   test
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_longpage\external\questions_bank_services
 * @runTestsInSeparateProcesses
 */
final class questions_bank_services_test extends \externallib_advanced_testcase {
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

        // Create longpage instance.
        $this->longpage = $this->getDataGenerator()->create_module('longpage', [
            'course' => $this->course->id,
            'name' => 'Test Longpage Questions Bank',
            'content' => 'Test content for question bank services',
        ]);

        $this->cm = get_coursemodule_from_instance('longpage', $this->longpage->id, $this->course->id);

        // Create users.
        $this->student = $this->getDataGenerator()->create_user([
            'firstname' => 'QBStudent',
            'lastname' => 'Test',
        ]);
        $this->teacher = $this->getDataGenerator()->create_user([
            'firstname' => 'QBTeacher',
            'lastname' => 'Test',
        ]);

        // Enrol users.
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);

        $this->getDataGenerator()->enrol_user($this->student->id, $this->course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($this->teacher->id, $this->course->id, $teacherrole->id);
    }

    /**
     * Test get_questions_for_page with valid page ID and no questions
     *
     * @covers ::get_questions_for_page
     */
    public function test_get_questions_for_page_empty(): void {
        $this->setUser($this->student);

        $result = questions_bank_services::get_questions_for_page($this->longpage->id);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test get_questions_for_page with invalid page ID
     *
     * @covers ::get_questions_for_page
     */
    public function test_get_questions_for_page_invalid(): void {
        $this->setUser($this->student);

        $this->expectException(\dml_exception::class);
        questions_bank_services::get_questions_for_page(99999);
    }

    /**
     * Test get_questions_for_page without login
     *
     * @covers ::get_questions_for_page
     */
    public function test_get_questions_for_page_not_logged_in(): void {
        $this->setUser(null);

        $this->expectException(\require_login_exception::class);
        questions_bank_services::get_questions_for_page($this->longpage->id);
    }

    /**
     * Test get_questions_for_page with non-enrolled user
     *
     * @covers ::get_questions_for_page
     */
    public function test_get_questions_for_page_not_enrolled(): void {
        $otheruser = $this->getDataGenerator()->create_user();
        $this->setUser($otheruser);

        $this->expectException(\moodle_exception::class);
        questions_bank_services::get_questions_for_page($this->longpage->id);
    }

    /**
     * Test get_question_detail with invalid question ID
     *
     * @covers ::get_question_detail
     */
    public function test_get_question_detail_invalid(): void {
        $this->setUser($this->student);

        $this->expectException(\moodle_exception::class);
        questions_bank_services::get_question_detail(99999, $this->longpage->id);
    }

    /**
     * Test get_question_detail without login
     *
     * @covers ::get_question_detail
     */
    public function test_get_question_detail_not_logged_in(): void {
        $this->setUser(null);

        $this->expectException(\require_login_exception::class);
        questions_bank_services::get_question_detail(1, $this->longpage->id);
    }

    /**
     * Test parameter validation for get_questions_for_page
     *
     * @covers ::get_questions_for_page_parameters
     */
    public function test_get_questions_for_page_parameters(): void {
        $params = questions_bank_services::get_questions_for_page_parameters();

        $this->assertInstanceOf(\external_function_parameters::class, $params);
    }

    /**
     * Test return structure for get_questions_for_page
     *
     * @covers ::get_questions_for_page_returns
     */
    public function test_get_questions_for_page_returns(): void {
        $returns = questions_bank_services::get_questions_for_page_returns();

        $this->assertInstanceOf(\external_multiple_structure::class, $returns);
    }

    /**
     * Test parameter validation for get_question_detail
     *
     * @covers ::get_question_detail_parameters
     */
    public function test_get_question_detail_parameters(): void {
        $params = questions_bank_services::get_question_detail_parameters();

        $this->assertInstanceOf(\external_function_parameters::class, $params);
    }

    /**
     * Test return structure for get_question_detail
     *
     * @covers ::get_question_detail_returns
     */
    public function test_get_question_detail_returns(): void {
        $returns = questions_bank_services::get_question_detail_returns();

        $this->assertInstanceOf(\external_single_structure::class, $returns);
    }
}
