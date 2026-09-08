<template>
  <div></div>
</template>

<script>
import ajax from "core/ajax";
import {
  ReadingBehaviorTracker,
  cleanTextPreview,
} from "@/lib/readingBehavior/reading-behavior-tracker";

/**
 * This Module visualizes the collective reading progress on the course/longpage text
 * Every user can see how many times a text section has been read by other users
 */
export default {
  name: "ReadingProgress",
  props: ["context"],

  data: function () {
    return {
      debug: false,
    };
  },

  mounted: function () {
    this.enableScrollLogging();
    if (this.context.showreadingprogress) {
      this.visualizeReadingProgress();
    }
  },

  methods: {
    enableScrollLogging: function () {
      let _this = this;

      // Console-based debug log of the reading-progress observer.
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
        var tracker = new ReadingBehaviorTracker({
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
                "  words=" + dataPoint.words +
                "\n    session=" + tracker.getSessionLabel() +
                "  profile=" + tracker.getUserProfileLabel() +
                (textPreview ? "\n    \"" + textPreview + "\"" : ""),
              );
            }

            _this.persistReadingBehaviorEvent(dataPoint);
          },
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
        var pCounter = 0;
        //

        //tie together text parts without wrapper to wrap them
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

        const containerEl = document.querySelector(
          container + " > .filter_mathjaxloader_equation",
        );
        if (containerEl) container += " > .filter_mathjaxloader_equation";

        // $($(container)
        //   .contents()
        //   .toArray()
        //   .reduce(function (prev, cur) {
        //     if (cur.tagName && observedElements.includes(cur.tagName.toLowerCase()))
        //       return prev;

        //     if (cur.nodeType === 3 && cur.data.trim() == "")
        //       return prev;

        //     if (prev.length == 0)
        //       return [[cur]];

        //     prev[prev.length - 1].push(cur);

        //     if (cur.nextSibling && cur.nextSibling.tagName && observedElements.includes(cur.nextSibling.tagName.toLowerCase())) {
        //       prev.push([]);
        //     }
        //     return prev;
        //   }, [])).wrap("<p></p>");

        var observedSelectors = observedElements.map(function (val) {
          return container + " > " + val;
        });

        document
          .querySelectorAll(observedSelectors.join(", "))
          .forEach((val, i) => {
            var attr = val.getAttribute("id");
            if (!attr) {
              attr = "paragraph-" + pCounter;
              val.setAttribute("id", attr);
              val.classList.add("longpage-paragraph");
              pCounter++;
            }
            // Wrap element
            const wrapper = document.createElement("div");
            wrapper.className = "wrapper";
            val.parentNode.insertBefore(wrapper, val);
            wrapper.appendChild(val);

            if (
              _this.context.showreadingprogress ||
              _this.context.showreadingcomprehension
            ) {
              const span = document.createElement("span");
              span.className = "reading-progress";
              span.setAttribute("data-html2canvas-ignore", "");
              if (_this.context.showreadingprogress) {
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

            var observedEl = document.querySelector("#" + attr);
            tracker.registerElement(observedEl);
            observer.observe(observedEl);
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

    percentageSeen: function (id) {
      let element = document.getElementById(id);
      // Get the relevant measurements and positions
      const viewportHeight = window.innerHeight;
      const scrollTop = window.scrollY;
      const elementOffsetTop = element.offsetTop;
      const elementHeight = element.offsetHeight;

      // Calculate percentage of the element that's been seen
      const distance = scrollTop + viewportHeight - elementOffsetTop;
      const percentage = Math.round(
        distance / ((viewportHeight + elementHeight) / 100),
      );

      // Restrict the range to between 0 and 100
      return Math.min(100, Math.max(0, percentage));
    },

    /** Detailed but slow method to estimate the portion of an element that is visble with the viewport. */
    get: function (element) {
      if (typeof element !== "object" || !(element instanceof HTMLElement))
        throw new Error("No valid HTMLElement.");
      const b = element.getBoundingClientRect();
      const vpw = window.innerWidth || document.documentElement.clientWidth;
      const vph = window.innerHeight || document.documentElement.clientHeight;
      let e = {
        element: element,
        dimensions: {
          height: b.height,
          width: b.width,
        },
        viewport: {
          width: vpw,
          height: vph,
        },
        position: {
          top: b.top,
          left: b.left,
          right: b.right,
          bottom: b.bottom,
          centerX: b.right + b.width / 2,
          centerY: b.top + b.height / 2,
        },
        fullyInsideVP:
          b.top >= 0 && b.bottom <= vph && b.left >= 0 && b.right <= vpw
            ? true
            : false,
        isHidden: false, //this._isHidden(),
        visibility: 0,
      };

      if (!e.isHidden) {
        let px = 0;
        for (let y = 0; y < Math.floor(b.height); y++) {
          const posY = b.top + y;
          for (let x = 0; x < Math.floor(b.width); x++) {
            const posX = b.left + x;
            if (posX >= 0 && posX <= vpw && posY >= 0 && posY <= vph) {
              let elem = document.elementFromPoint(posX, posY);
              if (elem !== null && elem === element) px++;
            }
          }
        }
        e.visibility = px / (Math.floor(b.width) * Math.floor(b.height));
        e.visibility = Number(e.visibility.toFixed(2));
      }
      return e;
    },

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
