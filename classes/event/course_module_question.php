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

namespace mod_longpage\event;

/**
 * The course module scroll event.
 *
 * @property-read array $other {
 * }
 *
 * @package   mod_longpage
 * @category  event
 * @copyright 
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_module_question extends \core\event\base {

    protected function init() {
        $this->data['objecttable'] = 'longpage';
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    public function get_description() {
        extract($this->data["other"]);

        return "The user with id '$this->userid' changed ($type) a question with id '$questionid' on the 'longpage activity' with course module id " .
            "'$this->contextinstanceid' in course '$this->courseid' (qtype: $qtype, selectedText: '$selectedText', selectedParagraphs: '$selectedParagraphs', useAI: $useAI, existingQuestions: '$existingQuestions', position: $position, embedid: $embedid, elapsedTimeMs: $elapsedTimeMs).";
    }

    public static function get_objectid_mapping() {
        return array('db' => 'longpage', 'restore' => 'longpage');
    }
}
