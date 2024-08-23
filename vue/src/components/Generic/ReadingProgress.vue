<template>
  <div></div>
</template>

<script>
import ajax from "core/ajax";


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
    if (this.context.showreadingprogress)
    {
      this.visualizeReadingProgress();
    }
  },

  methods: {
    hashCode: function (s) {
      return s.split("").reduce(function (a, b) {
        a = (a << 5) - a + b.charCodeAt(0);
        return a & a;
      }, 0);
    },
    enableScrollLogging: function () {
      let _this = this;
      if (
        "IntersectionObserver" in window &&
        "IntersectionObserverEntry" in window &&
        "intersectionRatio" in window.IntersectionObserverEntry.prototype
      ) {
        let last_entry = {};
        var sectionCount = 0;
        var behavior;

        // See: https://developer.mozilla.org/en-US/docs/Web/API/Intersection_Observer_API
        var handleScrolling = function (entries) {
          let time_diff = 0;
          // iterate over all entries that are within the viewport at the same time.
          for (var entry of entries) {
            // feature detection
            if (typeof entry.isVisible === "undefined") {
              // The browser doesn't support Intersection Observer v2, falling back to v1 behavior.
              entry.isVisible = true;
            }

            if (entry.isIntersecting && entry.isVisible) {
              var now = new Date();
              let word_count = $("#" + entry.target.id)
                .text()
                .split(" ").length;
              // TODO: Determine portion of visible text
              // _this.get($('#' + entry.target.id).get(0)).visibility

              //

              // reading detection
              time_diff = (now.getTime() - last_entry.utc) / 1000; // in seconds
              time_diff = time_diff === 0 ? 1 : time_diff;
              let ratio = last_entry.targetWordCount / time_diff;
              if (ratio < 0.1) {
                behavior = "idle";
              } else if (ratio >= 0.1 && ratio <= 3.6) {
                behavior = "reading";
              } else {
                behavior = "scrolling";
              }

              var measuredElement = document.querySelector("#longpage-app");
              if (measuredElement) {
                
                var yPadding, xPadding;
                if (window.getComputedStyle) {
                  var computedStyle = window.getComputedStyle(measuredElement);
                  yPadding =
                    parseFloat(computedStyle.paddingTop) +
                    parseFloat(computedStyle.paddingBottom);
                  xPadding =
                    parseFloat(computedStyle.paddingLeft) +
                    parseFloat(computedStyle.paddingRight);
                }
                //offsetHeight includes border, padding and margin, but clinetHeight includes onle the padding
                var containerHeight = measuredElement.clientHeight - yPadding;
                var containerWidth = measuredElement.clientWidth - xPadding;

                var longpageMain = document.querySelector("#longpage-main");
  
                var logentry = {
                  longpageid: _this.context.longpageid,
                  relativeTime: entry.time,
                  targetID: entry.target.id,
                  targetTag: entry.target.localName,
                  targetClasses: entry.target.className,
                  targetWordCount: word_count,
                  targetHeight: entry.target.scrollHeight,
                  scrollLeft: longpageMain.scrollLeft,
                  scrollTop: longpageMain.scrollTop,
                  scrollHeight: longpageMain.scrollHeight,
                  scrollWidth: longpageMain.scrollWidth,
                  containerHeight: containerHeight,
                  containerWidth: containerWidth,
                  behavior: behavior,
                  sectionhash: _this.hashCode(entry.target.id),
                  sectionCount: sectionCount,
                  utc: now.getTime(),
                  navigator: {
                    userAgent: navigator.userAgent,
                    platform: navigator.platform,
                    oscpu: navigator.oscpu,
                    language: navigator.language,
                    cookieEnabled: navigator.cookieEnabled
                  },
                  screenWidth: window.screen.width,
                  screenHeight: window.screen.height,
                  devicePixelRatio: window.devicePixelRatio,
                  intersectionRatio: entry.intersectionRatio,
                };

              ajax.call([
                {
                  methodname: "mod_longpage_log",
                  args: {
                    data: {
                      entry: JSON.stringify(logentry),
                      action: "scroll",
                      utc: Math.ceil(now.getTime() / 1000),
                      courseid: _this.context.courseId,
                      longpageid: _this.context.longpageid
                    },
                  },
                  done: function (reads) {
                  },
                  fail: function (e) {
                    console.error("fail", e);
                  },
                },
              ]);

                last_entry = logentry;
              }
            }
          }
        };

        var options = {
          root: null,
          rootMargin: "0px",
          threshold: [0.0],
          trackVisibility: true,
          delay: 100,
        };

        var observer = new IntersectionObserver(handleScrolling, options);
        var pCounter = 0;
        //

        //tie together text parts without wrapper to wrap them
        var observedElements = ["h2", "h3", "h4", "h5", "pre", "img", "p", "ol", "ul", "div"];
        var container = "#longpage-content";

        if ($(container + " > .filter_mathjaxloader_equation").length == 1)
          container += " > .filter_mathjaxloader_equation";

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

        $(observedSelectors.join(", ")).each(function (i, val) {
          var attr = $(this).attr("id");
          if (typeof attr === typeof undefined || attr === false) {
            attr = "paragraph-" + pCounter;
            $(this)
              .attr("id", attr)
              .addClass("longpage-paragraph");
            pCounter++;
          }
          sectionCount++;
          $("#" + attr).wrap("<div class='wrapper'></div>");
          var wrapper = $("#" + attr).parent();
          if (_this.context.showreadingprogress || _this.context.showreadingcomprehension) {
            var span = $("<span></span>")
              .addClass("reading-progress")
              .attr("data-html2canvas-ignore", "");
            if (_this.context.showreadingprogress) {
              $(span).attr(
                "title",
                "Der Abschnitt wurde <br>bislang 0 mal gelesen."
              )
            }
            else {
              $(span).addClass("progress-3");
            }
            $(wrapper).append(span);
          }
            if ($(wrapper).find(".filter_embedquestion-iframe").length > 0)
            {
              $(wrapper).height("0px");
              $(wrapper).css("padding", "0px");
            }
          
          observer.observe(document.querySelector("#" + attr));
        });
      }
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
        distance / ((viewportHeight + elementHeight) / 100)
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
                if ($("#" + data[i].section)) {
                  $("#" + data[i].section).next(".reading-progress")
                    .attr(
                      "title",
                      "Der Abschnitt wurde <br>bislang " +
                      data[i].count +
                      " mal gelesen."
                    )
                    .addClass(
                      "progress-" +
                      Math.ceil((data[i].count / max) * 5)
                    );
                  
                  if (_this.debug) {
                    $("#" + data[i].section).append(
                      $(
                        '<span style="position:absolute; right:-40px; font-size:8px; background-color:red; padding:1px 2px; color:#fff;">' +
                          data[i].section.replace("longpage-paragraph-", "") +
                          "</span>"
                      )
                    );
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

<style>
</style>