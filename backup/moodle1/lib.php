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
 * Provides support for the conversion of moodle1 backup to the moodle2 format
 *
 * @package mod_longpage
 * @copyright  2011 Andrew Davis <andrew@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * longpage conversion handler. This resource handler is called by moodle1_mod_resource_handler
 */
class moodle1_mod_longpage_handler extends moodle1_resource_successor_handler {
    /** @var moodle1_file_manager instance */
    protected $fileman = null;

    /**
     * Converts /MOODLE_BACKUP/COURSE/MODULES/MOD/RESOURCE data
     * Called by moodle1_mod_resource_handler::process_resource()
     */
    public function process_legacy_resource(array $data, array $raw = null) {

        // Get the course module id and context id.
        $instanceid = $data['id'];
        $cminfo     = $this->get_cminfo($instanceid, 'resource');
        $moduleid   = $cminfo['id'];
        $contextid  = $this->converter->get_contextid(CONTEXT_MODULE, $moduleid);

        // Convert the legacy data onto the new longpage record.
        $longpage                       = [];
        $longpage['id']                 = $data['id'];
        $longpage['name']               = $data['name'];
        $longpage['intro']              = $data['intro'];
        $longpage['introformat']        = $data['introformat'];
        $longpage['content']            = $data['alltext'];

        if ($data['type'] === 'html') {
            // Legacy Resource of the type Web longpage.
            $longpage['contentformat'] = FORMAT_HTML;
        } else {
            // Legacy Resource of the type Plain text longpage.
            $longpage['contentformat'] = (int)$data['reference'];

            if ($longpage['contentformat'] < 0 || $longpage['contentformat'] > 4) {
                $longpage['contentformat'] = FORMAT_MOODLE;
            }
        }

        $longpage['legacyfiles']        = RESOURCELIB_LEGACYFILES_ACTIVE;
        $longpage['legacyfileslast']    = null;
        $longpage['revision']           = 1;
        $longpage['timemodified']       = $data['timemodified'];

        // Populate display and displayoptions fields.
        $options = ['printheading' => 1, 'printintro' => 0];
        if ($data['popup']) {
            $longpage['display'] = RESOURCELIB_DISPLAY_POPUP;
            $rawoptions = explode(',', $data['popup']);
            foreach ($rawoptions as $rawoption) {
                [$name, $value] = explode('=', trim($rawoption), 2);
                if ($value > 0 && ($name == 'width' || $name == 'height')) {
                    $options['popup' . $name] = $value;
                    continue;
                }
            }
        } else {
            $longpage['display'] = RESOURCELIB_DISPLAY_OPEN;
        }
        $longpage['displayoptions'] = serialize($options);

        // Get a fresh new file manager for this instance.
        $this->fileman = $this->converter->get_file_manager($contextid, 'mod_longpage');

        // Convert course files embedded into the intro.
        $this->fileman->filearea = 'intro';
        $this->fileman->itemid   = 0;
        $longpage['intro'] = moodle1_converter::migrate_referenced_files($longpage['intro'], $this->fileman);

        // Convert course files embedded into the content.
        $this->fileman->filearea = 'content';
        $this->fileman->itemid   = 0;
        $longpage['content'] = moodle1_converter::migrate_referenced_files($longpage['content'], $this->fileman);

        // Write longpage.xml.
        $this->open_xml_writer("activities/longpage_{$moduleid}/longpage.xml");
        $this->xmlwriter->begin_tag('activity', ['id' => $instanceid, 'moduleid' => $moduleid,
            'modulename' => 'longpage', 'contextid' => $contextid]);
        $this->write_xml('longpage', $longpage, ['/longpage/id']);
        $this->xmlwriter->end_tag('activity');
        $this->close_xml_writer();

        // Write inforef.xml for migrated resource file.
        $this->open_xml_writer("activities/longpage_{$moduleid}/inforef.xml");
        $this->xmlwriter->begin_tag('inforef');
        $this->xmlwriter->begin_tag('fileref');
        foreach ($this->fileman->get_fileids() as $fileid) {
            $this->write_xml('file', ['id' => $fileid]);
        }
        $this->xmlwriter->end_tag('fileref');
        $this->xmlwriter->end_tag('inforef');
        $this->close_xml_writer();
    }
}
