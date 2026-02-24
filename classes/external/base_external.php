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
 * Base external API class with shared utility methods
 *
 * @package    mod_longpage
 * @category   external
 * @copyright  2024 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_longpage\external;

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once("$CFG->libdir/externallib.php");
require_once("$CFG->dirroot/mod/longpage/locallib.php");

/**
 * Base class with shared utility methods for external API
 *
 * @package    mod_longpage
 * @category   external
 * @copyright  2024 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base_external extends \external_api {
    /**
     * Validate course module context by page ID.
     *
     * @param int $pageid Page ID
     * @return \context_module
     */
    protected static function validate_cm_context($pageid) {
        $context = self::get_cm_context_by_pageid($pageid);
        self::validate_context($context);
        return $context;
    }

    /**
     * Get course module context by page ID.
     *
     * @param int $pageid Page ID
     * @return \context_module
     */
    protected static function get_cm_context_by_pageid($pageid) {
        $cm = get_coursemodule_by_pageid($pageid);
        return \context_module::instance($cm->id);
    }

    /**
     * Validate post write access.
     *
     * @param int $postid Post ID
     */
    protected static function validate_post_write($postid): void {
        $params = self::validate_parameters(
            new \external_function_parameters(['postid' => new \external_value(PARAM_INT)]),
            ['postid' => $postid]
        );
        $annotation = self::get_annotation_by_post_id($params['postid']);
        $context = self::get_cm_context_by_pageid($annotation->longpageid);
        self::validate_context($context);
        require_capability('mod/longpage:view', $context);
    }

    /**
     * Get annotation by post ID.
     *
     * @param int $id Post ID
     * @return object
     */
    protected static function get_annotation_by_post_id($id) {
        global $DB;

        $post = $DB->get_record('longpage_posts', ['id' => $id]);
        return self::get_annotation_by_thread_id($post->threadid);
    }

    /**
     * Get annotation by thread ID.
     *
     * @param int $threadid Thread ID
     * @return object
     */
    protected static function get_annotation_by_thread_id($threadid) {
        global $DB;

        $thread = $DB->get_record('longpage_threads', ['id' => $threadid]);
        return $DB->get_record('longpage_annotations', ['id' => $thread->annotationid]);
    }

    /**
     * Get annotation by thread object.
     *
     * @param int $threadid Thread ID
     * @return object
     */
    protected static function get_annotation_by_thread($threadid) {
        global $DB;

        $thread = $DB->get_record('longpage_threads', ['id' => $threadid], '*', MUST_EXIST);
        $annotationid = $thread->annotationid;
        return $DB->get_record('longpage_annotations', ['id' => $annotationid], '*', MUST_EXIST);
    }

    /**
     * Update annotation by thread.
     *
     * @param int $threadid Thread ID
     * @param bool $ispublic Is public flag
     */
    protected static function update_annotation_by_thread($threadid, $ispublic = false): void {
        global $DB;

        $annotation = self::get_annotation_by_thread_id($threadid);
        $annotation->timemodified = time();
        $annotation->ispublic = $DB->record_exists('longpage_posts', ['threadid' => $threadid, 'ispublic' => true])
            || $ispublic;
        $DB->update_record('longpage_annotations', $annotation);
    }

    /**
     * Anonymize post if anonymous and not the creator.
     *
     * @param object $post The post object
     */
    protected static function anonymize_post($post): void {
        global $USER;

        if ($post->anonymous && $USER->id !== $post->creatorid) {
            unset($post->creatorid);
        }
    }

    /**
     * Add recommendations to post.
     *
     * @param object $post Post object
     */
    protected static function add_recommendations_to_post($post): void {
        global $DB, $USER;

        if ($post->readbyuser) {
            return;
        }

        $post->recommendation = $DB->get_field(
            'longpage_post_recomends',
            'value',
            ['postid' => $post->id, 'userid' => $USER->id]
        ) ?: $DB->get_field('longpage', 'avgpostpreference', ['id' => $post->longpageid]);
    }

    /**
     * Add reactions to post.
     *
     * @param object $post Post object
     */
    protected static function add_reactions_to_post($post): void {
        global $DB, $USER;

        $post->likedbyuser = $DB->record_exists('longpage_post_likes', ['postid' => $post->id, 'userid' => $USER->id]);
        $post->bookmarkedbyuser = $DB->record_exists('longpage_post_bookmarks', ['postid' => $post->id, 'userid' => $USER->id]);
        $post->readbyuser = $DB->record_exists('longpage_post_readings', ['postid' => $post->id, 'userid' => $USER->id]);
        $post->likecount = $DB->count_records('longpage_post_likes', ['postid' => $post->id]);
    }

    /**
     * Schedule post recommendation calculation task for page with post.
     *
     * @param int $postid Post ID
     */
    protected static function schedule_post_recommendation_calculation_task_for_page_with_post($postid): void {
        global $DB;

        $post = $DB->get_record('longpage_posts', ['id' => $postid]);
        \mod_longpage\local\post_recommendation\post_recommendation_calculation_task::create_from_pageid_and_queue($post->longpageid);
    }

    /**
     * Common parameter: ID.
     *
     * @return array
     */
    protected static function id_parameter() {
        return ['id' => new \external_value(PARAM_INT)];
    }

    /**
     * Common parameters: Post.
     *
     * @return array
     */
    protected static function post_parameters() {
        return [
            'threadid' => new \external_value(PARAM_INT),
            'content' => new \external_value(PARAM_TEXT),
            'anonymous' => new \external_value(PARAM_BOOL),
            'ispublic' => new \external_value(PARAM_BOOL, 'Whether the item is public', VALUE_OPTIONAL),
            'creatorid' => new \external_value(PARAM_INT, 'ID of the creator user', VALUE_OPTIONAL),
        ];
    }

    /**
     * Common parameters: Timestamp.
     *
     * @return array
     */
    protected static function timestamp_parameters() {
        return [
            'timecreated' => new \external_value(PARAM_INT),
            'timemodified' => new \external_value(PARAM_INT),
        ];
    }
}
