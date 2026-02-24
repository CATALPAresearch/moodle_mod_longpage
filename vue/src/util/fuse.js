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
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import set from "lodash/set";

const hlStartTag = "<b>";
const hlEndTag = "</b>";

export const mapResultToHighlightedDoc = ({ item, matches }) => {
  return {
    ...item,
    ...matches.reduce((itemHighlighted, { indices, value, key }) => {
      let currentValueIndex = 0;
      return set(
        itemHighlighted,
        key,
        indices.reduce((highlight, [startIdx, endIdx], i) => {
          const result = [
            highlight,
            value.slice(currentValueIndex, startIdx),
            hlStartTag,
            value.slice(startIdx, endIdx + 1),
            hlEndTag,
          ].join("");
          currentValueIndex = endIdx + 1;
          return i === indices.length - 1
            ? [result, value.slice(currentValueIndex)].join("")
            : result;
        }, ""),
      );
    }, {}),
  };
};
