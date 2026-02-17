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
import "./components/LongpageContent/Footnote";
import "./lib/hashchange-listening";
import "./lib/page-ready-listening";
import "./lib/scroll-snapping";
import App from "./App.vue";
import { createApp } from "vue";
import { initStore } from "@/store";
import { LONGPAGE_APP_CONTAINER_ID } from "@/config/constants";
import { toIdSelector } from "@/util/style";
import { i18n } from "@/config/i18n";

export const init = async (
  courseId,
  longpageid,
  pageName,
  userId,
  content,
  scrollTop,
  showreadingprogress,
  showreadingcomprehension,
  showsearch,
  showtableofcontents,
  showposts,
  showhighlights,
  showbookmarks,
  showeditquestionsnoai,
  showeditquestionsai,
  tags,
  isAdmin,
) => {
  try {
    const store = initStore({
      courseId: Number(courseId),
      longpageid: Number(longpageid),
      pageName,
      userId: Number(userId),
      showreadingprogress: Boolean(showreadingprogress),
      showreadingcomprehension: Boolean(showreadingcomprehension),
      showsearch: Boolean(showsearch),
      showtableofcontents: Boolean(showtableofcontents),
      showposts: Boolean(showposts),
      showhighlights: Boolean(showhighlights),
      showbookmarks: Boolean(showbookmarks),
      showeditquestionsnoai: Boolean(showeditquestionsnoai),
      showeditquestionsai: Boolean(showeditquestionsai),
      showEditQuestionsNoAI: Boolean(showeditquestionsnoai),
      showEditQuestionsAI: Boolean(showeditquestionsai),
      tags: Array.isArray(tags) ? tags : [],
      isAdmin: Boolean(isAdmin),
    });

    // Load ALL language strings from PHP backend
    const lang = document
      .getElementsByTagName("html")[0]
      .getAttribute("lang")
      .replace(/-/g, "_");
    await store.dispatch("loadComponentStrings", lang);
    i18n.global.setLocaleMessage(lang, store.state.strings);

    const tmpElement = document.getElementById("longpage-tmp");
    content = tmpElement ? tmpElement.innerHTML : "";

    const app = createApp(App, { content, scrollTop: Number(scrollTop) });
    app.use(store);
    app.use(i18n);
    app.mount(toIdSelector(LONGPAGE_APP_CONTAINER_ID));
  } catch (e) {
    /* eslint-disable no-console */
    console.error("Longpage initialization error:", e);
  }
};
