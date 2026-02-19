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
 * Page configuration form
 *
 * @package mod_longpage
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/longpage/locallib.php');
require_once($CFG->libdir . '/filelib.php');

/**
 * Module instance settings form.
 */
class mod_longpage_mod_form extends moodleform_mod {
    /**
     * Defines forms elements.
     */
    public function definition() {
        global $CFG, $DB, $PAGE;

        // CRITICAL: Force standards mode at page level
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
        }
        
        // Ensure proper DOCTYPE is enforced
        $PAGE->set_docs_path('');
        $PAGE->set_pagelayout('admin');
        
        // CRITICAL: Prevent any Vue.js conflicts immediately
        if (!defined('EDITING_LONGPAGE_MODULE')) {
            define('EDITING_LONGPAGE_MODULE', true);
        }
        
        // Load conflict prevention script in head with highest priority
        $PAGE->requires->js('/mod/longpage/editor_fix.js', true);
        
        // Force standards mode through meta tags and inline script
        $PAGE->requires->js_amd_inline('
            // IMMEDIATE standards mode enforcement
            if (!document.doctype || document.compatMode !== "CSS1Compat") {
                console.warn("Longpage: Document not in standards mode, applying emergency fix");
                
                // Override TinyMCE compatibility check
                if (typeof window.tinymce !== "undefined") {
                    window.tinymce.Env = window.tinymce.Env || {};
                    window.tinymce.Env.quirks = false;
                    window.tinymce.Env.webkit = false;
                }
                
                // Override document.compatMode if needed
                if (document.compatMode !== "CSS1Compat") {
                    try {
                        Object.defineProperty(document, "compatMode", {
                            value: "CSS1Compat",
                            writable: false,
                            configurable: false
                        });
                    } catch(e) {
                        console.log("Could not override compatMode:", e.message);
                    }
                }
            }
            
            requirejs.undef("mod_longpage/app-lazy");
            window.EDITING_LONGPAGE_MODULE = true;
            console.log("Longpage: Editing context established, standards mode enforced");
        ');

        $mform = $this->_form;

        $config = get_config('longpage');

        // -------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement(
            'text',
            'name',
            get_string('name'),
            ['size' => '48']
        );
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule(
            'name',
            get_string('maximumchars', '', 255),
            'maxlength',
            255,
            'client'
        );
        $this->standard_intro_elements();

        // -------------------------------------------------------
        $mform->addElement('header', 'contentsection', get_string('contentheader', 'longpage'));
        $mform->addElement(
            'editor',
            'longpage',
            get_string('content', 'longpage'),
            null,
            longpage_get_editor_options($this->context)
        );
        $mform->addRule('longpage', get_string('required'), 'required', null, 'client');

        // -------------------------------------------------------
        $mform->addElement('header', 'appearancehdr', get_string('appearance'));

        if ($this->current->instance) {
            $options = resourcelib_get_displayoptions(
                explode(',', $config->displayoptions),
                $this->current->display
            );
        } else {
            $options = resourcelib_get_displayoptions(explode(',', $config->displayoptions));
        }
        if (count($options) == 1) {
            $mform->addElement('hidden', 'display');
            $mform->setType('display', PARAM_INT);
            reset($options);
            $mform->setDefault('display', key($options));
        } else {
            $mform->addElement('select', 'display', get_string('displayselect', 'longpage'), $options);
            $mform->setDefault('display', $config->display);
        }

        if (array_key_exists(RESOURCELIB_DISPLAY_POPUP, $options)) {
            $mform->addElement('text', 'popupwidth', get_string('popupwidth', 'longpage'), ['size' => 3]);
            if (count($options) > 1) {
                $mform->disabledIf('popupwidth', 'display', 'noteq', RESOURCELIB_DISPLAY_POPUP);
            }
            $mform->setType('popupwidth', PARAM_INT);
            $mform->setDefault('popupwidth', $config->popupwidth);

            $mform->addElement('text', 'popupheight', get_string('popupheight', 'longpage'), ['size' => 3]);
            if (count($options) > 1) {
                $mform->disabledIf('popupheight', 'display', 'noteq', RESOURCELIB_DISPLAY_POPUP);
            }
            $mform->setType('popupheight', PARAM_INT);
            $mform->setDefault('popupheight', $config->popupheight);
        }

        $mform->addElement(
            'advcheckbox',
            'printheading',
            get_string('printheading', 'longpage')
        );
        $mform->setDefault('printheading', $config->printheading);
        $mform->addElement('advcheckbox', 'printintro', get_string('printintro', 'longpage'));
        $mform->setDefault('printintro', $config->printintro);

        $mform->addElement(
            'advcheckbox',
            'showreadingprogress',
            get_string('showreadingprogress', 'longpage')
        );
        $mform->setType('showreadingprogress', PARAM_BOOL);
        $mform->setDefault('showreadingprogress', $config->showreadingprogress);

        $mform->addElement(
            'advcheckbox',
            'showreadingcomprehension',
            get_string('showreadingcomprehension', 'longpage')
        );
        $mform->setType('showreadingcomprehension', PARAM_BOOL);
        $mform->setDefault('showreadingcomprehension', $config->showreadingcomprehension);

        $mform->addElement('advcheckbox', 'showsearch', get_string('showsearch', 'longpage'));
        $mform->setType('showsearch', PARAM_BOOL);
        $mform->setDefault('showsearch', $config->showsearch);

        $mform->addElement('advcheckbox', 'showtableofcontents', get_string('showtableofcontents', 'longpage'));
        $mform->setType('showtableofcontents', PARAM_BOOL);
        $mform->setDefault('showtableofcontents', $config->showtableofcontents);

        $mform->addElement('advcheckbox', 'showposts', get_string('showposts', 'longpage'));
        $mform->setType('showposts', PARAM_BOOL);
        $mform->setDefault('showposts', $config->showposts);

        $mform->addElement('advcheckbox', 'showhighlights', get_string('showhighlights', 'longpage'));
        $mform->setType('showhighlights', PARAM_BOOL);
        $mform->setDefault('showhighlights', $config->showhighlights);

        $mform->addElement('advcheckbox', 'showbookmarks', get_string('showbookmarks', 'longpage'));
        $mform->setType('showbookmarks', PARAM_BOOL);
        $mform->setDefault('showbookmarks', $config->showbookmarks);

        $mform->addElement(
            'advcheckbox',
            'showeditquestionsnoai',
            get_string('showeditquestionsnoai', 'longpage')
        );
        $mform->setType('showeditquestionsnoai', PARAM_BOOL);
        $mform->setDefault('showeditquestionsnoai', $config->showeditquestionsnoai);

        $mform->addElement(
            'advcheckbox',
            'showeditquestionsai',
            get_string('showeditquestionsai', 'longpage')
        );
        $mform->setType('showeditquestionsai', PARAM_BOOL);
        $mform->setDefault('showeditquestionsai', $config->showeditquestionsai);

        // Add legacy files flag only if used.
        if (isset($this->current->legacyfiles) && $this->current->legacyfiles != RESOURCELIB_LEGACYFILES_NO) {
            $options = [RESOURCELIB_LEGACYFILES_DONE   => get_string('legacyfilesdone', 'longpage'),
                             RESOURCELIB_LEGACYFILES_ACTIVE => get_string('legacyfilesactive', 'longpage')];
            $mform->addElement('select', 'legacyfiles', get_string('legacyfiles', 'longpage'), $options);
            $mform->setAdvanced('legacyfiles', 1);
        }

        // Grade settings.
        $this->standard_grading_coursemodule_elements();

        // -------------------------------------------------------
        $this->standard_coursemodule_elements();

        // -------------------------------------------------------
        $this->add_action_buttons();

        // -------------------------------------------------------
        $mform->addElement('hidden', 'revision');
        $mform->setType('revision', PARAM_INT);
        $mform->setDefault('revision', 1);
    }

    /**
     * Perform minimal validation on the settings form.
     *
     * @param array $defaultvalues Default values.
     */
    public function data_preprocessing(&$defaultvalues) {
        if ($this->current->instance) {
            $draftitemid = file_get_submitted_draft_itemid('longpage');
            $defaultvalues['longpage']['format'] = $defaultvalues['contentformat'];
            $defaultvalues['longpage']['text'] = file_prepare_draft_area(
                $draftitemid,
                $this->context->id,
                'mod_longpage',
                'content',
                0,
                longpage_get_editor_options($this->context),
                $defaultvalues['content']
            );
            $defaultvalues['longpage']['itemid'] = $draftitemid;
        }
        if (!empty($defaultvalues['displayoptions'])) {
            $displayoptions = unserialize($defaultvalues['displayoptions']);
            if (isset($displayoptions['printintro'])) {
                $defaultvalues['printintro'] = $displayoptions['printintro'];
            }
            if (isset($displayoptions['printheading'])) {
                $defaultvalues['printheading'] = $displayoptions['printheading'];
            }
            if (!empty($displayoptions['popupwidth'])) {
                $defaultvalues['popupwidth'] = $displayoptions['popupwidth'];
            }
            if (!empty($displayoptions['popupheight'])) {
                $defaultvalues['popupheight'] = $displayoptions['popupheight'];
            }
        }
    }
}
