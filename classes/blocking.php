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
 * Blocking class for policy acceptance.
 *
 * @package    mod_longpage
 * @copyright  Marc Burchart <marc.burchart@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_longpage;

defined('MOODLE_INTERNAL') || die();

/**
 * Blocking class for policy acceptance.
 *
 * @copyright  Marc Burchart <marc.burchart@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class blocking {
    /**
     * Check if tool policy is accepted.
     *
     * @return bool
     */
    public static function tool_policy_accepted() {

        global $DB, $USER;
        require_login();
        if (isset($_SESSION['policy_accepted']) && $_SESSION['policy_accepted'] === true) {
            return true;
        }
        // Local_niels: 11, aple: 6.
        $version = 6;
        $res = $DB->get_record(
            "tool_policy_acceptances",
            ["policyversionid" => $version, "userid" => (int)$USER->id],
            "status"
        );

        if (isset($res->status) && (int)$res->status == 1) {
            $_SESSION['policy_accepted'] = true;
            return true;
        }
        $_SESSION['policy_accepted'] = false;
        return true;
    }
}
