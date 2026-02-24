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
 * Unit tests for user_services external API
 *
 * @package    mod_longpage
 * @category   test
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_longpage\external;

use mod_longpage\external\user_services;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/mod/longpage/locallib.php');

/**
 * Unit tests for user_services external API
 *
 * @package    mod_longpage
 * @category   test
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_longpage\external\user_services
 * @runTestsInSeparateProcesses
 */
final class user_services_test extends \externallib_advanced_testcase {
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

    /** @var stdClass Editing teacher user */
    private $editingteacher;

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
            'content' => 'Test content for user services',
        ]);

        $this->cm = get_coursemodule_from_instance('longpage', $this->longpage->id, $this->course->id);

        // Create users.
        $this->student = $this->getDataGenerator()->create_user([
            'firstname' => 'John',
            'lastname' => 'Student',
        ]);
        $this->student2 = $this->getDataGenerator()->create_user([
            'firstname' => 'Jane',
            'lastname' => 'Learner',
        ]);
        $this->teacher = $this->getDataGenerator()->create_user([
            'firstname' => 'Tom',
            'lastname' => 'Teacher',
        ]);
        $this->editingteacher = $this->getDataGenerator()->create_user([
            'firstname' => 'Mary',
            'lastname' => 'Editor',
        ]);

        // Enrol users.
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $teacherrole = $DB->get_record('role', ['shortname' => 'teacher']);
        $editingteacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);

        $this->getDataGenerator()->enrol_user($this->student->id, $this->course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($this->student2->id, $this->course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($this->teacher->id, $this->course->id, $teacherrole->id);
        $this->getDataGenerator()->enrol_user($this->editingteacher->id, $this->course->id, $editingteacherrole->id);
    }

    /**
     * Test getting user roles by valid page ID
     *
     * @covers \mod_longpage\external\user_services::get_user_roles_by_pageid
     */
    public function test_get_user_roles_by_pageid_success(): void {
        $this->setUser($this->student);

        $result = user_services::get_user_roles_by_pageid($this->longpage->id);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('userroles', $result);
        $this->assertNotEmpty($result['userroles']);

        // Verify role structure.
        foreach ($result['userroles'] as $role) {
            $this->assertIsObject($role);
            $this->assertObjectHasProperty('id', $role);
            $this->assertObjectHasProperty('localname', $role);
            $this->assertObjectHasProperty('shortname', $role);
        }
    }

    /**
     * Test getting user roles returns standard Moodle roles
     *
     * @covers \mod_longpage\external\user_services::get_user_roles_by_pageid
     */
    public function test_get_user_roles_contains_standard_roles(): void {
        global $DB;

        $this->setUser($this->teacher);

        $result = user_services::get_user_roles_by_pageid($this->longpage->id);

        // Get expected role shortnames.
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $teacherrole = $DB->get_record('role', ['shortname' => 'teacher']);
        $editingteacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);

        $roleshortnames = array_map(function ($role) {
            return $role->shortname;
        }, $result['userroles']);

        // Verify standard roles are present.
        $this->assertContains('student', $roleshortnames);
        $this->assertContains('teacher', $roleshortnames);
        $this->assertContains('editingteacher', $roleshortnames);
    }

    /**
     * Test getting user roles fails with invalid page ID
     *
     * @covers \mod_longpage\external\user_services::get_user_roles_by_pageid
     */
    public function test_get_user_roles_by_pageid_invalid_id(): void {
        $this->setUser($this->student);

        $this->expectException(\dml_missing_record_exception::class);
        user_services::get_user_roles_by_pageid(99999);
    }

    /**
     * Test getting user roles requires authentication
     *
     * @covers \mod_longpage\external\user_services::get_user_roles_by_pageid
     */
    public function test_get_user_roles_by_pageid_not_authenticated(): void {
        $this->expectException(\moodle_exception::class);
        user_services::get_user_roles_by_pageid($this->longpage->id);
    }

    /**
     * Test getting enrolled users with their roles
     *
     * @covers \mod_longpage\external\user_services::get_enrolled_users_with_roles_by_pageid
     */
    public function test_get_enrolled_users_with_roles_success(): void {
        $this->setUser($this->teacher);

        $result = user_services::get_enrolled_users_with_roles_by_pageid($this->longpage->id);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('users', $result);
        $this->assertArrayHasKey('warnings', $result);

        // Should have 4 enrolled users.
        $this->assertCount(4, $result['users']);

        // Verify user structure.
        foreach ($result['users'] as $user) {
            $this->assertIsObject($user);
            $this->assertObjectHasProperty('id', $user);
            $this->assertObjectHasProperty('fullname', $user);
            $this->assertObjectHasProperty('roles', $user);
            $this->assertObjectHasProperty('profilelink', $user);
            $this->assertIsArray($user->roles);
            $this->assertNotEmpty($user->roles);
        }
    }

    /**
     * Test enrolled users have correct role assignments
     *
     * @covers \mod_longpage\external\user_services::get_enrolled_users_with_roles_by_pageid
     */
    public function test_get_enrolled_users_have_correct_roles(): void {
        global $DB;

        $this->setUser($this->editingteacher);

        $result = user_services::get_enrolled_users_with_roles_by_pageid($this->longpage->id);

        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $teacherrole = $DB->get_record('role', ['shortname' => 'teacher']);
        $editingteacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);

        // Find student in results.
        $studentuser = null;
        $teacheruser = null;
        foreach ($result['users'] as $user) {
            if ($user->id == $this->student->id) {
                $studentuser = $user;
            }
            if ($user->id == $this->teacher->id) {
                $teacheruser = $user;
            }
        }

        $this->assertNotNull($studentuser);
        $this->assertContains($studentrole->id, $studentuser->roles);

        $this->assertNotNull($teacheruser);
        $this->assertContains($teacherrole->id, $teacheruser->roles);
    }

    /**
     * Test enrolled users include profile URLs
     *
     * @covers \mod_longpage\external\user_services::get_enrolled_users_with_roles_by_pageid
     */
    public function test_get_enrolled_users_have_profile_links(): void {
        $this->setUser($this->teacher);

        $result = user_services::get_enrolled_users_with_roles_by_pageid($this->longpage->id);

        foreach ($result['users'] as $user) {
            $this->assertNotEmpty($user->profilelink);
            $this->assertStringContainsString('/user/view.php', $user->profilelink);
            $this->assertStringContainsString('id=' . $user->id, $user->profilelink);
            $this->assertStringContainsString('course=' . $this->course->id, $user->profilelink);
        }
    }

    /**
     * Test multiple students enrolled with student role
     *
     * @covers \mod_longpage\external\user_services::get_enrolled_users_with_roles_by_pageid
     */
    public function test_get_enrolled_users_multiple_students(): void {
        global $DB;

        $this->setUser($this->teacher);

        $result = user_services::get_enrolled_users_with_roles_by_pageid($this->longpage->id);

        $studentrole = $DB->get_record('role', ['shortname' => 'student']);

        // Count how many users have student role.
        $studentcount = 0;
        foreach ($result['users'] as $user) {
            if (in_array($studentrole->id, $user->roles)) {
                $studentcount++;
            }
        }

        // Should have 2 students enrolled.
        $this->assertEquals(2, $studentcount);
    }

    /**
     * Test different role types are properly identified
     *
     * @covers \mod_longpage\external\user_services::get_enrolled_users_with_roles_by_pageid
     */
    public function test_get_enrolled_users_different_role_types(): void {
        global $DB;

        $this->setUser($this->editingteacher);

        $result = user_services::get_enrolled_users_with_roles_by_pageid($this->longpage->id);

        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $teacherrole = $DB->get_record('role', ['shortname' => 'teacher']);
        $editingteacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);

        $rolesfound = [
            'student' => false,
            'teacher' => false,
            'editingteacher' => false,
        ];

        foreach ($result['users'] as $user) {
            if (in_array($studentrole->id, $user->roles)) {
                $rolesfound['student'] = true;
            }
            if (in_array($teacherrole->id, $user->roles)) {
                $rolesfound['teacher'] = true;
            }
            if (in_array($editingteacherrole->id, $user->roles)) {
                $rolesfound['editingteacher'] = true;
            }
        }

        // Verify all three role types are present.
        $this->assertTrue($rolesfound['student']);
        $this->assertTrue($rolesfound['teacher']);
        $this->assertTrue($rolesfound['editingteacher']);
    }

    /**
     * Test user profile fields are correctly returned
     *
     * @covers \mod_longpage\external\user_services::get_enrolled_users_with_roles_by_pageid
     */
    public function test_get_enrolled_users_profile_fields(): void {
        $this->setUser($this->teacher);

        $result = user_services::get_enrolled_users_with_roles_by_pageid($this->longpage->id);

        // Find our known student.
        $studentuser = null;
        foreach ($result['users'] as $user) {
            if ($user->id == $this->student->id) {
                $studentuser = $user;
                break;
            }
        }

        $this->assertNotNull($studentuser);
        $this->assertEquals($this->student->id, $studentuser->id);
        $this->assertObjectHasProperty('firstname', $studentuser);
        $this->assertObjectHasProperty('lastname', $studentuser);
        $this->assertEquals('John', $studentuser->firstname);
        $this->assertEquals('Student', $studentuser->lastname);
    }

    /**
     * Test permissions - student can view enrolled users
     *
     * @covers \mod_longpage\external\user_services::get_enrolled_users_with_roles_by_pageid
     */
    public function test_get_enrolled_users_student_permission(): void {
        $this->setUser($this->student);

        $result = user_services::get_enrolled_users_with_roles_by_pageid($this->longpage->id);

        // Students should be able to see enrolled users.
        $this->assertIsArray($result);
        $this->assertArrayHasKey('users', $result);
        $this->assertNotEmpty($result['users']);
    }

    /**
     * Test getting enrolled users fails with invalid page ID
     *
     * @covers \mod_longpage\external\user_services::get_enrolled_users_with_roles_by_pageid
     */
    public function test_get_enrolled_users_invalid_page_id(): void {
        $this->setUser($this->teacher);

        $this->expectException(\dml_missing_record_exception::class);
        user_services::get_enrolled_users_with_roles_by_pageid(99999);
    }

    /**
     * Test getting enrolled users requires authentication
     *
     * @covers \mod_longpage\external\user_services::get_enrolled_users_with_roles_by_pageid
     */
    public function test_get_enrolled_users_not_authenticated(): void {
        $this->expectException(\moodle_exception::class);
        user_services::get_enrolled_users_with_roles_by_pageid($this->longpage->id);
    }
}
