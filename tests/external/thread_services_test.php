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
 * Unit tests for thread_services external API
 *
 * @package    mod_longpage
 * @category   test
 * @copyright  2024 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_longpage\external;

use mod_longpage\external\thread_services;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/mod/longpage/locallib.php');

/**
 * Unit tests for thread_services external API
 *
 * @package    mod_longpage
 * @category   test
 * @copyright  2024 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_longpage\external\thread_services
 */
final class thread_services_test extends \externallib_advanced_testcase {
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

    /** @var stdClass Second student user */
    private $student2;

    /** @var stdClass Annotation record */
    private $annotation;

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
        $this->student2 = $this->getDataGenerator()->create_user();

        // Enrol users.
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);

        $this->getDataGenerator()->enrol_user($this->student->id, $this->course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($this->teacher->id, $this->course->id, $teacherrole->id);
        $this->getDataGenerator()->enrol_user($this->student2->id, $this->course->id, $studentrole->id);

        // Create annotation for thread testing.
        $this->annotation = $DB->insert_record('longpage_annotations', [
            'creatorid' => $this->student->id,
            'longpageid' => $this->longpage->id,
            'type' => 1,
            'ispublic' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
    }

    /**
     * Test creating a thread with a simple post
     *
     * @covers \mod_longpage\external\thread_services::create_thread
     */
    public function test_create_thread_simple(): void {
        global $DB;

        $this->setUser($this->student);

        $threadparameters = [
            'content' => 'This is my first thread post',
            'anonymous' => false,
            'ispublic' => true,
        ];

        thread_services::create_thread($threadparameters, $this->annotation, $this->longpage->id);

        // Verify thread was created.
        $threads = $DB->get_records('longpage_threads', ['annotationid' => $this->annotation]);
        $this->assertCount(1, $threads);

        $thread = reset($threads);
        $this->assertEquals($this->annotation, $thread->annotationid);
        $this->assertEquals(0, $thread->replyrequested);

        // Verify post was created in the thread.
        $posts = $DB->get_records('longpage_posts', ['threadid' => $thread->id]);
        $this->assertCount(1, $posts);

        $post = reset($posts);
        $this->assertEquals('This is my first thread post', $post->content);
        $this->assertEquals($this->student->id, $post->creatorid);
        $this->assertEquals(0, $post->anonymous);
        $this->assertEquals(1, $post->ispublic);

        // Verify thread subscription was created automatically.
        $subscription = $DB->get_record('longpage_thread_subs', [
            'threadid' => $thread->id,
            'userid' => $this->student->id,
        ]);
        $this->assertNotFalse($subscription);
    }

    /**
     * Test creating a thread with reply requested flag
     *
     * @covers \mod_longpage\external\thread_services::create_thread
     */
    public function test_create_thread_with_reply_requested(): void {
        global $DB;

        $this->setUser($this->student);

        $threadparameters = [
            'content' => 'I need help with this topic',
            'anonymous' => false,
            'ispublic' => true,
            'replyrequested' => true,
        ];

        thread_services::create_thread($threadparameters, $this->annotation, $this->longpage->id);

        // Verify thread was created with reply requested.
        $threads = $DB->get_records('longpage_threads', ['annotationid' => $this->annotation]);
        $this->assertCount(1, $threads);

        $thread = reset($threads);
        $this->assertEquals(1, $thread->replyrequested);
    }

    /**
     * Test creating a thread without reply requested
     *
     * @covers \mod_longpage\external\thread_services::create_thread
     */
    public function test_create_thread_without_reply_requested(): void {
        global $DB;

        $this->setUser($this->student);

        $threadparameters = [
            'content' => 'Just sharing my thoughts',
            'anonymous' => false,
            'ispublic' => true,
            'replyrequested' => false,
        ];

        thread_services::create_thread($threadparameters, $this->annotation, $this->longpage->id);

        // Verify thread was created without reply requested.
        $threads = $DB->get_records('longpage_threads', ['annotationid' => $this->annotation]);
        $this->assertCount(1, $threads);

        $thread = reset($threads);
        $this->assertEquals(0, $thread->replyrequested);
    }

    /**
     * Test creating a thread with anonymous post
     *
     * @covers \mod_longpage\external\thread_services::create_thread
     */
    public function test_create_thread_with_anonymous_post(): void {
        global $DB;

        $this->setUser($this->student);

        $threadparameters = [
            'content' => 'This is an anonymous post',
            'anonymous' => true,
            'ispublic' => true,
        ];

        thread_services::create_thread($threadparameters, $this->annotation, $this->longpage->id);

        // Verify thread was created.
        $threads = $DB->get_records('longpage_threads', ['annotationid' => $this->annotation]);
        $this->assertCount(1, $threads);

        $thread = reset($threads);

        // Verify post is marked as anonymous.
        $posts = $DB->get_records('longpage_posts', ['threadid' => $thread->id]);
        $post = reset($posts);
        $this->assertEquals(1, $post->anonymous);
        $this->assertEquals($this->student->id, $post->creatorid); // Creator still stored in DB.
    }

    /**
     * Test creating a thread with public post
     *
     * @covers \mod_longpage\external\thread_services::create_thread
     */
    public function test_create_thread_with_public_post(): void {
        global $DB;

        $this->setUser($this->student);

        $threadparameters = [
            'content' => 'This is a public post',
            'anonymous' => false,
            'ispublic' => true,
        ];

        thread_services::create_thread($threadparameters, $this->annotation, $this->longpage->id);

        // Verify post is public.
        $threads = $DB->get_records('longpage_threads', ['annotationid' => $this->annotation]);
        $thread = reset($threads);
        $posts = $DB->get_records('longpage_posts', ['threadid' => $thread->id]);
        $post = reset($posts);
        $this->assertEquals(1, $post->ispublic);

        // Verify annotation is marked as public.
        $annotation = $DB->get_record('longpage_annotations', ['id' => $this->annotation]);
        $this->assertEquals(1, $annotation->ispublic);
    }

    /**
     * Test creating a thread with private post
     *
     * @covers \mod_longpage\external\thread_services::create_thread
     */
    public function test_create_thread_with_private_post(): void {
        global $DB;

        $this->setUser($this->student);

        $threadparameters = [
            'content' => 'This is a private post',
            'anonymous' => false,
            'ispublic' => false,
        ];

        thread_services::create_thread($threadparameters, $this->annotation, $this->longpage->id);

        // Verify post is private.
        $threads = $DB->get_records('longpage_threads', ['annotationid' => $this->annotation]);
        $thread = reset($threads);
        $posts = $DB->get_records('longpage_posts', ['threadid' => $thread->id]);
        $post = reset($posts);
        $this->assertEquals(0, $post->ispublic);
    }

    /**
     * Test getting thread details
     *
     * @covers \mod_longpage\external\thread_services::get_thread
     */
    public function test_get_thread(): void {
        global $DB;

        $this->setUser($this->student);

        // Create a thread.
        $threadid = $DB->insert_record('longpage_threads', [
            'annotationid' => $this->annotation,
            'replyrequested' => 1,
        ]);

        // Create posts in the thread.
        $DB->insert_record('longpage_posts', [
            'threadid' => $threadid,
            'longpageid' => $this->longpage->id,
            'creatorid' => $this->student->id,
            'content' => 'First post',
            'anonymous' => 0,
            'ispublic' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $DB->insert_record('longpage_posts', [
            'threadid' => $threadid,
            'longpageid' => $this->longpage->id,
            'creatorid' => $this->teacher->id,
            'content' => 'Reply post',
            'anonymous' => 0,
            'ispublic' => 1,
            'timecreated' => time() + 1,
            'timemodified' => time() + 1,
        ]);

        // Create subscription.
        $DB->insert_record('longpage_thread_subs', [
            'threadid' => $threadid,
            'userid' => $this->student->id,
            'timecreated' => time(),
        ]);

        // Get thread.
        $thread = thread_services::get_thread($this->annotation);

        // Verify thread data.
        $this->assertEquals($threadid, $thread->id);
        $this->assertEquals($this->annotation, $thread->annotationid);
        $this->assertEquals(1, $thread->replyrequested);
        $this->assertTrue($thread->subscribedtobyuser);

        // Verify posts are included.
        $this->assertIsArray($thread->posts);
        $this->assertCount(2, $thread->posts);
    }

    /**
     * Test getting thread without subscription
     *
     * @covers \mod_longpage\external\thread_services::get_thread
     */
    public function test_get_thread_not_subscribed(): void {
        global $DB;

        $this->setUser($this->student);

        // Create a thread.
        $threadid = $DB->insert_record('longpage_threads', [
            'annotationid' => $this->annotation,
            'replyrequested' => 0,
        ]);

        // Create a post.
        $DB->insert_record('longpage_posts', [
            'threadid' => $threadid,
            'longpageid' => $this->longpage->id,
            'creatorid' => $this->teacher->id,
            'content' => 'Post from teacher',
            'anonymous' => 0,
            'ispublic' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        // Get thread (no subscription exists).
        $thread = thread_services::get_thread($this->annotation);

        // Verify user is not subscribed.
        $this->assertFalse($thread->subscribedtobyuser);
    }

    /**
     * Test getting thread with mixed public and private posts
     *
     * @covers \mod_longpage\external\thread_services::get_thread
     */
    public function test_get_thread_with_mixed_posts(): void {
        global $DB;

        // Create a thread.
        $threadid = $DB->insert_record('longpage_threads', [
            'annotationid' => $this->annotation,
            'replyrequested' => 0,
        ]);

        // Create public post from student.
        $DB->insert_record('longpage_posts', [
            'threadid' => $threadid,
            'longpageid' => $this->longpage->id,
            'creatorid' => $this->student->id,
            'content' => 'Public post',
            'anonymous' => 0,
            'ispublic' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        // Create private post from student2.
        $DB->insert_record('longpage_posts', [
            'threadid' => $threadid,
            'longpageid' => $this->longpage->id,
            'creatorid' => $this->student2->id,
            'content' => 'Private post from student2',
            'anonymous' => 0,
            'ispublic' => 0,
            'timecreated' => time() + 1,
            'timemodified' => time() + 1,
        ]);

        // Get thread as student (not creator of private post).
        $this->setUser($this->student);
        $thread = thread_services::get_thread($this->annotation);

        // Should only see public post.
        $this->assertCount(1, $thread->posts);
        $this->assertEquals('Public post', $thread->posts[0]->content);

        // Get thread as student2 (creator of private post).
        $this->setUser($this->student2);
        $thread = thread_services::get_thread($this->annotation);

        // Should see both posts.
        $this->assertCount(2, $thread->posts);
    }

    /**
     * Test creating thread subscription
     *
     * @covers \mod_longpage\external\thread_services::create_thread_subscription
     */
    public function test_create_thread_subscription(): void {
        global $DB;

        $this->setUser($this->student);

        // Create a thread.
        $threadid = $DB->insert_record('longpage_threads', [
            'annotationid' => $this->annotation,
            'replyrequested' => 0,
        ]);

        // Create subscription.
        thread_services::create_thread_subscription($threadid);

        // Verify subscription was created.
        $subscription = $DB->get_record('longpage_thread_subs', [
            'threadid' => $threadid,
            'userid' => $this->student->id,
        ]);
        $this->assertNotFalse($subscription);
        $this->assertEquals($threadid, $subscription->threadid);
        $this->assertEquals($this->student->id, $subscription->userid);
        $this->assertGreaterThan(0, $subscription->timecreated);
    }

    /**
     * Test creating thread subscription is idempotent
     *
     * @covers \mod_longpage\external\thread_services::create_thread_subscription
     */
    public function test_create_thread_subscription_idempotent(): void {
        global $DB;

        $this->setUser($this->student);

        // Create a thread.
        $threadid = $DB->insert_record('longpage_threads', [
            'annotationid' => $this->annotation,
            'replyrequested' => 0,
        ]);

        // Create subscription twice.
        thread_services::create_thread_subscription($threadid);
        thread_services::create_thread_subscription($threadid);

        // Verify only one subscription exists.
        $subscriptions = $DB->get_records('longpage_thread_subs', [
            'threadid' => $threadid,
            'userid' => $this->student->id,
        ]);
        $this->assertCount(1, $subscriptions);
    }

    /**
     * Test multiple users can subscribe to same thread
     *
     * @covers \mod_longpage\external\thread_services::create_thread_subscription
     */
    public function test_create_thread_subscription_multiple_users(): void {
        global $DB;

        // Create a thread.
        $threadid = $DB->insert_record('longpage_threads', [
            'annotationid' => $this->annotation,
            'replyrequested' => 0,
        ]);

        // Student subscribes.
        $this->setUser($this->student);
        thread_services::create_thread_subscription($threadid);

        // Teacher subscribes.
        $this->setUser($this->teacher);
        thread_services::create_thread_subscription($threadid);

        // Verify both subscriptions exist.
        $subscriptions = $DB->get_records('longpage_thread_subs', ['threadid' => $threadid]);
        $this->assertCount(2, $subscriptions);
    }

    /**
     * Test deleting thread subscription
     *
     * @covers \mod_longpage\external\thread_services::delete_thread_subscription
     */
    public function test_delete_thread_subscription(): void {
        global $DB;

        $this->setUser($this->student);

        // Create a thread.
        $threadid = $DB->insert_record('longpage_threads', [
            'annotationid' => $this->annotation,
            'replyrequested' => 0,
        ]);

        // Create subscription.
        $DB->insert_record('longpage_thread_subs', [
            'threadid' => $threadid,
            'userid' => $this->student->id,
            'timecreated' => time(),
        ]);

        // Verify subscription exists.
        $this->assertTrue($DB->record_exists('longpage_thread_subs', [
            'threadid' => $threadid,
            'userid' => $this->student->id,
        ]));

        // Delete subscription.
        thread_services::delete_thread_subscription($threadid);

        // Verify subscription was deleted.
        $this->assertFalse($DB->record_exists('longpage_thread_subs', [
            'threadid' => $threadid,
            'userid' => $this->student->id,
        ]));
    }

    /**
     * Test deleting non-existent subscription does not error
     *
     * @covers \mod_longpage\external\thread_services::delete_thread_subscription
     */
    public function test_delete_thread_subscription_not_exists(): void {
        global $DB;

        $this->setUser($this->student);

        // Create a thread.
        $threadid = $DB->insert_record('longpage_threads', [
            'annotationid' => $this->annotation,
            'replyrequested' => 0,
        ]);

        // Try to delete non-existent subscription (should not error).
        thread_services::delete_thread_subscription($threadid);

        // Verify no subscription exists.
        $this->assertFalse($DB->record_exists('longpage_thread_subs', [
            'threadid' => $threadid,
            'userid' => $this->student->id,
        ]));
    }

    /**
     * Test deleting subscription only affects current user
     *
     * @covers \mod_longpage\external\thread_services::delete_thread_subscription
     */
    public function test_delete_thread_subscription_only_current_user(): void {
        global $DB;

        // Create a thread.
        $threadid = $DB->insert_record('longpage_threads', [
            'annotationid' => $this->annotation,
            'replyrequested' => 0,
        ]);

        // Create subscriptions for both users.
        $DB->insert_record('longpage_thread_subs', [
            'threadid' => $threadid,
            'userid' => $this->student->id,
            'timecreated' => time(),
        ]);
        $DB->insert_record('longpage_thread_subs', [
            'threadid' => $threadid,
            'userid' => $this->teacher->id,
            'timecreated' => time(),
        ]);

        // Delete student's subscription.
        $this->setUser($this->student);
        thread_services::delete_thread_subscription($threadid);

        // Verify only student's subscription was deleted.
        $this->assertFalse($DB->record_exists('longpage_thread_subs', [
            'threadid' => $threadid,
            'userid' => $this->student->id,
        ]));
        $this->assertTrue($DB->record_exists('longpage_thread_subs', [
            'threadid' => $threadid,
            'userid' => $this->teacher->id,
        ]));
    }

    /**
     * Test automatic subscription when creating thread
     *
     * @covers \mod_longpage\external\thread_services::create_thread
     */
    public function test_automatic_subscription_on_thread_creation(): void {
        global $DB;

        $this->setUser($this->student);

        $threadparameters = [
            'content' => 'Thread post',
            'anonymous' => false,
            'ispublic' => true,
        ];

        thread_services::create_thread($threadparameters, $this->annotation, $this->longpage->id);

        // Get the created thread.
        $threads = $DB->get_records('longpage_threads', ['annotationid' => $this->annotation]);
        $thread = reset($threads);

        // Verify automatic subscription was created.
        $subscription = $DB->get_record('longpage_thread_subs', [
            'threadid' => $thread->id,
            'userid' => $this->student->id,
        ]);
        $this->assertNotFalse($subscription);
    }

    /**
     * Test permissions for enrolled users only
     *
     * @covers \mod_longpage\external\thread_services::create_thread_subscription
     */
    public function test_create_subscription_requires_enrollment(): void {
        global $DB;

        // Create unenrolled user.
        $unenrolleduser = $this->getDataGenerator()->create_user();
        $this->setUser($unenrolleduser);

        // Create a thread.
        $threadid = $DB->insert_record('longpage_threads', [
            'annotationid' => $this->annotation,
            'replyrequested' => 0,
        ]);

        // Try to create subscription as unenrolled user.
        $this->expectException(\require_login_exception::class);
        thread_services::create_thread_subscription($threadid);
    }

    /**
     * Test permissions for deletion
     *
     * @covers \mod_longpage\external\thread_services::delete_thread_subscription
     */
    public function test_delete_subscription_requires_enrollment(): void {
        global $DB;

        // Create unenrolled user.
        $unenrolleduser = $this->getDataGenerator()->create_user();
        $this->setUser($unenrolleduser);

        // Create a thread.
        $threadid = $DB->insert_record('longpage_threads', [
            'annotationid' => $this->annotation,
            'replyrequested' => 0,
        ]);

        // Try to delete subscription as unenrolled user.
        $this->expectException(\require_login_exception::class);
        thread_services::delete_thread_subscription($threadid);
    }

    /**
     * Test handling invalid thread ID
     *
     * @covers \mod_longpage\external\thread_services::create_thread_subscription
     */
    public function test_create_subscription_invalid_thread_id(): void {
        $this->setUser($this->student);

        // Try to create subscription with invalid thread ID.
        $this->expectException(\dml_missing_record_exception::class);
        thread_services::create_thread_subscription(999999);
    }

    /**
     * Test handling invalid thread ID for deletion
     *
     * @covers \mod_longpage\external\thread_services::delete_thread_subscription
     */
    public function test_delete_subscription_invalid_thread_id(): void {
        $this->setUser($this->student);

        // Try to delete subscription with invalid thread ID.
        $this->expectException(\dml_missing_record_exception::class);
        thread_services::delete_thread_subscription(999999);
    }
}
