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
 * Question bank external services for direct question loading
 *
 * @package    mod_longpage
 * @category   external
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_longpage\external;

use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once("$CFG->libdir/externallib.php");
require_once("$CFG->libdir/questionlib.php");
require_once("$CFG->dirroot/question/engine/lib.php");

/**
 * External services for loading questions directly from question bank
 *
 * @package    mod_longpage
 * @category   external
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class questions_bank_services extends base_external {
    /**
     * Get questions for a page instance
     *
     * @param int $longpageid Page instance ID
     * @return array Array of questions with their data
     */
    public static function get_questions_for_page($longpageid) {
        global $DB, $USER;

        self::validate_parameters(
            self::get_questions_for_page_parameters(),
            ['longpageid' => $longpageid]
        );

        $page = $DB->get_record('longpage', ['id' => $longpageid], '*', MUST_EXIST);
        [$course, $cm] = get_course_and_cm_from_instance($page, 'longpage');
        $context = \context_module::instance($cm->id);
        self::validate_context($context);

        // Fetch questions tagged for this page.
        $sql = "SELECT DISTINCT q.id, q.name, q.questiontext, q.qtype
                FROM {question} q
                INNER JOIN {tag_instance} ti ON q.id = ti.itemid
                INNER JOIN {tag} t ON ti.tagid = t.id
                WHERE ti.itemtype = 'question'
                  AND ti.component = 'core_question'
                  AND t.name LIKE ?
                  AND q.qtype != 'category'
                ORDER BY q.id";

        $tagpattern = 'q:' . $cm->id . ':%';
        $questions = $DB->get_records_sql($sql, [$tagpattern]);

        $result = [];
        foreach ($questions as $question) {
            $q = \question_bank::load_question($question->id);
            $result[] = self::format_question($q, $context);
        }

        return $result;
    }

    /**
     * Get a single question with its current state
     *
     * @param int $questionid Question ID
     * @param int $longpageid Page instance ID
     * @return array Question data with state
     */
    public static function get_question_detail($questionid, $longpageid) {
        global $DB, $USER;

        self::validate_parameters(
            self::get_question_detail_parameters(),
            ['questionid' => $questionid, 'longpageid' => $longpageid]
        );

        $page = $DB->get_record('longpage', ['id' => $longpageid], '*', MUST_EXIST);
        [$course, $cm] = get_course_and_cm_from_instance($page, 'longpage');
        $context = \context_module::instance($cm->id);
        self::validate_context($context);

        $q = \question_bank::load_question($questionid);
        if (!$q) {
            throw new \moodle_exception('questionnotfound', 'mod_longpage');
        }

        return self::format_question($q, $context);
    }

    /**
     * Format question for API response
     *
     * @param object $question Question object
     * @param object $context Context
     * @return array Formatted question data
     */
    private static function format_question($question, $context) {
        $result = new \stdClass();
        $result->id = $question->id;
        $result->name = $question->name;
        $result->qtype = $question->qtype;
        $result->questiontext = file_rewrite_pluginfile_urls(
            $question->questiontext,
            'pluginfile.php',
            $context->id,
            'question',
            'questiontext',
            $question->id
        );

        // Load question-type-specific data.
        if ($question->qtype === 'multichoice') {
            $result->answers = [];
            foreach ($question->answers as $answer) {
                $result->answers[] = [
                    'id' => $answer->id,
                    'answer' => $answer->answer,
                    'fraction' => (float)$answer->fraction,
                ];
            }
        }

        return $result;
    }

    /**
     * Get parameters for get_questions_for_page
     *
     * @return external_function_parameters
     */
    public static function get_questions_for_page_parameters() {
        return new external_function_parameters([
            'longpageid' => new external_value(PARAM_INT, 'Page instance ID'),
        ]);
    }

    /**
     * Get return value structure for get_questions_for_page
     *
     * @return external_multiple_structure
     */
    public static function get_questions_for_page_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Question ID'),
                'name' => new external_value(PARAM_TEXT, 'Question name'),
                'qtype' => new external_value(PARAM_TEXT, 'Question type'),
                'questiontext' => new external_value(PARAM_RAW, 'Question text'),
                'answers' => new external_multiple_structure(
                    new external_single_structure([
                        'id' => new external_value(PARAM_INT, 'Answer ID'),
                        'answer' => new external_value(PARAM_RAW, 'Answer text'),
                        'fraction' => new external_value(PARAM_FLOAT, 'Fraction correct'),
                    ])
                ),
            ])
        );
    }

    /**
     * Get parameters for get_question_detail
     *
     * @return external_function_parameters
     */
    public static function get_question_detail_parameters() {
        return new external_function_parameters([
            'questionid' => new external_value(PARAM_INT, 'Question ID'),
            'longpageid' => new external_value(PARAM_INT, 'Page instance ID'),
        ]);
    }

    /**
     * Get return value structure for get_question_detail
     *
     * @return external_single_structure
     */
    public static function get_question_detail_returns() {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Question ID'),
            'name' => new external_value(PARAM_TEXT, 'Question name'),
            'qtype' => new external_value(PARAM_TEXT, 'Question type'),
            'questiontext' => new external_value(PARAM_RAW, 'Question text'),
            'answers' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Answer ID'),
                    'answer' => new external_value(PARAM_RAW, 'Answer text'),
                    'fraction' => new external_value(PARAM_FLOAT, 'Fraction correct'),
                ])
            ),
        ]);
    }
}
