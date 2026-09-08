<template>
  <div>
    <div id="longpage-app" class="row no-gutters w-100" tabindex="0">
      <div
        id="longpage-main"
        ref="mainRef"
        class="col overflow-y-auto overflow-x-hidden row no-gutters justify-content-center p-3 vh-100-wo-nav"
      >
        <!-- removed from longpage-pain: vh-100-wo-nav -->
        <annotation-toolbar-controller
          v-if="
            context.showhighlights || context.showposts || context.showbookmarks
          "
        />

        <div
          id="longpage-content"
          ref="contentRef"
          class="generalbox center clearfix col w-100"
          lang="de"
          v-html="content"
        />

        <div
          class="col col-auto p-0 mx-1"
          style="width: 35px"
          v-if="
            context.showhighlights || context.showposts || context.showbookmarks
          "
        >
          <annotation-indicator-sidebar />
        </div>
      </div>
      <longpage-sidebar class="col-auto" />
    </div>
    <!-- <CourseRecommendation></CourseRecommendation> -->
    <ReadingTime v-if="context.showreadingtime"></ReadingTime>
    <ReadingPositionIndicator :context="context"> </ReadingPositionIndicator>
    <ReadingBehaviorTracker :context="context"> </ReadingBehaviorTracker>
  </div>
</template>

<script>
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
import { ACT, GET } from "./store/types";
import { AnnotationType, LONGPAGE_CONTENT_ID } from "@/util/constants";
import AnnotationToolbarController from "@/components/LongpageContent/Annotations/AnnotationToolbarController";
import { EventBus } from "@/lib/core/event-bus";
import { getHighlightsAnchoredAt } from "@/lib/annotation/highlight-selection-listening";
import Log from "./lib/core/Logging";
import LongpageSidebar from "@/components/LongpageSidebar";
import { mapActions, mapGetters } from "vuex";
import AnnotationIndicatorSidebar from "@/components/LongpageContent/Annotations/AnnotationIndicatorSidebar";
//import { ReadingTimeEstimator } from "@/lib/reading-time-estimator";
import { toIdSelector } from "@/util/dom/style";
import Utils from "./util/utils";

import ReadingPositionIndicator from "@/components/Features/ReadingPositionIndicator";
import ReadingBehaviorTracker from "@/components/Features/ReadingBehaviorTracker";
import ReadingTime from "@/components/Features/ReadingTime";
import CourseRecommendation from "@/components/Features/CourseRecommendations";

export default {
  name: "App",
  components: {
    AnnotationToolbarController,
    AnnotationIndicatorSidebar,
    LongpageSidebar,
    ReadingPositionIndicator,
    ReadingBehaviorTracker,
    CourseRecommendation,
    ReadingTime,
  },
  props: {
    content: { type: String, required: true },
    scrollTop: { type: Number, default: 0 },
  },
  data() {
    return {
      eventListeners: [],
      // readingTimeEstimator: new ReadingTimeEstimator(
      //   toIdSelector(LONGPAGE_CONTENT_ID)
      // ),
      tabContentVisible: false,
    };
  },
  computed: {
    logger() {
      return this.context
        ? new Log(new Utils(), this.context.courseId, {
            context: "mod_longpage",
            outputType: 1,
          })
        : null;
    },
    ...mapGetters({ context: GET.LONGPAGE_CONTEXT }),
  },
  watch: {
    context: {
      handler(newVal) {
        // Context updated
      },
      immediate: true,
      deep: true,
    },
  },
  mounted() {
    // Restore the last reading position immediately (no waiting for
    // page-ready). Recording a NEW position is no longer done on every
    // scroll tick here — see ReadingBehaviorTracker.vue, which calls
    // UPDATE_READING_PROGRESS only for data points classified as genuine
    // reading (read/study/regression), not for a fast scroll-through.
    this.$nextTick(() => {
      this.$refs.mainRef.scrollTop = this.scrollTop; // * this.$refs.mainRef.scrollHeight;
    });

    var _this = this;

    EventBus.subscribe("searchresult-selected", (logdata) => {
      _this.logger.add("searchresult_selected", logdata);
    });
    EventBus.subscribe("searchterm-entered", (logdata) => {
      _this.logger.add("searchterm_entered", logdata);
    });
    this.$refs.mainRef.addEventListener("click", (event) => {
      const highlightsAtClickCoords = getHighlightsAnchoredAt(event.target);
      EventBus.publish("annotations-selected", {
        type: highlightsAtClickCoords.length
          ? AnnotationType.HIGHLIGHT
          : undefined,
        selection: highlightsAtClickCoords,
      });
    });

    // Defer MathJax typesetting for faster initial render
    if (window.requestIdleCallback) {
      requestIdleCallback(() => {
        Y.use("mathjax", () => {
          MathJax.Hub.Queue(["Typeset", MathJax.Hub, this.$refs.contentRef]);
        });
      });
    } else {
      setTimeout(() => {
        Y.use("mathjax", () => {
          MathJax.Hub.Queue(["Typeset", MathJax.Hub, this.$refs.contentRef]);
        });
      }, 100);
    }

    // Only fetch user roles and canMod if not pre-loaded from PHP
    if (!this.$store.state.UserModule.userRoles.length) {
      this[ACT.FETCH_USER_ROLES]();
      this[ACT.USER_CAN_MOD_ANNOTATION]();
    }
    this[ACT.FETCH_ENROLLED_USERS]();
    this[ACT.FETCH_ANNOTATIONS]();

    // Log bootstrap interactions
    document.querySelectorAll(".longpage-citation").forEach((el) => {
      el.addEventListener("click", function () {
        _this.log("citation_view", { citation: this.dataset.content });
      });
    });
    document.querySelectorAll(".longpage-footnote").forEach((el) => {
      el.addEventListener("click", function () {
        const button = this.querySelector("button");
        _this.log("footnote_view", {
          title: button?.dataset.originalTitle,
          text: button?.dataset.content,
        });
      });
    });
    document.querySelectorAll(".longpage-crossref").forEach((el) => {
      el.addEventListener("click", function () {
        _this.log("crossref_follow", {
          source: this.textContent,
          target: this.getAttribute("href"),
          parent: this.parentElement?.getAttribute("id"),
        });
      });
    });
    document.querySelectorAll(".longpage-assignment-link").forEach((el) => {
      el.addEventListener("click", function () {
        _this.log("assignment_open", { target: this.getAttribute("href") });
      });
    });
    // ["h2", "h3"].forEach((hTag) => {
    //   this.readingTimeEstimator.calcAndDisplay(hTag);
    // });
  },
  methods: {
    ...mapActions([
      ACT.FETCH_ENROLLED_USERS,
      ACT.FETCH_USER_ROLES,
      ACT.USER_CAN_MOD_ANNOTATION,
    ]),
    ...mapActions("annotation", [ACT.FETCH_ANNOTATIONS]),
    log(key, values) {
      this.logger.add(key, values);
    },
  },
};
</script>
