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
 * External annotation_services unit tests
 *
 * @package    mod_longpage
 * @category   external
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_longpage\external;

use mod_longpage\local\constants\annotation_type;
use mod_longpage\local\constants\selector;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/mod/longpage/locallib.php');

/**
 * External annotation_services unit tests
 *
 * @package    mod_longpage
 * @category   external
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_longpage\external\annotation_services
 * @runTestsInSeparateProcesses
 */
final class annotation_services_test extends \externallib_advanced_testcase {
    /** @var \stdClass Course object */
    private $course;

    /** @var \stdClass Longpage module instance */
    private $longpage;

    /** @var \stdClass Course module object */
    private $cm;

    /** @var \context_module Module context */
    private $context;

    /** @var \stdClass Student user */
    private $student;

    /** @var \stdClass Teacher user */
    private $teacher;

    /** @var \stdClass Another student user */
    private $student2;

    /**
     * Recursively convert stdClass objects to arrays.
     *
     * @param mixed $data Data to convert
     * @return mixed Converted data
     */
    private function to_array($data) {
        if (is_object($data)) {
            $data = (array) $data;
        }
        if (is_array($data)) {
            return array_map([$this, 'to_array'], $data);
        }
        return $data;
    }

    /**
     * Set up test data
     */
    protected function setUp(): void {
        global $DB;
        parent::setUp();
        $this->resetAfterTest(true);

        // Create course.
        $this->course = $this->getDataGenerator()->create_course();

        // Create longpage module.
        $this->longpage = $this->getDataGenerator()->create_module('longpage', [
            'course' => $this->course->id,
            'name' => 'Test Longpage',
            'content' => '<p>Test content for annotations</p>',
        ]);

        $this->cm = get_coursemodule_from_instance('longpage', $this->longpage->id);
        $this->context = \context_module::instance($this->cm->id);

        // Create users.
        $this->student = $this->getDataGenerator()->create_user();
        $this->student2 = $this->getDataGenerator()->create_user();
        $this->teacher = $this->getDataGenerator()->create_user();

        // Enrol users.
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);

        $this->getDataGenerator()->enrol_user($this->student->id, $this->course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($this->student2->id, $this->course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($this->teacher->id, $this->course->id, $teacherrole->id);
    }

    /**
     * Test creating a highlight annotation successfully
     *
     * @covers \mod_longpage\external\annotation_services::create_annotation
     */
    public function test_create_annotation_highlight_success(): void {
        global $DB;

        $this->setUser($this->student);

        $annotation = [
            'longpageid' => $this->longpage->id,
            'type' => annotation_type::HIGHLIGHT,
            'ispublic' => true,
            'target' => [
                'styleclass' => 'highlight-yellow',
                'selectors' => [
                    [
                        'type' => selector::TYPE_TEXT_QUOTE_SELECTOR,
                        'exact' => 'Test content',
                        'prefix' => 'Start of ',
                        'suffix' => ' for testing',
                    ],
                ],
            ],
        ];

        $result = annotation_services::create_annotation($annotation);
        $result = $this->to_array($result);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('annotation', $result);
        $this->assertNotEmpty($result['annotation']);

        $created = $result['annotation'];
        $this->assertEquals($this->longpage->id, $created['longpageid']);
        $this->assertEquals(annotation_type::HIGHLIGHT, $created['type']);
        $this->assertEquals(1, $created['ispublic']);
        $this->assertEquals($this->student->id, $created['creatorid']);

        // Verify target was created.
        $this->assertArrayHasKey('target', $created);
        $this->assertEquals('highlight-yellow', $created['target']['styleclass']);

        // Verify selectors were created.
        $this->assertArrayHasKey('selectors', $created['target']);
        $this->assertCount(1, $created['target']['selectors']);
        $this->assertEquals('Test content', $created['target']['selectors'][0]['exact']);

        // Verify database records.
        $dbannotation = $DB->get_record('longpage_annotations', ['id' => $created['id']]);
        $this->assertNotFalse($dbannotation);
        $this->assertEquals($this->student->id, $dbannotation->creatorid);
    }

    /**
     * Test creating a post annotation successfully
     *
     * @covers \mod_longpage\external\annotation_services::create_annotation
     */
    public function test_create_annotation_post_success(): void {
        global $DB;

        $this->setUser($this->student);

        $annotation = [
            'longpageid' => $this->longpage->id,
            'type' => annotation_type::POST,
            'ispublic' => true,
            'target' => [
                'styleclass' => 'post-marker',
                'selectors' => [
                    [
                        'type' => selector::TYPE_TEXT_POSITION_SELECTOR,
                        'startposition' => 0,
                        'endposition' => 10,
                    ],
                ],
            ],
            'body' => [
                'anonymous' => false,
                'content' => 'This is a test comment',
                'ispublic' => true,
                'replyrequested' => false,
            ],
        ];

        $result = annotation_services::create_annotation($annotation);
        $result = $this->to_array($result);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('annotation', $result);

        $created = $result['annotation'];
        $this->assertEquals(annotation_type::POST, $created['type']);
        $this->assertArrayHasKey('body', $created);

        // Verify thread was created.
        $thread = $DB->get_record('longpage_threads', ['annotationid' => $created['id']]);
        $this->assertNotFalse($thread);

        // Verify post was created.
        $post = $DB->get_record('longpage_posts', ['threadid' => $thread->id]);
        $this->assertNotFalse($post);
        $this->assertEquals('This is a test comment', $post->content);
    }

    /**
     * Test creating a private annotation
     *
     * @covers \mod_longpage\external\annotation_services::create_annotation
     */
    public function test_create_annotation_private_success(): void {
        $this->setUser($this->student);

        $annotation = [
            'longpageid' => $this->longpage->id,
            'type' => annotation_type::HIGHLIGHT,
            'ispublic' => false,
            'target' => [
                'selectors' => [
                    [
                        'type' => selector::TYPE_TEXT_QUOTE_SELECTOR,
                        'exact' => 'private note',
                        'prefix' => '',
                        'suffix' => '',
                    ],
                ],
            ],
        ];

        $result = annotation_services::create_annotation($annotation);
        $result = $this->to_array($result);
        $created = $result['annotation'];

        $this->assertEquals(0, $created['ispublic']);
    }

    /**
     * Test creating annotation with range selector
     *
     * @covers \mod_longpage\external\annotation_services::create_annotation
     */
    public function test_create_annotation_with_range_selector(): void {
        $this->setUser($this->student);

        $annotation = [
            'longpageid' => $this->longpage->id,
            'type' => annotation_type::HIGHLIGHT,
            'ispublic' => true,
            'target' => [
                'selectors' => [
                    [
                        'type' => selector::TYPE_RANGE_SELECTOR,
                        'startcontainer' => '/html/body/p',
                        'startoffset' => 0,
                        'endcontainer' => '/html/body/p',
                        'endoffset' => 20,
                    ],
                ],
            ],
        ];

        $result = annotation_services::create_annotation($annotation);
        $result = $this->to_array($result);
        $created = $result['annotation'];

        $this->assertNotEmpty($created['id']);
        $this->assertEquals(annotation_type::HIGHLIGHT, $created['type']);
    }

    /**
     * Test creating annotation fails without authentication
     *
     * @covers \mod_longpage\external\annotation_services::create_annotation
     */
    public function test_create_annotation_not_authenticated(): void {
        $annotation = [
            'longpageid' => $this->longpage->id,
            'type' => annotation_type::HIGHLIGHT,
            'target' => [
                'selectors' => [
                    [
                        'type' => selector::TYPE_TEXT_QUOTE_SELECTOR,
                        'exact' => 'test',
                        'prefix' => '',
                        'suffix' => '',
                    ],
                ],
            ],
        ];

        $this->expectException(\moodle_exception::class);
        annotation_services::create_annotation($annotation);
    }

    /**
     * Test creating annotation fails with invalid page ID
     *
     * @covers \mod_longpage\external\annotation_services::create_annotation
     */
    public function test_create_annotation_invalid_page_id(): void {
        $this->setUser($this->student);

        $annotation = [
            'longpageid' => 99999,
            'type' => annotation_type::HIGHLIGHT,
            'target' => [
                'selectors' => [
                    [
                        'type' => selector::TYPE_TEXT_QUOTE_SELECTOR,
                        'exact' => 'test',
                        'prefix' => '',
                        'suffix' => '',
                    ],
                ],
            ],
        ];

        $this->expectException(\dml_missing_record_exception::class);
        annotation_services::create_annotation($annotation);
    }

    /**
     * Test getting annotations by page ID
     *
     * @covers \mod_longpage\external\annotation_services::get_annotations
     */
    public function test_get_annotations_by_page_id(): void {
        global $DB;

        $this->setUser($this->student);

        // Create a public annotation.
        $publicannotationid = $DB->insert_record('longpage_annotations', [
            'longpageid' => $this->longpage->id,
            'type' => annotation_type::HIGHLIGHT,
            'creatorid' => $this->student2->id,
            'ispublic' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        // Create target and selector for public annotation.
        $targetid = $DB->insert_record('longpage_annotation_targets', [
            'annotationid' => $publicannotationid,
            'styleclass' => 'test-class',
        ]);

        $selectorid = $DB->insert_record('longpage_selectors', [
            'annotationtargetid' => $targetid,
            'type' => selector::TYPE_TEXT_QUOTE_SELECTOR,
        ]);

        $DB->insert_record('longpage_text_quote_selectrs', [
            'selectorid' => $selectorid,
            'exact' => 'public text',
            'prefix' => 'pre',
            'suffix' => 'suf',
        ]);

        // Create a private annotation by another student.
        $privateannotationid = $DB->insert_record('longpage_annotations', [
            'longpageid' => $this->longpage->id,
            'type' => annotation_type::HIGHLIGHT,
            'creatorid' => $this->student2->id,
            'ispublic' => 0,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $result = annotation_services::get_annotations([
            'longpageid' => $this->longpage->id,
        ]);
        $result = $this->to_array($result);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('annotations', $result);

        // Should only see public annotation, not private annotation from another user.
        $this->assertCount(1, $result['annotations']);
        $this->assertEquals($publicannotationid, $result['annotations'][0]['id']);
    }

    /**
     * Test getting annotations includes own private annotations
     *
     * @covers \mod_longpage\external\annotation_services::get_annotations
     */
    public function test_get_annotations_includes_own_private(): void {
        global $DB;

        $this->setUser($this->student);

        // Create own private annotation.
        $ownannotationid = $DB->insert_record('longpage_annotations', [
            'longpageid' => $this->longpage->id,
            'type' => annotation_type::HIGHLIGHT,
            'creatorid' => $this->student->id,
            'ispublic' => 0,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        // Create target and selector.
        $targetid = $DB->insert_record('longpage_annotation_targets', [
            'annotationid' => $ownannotationid,
        ]);

        $selectorid = $DB->insert_record('longpage_selectors', [
            'annotationtargetid' => $targetid,
            'type' => selector::TYPE_TEXT_QUOTE_SELECTOR,
        ]);

        $DB->insert_record('longpage_text_quote_selectrs', [
            'selectorid' => $selectorid,
            'exact' => 'my private note',
            'prefix' => '',
            'suffix' => '',
        ]);

        $result = annotation_services::get_annotations([
            'longpageid' => $this->longpage->id,
        ]);
        $result = $this->to_array($result);

        // Should see own private annotation.
        $this->assertCount(1, $result['annotations']);
        $this->assertEquals($ownannotationid, $result['annotations'][0]['id']);
        $this->assertEquals(0, $result['annotations'][0]['ispublic']);
    }

    /**
     * Test getting annotations by annotation ID
     *
     * @covers \mod_longpage\external\annotation_services::get_annotations
     */
    public function test_get_annotations_by_annotation_id(): void {
        global $DB;

        $this->setUser($this->student);

        $annotationid = $DB->insert_record('longpage_annotations', [
            'longpageid' => $this->longpage->id,
            'type' => annotation_type::HIGHLIGHT,
            'creatorid' => $this->student->id,
            'ispublic' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $targetid = $DB->insert_record('longpage_annotation_targets', [
            'annotationid' => $annotationid,
        ]);

        $selectorid = $DB->insert_record('longpage_selectors', [
            'annotationtargetid' => $targetid,
            'type' => selector::TYPE_TEXT_QUOTE_SELECTOR,
        ]);

        $DB->insert_record('longpage_text_quote_selectrs', [
            'selectorid' => $selectorid,
            'exact' => 'specific annotation',
            'prefix' => '',
            'suffix' => '',
        ]);

        $result = annotation_services::get_annotations([
            'longpageid' => $this->longpage->id,
            'annotationid' => $annotationid,
        ]);
        $result = $this->to_array($result);

        $this->assertCount(1, $result['annotations']);
        $this->assertEquals($annotationid, $result['annotations'][0]['id']);
    }

    /**
     * Test getting annotations with post body
     *
     * @covers \mod_longpage\external\annotation_services::get_annotations
     */
    public function test_get_annotations_with_post_body(): void {
        global $DB;

        $this->setUser($this->student);

        // Create post annotation.
        $annotationid = $DB->insert_record('longpage_annotations', [
            'longpageid' => $this->longpage->id,
            'type' => annotation_type::POST,
            'creatorid' => $this->student->id,
            'ispublic' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $targetid = $DB->insert_record('longpage_annotation_targets', [
            'annotationid' => $annotationid,
        ]);

        $selectorid = $DB->insert_record('longpage_selectors', [
            'annotationtargetid' => $targetid,
            'type' => selector::TYPE_TEXT_QUOTE_SELECTOR,
        ]);

        $DB->insert_record('longpage_text_quote_selectrs', [
            'selectorid' => $selectorid,
            'exact' => 'post text',
            'prefix' => '',
            'suffix' => '',
        ]);

        // Create thread.
        $threadid = $DB->insert_record('longpage_threads', [
            'annotationid' => $annotationid,
            'creatorid' => $this->student->id,
            'replyrequested' => 0,
        ]);

        // Create post.
        $postid = $DB->insert_record('longpage_posts', [
            'threadid' => $threadid,
            'creatorid' => $this->student->id,
            'anonymous' => 0,
            'content' => 'Post comment content',
            'ispublic' => 1,
            'longpageid' => $this->longpage->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $result = annotation_services::get_annotations([
            'longpageid' => $this->longpage->id,
            'annotationid' => $annotationid,
        ]);
        $result = $this->to_array($result);

        $this->assertCount(1, $result['annotations']);
        $annotation = $result['annotations'][0];
        $this->assertArrayHasKey('body', $annotation);
        $this->assertEquals($threadid, $annotation['body']['id']);
        $this->assertArrayHasKey('posts', $annotation['body']);
        $this->assertCount(1, $annotation['body']['posts']);
        $this->assertEquals('Post comment content', $annotation['body']['posts'][0]['content']);
    }

    /**
     * Test getting annotations fails without authentication
     *
     * @covers \mod_longpage\external\annotation_services::get_annotations
     */
    public function test_get_annotations_not_authenticated(): void {
        $this->expectException(\moodle_exception::class);
        annotation_services::get_annotations([
            'longpageid' => $this->longpage->id,
        ]);
    }

    /**
     * Test deleting annotation successfully
     *
     * @covers \mod_longpage\external\annotation_services::delete_annotation
     */
    public function test_delete_annotation_highlight_success(): void {
        global $DB;

        $this->setUser($this->student);

        // Create annotation.
        $annotationid = $DB->insert_record('longpage_annotations', [
            'longpageid' => $this->longpage->id,
            'type' => annotation_type::HIGHLIGHT,
            'creatorid' => $this->student->id,
            'ispublic' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $targetid = $DB->insert_record('longpage_annotation_targets', [
            'annotationid' => $annotationid,
        ]);

        $selectorid = $DB->insert_record('longpage_selectors', [
            'annotationtargetid' => $targetid,
            'type' => selector::TYPE_TEXT_QUOTE_SELECTOR,
        ]);

        $DB->insert_record('longpage_text_quote_selectrs', [
            'selectorid' => $selectorid,
            'exact' => 'to be deleted',
            'prefix' => '',
            'suffix' => '',
        ]);

        // Delete annotation.
        annotation_services::delete_annotation($annotationid);

        // Verify annotation is deleted.
        $this->assertFalse($DB->record_exists('longpage_annotations', ['id' => $annotationid]));

        // Verify target is deleted.
        $this->assertFalse($DB->record_exists('longpage_annotation_targets', ['annotationid' => $annotationid]));

        // Verify selector is deleted.
        $this->assertFalse($DB->record_exists('longpage_selectors', ['annotationtargetid' => $targetid]));

        // Verify selector details are deleted.
        $this->assertFalse($DB->record_exists('longpage_text_quote_selectrs', ['selectorid' => $selectorid]));
    }

    /**
     * Test deleting post annotation with cascading deletion
     *
     * @covers \mod_longpage\external\annotation_services::delete_annotation
     */
    public function test_delete_annotation_post_with_cascade(): void {
        global $DB;

        $this->setUser($this->student);

        // Create post annotation.
        $annotationid = $DB->insert_record('longpage_annotations', [
            'longpageid' => $this->longpage->id,
            'type' => annotation_type::POST,
            'creatorid' => $this->student->id,
            'ispublic' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $targetid = $DB->insert_record('longpage_annotation_targets', [
            'annotationid' => $annotationid,
        ]);

        $selectorid = $DB->insert_record('longpage_selectors', [
            'annotationtargetid' => $targetid,
            'type' => selector::TYPE_TEXT_QUOTE_SELECTOR,
        ]);

        $DB->insert_record('longpage_text_quote_selectrs', [
            'selectorid' => $selectorid,
            'exact' => 'post to delete',
            'prefix' => '',
            'suffix' => '',
        ]);

        // Create thread.
        $threadid = $DB->insert_record('longpage_threads', [
            'annotationid' => $annotationid,
            'creatorid' => $this->student->id,
            'replyrequested' => 0,
        ]);

        // Create post.
        $postid = $DB->insert_record('longpage_posts', [
            'threadid' => $threadid,
            'creatorid' => $this->student->id,
            'anonymous' => 0,
            'content' => 'Post to be deleted',
            'ispublic' => 1,
            'longpageid' => $this->longpage->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        // Create thread subscription.
        $DB->insert_record('longpage_thread_subs', [
            'threadid' => $threadid,
            'userid' => $this->student->id,
            'timecreated' => time(),
        ]);

        // Delete annotation.
        annotation_services::delete_annotation($annotationid);

        // Verify all related records are deleted.
        $this->assertFalse($DB->record_exists('longpage_annotations', ['id' => $annotationid]));
        $this->assertFalse($DB->record_exists('longpage_threads', ['id' => $threadid]));
        $this->assertFalse($DB->record_exists('longpage_posts', ['id' => $postid]));
        $this->assertFalse($DB->record_exists('longpage_thread_subs', ['threadid' => $threadid]));
    }

    /**
     * Test deleting annotation with multiple selectors
     *
     * @covers \mod_longpage\external\annotation_services::delete_annotation
     */
    public function test_delete_annotation_multiple_selectors(): void {
        global $DB;

        $this->setUser($this->student);

        $annotationid = $DB->insert_record('longpage_annotations', [
            'longpageid' => $this->longpage->id,
            'type' => annotation_type::HIGHLIGHT,
            'creatorid' => $this->student->id,
            'ispublic' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $targetid = $DB->insert_record('longpage_annotation_targets', [
            'annotationid' => $annotationid,
        ]);

        // Create text quote selector.
        $selector1id = $DB->insert_record('longpage_selectors', [
            'annotationtargetid' => $targetid,
            'type' => selector::TYPE_TEXT_QUOTE_SELECTOR,
        ]);

        $DB->insert_record('longpage_text_quote_selectrs', [
            'selectorid' => $selector1id,
            'exact' => 'text 1',
            'prefix' => '',
            'suffix' => '',
        ]);

        // Create text position selector.
        $selector2id = $DB->insert_record('longpage_selectors', [
            'annotationtargetid' => $targetid,
            'type' => selector::TYPE_TEXT_POSITION_SELECTOR,
        ]);

        $DB->insert_record('longpage_text_pos_selectors', [
            'selectorid' => $selector2id,
            'startposition' => 0,
            'endposition' => 10,
        ]);

        annotation_services::delete_annotation($annotationid);

        // Verify both selectors are deleted.
        $this->assertFalse($DB->record_exists('longpage_selectors', ['id' => $selector1id]));
        $this->assertFalse($DB->record_exists('longpage_selectors', ['id' => $selector2id]));
        $this->assertFalse($DB->record_exists('longpage_text_quote_selectrs', ['selectorid' => $selector1id]));
        $this->assertFalse($DB->record_exists('longpage_text_pos_selectors', ['selectorid' => $selector2id]));
    }

    /**
     * Test deleting annotation fails with invalid ID
     *
     * @covers \mod_longpage\external\annotation_services::delete_annotation
     */
    public function test_delete_annotation_invalid_id(): void {
        $this->setUser($this->student);

        $this->expectException(\moodle_exception::class);
        annotation_services::delete_annotation(99999);
    }

    /**
     * Test deleting annotation fails without authentication
     *
     * @covers \mod_longpage\external\annotation_services::delete_annotation
     */
    public function test_delete_annotation_not_authenticated(): void {
        global $DB;

        $annotationid = $DB->insert_record('longpage_annotations', [
            'longpageid' => $this->longpage->id,
            'type' => annotation_type::HIGHLIGHT,
            'creatorid' => $this->student->id,
            'ispublic' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $this->expectException(\moodle_exception::class);
        annotation_services::delete_annotation($annotationid);
    }

    /**
     * Test can_madify_annotations returns true for teacher
     *
     * @covers \mod_longpage\external\annotation_services::can_madify_annotations
     */
    public function test_can_madify_annotations_teacher_success(): void {
        global $DB;

        $this->setUser($this->teacher);

        // Ensure teacher has the capability.
        $teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);
        assign_capability('mod/longpage:modannotations', CAP_ALLOW, $teacherrole->id, $this->context->id);

        $result = annotation_services::can_madify_annotations($this->longpage->id);
        $result = $this->to_array($result);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('canmodannotations', $result);
        $this->assertTrue($result['canmodannotations']);
    }

    /**
     * Test can_madify_annotations returns false for student
     *
     * @covers \mod_longpage\external\annotation_services::can_madify_annotations
     */
    public function test_can_madify_annotations_student_fails(): void {
        global $DB;

        $this->setUser($this->student);

        // Ensure student doesn't have the capability.
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        assign_capability('mod/longpage:modannotations', CAP_PROHIBIT, $studentrole->id, $this->context->id);

        $result = annotation_services::can_madify_annotations($this->longpage->id);
        $result = $this->to_array($result);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('canmodannotations', $result);
        $this->assertFalse($result['canmodannotations']);
    }

    /**
     * Test can_madify_annotations with invalid page ID
     *
     * @covers \mod_longpage\external\annotation_services::can_madify_annotations
     */
    public function test_can_madify_annotations_invalid_page_id(): void {
        $this->setUser($this->teacher);

        $this->expectException(\dml_missing_record_exception::class);
        annotation_services::can_madify_annotations(99999);
    }

    /**
     * Test can_madify_annotations without authentication
     *
     * @covers \mod_longpage\external\annotation_services::can_madify_annotations
     */
    public function test_can_madify_annotations_not_authenticated(): void {
        $this->expectException(\moodle_exception::class);
        annotation_services::can_madify_annotations($this->longpage->id);
    }

    /**
     * Test parameter validation for create_annotation
     *
     * @covers \mod_longpage\external\annotation_services::create_annotation
     * @covers \mod_longpage\external\annotation_services::create_annotation_parameters
     */
    public function test_create_annotation_parameter_validation(): void {
        $this->setUser($this->student);

        // Missing required longpageid.
        try {
            annotation_services::create_annotation([
                'type' => annotation_type::HIGHLIGHT,
                'target' => [
                    'selectors' => [],
                ],
            ]);
            $this->fail('Expected invalid_parameter_exception for missing longpageid');
        } catch (\invalid_parameter_exception $e) {
            $this->assertStringContainsString('longpageid', $e->getMessage());
        }

        // Missing required type.
        try {
            annotation_services::create_annotation([
                'longpageid' => $this->longpage->id,
                'target' => [
                    'selectors' => [],
                ],
            ]);
            $this->fail('Expected invalid_parameter_exception for missing type');
        } catch (\invalid_parameter_exception $e) {
            $this->assertStringContainsString('type', $e->getMessage());
        }

        // Missing required target.
        try {
            annotation_services::create_annotation([
                'longpageid' => $this->longpage->id,
                'type' => annotation_type::HIGHLIGHT,
            ]);
            $this->fail('Expected invalid_parameter_exception for missing target');
        } catch (\invalid_parameter_exception $e) {
            $this->assertStringContainsString('target', $e->getMessage());
        }
    }

    /**
     * Test parameter validation for get_annotations
     *
     * @covers \mod_longpage\external\annotation_services::get_annotations
     * @covers \mod_longpage\external\annotation_services::get_annotations_parameters
     */
    public function test_get_annotations_parameter_validation(): void {
        $this->setUser($this->student);

        // Missing required longpageid.
        try {
            annotation_services::get_annotations([]);
            $this->fail('Expected invalid_parameter_exception for missing longpageid');
        } catch (\invalid_parameter_exception $e) {
            $this->assertStringContainsString('longpageid', $e->getMessage());
        }
    }

    /**
     * Test parameter validation for delete_annotation
     *
     * @covers \mod_longpage\external\annotation_services::delete_annotation
     * @covers \mod_longpage\external\annotation_services::delete_annotation_parameters
     */
    public function test_delete_annotation_parameter_validation(): void {
        $this->setUser($this->student);

        // Invalid ID type (string instead of int).
        try {
            annotation_services::delete_annotation('invalid');
            $this->fail('Expected invalid_parameter_exception for invalid id type');
        } catch (\invalid_parameter_exception $e) {
            $this->assertStringContainsString('id', $e->getMessage());
        }
    }

    /**
     * Test creating annotation with post containing reply requested
     *
     * @covers \mod_longpage\external\annotation_services::create_annotation
     */
    public function test_create_annotation_post_reply_requested(): void {
        global $DB;

        $this->setUser($this->student);

        $annotation = [
            'longpageid' => $this->longpage->id,
            'type' => annotation_type::POST,
            'ispublic' => true,
            'target' => [
                'selectors' => [
                    [
                        'type' => selector::TYPE_TEXT_QUOTE_SELECTOR,
                        'exact' => 'need help here',
                        'prefix' => '',
                        'suffix' => '',
                    ],
                ],
            ],
            'body' => [
                'anonymous' => false,
                'content' => 'Can someone explain this?',
                'ispublic' => true,
                'replyrequested' => true,
            ],
        ];

        $result = annotation_services::create_annotation($annotation);
        $result = $this->to_array($result);
        $created = $result['annotation'];

        // Verify reply requested flag.
        $thread = $DB->get_record('longpage_threads', ['annotationid' => $created['id']]);
        $this->assertEquals(1, $thread->replyrequested);
    }

    /**
     * Test creating anonymous post
     *
     * @covers \mod_longpage\external\annotation_services::create_annotation
     */
    public function test_create_annotation_anonymous_post(): void {
        global $DB;

        $this->setUser($this->student);

        $annotation = [
            'longpageid' => $this->longpage->id,
            'type' => annotation_type::POST,
            'ispublic' => true,
            'target' => [
                'selectors' => [
                    [
                        'type' => selector::TYPE_TEXT_QUOTE_SELECTOR,
                        'exact' => 'anonymous comment',
                        'prefix' => '',
                        'suffix' => '',
                    ],
                ],
            ],
            'body' => [
                'anonymous' => true,
                'content' => 'Anonymous comment here',
                'ispublic' => true,
            ],
        ];

        $result = annotation_services::create_annotation($annotation);
        $result = $this->to_array($result);
        $created = $result['annotation'];

        $thread = $DB->get_record('longpage_threads', ['annotationid' => $created['id']]);
        $post = $DB->get_record('longpage_posts', ['threadid' => $thread->id]);
        $this->assertEquals(1, $post->anonymous);
    }

    /**
     * Test student cannot see other students' private annotations
     *
     * @covers \mod_longpage\external\annotation_services::get_annotations
     */
    public function test_get_annotations_privacy_between_students(): void {
        global $DB;

        // Student 2 creates a private annotation.
        $this->setUser($this->student2);

        $privateannotationid = $DB->insert_record('longpage_annotations', [
            'longpageid' => $this->longpage->id,
            'type' => annotation_type::HIGHLIGHT,
            'creatorid' => $this->student2->id,
            'ispublic' => 0,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $targetid = $DB->insert_record('longpage_annotation_targets', [
            'annotationid' => $privateannotationid,
        ]);

        $selectorid = $DB->insert_record('longpage_selectors', [
            'annotationtargetid' => $targetid,
            'type' => selector::TYPE_TEXT_QUOTE_SELECTOR,
        ]);

        $DB->insert_record('longpage_text_quote_selectrs', [
            'selectorid' => $selectorid,
            'exact' => 'student2 private',
            'prefix' => '',
            'suffix' => '',
        ]);

        // Student 1 tries to retrieve annotations.
        $this->setUser($this->student);

        $result = annotation_services::get_annotations([
            'longpageid' => $this->longpage->id,
        ]);

        // Student 1 should not see student 2's private annotation.
        $this->assertCount(0, $result['annotations']);
    }

    /**
     * Test annotation timestamps are set correctly
     *
     * @covers \mod_longpage\external\annotation_services::create_annotation
     */
    public function test_create_annotation_timestamps(): void {
        $this->setUser($this->student);

        $timebefore = time();

        $annotation = [
            'longpageid' => $this->longpage->id,
            'type' => annotation_type::HIGHLIGHT,
            'target' => [
                'selectors' => [
                    [
                        'type' => selector::TYPE_TEXT_QUOTE_SELECTOR,
                        'exact' => 'timestamp test',
                        'prefix' => '',
                        'suffix' => '',
                    ],
                ],
            ],
        ];

        $result = annotation_services::create_annotation($annotation);
        $result = $this->to_array($result);
        $timeafter = time();

        $created = $result['annotation'];
        $this->assertGreaterThanOrEqual($timebefore, $created['timecreated']);
        $this->assertLessThanOrEqual($timeafter, $created['timecreated']);
        $this->assertEquals($created['timecreated'], $created['timemodified']);
    }
}
