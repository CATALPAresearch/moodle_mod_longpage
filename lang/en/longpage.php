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
 * Strings for component 'longpage', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package   mod_longpage
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['activatefunctionalities'] = '(De-)activate Longpage functionalities';
$string['activatefunctionalitiesexplain'] = '(De-)activate Longpage functionalities for one Longpage instance';

// AI question generation settings.
$string['aiquestiongeneration'] = 'AI Configuration';
$string['aiquestiongenerationexplain'] = 'Configure AI-powered question generation using Ollama LLM server';
$string['enableai'] = 'Enable AI question generation';
$string['enableai_desc'] = 'Allow teachers to generate reading comprehension questions using AI';
$string['aiurl'] = 'Primary LLM server URL';
$string['aiurl_desc'] = 'The URL of the Ollama API endpoint (e.g., http://server:11434/api/chat)';
$string['aiurlbackup'] = 'Backup LLM server URL';
$string['aiurlbackup_desc'] = 'Fallback URL if the primary server is unavailable';
$string['aimodel'] = 'AI model name';
$string['aimodel_desc'] = 'The Ollama model to use for question generation (e.g., llama3.1:latest, mistral:latest)';
$string['aitoken'] = 'Authorization token';
$string['aitoken_desc'] = 'Optional: JWT token or API key for authentication';
$string['aitimeout'] = 'Request timeout';
$string['aitimeout_desc'] = 'Maximum time in seconds to wait for AI response (default: 180)';
$string['availablemodels'] = 'Available models';
$string['availablemodels_desc'] = 'Models detected on the configured Ollama server';
$string['checkavailablemodels'] = 'Check available models on server';
$string['configure_url_first'] = 'Please configure the LLM server URL above first';
$string['invalid_url'] = 'Invalid URL format';
$string['refreshmodels'] = 'Refresh models list';
$string['configdisplayoptions'] = 'Select all options that should be available, existing settings are not modified. Hold CTRL key to select multiple fields.';
$string['content'] = 'Longpage content';
$string['contentheader'] = 'Content';
$string['createpage'] = 'Create a new Longpage resource';
$string['displayoptions'] = 'Available display options';
$string['displayselect'] = 'Display';
$string['displayselectexplain'] = 'Select display type.';
$string['indicator:cognitivedepth'] = 'Longpage cognitive';
$string['indicator:cognitivedepth_help'] = 'This indicator is based on the cognitive depth reached by the student in a Longpage resource.';
$string['indicator:socialbreadth'] = 'Longpage social';
$string['indicator:socialbreadth_help'] = 'This indicator is based on the social breadth reached by the student in a Longpage resource.';
$string['legacyfiles'] = 'Migration of old course file';
$string['legacyfilesactive'] = 'Active';
$string['legacyfilesdone'] = 'Finished';
$string['loading'] = 'Loading...';

$string['longpage:addinstance'] = 'Add a new Longpage resource';
$string['longpage:addpost'] = 'Create posts, annotations, highlights and bookmarks';
$string['longpage:modannotations'] = 'edit and delete annotations';
$string['longpage:view'] = 'View Longpage content';
$string['messagecontexturlnamepostcreated'] = 'Neuer Beitrag';
$string['messagecontexturlnamepostdeleted'] = 'Thread, innerhalb dessen der Beitrag gelöscht wurde';
$string['messagecontexturlnamepostupdated'] = 'Bearbeiteter Beitrag';
$string['messagefullpostcreated'] = '{$a->firstname} {$a->lastname} hat einen neuen Beitrag innerhalb eines von Ihnen abonnierten Threads verfasst:\n „{$a->content}“';
$string['messagefullpostdeleted'] = '{$a->firstname} {$a->lastname} hat folgenden Beitrag innerhalb eines von Ihnen abonnierten Threads gelöscht:\n „{$a->content}“';
$string['messagefullpostupdated'] = '{$a->firstname} {$a->lastname} hat folgenden Beitrag innerhalb eines von Ihnen abonnierten Threads bearbeitet:\n„{$a->oldcontent}“\nNeuer Text: „{$a->content}“';
$string['messagehtmlpostcreated'] = '<p>{$a->firstname} {$a->lastname} hat einen neuen Beitrag innerhalb eines von Ihnen abonnierten Threads verfasst:</p><p>„{$a->content}“</p>';
$string['messagehtmlpostdeleted'] = '<p>{$a->firstname} {$a->lastname} hat folgenden Beitrag innerhalb eines von Ihnen abonnierten Threads gelöscht:</p><p>„{$a->content}“</p>';
$string['messagehtmlpostupdated'] = '<p>{$a->firstname} {$a->lastname} hat folgenden Beitrag innerhalb eines von Ihnen abonnierten Threads bearbeitet:</p><p>„{$a->oldcontent}“</p><p>Neuer Text:</p><p>„{$a->content}“</p>';
$string['messageprovider:posts'] = 'Abonnierte Threads';

$string['messagesmallpostcreated'] = '{$a->firstname} {$a->lastname}: {$a->content}';
$string['messagesmallpostdeleted'] = '{$a->firstname} {$a->lastname}: {$a->content}';
$string['messagesmallpostupdated'] = '{$a->firstname} {$a->lastname}: {$a->content}';
$string['messagesubjectpostcreated'] = 'Neuer Beitrag innerhalb eines abonnierten Threads';
$string['messagesubjectpostcreated_shortcontent'] = '{$a->firstname} {$a->lastname} hat einen neuen Beitrag innerhalb eines von Ihnen abonnierten Threads verfasst: „{$a->shortcontent}“';

$string['messagesubjectpostdeleted'] = 'Beitrag innerhalb eines abonnierten Threads gelöscht';
$string['messagesubjectpostdeleted_shortcontent'] = '{$a->firstname} {$a->lastname} hat einen Beitrag innerhalb eines von Ihnen abonnierten Threads gelöscht';

$string['messagesubjectpostupdated'] = 'Beitrag innerhalb eines abonnierten Threads wurde bearbeitet';
$string['messagesubjectpostupdated_shortcontent'] = '{$a->firstname} {$a->lastname} hat einen Beitrag innerhalb eines von Ihnen abonnierten Threads bearbeitet';

$string['modulename'] = 'longpage';
$string['modulename_help'] = 'The Longpage module enables a teacher to create a web Longpage resource using the text editor. A Longpage can display text, images, sound, video, web links and embedded code, such as Google maps.

Advantages of using the Longpage module rather than the file module include the resource being more accessible (for example to users of mobile devices) and easier to update.

For large amounts of content, it\'s recommended that a book is used rather than a Longpage.

A Longpage may be used

* To present the terms and conditions of a course or a summary of the course syllabus
* To embed several videos or sound files together with some explanatory text';
$string['modulename_link'] = 'mod/longpage/view';
$string['modulenameplural'] = 'Longpages';
$string['optionsheader'] = 'Display options';
$string['page-mod-page-x'] = 'Any Longpage module Longpage';
$string['pluginadministration'] = 'Longpage module administration';
$string['pluginname'] = 'longpage';
$string['popupheight'] = 'Pop-up height (in pixels)';
$string['popupheightexplain'] = 'Specifies default height of popup windows.';
$string['popupwidth'] = 'Pop-up width (in pixels)';
$string['popupwidthexplain'] = 'Specifies default width of popup windows.';
$string['printheading'] = 'Display Longpage name';
$string['printheadingexplain'] = 'Display Longpage name above content?';
$string['printintro'] = 'Display Longpage description';
$string['printintroexplain'] = 'Display Longpage description above content?';
$string['privacy:annotations'] = 'Annotations';
$string['privacy:metadata'] = 'The Longpage module stores user annotations, posts, reading progress, and interaction data.';
$string['privacy:metadata:longpage_abs_post_prefs'] = 'Absolute post preferences for recommendations.';
$string['privacy:metadata:longpage_abs_post_prefs:postid'] = 'The ID of the post.';
$string['privacy:metadata:longpage_abs_post_prefs:userid'] = 'The ID of the user.';
$string['privacy:metadata:longpage_abs_post_prefs:value'] = 'The preference value.';
$string['privacy:metadata:longpage_annotations'] = 'Information about user annotations on longpage content.';
$string['privacy:metadata:longpage_annotations:creatorid'] = 'The ID of the user who created the annotation.';
$string['privacy:metadata:longpage_annotations:ispublic'] = 'Whether the annotation is public.';
$string['privacy:metadata:longpage_annotations:longpageid'] = 'The ID of the longpage activity.';
$string['privacy:metadata:longpage_annotations:timecreated'] = 'The time when the annotation was created.';
$string['privacy:metadata:longpage_annotations:timemodified'] = 'The time when the annotation was last modified.';
$string['privacy:metadata:longpage_annotations:type'] = 'The type of annotation.';
$string['privacy:metadata:longpage_post_bookmarks'] = 'Information about posts bookmarked by users.';
$string['privacy:metadata:longpage_post_bookmarks:postid'] = 'The ID of the post that was bookmarked.';
$string['privacy:metadata:longpage_post_bookmarks:timecreated'] = 'The time when the bookmark was created.';
$string['privacy:metadata:longpage_post_bookmarks:userid'] = 'The ID of the user who bookmarked the post.';
$string['privacy:metadata:longpage_post_likes'] = 'Information about posts liked by users.';
$string['privacy:metadata:longpage_post_likes:postid'] = 'The ID of the post that was liked.';
$string['privacy:metadata:longpage_post_likes:timecreated'] = 'The time when the like was created.';
$string['privacy:metadata:longpage_post_likes:userid'] = 'The ID of the user who liked the post.';
$string['privacy:metadata:longpage_post_pref_profiles'] = 'User preference profiles for post recommendations.';
$string['privacy:metadata:longpage_post_pref_profiles:avg'] = 'The average preference value.';
$string['privacy:metadata:longpage_post_pref_profiles:count'] = 'The count of preferences.';
$string['privacy:metadata:longpage_post_pref_profiles:userid'] = 'The ID of the user.';
$string['privacy:metadata:longpage_post_readings'] = 'Information about posts read by users.';
$string['privacy:metadata:longpage_post_readings:postid'] = 'The ID of the post that was read.';
$string['privacy:metadata:longpage_post_readings:timecreated'] = 'The time when the post was read.';
$string['privacy:metadata:longpage_post_readings:userid'] = 'The ID of the user who read the post.';
$string['privacy:metadata:longpage_post_recomends'] = 'Post recommendations for users.';
$string['privacy:metadata:longpage_post_recomends:postid'] = 'The ID of the recommended post.';
$string['privacy:metadata:longpage_post_recomends:userid'] = 'The ID of the user.';
$string['privacy:metadata:longpage_post_recomends:value'] = 'The recommendation score.';
$string['privacy:metadata:longpage_posts'] = 'Information about user posts in threads.';
$string['privacy:metadata:longpage_posts:anonymous'] = 'Whether the post is anonymous.';
$string['privacy:metadata:longpage_posts:content'] = 'The content of the post.';
$string['privacy:metadata:longpage_posts:creatorid'] = 'The ID of the user who created the post.';
$string['privacy:metadata:longpage_posts:ispublic'] = 'Whether the post is public.';
$string['privacy:metadata:longpage_posts:longpageid'] = 'The ID of the longpage activity.';
$string['privacy:metadata:longpage_posts:timecreated'] = 'The time when the post was created.';
$string['privacy:metadata:longpage_posts:timemodified'] = 'The time when the post was last modified.';
$string['privacy:metadata:longpage_reading_progress'] = 'Information about user reading progress.';
$string['privacy:metadata:longpage_reading_progress:longpageid'] = 'The ID of the longpage activity.';
$string['privacy:metadata:longpage_reading_progress:scrolltop'] = 'The scroll position.';
$string['privacy:metadata:longpage_reading_progress:section'] = 'The section being read.';
$string['privacy:metadata:longpage_reading_progress:timemodified'] = 'The time when the progress was last updated.';
$string['privacy:metadata:longpage_reading_progress:userid'] = 'The ID of the user.';
$string['privacy:metadata:longpage_rel_post_prefs'] = 'Relative post preferences for recommendations.';
$string['privacy:metadata:longpage_rel_post_prefs:postid'] = 'The ID of the post.';
$string['privacy:metadata:longpage_rel_post_prefs:userid'] = 'The ID of the user.';
$string['privacy:metadata:longpage_rel_post_prefs:value'] = 'The preference value.';
$string['privacy:metadata:longpage_thread_subs'] = 'Information about thread subscriptions.';
$string['privacy:metadata:longpage_thread_subs:threadid'] = 'The ID of the thread that was subscribed to.';
$string['privacy:metadata:longpage_thread_subs:timecreated'] = 'The time when the subscription was created.';
$string['privacy:metadata:longpage_thread_subs:userid'] = 'The ID of the user who subscribed.';
$string['privacy:postbookmarks'] = 'Post Bookmarks';
$string['privacy:postlikes'] = 'Post Likes';
$string['privacy:postreadings'] = 'Post Readings';
$string['privacy:posts'] = 'Posts';
$string['privacy:readingprogress'] = 'Reading Progress';
$string['search:activity'] = 'longpage';
$string['showbookmarks'] = 'Enable bookmarks';
$string['showbookmarksexplain'] = 'Enable functionality to create and use bookmarks';
$string['showeditquestionsai'] = 'Enable AI question creation';
$string['showeditquestionsai_desc'] = 'If enabled, teachers can use AI question creation features';
$string['showeditquestionsnoai'] = 'Enable manual question creation';
$string['showeditquestionsnoai_desc'] = 'If enabled, teachers can use manual question creation features';
$string['showhighlights'] = 'Enable highlights';
$string['showhighlightsexplain'] = 'Enable functionality to create and read highlights';
$string['showposts'] = 'Enable annotations';
$string['showpostsexplain'] = 'Enable functionality to create and read annotations';
$string['showreadingcomprehension'] = 'Display visualization of reading comprehension';
$string['showreadingcomprehensionexplain'] = 'Display visualization of reading comprehension';
$string['showreadingprogress'] = 'Show reading progress';
$string['showreadingprogressexplain'] = 'Show reading progress bar (reading progress is always logged!)';
$string['showreadingtime'] = 'Show estimated reading time';
$string['showreadingtimeexplain'] = 'Display estimated reading time for each section';
$string['showsearch'] = 'Show search';
$string['showsearchexplain'] = 'Show search in sidebar';
$string['showtableofcontents'] = 'Show table of contents';
$string['showtableofcontentsexplain'] = 'Show table of contents in sidebar';














// Post form strings
$string['post_form_action_cancel'] = 'Cancel';
$string['post_form_action_publish'] = 'Publish';
$string['post_form_action_publishAnonymously'] = 'Publish anonymously';
$string['post_form_action_save'] = 'Save';
$string['post_form_action_saveDisabled'] = 'Save (disabled)';
$string['post_form_bodyTextareaPlaceholder'] = 'Write your post here...';

// Post action strings
$string['post_action_answer'] = 'Reply';
$string['post_action_markWithStar'] = 'Bookmark post';
$string['post_action_rmMarkWithStar'] = 'Remove bookmark';
$string['post_action_subscribe'] = 'Subscribe to thread';
$string['post_action_unsubscribe'] = 'Unsubscribe from thread';
$string['post_action_more'] = 'More actions';
$string['post_action_edit'] = 'Edit';
$string['post_action_delete'] = 'Delete';
$string['post_action_admindelete'] = 'Delete (Admin)';
$string['post_action_censored'] = '[Content removed by administrator]';
$string['post_action_copyLink'] = 'Copy link';
$string['post_action_copyLinkMessage'] = 'Link copied to clipboard';

// Sidebar tab strings
$string['sidebar_tabs_tableOfContents_heading'] = 'Table of Contents';
$string['sidebar_tabs_posts_heading'] = 'Posts';
$string['sidebar_tabs_highlights_heading'] = 'Highlights';
$string['sidebar_tabs_bookmarks_heading'] = 'Bookmarks';
$string['sidebar_tabs_quiz_heading'] = 'Quiz';
$string['sidebar_tabs_search_heading'] = 'Search';

// Sidebar filter strings
$string['sidebar_tabs_posts_filter_title'] = 'Filter posts';
$string['sidebar_tabs_posts_filter_byContent'] = 'Search by content...';
$string['sidebar_tabs_posts_filter_authors'] = 'Filter by authors';
$string['sidebar_tabs_posts_filter_notFound'] = 'No authors found';
$string['sidebar_tabs_posts_filter_readingStatus'] = 'Reading status';
$string['sidebar_tabs_posts_filter_status'] = 'Status';
$string['sidebar_tabs_posts_filter_likedBy'] = 'Liked by';
$string['sidebar_tabs_posts_filter_timeCreated'] = 'Created';
$string['sidebar_tabs_posts_filter_timeModified'] = 'Modified';

// Sidebar sorting strings
$string['sidebar_tabs_posts_sorting_title'] = 'Sort posts';

// Sidebar message strings
$string['sidebar_tabs_posts_message_onlySelectedShown'] = 'Only selected posts are shown';
$string['sidebar_tabs_posts_message_showAllFiltered'] = 'Show all filtered posts';
$string['sidebar_tabs_posts_message_filtered'] = 'Some posts are filtered';
$string['sidebar_tabs_posts_message_showAll'] = 'Show all';
$string['sidebar_tabs_posts_message_noneFilteredShown'] = 'No posts match the current filters';
$string['sidebar_tabs_posts_message_noneShown'] = 'No posts available';
$string['sidebar_tabs_highlights_message_noneCreated'] = 'No highlights created yet';
$string['sidebar_tabs_bookmarks_message_noneCreated'] = 'No bookmarks created yet';

// Sidebar utility strings
$string['sidebar_util_changeWidth'] = 'Change sidebar width';

// Content annotation toolbar strings
$string['content_annotationToolbar_createHighlight'] = 'Create highlight';
$string['content_annotationToolbar_createBookmark'] = 'Create bookmark';
$string['content_annotationToolbar_createPost'] = 'Create post';

// Generic strings
$string['generic_toggleDropdown'] = 'Toggle dropdown';
$string['generic_loadingIndicator_creating'] = 'Creating...';
$string['generic_loadingIndicator_updating'] = 'Updating...';
$string['generic_defaultSelectOptionsCategory'] = 'Options';
$string['generic_message_notFound'] = 'No results found';

// Avatar strings
$string['avatar_alt'] = 'User avatar';
$string['avatar_nameAnonymous'] = 'Anonymous';

// Expandable strings
$string['expandable_less'] = 'Show less';
$string['expandable_more'] = 'Show more';

// Timestamp strings
$string['timestamps_created'] = 'Created:';
$string['timestamps_modified'] = 'Modified:';

// Response form strings
$string['responseForm_placeholder'] = 'Write your reply...';

// Post state filter labels
$string['post_state_unread'] = 'Unread';
$string['post_state_read'] = 'Read';
$string['post_state_liked'] = 'Liked';
$string['post_state_bookmarked'] = 'Bookmarked';
$string['post_state_subscribedToThread'] = 'Subscribed to thread';

// Sidebar tab menu titles
$string['sidebar_tabMenu_titles_search'] = 'Search';
$string['sidebar_tabMenu_titles_toc'] = 'Table of Contents';
$string['sidebar_tabMenu_titles_posts'] = 'Posts';
$string['sidebar_tabMenu_titles_highlights'] = 'Highlights';
$string['sidebar_tabMenu_titles_bookmarks'] = 'Bookmarks';
$string['sidebar_tabMenu_titles_quiz'] = 'Quiz';

// Sorting options
$string['sidebar_tabs_posts_sorting_option_byNovelty'] = 'By Novelty';
$string['sidebar_tabs_posts_sorting_option_byPosition'] = 'By Position';
$string['sidebar_tabs_posts_sorting_option_byRecommendation'] = 'By Recommendation';

// Annotation indicator labels
$string['content_annotationIndicator_0'] = 'Highlight';
$string['content_annotationIndicator_1'] = 'Post';
$string['content_annotationIndicator_2'] = 'Bookmark';

// Tab messages
$string['sidebar_tabs_highlights_message_showAll'] = 'Show all highlights';
$string['sidebar_tabs_highlights_message_onlySelectedShown'] = 'Only selected highlights are shown';
$string['sidebar_tabs_bookmarks_message_showAll'] = 'Show all bookmarks';
$string['sidebar_tabs_bookmarks_message_onlySelectedShown'] = 'Only selected bookmarks are shown';

// Highlight and bookmark actions
$string['highlight_action_delete'] = 'Delete highlight';
$string['bookmark_action_delete'] = 'Delete bookmark';

// Quiz toast messages
$string['quiz_toast_questionCreated'] = 'Question created.';
$string['quiz_toast_questionRemoved'] = 'Question removed.';
$string['quiz_toast_questionLocked'] = 'Question locked.';
$string['quiz_toast_questionUnlocked'] = 'Question unlocked.';
$string['quiz_toast_changesSaved'] = 'Changes saved.';
$string['quiz_toast_editingCancelled'] = 'Editing cancelled.';
$string['quiz_toast_distractorAdded'] = 'Distractor added.';
$string['quiz_toast_textRephrased'] = 'Text rephrased.';
$string['quiz_toast_editingEnded'] = 'Editing ended.';
$string['quiz_toast_genericError'] = 'An error occurred. Please try again with a different selection.';

// Quiz modal messages
$string['quiz_modal_removingQuestion'] = 'Removing question...';
$string['quiz_modal_changingQuestion'] = 'Modifying question...';
$string['quiz_modal_updatingQuestion'] = 'Updating question...';
$string['quiz_modal_deletingOption'] = 'Deleting option...';
$string['quiz_modal_addingDistractor'] = 'Adding distractor...';
$string['quiz_modal_rephrasingText'] = 'Rephrasing text...';

// Quiz button labels
$string['quiz_button_addBlankDistractor'] = 'Add blank distractor';
$string['quiz_button_addAIDistractor'] = 'Add distractor with AI';
$string['quiz_button_editQuestion'] = 'Edit question';
$string['quiz_button_deleteQuestion'] = 'Delete question';
$string['quiz_button_deleteOption'] = 'Delete option';
$string['quiz_button_rephraseText'] = 'Rephrase text with AI';
$string['quiz_button_done'] = 'Done';
$string['quiz_button_submit'] = 'Check answer';
$string['quiz_button_openQuestionBank'] = 'Open question bank';

// Question bank and carousel messages
$string['quiz_message_noQuestionsAvailable'] = 'No questions available for this page';
$string['quiz_button_previousQuestion'] = 'Previous question';
$string['quiz_button_nextQuestion'] = 'Next question';
$string['quiz_message_loadingQuestions'] = 'Loading questions...';
$string['quiz_message_loadFailed'] = 'Unable to load questions';

// Check models page
$string['aiurl_notconfigured'] = 'LLM Server URL is not configured. Please configure it in the plugin settings.';
$string['checking_models_at'] = 'Checking available models at: {$a}';
$string['connection_error'] = 'Connection error: {$a}';
$string['http_error'] = 'HTTP error code: {$a}';
$string['models_found'] = '{$a} model(s) found on server';
$string['model_name'] = 'Model Name';
$string['model_size'] = 'Size';
$string['model_modified'] = 'Last Modified';
$string['copy_model_name'] = 'Copy the model name from the table above and paste it into the "AI model name" setting.';
$string['no_models_found'] = 'No models found on server';
$string['back_to_settings'] = 'Back to settings';

// PDF download feature
$string['features_downloadPDF_label'] = 'Download page as PDF';
$string['features_downloadPDF_button'] = 'PDF';
$string['features_downloadPDF_generating'] = '...';
$string['features_downloadPDF_annotationsSummaryTitle'] = 'Annotations and Comments';
$string['features_downloadPDF_unknownAuthor'] = 'Unknown';
$string['features_downloadPDF_errorMessage'] = 'PDF export failed. Please try again.';

// Content edit warning
$string['contenteditwarning'] = 'Editing the text may cause annotations such as colour highlights or bookmarks to no longer appear in the intended position.';
