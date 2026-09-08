<template>
  <div></div>
</template>

<script>
import ajax from "core/ajax";
import {
  ReadingBehaviorTracker as Tracker,
  DATA_POINT_LABELS,
  cleanTextPreview,
} from "@/lib/readingBehavior/reading-behavior-tracker";
import { prepareContentElements } from "@/lib/longpageContent/prepare-content-elements";
import { ACT } from "@/store/types";

// Which data-point labels count as "this section was genuinely read", for
// the legacy per-section read-count shown by ReadingPositionIndicator.vue
// (longpage_reading_positions). SCAN and PREVIEW are deliberately excluded —
// a fast pass-through should not inflate that count; REGRESSION (re-reading)
// counts, since going back to re-read something is exactly the opposite of
// "just scrolling past".
var COUNTS_AS_READ = {
  [DATA_POINT_LABELS.READ]: true,
  [DATA_POINT_LABELS.STUDY]: true,
  [DATA_POINT_LABELS.REGRESSION]: true,
};

/**
 * Classifies HOW an individual user is reading right now — scan / read /
 * study / regression / preview, aggregated into a session and user-profile
 * label (see reading-behavior-tracker.js for the whole pipeline and its
 * tunable constants).
 *
 * Not to be confused with ReadingPositionIndicator.vue, which shows the
 * COLLECTIVE reading position/progress across all users (how many times a
 * section has been read). The two were previously combined in one file
 * (ReadingProgress.vue), which was a recurring source of confusion given how
 * similar "reading progress" and "reading behavior" sound — hence the split.
 */
export default {
  name: "ReadingBehaviorTracker",
  props: ["context"],

  mounted: function () {
    this.enableScrollLogging();
  },

  methods: {
    enableScrollLogging: function () {
      let _this = this;

      // Console-based debug log of the reading-behavior tracker.
      // On by default in dev builds (npm run dev/watch), off by default in
      // production builds (npm run build). Toggle from the browser console:
      // longpageReadingLog.enable() / .disable() — overrides the default and
      // is persisted in localStorage so it survives page reloads.
      var storageKey = "longpageReadingLogEnabled";
      // webpack's DefinePlugin replaces "process.env.NODE_ENV" with a string
      // literal at build time, so this compiles down to a plain boolean
      // constant — no runtime `process` global needs to exist for this to
      // work (there is none in the browser).
      var isDevBuild = process.env.NODE_ENV !== "production";
      // console.warn survives Terser's pure_funcs stripping in production
      // builds (see webpack.config.js), but Chrome/Firefox DevTools let you
      // hide the "Warnings" level independently of "Info"/"Log" — if that
      // filter happens to be off, warn() output is invisible while other
      // plugins' console.log() calls still show, which looks exactly like
      // "logging is broken" even though it fired. In dev builds there is no
      // stripping to worry about, so use plain console.log there — it is on
      // by default in every console and matches what other plugins use.
      var logFn = isDevBuild ? console.log.bind(console) : console.warn.bind(console);
      var storedEnabled;
      try {
        var storedValue = window.localStorage.getItem(storageKey);
        storedEnabled = storedValue === null ? isDevBuild : storedValue === "1";
      } catch (e) {
        storedEnabled = isDevBuild;
      }
      window.longpageReadingLog = window.longpageReadingLog || {
        enabled: storedEnabled,
        enable: function () {
          this.enabled = true;
          try {
            window.localStorage.setItem(storageKey, "1");
          } catch (e) {
            // ignore, e.g. localStorage disabled
          }
          logFn("[longpage] reading log enabled");
        },
        disable: function () {
          this.enabled = false;
          try {
            window.localStorage.setItem(storageKey, "0");
          } catch (e) {
            // ignore, e.g. localStorage disabled
          }
          logFn("[longpage] reading log disabled");
        },
      };

      if (
        "IntersectionObserver" in window &&
        "IntersectionObserverEntry" in window &&
        "intersectionRatio" in window.IntersectionObserverEntry.prototype
      ) {
        // Raw telemetry, independent of the classification constants above:
        // one row per IntersectionObserver threshold crossing, batched and
        // sent periodically so an offline analysis (R/Python dump of
        // longpage_intersection_events) can recompute its own metrics —
        // e.g. the percentage of an element visible on screen over time is
        // exactly `intersectionratio` here, sampled at every 10% step.
        var RAW_EVENT_FLUSH_INTERVAL_MS = 5000;
        var RAW_EVENT_MAX_BUFFER = 50;
        var rawEventBuffer = [];

        var flushRawEvents = function () {
          if (!rawEventBuffer.length) {
            return;
          }
          var batch = rawEventBuffer.splice(0, rawEventBuffer.length);
          ajax.call([
            {
              methodname: "mod_longpage_log_intersection_events",
              args: {
                longpageid: _this.context.longpageid,
                courseid: _this.context.courseId,
                events: JSON.stringify(batch),
              },
              done: function () {
                // No-op: fire-and-forget logging.
              },
              fail: function (e) {
                console.error("mod_longpage_log_intersection_events failed", e);
              },
            },
          ]);
        };
        setInterval(flushRawEvents, RAW_EVENT_FLUSH_INTERVAL_MS);
        window.addEventListener("beforeunload", flushRawEvents);

        // Reading-behavior tracking: turns viewport-crossing events into a
        // three-tier pipeline (data point -> session -> user profile). All
        // detection thresholds and the reading-speed model live in
        // reading-behavior-tracker.js — this method only wires the tracker
        // up to the DOM (observed elements) and to logging/persistence.
        var tracker = new Tracker({
          getViewportContainer: function () {
            return document.querySelector("#longpage-main");
          },
          onRawEntry: function (raw) {
            rawEventBuffer.push({
              sessionid: raw.sessionId,
              targetid: raw.id,
              targettag: raw.tag,
              intersectionratio: raw.ratio,
              boundingtop: raw.rect.top,
              boundingbottom: raw.rect.bottom,
              boundingheight: raw.rect.height,
              boundingwidth: raw.rect.width,
              viewportheight: raw.viewportHeight,
              scrolltop: raw.scrollTop,
              wordcount: raw.wordCount,
              clienttimestamp: raw.timestamp / 1000,
            });
            if (rawEventBuffer.length >= RAW_EVENT_MAX_BUFFER) {
              flushRawEvents();
            }
          },
          onSessionStart: function (reason) {
            if (window.longpageReadingLog && window.longpageReadingLog.enabled) {
              logFn("[longpage] new reading session (" + reason + ")");
            }
          },
          onBaselineEnter: function (baseline) {
            if (window.longpageReadingLog && window.longpageReadingLog.enabled) {
              logFn("[longpage] -> <" + baseline.tag + "> #" + baseline.id);
            }
          },
          onDataPoint: function (dataPoint) {
            if (window.longpageReadingLog && window.longpageReadingLog.enabled) {
              var targetElement = document.getElementById(dataPoint.id);
              var rawText = targetElement
                ? targetElement.textContent.trim().replace(/\s+/g, " ")
                : "";
              // Clean embedquestion shortcodes ("{Q{...}Q}") BEFORE truncating,
              // so the preview never shows a mid-token cut-off (see
              // cleanTextPreview in reading-behavior-tracker.js).
              var cleanedText = cleanTextPreview(rawText);
              var textPreview =
                cleanedText.length > 40 ? cleanedText.slice(0, 40) + "…" : cleanedText;

              // One block per data point, each field on its own line so it
              // stays scannable instead of one long run-on string.
              logFn(
                "[longpage] <" + dataPoint.tag + "> #" + dataPoint.id +
                "  ➜  " + dataPoint.label.toUpperCase() +
                "\n    dwell=" + dataPoint.dwellSeconds.toFixed(1) + "s" +
                "  peak=" + Math.round(dataPoint.peakRatio * 100) + "%" +
                "  coverage=" + Math.round(dataPoint.coverageRatio * 100) + "%" +
                "  words=" + dataPoint.words +
                "\n    session=" + tracker.getSessionLabel() +
                "  profile=" + tracker.getUserProfileLabel() +
                (textPreview ? "\n    \"" + textPreview + "\"" : ""),
              );
            }

            _this.persistReadingBehaviorEvent(dataPoint);
            if (COUNTS_AS_READ[dataPoint.label]) {
              _this.recordReadingPosition(dataPoint);
            }
          },
        });

        // Without this, the LAST element on the page (nothing further to
        // scroll/transition to) would never get its dwell finalized if the
        // tab is closed before the tracker's own heartbeat timeout fires —
        // see flushCurrentBaseline() in reading-behavior-tracker.js.
        window.addEventListener("beforeunload", function () {
          tracker.flushCurrentBaseline();
        });

        var observer = new IntersectionObserver(
          function (entries) {
            tracker.handleIntersections(entries);
          },
          {
            root: null,
            rootMargin: "0px",
            // Fine-grained steps so the tracker's peak-ratio tracking (and
            // the baseline-crossing midpoint search) sees intermediate
            // visibility, not just the 0%-crossing.
            threshold: [0, 0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9, 1.0],
            trackVisibility: true,
            delay: 100,
          },
        );

        // Shared with ReadingPositionIndicator.vue (idempotent — whichever
        // of the two mounts first does the actual DOM prep, see
        // prepare-content-elements.js), so this never assumes it runs
        // before or after that component.
        prepareContentElements(this.context).forEach(function (id) {
          var el = document.getElementById(id);
          tracker.registerElement(el);
          observer.observe(el);
        });
      }
    },

    /** Send one finalized reading-behavior data point to the server. */
    persistReadingBehaviorEvent: function (dataPoint) {
      ajax.call([
        {
          methodname: "mod_longpage_log_reading_behavior_event",
          args: {
            longpageid: this.context.longpageid,
            courseid: this.context.courseId,
            sessionid: dataPoint.sessionId,
            targetid: dataPoint.id,
            targettag: dataPoint.tag,
            wordcount: dataPoint.words,
            dwellseconds: dataPoint.dwellSeconds,
            peakratio: dataPoint.peakRatio,
            coverageratio: dataPoint.coverageRatio,
            minreadingtime: dataPoint.estimate.skimmingTime,
            avgreadingtime: dataPoint.estimate.readingTime,
            maxreadingtime: dataPoint.estimate.memorizingTime,
            datapointlabel: dataPoint.label,
            language: dataPoint.language,
          },
          done: function () {
            // No-op: fire-and-forget logging.
          },
          fail: function (e) {
            console.error("mod_longpage_log_reading_behavior_event failed", e);
          },
        },
      ]);
    },

    /**
     * Records dataPoint's section as genuinely read (see COUNTS_AS_READ) —
     * feeds longpage_reading_positions, i.e. the "resume last position"
     * feature (view.php) and the per-section read-count badge
     * (ReadingPositionIndicator.vue). Only called for read/study/regression
     * data points, never for scan/preview.
     */
    recordReadingPosition: function (dataPoint) {
      var container = document.querySelector("#longpage-main");
      var scrollFraction =
        container && container.scrollHeight
          ? container.scrollTop / container.scrollHeight
          : 0;
      this.$store.dispatch(ACT.UPDATE_READING_PROGRESS, {
        scrollTop: scrollFraction,
        section: dataPoint.id,
        sectionhash: hashSection(dataPoint.id),
      });
    },
  },
};

/** Same simple string hash the old (now removed) ReadingProgress.vue used. */
function hashSection(id) {
  return id.split("").reduce(function (hash, ch) {
    hash = (hash << 5) - hash + ch.charCodeAt(0);
    return hash & hash;
  }, 0);
}
</script>

<style></style>
