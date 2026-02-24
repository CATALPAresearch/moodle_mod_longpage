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
 * Utility services external API - logging and permissions
 *
 * @package    mod_longpage
 * @category   external
 * @copyright  2020 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_longpage\external;

use context_module;
use Exception;
use stdClass;

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once("$CFG->libdir/externallib.php");

/**
 * Utility services for external API - logging and permissions
 *
 * @package    mod_longpage
 * @category   external
 * @copyright  2020 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utility_services extends base_external {
    /**
     * Log user interactions and scroll progress.
     *
     * @param array $data Log data containing courseid, utc, action, and entry
     * @return array Response with JSON-encoded result
     */
    public static function log($data) {
        global $CFG, $DB, $USER;

        // Basic validation - check if user is authenticated.
        require_login();

        $r = new stdClass();
        $r->name = 'mod_longpage';
        $r->component = 'mod_longpage';
        $r->eventname = '\mod_longpage\event\course_module_' . $data['action'];
        $r->action = $data['action'];
        $r->target = 'course_module';
        $r->objecttable = 'longpage';
        $r->objectid = 0;
        $r->crud = 'r';
        $r->edulevel = 2;
        $r->contextid = 120;
        $r->contextlevel = 70;
        $r->contextinstanceid = 86;
        $r->userid = $USER->id;
        $r->courseid = (int) $data['courseid'];

        $r->anonymous = 0;
        $r->other = $data['entry'];
        $r->timecreated = $data['utc'];
        $r->origin = 'web';
        $r->ip = $_SERVER['REMOTE_ADDR'];

        $transaction = $DB->start_delegated_transaction();
        $res = $DB->insert_record("logstore_standard_log", (array) $r);
        $transaction->allow_commit();

        if ($data['action'] == "scroll") {
            $d = json_decode($data['entry']);
            $s = new stdClass();
            $s->section = (string)$d->targetID;
            $s->userid = (int) $USER->id;
            $s->course = (int) $data['courseid'];
            $s->longpageid = (int) $d->longpageid;
            $s->timemodified = (int) $d->utc;
            $s->scrolltop = (int) $d->scrolltop;

            try {
                $transaction = $DB->start_delegated_transaction();
                $res2 = $DB->insert_record("longpage_reading_progress", (array) $s);
                $transaction->allow_commit();
                // Log success only if debug mode is enabled
                if ($CFG->debugdeveloper) {
                    error_log('Longpage: Reading progress saved successfully');
                }
            } catch (Exception $e) {
                $transaction->rollback($e);
                // Always log errors for troubleshooting
                error_log('Longpage: Failed to save reading progress - ' . $e->getMessage());
            }
        }
        return ['response' => json_encode($res)];
    }

    /**
     * Takes Longpage log data from the client.
     *
     * @return external_function_parameters
     */
    public static function log_parameters() {
        return new \external_function_parameters(
            [
                'data' =>
                    new \external_single_structure(
                        [
                            'courseid' => new \external_value(PARAM_INT, 'id of course', VALUE_OPTIONAL),
                            'utc' => new \external_value(PARAM_INT, '...utc time', VALUE_OPTIONAL),
                            'action' => new \external_value(PARAM_TEXT, '..action', VALUE_OPTIONAL),
                            'entry' => new \external_value(PARAM_RAW, 'log data', VALUE_OPTIONAL),
                        ]
                    ),
            ]
        );
    }

    /**
     * Return structure for log.
     *
     * @return external_single_structure
     */
    public static function log_returns() {
        return new \external_single_structure(
            ['response' => new \external_value(PARAM_RAW, 'Server respons to the incomming log')]
        );
    }

    /**
     * Check if user can modify annotations.
     *
     * @param int $longpageid Longpage ID
     * @return array
     */
    public static function can_madify_annotations($longpageid) {
        global $DB, $USER;

        $params = self::validate_parameters(
            self::can_madify_annotations_parameters(),
            [
                'longpageid' => $longpageid,
            ]
        );
        $warnings = [];

        // Request and permission validation.
        $page = $DB->get_record('longpage', ['id' => $params['longpageid']], '*', MUST_EXIST);
        [$course, $cm] = get_course_and_cm_from_instance($page, 'longpage');

        $context = context_module::instance($cm->id);
        self::validate_context($context);

        if (has_capability('mod/longpage:modannotations', $context)) {
            return ['canmodannotations' => true];
        } else {
            return ['canmodannotations' => false];
        }
    }

    /**
     * Parameters for can_madify_annotations.
     *
     * @return external_function_parameters
     */
    public static function can_madify_annotations_parameters() {
        return new \external_function_parameters(
            [
                'longpageid' => new \external_value(PARAM_INT, 'page instance id'),
            ]
        );
    }

    /**
     * Returns for can_madify_annotations.
     *
     * @return external_single_structure
     */
    public static function can_madify_annotations_returns() {
        return new \external_single_structure(
            ['canmodannotations' => new \external_value(PARAM_BOOL)]
        );
    }
}
