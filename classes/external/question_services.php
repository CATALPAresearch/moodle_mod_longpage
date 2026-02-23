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
 * Question and quiz related external services
 *
 * @package    mod_longpage
 * @category   external
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_longpage\external;

use DateInterval;
use DateTime;
use DOMDocument;
use Exception;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use filter_embedquestion\attempt;
use filter_embedquestion\embed_id;
use filter_embedquestion\embed_location;
use filter_embedquestion\external;
use filter_embedquestion\utils;
use stdClass;

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once("$CFG->libdir/externallib.php");
require_once("$CFG->dirroot/mod/longpage/locallib.php");
require_once("$CFG->dirroot/mod/longpage/lib.php");
require_once("$CFG->libdir/questionlib.php");
require_once("$CFG->dirroot/question/engine/lib.php");
require_once("$CFG->dirroot/question/format.php");
require_once("$CFG->dirroot/question/format/gift/format.php");
require_once("$CFG->libdir/gradelib.php");

/**
 * External services for AI question generation and quiz integration.
 *
 * @package    mod_longpage
 * @category   external
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_services extends base_external {
    /**
     * Get questions rendered for a page instance.
     *
     * @param int $longpageid Page instance ID
     * @return array
     */
    public static function get_questions_by_page_id($longpageid) {
        global $DB;

        self::validate_parameters(
            self::get_questions_by_page_id_parameters(),
            [
                'longpageid' => $longpageid,
            ]
        );

        $page = $DB->get_record('longpage', ['id' => $longpageid], '*', MUST_EXIST);
        [$course, $cm] = get_course_and_cm_from_instance($page, 'longpage');

        $context = \context_module::instance($cm->id);
        self::validate_context($context);

        $query = "SELECT it.id, t.name as tagname
                    FROM {question} it INNER JOIN {tag_instance} tt ON it.id = tt.itemid INNER JOIN {tag} t on tt.tagid = t.id
                   WHERE tt.itemtype=? AND t.name LIKE ? AND tt.component=? ORDER BY it.id";

        $questions = $DB->get_records_sql($query, ['question', 'q:' . $cm->id . ':%', 'core_question']);

        $quba = \question_engine::make_questions_usage_by_activity('core_question', $context);
        $options = new \question_display_options();
        $options->correctness = \question_display_options::VISIBLE;
        $options->feedback = \question_display_options::VISIBLE;
        $options->marks = \question_display_options::MARK_AND_MAX;
        $quba->set_preferred_behaviour('immediatefeedback');

        $res = [];
        foreach ($questions as $question) {
            $q = \question_bank::load_question($question->id);
            $slot = $quba->add_question($q);
            $quba->start_question($slot);
            $html = $quba->render_question($slot, $options);
            $res[] = [
                'tagname' => str_replace('q:' . $cm->id . ':', '', $question->tagname),
                'questionid' => $question->id,
                'slot' => $slot,
                'html' => $html,
            ];
        }
        $qubaid = \question_engine::save_questions_usage_by_activity($quba);
        foreach ($res as &$row) {
            $row['qubaid'] = $qubaid;
        }
        unset($row);

        return ['qubaid' => $qubaid, 'questions' => $res];
    }

    /**
     * Parameters for get_questions_by_page_id.
     *
     * @return external_function_parameters
     */
    public static function get_questions_by_page_id_parameters() {
        return new external_function_parameters(
            [
                'longpageid' => new external_value(PARAM_INT, 'page instance id'),
            ]
        );
    }

    /**
     * Returns for get_questions_by_page_id.
     *
     * @return external_single_structure
     */
    public static function get_questions_by_page_id_returns() {
        return new external_single_structure(
            [
                'qubaid' => new external_value(PARAM_INT, 'question usage id'),
                'questions' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'tagname' => new external_value(PARAM_RAW),
                            'questionid' => new external_value(PARAM_INT),
                            'slot' => new external_value(PARAM_INT),
                            'qubaid' => new external_value(PARAM_INT),
                            'html' => new external_value(PARAM_RAW),
                        ]
                    )
                ),
            ]
        );
    }

    /**
     * Get reading comprehension data for the current user.
     *
     * @param int $longpageid Page instance ID
     * @return array
     */
    public static function get_reading_comprehension($longpageid) {
        global $DB, $USER, $CFG;

        self::validate_parameters(
            self::get_reading_comprehension_parameters(),
            [
                'longpageid' => $longpageid,
            ]
        );

        $page = $DB->get_record('longpage', ['id' => $longpageid], '*', MUST_EXIST);
        [$course, $cm] = get_course_and_cm_from_instance($page, 'longpage');

        $context = \context_module::instance($cm->id);
        self::validate_context($context);

        $options = ['noclean' => true];
        [$page->content, $page->contentformat] = external_format_text(
            $page->content,
            $page->contentformat,
            $context->id,
            'mod_longpage',
            'content',
            $page->revision,
            $options
        );

        $coursecontext = \context_course::instance($course->id);
        $result = [];

        preg_match_all(
            '/<iframe[\S\s]+class="filter_embedquestion-iframe[\S\s]+id="(?<catid>\S+)\/(?<qid>\S+)"/iU',
            $page->content,
            $matches
        );
        $len = count($matches[1]);
        $sum = 0;

        for ($i = 0; $i < $len; $i++) {
            $embed = new embed_id($matches['catid'][$i], $matches['qid'][$i]);
            $category = utils::get_category_by_idnumber($coursecontext, $embed->categoryidnumber);
            $question = utils::get_question_by_idnumber((int) $category->id, $embed->questionidnumber);
            $tagobjectsbyquestion = \core_tag_tag::get_item_tags('core_question', 'question', $question->id);
            $tagobjects = [];
            if (!empty($tagobjectsbyquestion)) {
                $tagobjects = array_map(function ($tagobject) {
                    return strtolower($tagobject->rawname);
                }, $tagobjectsbyquestion);
            }

            $questionisnew = false;
            foreach ($tagobjects as $tagobject) {
                if ($tagobject === 'neu') {
                    $questionisnew = true;
                    break;
                }
            }

            if ($questionisnew && !has_capability('mod/longpage:modannotations', $context)) {
                continue;
            }

            $avgfraction = $DB->get_field_sql(
                "SELECT AVG(fraction) as avgfraction FROM (SELECT qas.fraction FROM " . $CFG->prefix . "question_attempts qa
                                INNER JOIN " . $CFG->prefix . "question_attempt_steps qas
                                ON qas.questionattemptid = qa.id
                                WHERE qas.userid = ? AND qas.fraction IS NOT NULL AND qa.questionid = ?
                                AND qas.sequencenumber = (
                                                SELECT MAX(sequencenumber)
                                                FROM " . $CFG->prefix . "question_attempt_steps
                                                WHERE questionattemptid = qa.id
                                            )
                                AND qas.timecreated > ?
                                ORDER BY qas.timecreated DESC
                                LIMIT 5) alias",
                [
                    $USER->id,
                    $question->id,
                    date_format(date_sub(date_create(), DateInterval::createFromDateString('3 months')), 'U'),
                ]
            );

            $sum += $avgfraction;
            $result[strval($embed)] = [
                'value' => $avgfraction,
                'level' => 1,
                'id' => $question->id,
                'tags' => $tagobjects,
            ];
        }

        $grade = new stdClass();
        $grade->userid = $USER->id;
        $grade->rawgrade = $len;
        $gradepass = 0;
        $grades = grade_get_grades($course->id, 'mod', 'longpage', $page->id, $USER->id);
        if (!empty($grades->items)) {
            $gradepass = (float) $grades->items[0]->gradepass;
        }
        longpage_update_grades($page, $grade);

        return [
            'response' => json_encode($result),
            'gradeInfo' => json_encode(['grade' => $grade->rawgrade, 'gradepass' => $gradepass]),
        ];
    }

    /**
     * Parameters for get_reading_comprehension.
     *
     * @return external_function_parameters
     */
    public static function get_reading_comprehension_parameters() {
        return new external_function_parameters(
            [
                'longpageid' => new external_value(PARAM_INT, 'page instance id'),
            ]
        );
    }

    /**
     * Returns for get_reading_comprehension.
     *
     * @return external_single_structure
     */
    public static function get_reading_comprehension_returns() {
        return new external_single_structure(
            [
                'response' => new external_value(PARAM_RAW),
                'gradeInfo' => new external_value(PARAM_RAW),
            ]
        );
    }

    /**
     * Embed a question into the longpage content.
     *
     * @param int $longpageid Page instance ID
     * @param string $embedcode Embed code
     * @param int $position Position
     * @return array
     */
    public static function embed_question($longpageid, $embedcode, $position) {
        global $DB, $PAGE, $CFG;

        self::validate_parameters(
            self::embed_question_parameters(),
            [
                'longpageid' => $longpageid,
                'embedcode' => $embedcode,
                'position' => $position,
            ]
        );

        $page = $DB->get_record('longpage', ['id' => $longpageid], '*', MUST_EXIST);
        [$course, $cm] = get_course_and_cm_from_instance($page, 'longpage');

        $context = \context_module::instance($cm->id);
        self::validate_context($context);

        require_capability('mod/longpage:modannotations', $context);

        $options = ['trusted' => true, 'noclean' => true, 'filter' => false];
        [$page->content, $page->contentformat] = external_format_text(
            $page->content,
            $page->contentformat,
            $context->id,
            'mod_longpage',
            'content',
            $page->revision,
            $options
        );

        $dom = new DOMDocument();
        $dom->loadHTML(mb_convert_encoding($page->content, 'HTML-ENTITIES', 'UTF-8'));

        $toplevel = self::get_top_level_element($dom, $position);
        $newcontent = $page->content;

        if ($toplevel) {
            $sibling = $toplevel->nextSibling;
            while ($sibling && $sibling->nodeName !== $toplevel->nodeName) {
                $sibling = $sibling->nextSibling;
            }

            if (!$sibling || strpos($sibling->textContent, '{Q{') === false) {
                $newelement = $dom->createElement($toplevel->nodeName);
                $newelement->nodeValue = $embedcode;
                $toplevel->parentNode->insertBefore($newelement, $toplevel->nextSibling);
            } else {
                $sibling->nodeValue .= ' ' . $embedcode;
            }

            $newcontent = $dom->saveHTML();
        }

        $DB->update_record('longpage', ['id' => $longpageid, 'content' => $newcontent, 'timemodified' => time()]);

        require_once($CFG->dirroot . '/filter/embedquestion/filter.php');
        $filter = new \filter_embedquestion($context, []);
        $filter->setup($PAGE, $context);
        $embedcode = str_replace('{Q{', '', $embedcode);
        $embedcode = str_replace('}Q}', '', $embedcode);
        $iframecode = $filter->embed_question($embedcode);

        return ['response' => $iframecode];
    }

    /**
     * Parameters for embed_question.
     *
     * @return external_function_parameters
     */
    public static function embed_question_parameters() {
        return new external_function_parameters(
            [
                'longpageid' => new external_value(PARAM_INT, 'page instance id'),
                'embedcode' => new external_value(PARAM_RAW, 'embed code'),
                'position' => new external_value(PARAM_INT, 'position'),
            ]
        );
    }

    /**
     * Returns for embed_question.
     *
     * @return external_single_structure
     */
    public static function embed_question_returns() {
        return new external_single_structure(
            ['response' => new external_value(PARAM_RAW, 'Server response to autosave')]
        );
    }

    /**
     * Remove an embedded question from the content.
     *
     * @param int $longpageid Page instance ID
     * @param string $embedid Embed ID
     * @param int $position Position
     * @return array
     */
    public static function remove_question($longpageid, $embedid, $position) {
        global $DB;

        self::validate_parameters(
            self::remove_question_parameters(),
            [
                'longpageid' => $longpageid,
                'embedid' => $embedid,
                'position' => $position,
            ]
        );

        $page = $DB->get_record('longpage', ['id' => $longpageid], '*', MUST_EXIST);
        [$course, $cm] = get_course_and_cm_from_instance($page, 'longpage');

        $context = \context_module::instance($cm->id);
        self::validate_context($context);

        require_capability('mod/longpage:modannotations', $context);

        $options = ['trusted' => true, 'noclean' => true, 'filter' => false];
        [$page->content, $page->contentformat] = external_format_text(
            $page->content,
            $page->contentformat,
            $context->id,
            'mod_longpage',
            'content',
            $page->revision,
            $options
        );

        $dom = new DOMDocument();
        $dom->loadHTML(mb_convert_encoding($page->content, 'HTML-ENTITIES', 'UTF-8'));

        $toplevel = self::get_top_level_element($dom, $position);
        $newcontent = $page->content;

        if ($toplevel) {
            $sibling = $toplevel->nextSibling;
            while ($sibling && $sibling->nodeName !== $toplevel->nodeName) {
                $sibling = $sibling->nextSibling;
            }

            if ($sibling && strpos($sibling->textContent, $embedid) !== false) {
                $sibling->textContent = preg_replace('/{Q{' . preg_quote($embedid, '/') . '.*?}Q}/', '', $sibling->textContent);
                if (empty(trim($sibling->textContent))) {
                    $toplevel->parentNode->removeChild($sibling);
                }
            }

            $newcontent = $dom->saveHTML();
        }

        $DB->update_record('longpage', ['id' => $longpageid, 'content' => $newcontent, 'timemodified' => time()]);

        return ['response' => json_encode('success')];
    }

    /**
     * Parameters for remove_question.
     *
     * @return external_function_parameters
     */
    public static function remove_question_parameters() {
        return new external_function_parameters(
            [
                'longpageid' => new external_value(PARAM_INT, 'page instance id'),
                'embedid' => new external_value(PARAM_RAW, 'embed id'),
                'position' => new external_value(PARAM_INT, 'position'),
            ]
        );
    }

    /**
     * Returns for remove_question.
     *
     * @return external_single_structure
     */
    public static function remove_question_returns() {
        return new external_single_structure(
            ['response' => new external_value(PARAM_RAW, 'Server response to remove_question')]
        );
    }

    /**
     * Request a chat response for AI question generation.
     *
     * @param string $systemcontent
     * @param string $usercontent
     * @return object
     */
    protected static function chat($systemcontent, $usercontent) {
        $token = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6IjczYmUyMGFiLWI4YjYtNDNmNS05YmZjLWIz"
            . "MDU1OGZkODZiYyJ9.7QCdTgHAPVvTJgkbr7NLxYcO4iUTwlL4ai6rfw_neXE";
        $url = 'http://catalpa-llm.fernuni-hagen.de:11434/api/chat';
        $backupurl = 'http://catalpa-llm.fernuni-hagen.de:11434/api/chat';
        $model = 'llama3.1:latest';
        $authorization = '';

        $systemcontent = str_replace("\n", '', $systemcontent);
        $systemcontent = str_replace("\r", '', $systemcontent);

        $escapers = ["\\", "/", "\"", "\n", "\r", "\t", "\x08", "\x0c"];
        $replacements = ["\\\\", "\\/", "\\\"", "\\n", "\\r", "\\t", "\\f", "\\b"];
        $systemcontent = str_replace($escapers, $replacements, $systemcontent);
        $usercontent = str_replace($escapers, $replacements, $usercontent);
        $systemcontent = str_replace("'", "\\\"", $systemcontent);
        $usercontent = str_replace("'", "\\\"", $usercontent);

        $data = '{
            "model": "' . $model . '",
            "messages": [
            {"role": "system", "content": "' . $systemcontent . '"},
            {"role": "user", "content": "' . $usercontent . '"}
            ],
            "stream": false
        }';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', $authorization]);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 180);
        $res = curl_exec($ch);
        if (curl_errno($ch)) {
            curl_setopt($ch, CURLOPT_URL, $backupurl);
            $res = curl_exec($ch);
            if (curl_errno($ch)) {
                throw new Exception(curl_error($ch));
            }
        }
        $result = json_decode($res);
        curl_close($ch);

        if (!isset($result->message->content)) {
            throw new Exception("Problem with AI model: '" . $res . "'");
        }

        $result->message->content = str_replace(["'", '"'], '', $result->message->content);
        return $result;
    }

    /**
     * Get the target element by position.
     *
     * @param DOMDocument $dom
     * @param int $position
     * @return object|null
     */
    protected static function get_top_level_element($dom, $position) {
        $topleveltag = $dom->getElementsByTagName('body')->item(0)->childNodes->item(0);
        while ($topleveltag->nodeName !== 'div' && $topleveltag->nodeName !== 'p' && $topleveltag->nextSibling) {
            $topleveltag = $topleveltag->nextSibling;
        }

        $toplevelelements = array_filter(
            iterator_to_array($topleveltag->parentNode->childNodes),
            function ($element) use ($topleveltag) {
                return $element->nodeName === $topleveltag->nodeName;
            }
        );

        $filtered = [];
        foreach ($toplevelelements as $element) {
            if (strpos($element->textContent, '{Q{') === false) {
                $filtered[] = $element;
            }
        }

        if (count($filtered) > $position) {
            return $filtered[$position];
        }

        return $topleveltag;
    }

    /**
     * Create a question with AI or as an empty template.
     *
     * @param int $longpageid Page instance ID
     * @param int $position Position
     * @param bool $useai Use AI
     * @param string $existingquestions Existing questions
     * @param string $selectedtext Selected text
     * @param string $selectedparagraphs Selected paragraphs
     * @return array
     */
    public static function create_question(
        $longpageid,
        $position,
        $useai = true,
        $existingquestions = '',
        $selectedtext = '',
        $selectedparagraphs = ''
    ) {
        global $CFG, $DB, $USER;

        $now = new DateTime();

        self::validate_parameters(
            self::create_question_parameters(),
            [
                'longpageid' => $longpageid,
                'position' => $position,
                'useAI' => $useai,
                'existingQuestions' => $existingquestions,
                'selectedText' => $selectedtext,
                'selectedParagraphs' => $selectedparagraphs,
            ]
        );

        $page = $DB->get_record('longpage', ['id' => $longpageid], '*', MUST_EXIST);
        [$course, $cm] = get_course_and_cm_from_instance($page, 'longpage');

        $context = \context_module::instance($cm->id);
        self::validate_context($context);

        require_capability('mod/longpage:modannotations', $context);

        $coursecontext = \context_course::instance($course->id);
        
        // Auto-create question categories if they don't exist
        $idnumber = $useai ? 'aigenerated' : 'manuallygenerated';
        $categoryname = $useai ? 'AI Generated Questions' : 'Manually Generated Questions';
        
        $category = $DB->get_record(
            'question_categories',
            ['contextid' => $coursecontext->id, 'idnumber' => $idnumber]
        );
        
        if (!$category) {
            // Get or create top-level category for the course
            $topcat = $DB->get_record('question_categories', [
                'contextid' => $coursecontext->id,
                'parent' => 0
            ]);
            
            if (!$topcat) {
                // Create top-level category if it doesn't exist
                $topcat = new \stdClass();
                $topcat->contextid = $coursecontext->id;
                $topcat->parent = 0;
                $topcat->name = 'top';
                $topcat->info = '';
                $topcat->infoformat = FORMAT_HTML;
                $topcat->stamp = make_unique_id_code();
                $topcat->sortorder = 999;
                $topcat->id = $DB->insert_record('question_categories', $topcat);
            }
            
            // Create the category
            $category = new \stdClass();
            $category->contextid = $coursecontext->id;
            $category->parent = $topcat->id;
            $category->name = $categoryname;
            $category->idnumber = $idnumber;
            $category->info = 'Questions for longpage module';
            $category->infoformat = FORMAT_HTML;
            $category->stamp = make_unique_id_code();
            $category->sortorder = 999;
            $category->id = $DB->insert_record('question_categories', $category);
        }

        $options = ['noclean' => true, 'filter' => false];
        [$page->content, $page->contentformat] = external_format_text(
            $page->content,
            $page->contentformat,
            $context->id,
            'mod_longpage',
            'content',
            $page->revision,
            $options
        );

        $qformat = new \qformat_gift();

        if ($useai) {
            $dom = new DOMDocument();
            $dom->loadHTML(mb_convert_encoding($page->content, 'HTML-ENTITIES', 'UTF-8'));

            if ($selectedparagraphs !== '') {
                $selectedparagraphs = explode(',', $selectedparagraphs);
                $textfromselected = '';
                foreach ($selectedparagraphs as $selectedparagraph) {
                    if ($selectedparagraph != $position) {
                        $paragraph = self::get_top_level_element($dom, $selectedparagraph);
                        $textfromselected .= $paragraph->textContent;
                    }
                }
            }

            $toplevel = self::get_top_level_element($dom, $position);
            $textcontent = $toplevel->textContent;

            if ($selectedtext !== '') {
                $textcontent = "The complete text is: '" . $textcontent
                    . "' You should create a question based on the following excerpt: '" . $selectedtext . "'";
            }

            if ($selectedparagraphs !== '') {
                $textcontent .= " The following text is from the context and should be considered for the"
                    . " question and answer options: '" . $textfromselected . "'";
            }

            $qtypes = ['multichoice'];
            $qtype = $qtypes[array_rand($qtypes)];

            switch ($qtype) {
                case 'multichoice':
                    $explanation = "Please write one multiple choice question with one correct answer and multiple "
                        . "wrong answers in German language in GIFT format on the following text. GIFT format uses "
                        . "equal sign for right answers and tilde sign for wrong answers at the beginning of answers. "
                        . "For example: '::Question title:: Question text { =Correct answer 1 ~Wrong answer 1 "
                        . "~Wrong answer 2 ~Wrong answer 3 }' Do not forget any equal or tilde sign! Only one correct "
                        . "answer is allowed! Question title and question text are mandatory and different from each other!";
                    break;
                case 'multiresponse':
                    $explanation = "Please write one multiple choice question with multiple correct answers in German "
                        . "language in GIFT format on the following text. GIFT format uses a tilde and percent sign "
                        . "at the beginning of answers, followed by a positive grade in percent for correct answers "
                        . "and a negative grade in negative percent with minus sign for wrong answers. All positive "
                        . "grades must sum up to 100%. For example: '::Question title:: Question text { ~%-100% Wrong "
                        . "answer 1 ~%50% Correct answer 1 ~%50% Correct answer 2 ~%-100% Wrong answer 2 }' Do not "
                        . "forget any tilde or percent sign! ";
                    break;
                case 'match':
                    $explanation = "Please write one matching question in German language in GIFT format on the "
                        . "following text. GIFT format uses an equal sign at the beginning of answers and and arrow "
                        . "sign for assigning matching pairs. For example: '::Question title:: Question text { "
                        . "=match 1 -> match 1 =match 2 -> match 2 =match 3 -> match 3 }' The matches should be "
                        . "concepts of only a few words. Do not forget any equal or arrow sign! ";
                    break;
                default:
                    $explanation = '';
            }

            if ($existingquestions !== '') {
                $explanation .= "The following questions are already created and should not be "
                    . "created again: '" . $existingquestions . "' ";
            }

            $explanation .= 'Please write the question in the right format! Output only in GIFT format! '
                . 'Do not forget question title and question text!';
            $maxtries = 5;

            for ($i = 0; $i < $maxtries; $i++) {
                try {
                    $result = self::chat($textcontent, $explanation);

                    if ($qtype === 'multiresponse') {
                        $qtype = 'multichoice';
                    }

                    $q = $qformat->readquestion(explode("\n", $result->message->content));
                    if (!$q) {
                        throw new Exception('Question not valid.');
                    }

                    if ($q->questiontext == null) {
                        throw new Exception('Question text is empty.');
                    }

                    $correctanswers = 0;
                    $sum = 0;
                    foreach ($q->fraction as $fraction) {
                        if ($fraction == 1) {
                            $correctanswers++;
                            $sum += $fraction;
                            if ($correctanswers > 1) {
                                throw new Exception('More than one correct answer.');
                            }
                        }
                    }

                    if ($sum != 1) {
                        throw new Exception('There has to be one answer with 100%.');
                    }

                    $q->idnumber = 'ai-generated-' . time() . '-' . $USER->id;
                    $q->shuffleanswers = false;

                    $created = self::save_question($q, $category, $qtype);
                    if (!$created) {
                        throw new Exception('Question not created.');
                    }
                    break;
                } catch (\Throwable $th) {
                    global $CFG;
                    // Log error properly instead of using debugging()
                    if (!empty($CFG->debugdeveloper)) {
                        error_log('Longpage: Create Question Error - ' . $th->getMessage());
                    }
                    if ($i >= $maxtries - 1) {
                        throw $th;
                    }
                }
            }
        } else {
            $qtype = 'multichoice';
            $content = "::Fragenname:: Fragentext { " .
                "=Korrekte Antwort " .
                "~Falsche Antwort " .
                "}";
            $q = $qformat->readquestion(explode("\n", $content));
            $q->idnumber = 'manually-generated-' . time() . '-' . $USER->id;
            $q->shuffleanswers = false;

            $created = self::save_question($q, $category, $qtype);
            if (!$created) {
                throw new Exception('Question not created.');
            }
        }

        if (!empty($created)) {
            $embedcode = external::get_embed_code(
                $course->id,
                $category->idnumber,
                $q->idnumber,
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                ''
            );
            $iframecode = self::embed_question($longpageid, $embedcode, $position);
            $iframecode = $iframecode['response'];
            \core_tag_tag::add_item_tag('core_question', 'question', $created->id, $context, 'neu');
            utility_services::log([
                'longpageid' => $longpageid,
                'courseid' => $course->id,
                'utc' => time(),
                'action' => 'question',
                'entry' => json_encode([
                    'type' => 'create',
                    'questionid' => $created->id,
                    'qtype' => $qtype,
                    'selectedText' => $selectedtext,
                    'selectedParagraphs' => $selectedparagraphs,
                    'useAI' => $useai ? 'true' : 'false',
                    'existingQuestions' => $existingquestions,
                    'position' => $position,
                    'elapsedTimeMs' => $now->diff(new DateTime())->f,
                    'embedid' => $category->idnumber . '/' . $q->idnumber,
                    'longpageid' => $longpageid,
                ]),
            ]);
        }

        return ['response' => json_encode(['iframecode' => $iframecode, 'log' => 'Selected text: ' . $selectedtext], JSON_UNESCAPED_UNICODE)];
    }

    /**
     * Parameters for create_question.
     *
     * @return external_function_parameters
     */
    public static function create_question_parameters() {
        return new external_function_parameters(
            [
                'longpageid' => new external_value(PARAM_INT, 'page instance id'),
                'position' => new external_value(PARAM_INT, 'position of embed code in text'),
                'useAI' => new external_value(PARAM_BOOL, 'use AI, otherwise empty', VALUE_DEFAULT),
                'existingQuestions' => new external_value(PARAM_RAW, 'existing questions', VALUE_DEFAULT),
                'selectedText' => new external_value(PARAM_RAW, 'selected text', VALUE_DEFAULT),
                'selectedParagraphs' => new external_value(PARAM_RAW, 'selected paragraphs', VALUE_DEFAULT),
            ]
        );
    }

    /**
     * Returns for create_question.
     *
     * @return external_single_structure
     */
    public static function create_question_returns() {
        return new external_single_structure(
            ['response' => new external_value(PARAM_RAW, 'Server response to create_question')]
        );
    }

    /**
     * Toggle lock state for question.
     *
     * @param int $longpageid Page instance ID
     * @param int $questionid Question ID
     * @return array
     */
    public static function lock_question($longpageid, $questionid) {
        global $DB;

        self::validate_parameters(
            self::lock_question_parameters(),
            [
                'longpageid' => $longpageid,
                'questionid' => $questionid,
            ]
        );

        $page = $DB->get_record('longpage', ['id' => $longpageid], '*', MUST_EXIST);
        [$course, $cm] = get_course_and_cm_from_instance($page, 'longpage');

        $context = \context_module::instance($cm->id);
        self::validate_context($context);

        require_capability('mod/longpage:modannotations', $context);

        if (\core_tag_tag::is_item_tagged_with('core_question', 'question', $questionid, 'neu')) {
            \core_tag_tag::remove_item_tag('core_question', 'question', $questionid, 'neu');
        } else {
            \core_tag_tag::add_item_tag('core_question', 'question', $questionid, $context, 'neu');
        }

        return ['response' => json_encode('success')];
    }

    /**
     * Parameters for lock_question.
     *
     * @return external_function_parameters
     */
    public static function lock_question_parameters() {
        return new external_function_parameters(
            [
                'longpageid' => new external_value(PARAM_INT, 'page instance id'),
                'questionid' => new external_value(PARAM_INT, 'question id'),
            ]
        );
    }

    /**
     * Returns for lock_question.
     *
     * @return external_single_structure
     */
    public static function lock_question_returns() {
        return new external_single_structure(
            ['response' => new external_value(PARAM_RAW, 'Server response to lock_question')]
        );
    }

    /**
     * Edit a question or its options.
     *
     * @param int $longpageid Page instance ID
     * @param int $questionid Question ID
     * @param string $action Action
     * @param int $qubaid QUBA ID
     * @param bool $useai Use AI
     * @param string $text Text
     * @param int $optionnumber Option number
     * @return array
     */
    public static function edit_question(
        $longpageid,
        $questionid,
        $action,
        $qubaid,
        $useai = true,
        $text = '',
        $optionnumber = -1
    ) {
        global $DB, $USER;

        $now = new DateTime();

        self::validate_parameters(
            self::edit_question_parameters(),
            [
                'longpageid' => $longpageid,
                'questionid' => $questionid,
                'action' => $action,
                'text' => $text,
                'qubaid' => $qubaid,
                'optionNumber' => $optionnumber,
            ]
        );

        $page = $DB->get_record('longpage', ['id' => $longpageid], '*', MUST_EXIST);
        [$course, $cm] = get_course_and_cm_from_instance($page, 'longpage');

        $context = \context_module::instance($cm->id);
        self::validate_context($context);

        require_capability('mod/longpage:modannotations', $context);

        $question = \question_bank::load_question($questionid);
        $question->qtype = $question->qtype->name();
        $question->generalfeedback = ['text' => $question->generalfeedback, 'format' => $question->generalfeedbackformat];
        get_question_options($question);
        $question->questiontext = [
            'text' => $optionnumber != -1 || $action != 'edit' ? $question->questiontext : $text,
            'format' => $question->questiontextformat,
        ];
        $question->fraction = [];
        $question->feedback = [];
        $question->answer = $question->answers;
        $question->single = $question->options->single;

        $quba = \question_engine::load_questions_usage_by_activity($qubaid);
        $qa = $quba->get_question_attempt(count($quba->get_slots()));
        $order = $question->get_order($qa);
        $positive = false;
        $cntpositives = 0;

        foreach ($question->answer as $key => $answer) {
            if ($action === 'remove' && $key == $order[$optionnumber]) {
                if ($answer->fraction > 0) {
                    if ($question->single) {
                        throw new Exception('Korrekte Antwort kann nicht entfernt werden.');
                    }
                    $positive = true;
                }
                unset($question->answer[$key]);
                continue;
            }
            if ($answer->fraction > 0) {
                $cntpositives++;
            }
            $question->fraction[$key] = $answer->fraction;
            $question->feedback[$key] = ['text' => $answer->feedback, 'format' => $answer->feedbackformat];
            $aw = [
                'text' => $action === 'edit' && $optionnumber != -1 && $key == $order[$optionnumber]
                    ? $text : $answer->answer,
                'format' => $answer->answerformat,
            ];
            $aw->feedback = ['text' => $answer->feedback, 'format' => $answer->feedbackformat];
            $question->answer[$key] = $aw;

            if ($positive) {
                foreach ($question->fraction as $fkey => $fraction) {
                    $question->fraction[$fkey] = $fraction / $cntpositives;
                }
            }
        }

        if ($action === 'add') {
            $key = count($question->answer);
            $answers = implode(', ', array_map(function ($answer) {
                return "'" . $answer['text'] . "'";
            }, $question->answer));
            if ($useai) {
                $result = self::chat(
                    $text,
                    "Please write a new distractor in German language for the following question to the given "
                        . "text. Question: '" . $question->questiontext['text'] . "' The distractor should be "
                        . "different from the following answers: " . $answers . ". Give only the distractor text "
                        . "without any additional information."
                );
                $answertext = $result->message->content;
            } else {
                $answertext = 'Falsche Antwort';
            }
            $question->answer[$key] = ['text' => $answertext, 'format' => 1];
            $question->fraction[$key] = 0;
            $question->feedback[$key] = ['text' => '', 'format' => 1];
        } else if ($action === 'rephrase') {
            if ($optionnumber != -1) {
                $answers = implode(', ', array_map(function ($answer) {
                    return "'" . $answer['text'] . "'";
                }, $question->answer));
                $result = self::chat(
                    $text,
                    "Please rephrase the following answer for the following question in German language for the "
                        . "given text. Question: '" . $question->questiontext['text'] . "' Answer to rephrase: '"
                        . $question->answer[$order[$optionnumber]]['text'] . "' The rephrased answer should be "
                        . "different from the following answers: " . $answers . '. Give only the rephrased answer '
                        . 'text without any additional information. Keep it short.'
                );
                $question->answer[$order[$optionnumber]] = ['text' => $result->message->content, 'format' => 1];
            } else {
                $result = self::chat(
                    $text,
                    "Please rephrase the following question in German language for the given text with the "
                        . "following given answers. Question to rephrase: '" . $question->questiontext['text']
                        . "' Given answers: " . $answers . '. Give only the rephrased question text without any '
                        . 'additional information. Keep it short.'
                );
                $question->questiontext = ['text' => $result->message->content, 'format' => 1];
            }
        }

        $question->shuffleanswers = false;
        $created = \question_bank::get_qtype($question->qtype)->save_question($question, clone $question);
        if (\core_tag_tag::is_item_tagged_with('core_question', 'question', $questionid, 'neu')) {
            \core_tag_tag::add_item_tag('core_question', 'question', $created->id, $context, 'neu');
        }

        if ($created) {
            $category = $DB->get_record('question_categories', ['id' => $question->category]);
            $embedid = new embed_id($category->idnumber, $question->idnumber);
            $embedlocation = embed_location::make_for_test($context, $context->get_url(), 'Embed location');
            $options = new \filter_embedquestion\question_options();
            $options->set_from_request();
            $qa = new attempt($embedid, $embedlocation, $USER, $options);
            $qa->find_or_create_attempt();
            $qa->discard_broken_attempt();
            $qa->find_or_create_attempt();
            $qubaid = $qa->get_question_usage()->get_id();

            utility_services::log([
                'longpageid' => $longpageid,
                'courseid' => $course->id,
                'utc' => time(),
                'action' => 'question',
                'entry' => json_encode([
                    'type' => $action,
                    'questionid' => $created->id,
                    'qtype' => $question->qtype,
                    'useAI' => $useai ? 'true' : 'false',
                    'optionNumber' => $optionnumber,
                    'embedid' => $category->idnumber . '/' . $question->idnumber,
                    'elapsedTimeMs' => $now->diff(new DateTime())->f,
                    'longpageid' => $longpageid,
                ]),
            ]);

            return [
                'response' => json_encode(
                    ['questionid' => $created->id, 'qubaid' => $qubaid, 'text' => $text],
                    JSON_UNESCAPED_UNICODE
                ),
            ];
        }

        throw new Exception('Question not edited.');
    }

    /**
     * Parameters for edit_question.
     *
     * @return external_function_parameters
     */
    public static function edit_question_parameters() {
        return new external_function_parameters(
            [
                'longpageid' => new external_value(PARAM_INT, 'page instance id'),
                'questionid' => new external_value(PARAM_INT, 'question id'),
                'action' => new external_value(PARAM_TEXT, 'action'),
                'qubaid' => new external_value(PARAM_INT, 'qubaid'),
                'useAI' => new external_value(PARAM_BOOL, 'use AI, otherwise empty', VALUE_DEFAULT),
                'text' => new external_value(PARAM_RAW, 'question text', VALUE_DEFAULT),
                'optionNumber' => new external_value(PARAM_INT, 'option number', VALUE_DEFAULT),
            ]
        );
    }

    /**
     * Returns for edit_question.
     *
     * @return external_single_structure
     */
    public static function edit_question_returns() {
        return new external_single_structure(
            ['response' => new external_value(PARAM_RAW, 'Server response to edit_question')]
        );
    }

    /**
     * Export questions in a given format.
     *
     * @param string $format
     * @return void
     */
    public static function export_questions($format) {
        global $DB, $USER, $CFG, $PAGE;

        self::validate_parameters(
            self::export_questions_parameters(),
            [
                'format' => $format,
            ]
        );

        $questions = $DB->get_records('question', ['createdby' => $USER->id]);

        require_once($CFG->dirroot . '/question/format/xml/format.php');
        require_once($CFG->dirroot . '/question/format/gift/format.php');
        require_once($CFG->dirroot . '/question/format/aiken/format.php');
        require_once($CFG->dirroot . '/report/embedquestion/lib.php');

        $classname = 'qformat_' . $format;
        if (!class_exists($classname)) {
            throw new Exception('Format not found.');
        }

        $qformat = new $classname();
        $qformat->exportpreprocess();
        $PAGE = new \moodle_page();
        $expout = '';
        foreach ($questions as $question) {
            try {
                $qtype = $question->qtype;
                if ($qtype === 'multichoice') {
                    $question = \question_bank::load_question($question->id);
                    if (
                        count($question->answers) > 0 &&
                        is_latest($question->version, $question->questionbankentryid) &&
                        report_embedquestion_questions_in_use([$question->id])
                    ) {
                        $context = \context::instance_by_id($question->contextid);
                        $PAGE->set_context($context);
                        $course = $DB->get_record('course', ['id' => $context->instanceid]);
                        $qformat->setCourse($course);
                        $qformat->category = $question->category;
                        $question->qtype = $qtype;
                        get_question_options($question);
                        $question = json_decode(json_encode($question), false);
                        $expout .= $qformat->writequestion($question) . "\n";
                    }
                }
            } catch (Exception $e) {
                continue;
            }
        }

        send_file($expout, 'questions.txt', 0, 0, true, true, $qformat->mime_type());
    }

    /**
     * Parameters for export_questions.
     *
     * @return external_function_parameters
     */
    public static function export_questions_parameters() {
        return new external_function_parameters(
            [
                'format' => new external_value(PARAM_TEXT, 'format'),
            ]
        );
    }

    /**
     * Returns for export_questions.
     *
     * @return external_single_structure
     */
    public static function export_questions_returns() {
        return new external_single_structure(
            ['response' => new external_value(PARAM_RAW, 'Server response to export_questions')]
        );
    }

    /**
     * Autosave a quiz attempt.
     *
     * @param array $data
     * @return array
     */
    public static function autosave($data) {
        $form = json_decode($data['form'], true);
        $quba = \question_engine::load_questions_usage_by_activity($data['qubaid']);
        $quba->process_all_autosaves(null, $form);
        \question_engine::save_questions_usage_by_activity($quba);
        return ['response' => json_encode('success')];
    }

    /**
     * Process a question action (submit) for a given slot.
     *
     * @param int $qubaid QUBA ID
     * @param int $slot Question slot
     * @param string $form JSON-encoded form data
     * @return array
     */
    public static function process_question_action($qubaid, $slot, $form) {
        self::validate_parameters(
            self::process_question_action_parameters(),
            [
                'qubaid' => $qubaid,
                'slot' => $slot,
                'form' => $form,
            ]
        );

        $payload = json_decode($form, true);
        if (!is_array($payload)) {
            $payload = [];
        }

        $quba = \question_engine::load_questions_usage_by_activity($qubaid);
        $quba->process_action($slot, $payload);
        \question_engine::save_questions_usage_by_activity($quba);

        $options = new \question_display_options();
        $options->correctness = \question_display_options::VISIBLE;
        $options->feedback = \question_display_options::VISIBLE;
        $options->marks = \question_display_options::MARK_AND_MAX;

        $html = $quba->render_question($slot, $options);
        $qa = $quba->get_question_attempt($slot);

        return [
            'html' => $html,
            'state' => $qa->get_state()->get_state_class(),
        ];
    }

    /**
     * Parameters for process_question_action.
     *
     * @return external_function_parameters
     */
    public static function process_question_action_parameters() {
        return new external_function_parameters(
            [
                'qubaid' => new external_value(PARAM_INT, 'qubaid'),
                'slot' => new external_value(PARAM_INT, 'question slot'),
                'form' => new external_value(PARAM_RAW, 'form data'),
            ]
        );
    }

    /**
     * Returns for process_question_action.
     *
     * @return external_single_structure
     */
    public static function process_question_action_returns() {
        return new external_single_structure(
            [
                'html' => new external_value(PARAM_RAW, 'rendered question html'),
                'state' => new external_value(PARAM_TEXT, 'question state'),
            ]
        );
    }

    /**
     * Parameters for autosave.
     *
     * @return external_function_parameters
     */
    public static function autosave_parameters() {
        return new external_function_parameters(
            [
                'data' => new external_single_structure(
                    [
                        'qubaid' => new external_value(PARAM_INT, 'qubaid'),
                        'form' => new external_value(PARAM_RAW, 'form data'),
                    ]
                ),
            ]
        );
    }

    /**
     * Returns for autosave.
     *
     * @return external_single_structure
     */
    public static function autosave_returns() {
        return new external_single_structure(
            ['response' => new external_value(PARAM_RAW, 'Server response to autosave')]
        );
    }

    /**
     * Save a question instance.
     *
     * @param object $q
     * @param object $category
     * @param string $qtype
     * @return object
     */
    protected static function save_question($q, $category, $qtype) {
        global $USER;

        $q->questiontext = ['text' => '<p>' . $q->questiontext . '</p>'];
        $q->category = $category->id;
        $q->createdby = $USER->id;
        $q->modifiedby = $USER->id;
        $q->timecreated = time();
        $q->timemodified = time();
        $q->questiontextformat = 1;
        $q->shownumcorrect = 1;

        return \question_bank::get_qtype($qtype)->save_question($q, clone $q);
    }
}
