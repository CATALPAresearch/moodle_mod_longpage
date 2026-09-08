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

let preparedElementIds = null;

/**
 * Assigns a stable id to every top-level content element inside
 * #longpage-content and wraps each in a `.wrapper` div — a structural step
 * relied on by several unrelated features (Quiz/index.vue's embedded-question
 * UI, CourseRecommendations.vue), not just the reading-progress badge. If
 * enabled, also attaches the reading-progress badge span next to it.
 *
 * Both ReadingPositionIndicator.vue and ReadingBehaviorTracker.vue need this
 * done before they can do their own work, but neither may assume it runs
 * before the other (Vue does not guarantee sibling mount order in a way
 * either component should depend on). This is idempotent via the
 * module-level cache below: whichever of them mounts first does the real
 * work, the other gets the same (cached) result back.
 *
 * @param {object} context  the shared `context` prop (showreadingprogress, showreadingcomprehension)
 * @returns {string[]} ids of all prepared elements, in DOM order
 */
export function prepareContentElements(context) {
  if (preparedElementIds) {
    return preparedElementIds;
  }

  var pCounter = 0;
  var observedElements = [
    "h2",
    "h3",
    "h4",
    "h5",
    "pre",
    "img",
    "table",
    "p",
    "ol",
    "ul",
    "div",
  ];
  var container = "#longpage-content";

  var containerEl = document.querySelector(
    container + " > .filter_mathjaxloader_equation",
  );
  if (containerEl) {
    container += " > .filter_mathjaxloader_equation";
  }

  var observedSelectors = observedElements.map(function (val) {
    return container + " > " + val;
  });

  var ids = [];

  document
    .querySelectorAll(observedSelectors.join(", "))
    .forEach(function (val) {
      var attr = val.getAttribute("id");
      if (!attr) {
        attr = "paragraph-" + pCounter;
        val.setAttribute("id", attr);
        val.classList.add("longpage-paragraph");
        pCounter++;
      }

      var wrapper = document.createElement("div");
      wrapper.className = "wrapper";
      val.parentNode.insertBefore(wrapper, val);
      wrapper.appendChild(val);

      if (context.showreadingprogress || context.showreadingcomprehension) {
        var span = document.createElement("span");
        span.className = "reading-progress";
        span.setAttribute("data-html2canvas-ignore", "");
        if (context.showreadingprogress) {
          span.setAttribute(
            "title",
            "Der Abschnitt wurde <br>bislang 0 mal gelesen.",
          );
        } else {
          span.classList.add("progress-3");
        }
        wrapper.appendChild(span);
      }

      if (wrapper.querySelector(".filter_embedquestion-iframe")) {
        wrapper.style.height = "0px";
        wrapper.style.padding = "0px";
      }

      ids.push(attr);
    });

  preparedElementIds = ids;
  return ids;
}
