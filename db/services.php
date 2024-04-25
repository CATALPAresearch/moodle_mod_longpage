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

$functions = array(
    'mod_longpage_update_reading_progress' => array(
        'classname'     => 'mod_longpage_external',
        'methodname'    => 'update_reading_progress',
        'description'   => 'Update reading progress of the user for one page',
        'type'          => 'write',
        'capabilities'  => 'mod/longpage:view',
        'ajax'          => true
    ),
    'mod_longpage_get_reading_progress' => array(
        'classname'     => 'mod_longpage_external',
        'methodname'    => 'get_reading_progress',
        'description'   => 'Get reading progress of the user for one page',
        'type'          => 'read',
        'capabilities'  => 'mod/longpage:view',
        'ajax'          => true
    ),
    'mod_longpage_get_user_roles_by_pageid' => array(
        'classname'     => 'mod_longpage_external',
        'methodname'    => 'get_user_roles_by_pageid',
        'description'   => 'Get roles of users participating in a page instance',
        'type'          => 'read',
        'capabilities'  => 'mod/longpage:view',
        'ajax'          => true
    ),
    'mod_longpage_get_enrolled_users_with_roles_by_pageid' => array(
        'classname'     => 'mod_longpage_external',
        'methodname'    => 'get_enrolled_users_with_roles_by_pageid',
        'description'   => 'Get enrolled users with roles (ids) by page id',
        'type'          => 'read',
        'capabilities'  => 'mod/longpage:view',
        'ajax'          => true
    ),
    'mod_longpage_create_annotation' => array(
        'classname'     => 'mod_longpage_external',
        'methodname'    => 'create_annotation',
        'description'   => 'Create an annotation',
        'type'          => 'write',
        'capabilities'  => 'mod/longpage:view',
        'ajax'          => true
    ),
    'mod_longpage_delete_annotation' => array(
        'classname'     => 'mod_longpage_external',
        'methodname'    => 'delete_annotation',
        'description'   => 'Delete annotation',
        'type'          => 'write',
        'capabilities'  => 'mod/longpage:view',
        'ajax'          => true
    ),
    'mod_longpage_get_annotations' => array(
        'classname'     => 'mod_longpage_external',
        'methodname'    => 'get_annotations',
        'description'   => 'Get annotations by page',
        'type'          => 'read',
        'capabilities'  => 'mod/longpage:view',
        'ajax'          => true
    ),

    'mod_longpage_delete_highlight' => array(
        'classname'     => 'mod_longpage_external',
        'methodname'    => 'delete_highlight',
        'description'   => 'Delete highlight',
        'type'          => 'write',
        'capabilities'  => 'mod/longpage:view',
        'ajax'          => true
    ),
    'mod_longpage_update_highlight' => array(
        'classname'     => 'mod_longpage_external',
        'methodname'    => 'update_highlight',
        'description'   => 'Update highlight',
        'type'          => 'write',
        'capabilities'  => 'mod/longpage:view',
        'ajax'          => true
    ),

    'mod_longpage_create_post' => array(
        'classname'     => 'mod_longpage_external',
        'methodname'    => 'create_post',
        'description'   => 'Create postIntern',
        'type'          => 'write',
        'capabilities'  => 'mod/longpage:view',
        'ajax'          => true
    ),
    'mod_longpage_delete_post' => array(
        'classname'     => 'mod_longpage_external',
        'methodname'    => 'delete_post',
        'description'   => 'Delete postIntern',
        'type'          => 'write',
        'capabilities'  => 'mod/longpage:view',
        'ajax'          => true
    ),
    'mod_longpage_update_post' => array(
        'classname'     => 'mod_longpage_external',
        'methodname'    => 'update_post',
        'description'   => 'Update post',
        'type'          => 'write',
        'capabilities'  => 'mod/longpage:view',
        'ajax'          => true
    ),
    'mod_longpage_create_post_like' => array(
        'classname'     => 'mod_longpage_external',
        'methodname'    => 'create_post_like',
        'description'   => 'Create postIntern like',
        'type'          => 'write',
        'capabilities'  => 'mod/longpage:view',
        'ajax'          => true
    ),
    'mod_longpage_delete_post_like' => array(
        'classname'     => 'mod_longpage_external',
        'methodname'    => 'delete_post_like',
        'description'   => 'Delete postIntern like',
        'type'          => 'write',
        'capabilities'  => 'mod/longpage:view',
        'ajax'          => true
    ),
    'mod_longpage_create_post_bookmark' => array(
        'classname'     => 'mod_longpage_external',
        'methodname'    => 'create_post_bookmark',
        'description'   => 'Create postIntern mark',
        'type'          => 'write',
        'capabilities'  => 'mod/longpage:view',
        'ajax'          => true
    ),
    'mod_longpage_delete_post_bookmark' => array(
        'classname'     => 'mod_longpage_external',
        'methodname'    => 'delete_post_bookmark',
        'description'   => 'Delete postIntern mark',
        'type'          => 'write',
        'capabilities'  => 'mod/longpage:view',
        'ajax'          => true
    ),
    'mod_longpage_create_post_reading' => array(
        'classname'     => 'mod_longpage_external',
        'methodname'    => 'create_post_reading',
        'description'   => 'Create postIntern reading',
        'type'          => 'write',
        'capabilities'  => 'mod/longpage:view',
        'ajax'          => true
    ),
    'mod_longpage_delete_post_reading' => array(
        'classname'     => 'mod_longpage_external',
        'methodname'    => 'delete_post_reading',
        'description'   => 'Delete postIntern reading',
        'type'          => 'write',
        'capabilities'  => 'mod/longpage:view',
        'ajax'          => true
    ),
    'mod_longpage_create_thread_subscription' => array(
        'classname'     => 'mod_longpage_external',
        'methodname'    => 'create_thread_subscription',
        'description'   => 'Create thread subscription',
        'type'          => 'write',
        'capabilities'  => 'mod/longpage:view',
        'ajax'          => true
    ),
    'mod_longpage_delete_thread_subscription' => array(
        'classname'     => 'mod_longpage_external',
        'methodname'    => 'delete_thread_subscription',
        'description'   => 'Delete thread subscription',
        'type'          => 'write',
        'capabilities'  => 'mod/longpage:view',
        'ajax'          => true
    ),
    'mod_longpage_get_pages_by_courses' => array(
        'classname'     => 'mod_longpage_external',
        'methodname'    => 'get_pages_by_courses',
        'description'   => 'Returns a list of pages in a provided list of courses, if no list is provided all pages that the user
                            can view will be returned.',
        'type'          => 'read',
        'capabilities'  => 'mod/longpage:view',
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),
    'mod_longpage_view_page' => array(
        'classname'     => 'mod_longpage_external',
        'methodname'    => 'view_page',
        'description'   => 'Simulate the view.php web interface page: trigger events, completion, etc...',
        'type'          => 'write',
        'capabilities'  => 'mod/longpage:view',
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    ),
    'mod_longpage_log' => array(
        'classname'     => 'mod_longpage_external',
        'methodname'    => 'log',
        'description'   => 'Writes logdata to database',
        'type'          => 'write',
        'capabilities'  => 'mod/longpage:view',
        //'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'ajax'        => true
    ),
    'mod_longpage_can_madify_annotations' => array(
        'classname'     => 'mod_longpage_external',
        'methodname'    => 'can_madify_annotations',
        'description'   => 'Returns true if user can modify annotations (delete, edit)',
        'type'          => 'read',
        'capabilities'  => 'mod/longpage:view',
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'ajax'          => true
    ),
    'mod_longpage_get_questions_by_page_id' => array(
        'classname'     => 'mod_longpage_external',
        'methodname'    => 'get_questions_by_page_id',
        'description'   => 'Get questions for page',
        'type'          => 'read',
        'capabilities'  => 'mod/longpage:view',
        'ajax'          => true
    ),
    'mod_longpage_get_reading_comprehension' => array(
        'classname'     => 'mod_longpage_external',
        'methodname'    => 'get_reading_comprehension',
        'description'   => 'Get reading comprehension data for page',
        'type'          => 'read',
        'capabilities'  => 'mod/longpage:view',
        'ajax'          => true
    ),
    'mod_longpage_embed_question' => array(
        'classname'     => 'mod_longpage_external',
        'methodname'    => 'embed_question',
        'description'   => 'Embed question for reading comprehension on page',
        'type'          => 'write',
        'capabilities'  => 'mod/longpage:view',
        'ajax'          => true
    ),
    'mod_longpage_autosave' => array(
        'classname'     => 'mod_longpage_external',
        'methodname'    => 'autosave',
        'description'   => 'Autosaves reading comprehension tasks',
        'type'          => 'write',
        'capabilities'  => 'mod/longpage:view',
        'ajax'        => true
    ),
);
