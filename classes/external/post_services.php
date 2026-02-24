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
 * Post services external API
 *
 * @package    mod_longpage
 * @category   external
 * @copyright  2020 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_longpage\external;

use mod_longpage\local\post_recommendation\post_recommendation_calculation_task;
use mod_longpage\local\thread_subscriptions\manage_thread_subscriptions_task;
use mod_longpage\local\thread_subscriptions\post_action;

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once("$CFG->libdir/externallib.php");
require_once("$CFG->dirroot/mod/longpage/locallib.php");

/**
 * Post services external functions
 *
 * @package    mod_longpage
 * @category   external
 * @copyright  2020 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class post_services extends base_external {
    /**
     * Delete post from database.
     *
     * @param int $id Post ID
     */
    private static function delete_post_from_db($id): void {
        global $DB, $USER;

        $post = $DB->get_record('longpage_posts', ['id' => $id]);
        $DB->delete_records('longpage_post_likes', ['postid' => $id]);
        $DB->delete_records('longpage_post_readings', ['postid' => $id]);
        $DB->delete_records('longpage_post_bookmarks', ['postid' => $id]);
        $DB->delete_records('longpage_posts', ['id' => $id]);

        self::update_annotation_by_thread($post->threadid);

        manage_thread_subscriptions_task::create_manage_thread_subscriptions_task(
            get_coursemodule_by_pageid($post->longpageid)->id,
            $id,
            $post->threadid,
            $USER->id,
            post_action::DELETE,
            $post->content
        );
    }

    /**
     * Check if post is thread root.
     *
     * @param object $post Post object
     * @return bool
     */
    private static function is_post_thread_root($post) {
        global $DB;

        $postsinthreadcount = $DB->count_records('longpage_posts', ['threadid' => $post->threadid]);
        return $postsinthreadcount === 1;
    }

    /**
     * Create post.
     *
     * @param array $postparameters Post parameters
     * @return array
     */
    public static function create_post($postparameters) {
        global $DB, $USER;

        self::validate_parameters(self::create_post_parameters(), ['post' => $postparameters]);
        $postparameters = (object) $postparameters;
        $context = self::validate_cm_context($postparameters->longpageid);
        require_capability('mod/longpage:addpost', $context);

        $transaction = $DB->start_delegated_transaction();
        $id = $DB->insert_record(
            'longpage_posts',
            object_merge($postparameters, ['creatorid' => $USER->id, 'timecreated' => time(), 'timemodified' => time()])
        );
        $transaction->allow_commit();

        self::update_annotation_by_thread($postparameters->threadid, $postparameters->ispublic);
        self::create_post_reading($id);
        post_recommendation_calculation_task::create_from_pageid_and_queue($postparameters->longpageid);

        $posts = self::get_posts($postparameters->threadid, [$id]);

        manage_thread_subscriptions_task::create_manage_thread_subscriptions_task(
            get_coursemodule_by_pageid($postparameters->longpageid)->id,
            $id,
            $postparameters->threadid,
            $USER->id,
            post_action::CREATE,
            $postparameters->content
        );

        return ['post' => array_shift($posts)];
    }

    /**
     * Parameters for create_post.
     *
     * @return external_function_parameters
     */
    public static function create_post_parameters() {
        return new \external_function_parameters([
            'post' => new \external_single_structure(
                array_merge(omit_keys(self::post_parameters(), ['creatorid']), ['longpageid' => new \external_value(PARAM_INT)])
            ),
        ]);
    }

    /**
     * Return structure for create_post.
     *
     * @return external_function_parameters
     */
    public static function create_post_returns() {
        return new \external_function_parameters([
            'post' => self::get_post_returns(),
        ]);
    }

    /**
     * Create post reading.
     *
     * @param int $postid Post ID
     */
    public static function create_post_reading($postid) {
        self::validate_post_write($postid);

        self::create_post_reaction('longpage_post_readings', $postid);
    }

    /**
     * Parameters for create_post_reading.
     *
     * @return external_function_parameters
     */
    public static function create_post_reading_parameters() {
        return new \external_function_parameters([
            'postid' => new \external_value(PARAM_INT),
        ]);
    }

    /**
     * Return structure for create_post_reading.
     *
     * @return null
     */
    public static function create_post_reading_returns() {
        return null;
    }

    /**
     * Create post reaction.
     *
     * @param string $table Table name
     * @param int $postid Post ID
     */
    private static function create_post_reaction($table, $postid) {
        global $DB, $USER;

        $post = $DB->get_record('longpage_posts', ['id' => $postid]);

        $keyconditions = ['postid' => $postid, 'userid' => $USER->id];
        $transaction = $DB->start_delegated_transaction();
        if (!$DB->record_exists($table, $keyconditions)) {
            $DB->insert_record($table, array_merge($keyconditions, ['timecreated' => time()]));
        }
        $transaction->allow_commit();

        post_recommendation_calculation_task::create_from_pageid_and_queue($post->longpageid);
    }

    /**
     * Delete post.
     *
     * @param int $id Post ID
     */
    public static function delete_post($id) {
        global $DB, $USER;

        self::validate_parameters(self::delete_post_parameters(), ['id' => $id]);
        $annotation = self::get_annotation_by_post_id($id);
        $context = self::validate_cm_context($annotation->longpageid);

        $post = $DB->get_record('longpage_posts', ['id' => $id]);

        // Check permission: user must be the creator OR have modannotations capability.
        if ($post->creatorid != $USER->id && !has_capability('mod/longpage:modannotations', $context)) {
            throw new \moodle_exception('nopermissions', 'error', '', 'delete post');
        }

        // Move getting the thread into validation.
        $postisthreadroot = self::is_post_thread_root($post);

        self::validate_post_can_be_deleted_and_udpated($post, $postisthreadroot);

        $transaction = $DB->start_delegated_transaction();
        self::delete_post_from_db($id);
        $transaction->allow_commit();

        post_recommendation_calculation_task::create_from_pageid_and_queue($post->longpageid);
    }

    /**
     * Parameters for delete_post.
     *
     * @return external_function_parameters
     */
    public static function delete_post_parameters() {
        return new \external_function_parameters(self::id_parameter());
    }

    /**
     * Return structure for delete_post.
     *
     * @return null
     */
    public static function delete_post_returns() {
        return null;
    }

    /**
     * Return structure for get_post.
     *
     * @return external_single_structure
     */
    private static function get_post_returns(): \external_single_structure {
        return new \external_single_structure(array_merge(
            [
                'id' => new \external_value(PARAM_INT),
                'creatorid' => new \external_value(PARAM_INT, 'ID of the creator user', VALUE_OPTIONAL),
                'recommendation' => new \external_value(PARAM_FLOAT, 'Recommendation score', VALUE_OPTIONAL),
            ],
            omit_keys(self::post_parameters(), ['creatorid']),
            self::get_reactions_to_post_returns(),
            self::timestamp_parameters()
        ));
    }

    /**
     * Return structure for get_reactions_to_post.
     *
     * @return array
     */
    private static function get_reactions_to_post_returns() {
        return [
            'likescount' => new \external_value(PARAM_INT),
            'likedbyuser' => new \external_value(PARAM_BOOL),
            'bookmarkedbyuser' => new \external_value(PARAM_BOOL),
            'readingscount' => new \external_value(PARAM_INT),
            'readbyuser' => new \external_value(PARAM_BOOL),
        ];
    }

    /**
     * Get posts.
     *
     * @param int $threadid Thread ID
     * @param array $postids Post IDs
     * @return array
     */
    private static function get_posts($threadid, $postids = []) {
        global $DB, $USER;

        $select = 'threadid = ? AND (ispublic = 1 OR creatorid = ?)';
        $params = [$threadid, $USER->id];
        if ($postids) {
            [$inpostidssql, $inpostidsparams] = $DB->get_in_or_equal($postids);
            $select .= " AND id $inpostidssql";
            $params = array_merge($params, $inpostidsparams);
        }

        $posts = $DB->get_records_select('longpage_posts', $select, $params, 'timecreated ASC');

        return array_map(function ($post) {
            self::anonymize_post($post);
            self::add_reactions_to_post($post);
            self::add_recommendations_to_post($post);
            return $post;
        }, array_values($posts));
    }

    /**
     * Add reactions to post.
     *
     * @param object $post Post object
     * @return object
     */
    protected static function add_reactions_to_post($post): void {
        global $DB, $USER;

        $post->likescount = $DB->count_records('longpage_post_likes', ['postid' => $post->id]);
        $post->likedbyuser = $DB->record_exists('longpage_post_likes', ['postid' => $post->id, 'userid' => $USER->id]);

        $post->readingscount = $DB->count_records('longpage_post_readings', ['postid' => $post->id]);
        $post->readbyuser = $DB->record_exists('longpage_post_readings', ['postid' => $post->id, 'userid' => $USER->id]);

        $post->bookmarkedbyuser =
            $DB->record_exists('longpage_post_bookmarks', ['postid' => $post->id, 'userid' => $USER->id]);
    }

    /**
     * Post parameters.
     *
     * @return array
     */
    protected static function post_parameters() {
        return [
            'threadid' => new \external_value(PARAM_INT),
            'creatorid' => new \external_value(PARAM_INT),
            'anonymous' => new \external_value(PARAM_BOOL),
            'content' => new \external_value(PARAM_TEXT, 'Content text'),
            'ispublic' => new \external_value(PARAM_BOOL, 'Whether the item is public', VALUE_OPTIONAL),
            'islocked' => new \external_value(PARAM_BOOL, 'Whether the item is locked', VALUE_OPTIONAL),
        ];
    }

    /**
     * Update post.
     *
     * @param array $postupdateparams Post update parameters
     * @return array
     */
    public static function update_post($postupdateparams) {
        global $DB, $USER;

        self::validate_parameters(self::update_post_parameters(), ['postupdate' => $postupdateparams]);
        $postupdate = (object) $postupdateparams;
        $post = $DB->get_record('longpage_posts', ['id' => $postupdate->id]);
        $context = self::validate_cm_context($post->longpageid);

        // Check permission: user must be the creator OR have modannotations capability.
        if ($post->creatorid != $USER->id && !has_capability('mod/longpage:modannotations', $context)) {
            throw new \moodle_exception('nopermissions', 'error', '', 'update post');
        }

        $transaction = $DB->start_delegated_transaction();
        self::validate_post_can_be_updated($post, $postupdate);
        $DB->update_record('longpage_posts', array_merge((array) $postupdate, ['timemodified' => time()]));
        if (isset($postupdate->content) && $post->content !== $postupdate->content) {
            $DB->delete_records('longpage_post_readings', ['postid' => $post->id]);
            $DB->insert_record('longpage_post_readings', ['postid' => $post->id, 'userid' => $USER->id, 'timecreated' => time()]);
        }
        self::update_annotation_by_thread($post->threadid, isset($postupdate->ispublic) ? $postupdate->ispublic : $post->ispublic);
        $transaction->allow_commit();

        post_recommendation_calculation_task::create_from_pageid_and_queue($post->longpageid);
        if (isset($postupdate->content) && $post->content !== $postupdate->content) {
            manage_thread_subscriptions_task::create_manage_thread_subscriptions_task(
                get_coursemodule_by_pageid($post->longpageid)->id,
                $post->id,
                $post->threadid,
                $USER->id,
                post_action::UPDATE,
                $postupdate->content,
                $post->content
            );
        }

        $posts = self::get_posts($post->threadid, [$postupdate->id]);
        return ['post' => array_shift($posts)];
    }

    /**
     * Parameters for update_post.
     *
     * @return external_function_parameters
     */
    public static function update_post_parameters() {
        return new \external_function_parameters([
            'postupdate' => new \external_single_structure(array_merge(
                self::id_parameter(),
                [
                    'content' => new \external_value(PARAM_TEXT, 'Content text', VALUE_OPTIONAL),
                    'anonymous' => new \external_value(PARAM_BOOL, 'Whether the post is anonymous', VALUE_OPTIONAL),
                    'ispublic' => new \external_value(PARAM_BOOL, 'Whether the item is public', VALUE_OPTIONAL),
                    'islocked' => new \external_value(PARAM_BOOL, 'Whether the item is locked', VALUE_OPTIONAL),
                    'markedasrequestedreply' => new \external_value(PARAM_BOOL, 'Whether marked as requested reply', VALUE_OPTIONAL),
                    'creatorid' => new \external_value(PARAM_INT, 'ID of the creator user', VALUE_OPTIONAL),
                ]
            )),
        ]);
    }

    /**
     * Return structure for update_post.
     *
     * @return external_function_parameters
     */
    public static function update_post_returns() {
        return self::create_post_returns();
    }

    /**
     * Validate post can be deleted and updated.
     *
     * @param object $post Post object
     * @param bool $postisthreadroot Whether post is thread root
     */
    private static function validate_post_can_be_deleted_and_udpated($post, $postisthreadroot) {
        global $DB;

        // Validate that post is not root of thread.
        // Handle markasrequestedreply validation.

        self::validate_post_not_referenced_by_other_post($post);
    }

    /**
     * Validate post can be updated.
     *
     * @param object $post Original post object
     * @param object $postupdate Post update object
     */
    private static function validate_post_can_be_updated($post, $postupdate) {
        global $DB;

        // Check if content or visibility is being changed.
        $contentChanged = isset($postupdate->content) && $post->content !== $postupdate->content;
        $visibilityChanged = isset($postupdate->ispublic) && $post->ispublic && !$postupdate->ispublic;

        if ($contentChanged || $visibilityChanged) {
            // Check if this is the thread root post (the first post in the thread).
            $rootpost = $DB->get_record_sql(
                'SELECT id FROM {longpage_posts} WHERE threadid = ? ORDER BY timecreated ASC LIMIT 1',
                [$post->threadid]
            );
            $postisthreadroot = ($rootpost && $rootpost->id == $post->id);
            self::validate_post_can_be_deleted_and_udpated($post, $postisthreadroot);
        }
    }

    /**
     * Validate post is not referenced by other post.
     *
     * @param object $post Post object
     */
    private static function validate_post_not_referenced_by_other_post($post) {
        global $DB;

        $islastpost = !($DB->record_exists_select(
            'longpage_posts',
            'threadid = ? AND timecreated > ?',
            ['threadid' => $post->threadid, 'timecreated' => $post->timecreated]
        ));
        if (!$islastpost) {
            throw new \invalid_parameter_exception('Only the last postIntern in a thread can be deleted/updated as postIntern ' .
                'could be referenced by other posts.');
        }
    }
}
