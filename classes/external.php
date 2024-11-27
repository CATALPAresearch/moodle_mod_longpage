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
 * Page external API
 *
 * @package    mod_longpage
 * @category   external
 * @copyright  2015 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */

defined('MOODLE_INTERNAL') || die;

use core_question\statistics\questions\calculator;
use filter_embedquestion\embed_id;
use filter_embedquestion\external;
use filter_embedquestion\embed_location;
use filter_embedquestion\attempt;
use filter_embedquestion\utils;
use mod_longpage\local\constants\annotation_type as annotation_type;
use mod_longpage\local\constants\selector as selector;
use mod_longpage\local\post_recommendation\post_recommendation_calculation_task as post_recommendation_calculation_task;
use mod_longpage\local\thread_subscriptions\manage_thread_subscriptions_task as manage_thread_subscriptions_task;
use mod_longpage\local\thread_subscriptions\post_action as post_action;
use mod_longpage\lib\longpage_update_grades as longpage_update_grades;


require_once("$CFG->libdir/accesslib.php");
require_once("$CFG->libdir/externallib.php");
require_once("$CFG->dirroot/course/externallib.php");
require_once("$CFG->dirroot/user/externallib.php");
require_once("$CFG->dirroot/mod/longpage/locallib.php");
require_once("$CFG->dirroot/question/engine/lib.php");
require_once("$CFG->libdir/gradelib.php");

/**
 * Page external functions
 *
 * @package    mod_longpage
 * @category   external
 * @copyright  2015 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */
class mod_longpage_external extends external_api
{
    private static function add_recommendations_to_post($post)
    {
        global $DB, $USER;

        if ($post->readbyuser) return;

        $post->recommendation = $DB->get_field('longpage_post_recomends', 'value', ['postid' => $post->id, 'userid' => $USER->id]) ?:
            $DB->get_field('longpage', 'avgpostpreference', ['id' => $post->longpageid]);
    }

    private static function annotation_target_parameters_base()
    {
        return [
            'selectors' => new external_multiple_structure(
                new external_single_structure([
                    'type' => new external_value(PARAM_INT),
                    'startposition' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
                    'startcontainer' => new external_value(PARAM_TEXT, '', VALUE_OPTIONAL),
                    'startoffset' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
                    'endposition' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
                    'endcontainer' => new external_value(PARAM_TEXT, '', VALUE_OPTIONAL),
                    'endoffset' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
                    'exact' => new external_value(PARAM_TEXT, '', VALUE_OPTIONAL),
                    'prefix' => new external_value(PARAM_TEXT, '', VALUE_OPTIONAL),
                    'suffix' => new external_value(PARAM_TEXT, '', VALUE_OPTIONAL)
                ]),
                '',
                VALUE_OPTIONAL
            ),
            'styleclass' => new external_value(PARAM_TEXT, '', VALUE_OPTIONAL)
        ];
    }

    /**
     * @param $post
     */
    private static function anonymize_post($post): void
    {
        global $USER;

        if ($post->anonymous && $USER->id !== $post->creatorid) {
            unset($post->creatorid);
        }
    }

    public static function create_annotation($annotation)
    {
        global $DB, $USER;

        self::validate_parameters(self::create_annotation_parameters(), ['annotation' => $annotation]);
        self::validate_cm_context($annotation['longpageid']);

        $transaction = $DB->start_delegated_transaction();
        $id = $DB->insert_record('longpage_annotations', array_merge(
            pick_keys($annotation, ['longpageid', 'type']),
            [
                'timecreated' => time(),
                'timemodified' => time(),
                'creatorid' => $USER->id,
                'ispublic' => isset($annotation['ispublic']) && $annotation['ispublic']
            ]
        ));
        self::create_annotation_target($annotation['target'], $id);
        if (isset($annotation['body'])) {
            self::create_thread($annotation['body'], $id, $annotation['longpageid']);
        }
        $transaction->allow_commit();

        $annotationscount = $DB->count_records('longpage_annotations', ['longpageid' => $annotation['longpageid'], 'creatorid' => $USER->id]);

        $grade = new stdClass();
        $grade->userid = $USER->id;
        $grade->rawgrade = min(100, $annotationscount*10);

        $page = $DB->get_record('longpage', array('id' => $annotation['longpageid']), '*', MUST_EXIST);
        longpage_update_grades($page, $grade);

        return [
            'annotation' => self::get_annotations(['longpageid' => $annotation['longpageid'], 'annotationid' => $id])['annotations'][0]
        ];
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.0
     */
    public static function create_annotation_parameters()
    {
        return new external_function_parameters([
            'annotation' => new external_single_structure([
                'longpageid' => new external_value(PARAM_INT),
                'target' => self::create_annotation_target_parameters(),
                'type' => new external_value(PARAM_INT),
                'body' => new external_single_structure(self::create_thread_parameters_base(), '', VALUE_OPTIONAL),
                'ispublic' => new external_value(PARAM_BOOL, '', VALUE_DEFAULT)
            ]),
        ]);
    }

    /**
     * Returns description of method parameters
     *
     * @return external_single_structure
     * @since Moodle 3.0
     */
    public static function create_annotation_returns()
    {
        return new external_single_structure(['annotation' => self::get_annotation_returns()]);
    }

    private static function create_annotation_target($target, $annotationid)
    {
        global $DB;

        $targetid = $DB->insert_record(
            'longpage_annotation_targets',
            ['annotationid' => $annotationid, 'styleclass' => $target['styleclass'] ?? null]
        );
        self::create_selectors($target['selectors'], $targetid);
    }

    public static function create_annotation_target_parameters()
    {
        return new external_single_structure(self::annotation_target_parameters_base());
    }

    /**
     * @param $post
     */
    private static function delete_post_from_db($id): void
    {
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

    private static function get_annotation_by_thread_id($threadid)
    {
        global $DB;

        $thread = $DB->get_record('longpage_threads', ['id' => $threadid]);
        return $DB->get_record('longpage_annotations', ['id' => $thread->annotationid]);
    }

    private static function get_annotation_by_post_id($id)
    {
        global $DB;

        $post = $DB->get_record('longpage_posts', ['id' => $id]);
        return self::get_annotation_by_thread_id($post->threadid);
    }

    public static function create_post($postparameters)
    {
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

    public static function create_post_like($postid)
    {
        self::validate_post_write($postid);

        self::create_post_reaction('longpage_post_likes', $postid);
    }

    private static function create_post_reaction($table, $postid)
    {
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

    public static function create_post_like_parameters()
    {
        return new external_function_parameters([
            'postid' => new external_value(PARAM_INT),
        ]);
    }

    public static function create_post_like_returns()
    {
        return null;
    }

    public static function create_post_bookmark($postid)
    {
        self::validate_post_write($postid);

        self::create_post_reaction('longpage_post_bookmarks', $postid);
    }

    public static function create_post_bookmark_parameters()
    {
        return self::create_post_like_parameters();
    }

    public static function create_post_bookmark_returns()
    {
        return null;
    }

    public static function create_post_parameters()
    {
        return new external_function_parameters([
            'post' => new external_single_structure(
                array_merge(omit_keys(self::post_parameters(), ['creatorid']), ['longpageid' => new external_value(PARAM_INT)])
            ),
        ]);
    }

    public static function create_post_reading($postid)
    {
        self::validate_post_write($postid);

        self::create_post_reaction('longpage_post_readings', $postid);
    }

    public static function create_post_reading_parameters()
    {
        return self::create_post_like_parameters();
    }

    public static function create_post_reading_returns()
    {
        return null;
    }

    public static function create_post_returns()
    {
        return new external_function_parameters([
            'post' => self::get_post_returns(),
        ]);
    }

    private static function create_selectors($selectors, $annotationtargetid): void
    {
        global $DB;

        foreach ($selectors as $selector) {
            $selectorid = $DB->insert_record('longpage_selectors', ['annotationtargetid' => $annotationtargetid, 'type' => $selector['type']]);
            $DB->insert_record(
                selector::map_type_to_table_name($selector['type']),
                array_merge(omit_keys($selector, ['type']), ['selectorid' => $selectorid])
            );
        }
    }

    public static function create_thread($threadparameters, $annotationid, $pageid)
    {
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

        if(isset($threadparameters['ispublic']) && $threadparameters['ispublic'])
        {
            $page = $DB->get_record('longpage', array('id' => $pageid), '*', MUST_EXIST);
            list($course, $cm) = get_course_and_cm_from_instance($page, 'longpage');
            $context = \context_course::instance($course->id);
            $role = $DB->get_record('role', array('shortname' => 'editingteacher'));
            $teachers = get_role_users($role->id, $context);
            foreach ($teachers as $teacher) {
                self::create_thread_subscription($id, $teacher->id);
            }
        }        
    }

    public static function create_thread_parameters()
    {
    }

    private static function create_thread_parameters_base()
    {
        return [
            'anonymous' => new external_value(PARAM_BOOL),
            'content' => new external_value(PARAM_TEXT, ''),
            'ispublic' => new external_value(PARAM_BOOL, '', VALUE_OPTIONAL),
            'replyrequested' => new external_value(PARAM_BOOL, '', VALUE_OPTIONAL),
        ];
    }

    public static function create_thread_returns()
    {
    }

    public static function create_thread_subscription($threadid, $userid=null)
    {
        global $DB, $USER;

        self::validate_parameters(self::create_thread_subscription_parameters(), ['threadid' => $threadid]);
        $annotation = self::get_annotation_by_thread($threadid);
        self::validate_cm_context($annotation->longpageid);

        if($userid == null)
        {
            $userid = $USER->id;
        }

        $table = 'longpage_thread_subs';
        $keyconditions = ['threadid' => $threadid, 'userid' => $userid];
        $transaction = $DB->start_delegated_transaction();
        if (!$DB->record_exists($table, $keyconditions)) {
            $DB->insert_record($table, array_merge($keyconditions, ['timecreated' => time()]));
        }
        $transaction->allow_commit();

        post_recommendation_calculation_task::create_from_pageid_and_queue($annotation->longpageid);
    }

    public static function create_thread_subscription_parameters()
    {
        return new external_function_parameters([
            'threadid' => new external_value(PARAM_INT),
        ]);
    }

    public static function create_thread_subscription_returns()
    {
        return null;
    }

    private static function delete_thread($thread, $pageid)
    {
        global $DB;

        self::delete_post_from_db(self::get_posts($thread->id)[0]->id);
        $DB->delete_records('longpage_thread_subs', ['threadid' => $thread->id]);
        $DB->delete_records('longpage_threads', ['id' => $thread->id]);

        post_recommendation_calculation_task::create_from_pageid_and_queue($pageid);
    }

    public static function delete_annotation($id): void
    {
        global $DB;

        self::validate_parameters(self::delete_annotation_parameters(), ['id' => $id]);
        $annotation = $DB->get_record('longpage_annotations', ['id' => $id]);
        self::validate_cm_context($annotation->longpageid);

        // TODO validate that user can delete annotation and that annotation can be deleted (not part of a thread that others depend on), can be merged with validation of highlight & post

        $transaction = $DB->start_delegated_transaction();
        self::delete_annotation_target($id);
        if ($annotation->type == annotation_type::POST) {
            $thread = $DB->get_record('longpage_threads', ['annotationid' => $annotation->id]);
            self::delete_thread($thread, $annotation->longpageid);
        }
        $DB->delete_records('longpage_annotations', ['id' => $id]);
        $transaction->allow_commit();
    }

    public static function delete_annotation_parameters()
    {
        return new external_function_parameters([
            'id' => new external_value(PARAM_INT),
        ]);
    }

    public static function delete_annotation_returns()
    {
        return null;
    }

    private static function delete_annotation_target($annotationid): void
    {
        global $DB;

        $conditions = ['annotationid' => $annotationid];
        $target = $DB->get_record('longpage_annotation_targets', $conditions);
        self::delete_selectors($target->id);
        $DB->delete_records('longpage_annotation_targets', $conditions);
    }

    public static function delete_highlight($id)
    {
        global $DB;




        self::delete_annotation($id);
    }

    public static function delete_highlight_parameters()
    {
        return new external_function_parameters([
            'id' => new external_value(PARAM_INT),
        ]);
    }

    public static function delete_highlight_returns()
    {
        return null;
    }

    /**
     * @param $DB
     * @param $threadid
     * @return mixed
     */
    private static function get_annotation_by_thread($threadid)
    {
        global $DB;

        $thread = $DB->get_record('longpage_threads', ['id' => $threadid]);
        return $DB->get_record('longpage_annotations', ['id' => $thread->annotationid]);
    }

    private static function is_post_thread_root($post)
    {
        global $DB;

        $postsinthreadcount = $DB->count_records('longpage_posts', ['threadid' => $post->threadid]);
        return $postsinthreadcount === 1;
    }

    public static function delete_post($id)
    {
        global $DB;

        self::validate_parameters(self::delete_post_parameters(), ['id' => $id]);
        $annotation = self::get_annotation_by_post_id($id);
        self::validate_cm_context($annotation->longpageid);

        $post = $DB->get_record('longpage_posts', ['id' => $id]);
        // TODO Move getting the thread into validation
        $postisthreadroot = self::is_post_thread_root($post);

        self::validate_post_can_be_deleted_and_udpated($post, $postisthreadroot);

        $transaction = $DB->start_delegated_transaction();
        self::delete_post_from_db($id);
        $transaction->allow_commit();

        post_recommendation_calculation_task::create_from_pageid_and_queue($post->longpageid);
    }

    public static function delete_post_like($postid)
    {
        global $DB, $USER;

        self::validate_post_write($postid);

        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records('longpage_post_likes', ['postid' => $postid, 'userid' => $USER->id]);
        $transaction->allow_commit();

        self::schedule_post_recommendation_calculation_task_for_page_with_post($postid);
    }

    public static function delete_post_like_parameters()
    {
        return self::create_post_like_parameters();
    }

    public static function delete_post_like_returns()
    {
        return null;
    }

    public static function delete_post_bookmark($postid)
    {
        global $DB, $USER;

        self::validate_post_write($postid);

        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records('longpage_post_bookmarks', ['postid' => $postid, 'userid' => $USER->id]);
        $transaction->allow_commit();

        self::schedule_post_recommendation_calculation_task_for_page_with_post($postid);
    }

    public static function delete_post_bookmark_parameters()
    {
        return self::create_post_like_parameters();
    }

    public static function delete_post_bookmark_returns()
    {
        return null;
    }

    public static function delete_post_parameters()
    {
        return new external_function_parameters(self::id_parameter());
    }

    public static function delete_post_reading($postid)
    {
        global $DB, $USER;

        self::validate_post_write($postid);

        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records('longpage_post_readings', ['postid' => $postid, 'userid' => $USER->id]);
        $transaction->allow_commit();

        self::schedule_post_recommendation_calculation_task_for_page_with_post($postid);
    }

    public static function delete_post_reading_parameters()
    {
        return self::create_post_like_parameters();
    }

    public static function delete_post_reading_returns()
    {
        return null;
    }

    public static function delete_post_returns()
    {
        return null;
    }

    private static function delete_selectors($annotationtargetid)
    {
        global $DB;

        $conditions = ['annotationtargetid' => $annotationtargetid];
        $pageselectors = $DB->get_records('longpage_selectors', $conditions);
        foreach ($pageselectors as $pageselector) {
            $tablename = selector::map_type_to_table_name($pageselector->type);
            $DB->delete_records($tablename, ['selectorid' => $pageselector->id]);
        }
        $DB->delete_records('longpage_selectors', $conditions);
    }

    public static function get_user_roles_by_pageid($pageid)
    {
        $context = self::get_cm_context_by_pageid($pageid);
        return ['userroles' => role_get_names($context)];
    }

    public static function get_user_roles_by_pageid_parameters()
    {
        return new external_function_parameters([
            'longpageid' => new external_value(PARAM_INT)
        ]);
    }

    public static function get_user_roles_by_pageid_returns()
    {
        return new external_function_parameters([
            'userroles' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT),
                    'localname' => new external_value(PARAM_TEXT),
                    'shortname' => new external_value(PARAM_TEXT)
                ])
            ),
        ]);
    }

    private static function get_user_roles_ids($context, $userid)
    {
        $result = [];
        $roles = get_user_roles($context, $userid);
        foreach ($roles as $role) {
            array_push($result, $role->roleid);
        }
        return $result;
    }

    /**
     * Get the link to the users's profile page.
     *
     * @return string
     */
    public static function get_profile_link($userid, $courseid)
    {
        $queryparams = ['id' => $userid, 'course' => $courseid];
        $link = new \moodle_url('/user/view.php', $queryparams);
        return $link->out(false);
    }

    public static function get_enrolled_users_with_roles_by_pageid($pageid)
    {
        $cm = get_coursemodule_by_pageid($pageid);
        $get_enrolled_users_returns = core_course_external::get_enrolled_users_by_cmid($cm->id);
        foreach ($get_enrolled_users_returns['users'] as $user) {
            $user->roles = self::get_user_roles_ids(context_module::instance($cm->id), $user->id);
            $user->profilelink = self::get_profile_link($user->id, $cm->course);
        }
        return $get_enrolled_users_returns;
    }

    public static function get_enrolled_users_with_roles_by_pageid_parameters()
    {
        return self::get_user_roles_by_pageid_parameters();
    }

    public static function get_enrolled_users_with_roles_by_pageid_returns()
    {
        return new external_single_structure([
            'users' => new external_multiple_structure(self::user_description()),
            'warnings' => new external_warnings(),
        ]);
    }

    private static function schedule_post_recommendation_calculation_task_for_page_with_post($postid)
    {
        global $DB;

        $post = $DB->get_record('longpage_posts', ['id' => $postid], 'longpageid');

        post_recommendation_calculation_task::create_from_pageid_and_queue($post->longpageid);
    }

    private static function update_annotation_by_thread($threadid, $ispublic = null)
    {
        global $DB;

        $annotation = self::get_annotation_by_thread_id($threadid);
        $update = ['id' => $annotation->id, 'timemodified' => time()];
        if (isset($ispublic)) {
            $update['ispublic'] = $ispublic;
        }

        $transaction = $DB->start_delegated_transaction();
        $DB->update_record('longpage_annotations', $update);
        $transaction->allow_commit();
    }

    /**
     * Create user return value description.
     *
     * @return external_description
     */
    public static function user_description()
    {
        $userfields = [
            'id'    => new external_value(core_user::get_property_type('id'), 'ID of the user'),
            'profileimage' => new external_value(PARAM_URL, 'The location of the users larger image', VALUE_OPTIONAL),
            'profilelink' => new external_value(PARAM_URL),
            'imagealt' => new external_value(PARAM_TEXT, '', VALUE_OPTIONAL),
            'fullname' => new external_value(PARAM_TEXT, 'The full name of the user', VALUE_OPTIONAL), # TODO: Handle users without name in frontend
            'firstname'   => new external_value(
                core_user::get_property_type('firstname'),
                'The first name(s) of the user',
                VALUE_OPTIONAL
            ),
            'lastname'    => new external_value(
                core_user::get_property_type('lastname'),
                'The family name of the user',
                VALUE_OPTIONAL
            ),
            'roles' => new external_multiple_structure(
                new external_value(PARAM_INT)
            ),
        ];
        return new external_single_structure($userfields);
    }

    public static function delete_thread_subscription($threadid)
    {
        global $DB, $USER;

        self::validate_parameters(self::create_thread_subscription_parameters(), ['threadid' => $threadid]);
        $annotation = self::get_annotation_by_thread($threadid);
        self::validate_cm_context($annotation->longpageid);

        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records('longpage_thread_subs', ['threadid' => $threadid, 'userid' => $USER->id]);
        $transaction->allow_commit();
    }

    public static function delete_thread_subscription_parameters()
    {
        return self::create_thread_subscription_parameters();
    }

    public static function delete_thread_subscription_returns()
    {
        return null;
    }

    private static function get_annotation_returns()
    {
        return new external_single_structure(array_merge(
            [
                'id' => new external_value(PARAM_INT),
                'body' => self::get_thread_returns(),
                'creatorid' => new external_value(PARAM_INT),
                'ispublic' => new external_value(PARAM_INT),
                'longpageid' => new external_value(PARAM_INT),
                'target' => self::get_annotation_target_parameters(),
                'type' => new external_value(PARAM_INT),
            ],
            self::timestamp_parameters()
        ));
    }

    /**
     * @param object $targetid
     * @return array
     */
    private static function get_annotation_target($annotationid)
    {
        global $DB;

        $target = $DB->get_record('longpage_annotation_targets', ['annotationid' => $annotationid]);
        $target->selectors = self::get_selectors($target->id);

        return omit_keys($target, ['annotationid']);
    }

    public static function get_annotation_target_parameters()
    {
        return new external_single_structure(array_merge(
            ['id' => new external_value(PARAM_INT)],
            self::annotation_target_parameters_base()
        ));
    }

    public static function get_annotations($parameters)
    {
        self::validate_parameters(self::get_annotations_parameters(), ['parameters' => $parameters]);
        self::validate_cm_context($parameters['longpageid']);

        $annotations =
            isset($parameters['annotationid']) ?
                self::get_annotations_by_annotation_id($parameters['annotationid']) :
                self::get_annotations_by_page_id($parameters['longpageid'], $parameters['userid']);

        foreach ($annotations as $annotation) {
            $annotation->target = self::get_annotation_target($annotation->id);
            if ($annotation->type == annotation_type::POST) {
                $annotation->body = self::get_thread($annotation->id);
            }
        }

        return ['annotations' => array_values($annotations)];
    }

    private static function get_annotations_by_annotation_id($annotationid, $timemodified = 0)
    {
        global $DB, $USER;

        return $DB->get_records_select(
            'longpage_annotations',
            'id = ? AND (creatorid = ? OR ispublic = 1)',
            ['id' => $annotationid, 'creatorid' => $USER->id]
        );
    }

    private static function get_annotations_by_page_id($pageid, $userid = 0) {
        global $DB, $USER;

        $cm = get_coursemodule_by_pageid($pageid);
        $context = context_module::instance($cm->id);
        if($userid == 0 || !is_siteadmin())
        {
            $userid = $USER->id;
        }

        return $DB->get_records_select(
            'longpage_annotations',
            'longpageid = ? AND (creatorid = ? OR ispublic = 1)',
            ['longpageid' => $pageid, 'creatorid' => $userid]
        );
    }

    /**
     * Describes the parameters for get_annotations.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function get_annotations_parameters()
    {
        return new external_function_parameters([
            'parameters' => new external_single_structure([
                'longpageid' => new external_value(PARAM_INT),
                'annotationid' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
                'userid' => new external_value(PARAM_INT, '', VALUE_OPTIONAL)
            ]),
        ]);
    }

    /**
     * Returns description of method result value
     *
     * @return external_function_parameters
     * @since Moodle 3.0
     */
    public static function get_annotations_returns()
    {
        return new external_function_parameters([
            'annotations' => new external_multiple_structure(self::get_annotation_returns())
        ]);
    }

    /**
     * Returns a list of pages in a provided list of courses.
     * If no list is provided all pages that the user can view will be returned.
     *
     * @param array $courseids course ids
     * @return array of warnings and pages
     * @since Moodle 3.3
     */
    public static function get_pages_by_courses($courseids = array())
    {
        $warnings = array();
        $returnedpages = array();

        $params = array(
            'courseids' => $courseids,
        );
        $params = self::validate_parameters(self::get_pages_by_courses_parameters(), $params);

        $mycourses = array();
        if (empty($params['courseids'])) {
            $mycourses = enrol_get_my_courses();
            $params['courseids'] = array_keys($mycourses);
        }

        // Ensure there are courseids to loop through.
        if (!empty($params['courseids'])) {
            list($courses, $warnings) = external_util::validate_courses($params['courseids'], $mycourses);

            // Get the pages in this course, this function checks users visibility permissions.
            // We can avoid then additional validate_context calls.
            $pages = get_all_instances_in_courses("longpage", $courses);
            foreach ($pages as $page) {
                $context = context_module::instance($page->coursemodule);
                // Entry to return.
                $page->name = external_format_string($page->name, $context->id);

                list($page->intro, $page->introformat) = external_format_text(
                    $page->intro,
                    $page->introformat,
                    $context->id,
                    'mod_longpage',
                    'intro',
                    null
                );
                $page->introfiles = external_util::get_area_files($context->id, 'mod_longpage', 'intro', false, false);

                $options = array('noclean' => true);
                list($page->content, $page->contentformat) = external_format_text(
                    $page->content,
                    $page->contentformat,
                    $context->id,
                    'mod_longpage',
                    'content',
                    $page->revision,
                    $options
                );
                $page->contentfiles = external_util::get_area_files($context->id, 'mod_longpage', 'content');

                $returnedpages[] = $page;
            }
        }

        $result = array(
            'pages' => $returnedpages,
            'warnings' => $warnings
        );
        return $result;
    }

    /**
     * Describes the parameters for get_pages_by_courses.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function get_pages_by_courses_parameters()
    {
        return new external_function_parameters(
            array(
                'courseids' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'Course id'),
                    'Array of course ids',
                    VALUE_DEFAULT,
                    array()
                ),
            )
        );
    }

    /**
     * Describes the get_pages_by_courses return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function get_pages_by_courses_returns()
    {
        return new external_single_structure(
            array(
                'pages' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'Module id'),
                            'coursemodule' => new external_value(PARAM_INT, 'Course module id'),
                            'course' => new external_value(PARAM_INT, 'Course id'),
                            'name' => new external_value(PARAM_RAW, 'Page name'),
                            'intro' => new external_value(PARAM_RAW, 'Summary'),
                            'introformat' => new external_format_value('intro', 'Summary format'),
                            'introfiles' => new external_files('Files in the introduction text'),
                            'content' => new external_value(PARAM_RAW, 'Page content'),
                            'contentformat' => new external_format_value('content', 'Content format'),
                            'contentfiles' => new external_files('Files in the content'),
                            'legacyfiles' => new external_value(PARAM_INT, 'Legacy files flag'),
                            'legacyfileslast' => new external_value(PARAM_INT, 'Legacy files last control flag'),
                            'display' => new external_value(PARAM_INT, 'How to display the page'),
                            'displayoptions' => new external_value(PARAM_RAW, 'Display options (width, height)'),
                            'revision' => new external_value(PARAM_INT, 'Incremented when after each file changes, to avoid cache'),
                            'timemodified' => new external_value(PARAM_INT, 'Last time the page was modified'),
                            'section' => new external_value(PARAM_INT, 'Course section id'),
                            'visible' => new external_value(PARAM_INT, 'Module visibility'),
                            'groupmode' => new external_value(PARAM_INT, 'Group mode'),
                            'groupingid' => new external_value(PARAM_INT, 'Grouping id'),
                        )
                    )
                ),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * @return external_single_structure
     */
    private static function get_post_returns(): external_single_structure
    {
        return new external_single_structure(array_merge(
            [
                'id' => new external_value(PARAM_INT),
                'creatorid' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
                'recommendation' => new external_value(PARAM_FLOAT, '', VALUE_OPTIONAL),
            ],
            omit_keys(self::post_parameters(), ['creatorid']),
            self::get_reactions_to_post_returns(),
            self::timestamp_parameters()
        ));
    }

    private static function get_reactions_to_post_returns()
    {
        return [
            'likescount' => new external_value(PARAM_INT),
            'likedbyuser' => new external_value(PARAM_BOOL),
            'bookmarkedbyuser' => new external_value(PARAM_BOOL),
            'readingscount' => new external_value(PARAM_INT),
            'readbyuser' => new external_value(PARAM_BOOL),
        ];
    }

    private static function get_posts($threadid, $postids = [])
    {
        global $DB, $USER;

        $select = 'threadid = ? AND (ispublic = 1 OR creatorid = ?)';
        $params = [$threadid, $USER->id];
        if ($postids) {
            list($inpostidssql, $inpostidsparams) = $DB->get_in_or_equal($postids);
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

    private static function add_reactions_to_post($post)
    {
        global $DB, $USER;

        $post->likescount = $DB->count_records('longpage_post_likes', ['postid' => $post->id]);
        $post->likedbyuser = $DB->record_exists('longpage_post_likes', ['postid' => $post->id, 'userid' => $USER->id]);

        $post->readingscount = $DB->count_records('longpage_post_readings', ['postid' => $post->id]);
        $post->readbyuser = $DB->record_exists('longpage_post_readings', ['postid' => $post->id, 'userid' => $USER->id]);

        $post->bookmarkedbyuser =
            $DB->record_exists('longpage_post_bookmarks', ['postid' => $post->id, 'userid' => $USER->id]);

        return $post;
    }

    private static function get_selectors($annotationtargetid)
    {
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

    public static function get_thread($annotationid)
    {
        global $DB, $USER;

        $thread = $DB->get_record('longpage_threads', ['annotationid' => $annotationid]);
        $thread->posts = self::get_posts($thread->id);
        $thread->subscribedtobyuser =
            $DB->record_exists('longpage_thread_subs', ['threadid' => $thread->id, 'userid' => $USER->id]);

        return $thread;
    }

    public static function get_thread_returns()
    {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT),
            'annotationid' => new external_value(PARAM_INT),
            'posts' => new external_multiple_structure(self::get_post_returns()),
            'replyid' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
            'replyrequested' => new external_value(PARAM_BOOL),
            'subscribedtobyuser' => new external_value(PARAM_BOOL),
        ], '', VALUE_OPTIONAL);
    }

    private static function id_parameter()
    {
        return ['id' => new external_value(PARAM_INT)];
    }

    private static function post_parameters()
    {
        return [
            'threadid' => new external_value(PARAM_INT),
            'creatorid' => new external_value(PARAM_INT),
            'anonymous' => new external_value(PARAM_BOOL),
            'content' => new external_value(PARAM_TEXT, ''),
            'ispublic' => new external_value(PARAM_BOOL, '', VALUE_OPTIONAL),
            'islocked' => new external_value(PARAM_BOOL, '', VALUE_OPTIONAL)
        ];
    }

    private static function timestamp_parameters()
    {
        return ['timecreated' => new external_value(PARAM_INT), 'timemodified' => new external_value(PARAM_INT)];
    }

    public static function update_highlight($id, $styleclass)
    {
        global $DB;

        self::validate_parameters(self::create_annotation_parameters(), ['id' => $id, 'styleclass' => $styleclass]);
        $annotation = $DB->get_record('longpage_annotations', ['id' => $id]);
        self::validate_cm_context($annotation->longpageid);

        self::validate_highlight_can_be_deleted_and_updated($annotation);

        $transaction = $DB->start_delegated_transaction();
        $DB->update_record('longpage_annotation_targets', ['annotationid' => $id, 'styleclass' => $styleclass]);
        $DB->update_record('longpage_annotations', ['id' => $id, 'timemodified' => time()]);
        $transaction->allow_commit();

        return [
            'annotation' => self::get_annotations(['longpageid' => $annotation->longpageid, 'annotationid' => $id])['annotations'][0]
        ];
    }

    public static function update_highlight_parameters()
    {
        return new external_function_parameters([
            'id' => new external_value(PARAM_INT),
            'styleclass' => new external_value(PARAM_TEXT),
        ]);
    }

    public static function update_highlight_returns()
    {
        return self::create_annotation_returns();
    }

    public static function update_post($postupdateparams)
    {
        global $DB, $USER;



        self::validate_parameters(self::update_post_parameters(), ['postupdate' => $postupdateparams]);
        $postupdate = (object) $postupdateparams;
        $post = $DB->get_record('longpage_posts', ['id' => $postupdate->id]);
        self::validate_cm_context($post->longpageid);

        $transaction = $DB->start_delegated_transaction();
        self::validate_post_can_be_updated($postupdate);
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

    public static function update_post_parameters()
    {
        return new external_function_parameters([
            'postupdate' =>  new external_single_structure(array_merge(
                self::id_parameter(),
                pick_keys(self::post_parameters(), ['anonymous', 'ispublic', 'islocked']),
                [
                    'content' => new external_value(PARAM_TEXT, '', VALUE_OPTIONAL),
                    'markedasrequestedreply' => new external_value(PARAM_BOOL, '', VALUE_OPTIONAL),
                    'creatorid' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
                ]
            )),
        ]);
    }

    public static function update_post_returns()
    {
        return self::create_post_returns();
    }

    public static function update_reading_progress($pageid, $scrolltop, $courseId, $section, $sectionhash)
    {
        global $DB, $USER;

        self::validate_parameters(
            self::update_reading_progress_parameters(),
            ['longpageid' => $pageid, 'scrolltop' => $scrolltop, 'courseid' => $courseId, 'section' => $section, 'sectionhash' => $sectionhash]
        );
        self::validate_cm_context($pageid);

        try {
            $transaction = $DB->start_delegated_transaction();
            $DB->insert_record('longpage_reading_progress', [
                'longpageid' => $pageid, 'scrolltop' => $scrolltop, 'userid' => $USER->id,
                'timemodified' => time(), 'course' => $courseId, 'section' => $section, 'sectionhash' => $sectionhash
            ]);
            $transaction->allow_commit();
            error_log("updatescroll good" + "cid" + $courseId);
        } catch (Exception $e) {
            $transaction->rollback($e);
            error_log("updatescroll failed");
        }
    }

    public static function update_reading_progress_parameters()
    {
        return new external_function_parameters([
            'longpageid' => new external_value(PARAM_INT),
            'scrolltop' => new external_value(PARAM_FLOAT),
            'courseid' => new external_value(PARAM_INT),
            'section' => new external_value(PARAM_TEXT),
            'sectionhash' => new external_value(PARAM_INT)
        ]);
    }

    public static function update_reading_progress_returns()
    {
        return null;
    }

    public static function get_reading_progress($courseid, $longpageid)
    {
        global $CFG, $DB, $USER;

        $r = new stdClass();
        $r->userid = $USER->id;
        $r->courseid = $data['courseid'];
        $r->pageid = $data['longpageid'];

        self::validate_parameters(
            self::get_reading_progress_parameters(),
            ['courseid' => $courseid, 'longpageid' => $longpageid]
        );

        $query = '
            SELECT section, count(sectionhash) as count
            FROM (SELECT * FROM ' . $CFG->prefix . 'longpage_reading_progress AS m WHERE course=? AND longpageid=? AND userid=?) as mm
            GROUP by section
            ';

        //$transaction = $DB->start_delegated_transaction();
        $res = $DB->get_records_sql($query, array($courseid, $longpageid, $USER->id));
        //$transaction->allow_commit();

        return array('response' => json_encode($res));
    }

    public static function get_reading_progress_parameters()
    {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
                'longpageid' => new external_value(PARAM_INT, '', VALUE_OPTIONAL)
            )


        );
    }

    public static function get_reading_progress_returns()
    {
        return new external_single_structure(
            array('response' => new external_value(PARAM_RAW, 'All bookmarks of an user'))
        );
    }

    private static function update_or_create($table, $conditions, $dataobject)
    {
        global $DB;

        $record = $DB->get_record($table, $conditions);
        if ($record) {
            $DB->update_record($table, array_merge($dataobject, ['id' => $record->id]));
        } else {
            $DB->insert_record($table, $dataobject);
        }
    }

    private static function get_cm_context_by_pageid($pageid)
    {
        $cm = get_coursemodule_by_pageid($pageid);
        return context_module::instance($cm->id);
    }

    private static function validate_cm_context($pageid)
    {
        self::validate_context(self::get_cm_context_by_pageid($pageid));
    }

    private static function validate_highlight_can_be_deleted_and_updated($highlight): void
    {
        global $USER;

        if ($USER->id !== $highlight->creatorid) {
            throw new invalid_parameter_exception('Highlight cannot be updated by user other than its creator.');
        }
        if ($highlight->type !== annotation_type::HIGHLIGHT) {
            throw new invalid_parameter_exception('Annotation is no highlight. 
                Only highlights can be updated by using this method.');
        }
    }

    private static function validate_post_can_be_deleted_and_udpated($post, $postisthreadroot)
    {
        global $DB;

        // TODO: validate that post is not root of thread
        // TODO: markasrequestedreply
        //if ($postIntern->creatorid !== $USER->id) {
        //    throw new invalid_parameter_exception('Post can only be deleted or updated by user that created it.');
        //}
        //if ($post->markedasrequestedreply) {
        //    throw new invalid_parameter_exception('Post is marked as the reply requested.
        //        It cannot be deleted/updated since others might depend on it.');
        //}

        // test remove validation begin

        self::validate_post_not_referenced_by_other_post($post);

        // $isliked = $DB->record_exists('longpage_post_likes', ['postid' => $post->id]);
        // if ($isliked) {
        //     throw new invalid_parameter_exception('Post is liked by others. 
        //         It cannot be deleted/updated since others might depend on it.');
        // }

        // $isbookmarked = $DB->record_exists_select(
        //     'longpage_post_bookmarks',
        //     'postid = ? AND userid != ?',
        //     ['postid' => $post->id, 'userid' => $post->creatorid]
        // );
        // if ($isbookmarked) {
        //     throw new invalid_parameter_exception('Post is marked by others. 
        //         It cannot be deleted/updated since others might depend on it.');
        // }

        // test remove validation end


        // TODO: Validation
        /*        $threadhassubscription = $DB->record_exists_select(
            'longpage_thread_subs',
            'threadid = ? AND userid != ?',
            ['threadid' => $post->threadid, 'userid' => $post->creatorid]
        );
        if ($threadhassubscription && $postisthreadroot) {
            throw new invalid_parameter_exception('Thread that postIntern belongs to is subscribed to by others.
                The postIntern cannot be deleted/updated since it is the root of the thread and others might depend on the thread and,
                therefore, the postIntern.');
        }*/
    }

    private static function validate_post_can_be_updated($postupdate)
    {
        global $DB, $USER;

        // TODO: Check if user has capability to update postIntern without validation and return if so
        // TODO: Check if user has capability for updating postIntern
        // TODO: Enable validation again

        //$post = $DB->get_record('longpage_posts', ['id' => $postupdate->id]);
        //$thread = $DB->get_record('longpage_threads', ['id' => $post->threadid]);
        //$rootpost = $DB->get_record('longpage_posts', ['id' => $thread->rootid]);
        //$postisthreadroot = $post->threadid === $thread->rootid;
        if (($post->ispublic && !$postupdate->ispublic) || $post->content !== $postupdate->content) {
            self::validate_post_can_be_deleted_and_udpated($post, $postisthreadroot);
        }
        //if ($postupdate->markedasrequestedreply) {
        //    if ($rootpost->creatorid !== $USER->id) {
        //        throw new invalid_parameter_exception('The postIntern can only be marked as the reply requested by the user who requested
        //            the reply.');
        //    }
        //    if (!$thread->requestedreply) {
        //        throw new invalid_parameter_exception('The postIntern cannot be marked as the reply requested since no reply was requested
        //        for thread.');
        //    }
        //    if ($thread->rootid === $postupdate->id) {
        //        throw new invalid_parameter_exception('The postIntern cannot be marked as the reply requested since it is the root of the
        //        thread were the reply has been requested.');
        //    }
        //}
    }

    /**
     * @param $post
     */
    private static function validate_post_not_referenced_by_other_post($post)
    {
        global $DB;

        $islastpost = !($DB->record_exists_select(
            'longpage_posts',
            'threadid = ? AND timecreated > ?',
            ['threadid' => $post->threadid, 'timecreated' => $post->timecreated]
        ));
        if (!$islastpost) {
            throw new invalid_parameter_exception('Only the last postIntern in a thread can be deleted/updated as postIntern could be referenced
                by other posts.');
        }
    }

    /**
     * @param $postid
     */
    private static function validate_post_write($postid): void
    {
        self::validate_parameters(self::create_post_like_parameters(), ['postid' => $postid]);
        $annotation = self::get_annotation_by_post_id($postid);
        self::validate_cm_context($annotation->longpageid);
    }

    /**
     * Simulate the page/view.php web interface page: trigger events, completion, etc...
     *
     * @param int $pageid the page instance id
     * @return array of warnings and status result
     * @throws moodle_exception
     * @since Moodle 3.0
     */
    public static function view_page($pageid)
    {
        global $DB, $CFG;
        require_once($CFG->dirroot . "/mod/longpage/lib.php");

        $params = self::validate_parameters(
            self::view_page_parameters(),
            array(
                'longpageid' => $pageid
            )
        );
        $warnings = array();

        // Request and permission validation.
        $page = $DB->get_record('longpage', array('id' => $params['longpageid']), '*', MUST_EXIST);
        list($course, $cm) = get_course_and_cm_from_instance($page, 'longpage');

        $context = context_module::instance($cm->id);
        self::validate_context($context);

        require_capability('mod/longpage:view', $context);

        // Call the page/lib API.
        longpage_view($page, $course, $cm, $context);

        $result = array();
        $result['status'] = true;
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.0
     */
    public static function view_page_parameters()
    {
        return new external_function_parameters(
            array(
                'longpageid' => new external_value(PARAM_INT, 'page instance id')
            )
        );
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function view_page_returns()
    {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_BOOL, 'status: true if success'),
                'warnings' => new external_warnings()
            )
        );
    }
    public static function log($data)
    {
        global $CFG, $DB, $USER;

        $page = $DB->get_record('longpage', array('id' => $data['longpageid']), '*', MUST_EXIST);
        list($course, $cm) = get_course_and_cm_from_instance($page, 'longpage');

        $context = context_module::instance($cm->id);        

        $r = new stdClass();
        $r->name = 'mod_longpage';
        $r->component = 'mod_longpage';
        $r->eventname = '\mod_longpage\event\course_module_' . $data['action'];
        $r->action = $data['action'];
        $r->target = 'course_module';
        $r->objecttable = 'longpage';
        $r->objectid = 0;
        $r->crud = 'r';
        $r->edulevel = 2;
        $r->contextid = $context->id;
        $r->contextlevel = $context->contextlevel;
        $r->contextinstanceid = $context->instanceid;
        $r->userid = $USER->id;
        $r->courseid = (int) $data['courseid'];

        $r->anonymous = 0;
        $r->other = $data['entry'];
        $r->timecreated = $data['utc'];
        $r->origin = 'web';
        $r->ip = $_SERVER['REMOTE_ADDR'];

        $transaction = $DB->start_delegated_transaction();
        $res = $DB->insert_record("logstore_standard_log", (array) $r);
        $transaction->allow_commit();

        if ($data['action'] == "scroll") {
            $d = json_decode($data['entry']);
            $s = new stdClass();
            $s->section = (string)$d->targetID;
            $s->sectionhash = (int) $d->sectionhash;
            $s->userid = (int) $USER->id;
            $s->course = (int) $data['courseid'];
            $s->longpageid = (int) $data['longpageid'];
            $s->timemodified = (int) $data['utc'];
            $s->scrolltop = (int) $d->scrollTop;
            $s->scrollheight = (int) $d->scrollHeight;
            $s->sectioncount = (int) $d->sectionCount;

            try {
                $transaction = $DB->start_delegated_transaction();
                $res2 = $DB->insert_record("longpage_reading_progress", (array) $s);
                $transaction->allow_commit();
                //error_log("scrolldb good"); // spams the log file
            } catch (Exception $e) {
                $transaction->rollback($e);
                error_log("scrolldb failed");
            }
        }
        return array('response' => json_encode($res));
    }
    /**
     * Takes Longpage log data form the client
     */
    public static function log_parameters()
    {
        return new external_function_parameters(
            array(
                'data' =>
                new external_single_structure(
                    array(
                        'courseid' => new external_value(PARAM_INT, 'id of course', VALUE_OPTIONAL),
                        'utc' => new external_value(PARAM_INT, '...utc time', VALUE_OPTIONAL),
                        'action' => new external_value(PARAM_TEXT, '..action', VALUE_OPTIONAL),
                        'entry' => new external_value(PARAM_RAW, 'log data', VALUE_OPTIONAL),
                        'longpageid' => new external_value(PARAM_INT, 'id of longpage', VALUE_OPTIONAL)
                    )
                )
            )
        );
    }

    public static function log_returns()
    {
        return new external_single_structure(
            array('response' => new external_value(PARAM_RAW, 'Server respons to the incomming log'))
        );
    }

    public static function can_madify_annotations($longpageid)
    {
        global $DB, $USER;

        $params = self::validate_parameters(
            self::can_madify_annotations_parameters(),
            array(
                'longpageid' => $longpageid
            )
        );
        $warnings = array();

        // Request and permission validation.
        $page = $DB->get_record('longpage', array('id' => $params['longpageid']), '*', MUST_EXIST);
        list($course, $cm) = get_course_and_cm_from_instance($page, 'longpage');

        $context = context_module::instance($cm->id);
        self::validate_context($context);

        if (has_capability('mod/longpage:modannotations', $context)) {
            return array('canmodannotations' => true);
        } else {
            return array('canmodannotations' => false);
        }
    }

    public static function can_madify_annotations_parameters()
    {
        return new external_function_parameters(
            array(
                'longpageid' => new external_value(PARAM_INT, 'page instance id')
            )
        );
    }

    public static function can_madify_annotations_returns()
    {
        return new external_single_structure(
            array('canmodannotations' => new external_value(PARAM_BOOL))
        );
    }

    public static function get_questions_by_page_id($longpageid){
        global $DB;

        $params = self::validate_parameters(
            self::get_questions_by_page_id_parameters(),
            array(
                'longpageid' => $longpageid
            )
        );

        // Request and permission validation.
        $page = $DB->get_record('longpage', array('id' => $params['longpageid']), '*', MUST_EXIST);
        list($course, $cm) = get_course_and_cm_from_instance($page, 'longpage');

        $context = context_module::instance($cm->id);
        self::validate_context($context);

        $query = "SELECT it.id, t.name as tagname
                    FROM {question} it INNER JOIN {tag_instance} tt ON it.id = tt.itemid INNER JOIN {tag} t on tt.tagid = t.id
                   WHERE tt.itemtype=? AND t.name LIKE ? AND tt.component=? ORDER BY it.id";

        $questions = $DB->get_records_sql($query, array('question', 'q:'.$cm->id.':%', 'core_question'));

        $quba = question_engine::make_questions_usage_by_activity("core_question", $context);
        $options = new question_display_options();
        $quba->set_preferred_behaviour("manualgraded");

        $res = array();
        $i = 1;
        foreach($questions as $id => $question)
        {
            $q = question_bank::load_question($question->id);
            $entry = array();
            $quba->add_question($q);
            $quba->start_question($i);
            $html = $quba->render_question($i, $options);
            $entry["tagname"] = str_replace('q:'.$cm->id.':', "", $question->tagname);
            $entry["html"] = $html;
            $i++;
            $res[] = $entry;
        }

        $return = array(
            'questions' => $res
        );
        return $return;
    }

    public static function get_questions_by_page_id_parameters(){
        return new external_function_parameters(
            array(
                'longpageid' => new external_value(PARAM_INT, 'page instance id')
            )
        );
    }

    public static function get_questions_by_page_id_returns(){
        return new external_single_structure(
            array(
                "questions" =>  new external_multiple_structure(
                    new external_single_structure(
                    array(
                        'tagname' => new external_value(PARAM_RAW),
                        'html' => new external_value(PARAM_RAW),
        )))));
    }


    public static function get_reading_comprehension($longpageid){
        global $DB, $USER, $CFG;

        $params = self::validate_parameters(
            self::get_reading_comprehension_parameters(),
            array(
                'longpageid' => $longpageid
            )
        );

        // Request and permission validation.
        $page = $DB->get_record('longpage', array('id' => $params['longpageid']), '*', MUST_EXIST);
        list($course, $cm) = get_course_and_cm_from_instance($page, 'longpage');

        $context = context_module::instance($cm->id);
        self::validate_context($context);

        $options = array('noclean' => true);
        list($page->content, $page->contentformat) = external_format_text(
            $page->content,
            $page->contentformat,
            $context->id,
            'mod_longpage',
            'content',
            $page->revision,
            $options
        );

        // $customfieldhandler = qbank_customfields\customfield\question_handler::create();
        // $sql = "SELECT d.*
        //           FROM {customfield_field} f
        //           JOIN {customfield_data} d ON (f.id = d.fieldid AND d.instanceid {$sqlinstances})
        //          WHERE f.shortname = 'readingcomprehension'";
        // $fieldsdata = $DB->get_recordset_sql($sql);
        // $field = \core_customfield\field_controller::create($fieldsdata->current()->id);
        // $fieldsdata->close();
        
        $context = \context_course::instance($course->id);
        $result = array();
        

        preg_match_all('/<iframe[\S\s]+class=\"filter_embedquestion-iframe[\S\s]+id=\"(?<catid>\S+)\/(?<qid>\S+)\"/iU', $page->content, $matches);
        $len = count($matches[1]);
        $cntSubmitted = 0;
        $cntUnlocked = 0;
        $sum = 0;
        for ($i=0; $i<$len; $i++) {
            $embed = new embed_id($matches["catid"][$i], $matches["qid"][$i]);
            $category = utils::get_category_by_idnumber($context, $embed->categoryidnumber);
            $question = utils::get_question_by_idnumber(intval($category->id), $embed->questionidnumber);
            $tagobjectsbyquestion = \core_tag_tag::get_item_tags('core_question', 'question', $question->id);
            $tagobjects = array();
            if (!empty($tagobjectsbyquestion)) {
                $tagobjects = array_map(function($tagobject) {
                    return strtolower($tagobject->rawname);
                }, $tagobjectsbyquestion);
            }

            $questionIsNew = false;
            foreach ($tagobjects as $tagobject) {
                if ($tagobject == "neu") {
                    $questionIsNew = true;
                    break;
                }
            }

            if($questionIsNew && !has_capability('mod/longpage:modannotations', $context))
            {
                continue;
            }

            $avgfraction = $DB->get_field_sql("SELECT AVG(fraction) as avgfraction FROM (SELECT qas.fraction FROM ". $CFG->prefix."question_attempts qa 
                                INNER JOIN ". $CFG->prefix."question_attempt_steps qas 
                                ON qas.questionattemptid = qa.id 
                                WHERE qas.userid = ? AND qas.fraction IS NOT NULL AND qa.questionid = ? 
                                AND qas.sequencenumber = (
                                                SELECT MAX(sequencenumber)
                                                FROM ". $CFG->prefix."question_attempt_steps
                                                WHERE questionattemptid = qa.id
                                            )
                                AND qas.timecreated > ?
                                ORDER BY qas.timecreated DESC 
                                LIMIT 5) alias", 
                                array($USER->id, $question->id, date_format(date_sub(date_create(), DateInterval::createFromDateString('3 months')), "U")));

            $cntSubmitted += $avgfraction == null ? 0 : 1;
            $cntUnlocked += $questionIsNew ? 0 : 1;
            $sum += $avgfraction;                        
            // $field_data = $customfieldhandler->get_field_data($field, $question->id);
            // $level = $field_data->get_value();
            $level = 1;
            $result[strval($embed)] = array("value" => $avgfraction, "level" => $level, "id" => $question->id, "tags" => $tagobjects);        
        }

        // if($len > 0 && $cntSubmitted == $len)
        // {

        $grade = new stdClass();
        $grade->userid = $USER->id;
        $grade->rawgrade = $len;
            //$grade->rawgrade = 100*$sum/$len;
        $gradepass = 0;
        $grades = grade_get_grades($course->id, 'mod', 'longpage', $page->id, $USER->id);
        if (!empty($grades->items)) {            
            $gradepass = floatval($grades->items[0]->gradepass);
        }
        longpage_update_grades($page, $grade);
        // }
        
        $return = array(
            'response' => json_encode($result),
            'gradeInfo' => json_encode(array("grade" => $grade->rawgrade, "gradepass" => $gradepass))
        );
        return $return;

    }

    public static function get_reading_comprehension_parameters(){
        return new external_function_parameters(
            array(
                'longpageid' => new external_value(PARAM_INT, 'page instance id')
            )
        );
    }

    public static function get_reading_comprehension_returns(){
        return new external_single_structure(
            array(
                "response" =>  new external_value(PARAM_RAW),
                'gradeInfo' => new external_value(PARAM_RAW)
            ));
    }

    public static function embed_question($longpageid, $embedcode, $position)
    {
        global $DB, $USER, $PAGE, $CFG;

        $params = self::validate_parameters(
            self::embed_question_parameters(),
            array(
                'longpageid' => $longpageid,
                'embedcode' => $embedcode,
                'position' => $position
            )
        );

        // Request and permission validation.
        $page = $DB->get_record('longpage', array('id' => $longpageid), '*', MUST_EXIST);
        list($course, $cm) = get_course_and_cm_from_instance($page, 'longpage');

        $context = context_module::instance($cm->id);
        self::validate_context($context);

        require_capability('mod/longpage:modannotations', $context);

        $options = array('trusted' => true, 'noclean' => true, 'filter' => false);
        list($page->content, $page->contentformat) = external_format_text(
            $page->content,
            $page->contentformat,
            $context->id,
            'mod_longpage',
            'content',
            $page->revision,
            $options
        );
        
        // Load $page->content as HTML
        $dom = new DOMDocument();
        $dom->loadHTML(mb_convert_encoding($page->content, 'HTML-ENTITIES', 'UTF-8'));

        $topLevelElement = self::getTopLevelElement($dom, $position);

        $newcontent = $page->content;

        if ($topLevelElement) {

            $sibling = $topLevelElement->nextSibling;
            while ($sibling && $sibling->nodeName !== $topLevelElement->nodeName) {
                $sibling = $sibling->nextSibling;
            }

            if (!$sibling || strpos($sibling->textContent, "{Q{") === false) {
                // Create a new p-tag or div-tag with $embedcode
                $newElement = $dom->createElement($topLevelElement->nodeName);
                $newElement->nodeValue = $embedcode;

                // Append the new tag to the DOM
                $topLevelElement->parentNode->insertBefore($newElement, $topLevelElement->nextSibling);
            } else {
                // Add to content of p-tag when $embedcode is already present
                $sibling->nodeValue .= " " . $embedcode;
            }
           

            // Get the updated content
            $newcontent = $dom->saveHTML();
        } else {
            // Handle the case when $position is out of range
            $newcontent = $page->content;
        }

        // Save newcontent to page
        $DB->update_record('longpage', ['id' => $longpageid, 'content' => $newcontent, 'timemodified' => time()]);

        require_once($CFG->dirroot . '/filter/embedquestion/filter.php');
        $filter = new filter_embedquestion($context, []);
        $filter->setup($PAGE, $context);
        $embedcode = str_replace("{Q{", "", $embedcode);
        $embedcode = str_replace("}Q}", "", $embedcode);
        $iframecode = $filter->embed_question($embedcode);

        // Return the updated content
        return array('response' => $iframecode);
    }

    public static function embed_question_parameters()
    {
        return new external_function_parameters(
            array(
                'longpageid' => new external_value(PARAM_INT, 'page instance id'),
                'embedcode' => new external_value(PARAM_RAW, 'embed code'),
                'position' => new external_value(PARAM_INT, 'position')
            )
        );
    }

    public static function embed_question_returns()
    {
        return new external_single_structure(
            array('response' => new external_value(PARAM_RAW, 'Server response to autosave'))
        );
    }

    public static function remove_question($longpageid, $embedid, $position)
    {
        global $DB, $USER;

        $params = self::validate_parameters(
            self::remove_question_parameters(),
            array(
                'longpageid' => $longpageid,
                'embedid' => $embedid,
                'position' => $position
            )
        );

        // Request and permission validation.
        $page = $DB->get_record('longpage', array('id' => $longpageid), '*', MUST_EXIST);
        list($course, $cm) = get_course_and_cm_from_instance($page, 'longpage');

        $context = context_module::instance($cm->id);
        self::validate_context($context);

        require_capability('mod/longpage:modannotations', $context);

        $options = array('trusted' => true, 'noclean' => true, 'filter' => false);
        list($page->content, $page->contentformat) = external_format_text(
            $page->content,
            $page->contentformat,
            $context->id,
            'mod_longpage',
            'content',
            $page->revision,
            $options
        );
        
        // Load $page->content as HTML
        $dom = new DOMDocument();
        $dom->loadHTML(mb_convert_encoding($page->content, 'HTML-ENTITIES', 'UTF-8'));

        $topLevelElement = self::getTopLevelElement($dom, $position);

        $newcontent = $page->content;

        if ($topLevelElement) {

            $sibling = $topLevelElement->nextSibling;
            while ($sibling && $sibling->nodeName !== $topLevelElement->nodeName) {
                $sibling = $sibling->nextSibling;
            }

            // Find element with text content equal to $embedcode and remove from next sibling of topLevelElement
            if (strpos($sibling->textContent, $embedid) !== false) {
                $sibling->textContent = preg_replace('/{Q{' . preg_quote($embedid, '/') . '.*?}Q}/', '', $sibling->textContent);
                if (empty(trim($sibling->textContent))) {
                    $topLevelElement->parentNode->removeChild($sibling);
                }
            }

            // Get the updated content
            $newcontent = $dom->saveHTML();
        } else {
            // Handle the case when $position is out of range
            $newcontent = $page->content;
        }

        // Save newcontent to page
        $DB->update_record('longpage', ['id' => $longpageid, 'content' => $newcontent, 'timemodified' => time()]);

        // Return the updated content
        return array('response' => json_encode("success"));
    }

    public static function remove_question_parameters()
    {
        return new external_function_parameters(
            array(
                'longpageid' => new external_value(PARAM_INT, 'page instance id'),
                'embedid' => new external_value(PARAM_RAW, 'embed id'),
                'position' => new external_value(PARAM_INT, 'position')
            )
        );
    }

    public static function remove_question_returns()
    {
        return new external_single_structure(
            array('response' => new external_value(PARAM_RAW, 'Server response to remove_question'))
        );
    }

    protected static function chat($systemContent, $userContent)
    {
        $token = "sk-e9cd0f26c3ab4a778ae4bf42199d4e85";
        $url = "https://chat-impact.fernuni-hagen.de/ollama/api/chat";
        $backupUrl = "http://catalpa-llm.fernuni-hagen.de:11434/api/chat";
        $model = "mixtral:latest";
        $authorization = "Authorization: Bearer " . $token;

        // Remove new lines and carriage returns.
        $systemContent = str_replace("\n", "", $systemContent);
        $systemContent = str_replace("\r", "", $systemContent);

        $escapers = array("\\", "/", "\"", "\n", "\r", "\t", "\x08", "\x0c");
        $replacements = array("\\\\", "\\/", "\\\"", "\\n", "\\r", "\\t", "\\f", "\\b");
        $systemContent = str_replace($escapers, $replacements, $systemContent);
        $userContent = str_replace($escapers, $replacements, $userContent);
        //replace single quotes with double quotes
        $systemContent = str_replace("'", "\\\"", $systemContent);
        $userContent = str_replace("'", "\\\"", $userContent);

        $data = '{
            "model": "' . $model . '",
            "messages": [
            {"role": "system", "content": "' . $systemContent . '"},
            {"role": "user", "content": "' . $userContent. '"}
            ],
            "stream": false
        }';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);   
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization ));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 180);
        $res = curl_exec($ch);
        if (curl_errno($ch)) {
            // Use backup URL if the primary URL fails
            curl_setopt($ch, CURLOPT_URL, $backupUrl);
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

        $result->message->content = str_replace(["'", '"'], "", $result->message->content);    

        return $result;
    }

    protected static function getTopLevelElement($dom, $position)
    {
        // Get the top level tag (div or p), i.e. that has no other div or p as a parent
        $topLevelTag = $dom->getElementsByTagName("body")->item(0)->childNodes->item(0);
        while ($topLevelTag->nodeName != "div" && $topLevelTag->nodeName != "p" && $topLevelTag->nextSibling) {
            $topLevelTag = $topLevelTag->nextSibling;
        }

        // Get all elements that are direct children of topLevelTag with the same tag name                  
        //filter elements that are not the same tag name as topLevelTag
        $topLevelElements = array_filter(iterator_to_array($topLevelTag->parentNode->childNodes), function($element) use ($topLevelTag) {
            return $element->nodeName == $topLevelTag->nodeName;
        });                    

        // Filter out p-tags that do not contain text with "{Q{"
        $filteredElements = [];
        foreach ($topLevelElements as $element) {
            if (strpos($element->textContent, '{Q{') === false) {
                $filteredElements[] = $element;
            }
        }

        // Find $position-th top level tag "p" or "div"
        if (count($filteredElements) > $position) {
            $topLevelElement = $filteredElements[$position];
        }
        else {
            $topLevelElement = $topLevelTag;
        }

        return $topLevelElement;
    }

    public static function create_question($longpageid, $position, $useAI=true, $existingQuestions="", $selectedText="", $selectedParagraphs="")
    {
        global $CFG, $DB, $USER, $PAGE;

        $now = new DateTime();
    
        $params = self::validate_parameters(
            self::create_question_parameters(),
            array(
                'longpageid' => $longpageid,
                'position' => $position,
                'useAI' => $useAI,
                'existingQuestions' => $existingQuestions,
                'selectedText' => $selectedText,
                'selectedParagraphs' => $selectedParagraphs
            )
        );

        // Request and permission validation.
        $page = $DB->get_record('longpage', array('id' => $longpageid), '*', MUST_EXIST);
        list($course, $cm) = get_course_and_cm_from_instance($page, 'longpage');
        
        $context = context_module::instance($cm->id);
        self::validate_context($context);

        require_capability('mod/longpage:modannotations', $context);

        $coursecontext = \context_course::instance($course->id);
        // Use existing questions category for quiz or create the defaults.
        if($useAI)
        {
            if (!$category = $DB->get_record('question_categories', ['contextid' => $coursecontext->id, 'idnumber' => 'aigenerated'])) {
                throw new Exception("Category with idnumber 'aigenerated' not found.");
            }
        }        
        else
        {
            if (!$category = $DB->get_record('question_categories', ['contextid' => $coursecontext->id, 'idnumber' => 'manuallygenerated'])) {
                throw new Exception("Category with idnumber 'manuallygenerated' not found.");
            }
        }
        
        $options = array('noclean' => true, 'filter' => false);
        list($page->content, $page->contentformat) = external_format_text(
            $page->content,
            $page->contentformat,
            $context->id,
            'mod_longpage',
            'content',
            $page->revision,
            $options
        );

        require_once($CFG->libdir . '/questionlib.php');
        require_once($CFG->dirroot . '/question/format.php');
        require_once($CFG->dirroot . '/question/format/gift/format.php');

        $qformat = new \qformat_gift(); 

        if($useAI)
        {
            // Load $page->content as HTML
            $dom = new DOMDocument();
            $dom->loadHTML(mb_convert_encoding($page->content, 'HTML-ENTITIES', 'UTF-8'));

            //Get the text content of the selected paragraphs
            if($selectedParagraphs != "")
            {
                $selectedParagraphs = explode(",", $selectedParagraphs);
                $textFromSelectedParagraphs = "";
                foreach ($selectedParagraphs as $selectedParagraph) {
                    if($selectedParagraph != $position)
                    {
                        $paragraph = self::getTopLevelElement($dom, $selectedParagraph);
                        $textFromSelectedParagraphs .= $paragraph->textContent;
                    }
                }
            }

            $topLevelElement = self::getTopLevelElement($dom, $position);
            
            // get text from top level element
            $textContent = $topLevelElement->textContent;
        
            // get text from startIndex to endIndex
            if ($selectedText != "") {
                $textContent = "The complete text is: '" . $textContent . "' You should create a question based on the following excerpt: '" . $selectedText . "'";
            }   
            
            if($selectedParagraphs != "")
            {
                $textContent .= " The following text is from the context and should be considered for the question and answer options: '" . $textFromSelectedParagraphs . "'";
            }

            $qtypes = array('multichoice'); //array('match', 'multichoice', 'multiresponse');          
            $qtype = $qtypes[array_rand($qtypes)];

            // create new question
            switch ($qtype) {
                case 'multichoice':
                    $explanation = 
                    "Please write one multiple choice question with one correct answer and multiple wrong answers in German language in GIFT format on the following text. GIFT format uses equal sign for right answers and tilde sign for wrong answers at the beginning of answers. For example: " .
                    "'::Question title:: Question text { " .
                    "=Correct answer 1 " . 
                    "~Wrong answer 1 " .
                    //"#Feedback to wrong answer1 " .
                    "~Wrong answer 2 " .
                    //"#Feedback to wrong answer2 " . 
                    "~Wrong answer 3 " . 
                    //"#Feedback to wrong answer3 " .  
                    "}' " .
                    "Do not forget any equal or tilde sign! Only one correct answer is allowed! Question title and question text are mandatory and different from each other!";   
                    break;
                case 'multiresponse':
                    $explanation = 
                    "Please write one multiple choice question with multiple correct answers in German language in GIFT format on the following text. GIFT format uses a tilde and percent sign at the beginning of answers, followed by a positive grade in percent for correct answers and a negative grade in negative percent with minus sign for wrong answers. All positive grades must sum up to 100%. For example: " .
                    "'::Question title:: Question text { " .
                    "~%-100% Wrong answer 1 " .
                    "~%50% Correct answer 1 " .
                    "~%50% Correct answer 2 " .
                    "~%-100% Wrong answer 2 " .
                    "}' " .
                    "Do not forget any tilde or percent sign! ";
                    break;
                case 'match':
                    $explanation =  
                    "Please write one matching question in German language in GIFT format on the following text. GIFT format uses an equal sign at the beginning of answers and and arrow sign for assigning matching pairs. For example: " .
                    "'::Question title:: Question text { " .
                    "=match 1 -> match 1 " .
                    "=match 2 -> match 2 " .
                    "=match 3 -> match 3 " .
                    "}' " .
                    "The matches should be concepts of only a few words. ".
                    "Do not forget any equal or arrow sign! ";
                    break;
            }

            if($existingQuestions != "")
            {
                $explanation .= "The following questions are already created and should not be created again: '" . $existingQuestions . "' ";
            }

            $explanation .= "Please write the question in the right format! Output only in GIFT format! Do not forget question title and question text!";
        
            $maxTries = 5;
        
            for ($i=0; $i < $maxTries; $i++) { 
                try {              
                    $result = self::chat($textContent, $explanation);

                    if($qtype == "multiresponse") {
                        $qtype = "multichoice";
                    }           
                
                    $q = $qformat->readquestion(explode("\n", $result->message->content));
                    
                    if (!$q) {
                        throw new Exception("Question not valid.");
                    }

                    if($q->questiontext == null) {
                        throw new Exception("Question text is empty.");
                    }

                    $correctAnswers = 0;
                    $sum = 0;
                    foreach ($q->fraction as $fraction) {
                        if ($fraction == 1) {
                            $correctAnswers++;
                            $sum += $fraction;
                            if ($correctAnswers > 1) {
                                throw new Exception("More than one correct answer.");
                            }
                        }
                    }
                    
                    if($sum != 1) {
                        throw new Exception("There has to be one answer with 100%.");
                    }
                                    
                    $q->idnumber = "ai-generated-".time()."-".$USER->id;  
                    $q->shuffleanswers = false;    

                    $created = self::save_question($q, $category, $qtype);
                    if (!$created) {
                        throw new Exception("Question not created.");
                    }
                    break;
                }
                catch (\Throwable $th) {
                    error_log("Create Question Error: " . $th->getMessage());
                    if ($i >= $maxTries-1) {
                        throw $th;
                    }
                }
            }
        }
        else
        {
            $qtype = 'multichoice';
            $content = "::Fragenname:: Fragentext { " .
                    "=Korrekte Antwort " . 
                    "~Falsche Antwort " .
                    "}";           
            $q = $qformat->readquestion(explode("\n", $content));   
            $q->idnumber = "manually-generated-".time()."-".$USER->id;
            $q->shuffleanswers = false;

            $created = self::save_question($q, $category, $qtype);
            if (!$created) {
                throw new Exception("Question not created.");
            }
        }

        if ($created) {
            $embedcode = external::get_embed_code($course->id, $category->idnumber, $q->idnumber, "", "", "", "", "", "", "", "", "", "", "", "");
            $iframecode  = self::embed_question($longpageid, $embedcode, $position);
            $iframecode = $iframecode['response'];    
            \core_tag_tag::add_item_tag('core_question', 'question', $created->id, $context, "neu");   
            self::log(array("longpageid" => $longpageid, "courseid" => $course->id, "utc" => time(), "action" => "question", "entry" => json_encode(array("type" => "create", "questionid" => $created->id, "qtype" => $qtype, "selectedText" => $selectedText, "selectedParagraphs" => $selectedParagraphs, "useAI" => $useAI == true ? "true" : "false", 
            "existingQuestions" => $existingQuestions, "position" => $position, "elapsedTimeMs" => $now->diff(new DateTime())->f,"embedid" => $category->idnumber . "/" . $q->idnumber, "longpageid" => $longpageid)))); 
        }

        return array('response' => json_encode(array("iframecode" => $iframecode, "log" => "Selected text: ". $selectedText), JSON_UNESCAPED_UNICODE));
    }

    protected static function save_question($q, $category, $qtype)
    {
        global $USER;
        $q->questiontext = ['text' => "<p>" . $q->questiontext . "</p>"];
        $q->category = $category->id;
        $q->createdby = $USER->id;
        $q->modifiedby = $USER->id;
        $q->timecreated = time();
        $q->timemodified = time();                
        $q->questiontextformat = 1;                
        $q->shownumcorrect = 1;
        
        $created = question_bank::get_qtype($qtype)->save_question($q, clone $q);
        return $created;
    }

    public static function create_question_parameters()
    {
        return new external_function_parameters(
            array(
                'longpageid' => new external_value(PARAM_INT, 'page instance id'),
                'position' => new external_value(PARAM_INT, 'position of embed code in text'),
                'useAI' => new external_value(PARAM_BOOL, 'use AI, otherwise empty', VALUE_DEFAULT),
                'existingQuestions' => new external_value(PARAM_RAW, 'existing questions', VALUE_DEFAULT),
                'selectedText' => new external_value(PARAM_RAW, 'selected text', VALUE_DEFAULT),
                'selectedParagraphs' => new external_value(PARAM_RAW, 'selected paragraphs', VALUE_DEFAULT),
            )
        );
    }

    public static function create_question_returns()
    {
        return new external_single_structure(
            array('response' => new external_value(PARAM_RAW, 'Server response to create_question'))
        );
    }

    public static function lock_question($longpageid, $questionid)
    {
        global $DB, $USER;

        $params = self::validate_parameters(
            self::lock_question_parameters(),
            array(
                'longpageid' => $longpageid,
                'questionid' => $questionid
            )
        );

        // Request and permission validation.
        $page = $DB->get_record('longpage', array('id' => $longpageid), '*', MUST_EXIST);
        list($course, $cm) = get_course_and_cm_from_instance($page, 'longpage');

        $context = context_module::instance($cm->id);
        self::validate_context($context);

        require_capability('mod/longpage:modannotations', $context);

        if (\core_tag_tag::is_item_tagged_with('core_question', 'question', $questionid, "neu")) {
            \core_tag_tag::remove_item_tag('core_question', 'question', $questionid, "neu");
        } else {
            \core_tag_tag::add_item_tag('core_question', 'question', $questionid, $context, "neu");
        }
           
        if (\core_tag_tag::is_item_tagged_with('core_question', 'question', $questionid, "neu")) {
            \core_tag_tag::remove_item_tag('core_question', 'question', $questionid, "neu");
        } else {
            \core_tag_tag::add_item_tag('core_question', 'question', $questionid, $context, "neu");
        }
               
        return array('response' => json_encode("success"));
    }

    public static function lock_question_parameters()
    {
        return new external_function_parameters(
            array(
                'longpageid' => new external_value(PARAM_INT, 'page instance id'),
                'questionid' => new external_value(PARAM_INT, 'question id')
            )
        );
    }

    public static function lock_question_returns()
    {
        return new external_single_structure(
            array('response' => new external_value(PARAM_RAW, 'Server response to lock_question'))
                //'gradeInfo' => new external_value(PARAM_RAW))
        );
    }

    public static function edit_question($longpageid, $questionid, $action, $qubaid, $useAI=true, $text="", $optionNumber=-1)
    {
        global $DB, $USER;

        $now = new DateTime();

        $params = self::validate_parameters(
            self::edit_question_parameters(),
            array(
                'longpageid' => $longpageid,
                'questionid' => $questionid,
                'action' => $action,
                'text' => $text,
                'qubaid' => $qubaid,
                'optionNumber' => $optionNumber
            )
        );

        // Request and permission validation.
        $page = $DB->get_record('longpage', array('id' => $longpageid), '*', MUST_EXIST);
        list($course, $cm) = get_course_and_cm_from_instance($page, 'longpage');

        $context = context_module::instance($cm->id);
        self::validate_context($context);

        require_capability('mod/longpage:modannotations', $context);

        $question = question_bank::load_question($questionid);
        $question->qtype = $question->qtype->name();
        $question->generalfeedback = ['text' => $question->generalfeedback, 'format' => $question->generalfeedbackformat];
        get_question_options($question);
        $question->questiontext = ['text' => $optionNumber != -1 || $action != "edit" ? $question->questiontext : $text, 'format' => $question->questiontextformat];
        $question->fraction = [];
        $question->feedback = [];
        $question->answer = $question->answers;
        $question->single = $question->options->single;

        $quba = question_engine::load_questions_usage_by_activity($qubaid);
        $qa = $quba->get_question_attempt(count($quba->get_slots()));
        $order = $question->get_order($qa);
        $positive = false;
        $cntPositives = 0;

        foreach ($question->answer as $key => $answer) {
            if($action == "remove" && $key == $order[$optionNumber])
            {
                if($answer->fraction > 0) {
                    if($question->single)
                    {
                        throw new Exception("Korrekte Antwort kann nicht entfernt werden.");
                    }
                    $positive = true;
                }   
                unset($question->answer[$key]);
                continue;
            }
            if($answer->fraction > 0) {
                $cntPositives++;
            }
            $question->fraction[$key] = $answer->fraction;
            $question->feedback[$key] = ['text' => $answer->feedback, 'format' => $answer->feedbackformat];
            $aw = ['text' => $action == "edit" && $optionNumber != -1 && $key == $order[$optionNumber] ? $text : $answer->answer, 'format' => $answer->answerformat];
            $aw->feedback = ['text' => $answer->feedback, 'format' => $answer->feedbackformat];
            $question->answer[$key] = $aw;
    

            if($positive) {
                foreach ($question->fraction as $key => $fraction) {
                    $question->fraction[$key] = $fraction / $cntPositives;
                }
            }
        }

        if($action == "add") {
            $key = count($question->answer);
            $answers = implode(", ", array_map(function($answer) { return "'". $answer["text"] . "'"; }, $question->answer));
            if($useAI)
            {
                $result = self::chat($text, "Please write a new distractor in German language for the following question to the given text. Question: '". $question->questiontext['text'] . "' The distractor should be different from the following answers: " . $answers . ". Give only the distractor text without any additional information.");
                $answer = $result->message->content;      
            }
            else
            {
                $answer = "Falsche Antwort";
            } 
            $question->answer[$key] = ['text' => $answer, 'format' => 1];
            $question->fraction[$key] = 0;
            $question->feedback[$key] = ['text' => "", 'format' => 1];
        }
        elseif($action == "rephrase")
        {
            if($optionNumber != -1)
            {
                $answers = implode(", ", array_map(function($answer) { return "'". $answer["text"] . "'"; }, $question->answer));
                $result = self::chat($text, "Please rephrase the following answer for the following question in German language for the given text. Question: '" . $question->questiontext['text'] . "' Answer to rephrase: '" . $question->answer[$order[$optionNumber]]['text'] . "' The rephrased answer should be different from the following answers: " . $answers . ". Give only the rephrased answer text without any additional information. Keep it short."); 
                $question->answer[$order[$optionNumber]] = ['text' => $result->message->content, 'format' => 1];
            }
            else
            {
                $result = self::chat($text, "Please rephrase the following question in German language for the given text with the following given answers. Question to rephrase: '" . $question->questiontext['text'] . "' Given answers: " . $answers . ". Give only the rephrased question text without any additional information. Keep it short."); 
                $question->questiontext = ['text' => $result->message->content, 'format' => 1];
            }
        }
    
        $question->shuffleanswers = false;
        $created = question_bank::get_qtype($question->qtype)->save_question($question, clone $question);

        if (\core_tag_tag::is_item_tagged_with('core_question', 'question', $questionid, "neu")) {
            \core_tag_tag::add_item_tag('core_question', 'question', $created->id, $context, "neu");
        } 

        if ($created) {
            $category = $DB->get_record('question_categories', ['id' => $question->category]);
            $embedid = new embed_id($category->idnumber, $question->idnumber);
            $embedlocation = embed_location::make_for_test($context, $context->get_url(), 'Embed location');
            $options = new filter_embedquestion\question_options();
            $options->set_from_request();
            $qa = new attempt($embedid, $embedlocation, $USER, $options);
            $qa->find_or_create_attempt();
            $qa->discard_broken_attempt();
            $qa->find_or_create_attempt();
            $qubaid = $qa->get_question_usage()->get_id();

            self::log(array("longpageid" => $longpageid, "courseid" => $course->id, "utc" => time(), "action" => "question", "entry" => json_encode(array("type" => $action, "questionid" => $created->id, "qtype" => $question->qtype, "useAI" => $useAI == true ? "true" : "false", "optionNumber" => $optionNumber, "embedid" => $category->idnumber . "/" . $question->idnumber, "elapsedTimeMs" => $now->diff(new DateTime())->f, "longpageid" => $longpageid)))); 

            return array('response' => json_encode(array("questionid" => $created->id, "qubaid" => $qubaid, "text" => $text), JSON_UNESCAPED_UNICODE));
        } else {
            throw new Exception("Question not edited.");
        }
    }

    public static function edit_question_parameters()
    {
        return new external_function_parameters(
            array(
                'longpageid' => new external_value(PARAM_INT, 'page instance id'),
                'questionid' => new external_value(PARAM_INT, 'question id'),
                'action' => new external_value(PARAM_TEXT, 'action'),
                'qubaid' => new external_value(PARAM_INT, 'qubaid'),
                'useAI' => new external_value(PARAM_BOOL, 'use AI, otherwise empty', VALUE_DEFAULT),
                'text' => new external_value(PARAM_RAW, 'question text', VALUE_DEFAULT),
                'optionNumber' => new external_value(PARAM_INT, 'option number', VALUE_DEFAULT)
            )
        );
    }

    public static function edit_question_returns()
    {
        return new external_single_structure(
            array('response' => new external_value(PARAM_RAW, 'Server response to edit_question'))
        );
    }

    public static function export_questions($format)
    {
        global $DB, $USER, $CFG, $PAGE, $COURSE;

        $params = self::validate_parameters(
            self::export_questions_parameters(),
            array(
                'format' => $format
            )
        );

        $questions = $DB->get_records('question', ['createdby' => $USER->id]);

        require_once($CFG->dirroot . '/question/format/xml/format.php');
        require_once($CFG->dirroot . '/question/format/gift/format.php');
        require_once($CFG->dirroot . '/question/format/aiken/format.php');
        require_once($CFG->dirroot . '/report/embedquestion/lib.php');

        $classname = 'qformat_' . $format;
        if (!class_exists($classname)) {
            throw new Exception("Format not found.");
        }

        $qformat = new $classname();
        $qformat->exportpreprocess();
        $PAGE = new \moodle_page();
        $expout = "";
        foreach ($questions as $question) {
            try {
                $qtype = $question->qtype;
                if($qtype == "multichoice") {
                    $question = question_bank::load_question($question->id);
                    if(count($question->answers) > 0 && is_latest($question->version, $question->questionbankentryid) && report_embedquestion_questions_in_use(array($question->id))) {
                        $context = context::instance_by_id($question->contextid);
                        $PAGE->set_context($context);
                        $course = $DB->get_record('course', ['id' => $context->instanceid]);
                        $qformat->setCourse($course);
                        $qformat->category = $question->category;
                        $question->qtype = $qtype;
                        get_question_options($question);
                        $question = json_decode(json_encode($question), false);
                        $expout .= $qformat->writequestion($question)."\n";
                    }   
                }             
            } catch (Exception $e) {
                continue;
            }            
        }

        send_file($expout, 'questions.txt', 0, 0, true, true, $qformat->mime_type());
    }

    public static function export_questions_parameters()
    {
        return new external_function_parameters(
            array(
                'format' => new external_value(PARAM_TEXT, 'format')
            )
        );
    }

    public static function export_questions_returns()
    {
        return new external_single_structure(
            array('response' => new external_value(PARAM_RAW, 'Server response to export_questions'))
        );
    }



    public static function autosave($data)
    {
        global $CFG, $DB, $USER, $PAGE;
        
        $form = json_decode($data['form'], true);
        $quba = question_engine::load_questions_usage_by_activity($data["qubaid"]);
        $quba->process_all_autosaves(null, $form);
        question_engine::save_questions_usage_by_activity($quba);
        return array('response' => json_encode("success"));
    }

    public static function autosave_parameters()
    {
        return new external_function_parameters(
            array(
                'data' =>
                new external_single_structure(
                    array(
                        'qubaid' => new external_value(PARAM_INT, 'qubaid'),
                        'form' => new external_value(PARAM_RAW, 'form data')
                    )
                )
            )
        );
    }

    public static function autosave_returns()
    {
        return new external_single_structure(
            array('response' => new external_value(PARAM_RAW, 'Server response to autosave'))
        );
    }
}
