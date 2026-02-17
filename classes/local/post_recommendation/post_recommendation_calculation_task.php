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
 * Post Preference Calculation Task
 *
 * @package    mod_longpage
 * @copyright  2021 Adrian Stritzinger <adrian.stritzinger@studium.fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_longpage\local\post_recommendation;

defined('MOODLE_INTERNAL') || die();


/**
 * Post Preference Calculation Task
 *
 * @copyright  2021 Adrian Stritzinger <adrian.stritzinger@studium.fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class post_recommendation_calculation_task extends \core\task\adhoc_task {
    /**
     * Execute the task.
     */
    public function execute() {
        $data = $this->get_custom_data();
        $pageid = $data->longpageid;
        mtrace('Started calculating recommendations for posts on page ' . $pageid . '.');

        mtrace('Deleting obsolete recommendations of posts on page ' . $pageid . '.');
        post_recommendation_calculator::delete_recommendations($pageid);

        mtrace('Deleting obsolete similarities of posts on page ' . $pageid . '.');
        post_similarity_calculator::delete_similarities($pageid);

        mtrace('Deleting obsolete novelties of posts on page ' . $pageid . '.');
        post_novelty_calculator::delete_post_novelties($pageid);

        mtrace('Deleting obsolete relative preferences for posts on page ' . $pageid . '.');
        post_preference_calculator::delete_relative_preferences($pageid);

        mtrace('Deleting obsolete preference profiles of users on page ' . $pageid . '.');
        post_preference_calculator::delete_preference_profiles($pageid);

        mtrace('Deleting obsolete absolute preferences for posts on page ' . $pageid . '.');
        post_preference_calculator::delete_absolute_preferences($pageid);

        mtrace('Calculating absolute preferences for posts on page ' . $pageid . '.');
        post_preference_calculator::calculate_and_save_absolute_preferences($pageid);

        mtrace('Calculating preference profiles of users on page ' . $pageid . '.');
        post_preference_calculator::calculate_and_save_preference_profiles($pageid);

        mtrace('Calculating average preference of users on page ' . $pageid . '.');
        post_preference_calculator::calculate_and_save_avg_preference($pageid);

        mtrace('Calculating relative preferences for posts on page ' . $pageid . '.');
        post_preference_calculator::calculate_and_save_relative_preferences($pageid);

        mtrace('Deleting absolute preferences for posts on page ' . $pageid . ' to save disc space.');
        post_preference_calculator::delete_absolute_preferences($pageid);

        mtrace('Calculating similarities of posts on page ' . $pageid . '.');
        post_similarity_calculator::calculate_and_save_post_similarities($pageid);

        mtrace('Calculating novelties of posts on page ' . $pageid . '.');
        post_novelty_calculator::calculate_and_save_post_novelties($pageid);

        mtrace('Calculating recommendations of posts on page ' . $pageid . '.');
        post_recommendation_calculator::calculate_and_save_recommendations($pageid);

        mtrace('Deleting similarities of posts on page ' . $pageid . ' to save disc space.');
        post_similarity_calculator::delete_similarities($pageid);

        mtrace('Deleting novelties of posts on page ' . $pageid . ' to save disc space.');
        post_novelty_calculator::delete_post_novelties($pageid);

        mtrace('Deleting relative preferences for posts on page ' . $pageid . ' to save disc space.');
        post_preference_calculator::delete_relative_preferences($pageid);

        mtrace('Deleting preference profiles of users on page ' . $pageid . ' to save disc space.');
        post_preference_calculator::delete_preference_profiles($pageid);

        mtrace('Calculation of recommendations for posts on page ' . $pageid . ' successfully finished.');
    }

    /**
     * Create task from page ID and queue it.
     *
     * @param int $pageid Page ID
     */
    public static function create_from_pageid_and_queue($pageid) {
        $postrecommcalctask = new post_recommendation_calculation_task();
        $postrecommcalctask->set_custom_data(['longpageid' => $pageid]);
        $postrecommcalctask->set_next_run_time(self::get_next_task_run_time());
        \core\task\manager::queue_adhoc_task($postrecommcalctask, true);
    }

    /**
     * Get next task run time.
     *
     * @return int
     */
    private static function get_next_task_run_time() {
        $time = new \DateTime("now", \core_date::get_server_timezone_object());
        $time->add(new \DateInterval("P1D"));
        $time->setTime(3, 0, 0);
        return $time->getTimestamp();
    }
}
