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
 * Post interaction external API services
 *
 * @package    mod_longpage
 * @category   external
 * @copyright  2020 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */

namespace mod_longpage\external;

defined('MOODLE_INTERNAL') || die;

use mod_longpage\local\post_recommendation\post_recommendation_calculation_task;

require_once("$CFG->libdir/externallib.php");

/**
 * Post interaction external functions
 *
 * @package    mod_longpage
 * @category   external
 * @copyright  2020 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */
class post_interaction_services extends base_external {
    /**
     * Create post like.
     *
     * @param int $postid Post ID
     */
    public static function create_post_like($postid) {
        self::validate_post_write($postid);

        self::create_post_reaction('longpage_post_likes', $postid);
    }

    /**
     * Parameters for create_post_like.
     *
     * @return external_function_parameters
     */
    public static function create_post_like_parameters() {
        return new \external_function_parameters([
            'postid' => new \external_value(PARAM_INT),
        ]);
    }

    /**
     * Return structure for create_post_like.
     *
     * @return null
     */
    public static function create_post_like_returns() {
        return null;
    }

    /**
     * Create post bookmark.
     *
     * @param int $postid Post ID
     */
    public static function create_post_bookmark($postid) {
        self::validate_post_write($postid);

        self::create_post_reaction('longpage_post_bookmarks', $postid);
    }

    /**
     * Parameters for create_post_bookmark.
     *
     * @return external_function_parameters
     */
    public static function create_post_bookmark_parameters() {
        return self::create_post_like_parameters();
    }

    /**
     * Return structure for create_post_bookmark.
     *
     * @return null
     */
    public static function create_post_bookmark_returns() {
        return null;
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
        return self::create_post_like_parameters();
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
     * Delete post like.
     *
     * @param int $postid Post ID
     */
    public static function delete_post_like($postid) {
        global $DB, $USER;

        self::validate_post_write($postid);

        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records('longpage_post_likes', ['postid' => $postid, 'userid' => $USER->id]);
        $transaction->allow_commit();

        self::schedule_post_recommendation_calculation_task_for_page_with_post($postid);
    }

    /**
     * Parameters for delete_post_like.
     *
     * @return external_function_parameters
     */
    public static function delete_post_like_parameters() {
        return self::create_post_like_parameters();
    }

    /**
     * Return structure for delete_post_like.
     *
     * @return null
     */
    public static function delete_post_like_returns() {
        return null;
    }

    /**
     * Delete post bookmark.
     *
     * @param int $postid Post ID
     */
    public static function delete_post_bookmark($postid) {
        global $DB, $USER;

        self::validate_post_write($postid);

        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records('longpage_post_bookmarks', ['postid' => $postid, 'userid' => $USER->id]);
        $transaction->allow_commit();

        self::schedule_post_recommendation_calculation_task_for_page_with_post($postid);
    }

    /**
     * Parameters for delete_post_bookmark.
     *
     * @return external_function_parameters
     */
    public static function delete_post_bookmark_parameters() {
        return self::create_post_like_parameters();
    }

    /**
     * Return structure for delete_post_bookmark.
     *
     * @return null
     */
    public static function delete_post_bookmark_returns() {
        return null;
    }

    /**
     * Delete post reading.
     *
     * @param int $postid Post ID
     */
    public static function delete_post_reading($postid) {
        global $DB, $USER;

        self::validate_post_write($postid);

        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records('longpage_post_readings', ['postid' => $postid, 'userid' => $USER->id]);
        $transaction->allow_commit();

        self::schedule_post_recommendation_calculation_task_for_page_with_post($postid);
    }

    /**
     * Parameters for delete_post_reading.
     *
     * @return external_function_parameters
     */
    public static function delete_post_reading_parameters() {
        return self::create_post_like_parameters();
    }

    /**
     * Return structure for delete_post_reading.
     *
     * @return null
     */
    public static function delete_post_reading_returns() {
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
     * Return structure for reactions to post.
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
}
