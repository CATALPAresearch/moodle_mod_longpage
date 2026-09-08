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
 * Analytics external services for Teacher Dashboard
 *
 * @package    mod_longpage
 * @category   external
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_longpage\external;

use external_function_parameters;
use external_single_structure;
use external_multiple_structure;
use external_value;

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once("$CFG->libdir/externallib.php");

/**
 * Analytics external services class for Teacher Dashboard
 *
 * @package    mod_longpage
 * @category   external
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class analytics_services extends base_external {

    /**
     * Get analytics data for the teacher dashboard.
     *
     * @param int $longpageid Longpage instance ID
     * @param int $semesterstart Semester start timestamp
     * @param int $semesterend Semester end timestamp
     * @return array Analytics data
     */
    public static function get_dashboard_analytics($longpageid, $semesterstart, $semesterend) {
        global $DB;

        $params = self::validate_parameters(
            self::get_dashboard_analytics_parameters(),
            [
                'longpageid' => $longpageid,
                'semesterstart' => $semesterstart,
                'semesterend' => $semesterend,
            ]
        );

        $context = self::validate_cm_context($params['longpageid']);
        require_capability('mod/longpage:modannotations', $context);

        $cm = get_coursemodule_by_pageid($params['longpageid']);
        $contextid = $context->id;

        // Get weekly activity data.
        $weeklydata = self::get_weekly_activity($cm->id, $contextid, $params['semesterstart'], $params['semesterend']);

        // Get user engagement data.
        $userdata = self::get_user_engagement($cm->id, $contextid, $params['semesterstart'], $params['semesterend']);

        // Get reading position distribution.
        $readingdistribution = self::get_reading_distribution($params['longpageid'], $params['semesterstart'], $params['semesterend']);

        return [
            'weeklyactivity' => $weeklydata,
            'userengagement' => $userdata,
            'readingdistribution' => $readingdistribution,
        ];
    }

    /**
     * Get weekly activity statistics.
     *
     * @param int $cmid Course module ID
     * @param int $contextid Context ID
     * @param int $starttime Start timestamp
     * @param int $endtime End timestamp
     * @return array Weekly activity data
     */
    private static function get_weekly_activity($cmid, $contextid, $starttime, $endtime) {
        global $DB;

        $weeks = [];
        $currentweek = strtotime('monday this week', $starttime);

        while ($currentweek < $endtime) {
            $weekend = $currentweek + (7 * 24 * 60 * 60);
            $weekkey = date('Y-W', $currentweek);

            // Page views.
            $views = $DB->count_records_select(
                'logstore_standard_log',
                "contextid = :contextid AND action = 'viewed' AND target = 'course_module' 
                 AND timecreated >= :start AND timecreated < :end",
                ['contextid' => $contextid, 'start' => $currentweek, 'end' => $weekend]
            );

            // Searches.
            $searches = $DB->count_records_select(
                'logstore_standard_log',
                "contextid = :contextid AND eventname LIKE '%search%' 
                 AND timecreated >= :start AND timecreated < :end",
                ['contextid' => $contextid, 'start' => $currentweek, 'end' => $weekend]
            );

            // TOC uses (scroll events to specific sections).
            $tocuses = $DB->count_records_select(
                'logstore_standard_log',
                "contextid = :contextid AND eventname LIKE '%moved%' 
                 AND timecreated >= :start AND timecreated < :end",
                ['contextid' => $contextid, 'start' => $currentweek, 'end' => $weekend]
            );

            // Quiz attempts.
            $quizattempts = $DB->count_records_select(
                'logstore_standard_log',
                "contextid = :contextid AND eventname LIKE '%question%' 
                 AND timecreated >= :start AND timecreated < :end",
                ['contextid' => $contextid, 'start' => $currentweek, 'end' => $weekend]
            );

            // Annotation counts by type from longpage_annotations table.
            $highlightcount = $DB->count_records_select(
                'longpage_annotations',
                "longpageid = (SELECT instance FROM {course_modules} WHERE id = :cmid) 
                 AND type = 0 AND timecreated >= :start AND timecreated < :end",
                ['cmid' => $cmid, 'start' => $currentweek, 'end' => $weekend]
            );

            $postcount = $DB->count_records_select(
                'longpage_annotations',
                "longpageid = (SELECT instance FROM {course_modules} WHERE id = :cmid) 
                 AND type = 1 AND timecreated >= :start AND timecreated < :end",
                ['cmid' => $cmid, 'start' => $currentweek, 'end' => $weekend]
            );

            $bookmarkcount = $DB->count_records_select(
                'longpage_annotations',
                "longpageid = (SELECT instance FROM {course_modules} WHERE id = :cmid) 
                 AND type = 2 AND timecreated >= :start AND timecreated < :end",
                ['cmid' => $cmid, 'start' => $currentweek, 'end' => $weekend]
            );

            $weeks[] = [
                'week' => $weekkey,
                'weekstart' => $currentweek,
                'views' => (int) $views,
                'searches' => (int) $searches,
                'tocuses' => (int) $tocuses,
                'quizattempts' => (int) $quizattempts,
                'highlights' => (int) $highlightcount,
                'posts' => (int) $postcount,
                'bookmarks' => (int) $bookmarkcount,
            ];

            $currentweek = $weekend;
        }

        return $weeks;
    }

    /**
     * Get user engagement statistics over time.
     *
     * @param int $cmid Course module ID
     * @param int $contextid Context ID
     * @param int $starttime Start timestamp
     * @param int $endtime End timestamp
     * @return array User engagement data
     */
    private static function get_user_engagement($cmid, $contextid, $starttime, $endtime) {
        global $DB;

        $weeks = [];
        $currentweek = strtotime('monday this week', $starttime);

        while ($currentweek < $endtime) {
            $weekend = $currentweek + (7 * 24 * 60 * 60);
            $weekkey = date('Y-W', $currentweek);

            // Count unique users.
            $uniqueusers = $DB->count_records_sql(
                "SELECT COUNT(DISTINCT userid) 
                 FROM {logstore_standard_log} 
                 WHERE contextid = :contextid 
                 AND timecreated >= :start AND timecreated < :end",
                ['contextid' => $contextid, 'start' => $currentweek, 'end' => $weekend]
            );

            // Estimate time spent (based on session activity).
            // Calculate approximate time by looking at consecutive events.
            $timespent = $DB->get_field_sql(
                "SELECT SUM(duration) FROM (
                    SELECT LEAST(
                        COALESCE(
                            LEAD(timecreated) OVER (PARTITION BY userid ORDER BY timecreated) - timecreated, 
                            300
                        ), 
                        1800
                    ) as duration
                    FROM {logstore_standard_log}
                    WHERE contextid = :contextid 
                    AND timecreated >= :start AND timecreated < :end
                ) subquery",
                ['contextid' => $contextid, 'start' => $currentweek, 'end' => $weekend]
            );

            // Fallback for databases that don't support window functions.
            if ($timespent === false || $timespent === null) {
                // Simple estimation: count events * average time per event (60 seconds).
                $eventcount = $DB->count_records_select(
                    'logstore_standard_log',
                    "contextid = :contextid AND timecreated >= :start AND timecreated < :end",
                    ['contextid' => $contextid, 'start' => $currentweek, 'end' => $weekend]
                );
                $timespent = $eventcount * 60;
            }

            $avgtime = $uniqueusers > 0 ? round($timespent / $uniqueusers) : 0;

            $weeks[] = [
                'week' => $weekkey,
                'weekstart' => $currentweek,
                'uniqueusers' => (int) $uniqueusers,
                'totaltimespent' => (int) $timespent,
                'avgtimespent' => (int) $avgtime,
            ];

            $currentweek = $weekend;
        }

        return $weeks;
    }

    /**
     * Get reading position distribution.
     *
     * @param int $longpageid Longpage instance ID
     * @param int $starttime Start timestamp
     * @param int $endtime End timestamp
     * @return array Reading distribution data
     */
    private static function get_reading_distribution($longpageid, $starttime, $endtime) {
        global $DB;

        // Get reading progress data grouped by scroll position (in 10% buckets).
        $distribution = [];

        for ($i = 0; $i < 10; $i++) {
            $minpos = $i * 10;
            $maxpos = ($i + 1) * 10;

            $count = $DB->count_records_sql(
                "SELECT COUNT(*) 
                 FROM {longpage_reading_positions} 
                 WHERE longpageid = :longpageid 
                 AND timemodified >= :start AND timemodified < :end
                 AND scrolltop >= :minpos AND scrolltop < :maxpos",
                [
                    'longpageid' => $longpageid,
                    'start' => $starttime,
                    'end' => $endtime,
                    'minpos' => $minpos,
                    'maxpos' => $maxpos,
                ]
            );

            $distribution[] = [
                'position' => $minpos . '-' . $maxpos . '%',
                'positionstart' => $minpos,
                'positionend' => $maxpos,
                'count' => (int) $count,
            ];
        }

        return $distribution;
    }

    /**
     * Parameters for get_dashboard_analytics.
     *
     * @return external_function_parameters
     */
    public static function get_dashboard_analytics_parameters() {
        return new external_function_parameters([
            'longpageid' => new external_value(PARAM_INT, 'Longpage instance ID'),
            'semesterstart' => new external_value(PARAM_INT, 'Semester start timestamp'),
            'semesterend' => new external_value(PARAM_INT, 'Semester end timestamp'),
        ]);
    }

    /**
     * Return type for get_dashboard_analytics.
     *
     * @return external_single_structure
     */
    public static function get_dashboard_analytics_returns() {
        return new external_single_structure([
            'weeklyactivity' => new external_multiple_structure(
                new external_single_structure([
                    'week' => new external_value(PARAM_TEXT, 'Week identifier (YYYY-WW)'),
                    'weekstart' => new external_value(PARAM_INT, 'Week start timestamp'),
                    'views' => new external_value(PARAM_INT, 'Page views count'),
                    'searches' => new external_value(PARAM_INT, 'Search count'),
                    'tocuses' => new external_value(PARAM_INT, 'TOC navigation count'),
                    'quizattempts' => new external_value(PARAM_INT, 'Quiz attempt count'),
                    'highlights' => new external_value(PARAM_INT, 'Highlight annotation count'),
                    'posts' => new external_value(PARAM_INT, 'Post annotation count'),
                    'bookmarks' => new external_value(PARAM_INT, 'Bookmark annotation count'),
                ])
            ),
            'userengagement' => new external_multiple_structure(
                new external_single_structure([
                    'week' => new external_value(PARAM_TEXT, 'Week identifier (YYYY-WW)'),
                    'weekstart' => new external_value(PARAM_INT, 'Week start timestamp'),
                    'uniqueusers' => new external_value(PARAM_INT, 'Unique user count'),
                    'totaltimespent' => new external_value(PARAM_INT, 'Total time spent in seconds'),
                    'avgtimespent' => new external_value(PARAM_INT, 'Average time spent per user in seconds'),
                ])
            ),
            'readingdistribution' => new external_multiple_structure(
                new external_single_structure([
                    'position' => new external_value(PARAM_TEXT, 'Position range label'),
                    'positionstart' => new external_value(PARAM_INT, 'Position range start (%)'),
                    'positionend' => new external_value(PARAM_INT, 'Position range end (%)'),
                    'count' => new external_value(PARAM_INT, 'Reading event count'),
                ])
            ),
        ]);
    }

    /**
     * Get available semesters for selection.
     *
     * @param int $longpageid Longpage instance ID
     * @return array Available semesters
     */
    public static function get_available_semesters($longpageid) {
        global $DB;

        $params = self::validate_parameters(
            self::get_available_semesters_parameters(),
            ['longpageid' => $longpageid]
        );

        $context = self::validate_cm_context($params['longpageid']);
        require_capability('mod/longpage:modannotations', $context);

        // Get the course module creation date from course_modules table.
        $cm = get_coursemodule_by_pageid($params['longpageid']);
        $createdtime = $cm->added ?? time();

        $semesters = [];
        $currenttime = time();

        // Determine starting semester (April or October).
        $createdyear = (int) date('Y', $createdtime);
        $createdmonth = (int) date('n', $createdtime);

        if ($createdmonth >= 10) {
            // Winter semester starting October.
            $semesterstart = mktime(0, 0, 0, 10, 1, $createdyear);
        } elseif ($createdmonth >= 4) {
            // Summer semester starting April.
            $semesterstart = mktime(0, 0, 0, 4, 1, $createdyear);
        } else {
            // Previous winter semester.
            $semesterstart = mktime(0, 0, 0, 10, 1, $createdyear - 1);
        }

        // Generate semesters until now.
        while ($semesterstart <= $currenttime) {
            $month = (int) date('n', $semesterstart);
            $year = (int) date('Y', $semesterstart);

            if ($month == 10) {
                // Winter semester: October to March.
                $semesterend = mktime(23, 59, 59, 3, 31, $year + 1);
                $label = 'WS ' . $year . '/' . ($year + 1);
            } else {
                // Summer semester: April to September.
                $semesterend = mktime(23, 59, 59, 9, 30, $year);
                $label = 'SS ' . $year;
            }

            $semesters[] = [
                'label' => $label,
                'start' => (int) $semesterstart,
                'end' => (int) $semesterend,
            ];

            // Move to next semester.
            if ($month == 10) {
                $semesterstart = mktime(0, 0, 0, 4, 1, $year + 1);
            } else {
                $semesterstart = mktime(0, 0, 0, 10, 1, $year);
            }
        }

        return $semesters;
    }

    /**
     * Parameters for get_available_semesters.
     *
     * @return external_function_parameters
     */
    public static function get_available_semesters_parameters() {
        return new external_function_parameters([
            'longpageid' => new external_value(PARAM_INT, 'Longpage instance ID'),
        ]);
    }

    /**
     * Return type for get_available_semesters.
     *
     * @return external_multiple_structure
     */
    public static function get_available_semesters_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'label' => new external_value(PARAM_TEXT, 'Semester label'),
                'start' => new external_value(PARAM_INT, 'Semester start timestamp'),
                'end' => new external_value(PARAM_INT, 'Semester end timestamp'),
            ])
        );
    }
}
