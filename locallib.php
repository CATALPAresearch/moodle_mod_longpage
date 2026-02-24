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
 * Private page module utility functions
 *
 * @package mod_longpage
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/filelib.php");
require_once("$CFG->libdir/resourcelib.php");
require_once("$CFG->dirroot/mod/longpage/lib.php");

/**
 * Get course module by page ID.
 *
 * @param int $pageid The page ID
 * @return object Course module object
 */
function get_coursemodule_by_pageid($pageid) {
    global $DB;

    $page = $DB->get_record('longpage', ['id' => $pageid], '*', MUST_EXIST);
    return get_coursemodule_from_instance('longpage', $page->id, $page->course, false, MUST_EXIST);
}

/**
 * Get user IDs enrolled in a page.
 *
 * @param int $pageid The page ID
 * @param int $limitfrom Limit from
 * @param int $limitnum Limit number
 * @return array Array of user IDs
 */
function get_page_users_ids($pageid, $limitfrom = 0, $limitnum = 100) {
    $cm = get_coursemodule_by_pageid($pageid);
    $context = \context_module::instance($cm->id);
    $users = get_enrolled_users($context, '', 0, 'u.id', 'timecreated ASC', $limitfrom, $limitnum);
    return array_map(function ($user) {
        return (int) $user->id;
    }, $users);
}

/**
 * Pick specific keys from an array or object.
 *
 * @param array|object $arrorobj Array or object to pick from
 * @param array $keys Keys to pick
 * @param bool $inplace Whether to modify in place
 * @return array|object Filtered array or object
 */
function pick_keys($arrorobj, $keys, $inplace = false) {
    if (!$inplace) {
        $result = array_intersect_key((array) $arrorobj, array_fill_keys($keys, 1));
        return gettype($arrorobj) == 'array' ? $result : (object) $result;
    }

    foreach (array_keys((array) $arrorobj) as $key) {
        if (in_array($key, $keys, false)) {
            continue;
        }
        if (gettype($arrorobj) == 'array') {
            unset($arrorobj[$key]);
        } else {
            unset($arrorobj->{$key});
        }
    }
    return $arrorobj;
}

/**
 * Omit specific keys from an array or object.
 *
 * @param array|object $arrorobj Array or object to omit from
 * @param array $keys Keys to omit
 * @param bool $inplace Whether to modify in place
 * @return array|object Filtered array or object
 */
function omit_keys($arrorobj, $keys, $inplace = false) {
    if (!$inplace) {
        $result = array_diff_key((array) $arrorobj, array_fill_keys($keys, 1));
        return gettype($arrorobj) == 'array' ? $result : (object) $result;
    }

    foreach ($keys as $key) {
        if (gettype($arrorobj) == 'array') {
            unset($arrorobj[$key]);
        } else {
            unset($arrorobj->{$key});
        }
    }
    return $arrorobj;
}

/**
 * Map and merge arrays.
 *
 * @param array $arrays Arrays to map
 * @param array $tomerge Array to merge with each
 * @return array Merged arrays
 */
function array_map_merge($arrays, $tomerge) {
    return array_map(static function ($array) use ($tomerge) {
        return array_merge($array, $tomerge);
    }, $arrays);
}

/**
 * Merge multiple objects into one.
 *
 * @param object ...$objects Objects to merge
 * @return object Merged object
 */
function object_merge(...$objects) {
    $result = [];
    foreach ($objects as $object) {
        $result = array_merge($result, (array) $object);
    }
    return (object) $result;
}

/**
 * File browsing support class
 */
class longpage_content_file_info extends file_info_stored {
    /**
     * Get parent file info.
     *
     * @return object Parent file info
     */
    public function get_parent() {
        if ($this->lf->get_filepath() === '/' && $this->lf->get_filename() === '.') {
            return $this->browser->get_file_info($this->context);
        }
        return parent::get_parent();
    }
    /**
     * Get visible name.
     *
     * @return string Visible name
     */
    public function get_visible_name() {
        if ($this->lf->get_filepath() === '/' && $this->lf->get_filename() === '.') {
            return $this->topvisiblename;
        }
        return parent::get_visible_name();
    }
}

/**
 * Get editor options for longpage.
 *
 * @param object $context Context object
 * @return array Editor options
 * atto:toolbar options are not used by atto. FixMe.
 *
 */
function longpage_get_editor_options($context) {
    global $CFG;

    $toolbar = <<<EOT
collapse = collapse, embedquestion, html
style1 = title, bold, italic
list = unorderedlist, orderedlist, indent
links = link
files = emojipicker, image, media, recordrtc, managefiles, h5p
style2 = underline, strike, subscript, superscript
align = align
insert = equation, charmap, table, clear
undo = undo
accessibility = accessibilitychecker, accessibilityhelper
EOT;

    return [
        'subdirs' => 1,
        'maxbytes' => $CFG->maxbytes,
        'maxfiles' => -1,
        'changeformat' => 1,
        'context' => $context,
        'noclean' => 1,
        'trusttext' => 0,
        'atto:toolbar' => $toolbar,
        'autosave' => false,
    ];
}
