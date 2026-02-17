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
 * Post Novelty Calculator
 *
 * @package    mod_longpage
 * @copyright  2021 Adrian Stritzinger <adrian.stritzinger@studium.fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_longpage\local\post_recommendation;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->dirroot/mod/longpage/locallib.php");

/**
 * Post Novelty Calculator
 *
 * Calculates the novelty of a post defined as the number of users who can acces the post but have not yet read the post divided by
 * the total number of users that can access the post.
 *
 * @copyright  2021 Adrian Stritzinger <adrian.stritzinger@studium.fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class post_novelty_calculator {
    /**
     * Calculate and save post novelties.
     *
     * @param int $pageid Page ID
     * @param int $batchsize Batch size for processing
     */
    public static function calculate_and_save_post_novelties($pageid, $batchsize = 100) {
        global $DB;

        $totalnoofpageusers = self::get_total_no_of_page_users($pageid);

        $limitfrom = 0;
        while (true) {
            $posts = $DB->get_records('longpage_posts', ['longpageid' => $pageid], 'id ASC', 'id', $limitfrom, $batchsize);
            if (!count($posts)) {
                break;
            }

            foreach ($posts as $post) {
                $noofreadings = $DB->count_records('longpage_post_readings', ['postid' => $post->id]);
                $transaction = $DB->start_delegated_transaction();
                $DB->insert_record(
                    'longpage_post_novelties',
                    [
                        'longpageid' => $pageid,
                        'postid' => $post->id,
                        'value' => ($totalnoofpageusers - $noofreadings) / (float) $totalnoofpageusers,
                    ],
                    false,
                    true,
                );
                $transaction->allow_commit();
            }
            if (count($posts) < $batchsize) {
                break;
            }

            $limitfrom += $batchsize;
        }
    }

    /**
     * Delete post novelties.
     *
     * @param int $pageid Page ID
     */
    public static function delete_post_novelties($pageid) {
        global $DB;

        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records('longpage_post_novelties', ['longpageid' => $pageid]);
        $transaction->allow_commit();
    }

    /**
     * Get total number of page users.
     *
     * @param int $pageid Page ID
     * @return int
     */
    private static function get_total_no_of_page_users($pageid) {
        return count(\get_page_users_ids($pageid));
    }
}
