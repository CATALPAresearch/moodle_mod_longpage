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
 * Reading-behavior tracking external services.
 *
 * @package    mod_longpage
 * @category   external
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_longpage\external;

use Exception;
use external_function_parameters;
use external_value;

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once("$CFG->libdir/externallib.php");

/**
 * Persists one finalized reading-behavior data point (one "baseline
 * crossing" of an observed element, classified client-side by
 * ReadingBehaviorTracker in vue/src/lib/readingBehavior/reading-behavior-tracker.js)
 * into a dedicated, typed table — longpage_reading_behavior_events — instead
 * of a JSON-blob log row.
 *
 * Session-level and user-profile labels are aggregated client-side from the
 * data points seen during the current page load (see
 * ReadingBehaviorTracker.getSessionLabel/getUserProfileLabel) and are not
 * persisted here; a cross-visit user profile would require a read endpoint
 * that re-aggregates a user's historical rows, which is a natural next step
 * but out of scope for this write-only logging endpoint.
 *
 * @package    mod_longpage
 * @category   external
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reading_behavior_services extends base_external {
    /**
     * Persist one reading-behavior data point.
     *
     * @param int $longpageid
     * @param int $courseid
     * @param string $sessionid
     * @param string $targetid
     * @param string $targettag
     * @param int $wordcount
     * @param float $dwellseconds
     * @param float $peakratio
     * @param float $minreadingtime
     * @param float $avgreadingtime
     * @param float $maxreadingtime
     * @param string $datapointlabel
     * @param string $language
     */
    public static function log_reading_behavior_event(
        $longpageid,
        $courseid,
        $sessionid,
        $targetid,
        $targettag,
        $wordcount,
        $dwellseconds,
        $peakratio,
        $minreadingtime,
        $avgreadingtime,
        $maxreadingtime,
        $datapointlabel,
        $language
    ) {
        global $DB, $USER;

        $params = self::validate_parameters(
            self::log_reading_behavior_event_parameters(),
            [
                'longpageid' => $longpageid,
                'courseid' => $courseid,
                'sessionid' => $sessionid,
                'targetid' => $targetid,
                'targettag' => $targettag,
                'wordcount' => $wordcount,
                'dwellseconds' => $dwellseconds,
                'peakratio' => $peakratio,
                'minreadingtime' => $minreadingtime,
                'avgreadingtime' => $avgreadingtime,
                'maxreadingtime' => $maxreadingtime,
                'datapointlabel' => $datapointlabel,
                'language' => $language,
            ]
        );

        $context = self::validate_cm_context($params['longpageid']);
        require_capability('mod/longpage:view', $context);

        try {
            $transaction = $DB->start_delegated_transaction();
            $DB->insert_record('longpage_reading_behavior_events', [
                'longpageid' => $params['longpageid'],
                'courseid' => $params['courseid'],
                'userid' => $USER->id,
                'sessionid' => $params['sessionid'],
                'targetid' => $params['targetid'],
                'targettag' => $params['targettag'],
                'wordcount' => $params['wordcount'],
                'dwellseconds' => $params['dwellseconds'],
                'peakratio' => $params['peakratio'],
                'minreadingtime' => $params['minreadingtime'],
                'avgreadingtime' => $params['avgreadingtime'],
                'maxreadingtime' => $params['maxreadingtime'],
                'datapointlabel' => $params['datapointlabel'],
                'language' => $params['language'],
                'timecreated' => time(),
            ]);
            $transaction->allow_commit();
        } catch (Exception $e) {
            $transaction->rollback($e);
            throw $e;
        }
    }

    /**
     * Parameters for log_reading_behavior_event.
     *
     * @return external_function_parameters
     */
    public static function log_reading_behavior_event_parameters() {
        return new external_function_parameters([
            'longpageid' => new external_value(PARAM_INT, 'Longpage id'),
            'courseid' => new external_value(PARAM_INT, 'Course id'),
            'sessionid' => new external_value(PARAM_ALPHANUMEXT, 'Client-generated reading-session UUID'),
            'targetid' => new external_value(PARAM_TEXT, 'DOM id of the observed element'),
            'targettag' => new external_value(PARAM_ALPHA, 'Tag name of the observed element'),
            'wordcount' => new external_value(PARAM_INT, 'Word count (0 for images/tables)'),
            'dwellseconds' => new external_value(PARAM_FLOAT, 'How long the element held the viewport baseline'),
            'peakratio' => new external_value(PARAM_FLOAT, 'Highest intersectionRatio reached, 0-1'),
            'minreadingtime' => new external_value(PARAM_FLOAT, 'Expected "scan/skim" duration, seconds'),
            'avgreadingtime' => new external_value(PARAM_FLOAT, 'Expected normal-reading duration, seconds'),
            'maxreadingtime' => new external_value(PARAM_FLOAT, 'Expected "study" (memorizing) duration, seconds'),
            'datapointlabel' => new external_value(PARAM_ALPHA, 'scan|read|study|regression|preview'),
            'language' => new external_value(PARAM_ALPHA, 'Language used for the reading-speed estimate'),
        ]);
    }

    /**
     * Returns for log_reading_behavior_event.
     *
     * @return null
     */
    public static function log_reading_behavior_event_returns() {
        return null;
    }

    /**
     * Persist a batch of raw IntersectionObserver telemetry rows — one row
     * per threshold crossing, independent of any reading-behavior
     * classification. Intended for later offline analysis (e.g. a DB dump
     * loaded into R/Python) rather than for anything this plugin itself
     * reads back.
     *
     * @param int $longpageid
     * @param int $courseid
     * @param string $events  JSON-encoded array of raw event objects (see
     *                        ReadingProgress.vue's rawEventBuffer entries)
     */
    public static function log_intersection_events($longpageid, $courseid, $events) {
        global $DB, $USER;

        $params = self::validate_parameters(
            self::log_intersection_events_parameters(),
            ['longpageid' => $longpageid, 'courseid' => $courseid, 'events' => $events]
        );

        $context = self::validate_cm_context($params['longpageid']);
        require_capability('mod/longpage:view', $context);

        $decoded = json_decode($params['events'], true);
        if (!is_array($decoded)) {
            return;
        }

        // The client batches in small chunks (see RAW_EVENT_MAX_BUFFER in
        // ReadingProgress.vue), but a write endpoint should never trust an
        // array's size from the request alone.
        $decoded = array_slice($decoded, 0, 500);

        try {
            $transaction = $DB->start_delegated_transaction();
            foreach ($decoded as $event) {
                if (!is_array($event)) {
                    continue;
                }
                $DB->insert_record('longpage_intersection_events', [
                    'longpageid' => $params['longpageid'],
                    'courseid' => $params['courseid'],
                    'userid' => $USER->id,
                    'sessionid' => (string) ($event['sessionid'] ?? ''),
                    'targetid' => (string) ($event['targetid'] ?? ''),
                    'targettag' => (string) ($event['targettag'] ?? ''),
                    'intersectionratio' => (float) ($event['intersectionratio'] ?? 0),
                    'boundingtop' => (float) ($event['boundingtop'] ?? 0),
                    'boundingbottom' => (float) ($event['boundingbottom'] ?? 0),
                    'boundingheight' => (float) ($event['boundingheight'] ?? 0),
                    'boundingwidth' => (float) ($event['boundingwidth'] ?? 0),
                    'viewportheight' => (float) ($event['viewportheight'] ?? 0),
                    'scrolltop' => (float) ($event['scrolltop'] ?? 0),
                    'wordcount' => (int) ($event['wordcount'] ?? 0),
                    'clienttimestamp' => (float) ($event['clienttimestamp'] ?? 0),
                    'timecreated' => time(),
                ]);
            }
            $transaction->allow_commit();
        } catch (Exception $e) {
            $transaction->rollback($e);
            throw $e;
        }
    }

    /**
     * Parameters for log_intersection_events.
     *
     * @return external_function_parameters
     */
    public static function log_intersection_events_parameters() {
        return new external_function_parameters([
            'longpageid' => new external_value(PARAM_INT, 'Longpage id'),
            'courseid' => new external_value(PARAM_INT, 'Course id'),
            'events' => new external_value(PARAM_RAW, 'JSON array of raw intersection-observer events'),
        ]);
    }

    /**
     * Returns for log_intersection_events.
     *
     * @return null
     */
    public static function log_intersection_events_returns() {
        return null;
    }
}
