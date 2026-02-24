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
import {
  getPostIdFromItsDOMId,
  getThreadIdFromItsDOMId,
  isDOMIdOfPost,
  isDOMIdOfThread,
} from "@/util/dom/annotation";
import { EventBus } from "@/lib/core/event-bus";

const scrollInPost = () => {
  const hash = window.location.hash.substring(1);
  if (!isDOMIdOfPost(hash)) return;

  const postId = getPostIdFromItsDOMId(hash);

  EventBus.publish("post-selected-by-url-hash", { postId, postDOMId: hash });
};

const scrollInThread = () => {
  const hash = window.location.hash.substring(1);
  if (!isDOMIdOfThread(hash)) return;

  const threadId = getThreadIdFromItsDOMId(hash);

  EventBus.publish("thread-selected-by-url-hash", {
    threadId,
    threadDOMId: hash,
  });
};

window.addEventListener("hashchange", () => {
  scrollInPost();
  scrollInThread();
});

EventBus.subscribe("page-ready", () => {
  scrollInPost();
  scrollInThread();
});
