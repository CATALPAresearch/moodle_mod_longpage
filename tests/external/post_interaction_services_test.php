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
 * Post interaction services external functions unit tests
 *
 * @package    mod_longpage
 * @category   external
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_longpage\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * Post interaction services external functions unit tests
 *
 * @package    mod_longpage
 * @category   external
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_longpage\external\post_interaction_services
 */
final class post_interaction_services_test extends \externallib_advanced_testcase {
    /**
     * Set up test data
     *
     * @return array Test data components
     */
    private function setup_test_data(): array {
        global $DB;

        $this->resetAfterTest(true);

        // Create course and longpage module.
        $course = $this->getDataGenerator()->create_course();
        $longpage = $this->getDataGenerator()->create_module('longpage', [
            'course' => $course->id,
            'name' => 'Test Longpage',
            'content' => 'Test content for longpage',
        ]);

        // Create users.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Enrol users in the course.
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $this->getDataGenerator()->enrol_user($user1->id, $course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id, $studentrole->id);

        // Create annotation.
        $annotation = new \stdClass();
        $annotation->creatorid = $user1->id;
        $annotation->longpageid = $longpage->id;
        $annotation->type = 1;
        $annotation->ispublic = 1;
        $annotation->timecreated = time();
        $annotation->timemodified = time();
        $annotationid = $DB->insert_record('longpage_annotations', $annotation);

        // Create thread.
        $thread = new \stdClass();
        $thread->annotationid = $annotationid;
        $thread->replyrequested = 0;
        $threadid = $DB->insert_record('longpage_threads', $thread);

        // Create post.
        $post = new \stdClass();
        $post->threadid = $threadid;
        $post->longpageid = $longpage->id;
        $post->creatorid = $user1->id;
        $post->timecreated = time();
        $post->timemodified = time();
        $post->anonymous = 0;
        $post->content = 'Test post content';
        $post->ispublic = 1;
        $post->islocked = 0;
        $postid = $DB->insert_record('longpage_posts', $post);

        return [
            'course' => $course,
            'longpage' => $longpage,
            'user1' => $user1,
            'user2' => $user2,
            'annotation' => $annotationid,
            'thread' => $threadid,
            'post' => $postid,
        ];
    }

    /**
     * Test creating a post like
     *
     * @covers \mod_longpage\external\post_interaction_services::create_post_like
     */
    public function test_create_post_like(): void {
        global $DB;

        $data = $this->setup_test_data();
        $this->setUser($data['user1']);

        // Create post like.
        post_interaction_services::create_post_like($data['post']);

        // Verify like was created.
        $likes = $DB->get_records('longpage_post_likes', ['postid' => $data['post'], 'userid' => $data['user1']->id]);
        $this->assertCount(1, $likes);

        $like = reset($likes);
        $this->assertEquals($data['post'], $like->postid);
        $this->assertEquals($data['user1']->id, $like->userid);
        $this->assertNotEmpty($like->timecreated);
    }

    /**
     * Test creating a post bookmark
     *
     * @covers \mod_longpage\external\post_interaction_services::create_post_bookmark
     */
    public function test_create_post_bookmark(): void {
        global $DB;

        $data = $this->setup_test_data();
        $this->setUser($data['user1']);

        // Create post bookmark.
        post_interaction_services::create_post_bookmark($data['post']);

        // Verify bookmark was created.
        $bookmarks = $DB->get_records('longpage_post_bookmarks', ['postid' => $data['post'], 'userid' => $data['user1']->id]);
        $this->assertCount(1, $bookmarks);

        $bookmark = reset($bookmarks);
        $this->assertEquals($data['post'], $bookmark->postid);
        $this->assertEquals($data['user1']->id, $bookmark->userid);
        $this->assertNotEmpty($bookmark->timecreated);
    }

    /**
     * Test creating a post reading
     *
     * @covers \mod_longpage\external\post_interaction_services::create_post_reading
     */
    public function test_create_post_reading(): void {
        global $DB;

        $data = $this->setup_test_data();
        $this->setUser($data['user1']);

        // Create post reading.
        post_interaction_services::create_post_reading($data['post']);

        // Verify reading was created.
        $readings = $DB->get_records('longpage_post_readings', ['postid' => $data['post'], 'userid' => $data['user1']->id]);
        $this->assertCount(1, $readings);

        $reading = reset($readings);
        $this->assertEquals($data['post'], $reading->postid);
        $this->assertEquals($data['user1']->id, $reading->userid);
        $this->assertNotEmpty($reading->timecreated);
    }

    /**
     * Test deleting a post like
     *
     * @covers \mod_longpage\external\post_interaction_services::delete_post_like
     */
    public function test_delete_post_like(): void {
        global $DB;

        $data = $this->setup_test_data();
        $this->setUser($data['user1']);

        // Create and then delete post like.
        post_interaction_services::create_post_like($data['post']);
        $this->assertTrue($DB->record_exists('longpage_post_likes', ['postid' => $data['post'], 'userid' => $data['user1']->id]));

        post_interaction_services::delete_post_like($data['post']);

        // Verify like was deleted.
        $this->assertFalse($DB->record_exists('longpage_post_likes', ['postid' => $data['post'], 'userid' => $data['user1']->id]));
    }

    /**
     * Test deleting a post bookmark
     *
     * @covers \mod_longpage\external\post_interaction_services::delete_post_bookmark
     */
    public function test_delete_post_bookmark(): void {
        global $DB;

        $data = $this->setup_test_data();
        $this->setUser($data['user1']);

        // Create and then delete post bookmark.
        post_interaction_services::create_post_bookmark($data['post']);
        $this->assertTrue($DB->record_exists(
            'longpage_post_bookmarks',
            ['postid' => $data['post'], 'userid' => $data['user1']->id]
        ));

        post_interaction_services::delete_post_bookmark($data['post']);

        // Verify bookmark was deleted.
        $this->assertFalse($DB->record_exists(
            'longpage_post_bookmarks',
            ['postid' => $data['post'], 'userid' => $data['user1']->id]
        ));
    }

    /**
     * Test deleting a post reading
     *
     * @covers \mod_longpage\external\post_interaction_services::delete_post_reading
     */
    public function test_delete_post_reading(): void {
        global $DB;

        $data = $this->setup_test_data();
        $this->setUser($data['user1']);

        // Create and then delete post reading.
        post_interaction_services::create_post_reading($data['post']);
        $this->assertTrue($DB->record_exists(
            'longpage_post_readings',
            ['postid' => $data['post'], 'userid' => $data['user1']->id]
        ));

        post_interaction_services::delete_post_reading($data['post']);

        // Verify reading was deleted.
        $this->assertFalse($DB->record_exists(
            'longpage_post_readings',
            ['postid' => $data['post'], 'userid' => $data['user1']->id]
        ));
    }

    /**
     * Test idempotency of creating same like twice
     *
     * @covers \mod_longpage\external\post_interaction_services::create_post_like
     */
    public function test_create_post_like_idempotency(): void {
        global $DB;

        $data = $this->setup_test_data();
        $this->setUser($data['user1']);

        // Create like twice.
        post_interaction_services::create_post_like($data['post']);
        post_interaction_services::create_post_like($data['post']);

        // Verify only one like exists.
        $likes = $DB->get_records('longpage_post_likes', ['postid' => $data['post'], 'userid' => $data['user1']->id]);
        $this->assertCount(1, $likes);
    }

    /**
     * Test idempotency of creating same bookmark twice
     *
     * @covers \mod_longpage\external\post_interaction_services::create_post_bookmark
     */
    public function test_create_post_bookmark_idempotency(): void {
        global $DB;

        $data = $this->setup_test_data();
        $this->setUser($data['user1']);

        // Create bookmark twice.
        post_interaction_services::create_post_bookmark($data['post']);
        post_interaction_services::create_post_bookmark($data['post']);

        // Verify only one bookmark exists.
        $bookmarks = $DB->get_records('longpage_post_bookmarks', ['postid' => $data['post'], 'userid' => $data['user1']->id]);
        $this->assertCount(1, $bookmarks);
    }

    /**
     * Test idempotency of creating same reading twice
     *
     * @covers \mod_longpage\external\post_interaction_services::create_post_reading
     */
    public function test_create_post_reading_idempotency(): void {
        global $DB;

        $data = $this->setup_test_data();
        $this->setUser($data['user1']);

        // Create reading twice.
        post_interaction_services::create_post_reading($data['post']);
        post_interaction_services::create_post_reading($data['post']);

        // Verify only one reading exists.
        $readings = $DB->get_records('longpage_post_readings', ['postid' => $data['post'], 'userid' => $data['user1']->id]);
        $this->assertCount(1, $readings);
    }

    /**
     * Test different users can react to same post
     *
     * @covers \mod_longpage\external\post_interaction_services::create_post_like
     */
    public function test_multiple_users_can_like_same_post(): void {
        global $DB;

        $data = $this->setup_test_data();

        // User 1 likes the post.
        $this->setUser($data['user1']);
        post_interaction_services::create_post_like($data['post']);

        // User 2 likes the post.
        $this->setUser($data['user2']);
        post_interaction_services::create_post_like($data['post']);

        // Verify both likes exist.
        $totallikes = $DB->count_records('longpage_post_likes', ['postid' => $data['post']]);
        $this->assertEquals(2, $totallikes);

        $user1like = $DB->record_exists('longpage_post_likes', ['postid' => $data['post'], 'userid' => $data['user1']->id]);
        $this->assertTrue($user1like);

        $user2like = $DB->record_exists('longpage_post_likes', ['postid' => $data['post'], 'userid' => $data['user2']->id]);
        $this->assertTrue($user2like);
    }

    /**
     * Test different users can bookmark same post
     *
     * @covers \mod_longpage\external\post_interaction_services::create_post_bookmark
     */
    public function test_multiple_users_can_bookmark_same_post(): void {
        global $DB;

        $data = $this->setup_test_data();

        // User 1 bookmarks the post.
        $this->setUser($data['user1']);
        post_interaction_services::create_post_bookmark($data['post']);

        // User 2 bookmarks the post.
        $this->setUser($data['user2']);
        post_interaction_services::create_post_bookmark($data['post']);

        // Verify both bookmarks exist.
        $totalbookmarks = $DB->count_records('longpage_post_bookmarks', ['postid' => $data['post']]);
        $this->assertEquals(2, $totalbookmarks);

        $user1bookmark = $DB->record_exists(
            'longpage_post_bookmarks',
            ['postid' => $data['post'], 'userid' => $data['user1']->id]
        );
        $this->assertTrue($user1bookmark);

        $user2bookmark = $DB->record_exists(
            'longpage_post_bookmarks',
            ['postid' => $data['post'], 'userid' => $data['user2']->id]
        );
        $this->assertTrue($user2bookmark);
    }

    /**
     * Test different users can mark same post as read
     *
     * @covers \mod_longpage\external\post_interaction_services::create_post_reading
     */
    public function test_multiple_users_can_read_same_post(): void {
        global $DB;

        $data = $this->setup_test_data();

        // User 1 reads the post.
        $this->setUser($data['user1']);
        post_interaction_services::create_post_reading($data['post']);

        // User 2 reads the post.
        $this->setUser($data['user2']);
        post_interaction_services::create_post_reading($data['post']);

        // Verify both readings exist.
        $totalreadings = $DB->count_records('longpage_post_readings', ['postid' => $data['post']]);
        $this->assertEquals(2, $totalreadings);

        $user1reading = $DB->record_exists(
            'longpage_post_readings',
            ['postid' => $data['post'], 'userid' => $data['user1']->id]
        );
        $this->assertTrue($user1reading);

        $user2reading = $DB->record_exists(
            'longpage_post_readings',
            ['postid' => $data['post'], 'userid' => $data['user2']->id]
        );
        $this->assertTrue($user2reading);
    }

    /**
     * Test reactions are properly counted
     *
     * @covers \mod_longpage\external\post_interaction_services::create_post_like
     * @covers \mod_longpage\external\post_interaction_services::create_post_bookmark
     * @covers \mod_longpage\external\post_interaction_services::create_post_reading
     */
    public function test_reactions_counting(): void {
        global $DB;

        $data = $this->setup_test_data();

        // User 1 creates all reactions.
        $this->setUser($data['user1']);
        post_interaction_services::create_post_like($data['post']);
        post_interaction_services::create_post_bookmark($data['post']);
        post_interaction_services::create_post_reading($data['post']);

        // User 2 creates all reactions.
        $this->setUser($data['user2']);
        post_interaction_services::create_post_like($data['post']);
        post_interaction_services::create_post_bookmark($data['post']);
        post_interaction_services::create_post_reading($data['post']);

        // Verify counts.
        $likecount = $DB->count_records('longpage_post_likes', ['postid' => $data['post']]);
        $this->assertEquals(2, $likecount);

        $bookmarkcount = $DB->count_records('longpage_post_bookmarks', ['postid' => $data['post']]);
        $this->assertEquals(2, $bookmarkcount);

        $readingcount = $DB->count_records('longpage_post_readings', ['postid' => $data['post']]);
        $this->assertEquals(2, $readingcount);
    }

    /**
     * Test recommendation task is scheduled after creating like
     *
     * @covers \mod_longpage\external\post_interaction_services::create_post_like
     */
    public function test_recommendation_task_scheduled_after_like(): void {
        global $DB;

        $data = $this->setup_test_data();
        $this->setUser($data['user1']);

        // Clear any existing adhoc tasks.
        $DB->delete_records('task_adhoc');

        // Create like.
        post_interaction_services::create_post_like($data['post']);

        // Verify task was scheduled.
        $tasks = $DB->get_records('task_adhoc', [
            'classname' => '\\mod_longpage\\local\\post_recommendation\\post_recommendation_calculation_task',
        ]);
        $this->assertNotEmpty($tasks);

        // Verify task contains correct page ID.
        $task = reset($tasks);
        $customdata = json_decode($task->customdata, true);
        $this->assertEquals($data['longpage']->id, $customdata['pageid']);
    }

    /**
     * Test recommendation task is scheduled after deleting like
     *
     * @covers \mod_longpage\external\post_interaction_services::delete_post_like
     */
    public function test_recommendation_task_scheduled_after_delete_like(): void {
        global $DB;

        $data = $this->setup_test_data();
        $this->setUser($data['user1']);

        // Create like first.
        post_interaction_services::create_post_like($data['post']);

        // Clear any existing adhoc tasks.
        $DB->delete_records('task_adhoc');

        // Delete like.
        post_interaction_services::delete_post_like($data['post']);

        // Verify task was scheduled.
        $tasks = $DB->get_records('task_adhoc', [
            'classname' => '\\mod_longpage\\local\\post_recommendation\\post_recommendation_calculation_task',
        ]);
        $this->assertNotEmpty($tasks);
    }

    /**
     * Test recommendation task is scheduled after creating bookmark
     *
     * @covers \mod_longpage\external\post_interaction_services::create_post_bookmark
     */
    public function test_recommendation_task_scheduled_after_bookmark(): void {
        global $DB;

        $data = $this->setup_test_data();
        $this->setUser($data['user1']);

        // Clear any existing adhoc tasks.
        $DB->delete_records('task_adhoc');

        // Create bookmark.
        post_interaction_services::create_post_bookmark($data['post']);

        // Verify task was scheduled.
        $tasks = $DB->get_records('task_adhoc', [
            'classname' => '\\mod_longpage\\local\\post_recommendation\\post_recommendation_calculation_task',
        ]);
        $this->assertNotEmpty($tasks);
    }

    /**
     * Test recommendation task is scheduled after creating reading
     *
     * @covers \mod_longpage\external\post_interaction_services::create_post_reading
     */
    public function test_recommendation_task_scheduled_after_reading(): void {
        global $DB;

        $data = $this->setup_test_data();
        $this->setUser($data['user1']);

        // Clear any existing adhoc tasks.
        $DB->delete_records('task_adhoc');

        // Create reading.
        post_interaction_services::create_post_reading($data['post']);

        // Verify task was scheduled.
        $tasks = $DB->get_records('task_adhoc', [
            'classname' => '\\mod_longpage\\local\\post_recommendation\\post_recommendation_calculation_task',
        ]);
        $this->assertNotEmpty($tasks);
    }

    /**
     * Test unauthenticated user cannot create like
     *
     * @covers \mod_longpage\external\post_interaction_services::create_post_like
     */
    public function test_unauthenticated_user_cannot_create_like(): void {
        $data = $this->setup_test_data();

        // Set guest user.
        $this->setGuestUser();

        // Attempt to create like should fail.
        $this->expectException(\moodle_exception::class);
        post_interaction_services::create_post_like($data['post']);
    }

    /**
     * Test unenrolled user cannot create like
     *
     * @covers \mod_longpage\external\post_interaction_services::create_post_like
     */
    public function test_unenrolled_user_cannot_create_like(): void {
        $data = $this->setup_test_data();

        // Create user not enrolled in course.
        $user3 = $this->getDataGenerator()->create_user();
        $this->setUser($user3);

        // Attempt to create like should fail.
        $this->expectException(\moodle_exception::class);
        post_interaction_services::create_post_like($data['post']);
    }

    /**
     * Test invalid post ID throws exception for like
     *
     * @covers \mod_longpage\external\post_interaction_services::create_post_like
     */
    public function test_invalid_post_id_create_like(): void {
        $data = $this->setup_test_data();
        $this->setUser($data['user1']);

        // Attempt to create like with invalid post ID.
        $this->expectException(\dml_missing_record_exception::class);
        post_interaction_services::create_post_like(99999);
    }

    /**
     * Test invalid post ID throws exception for bookmark
     *
     * @covers \mod_longpage\external\post_interaction_services::create_post_bookmark
     */
    public function test_invalid_post_id_create_bookmark(): void {
        $data = $this->setup_test_data();
        $this->setUser($data['user1']);

        // Attempt to create bookmark with invalid post ID.
        $this->expectException(\dml_missing_record_exception::class);
        post_interaction_services::create_post_bookmark(99999);
    }

    /**
     * Test invalid post ID throws exception for reading
     *
     * @covers \mod_longpage\external\post_interaction_services::create_post_reading
     */
    public function test_invalid_post_id_create_reading(): void {
        $data = $this->setup_test_data();
        $this->setUser($data['user1']);

        // Attempt to create reading with invalid post ID.
        $this->expectException(\dml_missing_record_exception::class);
        post_interaction_services::create_post_reading(99999);
    }

    /**
     * Test invalid post ID throws exception for delete like
     *
     * @covers \mod_longpage\external\post_interaction_services::delete_post_like
     */
    public function test_invalid_post_id_delete_like(): void {
        $data = $this->setup_test_data();
        $this->setUser($data['user1']);

        // Attempt to delete like with invalid post ID.
        $this->expectException(\dml_missing_record_exception::class);
        post_interaction_services::delete_post_like(99999);
    }

    /**
     * Test deleting non-existent like does not throw error
     *
     * @covers \mod_longpage\external\post_interaction_services::delete_post_like
     */
    public function test_delete_nonexistent_like(): void {
        global $DB;

        $data = $this->setup_test_data();
        $this->setUser($data['user1']);

        // Verify no like exists.
        $this->assertFalse($DB->record_exists('longpage_post_likes', ['postid' => $data['post'], 'userid' => $data['user1']->id]));

        // Delete should not throw error.
        post_interaction_services::delete_post_like($data['post']);

        // Still no like exists.
        $this->assertFalse($DB->record_exists('longpage_post_likes', ['postid' => $data['post'], 'userid' => $data['user1']->id]));
    }

    /**
     * Test deleting non-existent bookmark does not throw error
     *
     * @covers \mod_longpage\external\post_interaction_services::delete_post_bookmark
     */
    public function test_delete_nonexistent_bookmark(): void {
        global $DB;

        $data = $this->setup_test_data();
        $this->setUser($data['user1']);

        // Verify no bookmark exists.
        $this->assertFalse($DB->record_exists(
            'longpage_post_bookmarks',
            ['postid' => $data['post'], 'userid' => $data['user1']->id]
        ));

        // Delete should not throw error.
        post_interaction_services::delete_post_bookmark($data['post']);

        // Still no bookmark exists.
        $this->assertFalse($DB->record_exists(
            'longpage_post_bookmarks',
            ['postid' => $data['post'], 'userid' => $data['user1']->id]
        ));
    }

    /**
     * Test deleting non-existent reading does not throw error
     *
     * @covers \mod_longpage\external\post_interaction_services::delete_post_reading
     */
    public function test_delete_nonexistent_reading(): void {
        global $DB;

        $data = $this->setup_test_data();
        $this->setUser($data['user1']);

        // Verify no reading exists.
        $this->assertFalse($DB->record_exists(
            'longpage_post_readings',
            ['postid' => $data['post'], 'userid' => $data['user1']->id]
        ));

        // Delete should not throw error.
        post_interaction_services::delete_post_reading($data['post']);

        // Still no reading exists.
        $this->assertFalse($DB->record_exists(
            'longpage_post_readings',
            ['postid' => $data['post'], 'userid' => $data['user1']->id]
        ));
    }

    /**
     * Test user can only delete their own like
     *
     * @covers \mod_longpage\external\post_interaction_services::delete_post_like
     */
    public function test_user_can_only_delete_own_like(): void {
        global $DB;

        $data = $this->setup_test_data();

        // User 1 creates like.
        $this->setUser($data['user1']);
        post_interaction_services::create_post_like($data['post']);

        // User 2 tries to delete, but should only delete their own (non-existent) like.
        $this->setUser($data['user2']);
        post_interaction_services::delete_post_like($data['post']);

        // User 1's like should still exist.
        $this->assertTrue($DB->record_exists('longpage_post_likes', ['postid' => $data['post'], 'userid' => $data['user1']->id]));

        // Now User 1 deletes their own like.
        $this->setUser($data['user1']);
        post_interaction_services::delete_post_like($data['post']);

        // User 1's like should now be deleted.
        $this->assertFalse($DB->record_exists('longpage_post_likes', ['postid' => $data['post'], 'userid' => $data['user1']->id]));
    }

    /**
     * Test user can only delete their own bookmark
     *
     * @covers \mod_longpage\external\post_interaction_services::delete_post_bookmark
     */
    public function test_user_can_only_delete_own_bookmark(): void {
        global $DB;

        $data = $this->setup_test_data();

        // User 1 creates bookmark.
        $this->setUser($data['user1']);
        post_interaction_services::create_post_bookmark($data['post']);

        // User 2 tries to delete, but should only delete their own (non-existent) bookmark.
        $this->setUser($data['user2']);
        post_interaction_services::delete_post_bookmark($data['post']);

        // User 1's bookmark should still exist.
        $this->assertTrue($DB->record_exists(
            'longpage_post_bookmarks',
            ['postid' => $data['post'], 'userid' => $data['user1']->id]
        ));

        // Now User 1 deletes their own bookmark.
        $this->setUser($data['user1']);
        post_interaction_services::delete_post_bookmark($data['post']);

        // User 1's bookmark should now be deleted.
        $this->assertFalse($DB->record_exists(
            'longpage_post_bookmarks',
            ['postid' => $data['post'], 'userid' => $data['user1']->id]
        ));
    }

    /**
     * Test user can only delete their own reading
     *
     * @covers \mod_longpage\external\post_interaction_services::delete_post_reading
     */
    public function test_user_can_only_delete_own_reading(): void {
        global $DB;

        $data = $this->setup_test_data();

        // User 1 marks post as read.
        $this->setUser($data['user1']);
        post_interaction_services::create_post_reading($data['post']);

        // User 2 tries to delete, but should only delete their own (non-existent) reading.
        $this->setUser($data['user2']);
        post_interaction_services::delete_post_reading($data['post']);

        // User 1's reading should still exist.
        $this->assertTrue($DB->record_exists(
            'longpage_post_readings',
            ['postid' => $data['post'], 'userid' => $data['user1']->id]
        ));

        // Now User 1 deletes their own reading.
        $this->setUser($data['user1']);
        post_interaction_services::delete_post_reading($data['post']);

        // User 1's reading should now be deleted.
        $this->assertFalse($DB->record_exists(
            'longpage_post_readings',
            ['postid' => $data['post'], 'userid' => $data['user1']->id]
        ));
    }
}
