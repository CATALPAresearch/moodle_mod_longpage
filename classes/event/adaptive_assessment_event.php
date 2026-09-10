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
 * Event fired whenever an ELO rating update occurs.
 *
 * @package    mod_longpage
 * @category   event
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_longpage\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Logs each adaptive assessment ELO update for analysis purposes.
 */
class adaptive_assessment_event extends \core\event\base {
    /**
     * @return string Human-readable event name.
     */
    public static function get_name() {
        return get_string('event_adaptive_assessment', 'mod_longpage');
    }

    /**
     * Initialise the event data.
     */
    public function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'question';
    }

    /**
     * Build an event instance from a log entry array.
     *
     * @param array $logentry
     * @param int   $courseid
     * @return self
     */
    public static function create_from_log_entry(array $logentry, int $courseid): self {
        if (!$courseid || !get_course($courseid)) {
            throw new \moodle_exception('invalidcourseid', 'mod_longpage');
        }

        $context = \context_course::instance($courseid);
        if (!$context) {
            throw new \moodle_exception('invalidcontext', 'mod_longpage');
        }

        return self::create([
            'context' => $context,
            'userid'  => $logentry['userid'],
            'objectid' => $logentry['questionid'],
            'other'   => [
                'timestamp'               => $logentry['timestamp'],
                'questionid'              => $logentry['questionid'],
                'userid'                  => $logentry['userid'],
                'question_difficulty_old' => $logentry['question_difficulty_old'],
                'question_difficulty_new' => $logentry['question_difficulty_new'],
                'user_comprehension_old'  => $logentry['user_comprehension_old'],
                'user_comprehension_new'  => $logentry['user_comprehension_new'],
                'answer_correct'          => (int) $logentry['answer_correct'],
                'question_attempt'        => (int) $logentry['question_attempt'],
                'user_update_times'       => (int) $logentry['user_update_times'],
            ],
        ]);
    }
}
