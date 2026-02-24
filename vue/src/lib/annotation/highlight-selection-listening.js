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
import { AnnotationType, HighlightingConfig } from "../../util/constants";

/**
 * Return the annotations associated with any highlights that contain a given
 * DOM node.
 *
 * @param {Node} node
 * @return {AnnotationData[]}
 */
const getAnnotationsAnchoredAt = (node) => {
  const items = getHighlightsContainingNode(node)
    .map((h) => /** @type {AnnotationHighlight} */ (h)._annotation)
    .filter((ann) => ann !== undefined);

  return /** @type {AnnotationData[]} */ (items);
};

/**
 * Return the highlights associated that contain a given
 * DOM node.
 *
 * @param {Node} node
 * @return {AnnotationData[]}
 */
export const getHighlightsAnchoredAt = (node) => {
  return getAnnotationsAnchoredAt(node).filter(
    (annotation) => annotation.type === AnnotationType.HIGHLIGHT,
  );
};

/**
 * Get the highlight elements that contain the given node.
 *
 * @param {Node} node
 * @return {HighlightElement[]}
 */
const getHighlightsContainingNode = (node) => {
  let el =
    node.nodeType === Node.ELEMENT_NODE
      ? /** @type {Element} */ (node)
      : node.parentElement;
  const highlights = [];
  while (el) {
    if (el.classList.contains(HighlightingConfig.HL_CLASS_NAME)) {
      highlights.push(/** @type {HighlightElement} */ (el));
    }
    el = el.parentElement;
  }
  return highlights;
};
