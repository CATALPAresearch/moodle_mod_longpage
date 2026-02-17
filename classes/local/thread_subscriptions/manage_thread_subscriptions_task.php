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
 * Manage Post Subscriptions Task
 *
 * @package    mod_longpage
 * @copyright  2021 Adrian Stritzinger <adrian.stritzinger@studium.fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_longpage\local\thread_subscriptions;

defined('MOODLE_INTERNAL') || die();

/**
 * Manage Post Subscriptions Task
 *
 * @copyright  2021 Adrian Stritzinger <adrian.stritzinger@studium.fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manage_thread_subscriptions_task extends \core\task\adhoc_task {
    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;

        $data = $this->get_custom_data();
        $threadid = $data->threadid;
        mtrace('Started delivering messages regarding updates of thread ' . $threadid . ' to subscribers.');

        $subscriptions = $DB->get_records('longpage_thread_subs', ['threadid' => $threadid]);
        foreach ($subscriptions as $subscription) {
            $data->subscriberid = $subscription->userid;
            if ((int) $data->subscriberid === (int) $data->actorid) {
                continue;
            }

            $message = message_builder::build_message($data);
            message_send($message);
        }

        mtrace('Finished delivering messages regarding updates of thread ' . $threadid . ' to subscribers.');
    }

    /**
     * Create and queue the manage thread subscriptions task.
     *
     * @param int $cmid Course module ID.
     * @param int $postid Post ID.
     * @param int $threadid Thread ID.
     * @param int $actorid Actor user ID.
     * @param string $action Action type.
     * @param string $content Content.
     * @param string $oldcontent Old content.
     */
    public static function create_manage_thread_subscriptions_task(
        $cmid,
        $postid,
        $threadid,
        $actorid,
        $action = post_action::CREATE,
        $content = '',
        $oldcontent = ''
    ) {
        $managepostsubscriptionstask = new manage_thread_subscriptions_task();
        $managepostsubscriptionstask->set_custom_data([
            'cmid' => $cmid,
            'postid' => $postid,
            'threadid' => $threadid,
            'actorid' => $actorid,
            'action' => $action,
            'content' => $content,
            'oldcontent' => $oldcontent,
        ]);
        $managepostsubscriptionstask->set_next_run_time(time());
        \core\task\manager::queue_adhoc_task($managepostsubscriptionstask, true);
    }
}
