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
     * Get analytics data for the teacher dashboard, aggregated across one or
     * more longpage instances (the multiselect at the top of the dashboard
     * lets a teacher combine several instances of the same course into one
     * view).
     *
     * @param array $longpageids Longpage instance IDs
     * @param int $semesterstart Semester start timestamp
     * @param int $semesterend Semester end timestamp
     * @return array Analytics data
     */
    public static function get_dashboard_analytics($longpageids, $semesterstart, $semesterend) {
        $params = self::validate_parameters(
            self::get_dashboard_analytics_parameters(),
            [
                'longpageids' => $longpageids,
                'semesterstart' => $semesterstart,
                'semesterend' => $semesterend,
            ]
        );

        if (empty($params['longpageids'])) {
            throw new \invalid_parameter_exception('longpageids must not be empty');
        }

        // Capability is checked per selected instance — the multiselect
        // could otherwise be used to pull in analytics for an instance the
        // caller isn't a teacher on.
        $contextids = [];
        foreach ($params['longpageids'] as $longpageid) {
            $context = self::validate_cm_context($longpageid);
            require_capability('mod/longpage:modannotations', $context);
            $contextids[] = $context->id;
        }

        $weeklydata = self::get_weekly_activity(
            $params['longpageids'],
            $contextids,
            $params['semesterstart'],
            $params['semesterend']
        );
        $userdata = self::get_user_engagement($contextids, $params['semesterstart'], $params['semesterend']);
        $readingdistribution = self::get_reading_distribution(
            $params['longpageids'],
            $params['semesterstart'],
            $params['semesterend']
        );
        $readingbehaviortrend = self::get_reading_behavior_trend(
            $params['longpageids'],
            $params['semesterstart'],
            $params['semesterend']
        );
        $readingbehaviorbystudent = self::get_reading_behavior_by_student(
            $params['longpageids'],
            $params['semesterstart'],
            $params['semesterend']
        );

        return [
            'weeklyactivity' => $weeklydata,
            'userengagement' => $userdata,
            'readingdistribution' => $readingdistribution,
            'readingbehaviortrend' => $readingbehaviortrend,
            'readingbehaviorbystudent' => $readingbehaviorbystudent,
        ];
    }

    /**
     * Get weekly activity statistics, summed across all selected instances.
     *
     * @param array $longpageids Longpage instance IDs (for annotation counts)
     * @param array $contextids Course module context IDs (for log counts)
     * @param int $starttime Start timestamp
     * @param int $endtime End timestamp
     * @return array Weekly activity data
     */
    private static function get_weekly_activity($longpageids, $contextids, $starttime, $endtime) {
        global $DB;

        [$ctxinsql, $ctxparams] = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED, 'ctx');
        [$pageinsql, $pageparams] = $DB->get_in_or_equal($longpageids, SQL_PARAMS_NAMED, 'page');

        $weeks = [];
        $currentweek = strtotime('monday this week', $starttime);

        while ($currentweek < $endtime) {
            $weekend = $currentweek + (7 * 24 * 60 * 60);
            $weekkey = date('Y-W', $currentweek);
            $timeparams = ['start' => $currentweek, 'end' => $weekend];

            // Page views.
            $views = $DB->count_records_select(
                'logstore_standard_log',
                "contextid $ctxinsql AND action = 'viewed' AND target = 'course_module'
                 AND timecreated >= :start AND timecreated < :end",
                array_merge($ctxparams, $timeparams)
            );

            // Searches.
            $searches = $DB->count_records_select(
                'logstore_standard_log',
                "contextid $ctxinsql AND eventname LIKE '%search%'
                 AND timecreated >= :start AND timecreated < :end",
                array_merge($ctxparams, $timeparams)
            );

            // TOC uses (scroll events to specific sections).
            $tocuses = $DB->count_records_select(
                'logstore_standard_log',
                "contextid $ctxinsql AND eventname LIKE '%moved%'
                 AND timecreated >= :start AND timecreated < :end",
                array_merge($ctxparams, $timeparams)
            );

            // Quiz attempts.
            $quizattempts = $DB->count_records_select(
                'logstore_standard_log',
                "contextid $ctxinsql AND eventname LIKE '%question%'
                 AND timecreated >= :start AND timecreated < :end",
                array_merge($ctxparams, $timeparams)
            );

            // Annotation counts by type from longpage_annotations table.
            $highlightcount = $DB->count_records_select(
                'longpage_annotations',
                "longpageid $pageinsql AND type = 0 AND timecreated >= :start AND timecreated < :end",
                array_merge($pageparams, $timeparams)
            );

            $postcount = $DB->count_records_select(
                'longpage_annotations',
                "longpageid $pageinsql AND type = 1 AND timecreated >= :start AND timecreated < :end",
                array_merge($pageparams, $timeparams)
            );

            $bookmarkcount = $DB->count_records_select(
                'longpage_annotations',
                "longpageid $pageinsql AND type = 2 AND timecreated >= :start AND timecreated < :end",
                array_merge($pageparams, $timeparams)
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
     * Get user engagement statistics over time, summed across all selected
     * instances (a user active on two of the selected instances in the same
     * week is counted once, via COUNT(DISTINCT userid)).
     *
     * @param array $contextids Course module context IDs
     * @param int $starttime Start timestamp
     * @param int $endtime End timestamp
     * @return array User engagement data
     */
    private static function get_user_engagement($contextids, $starttime, $endtime) {
        global $DB;

        [$ctxinsql, $ctxparams] = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED, 'ctx');

        $weeks = [];
        $currentweek = strtotime('monday this week', $starttime);

        while ($currentweek < $endtime) {
            $weekend = $currentweek + (7 * 24 * 60 * 60);
            $weekkey = date('Y-W', $currentweek);
            $timeparams = ['start' => $currentweek, 'end' => $weekend];

            // Count unique users.
            $uniqueusers = $DB->count_records_sql(
                "SELECT COUNT(DISTINCT userid)
                 FROM {logstore_standard_log}
                 WHERE contextid $ctxinsql
                 AND timecreated >= :start AND timecreated < :end",
                array_merge($ctxparams, $timeparams)
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
                    WHERE contextid $ctxinsql
                    AND timecreated >= :start AND timecreated < :end
                ) subquery",
                array_merge($ctxparams, $timeparams)
            );

            // Fallback for databases that don't support window functions.
            if ($timespent === false || $timespent === null) {
                // Simple estimation: count events * average time per event (60 seconds).
                $eventcount = $DB->count_records_select(
                    'logstore_standard_log',
                    "contextid $ctxinsql AND timecreated >= :start AND timecreated < :end",
                    array_merge($ctxparams, $timeparams)
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
     * Get reading position distribution, broken down by reading-behavior
     * classification (scan/read/study/regression/preview) so the chart can
     * show not just how much reading activity happened at each position in
     * the document, but what kind of reading it was.
     *
     * Uses longpage_reading_behavior_events.scrolltop (0-1 fraction of
     * #longpage-main scrollTop/scrollHeight when the data point was
     * finalized — see ReadingBehaviorTracker.vue's persistReadingBehaviorEvent).
     * That column was added after the events table itself, so rows logged
     * before the upgrade have scrolltop = null and are excluded here rather
     * than defaulted to 0, which would fake a spike at the start of the
     * document.
     *
     * @param array $longpageids Longpage instance IDs
     * @param int $starttime Start timestamp
     * @param int $endtime End timestamp
     * @return array Reading distribution data, one row per 10% position bucket
     */
    private static function get_reading_distribution($longpageids, $starttime, $endtime) {
        global $DB;

        [$pageinsql, $pageparams] = $DB->get_in_or_equal($longpageids, SQL_PARAMS_NAMED, 'page');
        $labels = ['scan', 'read', 'study', 'regression', 'preview'];
        $distribution = [];

        for ($i = 0; $i < 10; $i++) {
            $minpos = $i * 10;
            $maxpos = ($i + 1) * 10;

            $counts = array_fill_keys($labels, 0);
            $rows = $DB->get_records_sql(
                "SELECT datapointlabel, COUNT(*) as cnt
                 FROM {longpage_reading_behavior_events}
                 WHERE longpageid $pageinsql
                 AND timecreated >= :start AND timecreated < :end
                 AND scrolltop IS NOT NULL
                 AND scrolltop >= :minpos AND scrolltop < :maxpos
                 GROUP BY datapointlabel",
                array_merge($pageparams, [
                    'start' => $starttime,
                    'end' => $endtime,
                    'minpos' => $minpos / 100,
                    'maxpos' => $maxpos / 100,
                ])
            );

            $total = 0;
            foreach ($rows as $row) {
                if (array_key_exists($row->datapointlabel, $counts)) {
                    $counts[$row->datapointlabel] = (int) $row->cnt;
                }
                $total += (int) $row->cnt;
            }

            $bucket = [
                'position' => $minpos . '-' . $maxpos . '%',
                'positionstart' => $minpos,
                'positionend' => $maxpos,
                'count' => $total,
            ];
            foreach ($labels as $label) {
                $bucket[$label] = $counts[$label];
            }
            $distribution[] = $bucket;
        }

        return $distribution;
    }

    /**
     * Weekly share (%) of each reading-behavior classification
     * (scan/read/study/regression/preview — see ReadingBehaviorTracker.vue),
     * across the whole cohort. Normalized to a percentage of that week's
     * classified data points so the mix is comparable week over week
     * independent of how much reading activity happened that week.
     *
     * @param array $longpageids Longpage instance IDs
     * @param int $starttime Start timestamp
     * @param int $endtime End timestamp
     * @return array Weekly reading-behavior share data
     */
    private static function get_reading_behavior_trend($longpageids, $starttime, $endtime) {
        global $DB;

        [$pageinsql, $pageparams] = $DB->get_in_or_equal($longpageids, SQL_PARAMS_NAMED, 'page');
        $labels = ['scan', 'read', 'study', 'regression', 'preview'];
        $weeks = [];
        $currentweek = strtotime('monday this week', $starttime);

        while ($currentweek < $endtime) {
            $weekend = $currentweek + (7 * 24 * 60 * 60);
            $weekkey = date('Y-W', $currentweek);

            $counts = array_fill_keys($labels, 0);
            $rows = $DB->get_records_sql(
                "SELECT datapointlabel, COUNT(*) as cnt
                 FROM {longpage_reading_behavior_events}
                 WHERE longpageid $pageinsql
                 AND timecreated >= :start AND timecreated < :end
                 GROUP BY datapointlabel",
                array_merge($pageparams, ['start' => $currentweek, 'end' => $weekend])
            );

            $total = 0;
            foreach ($rows as $row) {
                if (array_key_exists($row->datapointlabel, $counts)) {
                    $counts[$row->datapointlabel] = (int) $row->cnt;
                }
                $total += (int) $row->cnt;
            }

            $week = [
                'week' => $weekkey,
                'weekstart' => $currentweek,
                'total' => $total,
            ];
            foreach ($labels as $label) {
                $week[$label] = $total > 0 ? round(($counts[$label] / $total) * 100, 1) : 0.0;
            }
            $weeks[] = $week;

            $currentweek = $weekend;
        }

        return $weeks;
    }

    /**
     * Per-student breakdown of reading-behavior classification shares over
     * the selected period — lets a teacher spot students whose reading is
     * dominated by scan/preview (little genuine engagement) versus a
     * healthy read/study mix, independent of the cohort-level weekly trend.
     *
     * @param array $longpageids Longpage instance IDs
     * @param int $starttime Start timestamp
     * @param int $endtime End timestamp
     * @return array Per-student reading-behavior data
     */
    private static function get_reading_behavior_by_student($longpageids, $starttime, $endtime) {
        global $DB;

        [$pageinsql, $pageparams] = $DB->get_in_or_equal($longpageids, SQL_PARAMS_NAMED, 'page');

        $fullnamesql = $DB->sql_fullname('u.firstname', 'u.lastname');
        $sql = "SELECT
                    e.userid,
                    $fullnamesql AS fullname,
                    COUNT(*) AS total,
                    SUM(CASE WHEN e.datapointlabel = 'scan' THEN 1 ELSE 0 END) AS scancount,
                    SUM(CASE WHEN e.datapointlabel = 'read' THEN 1 ELSE 0 END) AS readcount,
                    SUM(CASE WHEN e.datapointlabel = 'study' THEN 1 ELSE 0 END) AS studycount,
                    SUM(CASE WHEN e.datapointlabel = 'regression' THEN 1 ELSE 0 END) AS regressioncount,
                    SUM(CASE WHEN e.datapointlabel = 'preview' THEN 1 ELSE 0 END) AS previewcount,
                    AVG(e.coverageratio) AS avgcoverage,
                    MAX(e.timecreated) AS lastactive
                FROM {longpage_reading_behavior_events} e
                JOIN {user} u ON u.id = e.userid
                WHERE e.longpageid $pageinsql
                AND e.timecreated >= :start AND e.timecreated < :end
                GROUP BY e.userid, u.firstname, u.lastname";

        $behaviorrows = $DB->get_records_sql($sql, array_merge($pageparams, [
            'start' => $starttime,
            'end' => $endtime,
        ]));
        $behaviorbyuser = [];
        foreach ($behaviorrows as $row) {
            $behaviorbyuser[(int) $row->userid] = $row;
        }

        // Annotation counts (highlights/bookmarks/personal notes/public
        // comments) are a separate fact table (longpage_annotations, keyed
        // by creatorid, not userid) — queried and merged in PHP rather than
        // joined in SQL, since a student can have annotations without any
        // classified reading-behavior events in the selected period (e.g.
        // annotations made before this behavior-tracking feature existed)
        // or vice versa, and this avoids a portability-unsafe FULL OUTER
        // JOIN.
        // Type values: HIGHLIGHT=0, POST=1, BOOKMARK=2 (see
        // classes/local/constants/annotation_type.php); a POST is a
        // "personal note" when ispublic=0, a "public comment" when
        // ispublic=1 — there is no separate DB type for notes vs comments.
        $annotationsql = "SELECT
                    a.creatorid AS userid,
                    SUM(CASE WHEN a.type = 0 THEN 1 ELSE 0 END) AS highlightcount,
                    SUM(CASE WHEN a.type = 2 THEN 1 ELSE 0 END) AS bookmarkcount,
                    SUM(CASE WHEN a.type = 1 AND a.ispublic = 0 THEN 1 ELSE 0 END) AS notecount,
                    SUM(CASE WHEN a.type = 1 AND a.ispublic = 1 THEN 1 ELSE 0 END) AS commentcount
                FROM {longpage_annotations} a
                WHERE a.longpageid $pageinsql
                AND a.timecreated >= :start AND a.timecreated < :end
                GROUP BY a.creatorid";

        $annotationrows = $DB->get_records_sql($annotationsql, array_merge($pageparams, [
            'start' => $starttime,
            'end' => $endtime,
        ]));
        $annotationsbyuser = [];
        foreach ($annotationrows as $arow) {
            $annotationsbyuser[(int) $arow->userid] = $arow;
        }

        // Union of both user sets: a student who only annotated (no
        // classified reading events yet) or only generated reading events
        // (no annotations) must still get exactly one row, not be dropped
        // by an inner join anchored on the other table.
        $userids = array_unique(array_merge(
            array_keys($behaviorbyuser),
            array_keys($annotationsbyuser)
        ));

        $namesbyuser = [];
        $missingids = array_diff($userids, array_keys($behaviorbyuser));
        if (!empty($missingids)) {
            [$insql, $inparams] = $DB->get_in_or_equal(array_values($missingids), SQL_PARAMS_NAMED);
            $namerows = $DB->get_records_sql(
                "SELECT id AS userid, $fullnamesql AS fullname FROM {user} u WHERE id $insql",
                $inparams
            );
            foreach ($namerows as $nrow) {
                $namesbyuser[(int) $nrow->userid] = $nrow->fullname;
            }
        }

        $students = [];
        foreach ($userids as $userid) {
            $row = $behaviorbyuser[$userid] ?? null;
            $arow = $annotationsbyuser[$userid] ?? null;
            $total = $row ? (int) $row->total : 0;
            $students[] = [
                'userid' => $userid,
                'fullname' => $row ? $row->fullname : ($namesbyuser[$userid] ?? ''),
                'scan' => $total > 0 ? round($row->scancount / $total * 100, 1) : 0.0,
                'read' => $total > 0 ? round($row->readcount / $total * 100, 1) : 0.0,
                'study' => $total > 0 ? round($row->studycount / $total * 100, 1) : 0.0,
                'regression' => $total > 0 ? round($row->regressioncount / $total * 100, 1) : 0.0,
                'preview' => $total > 0 ? round($row->previewcount / $total * 100, 1) : 0.0,
                'avgcoverage' => $row ? round(((float) $row->avgcoverage) * 100, 1) : 0.0,
                'lastactive' => $row ? (int) $row->lastactive : 0,
                'highlights' => $arow ? (int) $arow->highlightcount : 0,
                'bookmarks' => $arow ? (int) $arow->bookmarkcount : 0,
                'notes' => $arow ? (int) $arow->notecount : 0,
                'comments' => $arow ? (int) $arow->commentcount : 0,
            ];
        }

        usort($students, function ($a, $b) {
            return strcasecmp($a['fullname'], $b['fullname']);
        });

        return $students;
    }

    /**
     * Parameters for get_dashboard_analytics.
     *
     * @return external_function_parameters
     */
    public static function get_dashboard_analytics_parameters() {
        return new external_function_parameters([
            'longpageids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'Longpage instance ID'),
                'Longpage instance IDs to aggregate analytics across'
            ),
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
                    'count' => new external_value(PARAM_INT, 'Total classified reading event count'),
                    'scan' => new external_value(PARAM_INT, 'Count classified as scan'),
                    'read' => new external_value(PARAM_INT, 'Count classified as read'),
                    'study' => new external_value(PARAM_INT, 'Count classified as study'),
                    'regression' => new external_value(PARAM_INT, 'Count classified as regression'),
                    'preview' => new external_value(PARAM_INT, 'Count classified as preview'),
                ])
            ),
            'readingbehaviortrend' => new external_multiple_structure(
                new external_single_structure([
                    'week' => new external_value(PARAM_TEXT, 'Week identifier (YYYY-WW)'),
                    'weekstart' => new external_value(PARAM_INT, 'Week start timestamp'),
                    'total' => new external_value(PARAM_INT, 'Total classified data points this week'),
                    'scan' => new external_value(PARAM_FLOAT, 'Share (%) classified as scan'),
                    'read' => new external_value(PARAM_FLOAT, 'Share (%) classified as read'),
                    'study' => new external_value(PARAM_FLOAT, 'Share (%) classified as study'),
                    'regression' => new external_value(PARAM_FLOAT, 'Share (%) classified as regression'),
                    'preview' => new external_value(PARAM_FLOAT, 'Share (%) classified as preview'),
                ])
            ),
            'readingbehaviorbystudent' => new external_multiple_structure(
                new external_single_structure([
                    'userid' => new external_value(PARAM_INT, 'User ID'),
                    'fullname' => new external_value(PARAM_TEXT, 'Student full name'),
                    'scan' => new external_value(PARAM_FLOAT, 'Share (%) classified as scan'),
                    'read' => new external_value(PARAM_FLOAT, 'Share (%) classified as read'),
                    'study' => new external_value(PARAM_FLOAT, 'Share (%) classified as study'),
                    'regression' => new external_value(PARAM_FLOAT, 'Share (%) classified as regression'),
                    'preview' => new external_value(PARAM_FLOAT, 'Share (%) classified as preview'),
                    'avgcoverage' => new external_value(PARAM_FLOAT, 'Average element coverage (%)'),
                    'lastactive' => new external_value(PARAM_INT, 'Timestamp of most recent classified event'),
                    'highlights' => new external_value(PARAM_INT, 'Highlight annotation count'),
                    'bookmarks' => new external_value(PARAM_INT, 'Bookmark annotation count'),
                    'notes' => new external_value(PARAM_INT, 'Personal (non-public) note count'),
                    'comments' => new external_value(PARAM_INT, 'Public comment count'),
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
        } else if ($createdmonth >= 4) {
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

    /**
     * List the longpage instances in a course that the current user may see
     * analytics for — feeds the dashboard's multiselect, which lets a
     * teacher combine several instances (e.g. one per week's reading) into
     * one aggregated view instead of only ever seeing a single instance.
     *
     * @param int $courseid Course ID
     * @return array Longpage instances {id, name}
     */
    public static function get_course_longpages($courseid) {
        $params = self::validate_parameters(
            self::get_course_longpages_parameters(),
            ['courseid' => $courseid]
        );

        $course = get_course($params['courseid']);
        self::validate_context(\context_course::instance($course->id));

        $instances = get_all_instances_in_course('longpage', $course);
        $longpages = [];
        foreach ($instances as $instance) {
            $cmcontext = \context_module::instance($instance->coursemodule);
            if (!has_capability('mod/longpage:modannotations', $cmcontext)) {
                continue;
            }
            $longpages[] = [
                'id' => (int) $instance->id,
                'name' => $instance->name,
            ];
        }

        return $longpages;
    }

    /**
     * Parameters for get_course_longpages.
     *
     * @return external_function_parameters
     */
    public static function get_course_longpages_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
        ]);
    }

    /**
     * Return type for get_course_longpages.
     *
     * @return external_multiple_structure
     */
    public static function get_course_longpages_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Longpage instance ID'),
                'name' => new external_value(PARAM_TEXT, 'Longpage instance name'),
            ])
        );
    }
}
