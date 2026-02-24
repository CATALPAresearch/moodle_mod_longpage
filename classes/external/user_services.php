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
 * User and role related external API
 *
 * @package    mod_longpage
 * @category   external
 * @copyright  2020 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */

namespace mod_longpage\external;

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once("$CFG->libdir/externallib.php");
require_once("$CFG->dirroot/course/externallib.php");
require_once("$CFG->dirroot/mod/longpage/locallib.php");

use context_module;
use core_user;

/**
 * User and role related external functions
 *
 * @package    mod_longpage
 * @category   external
 * @copyright  2020 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */
class user_services extends base_external {
    /**
     * Get user roles by page ID.
     *
     * @param int $pageid Page ID
     * @return array
     */
    public static function get_user_roles_by_pageid($pageid) {
        $context = self::get_cm_context_by_pageid($pageid);
        self::validate_context($context);
        require_capability('mod/longpage:view', $context);
        return ['userroles' => role_get_names($context)];
    }

    /**
     * Parameters for get_user_roles_by_pageid.
     *
     * @return external_function_parameters
     */
    public static function get_user_roles_by_pageid_parameters() {
        return new \external_function_parameters([
            'longpageid' => new \external_value(PARAM_INT),
        ]);
    }

    /**
     * Return structure for get_user_roles_by_pageid.
     *
     * @return external_function_parameters
     */
    public static function get_user_roles_by_pageid_returns() {
        return new \external_function_parameters([
            'userroles' => new \external_multiple_structure(
                new \external_single_structure([
                    'id' => new \external_value(PARAM_INT),
                    'localname' => new \external_value(PARAM_TEXT),
                    'shortname' => new \external_value(PARAM_TEXT),
                ])
            ),
        ]);
    }

    /**
     * Get user role IDs.
     *
     * @param object $context Context object
     * @param int $userid User ID
     * @return array
     */
    private static function get_user_roles_ids($context, $userid) {
        $result = [];
        $roles = get_user_roles($context, $userid);
        foreach ($roles as $role) {
            array_push($result, $role->roleid);
        }
        return $result;
    }

    /**
     * Get the link to the user's profile page.
     *
     * @param int $userid User ID
     * @param int $courseid Course ID
     * @return string
     */
    public static function get_profile_link($userid, $courseid) {
        $queryparams = ['id' => $userid, 'course' => $courseid];
        $link = new \moodle_url('/user/view.php', $queryparams);
        return $link->out(false);
    }

    /**
     * Get enrolled users with roles by page ID.
     *
     * @param int $pageid Page ID
     * @return array
     */
    public static function get_enrolled_users_with_roles_by_pageid($pageid) {
        $cm = get_coursemodule_by_pageid($pageid);
        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/longpage:view', $context);
        $getenrolledusersreturns = \core_course_external::get_enrolled_users_by_cmid($cm->id);
        foreach ($getenrolledusersreturns['users'] as $user) {
            $user->roles = self::get_user_roles_ids(context_module::instance($cm->id), $user->id);
            $user->profilelink = self::get_profile_link($user->id, $cm->course);
        }
        return $getenrolledusersreturns;
    }

    /**
     * Parameters for get_enrolled_users_with_roles_by_pageid.
     *
     * @return external_function_parameters
     */
    public static function get_enrolled_users_with_roles_by_pageid_parameters() {
        return self::get_user_roles_by_pageid_parameters();
    }

    /**
     * Return structure for get_enrolled_users_with_roles_by_pageid.
     *
     * @return external_single_structure
     */
    public static function get_enrolled_users_with_roles_by_pageid_returns() {
        return new \external_single_structure([
            'users' => new \external_multiple_structure(self::user_description()),
            'warnings' => new \external_warnings(),
        ]);
    }

    /**
     * Create user return value description.
     *
     * @return external_description
     */
    public static function user_description() {
        $userfields = [
            'id'    => new \external_value(core_user::get_property_type('id'), 'ID of the user'),
            'profileimage' => new \external_value(
                PARAM_URL,
                'The location of the users larger image',
                VALUE_OPTIONAL
            ),
            'profilelink' => new \external_value(PARAM_URL),
            'imagealt' => new \external_value(PARAM_TEXT, 'Alternative text for image', VALUE_OPTIONAL),
            'fullname' => new \external_value(
                PARAM_TEXT,
                'The full name of the user',
                VALUE_OPTIONAL
            ),
            'firstname' => new \external_value(
                core_user::get_property_type('firstname'),
                'The first name(s) of the user',
                VALUE_OPTIONAL
            ),
            'lastname' => new \external_value(
                core_user::get_property_type('lastname'),
                'The family name of the user',
                VALUE_OPTIONAL
            ),
            'roles' => new \external_multiple_structure(
                new \external_value(PARAM_INT)
            ),
        ];
        return new \external_single_structure($userfields);
    }
}
