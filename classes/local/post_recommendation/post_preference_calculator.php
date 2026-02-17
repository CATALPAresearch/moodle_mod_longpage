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
 * Post Preference Calculator
 *
 * @package    mod_longpage
 * @copyright  2021 Adrian Stritzinger <adrian.stritzinger@studium.fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_longpage\local\post_recommendation;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->dirroot/mod/longpage/locallib.php");

/**
 * Post Preference Calculator
 *
 * Calculates (all current) preferences of users for posts in batches of users and posts.
 *
 * @copyright  2021 Adrian Stritzinger <adrian.stritzinger@studium.fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class post_preference_calculator {
    /**
     * Minimum number of preferences required per user.
     */
    public const MIN_PREFERENCES_PER_USER = 3;

    /**
     * Calculate and save relative preferences for all users.
     *
     * @param int $pageid The page ID
     * @param int $batchsize The batch size for processing
     */
    public static function calculate_and_save_relative_preferences($pageid, $batchsize = 100) {
        global $DB;

        $limitfrom = 0;
        $fields = 'userid, avg, count';
        while (true) {
            $prefprofiles = $DB->get_records(
                'longpage_post_pref_profiles',
                ['longpageid' => $pageid],
                'id ASC',
                $fields,
                $limitfrom,
                $batchsize
            );
            if (!count($prefprofiles)) {
                break;
            }

            foreach ($prefprofiles as $profile) {
                self::calculate_and_save_relative_preferences_for_user_with_profile($profile, $pageid);
            }
            if (count($prefprofiles) < $batchsize) {
                break;
            }

            $limitfrom += $batchsize;
        }
    }

    /**
     * Calculate and save relative preferences for a specific user with profile.
     *
     * @param object $prefprofile User preference profile
     * @param int $pageid The page ID
     * @param int $batchsize The batch size for processing
     */
    private static function calculate_and_save_relative_preferences_for_user_with_profile($prefprofile, $pageid, $batchsize = 100) {
        global $DB;

        $fields = 'postid, value';
        $conditions = ['longpageid' => $pageid, 'userid' => $prefprofile->userid];
        $limitfrom = 0;
        while (true) {
            $abspreferences = $DB->get_records(
                'longpage_abs_post_prefs',
                $conditions,
                'id ASC',
                $fields,
                $limitfrom,
                $batchsize,
            );
            if (count($abspreferences) < self::MIN_PREFERENCES_PER_USER) {
                break;
            }

            $relpreferences = self::map_abs_prefs_to_rel_prefs($prefprofile, $pageid, $abspreferences);

            $transaction = $DB->start_delegated_transaction();
            $DB->insert_records('longpage_rel_post_prefs', $relpreferences);
            $transaction->allow_commit();
            if (count($abspreferences) < $batchsize) {
                break;
            }
        }
    }

    /**
     * Calculate and save average preference for a page.
     *
     * @param int $pageid The page ID
     */
    public static function calculate_and_save_avg_preference($pageid) {
        global $DB;

        $sql = 'SELECT COALESCE(AVG(avg), 0.5) AS avg
                FROM {longpage_post_pref_profiles}
                WHERE longpageid = ?';
        $avgpostpreference = $DB->get_field_sql($sql, ['longpageid' => $pageid]);
        $transaction = $DB->start_delegated_transaction();
        $DB->update_record('longpage', ['id' => $pageid, 'avgpostpreference' => $avgpostpreference]);
        $transaction->allow_commit();
    }

    /**
     * Calculate and save preference profiles for all users.
     *
     * @param int $pageid The page ID
     * @param int $batchsize The batch size for processing
     */
    public static function calculate_and_save_preference_profiles($pageid, $batchsize = 100) {
        $limitfrom = 0;
        while (true) {
            $userids = \get_page_users_ids($pageid, $limitfrom, $batchsize);
            if (!count($userids)) {
                break;
            }

            self::calculate_and_save_preference_profiles_for_users($userids, $pageid);
            if (count($userids) < $batchsize) {
                break;
            }
            $limitfrom += $batchsize;
        }
    }

    /**
     * Calculate and save preference profiles for specific users.
     *
     * @param array $userids Array of user IDs
     * @param int $pageid The page ID
     */
    public static function calculate_and_save_preference_profiles_for_users($userids, $pageid) {
        global $DB;

        $profiles = [];

        $sql = 'SELECT AVG(value) AS avg, COUNT(*) as count
                FROM {longpage_abs_post_prefs}
                WHERE userid = ? AND longpageid = ?';
        foreach ($userids as $userid) {
            $profile = $DB->get_record_sql($sql, [$userid, $pageid]);
            if (((int) $profile->count) < self::MIN_PREFERENCES_PER_USER) {
                continue;
            }

            array_push($profiles, self::get_preference_profile($userid, $pageid, $profile->avg, $profile->count));
        }

        $table = 'longpage_post_pref_profiles';
        $transaction = $DB->start_delegated_transaction();
        $DB->insert_records($table, $profiles);
        $transaction->allow_commit();
    }

    /**
     * Check if user participated in a thread.
     *
     * @param int $threadid The thread ID
     * @param int $userid The user ID
     * @return bool True if user participated, false otherwise
     */
    private static function did_user_participate_in_thread($threadid, $userid) {
        global $DB;

        $postsinthread = $DB->get_records('longpage_posts', ['threadid' => $threadid]);
        foreach ($postsinthread as $post) {
            if ($post->creatorid == $userid) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get preference profile object for a user.
     *
     * @param int $userid The user ID
     * @param int $pageid The page ID
     * @param float $avg Average preference value
     * @param int $count Number of preferences
     * @return object Preference profile object
     */
    public static function get_preference_profile($userid, $pageid, $avg, $count) {
        $profile = new \stdClass();
        $profile->userid = $userid;
        $profile->longpageid = $pageid;
        $profile->avg = $avg;
        $profile->count = $count;

        return $profile;
    }

    /**
     * Delete all absolute preferences for a page.
     *
     * @param int $pageid The page ID
     */
    public static function delete_absolute_preferences($pageid) {
        global $DB;

        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records('longpage_abs_post_prefs', ['longpageid' => $pageid]);
        $transaction->allow_commit();
    }

    /**
     * Delete all preference profiles for a page.
     *
     * @param int $pageid The page ID
     */
    public static function delete_preference_profiles($pageid) {
        global $DB;

        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records('longpage_post_pref_profiles', ['longpageid' => $pageid]);
        $transaction->allow_commit();
    }

    /**
     * Delete all relative preferences for a page.
     *
     * @param int $pageid The page ID
     */
    public static function delete_relative_preferences($pageid) {
        global $DB;

        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records('longpage_rel_post_prefs', ['longpageid' => $pageid]);
        $transaction->allow_commit();
    }

    /**
     * Calculate and save absolute preferences for all posts.
     *
     * @param int $pageid The page ID
     * @param int $batchsize The batch size for processing
     */
    public static function calculate_and_save_absolute_preferences($pageid, $batchsize = 100) {
        global $DB;

        $limitfrom = 0;
        $fields = 'id, longpageid, threadid, creatorid';
        $conditions = ['longpageid' => $pageid, 'ispublic' => 1];
        while (true) {
            $posts = $DB->get_records('longpage_posts', $conditions, 'id ASC', $fields, $limitfrom, $batchsize);
            if (!count($posts)) {
                break;
            }

            foreach ($posts as $post) {
                self::calculate_and_save_absolute_preferences_for_post($post, $pageid);
            }
            if (count($posts) < $batchsize) {
                break;
            }

            $limitfrom += $batchsize;
        }
    }

    /**
     * Calculate and save absolute preferences for a specific post.
     *
     * @param object $post The post object
     * @param int $pageid The page ID
     * @param int $batchsize The batch size for processing
     */
    public static function calculate_and_save_absolute_preferences_for_post($post, $pageid, $batchsize = 100) {
        $limitfrom = 0;
        while (true) {
            $userids = \get_page_users_ids($pageid, $limitfrom, $batchsize);
            if (!count($userids)) {
                break;
            }

            foreach ($userids as $userid) {
                self::calculate_and_save_absolute_preferences_for_post_and_user($post, $userid, $pageid);
            }
            if (count($userids) < $batchsize) {
                break;
            }

            $limitfrom += $batchsize;
        }
    }

    /**
     * Calculate and save absolute preferences for a specific post and user.
     *
     * @param object $post The post object
     * @param int $userid The user ID
     * @param int $pageid The page ID
     */
    private static function calculate_and_save_absolute_preferences_for_post_and_user($post, $userid, $pageid) {
        global $DB;

        $conditions = ['postid' => $post->id, 'userid' => $userid];
        $userreadpost = $DB->record_exists('longpage_post_readings', $conditions);
        if (!$userreadpost) {
            return;
        }

        $preference = self::get_absolute_preference($post, $userid, $pageid);

        if ((int) $preference->value == 1) {
            $transaction = $DB->start_delegated_transaction();
            $DB->insert_record('longpage_abs_post_prefs', (array) $preference);
            $transaction->allow_commit();
        }
    }

    /**
     * Get absolute preference object for a post and user.
     *
     * @param object $post The post object
     * @param int $userid The user ID
     * @param int $pageid The page ID
     * @return object Absolute preference object
     */
    private static function get_absolute_preference($post, $userid, $pageid) {
        $preference = new \stdClass();
        $preference->longpageid = $pageid;
        $preference->postid = $post->id;
        $preference->userid = $userid;
        $preference->value = self::get_post_preference_of_user($post, $userid);
        return $preference;
    }

    /**
     * Get post preference value for a user.
     *
     * @param object $post The post object
     * @param int $userid The user ID
     * @return int Preference value (1 if user likes/bookmarked/participated, 0 otherwise)
     */
    private static function get_post_preference_of_user($post, $userid) {
        global $DB;

        $conditions = ['postid' => $post->id, 'userid' => $userid];
        $userlikespost = $DB->record_exists('longpage_post_likes', $conditions);
        $userbookmarkedpost = $DB->record_exists('longpage_post_bookmarks', $conditions);
        $userparticipatedinthread = self::did_user_participate_in_thread($post->threadid, $userid);
        $usersubscribedtothread =
            $DB->record_exists('longpage_thread_subs', ['threadid' => $post->threadid, 'userid' => $userid]);
        return $userlikespost || $userbookmarkedpost || $userparticipatedinthread || $usersubscribedtothread ? 1 : 0;
    }

    /**
     * Map absolute preferences to relative preferences.
     *
     * @param object $prefprofile User preference profile
     * @param int $pageid The page ID
     * @param array $abspreferences Array of absolute preferences
     * @return array Array of relative preferences
     */
    private static function map_abs_prefs_to_rel_prefs($prefprofile, $pageid, $abspreferences) {
        return array_map(function ($abspreference) use ($prefprofile, $pageid) {
            $relpreference = new \stdClass();
            $relpreference->longpageid = $pageid;
            $relpreference->postid = $abspreference->postid;
            $relpreference->userid = $prefprofile->userid;
            $relpreference->value = (float) $abspreference->value - (float) $prefprofile->avg;
            return $relpreference;
        }, $abspreferences);
    }
}
