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
 * Page external functions and service definitions.
 *
 * @package    mod_longpage
 * @category   external
 * @copyright  2015 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */

defined('MOODLE_INTERNAL') || die;

$functions = [
    'mod_longpage_update_reading_progress' => [
        'classname' => 'mod_longpage\\external\\reading_progress_services',
        'methodname' => 'update_reading_progress',
        'description' => 'Update reading progress of the user for one page',
        'type' => 'write',
        'capabilities' => 'mod/longpage:view',
        'ajax' => true,
    ],
    'mod_longpage_get_reading_progress' => [
        'classname' => 'mod_longpage\\external\\reading_progress_services',
        'methodname' => 'get_reading_progress',
        'description' => 'Get reading progress of the user for one page',
        'type' => 'read',
        'capabilities' => 'mod/longpage:view',
        'ajax' => true,
    ],
    'mod_longpage_get_user_roles_by_pageid' => [
        'classname' => 'mod_longpage\\external\\user_services',
        'methodname' => 'get_user_roles_by_pageid',
        'description' => 'Get roles of users participating in a page instance',
        'type' => 'read',
        'capabilities' => 'mod/longpage:view',
        'ajax' => true,
    ],
    'mod_longpage_get_enrolled_users_with_roles_by_pageid' => [
        'classname' => 'mod_longpage\\external\\user_services',
        'methodname' => 'get_enrolled_users_with_roles_by_pageid',
        'description' => 'Get enrolled users with roles (ids) by page id',
        'type' => 'read',
        'capabilities' => 'mod/longpage:view',
        'ajax' => true,
    ],
    'mod_longpage_create_annotation' => [
        'classname' => 'mod_longpage\\external\\annotation_services',
        'methodname' => 'create_annotation',
        'description' => 'Create an annotation',
        'type' => 'write',
        'capabilities' => 'mod/longpage:view',
        'ajax' => true,
    ],
    'mod_longpage_delete_annotation' => [
        'classname' => 'mod_longpage\\external\\annotation_services',
        'methodname' => 'delete_annotation',
        'description' => 'Delete annotation',
        'type' => 'write',
        'capabilities' => 'mod/longpage:view',
        'ajax' => true,
    ],
    'mod_longpage_get_annotations' => [
        'classname' => 'mod_longpage\\external\\annotation_services',
        'methodname' => 'get_annotations',
        'description' => 'Get annotations by page',
        'type' => 'read',
        'capabilities' => 'mod/longpage:view',
        'ajax' => true,
    ],

    'mod_longpage_delete_highlight' => [
        'classname' => 'mod_longpage\\external\\highlight_services',
        'methodname' => 'delete_highlight',
        'description' => 'Delete highlight',
        'type' => 'write',
        'capabilities' => 'mod/longpage:view',
        'ajax' => true,
    ],
    'mod_longpage_update_highlight' => [
        'classname' => 'mod_longpage\\external\\highlight_services',
        'methodname' => 'update_highlight',
        'description' => 'Update highlight',
        'type' => 'write',
        'capabilities' => 'mod/longpage:view',
        'ajax' => true,
    ],

    'mod_longpage_create_post' => [
        'classname' => 'mod_longpage\\external\\post_services',
        'methodname' => 'create_post',
        'description' => 'Create postIntern',
        'type' => 'write',
        'capabilities' => 'mod/longpage:view',
        'ajax' => true,
    ],
    'mod_longpage_delete_post' => [
        'classname' => 'mod_longpage\\external\\post_services',
        'methodname' => 'delete_post',
        'description' => 'Delete postIntern',
        'type' => 'write',
        'capabilities' => 'mod/longpage:view',
        'ajax' => true,
    ],
    'mod_longpage_update_post' => [
        'classname' => 'mod_longpage\\external\\post_services',
        'methodname' => 'update_post',
        'description' => 'Update post',
        'type' => 'write',
        'capabilities' => 'mod/longpage:view',
        'ajax' => true,
    ],
    'mod_longpage_create_post_like' => [
        'classname' => 'mod_longpage\\external\\post_interaction_services',
        'methodname' => 'create_post_like',
        'description' => 'Create postIntern like',
        'type' => 'write',
        'capabilities' => 'mod/longpage:view',
        'ajax' => true,
    ],
    'mod_longpage_delete_post_like' => [
        'classname' => 'mod_longpage\\external\\post_interaction_services',
        'methodname' => 'delete_post_like',
        'description' => 'Delete postIntern like',
        'type' => 'write',
        'capabilities' => 'mod/longpage:view',
        'ajax' => true,
    ],
    'mod_longpage_create_post_bookmark' => [
        'classname' => 'mod_longpage\\external\\post_interaction_services',
        'methodname' => 'create_post_bookmark',
        'description' => 'Create postIntern mark',
        'type' => 'write',
        'capabilities' => 'mod/longpage:view',
        'ajax' => true,
    ],
    'mod_longpage_delete_post_bookmark' => [
        'classname' => 'mod_longpage\\external\\post_interaction_services',
        'methodname' => 'delete_post_bookmark',
        'description' => 'Delete postIntern mark',
        'type' => 'write',
        'capabilities' => 'mod/longpage:view',
        'ajax' => true,
    ],
    'mod_longpage_create_post_reading' => [
        'classname' => 'mod_longpage\\external\\post_interaction_services',
        'methodname' => 'create_post_reading',
        'description' => 'Create postIntern reading',
        'type' => 'write',
        'capabilities' => 'mod/longpage:view',
        'ajax' => true,
    ],
    'mod_longpage_delete_post_reading' => [
        'classname' => 'mod_longpage\\external\\post_interaction_services',
        'methodname' => 'delete_post_reading',
        'description' => 'Delete postIntern reading',
        'type' => 'write',
        'capabilities' => 'mod/longpage:view',
        'ajax' => true,
    ],
    'mod_longpage_create_thread_subscription' => [
        'classname' => 'mod_longpage\\external\\thread_services',
        'methodname' => 'create_thread_subscription',
        'description' => 'Create thread subscription',
        'type' => 'write',
        'capabilities' => 'mod/longpage:view',
        'ajax' => true,
    ],
    'mod_longpage_delete_thread_subscription' => [
        'classname' => 'mod_longpage\\external\\thread_services',
        'methodname' => 'delete_thread_subscription',
        'description' => 'Delete thread subscription',
        'type' => 'write',
        'capabilities' => 'mod/longpage:view',
        'ajax' => true,
    ],
    'mod_longpage_get_pages_by_courses' => [
        'classname' => 'mod_longpage\\external\\page_services',
        'methodname' => 'get_pages_by_courses',
        'description' => 'Returns a list of pages in a provided list of courses, if no list is provided all pages that the user
                            can view will be returned.',
        'type' => 'read',
        'capabilities' => 'mod/longpage:view',
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
    'mod_longpage_view_page' => [
        'classname' => 'mod_longpage\\external\\page_services',
        'methodname' => 'view_page',
        'description' => 'Simulate the view.php web interface page: trigger events, completion, etc...',
        'type' => 'write',
        'capabilities' => 'mod/longpage:view',
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
    'mod_longpage_log' => [
        'classname' => 'mod_longpage\\external\\utility_services',
        'methodname' => 'log',
        'description' => 'Writes logdata to database',
        'type' => 'write',
        'capabilities' => 'mod/longpage:view',
        'ajax' => true,
    ],
    'mod_longpage_can_madify_annotations' => [
        'classname' => 'mod_longpage\\external\\utility_services',
        'methodname' => 'can_madify_annotations',
        'description' => 'Returns true if user can modify annotations (delete, edit)',
        'type' => 'read',
        'capabilities' => 'mod/longpage:view',
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE],
        'ajax' => true,
    ],
    'mod_longpage_get_questions_by_page_id' => [
        'classname' => 'mod_longpage\\external\\question_services',
        'methodname' => 'get_questions_by_page_id',
        'description' => 'Get questions for page',
        'type' => 'read',
        'capabilities' => 'mod/longpage:view',
        'ajax' => true,
    ],
    'mod_longpage_get_reading_comprehension' => [
        'classname' => 'mod_longpage\\external\\question_services',
        'methodname' => 'get_reading_comprehension',
        'description' => 'Get reading comprehension data for page',
        'type' => 'read',
        'capabilities' => 'mod/longpage:view',
        'ajax' => true,
    ],
    'mod_longpage_create_question' => [
        'classname' => 'mod_longpage\\external\\question_services',
        'methodname' => 'create_question',
        'description' => 'Create question with AI for reading comprehension on page',
        'type' => 'write',
        'capabilities' => 'mod/longpage:view',
        'ajax' => true,
    ],
    'mod_longpage_embed_question' => [
        'classname' => 'mod_longpage\\external\\question_services',
        'methodname' => 'embed_question',
        'description' => 'Embed question for reading comprehension on page',
        'type' => 'write',
        'capabilities' => 'mod/longpage:view',
        'ajax' => true,
    ],
    'mod_longpage_lock_question' => [
        'classname' => 'mod_longpage\\external\\question_services',
        'methodname' => 'lock_question',
        'description' => 'Lock question for reading comprehension on page',
        'type' => 'write',
        'capabilities' => 'mod/longpage:view',
        'ajax' => true,
    ],
    'mod_longpage_edit_question' => [
        'classname' => 'mod_longpage\\external\\question_services',
        'methodname' => 'edit_question',
        'description' => 'Edit question for reading comprehension on page',
        'type' => 'write',
        'capabilities' => 'mod/longpage:view',
        'ajax' => true,
    ],
    'mod_longpage_remove_question' => [
        'classname' => 'mod_longpage\\external\\question_services',
        'methodname' => 'remove_question',
        'description' => 'Remove question for reading comprehension on page',
        'type' => 'write',
        'capabilities' => 'mod/longpage:view',
        'ajax' => true,
    ],
    'mod_longpage_export_questions' => [
        'classname' => 'mod_longpage\\external\\question_services',
        'methodname' => 'export_questions',
        'description' => 'Export questions for reading comprehension on page',
        'type' => 'read',
        'capabilities' => 'mod/longpage:view',
        'ajax' => true,
    ],
    'mod_longpage_autosave' => [
        'classname' => 'mod_longpage\\external\\question_services',
        'methodname' => 'autosave',
        'description' => 'Autosaves reading comprehension tasks',
        'type' => 'write',
        'capabilities' => 'mod/longpage:view',
        'ajax' => true,
    ],
    'mod_longpage_process_question_action' => [
        'classname' => 'mod_longpage\\external\\question_services',
        'methodname' => 'process_question_action',
        'description' => 'Process a question submission action',
        'type' => 'write',
        'capabilities' => 'mod/longpage:view',
        'ajax' => true,
    ],
    'mod_longpage_get_questions_for_page' => [
        'classname' => 'mod_longpage\\external\\questions_bank_services',
        'methodname' => 'get_questions_for_page',
        'description' => 'Get questions for a page from question bank',
        'type' => 'read',
        'capabilities' => 'mod/longpage:view',
        'ajax' => true,
    ],
    'mod_longpage_get_question_detail' => [
        'classname' => 'mod_longpage\\external\\questions_bank_services',
        'methodname' => 'get_question_detail',
        'description' => 'Get detailed question data',
        'type' => 'read',
        'capabilities' => 'mod/longpage:view',
        'ajax' => true,
    ],
    'mod_longpage_get_dashboard_analytics' => [
        'classname' => 'mod_longpage\\external\\analytics_services',
        'methodname' => 'get_dashboard_analytics',
        'description' => 'Get analytics data for teacher dashboard',
        'type' => 'read',
        'capabilities' => 'mod/longpage:modannotations',
        'ajax' => true,
    ],
    'mod_longpage_get_available_semesters' => [
        'classname' => 'mod_longpage\\external\\analytics_services',
        'methodname' => 'get_available_semesters',
        'description' => 'Get available semesters for analytics filtering',
        'type' => 'read',
        'capabilities' => 'mod/longpage:modannotations',
        'ajax' => true,
    ],
];
