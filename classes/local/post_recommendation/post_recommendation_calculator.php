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
 * Post Recommendation Calculator
 *
 * @package    mod_longpage
 * @copyright  2021 Adrian Stritzinger <adrian.stritzinger@studium.fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_longpage\local\post_recommendation;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->dirroot/mod/longpage/locallib.php");

/**
 * Post Recommendation Calculator
 *
 * @copyright  2021 Adrian Stritzinger <adrian.stritzinger@studium.fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class post_recommendation_calculator {
    /** @var int Minimum number of preferences per user in the neighborhood */
    public const MIN_PREFERENCES_PER_USER_IN_NBH = 2;
    /** @var int Minimum size of the neighborhood */
    public const MIN_NEIGHBOURHOOD_SIZE = 2;
    /** @var float Weight for collaborative filter */
    public const WEIGHT_COLLAB_FILTER = 0.5;
    /** @var float Weight for novelty factor */
    public const WEIGHT_NOVELTY_FACTOR = 0.5;

    /**
     * Calculate and save recommendations for all users in a page
     *
     * @param int $pageid The page ID
     * @param int $batchsize The batch size for processing users
     */
    public static function calculate_and_save_recommendations($pageid, $batchsize = 100) {
        $limitfrom = 0;
        while (true) {
            $userids = \get_page_users_ids($pageid, $limitfrom, $batchsize);
            foreach ($userids as $userid) {
                self::calculate_and_save_recommendations_for_user($userid, $pageid);
            }
            if (count($userids) < $batchsize) {
                break;
            }
            $limitfrom += $batchsize;
        }
    }

    /**
     * Only posts that the user has not yet a preference for are recommended
     *
     * @param int $userid The user ID
     * @param int $pageid The page ID
     * @param int $batchsize The batch size for processing posts
     */
    private static function calculate_and_save_recommendations_for_user($userid, $pageid, $batchsize = 100) {
        global $DB;

        $preferences = $DB->get_records(
            'longpage_rel_post_prefs',
            ['longpageid' => $pageid, 'userid' => $userid],
            'postid, value'
        );
        if (count($preferences) < self::MIN_PREFERENCES_PER_USER_IN_NBH) {
            return;
        }

        $prefprofile = $DB->get_record('longpage_post_pref_profiles', ['longpageid' => $pageid, 'userid' => $userid], 'avg');

        $limitfrom = 0;
        $idsofpostswithprefs = array_map(function ($pref) {
            return (int) $pref->postid;
        }, $preferences);
        [$select, $params] = self::get_select_and_params_for_posts_to_calc_rec_for($idsofpostswithprefs, $pageid);
        while (true) {
            $posts = $DB->get_records_select('longpage_posts', $select, $params, 'timecreated ASC', 'id', $limitfrom, $batchsize);
            $idsofpostswithoutprefs = array_map(function ($post) {
                return (int) $post->id;
            }, $posts);
            foreach ($idsofpostswithoutprefs as $postid) {
                self::calculate_and_save_recommendation_for_user_for_post(
                    $userid,
                    $postid,
                    $preferences,
                    $idsofpostswithprefs,
                    is_nan($prefprofile->avg) ? 0.5 : $prefprofile->avg,
                    $pageid
                );
            }

            if (count($posts) < $batchsize) {
                break;
            }

            $limitfrom += $batchsize;
        }
    }

    /**
     * Calculate and save recommendation for a specific user and post
     *
     * @param int $userid The user ID
     * @param int $postid The post ID
     * @param array $preferences Array of user preferences
     * @param array $idsofpostswithprefs Array of post IDs with preferences
     * @param float $avgpref Average preference value
     * @param int $pageid The page ID
     */
    private static function calculate_and_save_recommendation_for_user_for_post(
        $userid,
        $postid,
        $preferences,
        $idsofpostswithprefs,
        $avgpref,
        $pageid
    ) {
        global $DB;

        $recommendation = self::get_recommendation_base($pageid, $postid, $userid);

        [$inpostidssql, $inpostidsparams] = $DB->get_in_or_equal($idsofpostswithprefs);
        $select = "(postaid $inpostidssql AND postbid = ?) OR (postaid = ? AND postbid $inpostidssql)";
        $relevantneighbourhood = $DB->get_records_select(
            'longpage_post_similarities',
            $select,
            array_merge($inpostidsparams, [$postid, $postid], $inpostidsparams),
        );
        $recommendationbycollabfilter = count($relevantneighbourhood) < self::MIN_NEIGHBOURHOOD_SIZE ? (float) $avgpref :
            self::calculate_recommendation_from_preferences_and_neighbourhood($postid, $preferences, $relevantneighbourhood);

        $postnovelty = $DB->get_record('longpage_post_novelties', ['postid' => $postid]);

        $recommendation->value =
            self::WEIGHT_COLLAB_FILTER * $recommendationbycollabfilter + self::WEIGHT_NOVELTY_FACTOR * $postnovelty->value;

        $transaction = $DB->start_delegated_transaction();
        $DB->insert_record('longpage_post_recomends', $recommendation, false, true);
        $transaction->allow_commit();
    }

    /**
     * Calculate recommendation value from preferences and neighbourhood
     *
     * @param int $postid The post ID
     * @param array $preferences Array of user preferences
     * @param array $neighbourhood Array of similar posts
     * @return float The calculated recommendation value
     */
    private static function calculate_recommendation_from_preferences_and_neighbourhood($postid, $preferences, $neighbourhood) {
        $dividend = 0.0;
        $divisor = 0.0;
        uasort($preferences, ['self', 'cmppostid']);
        $normalizedneighbourhood = array_map(function ($similarity) use ($postid) {
            $sim = new \stdClass();
            $sim->postid =
                ((int) $postid) == ((int) $similarity->postaid) ? (int) $similarity->postbid : (int) $similarity->postaid;
            $sim->value = $similarity->value;
            return $sim;
        }, $neighbourhood);
        uasort($normalizedneighbourhood, ['self', 'cmppostid']);
        foreach ($normalizedneighbourhood as $similarity) {
            $divisor += (float) $similarity->value;
            $dividend += ((float) $similarity->value) *
                self::shift_until_match_and_return_match($preferences, $similarity->postid);
        }
        return $dividend / $divisor;
    }

    /**
     * Delete all recommendations for a page
     *
     * @param int $pageid The page ID
     */
    public static function delete_recommendations($pageid) {
        global $DB;

        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records('longpage_post_recomends', ['longpageid' => $pageid]);
        $transaction->allow_commit();
    }

    /**
     * Shift through preferences array until matching post ID is found
     *
     * @param array $prefssortedbypostid Array of preferences sorted by post ID
     * @param int $postid The post ID to match
     * @return float|null The preference value or null if not found
     */
    private static function shift_until_match_and_return_match($prefssortedbypostid, $postid) {
        $preference = array_shift($prefssortedbypostid);
        while (isset($preference)) {
            if ($preference->postid == $postid) {
                return (float) $preference->value;
            }
            $preference = array_shift($prefssortedbypostid);
        }
    }

    /**
     * Create a base recommendation object
     *
     * @param int $pageid The page ID
     * @param int $postid The post ID
     * @param int $userid The user ID
     * @return \stdClass The recommendation object
     */
    private static function get_recommendation_base($pageid, $postid, $userid): \stdClass {
        $recommendation = new \stdClass();
        $recommendation->longpageid = $pageid;
        $recommendation->postid = $postid;
        $recommendation->userid = $userid;
        return $recommendation;
    }

    /**
     * Get SQL select statement and parameters for posts to calculate recommendations for
     *
     * @param array $idsofpostswithprefs Array of post IDs with preferences
     * @param int $pageid The page ID
     * @return array Array containing select statement and parameters
     */
    private static function get_select_and_params_for_posts_to_calc_rec_for($idsofpostswithprefs, $pageid) {
        global $DB;

        [$insql, $inparams] = $DB->get_in_or_equal($idsofpostswithprefs);
        $select = "NOT (id $insql) AND longpageid = ? AND ispublic = ?";
        $params = array_merge($inparams, [$pageid, 1]);
        return [$select, $params];
    }

    /**
     * Compare two objects by their postid property
     *
     * @param \stdClass $a First object to compare
     * @param \stdClass $b Second object to compare
     * @return int Returns -1, 0, or 1 based on comparison
     */
    private static function cmppostid($a, $b) {
        $apostid = (int) $a->postid;
        $bpostid = (int) $b->postid;
        if ($apostid == $bpostid) {
            return 0;
        }
        return ($apostid < $bpostid) ? -1 : 1;
    }
}
