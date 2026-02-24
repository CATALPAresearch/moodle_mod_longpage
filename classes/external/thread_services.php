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
 * Thread services external API
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
 * Thread services external functions
 *
 * @package    mod_longpage
 * @category   external
 * @copyright  2020 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class thread_services extends base_external {
    /**
     * Create thread.
     *
     * @param array $threadparameters Thread parameters
     * @param int $annotationid Annotation ID
     * @param int $pageid Page ID
     */
    public static function create_thread($threadparameters, $annotationid, $pageid) {
        global $DB, $USER;

        $id = $DB->insert_record(
            'longpage_threads',
            [
                'annotationid' => $annotationid,
                'creatorid' => $USER->id,
                'replyrequested' => isset($threadparameters['replyrequested']) && $threadparameters['replyrequested'],
            ]
        );
        $postparameters = omit_keys($threadparameters, ['replyrequested']);
        $postparameters['longpageid'] = $pageid;
        $postparameters['threadid'] = $id;
        self::create_post($postparameters);
        self::create_thread_subscription($id);
    }

    /**
     * Parameters for create_thread.
     *
     * @return void
     */
    public static function create_thread_parameters() {
    }

    /**
     * Base parameters for create_thread.
     *
     * @return array
     */
    private static function create_thread_parameters_base() {
        return [
            'anonymous' => new \external_value(PARAM_BOOL),
            'content' => new \external_value(PARAM_TEXT, 'Content text'),
            'ispublic' => new \external_value(PARAM_BOOL, 'Whether the item is public', VALUE_OPTIONAL),
            'replyrequested' => new \external_value(PARAM_BOOL, 'Whether a reply was requested', VALUE_OPTIONAL),
        ];
    }

    /**
     * Return structure for create_thread.
     *
     * @return void
     */
    public static function create_thread_returns() {
    }

    /**
     * Create thread subscription.
     *
     * @param int $threadid Thread ID
     */
    public static function create_thread_subscription($threadid) {
        global $DB, $USER;

        self::validate_parameters(self::create_thread_subscription_parameters(), ['threadid' => $threadid]);
        $annotation = self::get_annotation_by_thread($threadid);
        $context = self::validate_cm_context($annotation->longpageid);
        require_capability('mod/longpage:view', $context);

        $table = 'longpage_thread_subs';
        $keyconditions = ['threadid' => $threadid, 'userid' => $USER->id];
        $transaction = $DB->start_delegated_transaction();
        if (!$DB->record_exists($table, $keyconditions)) {
            $DB->insert_record($table, array_merge($keyconditions, ['timecreated' => time()]));
        }
        $transaction->allow_commit();

        post_recommendation_calculation_task::create_from_pageid_and_queue($annotation->longpageid);
    }

    /**
     * Parameters for create_thread_subscription.
     *
     * @return external_function_parameters
     */
    public static function create_thread_subscription_parameters() {
        return new \external_function_parameters([
            'threadid' => new \external_value(PARAM_INT),
        ]);
    }

    /**
     * Return structure for create_thread_subscription.
     *
     * @return null
     */
    public static function create_thread_subscription_returns() {
        return null;
    }

    /**
     * Delete thread.
     *
     * @param object $thread Thread object
     * @param int $pageid Page ID
     */
    private static function delete_thread($thread, $pageid) {
        global $DB;

        self::delete_post_from_db(self::get_posts($thread->id)[0]->id);
        $DB->delete_records('longpage_thread_subs', ['threadid' => $thread->id]);
        $DB->delete_records('longpage_threads', ['id' => $thread->id]);

        post_recommendation_calculation_task::create_from_pageid_and_queue($pageid);
    }

    /**
     * Delete thread subscription.
     *
     * @param int $threadid Thread ID
     */
    public static function delete_thread_subscription($threadid) {
        global $DB, $USER;

        self::validate_parameters(self::create_thread_subscription_parameters(), ['threadid' => $threadid]);
        $annotation = self::get_annotation_by_thread($threadid);
        $context = self::validate_cm_context($annotation->longpageid);
        require_capability('mod/longpage:view', $context);

        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records('longpage_thread_subs', ['threadid' => $threadid, 'userid' => $USER->id]);
        $transaction->allow_commit();
    }

    /**
     * Parameters for delete_thread_subscription.
     *
     * @return external_function_parameters
     */
    public static function delete_thread_subscription_parameters() {
        return self::create_thread_subscription_parameters();
    }

    /**
     * Return structure for delete_thread_subscription.
     *
     * @return null
     */
    public static function delete_thread_subscription_returns() {
        return null;
    }

    /**
     * Get thread.
     *
     * @param int $annotationid Annotation ID
     * @return object
     */
    public static function get_thread($annotationid) {
        global $DB, $USER;

        $thread = $DB->get_record('longpage_threads', ['annotationid' => $annotationid]);
        $thread->posts = self::get_posts($thread->id);
        $thread->subscribedtobyuser =
            $DB->record_exists('longpage_thread_subs', ['threadid' => $thread->id, 'userid' => $USER->id]);

        return $thread;
    }

    /**
     * Return structure for get_thread.
     *
     * @return external_single_structure
     */
    public static function get_thread_returns() {
        return new \external_single_structure([
            'id' => new \external_value(PARAM_INT),
            'annotationid' => new \external_value(PARAM_INT),
            'posts' => new \external_multiple_structure(self::get_post_returns()),
            'replyid' => new \external_value(PARAM_INT, 'ID of the reply', VALUE_OPTIONAL),
            'replyrequested' => new \external_value(PARAM_BOOL),
            'subscribedtobyuser' => new \external_value(PARAM_BOOL),
        ], '', VALUE_OPTIONAL);
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
        self::validate_cm_context($postparameters->longpageid);

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
     * @param array $postids Optional array of post IDs to filter
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
}
