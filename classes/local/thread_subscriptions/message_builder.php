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
 * Message Builder
 *
 * @package    mod_longpage
 * @copyright  2021 Adrian Stritzinger <adrian.stritzinger@studium.fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_longpage\local\thread_subscriptions;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/longpage/locallib.php');

/**
 * Message Builder
 *
 * @copyright  2021 Adrian Stritzinger <adrian.stritzinger@studium.fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class message_builder {
    /**
     * Build message based on post action.
     *
     * @param object $data Message data
     * @return object|null
     */
    public static function build_message($data) {
        switch ($data->action) {
            case post_action::CREATE:
                return self::build_post_in_thread_created_message($data);
            case post_action::DELETE:
                return self::build_post_in_thread_deleted_message($data);
            case post_action::UPDATE:
                return self::build_post_in_thread_updated_message($data);
            default:
                return null;
        }
    }

    /**
     * Build message for post creation in thread.
     *
     * @param object $data Message data
     * @return object
     */
    private static function build_post_in_thread_created_message($data) {
        $message = self::get_message_base($data->subscriberid);
        $substitutions = self::get_string_substitutions($data);
        // Message subject is generated with short content.
        $message->subject = get_string('messagesubjectpostcreated_shortcontent', 'longpage', $substitutions);
        $message->fullmessage = get_string('messagefullpostcreated', 'longpage', $substitutions);
        $message->fullmessagehtml = get_string('messagehtmlpostcreated', 'longpage', $substitutions);
        $message->smallmessage = get_string('messagesmallpostcreated', 'longpage', $substitutions);
        $message->contexturl = (new \moodle_url(
            '/mod/longpage/view.php',
            ['id' => $data->cmid],
            "post-{$data->postid}"
        ))->out(false);
        $message->contexturlname = get_string('messagecontexturlnamepostcreated', 'longpage');
        return $message;
    }

    /**
     * Build message for post deletion in thread.
     *
     * @param object $data Message data
     * @return object
     */
    private static function build_post_in_thread_deleted_message($data) {
        $message = self::get_message_base($data->subscriberid);
        $substitutions = self::get_string_substitutions($data);
        $message->subject = get_string('messagesubjectpostdeleted_shortcontent', 'longpage', $substitutions);
        $message->fullmessage = get_string('messagefullpostdeleted', 'longpage', $substitutions);
        $message->fullmessagehtml = get_string('messagehtmlpostdeleted', 'longpage', $substitutions);
        $message->smallmessage = get_string('messagesmallpostdeleted', 'longpage', $substitutions);
        $message->contexturl = (new \moodle_url(
            '/mod/longpage/view.php',
            ['id' => $data->cmid],
            "thread-{$data->threadid}"
        ))->out(false);
        $message->contexturlname = get_string('messagecontexturlnamepostdeleted', 'longpage');
        return $message;
    }

    /**
     * Build message for post update in thread.
     *
     * @param object $data Message data
     * @return object
     */
    private static function build_post_in_thread_updated_message($data) {
        $message = self::get_message_base($data->subscriberid);
        $substitutions = self::get_string_substitutions($data);
        $message->subject = get_string('messagesubjectpostupdated_shortcontent', 'longpage', $substitutions);
        $message->fullmessage = get_string('messagefullpostupdated', 'longpage', $substitutions);
        $message->fullmessagehtml = get_string('messagehtmlpostupdated', 'longpage', $substitutions);
        $message->smallmessage = get_string('messagesmallpostupdated', 'longpage', $substitutions);
        $message->contexturl = (new \moodle_url(
            '/mod/longpage/view.php',
            ['id' => $data->cmid],
            "post-{$data->postid}"
        ))->out(false);
        $message->contexturlname = get_string('messagecontexturlnamepostupdated', 'longpage');
        return $message;
    }

    /**
     * Get base message object.
     *
     * @param int $userid User ID
     * @return object
     */
    private static function get_message_base($userid) {
        $message = new \core\message\message();
        $message->component = 'mod_longpage';
        $message->name = 'posts';
        $message->userfrom = \core_user::get_noreply_user();
        $message->userto = \core_user::get_user($userid);
        $message->fullmessageformat = \FORMAT_MOODLE;
        $message->notification = 1;
        return $message;
    }

    /**
     * Get string substitutions for message.
     *
     * @param object $data Message data
     * @return array
     */
    private static function get_string_substitutions($data) {
        $actor = \core_user::get_user($data->actorid);
        $shortcontent = self::shorten_content($data->content);
        $substitutions = [
            'firstname' => $actor->firstname,
            'lastname' => $actor->lastname,
            'content' => $data->content,
            'oldcontent' => $data->oldcontent,
            'shortcontent' => $shortcontent,
            'contexturl' => (new \moodle_url('/mod/longpage/view.php', ['id' => $data->cmid], "post-{$data->postid}"))->out(false),
        ];
        return $substitutions;
    }

    /**
     * Shorten content for display.
     *
     * @param string $content Content to shorten
     * @return string
     */
    private static function shorten_content($content) {
        $returnval;
        if (strlen($content) >= 15) {
            $returnval = substr($content, 0, 12);
            $returnval .= "...";
        } else {
            $returnval = $content;
        }
        return $returnval;
    }
}
