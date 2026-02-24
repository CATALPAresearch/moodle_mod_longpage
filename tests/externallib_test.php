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
 * External mod_longpage functions unit tests
 *
 * @package    mod_longpage
 * @category   external
 * @copyright  2015 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * External mod_longpage functions unit tests
 *
 * @package    mod_longpage
 * @category   external
 * @copyright  2015 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */
final class externallib_test extends externallib_advanced_testcase {
    /**
     * Test view_page
     * @covers ::mod_longpage_view_page
     */
    public function test_view_page(): void {
        $this->markTestSkipped('This test uses the deprecated mod_longpage_external class. ' .
            'Use page_services_test.php instead which tests the namespaced page_services class.');
    }

    /**
     * Test test_mod_longpage_get_pages_by_courses
     * @covers ::mod_longpage_get_pages_by_courses
     */
    public function test_mod_longpage_get_pages_by_courses(): void {
        $this->markTestSkipped('This test uses the deprecated mod_longpage_external class. ' .
            'Use page_services_test.php instead which tests the namespaced page_services class.');
    }
}
