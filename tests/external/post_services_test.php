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
 * Unit tests for post_services external API
 *
 * @package    mod_longpage
 * @category   test
 * @copyright  2024 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_longpage\external;

use mod_longpage\external\post_services;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/mod/longpage/locallib.php');

/**
 * Unit tests for post_services external API
 *
 * @package    mod_longpage
 * @category   test
 * @copyright  2024 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_longpage\external\post_services
 * @runTestsInSeparateProcesses
 */
final class post_services_test extends \externallib_advanced_testcase {
    /** @var stdClass Course object */
    private $course;

    /** @var stdClass Longpage module instance */
    private $longpage;

    /** @var stdClass Course module */
    private $cm;

    /** @var stdClass Student user */
    private $student;

    /** @var stdClass Teacher user */
    private $teacher;

    /** @var stdClass Annotation record */
    private $annotation;

    /** @var stdClass Thread record */
    private $thread;

    /**
     * Set up test data
     */
    protected function setUp(): void {
        global $DB;
        parent::setUp();

        $this->resetAfterTest(true);

        // Create course.
        $this->course = $this->getDataGenerator()->create_course();

        // Create longpage instance.
        $this->longpage = $this->getDataGenerator()->create_module('longpage', [
            'course' => $this->course->id,
            'name' => 'Test Longpage',
            'content' => 'Test content for discussion',
        ]);

        $this->cm = get_coursemodule_from_instance('longpage', $this->longpage->id, $this->course->id);

        // Create users.
        $this->student = $this->getDataGenerator()->create_user();
        $this->teacher = $this->getDataGenerator()->create_user();

        // Enrol users.
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);

        $this->getDataGenerator()->enrol_user($this->student->id, $this->course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($this->teacher->id, $this->course->id, $teacherrole->id);

        // Create annotation.
        $this->annotation = $DB->insert_record('longpage_annotations', [
            'creatorid' => $this->student->id,
            'longpageid' => $this->longpage->id,
            'type' => 1,
            'ispublic' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        // Create thread.
        $this->thread = $DB->insert_record('longpage_threads', [
            'annotationid' => $this->annotation,
            'replyrequested' => 0,
        ]);
    }

    /**
     * Test creating a first post in a thread (thread root)
     *
     * @covers \mod_longpage\external\post_services::create_post
     */
    public function test_create_post_first_in_thread(): void {
        global $DB;

        $this->setUser($this->student);

        $postdata = [
            'threadid' => $this->thread,
            'longpageid' => $this->longpage->id,
            'content' => 'This is the first post in the thread',
            'anonymous' => false,
            'ispublic' => true,
        ];

        $result = post_services::create_post($postdata);

        // Verify result structure.
        $this->assertIsArray($result);
        $this->assertArrayHasKey('post', $result);
        $this->assertIsObject($result['post']);

        // Verify post was created in database.
        $post = $DB->get_record('longpage_posts', ['threadid' => $this->thread]);
        $this->assertNotFalse($post);
        $this->assertEquals($this->thread, $post->threadid);
        $this->assertEquals('This is the first post in the thread', $post->content);
        $this->assertEquals($this->student->id, $post->creatorid);
        $this->assertEquals(0, $post->anonymous);
        $this->assertEquals(1, $post->ispublic);

        // Verify post reading was created automatically.
        $reading = $DB->get_record('longpage_post_readings', [
            'postid' => $post->id,
            'userid' => $this->student->id,
        ]);
        $this->assertNotFalse($reading);

        // Verify annotation was updated.
        $annotation = $DB->get_record('longpage_annotations', ['id' => $this->annotation]);
        $this->assertEquals(1, $annotation->ispublic);
    }

    /**
     * Test creating a reply post
     *
     * @covers \mod_longpage\external\post_services::create_post
     */
    public function test_create_post_reply(): void {
        global $DB;

        $this->setUser($this->student);

        // Create first post.
        $firstpost = $DB->insert_record('longpage_posts', [
            'threadid' => $this->thread,
            'longpageid' => $this->longpage->id,
            'creatorid' => $this->student->id,
            'content' => 'First post',
            'anonymous' => 0,
            'ispublic' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        // Create reply post.
        $this->setUser($this->teacher);

        $postdata = [
            'threadid' => $this->thread,
            'longpageid' => $this->longpage->id,
            'content' => 'This is a reply to the first post',
            'anonymous' => false,
            'ispublic' => true,
        ];

        $result = post_services::create_post($postdata);

        // Verify reply was created.
        $posts = $DB->get_records('longpage_posts', ['threadid' => $this->thread], 'timecreated ASC');
        $this->assertCount(2, $posts);

        $replypost = end($posts);
        $this->assertEquals('This is a reply to the first post', $replypost->content);
        $this->assertEquals($this->teacher->id, $replypost->creatorid);
    }

    /**
     * Test creating an anonymous post
     *
     * @covers \mod_longpage\external\post_services::create_post
     */
    public function test_create_post_anonymous(): void {
        global $DB;

        $this->setUser($this->student);

        $postdata = [
            'threadid' => $this->thread,
            'longpageid' => $this->longpage->id,
            'content' => 'This is an anonymous post',
            'anonymous' => true,
            'ispublic' => true,
        ];

        $result = post_services::create_post($postdata);

        // Verify post was created as anonymous.
        $post = $DB->get_record('longpage_posts', ['threadid' => $this->thread]);
        $this->assertEquals(1, $post->anonymous);
        $this->assertEquals($this->student->id, $post->creatorid); // Creator is still stored in DB.

        // Verify response - creatorid should be removed for anonymous posts.
        $this->setUser($this->teacher);
        $posts = $DB->get_records('longpage_posts', ['threadid' => $this->thread]);
        // Note: Anonymization happens in get_posts() when returning data.
    }

    /**
     * Test creating a private post
     *
     * @covers \mod_longpage\external\post_services::create_post
     */
    public function test_create_post_private(): void {
        global $DB;

        $this->setUser($this->student);

        $postdata = [
            'threadid' => $this->thread,
            'longpageid' => $this->longpage->id,
            'content' => 'This is a private post',
            'anonymous' => false,
            'ispublic' => false,
        ];

        $result = post_services::create_post($postdata);

        // Verify post was created as private.
        $post = $DB->get_record('longpage_posts', ['threadid' => $this->thread]);
        $this->assertEquals(0, $post->ispublic);

        // Verify annotation reflects that thread contains only private posts.
        $annotation = $DB->get_record('longpage_annotations', ['id' => $this->annotation]);
        $this->assertEquals(0, $annotation->ispublic);
    }

    /**
     * Test update post content
     *
     * @covers \mod_longpage\external\post_services::update_post
     */
    public function test_update_post_content(): void {
        global $DB;

        $this->setUser($this->student);

        // Create initial post.
        $postdata = [
            'threadid' => $this->thread,
            'longpageid' => $this->longpage->id,
            'content' => 'Initial content',
            'anonymous' => false,
            'ispublic' => true,
        ];

        $createresult = post_services::create_post($postdata);
        $postid = $createresult['post']->id;

        // Update post content.
        $updatedata = [
            'id' => $postid,
            'content' => 'Updated content',
        ];

        $result = post_services::update_post($updatedata);

        // Verify update.
        $this->assertIsArray($result);
        $this->assertArrayHasKey('post', $result);

        $updatedpost = $DB->get_record('longpage_posts', ['id' => $postid]);
        $this->assertEquals('Updated content', $updatedpost->content);

        // Verify post readings were reset for this post.
        $readingscount = $DB->count_records('longpage_post_readings', ['postid' => $postid]);
        $this->assertEquals(1, $readingscount); // Only creator's reading should remain.

        // Verify reading exists for creator after update.
        $reading = $DB->get_record('longpage_post_readings', [
            'postid' => $postid,
            'userid' => $this->student->id,
        ]);
        $this->assertNotFalse($reading);
    }

    /**
     * Test update post visibility
     *
     * @covers \mod_longpage\external\post_services::update_post
     */
    public function test_update_post_visibility(): void {
        global $DB;

        $this->setUser($this->student);

        // Create public post.
        $postdata = [
            'threadid' => $this->thread,
            'longpageid' => $this->longpage->id,
            'content' => 'Test content',
            'anonymous' => false,
            'ispublic' => true,
        ];

        $createresult = post_services::create_post($postdata);
        $postid = $createresult['post']->id;

        // Verify annotation is public.
        $annotation = $DB->get_record('longpage_annotations', ['id' => $this->annotation]);
        $this->assertEquals(1, $annotation->ispublic);

        // Update post to private (if it's the last post).
        $updatedata = [
            'id' => $postid,
            'ispublic' => false,
        ];

        $result = post_services::update_post($updatedata);

        // Verify post visibility was updated.
        $updatedpost = $DB->get_record('longpage_posts', ['id' => $postid]);
        $this->assertEquals(0, $updatedpost->ispublic);

        // Verify annotation visibility was updated.
        $annotation = $DB->get_record('longpage_annotations', ['id' => $this->annotation]);
        $this->assertEquals(0, $annotation->ispublic);
    }

    /**
     * Test update post anonymous flag
     *
     * @covers \mod_longpage\external\post_services::update_post
     */
    public function test_update_post_anonymous_flag(): void {
        global $DB;

        $this->setUser($this->student);

        // Create non-anonymous post.
        $postdata = [
            'threadid' => $this->thread,
            'longpageid' => $this->longpage->id,
            'content' => 'Test content',
            'anonymous' => false,
            'ispublic' => true,
        ];

        $createresult = post_services::create_post($postdata);
        $postid = $createresult['post']->id;

        // Update to anonymous.
        $updatedata = [
            'id' => $postid,
            'anonymous' => true,
        ];

        $result = post_services::update_post($updatedata);

        // Verify anonymous flag was updated.
        $updatedpost = $DB->get_record('longpage_posts', ['id' => $postid]);
        $this->assertEquals(1, $updatedpost->anonymous);
    }

    /**
     * Test delete post as the last post in thread
     *
     * @covers \mod_longpage\external\post_services::delete_post
     */
    public function test_delete_post_last_in_thread(): void {
        global $DB;

        $this->setUser($this->student);

        // Create a post.
        $postid = $DB->insert_record('longpage_posts', [
            'threadid' => $this->thread,
            'longpageid' => $this->longpage->id,
            'creatorid' => $this->student->id,
            'content' => 'Post to be deleted',
            'anonymous' => 0,
            'ispublic' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        // Add some reactions to test cleanup.
        $DB->insert_record('longpage_post_likes', [
            'postid' => $postid,
            'userid' => $this->student->id,
            'timecreated' => time(),
        ]);

        $DB->insert_record('longpage_post_readings', [
            'postid' => $postid,
            'userid' => $this->student->id,
            'timecreated' => time(),
        ]);

        $DB->insert_record('longpage_post_bookmarks', [
            'postid' => $postid,
            'userid' => $this->student->id,
            'timecreated' => time(),
        ]);

        // Delete the post.
        post_services::delete_post($postid);

        // Verify post was deleted.
        $post = $DB->get_record('longpage_posts', ['id' => $postid]);
        $this->assertFalse($post);

        // Verify reactions were cleaned up.
        $this->assertFalse($DB->record_exists('longpage_post_likes', ['postid' => $postid]));
        $this->assertFalse($DB->record_exists('longpage_post_readings', ['postid' => $postid]));
        $this->assertFalse($DB->record_exists('longpage_post_bookmarks', ['postid' => $postid]));
    }

    /**
     * Test that deleting a post with replies fails
     *
     * @covers \mod_longpage\external\post_services::delete_post
     */
    public function test_delete_post_with_replies_fails(): void {
        global $DB;

        $this->setUser($this->student);

        // Create first post.
        $firstpostid = $DB->insert_record('longpage_posts', [
            'threadid' => $this->thread,
            'longpageid' => $this->longpage->id,
            'creatorid' => $this->student->id,
            'content' => 'First post',
            'anonymous' => 0,
            'ispublic' => 1,
            'timecreated' => time() - 100,
            'timemodified' => time() - 100,
        ]);

        // Create reply post.
        $DB->insert_record('longpage_posts', [
            'threadid' => $this->thread,
            'longpageid' => $this->longpage->id,
            'creatorid' => $this->teacher->id,
            'content' => 'Reply post',
            'anonymous' => 0,
            'ispublic' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        // Try to delete first post (should fail).
        $this->expectException(\invalid_parameter_exception::class);
        $this->expectExceptionMessage('Only the last postIntern in a thread can be deleted');

        post_services::delete_post($firstpostid);
    }

    /**
     * Test creating post with mixed public/private posts in thread
     *
     * @covers \mod_longpage\external\post_services::create_post
     */
    public function test_create_post_mixed_visibility_in_thread(): void {
        global $DB;

        $this->setUser($this->student);

        // Create private post.
        $postdata1 = [
            'threadid' => $this->thread,
            'longpageid' => $this->longpage->id,
            'content' => 'Private post',
            'anonymous' => false,
            'ispublic' => false,
        ];

        post_services::create_post($postdata1);

        // Verify annotation is private.
        $annotation = $DB->get_record('longpage_annotations', ['id' => $this->annotation]);
        $this->assertEquals(0, $annotation->ispublic);

        // Create public post in same thread.
        $postdata2 = [
            'threadid' => $this->thread,
            'longpageid' => $this->longpage->id,
            'content' => 'Public post',
            'anonymous' => false,
            'ispublic' => true,
        ];

        post_services::create_post($postdata2);

        // Verify annotation is now public (has at least one public post).
        $annotation = $DB->get_record('longpage_annotations', ['id' => $this->annotation]);
        $this->assertEquals(1, $annotation->ispublic);
    }

    /**
     * Test parameter validation for create_post
     *
     * @covers \mod_longpage\external\post_services::create_post
     * @covers \mod_longpage\external\post_services::create_post_parameters
     */
    public function test_create_post_parameter_validation(): void {
        $this->setUser($this->student);

        // Test with missing required field.
        $this->expectException(\invalid_parameter_exception::class);

        post_services::create_post([
            'threadid' => $this->thread,
            // Missing longpageid.
            'content' => 'Test',
            'anonymous' => false,
        ]);
    }

    /**
     * Test parameter validation for update_post
     *
     * @covers \mod_longpage\external\post_services::update_post
     * @covers \mod_longpage\external\post_services::update_post_parameters
     */
    public function test_update_post_parameter_validation(): void {
        $this->setUser($this->student);

        // Test with missing required field.
        $this->expectException(\invalid_parameter_exception::class);

        post_services::update_post([
            // Missing id.
            'content' => 'Updated content',
        ]);
    }

    /**
     * Test parameter validation for delete_post
     *
     * @covers \mod_longpage\external\post_services::delete_post
     * @covers \mod_longpage\external\post_services::delete_post_parameters
     */
    public function test_delete_post_parameter_validation(): void {
        $this->setUser($this->student);

        // Test with invalid post ID.
        $this->expectException(\dml_missing_record_exception::class);

        post_services::delete_post(99999);
    }

    /**
     * Test that post reactions are preserved after update (when content unchanged)
     *
     * @covers \mod_longpage\external\post_services::update_post
     */
    public function test_update_post_preserves_reactions_when_content_unchanged(): void {
        global $DB;

        $this->setUser($this->student);

        // Create post.
        $postdata = [
            'threadid' => $this->thread,
            'longpageid' => $this->longpage->id,
            'content' => 'Test content',
            'anonymous' => false,
            'ispublic' => true,
        ];

        $createresult = post_services::create_post($postdata);
        $postid = $createresult['post']->id;

        // Add reactions from another user.
        $this->setUser($this->teacher);
        $DB->insert_record('longpage_post_likes', [
            'postid' => $postid,
            'userid' => $this->teacher->id,
            'timecreated' => time(),
        ]);

        $DB->insert_record('longpage_post_readings', [
            'postid' => $postid,
            'userid' => $this->teacher->id,
            'timecreated' => time(),
        ]);

        // Update post (not content, just visibility).
        $this->setUser($this->student);
        $updatedata = [
            'id' => $postid,
            'anonymous' => true,
        ];

        post_services::update_post($updatedata);

        // Verify reactions are still there.
        $this->assertTrue($DB->record_exists('longpage_post_likes', [
            'postid' => $postid,
            'userid' => $this->teacher->id,
        ]));

        $this->assertTrue($DB->record_exists('longpage_post_readings', [
            'postid' => $postid,
            'userid' => $this->teacher->id,
        ]));
    }

    /**
     * Test that updating post content clears other users' reading status
     *
     * @covers \mod_longpage\external\post_services::update_post
     */
    public function test_update_post_content_clears_readings(): void {
        global $DB;

        $this->setUser($this->student);

        // Create post.
        $postdata = [
            'threadid' => $this->thread,
            'longpageid' => $this->longpage->id,
            'content' => 'Original content',
            'anonymous' => false,
            'ispublic' => true,
        ];

        $createresult = post_services::create_post($postdata);
        $postid = $createresult['post']->id;

        // Teacher reads the post.
        $this->setUser($this->teacher);
        $DB->insert_record('longpage_post_readings', [
            'postid' => $postid,
            'userid' => $this->teacher->id,
            'timecreated' => time(),
        ]);

        // Verify teacher's reading exists.
        $this->assertTrue($DB->record_exists('longpage_post_readings', [
            'postid' => $postid,
            'userid' => $this->teacher->id,
        ]));

        // Student updates content.
        $this->setUser($this->student);
        $updatedata = [
            'id' => $postid,
            'content' => 'Updated content',
        ];

        post_services::update_post($updatedata);

        // Verify all readings were deleted, then only creator's reading was re-added.
        $readings = $DB->get_records('longpage_post_readings', ['postid' => $postid]);
        $this->assertCount(1, $readings);
        $reading = reset($readings);
        $this->assertEquals($this->student->id, $reading->userid);
    }

    /**
     * Test context validation for unauthorized access
     *
     * @covers \mod_longpage\external\post_services::create_post
     */
    public function test_create_post_requires_valid_context(): void {
        // Create a user not enrolled in the course.
        $unenrolleduser = $this->getDataGenerator()->create_user();
        $this->setUser($unenrolleduser);

        $postdata = [
            'threadid' => $this->thread,
            'longpageid' => $this->longpage->id,
            'content' => 'Unauthorized post',
            'anonymous' => false,
            'ispublic' => true,
        ];

        // Expect exception for unauthorized access.
        $this->expectException(\require_login_exception::class);

        post_services::create_post($postdata);
    }

    /**
     * Test deleting a thread root post (only post in thread)
     *
     * @covers \mod_longpage\external\post_services::delete_post
     */
    public function test_delete_thread_root_post(): void {
        global $DB;

        $this->setUser($this->student);

        // Create a single post (thread root).
        $postid = $DB->insert_record('longpage_posts', [
            'threadid' => $this->thread,
            'longpageid' => $this->longpage->id,
            'creatorid' => $this->student->id,
            'content' => 'Thread root post',
            'anonymous' => 0,
            'ispublic' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        // Delete the thread root.
        post_services::delete_post($postid);

        // Verify post was deleted.
        $this->assertFalse($DB->record_exists('longpage_posts', ['id' => $postid]));

        // Thread should still exist (thread management is separate).
        $this->assertTrue($DB->record_exists('longpage_threads', ['id' => $this->thread]));
    }

    /**
     * Test return structure of create_post
     *
     * @covers \mod_longpage\external\post_services::create_post
     * @covers \mod_longpage\external\post_services::create_post_returns
     */
    public function test_create_post_return_structure(): void {
        $this->setUser($this->student);

        $postdata = [
            'threadid' => $this->thread,
            'longpageid' => $this->longpage->id,
            'content' => 'Test post',
            'anonymous' => false,
            'ispublic' => true,
        ];

        $result = post_services::create_post($postdata);

        // Clean the result using external API.
        $result = \external_api::clean_returnvalue(
            post_services::create_post_returns(),
            $result
        );

        // Verify structure.
        $this->assertIsArray($result);
        $this->assertArrayHasKey('post', $result);

        $post = $result['post'];
        $this->assertArrayHasKey('id', $post);
        $this->assertArrayHasKey('threadid', $post);
        $this->assertArrayHasKey('content', $post);
        $this->assertArrayHasKey('anonymous', $post);
        $this->assertArrayHasKey('ispublic', $post);
        $this->assertArrayHasKey('timecreated', $post);
        $this->assertArrayHasKey('timemodified', $post);

        // Verify reaction counts are included.
        $this->assertArrayHasKey('likescount', $post);
        $this->assertArrayHasKey('likedbyuser', $post);
        $this->assertArrayHasKey('readingscount', $post);
        $this->assertArrayHasKey('readbyuser', $post);
        $this->assertArrayHasKey('bookmarkedbyuser', $post);
    }

    /**
     * Test return structure of update_post
     *
     * @covers \mod_longpage\external\post_services::update_post
     * @covers \mod_longpage\external\post_services::update_post_returns
     */
    public function test_update_post_return_structure(): void {
        global $DB;

        $this->setUser($this->student);

        // Create post.
        $postid = $DB->insert_record('longpage_posts', [
            'threadid' => $this->thread,
            'longpageid' => $this->longpage->id,
            'creatorid' => $this->student->id,
            'content' => 'Original content',
            'anonymous' => 0,
            'ispublic' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        // Update post.
        $updatedata = [
            'id' => $postid,
            'content' => 'Updated content',
        ];

        $result = post_services::update_post($updatedata);

        // Clean the result.
        $result = \external_api::clean_returnvalue(
            post_services::update_post_returns(),
            $result
        );

        // Verify structure (same as create_post).
        $this->assertIsArray($result);
        $this->assertArrayHasKey('post', $result);

        $post = $result['post'];
        $this->assertArrayHasKey('id', $post);
        $this->assertArrayHasKey('content', $post);
        $this->assertEquals('Updated content', $post['content']);
    }
}
