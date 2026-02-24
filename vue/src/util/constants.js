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
 * @package    mod_longpage
 * @copyright  2021 Adrian Stritzinger <Adrian.Stritzinger@studium.fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
export const AnnotationType = Object.freeze({
    HIGHLIGHT: 0,
    POST: 1,
    BOOKMARK: 2,
    QUIZ: 3
});

export const AnnotationTypeIcon = Object.freeze({
    [AnnotationType.BOOKMARK]: 'fa-bookmark-o',
    [AnnotationType.HIGHLIGHT]: 'fa-pencil',
    [AnnotationType.POST]: 'fa-comment-o',
    [AnnotationType.QUIZ]: 'fa-question'
});

/**
 * The preferred gap between the end of the text selection and the
 * arrow position of the annotation toolbar popover.
 */
export const ARROW_H_MARGIN = 20;

export const ArrowDirection = Object.freeze({
    DOWN: 0,
    UP: 1
});

export const HighlightingConfig = Object.freeze({
    HL_TAG_NAME: 'longpage-highlight',
    HL_CLASS_NAME: 'longpage-highlight',
    SHOW_HLS_CLASS_NAME: 'longpage-highlights-always-on',
});

export const LANGUAGE = 'de';

export const THREAD_CONTAINER_ID = 'thread-container';
export const LONGPAGE_APP_CONTAINER_ID = 'longpage-app-container';
export const LONGPAGE_APP_ID = 'longpage-app';
export const LONGPAGE_CONTENT_ID = 'longpage-content';
export const LONGPAGE_MAIN_ID = 'longpage-main';

export const MoodleWSMethods = Object.freeze({
    CREATE_ANNOTATION: 'mod_longpage_create_annotation',
    CREATE_POST: 'mod_longpage_create_post',
    CREATE_POST_BOOKMARK: 'mod_longpage_create_post_bookmark',
    CREATE_POST_LIKE: 'mod_longpage_create_post_like',
    CREATE_POST_READING: 'mod_longpage_create_post_reading',
    CREATE_THREAD_SUBSCRIPTION: 'mod_longpage_create_thread_subscription',
    DELETE_ANNOTATION: 'mod_longpage_delete_annotation',
    DELETE_POST_BOOKMARK: 'mod_longpage_delete_post_bookmark',
    DELETE_POST: 'mod_longpage_delete_post',
    DELETE_POST_LIKE: 'mod_longpage_delete_post_like',
    DELETE_POST_READING: 'mod_longpage_delete_post_reading',
    DELETE_THREAD_SUBSCRIPTION: 'mod_longpage_delete_thread_subscription',
    GET_ANNOTATIONS: 'mod_longpage_get_annotations',
    GET_ENROLLED_USERS: 'mod_longpage_get_enrolled_users_with_roles_by_pageid',
    GET_USER_ROLES_FOR_MODULE: 'mod_longpage_get_user_roles_by_pageid',
    UPDATE_ANNOTATION: 'mod_longpage_update_annotation',
    UPDATE_POST: 'mod_longpage_update_post',
    UPDATE_READING_PROGRESS: 'mod_longpage_update_reading_progress',
    USER_CAN_MOD_ANNOTATION: 'mod_longpage_can_madify_annotations'
});

export const SidebarTabKeys = Object.freeze({
    BOOKMARKS: 'bookmarks',
    HIGHLIGHTS: 'highlights',
    POSTS: 'posts',
    TOC: 'toc',
    SEARCH: 'search',
    COURSE_RECOMMENDATIONS: 'course-recomm',
    QUIZ: "quiz"
});

export const SidebarEvents = Object.freeze({
    TOGGLE_TABS: 'toggle_tabs',
    CHANGE_BADGES: 'change_badges'
});

export const SCROLL_INTO_VIEW_OPTIONS = {
    behavior: 'smooth',
    block: 'start',
};

export const SelectorType = Object.freeze({
    TEXT_POSITION_SELECTOR: 'TextPositionSelector',
    TEXT_QUOTE_SELECTOR: 'TextQuoteSelector',
    RANGE_SELECTOR: 'RangeSelector',
});

export const MOODLE_NAVBAR_HEIGHT_IN_PX = 50;

export const SAVE_ACTIONS = [
    {key: 'publish', iconClasses: ['fa-users'], accessClass: ['']},
    {key: 'publishAnonymously', iconClasses: ['fa-user-secret'], accessClass: ['']},
    {key: 'save', iconClasses: ['fa-lock']},
    {key: 'saveDisabled', iconClasses: ['fa-lock'], accessClass: ['disabled']}      // added 'accessClass', sole purpose is to add 
                                                                                        //a disabled button in place of 'save' for user experience
];