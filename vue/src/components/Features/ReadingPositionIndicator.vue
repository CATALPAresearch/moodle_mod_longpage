<template>
  <div></div>
</template>

<script>
import ajax from "core/ajax";
import { prepareContentElements } from "@/lib/longpageContent/prepare-content-elements";

/**
 * Shows the COLLECTIVE reading position/progress on the course/longpage text:
 * how many times each section has been read across all users (the small
 * "progress-N" badge shown next to each paragraph), backed by the
 * longpage_reading_positions table (see mod_longpage_get_reading_progress /
 * mod_longpage_update_reading_progress).
 *
 * Not to be confused with ReadingBehaviorTracker.vue, which classifies HOW
 * an individual user is reading right now (scan/read/study/...). The two
 * were previously combined in one file (ReadingProgress.vue), which was a
 * recurring source of confusion given how similar "reading progress" and
 * "reading behavior" sound — hence the split.
 */
export default {
  name: "ReadingPositionIndicator",
  props: ["context"],

  data: function () {
    return {
      debug: false,
    };
  },

  mounted: function () {
    prepareContentElements(this.context);
    if (this.context.showreadingprogress) {
      this.visualizeReadingProgress();
    }
  },

  methods: {
    visualizeReadingProgress: function () {
      let _this = this;

      ajax.call([
        {
          methodname: "mod_longpage_get_reading_progress",
          args: {
            courseid: _this.context.courseId,
            longpageid: _this.context.longpageid,
          },
          done: function (reads) {
            try {
              let data = Object.values(JSON.parse(reads.response));
              let max_arr = data.map(function (d) {
                return d.count;
              });
              let max = max_arr.reduce((a, b) => Math.max(a, b), -Infinity);
              for (var i = 0; i < data.length; i++) {
                const sectionEl = document.getElementById(data[i].section);
                if (sectionEl) {
                  const progressEl = sectionEl.nextElementSibling;
                  if (
                    progressEl &&
                    progressEl.classList.contains("reading-progress")
                  ) {
                    progressEl.setAttribute(
                      "title",
                      "Der Abschnitt wurde <br>bislang " +
                        data[i].count +
                        " mal gelesen.",
                    );
                    progressEl.classList.add(
                      "progress-" + Math.ceil((data[i].count / max) * 5),
                    );
                  }

                  if (_this.debug) {
                    const span = document.createElement("span");
                    span.style.cssText =
                      "position:absolute; right:-40px; font-size:8px; background-color:red; padding:1px 2px; color:#fff;";
                    span.textContent = data[i].section.replace(
                      "longpage-paragraph-",
                      "",
                    );
                    sectionEl.appendChild(span);
                  }
                } else {
                  console.log("Section not found", data[i].section);
                }
              }
            } catch (e) {
              console.log(e);
            }
          },
          fail: function (e) {
            console.error("fail", e);
          },
        },
      ]);
    },
  },
};
</script>

<style></style>
