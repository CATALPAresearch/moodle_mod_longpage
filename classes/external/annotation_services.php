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
 * Annotation external API services
 *
 * @package    mod_longpage
 * @category   external
 * @copyright  2020 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */

namespace mod_longpage\external;

defined('MOODLE_INTERNAL') || die;

use mod_longpage\local\constants\annotation_type;
use mod_longpage\local\constants\selector;
use mod_longpage\local\post_recommendation\post_recommendation_calculation_task;
use mod_longpage\local\thread_subscriptions\manage_thread_subscriptions_task;
use mod_longpage\local\thread_subscriptions\post_action;

global $CFG;
require_once("$CFG->libdir/externallib.php");
require_once("$CFG->dirroot/mod/longpage/locallib.php");

/**
 * Annotation external API services
 *
 * @package    mod_longpage
 * @category   external
 * @copyright  2020 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */
class annotation_services extends base_external {
    /**
     * Base parameters for annotation target.
     *
     * @return array
     */
    private static function annotation_target_parameters_base() {
        return [
            'selectors' => new \external_multiple_structure(
                new \external_single_structure([
                    'type' => new \external_value(PARAM_INT),
                    'startposition' => new \external_value(PARAM_INT, 'Start position', VALUE_OPTIONAL),
                    'startcontainer' => new \external_value(PARAM_TEXT, 'Start container element', VALUE_OPTIONAL),
                    'startoffset' => new \external_value(PARAM_INT, 'Start offset within container', VALUE_OPTIONAL),
                    'endposition' => new \external_value(PARAM_INT, 'End position', VALUE_OPTIONAL),
                    'endcontainer' => new \external_value(PARAM_TEXT, 'End container element', VALUE_OPTIONAL),
                    'endoffset' => new \external_value(PARAM_INT, 'End offset within container', VALUE_OPTIONAL),
                    'exact' => new \external_value(PARAM_TEXT, 'Exact text content', VALUE_OPTIONAL),
                    'prefix' => new \external_value(PARAM_TEXT, 'Text prefix before selection', VALUE_OPTIONAL),
                    'suffix' => new \external_value(PARAM_TEXT, 'Text suffix after selection', VALUE_OPTIONAL),
                ]),
                '',
                VALUE_OPTIONAL
            ),
            'styleclass' => new \external_value(PARAM_TEXT, 'CSS style class', VALUE_OPTIONAL),
        ];
    }

    /**
     * Create annotation.
     *
     * @param array $annotation Annotation data
     * @return array
     */
    public static function create_annotation($annotation) {
        global $DB, $USER;

        self::validate_parameters(self::create_annotation_parameters(), ['annotation' => $annotation]);
        $context = self::validate_cm_context($annotation['longpageid']);
        require_capability('mod/longpage:addpost', $context);

        $transaction = $DB->start_delegated_transaction();
        $id = $DB->insert_record('longpage_annotations', array_merge(
            pick_keys($annotation, ['longpageid', 'type']),
            [
                'timecreated' => time(),
                'timemodified' => time(),
                'creatorid' => $USER->id,
                'ispublic' => isset($annotation['ispublic']) && $annotation['ispublic'],
            ]
        ));
        self::create_annotation_target($annotation['target'], $id);
        if (isset($annotation['body'])) {
            self::create_thread($annotation['body'], $id, $annotation['longpageid']);
        }
        $transaction->allow_commit();

        return [
            'annotation' => self::get_annotations([
                'longpageid' => $annotation['longpageid'],
                'annotationid' => $id,
            ])['annotations'][0],
        ];
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.0
     */
    public static function create_annotation_parameters() {
        return new \external_function_parameters([
            'annotation' => new \external_single_structure([
                'longpageid' => new \external_value(PARAM_INT),
                'target' => self::create_annotation_target_parameters(),
                'type' => new \external_value(PARAM_INT),
                'body' => new \external_single_structure(self::create_thread_parameters_base(), '', VALUE_OPTIONAL),
                'ispublic' => new \external_value(PARAM_BOOL, 'Whether the item is public', VALUE_DEFAULT),
            ]),
        ]);
    }

    /**
     * Returns description of method parameters
     *
     * @return external_single_structure
     * @since Moodle 3.0
     */
    public static function create_annotation_returns() {
        return new \external_single_structure(['annotation' => self::get_annotation_returns()]);
    }

    /**
     * Create annotation target.
     *
     * @param array $target Target data
     * @param int $annotationid Annotation ID
     */
    private static function create_annotation_target($target, $annotationid) {
        global $DB;

        $targetid = $DB->insert_record(
            'longpage_annotation_targets',
            ['annotationid' => $annotationid, 'styleclass' => $target['styleclass'] ?? null]
        );
        self::create_selectors($target['selectors'], $targetid);
    }

    /**
     * Parameters for create_annotation_target.
     *
     * @return external_single_structure
     */
    public static function create_annotation_target_parameters() {
        return new \external_single_structure(self::annotation_target_parameters_base());
    }

    /**
     * Create selectors.
     *
     * @param array $selectors Selectors data
     * @param int $annotationtargetid Annotation target ID
     */
    private static function create_selectors($selectors, $annotationtargetid): void {
        global $DB;

        foreach ($selectors as $selector) {
            $selectorid = $DB->insert_record(
                'longpage_selectors',
                ['annotationtargetid' => $annotationtargetid, 'type' => $selector['type']]
            );
            $DB->insert_record(
                selector::map_type_to_table_name($selector['type']),
                array_merge(omit_keys($selector, ['type']), ['selectorid' => $selectorid])
            );
        }
    }

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
     * Create thread subscription.
     *
     * @param int $threadid Thread ID
     */
    public static function create_thread_subscription($threadid) {
        global $DB, $USER;

        self::validate_parameters(self::create_thread_subscription_parameters(), ['threadid' => $threadid]);
        $annotation = self::get_annotation_by_thread($threadid);
        self::validate_cm_context($annotation->longpageid);

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
     * Delete annotation.
     *
     * @param int $id Annotation ID
     */
    public static function delete_annotation($id): void {
        global $DB, $USER;

        self::validate_parameters(self::delete_annotation_parameters(), ['id' => $id]);
        $annotation = $DB->get_record('longpage_annotations', ['id' => $id]);
        $context = self::validate_cm_context($annotation->longpageid);

        // Check permission: user must be the creator OR have modannotations capability.
        if ($annotation->creatorid != $USER->id && !has_capability('mod/longpage:modannotations', $context)) {
            throw new \moodle_exception('nopermissions', 'error', '', 'delete annotation');
        }

        // Validate that user can delete annotation and that annotation can be deleted.
        // (not part of a thread that others depend on), can be merged with validation of highlight & post.

        $transaction = $DB->start_delegated_transaction();
        self::delete_annotation_target($id);
        if ($annotation->type == annotation_type::POST) {
            $thread = $DB->get_record('longpage_threads', ['annotationid' => $annotation->id]);
            self::delete_thread($thread, $annotation->longpageid);
        }
        $DB->delete_records('longpage_annotations', ['id' => $id]);
        $transaction->allow_commit();
    }

    /**
     * Parameters for delete_annotation.
     *
     * @return external_function_parameters
     */
    public static function delete_annotation_parameters() {
        return new \external_function_parameters([
            'id' => new \external_value(PARAM_INT),
        ]);
    }

    /**
     * Return structure for delete_annotation.
     *
     * @return null
     */
    public static function delete_annotation_returns() {
        return null;
    }

    /**
     * Delete annotation target.
     *
     * @param int $annotationid Annotation ID
     */
    private static function delete_annotation_target($annotationid): void {
        global $DB;

        $conditions = ['annotationid' => $annotationid];
        $target = $DB->get_record('longpage_annotation_targets', $conditions);
        self::delete_selectors($target->id);
        $DB->delete_records('longpage_annotation_targets', $conditions);
    }

    /**
     * Delete selectors.
     *
     * @param int $annotationtargetid Annotation target ID
     */
    private static function delete_selectors($annotationtargetid) {
        global $DB;

        $conditions = ['annotationtargetid' => $annotationtargetid];
        $pageselectors = $DB->get_records('longpage_selectors', $conditions);
        foreach ($pageselectors as $pageselector) {
            $tablename = selector::map_type_to_table_name($pageselector->type);
            $DB->delete_records($tablename, ['selectorid' => $pageselector->id]);
        }
        $DB->delete_records('longpage_selectors', $conditions);
    }

    /**
     * Get annotation returns.
     *
     * @return external_single_structure
     */
    private static function get_annotation_returns() {
        return new \external_single_structure(array_merge(
            [
                'id' => new \external_value(PARAM_INT),
                'body' => self::get_thread_returns(),
                'creatorid' => new \external_value(PARAM_INT),
                'ispublic' => new \external_value(PARAM_INT),
                'longpageid' => new \external_value(PARAM_INT),
                'target' => self::get_annotation_target_parameters(),
                'type' => new \external_value(PARAM_INT),
            ],
            self::timestamp_parameters()
        ));
    }

    /**
     * Get annotation target.
     *
     * @param int $annotationid Annotation ID
     * @return array
     */
    private static function get_annotation_target($annotationid) {
        global $DB;

        $target = $DB->get_record('longpage_annotation_targets', ['annotationid' => $annotationid]);
        $target->selectors = self::get_selectors($target->id);

        return omit_keys($target, ['annotationid']);
    }

    /**
     * Parameters for get_annotation_target.
     *
     * @return external_single_structure
     */
    public static function get_annotation_target_parameters() {
        return new \external_single_structure(array_merge(
            ['id' => new \external_value(PARAM_INT)],
            self::annotation_target_parameters_base()
        ));
    }

    /**
     * Get annotations.
     *
     * @param array $parameters Query parameters
     * @return array
     */
    public static function get_annotations($parameters) {
        self::validate_parameters(self::get_annotations_parameters(), ['parameters' => $parameters]);
        $context = self::validate_cm_context($parameters['longpageid']);
        require_capability('mod/longpage:view', $context);

        $annotations =
            isset($parameters['annotationid']) ?
                self::get_annotations_by_annotation_id($parameters['annotationid']) :
                self::get_annotations_by_page_id($parameters['longpageid']);

        foreach ($annotations as $annotation) {
            $annotation->target = self::get_annotation_target($annotation->id);
            if ($annotation->type == annotation_type::POST) {
                $annotation->body = self::get_thread($annotation->id);
            }
        }

        return ['annotations' => array_values($annotations)];
    }

    /**
     * Get annotations by annotation ID.
     *
     * @param int $annotationid Annotation ID
     * @param int $timemodified Time modified
     * @return array
     */
    private static function get_annotations_by_annotation_id($annotationid, $timemodified = 0) {
        global $DB, $USER;

        return $DB->get_records_select(
            'longpage_annotations',
            'id = ? AND (creatorid = ? OR ispublic = 1)',
            ['id' => $annotationid, 'creatorid' => $USER->id]
        );
    }

    /**
     * Get annotations by page ID.
     *
     * @param int $pageid Page ID
     * @param int $timemodified Time modified
     * @return array
     */
    private static function get_annotations_by_page_id($pageid, $timemodified = 0) {
        global $DB, $USER;

        return $DB->get_records_select(
            'longpage_annotations',
            'longpageid = ? AND (creatorid = ? OR ispublic = 1)',
            ['longpageid' => $pageid, 'creatorid' => $USER->id]
        );
    }

    /**
     * Describes the parameters for get_annotations.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function get_annotations_parameters() {
        return new \external_function_parameters([
            'parameters' => new \external_single_structure([
                'longpageid' => new \external_value(PARAM_INT),
                'annotationid' => new \external_value(PARAM_INT, 'ID of the annotation', VALUE_OPTIONAL),
            ]),
        ]);
    }

    /**
     * Returns description of method result value
     *
     * @return external_function_parameters
     * @since Moodle 3.0
     */
    public static function get_annotations_returns() {
        return new \external_function_parameters([
            'annotations' => new \external_multiple_structure(self::get_annotation_returns()),
        ]);
    }

    /**
     * Get selectors.
     *
     * @param int $annotationtargetid Annotation target ID
     * @return array
     */
    private static function get_selectors($annotationtargetid) {
        global $DB;

        $selectors = $DB->get_records('longpage_selectors', ['annotationtargetid' => $annotationtargetid]);
        return array_values(array_map(function ($selector) use ($DB) {
            $result = $DB->get_record(
                selector::map_type_to_table_name($selector->type),
                ['selectorid' => $selector->id]
            );
            $result->type = $selector->type;
            return omit_keys($result, ['id', 'selectorid']);
        }, $selectors));
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
     * Get post returns.
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
     * Check if user can modify annotations.
     *
     * @param int $longpageid Page ID
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

        $context = \context_module::instance($cm->id);
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
