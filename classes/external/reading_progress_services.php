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
 * Reading progress external services
 *
 * @package    mod_longpage
 * @category   external
 * @copyright  2020 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_longpage\external;

use external_function_parameters;
use external_single_structure;
use external_value;
use Exception;

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");

/**
 * Reading progress external services class
 *
 * @package    mod_longpage
 * @category   external
 * @copyright  2020 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reading_progress_services extends base_external {
    /**
     * Update reading progress.
     *
     * @param int $pageid Page ID
     * @param float $scrolltop Scroll top position
     * @param int $courseid Course ID
     * @param string $section Section
     * @param int $sectionhash Section hash
     */
    public static function update_reading_progress($pageid, $scrolltop, $courseid, $section, $sectionhash) {
        global $DB, $USER;

        self::validate_parameters(
            self::update_reading_progress_parameters(),
            [
                'longpageid' => $pageid,
                'scrolltop' => $scrolltop,
                'courseid' => $courseid,
                'section' => $section,
                'sectionhash' => $sectionhash,
            ]
        );
        $context = self::validate_cm_context($pageid);
        require_capability('mod/longpage:view', $context);

        try {
            global $CFG;  // Add global reference for CFG

            $transaction = $DB->start_delegated_transaction();
            $DB->insert_record('longpage_reading_progress', [
                'longpageid' => $pageid,
                'scrolltop' => $scrolltop,
                'userid' => $USER->id,
                'timemodified' => time(),
                'course' => $courseid,
                'section' => $section,
                'sectionhash' => $sectionhash,
            ]);
            $transaction->allow_commit();
        } catch (Exception $e) {
            $transaction->rollback($e);
            throw $e;
        }
    }

    /**
     * Parameters for update_reading_progress.
     *
     * @return external_function_parameters
     */
    public static function update_reading_progress_parameters() {
        return new external_function_parameters([
            'longpageid' => new external_value(PARAM_INT),
            'scrolltop' => new external_value(PARAM_FLOAT),
            'courseid' => new external_value(PARAM_INT),
            'section' => new external_value(PARAM_TEXT),
            'sectionhash' => new external_value(PARAM_INT),
        ]);
    }

    /**
     * Return structure for update_reading_progress.
     *
     * @return null
     */
    public static function update_reading_progress_returns() {
        return null;
    }

    /**
     * Get reading progress.
     *
     * @param int $courseid Course ID
     * @param int $longpageid Long page ID
     * @return array
     */
    public static function get_reading_progress($courseid, $longpageid) {
        global $CFG, $DB, $USER;

        $r = new \stdClass();
        $r->userid = $USER->id;
        $r->courseid = $courseid;
        $r->pageid = $longpageid;

        self::validate_parameters(
            self::get_reading_progress_parameters(),
            ['courseid' => $courseid, 'longpageid' => $longpageid]
        );
        $context = self::validate_cm_context($longpageid);
        require_capability('mod/longpage:view', $context);

        $query = '
            SELECT section, count(sectionhash) as count
            FROM (
                SELECT * FROM {longpage_reading_progress} m
                WHERE course=:courseid AND longpageid=:longpageid AND userid=:userid
            ) mm
            GROUP by section
            ';

        $transaction = $DB->start_delegated_transaction();
        $res = $DB->get_records_sql($query, ['courseid' => $courseid, 'longpageid' => $longpageid, 'userid' => $USER->id]);
        $transaction->allow_commit();

        return ['response' => json_encode($res)];
    }

    /**
     * Parameters for get_reading_progress.
     *
     * @return external_function_parameters
     */
    public static function get_reading_progress_parameters() {
        return new external_function_parameters(
            [
                'courseid' => new external_value(PARAM_INT, 'Course ID'),
                'longpageid' => new external_value(PARAM_INT, 'Longpage ID'),
            ]
        );
    }

    /**
     * Return structure for get_reading_progress.
     *
     * @return external_single_structure
     */
    public static function get_reading_progress_returns() {
        return new external_single_structure(
            ['response' => new external_value(PARAM_RAW, 'All bookmarks of an user')]
        );
    }
}
