<template>
  <div>
    <div v-if="!pageReady" class="row no-gutters vh-50">
      <div class="spinner-border m-auto" role="status">
        <span class="sr-only" />
      </div>
    </div>
    <div v-show="pageReady" id="longpage-app" class="row no-gutters w-100" tabindex="0">
      <div
        id="longpage-main"
        ref="mainRef"
        class="
          col
          overflow-y-auto
          overflow-x-hidden
          row
          no-gutters
          justify-content-center
          p-3
          vh-100-wo-nav
        "
      >
        <!-- removed from longpage-pain: vh-100-wo-nav -->
            <annotation-toolbar-controller v-if="context.showhighlights || context.showposts || context.showbookmarks"/>

        <div
          id="longpage-content"
          ref="contentRef"
          class="generalbox center clearfix col w-100"
          lang="de"
          v-html="content"
        />
        <div style="position: absolute; right: 0">
          <!-- <DownloadPDF /> -->
        </div>
        <div class="col col-auto p-0 mx-1" style="width: 35px" v-if="context.showhighlights || context.showposts || context.showbookmarks">
          <annotation-indicator-sidebar   />
        </div>
      </div>
      <longpage-sidebar class="col-auto" />
    </div>
    <!-- <CourseRecommendation></CourseRecommendation> -->
    <ReadingTime v-if="context.showreadingprogress"></ReadingTime>
    <ReadingProgress :context="context"> </ReadingProgress>
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
import {
  AnnotationType,
  LONGPAGE_CONTENT_ID,
  LONGPAGE_MAIN_ID,
} from "@/config/constants";
import AnnotationToolbarController from "@/components/LongpageContent/AnnotationToolbarController";
import { EventBus } from "@/lib/event-bus";
import { getHighlightsAnchoredAt } from "@/lib/annotation/highlight-selection-listening";
import Log from "./lib/Logging";
import LongpageSidebar from "@/components/LongpageSidebar";
import { mapActions, mapGetters } from "vuex";
import AnnotationIndicatorSidebar from "@/components/LongpageContent/AnnotationIndicatorSidebar";
//import { ReadingTimeEstimator } from "@/lib/reading-time-estimator";
import throttle from 'lodash/throttle';
import { toIdSelector } from "@/util/style";
import Utils from "./util/utils";

import ReadingProgress from "@/components/Generic/ReadingProgress";
import ReadingTime from "@/components/Generic/ReadingTime";
import CourseRecommendation from "@/components/Generic/CourseRecommendations";

export default {
  name: "App",
  components: {
    AnnotationToolbarController,
    AnnotationIndicatorSidebar,
    LongpageSidebar,
    ReadingProgress,
    CourseRecommendation,
    ReadingTime
  },
  props: {
    content: { type: String, required: true },
    scrollTop: { type: Number, default: 0 },
  },
  data() {
    return {
      eventListeners: [],
      pageReady: false,
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
  mounted() {
    EventBus.subscribe("page-ready", () => {
      this.pageReady = true;
      this.$nextTick(() => {
        this.$refs.mainRef.scrollTop = this.scrollTop; // * this.$refs.mainRef.scrollHeight;
        document.getElementById(LONGPAGE_MAIN_ID).addEventListener(
          "scroll",
          throttle(
            ($event) => {
              this[ACT.UPDATE_READING_PROGRESS](
                $event.target.scrollTop / $event.target.scrollHeight
              );
            },
            1000,
            { leading: false }
          )
        );
      });
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
    Y.use('mathjax', () => {
      MathJax.Hub.Queue(['Typeset', MathJax.Hub, this.$refs.contentRef]);
    });
    this[ACT.FETCH_USER_ROLES]();
    this[ACT.FETCH_ENROLLED_USERS]();
    this[ACT.USER_CAN_MOD_ANNOTATION]();

    // Log bootstrap interactions
    $(".longpage-citation").click(function () {
      _this.log("citation_view", { citation: $(this).data("content") });
    });
    $(".longpage-footnote").click(function () {
      _this.log("footnote_view", {
        title: $(this).find("button").data("original-title"),
        text: $(this).find("button").data("content"),
      });
    });
    $(".longpage-crossref").click(function () {
      _this.log("crossref_follow", {
        source: $(this).text(),
        target: $(this).attr("href"),
        parent: $(this).parent().attr("id"),
      });
    });
    $(".longpage-assignment-link").click(function () {
      _this.log("assignment_open", { target: $(this).attr("href") });
    });
    // ["h2", "h3"].forEach((hTag) => {
    //   this.readingTimeEstimator.calcAndDisplay(hTag);
    // });
  },
  methods: {
    ...mapActions([
      ACT.FETCH_ENROLLED_USERS,
      ACT.FETCH_USER_ROLES,
      ACT.UPDATE_READING_PROGRESS,
      ACT.USER_CAN_MOD_ANNOTATION,
    ]),
    log(key, values) {
      this.logger.add(key, values);
    },
  },
};
</script>
