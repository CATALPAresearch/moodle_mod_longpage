<template>
  <sidebar-tab>
  <template #body> 
    <div class="row pr-3">
      <h3
          class="col m-0 tab-title" style="width: 70%"
        >
          {{$t('sidebar.tabs.quiz.heading')}}
        </h3>
      
        <!-- TODO: removed for study, add back in for production 
        <button class="btn dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-if="this.$store.state.UserModule.userCanMod">
          <i class="fa fa-cog fa-fw fa-lg" /> 
        </button>
        <div class="dropdown-menu dropdown-menu-right" style="min-width: 15rem;" v-show="this.$store.state.UserModule.userCanMod">
            <a class="dropdown-item" id="changeQuestion" href="javascript:void(0)"><i class="fa fa-cog fa-fw" /> Einbettung editieren</a> 
          <a class="dropdown-item" id="editQuestion" href="javascript:void(0)"><i class="fa fa-pencil fa-fw" /> Aufgabe bearbeiten <i class="fa fa-external-link fa-fw small" /> </a>
          <a class="dropdown-item" id="deleteQuestion" href="javascript:void(0)"><i class="fa fa-trash fa-fw" />Aufgabe löschen <i class="fa fa-external-link fa-fw small" /> </a>
          <a class="dropdown-item" id="openQuestionBank" href="javascript:void(0)"><i class="fa fa-question fa-fw" />Aufgabensammlung öffnen <i class="fa fa-external-link fa-fw small" /> </a>
        </div> -->
      
      <div class="col-auto px-0 offset-md-1">
        <a href="javascript:void(0)" id="total-reading-comprehension" title="Aufgabe oben halten" data-toggle="tooltip"><i class="fa fa-battery-0 fa-fw fa-lg" /></a>
      </div>
      <div class="col-auto px-0">
        <a href="javascript:void(0)" id="pinQuestion" title="Aufgabe oben halten" data-toggle="tooltip"><i class="fa fa-thumb-tack fa-fw fa-lg" /></a>
      </div>
      <div class="col-auto px-0">
        <a href="javascript:void(0)" id="prevQuestion" title="Vorherige Aufgabe" data-toggle="tooltip"><i class="fa fa-arrow-up fa-fw fa-lg" /></a> 
      </div>
      <div class="col-auto px-0">
        <a href="javascript:void(0)" id="nextQuestion" title="Nächste Aufgabe" data-toggle="tooltip"><i class="fa fa-arrow-down fa-fw fa-lg" /></a>
      </div>
    </div> 
    <div id="editButtons" class="btn-group position-absolute position-right z-index-1 mr-5 mt-3" role="group" v-if="this.$store.state.UserModule.userCanMod" style="display: none;">
      <a class='btn btn-secondary' id="quickEditQuestion" title='Aufgabe direkt bearbeiten' href='javascript:void(0)'><i class='fa fa-edit' style='cursor:pointer;'></i></a>
      <a class='btn btn-secondary' id="lockQuestion" title='Aufgabe sperren' href='javascript:void(0)'><i class='fa fa-lock' style='cursor:pointer;'></i></a>
      <a class='btn btn-secondary' id="removeQuestion" title='Einbettung entfernen' href='javascript:void(0)'><i class='fa fa-trash' style='cursor:pointer;'></i></a>
    </div>
    <hr class="my-3">    
    <p id="quiz-placeholder" class="p-3">Zu diesem Abschnitt gibt es keine Aufgaben.</p>
    <div id="carousel" class="carousel slide" data-interval="false" style="display:none">
      <ol id="carousel-indicators" class="carousel-indicators">
      </ol>
      <div id="question" class="carousel-inner"></div>     
      <a class="carousel-control-next" href="#carousel" role="button" data-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="sr-only">-&gt;</span>
      </a>
      <a class="carousel-control-prev" href="#carousel" role="button" data-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="sr-only">&lt;-</span>
      </a>
    </div>
    <div id="embedQuestion">    
      <!-- TODO: v-if added for study, remove for production -->
      <a href="javascript:void(0)" class="embedNewAIQuestion" v-if="this.context.tags.includes('AI')">
        <i class="fa fa-plus fa-fw" title="Neue KI-generierte Aufgabe einbetten (Mit Markierung Aufgabe eingrenzen, mit Strg oder Umschalt plus Klick auf Absätze Kontext erweitern)" data-toggle="tooltip"/>
      </a>
      <!-- TODO: v-if added for study, remove for production -->
      <a href="javascript:void(0)" class="embedNewEmptyQuestion" v-if="this.context.tags.includes('noAI')">
        <i class="fa fa-plus-square fa-fw" title="Neue Blanko-Aufgabe einbetten" data-toggle="tooltip"/>
      </a>
      <!-- TODO: removed for study, add back in for production 
      <a href="javascript:void(0)" class="embedExistingQuestion" v-show="this.$store.state.UserModule.userCanMod">
        <i class="fa fa-plus-circle fa-fw" title="Vorhandene Aufgabe einbetten" data-toggle="tooltip"/>
      </a> -->
    </div>
    </template>
  </sidebar-tab>
</template>
<style>
#page-content #region-main
{
  padding-right: 0 !important;
}

#quiz-spinner
{
  position: absolute;
  top: 50px;
  width: 100%;
}

#quiz-spinner .spinner-border
{
  display: block;
}

#longpage-main .filter_embedquestion-iframe
{
  height: 0 !important;
}

#carousel
{
  display: none;
  height: 100%;
}

#question .filter_embedquestion-iframe
{
  /* min-height: 350px; */
  height: 100% !important;
  text-align: center;
}

#question
{
  height: 100%;
}

.carousel-indicators
{
  top: -35px;
  bottom: initial;
  margin-left: 40%;
  margin-right: 40%;
}

.carousel-indicators li
{
  background-color: #ccc;
  height: 20px;
  text-align: center;
  text-indent: initial;
}

.carousel-control-prev, .carousel-control-next 
{
  width: 25px;
  filter: invert(100%);
  align-items: normal;
  top: 100px;
}

.carousel-control-prev
{
  left: -15px;
}

.carousel-control-next {
  right: -10px;
}

.carousel-control-prev:focus, .carousel-control-next:focus
{
  box-shadow: none;
}

.carousel-control-prev-icon, .carousel-control-next-icon
{
  width: 25px;
  height: 25px;
}

.carousel-item
{
  position: absolute;
  height: 100%;
}

#pinQuestion {
  transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  transform-origin: bottom center;
  display: block;
}

#pinQuestion.active {
  color: #0f6cbf !important;
  -webkit-transform: scale3d(1.3, 1.3, 1);
  transform: scale3d(1.3);
}

.reading-comprehension
{
  background-color: green !important;
  cursor: pointer;
}

.reading-progress, #total-reading-comprehension
{
  display: none;
}

#embedQuestion
{
  display: none;
}

.embedQuestion
{
  position: absolute;
  right: 15px;
  z-index: 100;
  opacity: 0;
  margin-top: -20px !important;
}

.embedNewAIQuestion, .embedNewEmptyQuestion, .embedExistingQuestion
{
  background-color: white;
}

.selected-paragraph
{
  background-color: rgba(255, 230, 0, 0.1);
}

mark
{
  padding: 0;
}

.highlight-0
{
  background-color: rgba(255, 230, 0, 0.0);
}

.highlight-25
{
  background-color: rgba(255, 230, 0, 0.05);
}

.highlight-50
{
  background-color: rgba(255, 230, 0, 0.2);
}

.highlight-75
{
  background-color: rgba(255, 230, 0, 0.4);
}

.highlight-100
{
  background-color: rgba(255, 230, 0, 0.6);
}

.driver-popover.driverjs-theme {
  background-color: #ffffff;
  color: #000;
  border: 1px solid #000;
}

.driver-popover.driverjs-theme .driver-popover-next-btn {
  text-align: center;
  border: 1px solid #000;
  text-shadow: none;
  font-size: 14px;
  padding: 5px 8px;
  border-radius: 6px;
}


</style>
<script>

import { AnnotationType, SidebarEvents, SidebarTabKeys } from "@/config/constants";
import { GET, MUTATE } from "@/store/types";
import { mapActions, mapGetters, mapMutations } from "vuex";
import SidebarTab from "@/components/LongpageSidebar/SidebarTab";
import ajax from "core/ajax";
import Fragment from "core/fragment";
import { EventBus } from "@/lib/event-bus"; 
import "mark.js/dist/jquery.mark.min.js";
import "bootstrap/js/dist/tooltip";
import { driver } from "driver.js";
import "driver.js/dist/driver.css";

export default {
  name: "Quiz",
  props: ["content"],
  components: { SidebarTab },
  computed: {
    ...mapGetters({
      highlights: GET.QUIZ,
      context: GET.LONGPAGE_CONTEXT
    }),
    type() {
      return AnnotationType.QUIZ;
    }
  },
  emits: [SidebarEvents.TOGGLE_TABS, SidebarEvents.CHANGE_BADGES],
  methods: {
    ...mapMutations([MUTATE.RESET_SIDEBAR_TAB_OPENED_KEY]),
    toggleTab()
    {
      EventBus.publish(SidebarEvents.TOGGLE_TABS, SidebarTabKeys.QUIZ);
    }
  },
  mounted() {
    let _this = this;
    const _ = require('lodash');

    $("#page").attr("style", ($("#page").attr("style") ? $("#page").attr("style") + " " : "") + "overflow: clip !important;");

    let moodleRelease = $("#longpage-app-container").data("moodle-release").split(".");
    moodleRelease = parseInt(moodleRelease[0]) * 100 + parseInt(moodleRelease[1]);

    function get_reading_comprehension(successFunction) {
      ajax.call([
        {
          methodname: "mod_longpage_get_reading_comprehension",
          args: {
            longpageid: _this.context.longpageid,
          },
          done: function (reads) {
            try {

              //TODO: for study
              let gradeInfo = JSON.parse(reads.gradeInfo);
              $("#btnContinueStudy").toggleClass("disabled", gradeInfo.grade < gradeInfo.gradepass);

              let data = JSON.parse(reads.response);

              for (const [id, entry] of Object.entries(data)) {
                var value = entry["value"];
                var level = entry["level"];
                var tags = Object.values(entry["tags"]).join(", ");
                var idFixed = id.replace("/", "\\/");
                var iframe = $("#longpage-content #" + idFixed);
                var paragraph = $(iframe).parents(".wrapper").prev();
                $(paragraph).each(function (index, p) {
                  if (!$(p).attr("data-reading-comprehension-count")) {
                    $(p).attr("data-reading-comprehension-count", level);
                    $(p).attr("data-reading-comprehension-sum", level * value);
                  }
                  else {
                    $(p).attr("data-reading-comprehension-count", parseInt($(p).attr("data-reading-comprehension-count")) + level);
                    $(p).attr("data-reading-comprehension-sum", parseFloat($(p).attr("data-reading-comprehension-sum")) + (level * value));
                  }
                });

                $(iframe, "#question iframe#" + idFixed).attr("data-embedid", idFixed);
                $(iframe, "#question iframe#" + idFixed).attr("data-questionid", entry["id"]);
                $(iframe, "#question iframe#" + idFixed).attr("data-tags", tags);
              }

              var sum = 0;
              var len = 0;
              var repeat = 0;

              $(".wrapper[data-reading-comprehension-count]").each(function (index, paragraph) {
                var progress = $(paragraph).find(".reading-progress");
                var value = parseFloat($(paragraph).attr("data-reading-comprehension-sum")) / parseInt($(paragraph).attr("data-reading-comprehension-count"));
                repeat += (value < 0.5 ? 1 : 0);
                sum += value;
                len += 1;
                $(progress).attr("title", $(progress).attr("data-bs-original-title"));
                $(progress)
                  .attr(
                    "title",
                    (_this.context.showreadingprogress ? ($(progress).attr("title").substr(0, $(progress).attr("title").indexOf("gelesen.") + 8) + "<br>") : "") +
                    "Ihr geschätztes Leseverständnis beträgt " +
                    (100 * value).toFixed(2) +
                    "%."
                  ).css("opacity", Math.max(0.1, value)).addClass("reading-comprehension");
                $(paragraph).attr("data-reading-comprehension-count", "");
              });

              $(".reading-progress").tooltip("dispose").tooltip({ "placement": "auto", "html": true });

              var rc = 0;
              if (len > 0) {
                rc = (100 * sum / len).toFixed(0);
              }

              if (rc <= 50) {
                EventBus.publish(SidebarEvents.CHANGE_BADGES, { type: SidebarTabKeys.QUIZ, count: repeat, title: "Ihr geschätztes Leseverständnis für die ganze Seite \nund " + repeat + " Aufgaben beträgt weniger als 50%." });
              }

              $("#sidebar-tab-quiz #total-reading-comprehension").attr("title", "Ihr geschätztes Leseverständnis für die ganze Seite <br>beträgt: " + rc + " %.<br>Klicken Sie für eine Übersicht der Aufgaben.").tooltip({ "placement": "top", "html": true, "title": "" }).attr("title", "");
              $("#sidebar-tab-quiz #total-reading-comprehension i").attr("class", "fa fa-fw fa-lg fa-battery-" + Math.floor(rc / 25));
              $("#sidebar-tab-quiz #total-reading-comprehension").show();
              if (successFunction)
                successFunction();

            } catch (e) {
              console.log(e);
            }
          },
          fail: function (e) {
            console.error("fail", e);
          },
        },
      ]);
    }

    function isElementInViewport(element, index, array) {
      if (typeof jQuery === "function" && element instanceof jQuery) {
        element = element[0];
      }

      var rectEl = element.getBoundingClientRect();
      var rectApp = document.querySelector('#longpage-app').getBoundingClientRect();

      return (
        rectEl.top >= rectApp.top &&
        rectEl.left >= rectApp.left &&
        rectEl.bottom <= rectApp.bottom &&
        rectEl.right <= rectApp.right
      );
    }

    function isElementBottomInViewport(element) {
      if (typeof jQuery === "function" && element instanceof jQuery) {
        element = element[0];
      }

      var rectEl = element.getBoundingClientRect();
      var rectApp = document.querySelector('#longpage-app').getBoundingClientRect();

      return (
        rectEl.bottom >= rectApp.top &&
        rectEl.left >= rectApp.left &&
        rectEl.bottom <= rectApp.bottom &&
        rectEl.right <= rectApp.right
      );
    }

    function getCssSelector(element) {
      var css = element.tagName.toLowerCase();
      if (element.id && !element.id.startsWith("yui_")) {
        css += "#" + element.id;
      }
      if (element.className) {
        css += "." + element.className.split(" ").join(".");
      }
      css = css.replaceAll("..", ".");
      return css;
    }

    function getCssSelectorsOfAllParents(element) {
      var selectors = "";
      while (element) {
        var css = getCssSelector(element);
        if (css != element.tagName.toLowerCase()) {
          selectors = css + " " + selectors;
        } 
        
        element = element.parentElement;
      }
      selectors = selectors.slice(0, -1);
      return selectors;
    }

    $(document).ready(function () {
      var readfun = _.debounce(function () {
        get_reading_comprehension();
      }, 2000);

      get_reading_comprehension();

      $(".reading-progress").not($("h1, h2, h3, h4, h5, h6").next().add($(".filter_embedquestion-iframe").parent().next())).parent()
        .append($("#embedQuestion").clone().removeAttr("id").addClass("embedQuestion"));
      $(".embedNewAIQuestion, .embedNewEmptyQuestion, .embedExistingQuestion").show();

      //let previousY = 0;
      let directionUp = false;
      //let currentY = 0;

      function log(action, logentry)
      {
        logentry["longpageid"] = _this.context.longpageid;
        ajax.call([
          {
            methodname: "mod_longpage_log",
            args: {
              data: {
                entry: JSON.stringify(logentry),
                action: action,
                utc: Math.ceil(new Date().getTime() / 1000),
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
      }

      function logClick(ev) {
        var iframe = $("#question .carousel-item.active iframe");
        var logentry = {
          pageX: ev.pageX ? ev.pageX : 0,
          pageY: ev.pageY ? ev.pageY : 0,
          embedid: $(iframe).attr("id"),
          questionid: $(iframe).attr("data-questionid"),
          target: getCssSelectorsOfAllParents(ev.target),
          textContent: ev.target.textContent,
        };
        log("clicked", logentry);
      }

      $(document).on('click', "#sidebar-tab-quiz", function (ev) {
        logClick(ev);
      });

      var autosavefun = _.debounce(function () {
        if ($(this).attr("data-autosave") == "false")
          return;

        ajax.call([
          {
            methodname: "mod_longpage_autosave",
            args: {
              data: {
                qubaid: new URLSearchParams($(this).find("form").attr("action")).get("qubaid"),
                form: JSON.stringify(Object.fromEntries(new FormData($(this).find("form")[0])))
              },
            },
            done: function (reads) {
            },
            fail: function (e) {
              console.error("fail", e);
              alert(e);
            },
          },
        ]);
      }, 2000);

      var observerStates = {};

      function observerCall(entries = []) {
        if (hasPin()) {
          for (var i = 0; i < entries.length; i++) {
            var entry = entries[i];
            $(entry.target).next().find("iframe").each(function (idx, target) {
              observerStates["#" + $(target).first().attr("data-embedid")] = entry;
            });
          }
          return;
        }

        var added = {};
        for (const entry of entries) {
          //currentY = entry.boundingClientRect.y
          // if (currentY < previousY) {
          //   directionUp = false;
          // }
          // else {
          //   directionUp = true;
          // }
          $(entry.target).next().find("iframe").each(function (idx, target) {
            if (entry.isIntersecting === true) {
              $("#longpage-main .filter_embedquestion-iframe").removeClass("last-visible");
              var idFixed = "#" + target.id.replace("/", "\\/");

              if ($(target).attr("data-tags") && $(target).attr("data-tags").includes("neu") && !_this.$store.state.UserModule.userCanMod) {
                return;
              }

              target.classList.add("last-visible");
              added[idFixed] = 1;

              var div = $(`<div class="carousel-item"></div>`);
              var iframeCloned = $($("#longpage-main " + idFixed)[0]).clone(true);
              var src = $(iframeCloned).attr("src");
              $(iframeCloned).attr("src", "");
              $(iframeCloned).appendTo(div);

              var obs = new IntersectionObserver((entries, o) => {
                var spinner = `<div id="quiz-spinner" class="row no-gutters vh-50">
              <div class="spinner-border m-auto" role="status">
                <span class="sr-only" />
              </div>
              </div>`;

                entries.forEach((entry) => {
                  if (entry.isIntersecting) {
                    o.unobserve(entry.target);
                    $(div).css("opacity", 0.2);
                    $(spinner).appendTo(div);
                    $(entry.target).attr("src", src);
                  }
                });
              });

              obs.observe($(iframeCloned)[0]);

              if (directionUp) {
                $(div).prependTo("#question");
              }
              else {
                (div).appendTo("#question");
              }

              $("#question iframe" + idFixed).on("load", function () {
                removePin(true);
                if (_this.$store.state.UserModule.userCanMod) {   
                  $(this).attr("data-questionid", $("#longpage-main " + idFixed).attr("data-questionid")); 
                  $(this).attr("data-tags", $("#longpage-main " + idFixed).attr("data-tags"));     
                  $("#editButtons").show();       
                  $("#question iframe" + idFixed).contents().find("body").attr("data-tags", $(this).attr("data-tags"));
                  changeLockButton(!($(this).attr("data-tags") && $(this).attr("data-tags").includes("neu")));
                }

                var cssLink = document.createElement("link");
                cssLink.href = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/')) + "/vue/src/styles/tasks.css";
                cssLink.rel = "stylesheet";
                cssLink.type = "text/css";
                $("#question iframe" + idFixed).contents().find("head").append(cssLink);

                var jsLink = document.createElement("script");
                jsLink.src = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/')) + "/vue/src/components/LongpageSidebar/Quiz/tasks.js";
                jsLink.type = "text/javascript";
                $("#question iframe" + idFixed).contents().find("head").append(jsLink);

                readfun();

                function waitPending(cnt = 0) {
                  if (!$("#question iframe" + idFixed).contents().find(".que").hasClass("multichoice") && M.util.js_pending() && cnt < 10) {
                    setTimeout(waitPending, 500, cnt + 1);
                  }
                  else {
                    $(div).find("#quiz-spinner").remove();
                    $(div).css("opacity", 1);
                    if($(div).is(":visible")) {
                      adjustCarouselControltoHeight();
                    }
                  }
                }

                waitPending();

                $(this).contents().find("body").on('click', function (ev) {
                  addPin();
                  logClick(ev);
                });

                $(this).contents().find("body").attr("data-autosave", "true").on('click keyup', autosavefun);

                $(this).contents().find("body").on('dblclick', function () {
                  var el = $("#" + $("#question iframe" + idFixed).attr("data-paragraph"));
                  $(el)[0].scrollIntoView({ "behavior": "smooth", "block": "start" });
                });
              });
            }
            else {
              //TODO: hiermit arbeiten, nicht isElementInViewport
              var idFixed = "#" + target.id.replace("/", "\\/");
              $("#question").find(idFixed).parents(".carousel-item").remove();
            }

          });
        }

        //previousY = currentY; 
        var found = false;
        var visible = {};
        $("#longpage-main .filter_embedquestion-iframe").each(function (i, el) {
          var idFixed = "#" + el.id.replace("/", "\\/");
          visible[idFixed] = (idFixed in visible && visible[idFixed]) || isElementInViewport(el);
        });

        $("#longpage-main .filter_embedquestion-iframe").each(function (i, el) {
          var idFixed = "#" + el.id.replace("/", "\\/");

          if (!(idFixed in added) && !visible[idFixed]) {
            //$("#question").find(idFixed).parents(".carousel-item").remove();
          }
          else {
            found = true;
          }
        });

        if (!found) {
          $("#question").children().remove();
        }

        if ($("#question").children().length > 0) {
          $("#quiz-placeholder").hide();
          $("#editButtons").show(); 
          $("#carousel").show();
          $("#question .carousel-item").removeClass("active");
          $("#question .carousel-item:first").addClass("active");
          $("#carousel-indicators").children().remove();
          $(".carousel-control-prev, .carousel-control-next").hide();

          if ($("#question").children().length > 1) {
            $(".carousel-control-prev, .carousel-control-next").show();
            for (var i = 0; i < $("#question").children().length; i++) {
              var div = `<li data-target="#carousel" data-slide-to="${i}" class="${i == 0 ? "active" : ""}">${i + 1}</li>`;
              $(div).appendTo("#carousel-indicators");
            }
          }
        }
        else {
          $("#carousel").hide();
          $("#quiz-spinner").remove();
          $("#quiz-placeholder").show();
          $("#editButtons").hide();
          $("#carousel-indicators").children().remove();
        }
      }

      var observer = new IntersectionObserver(observerCall, { rootMargin: "-100px 0px -100px 0px", threshold: 0, root: document.querySelector('#longpage-main') });

      $("#longpage-main .filter_embedquestion-iframe").each(function (i, el) {
        var paragraph = $(el).parents(".wrapper").prev();
        $(el).attr("data-paragraph", $(paragraph).children().first().attr("id"));
        $(el).attr("data-embedid", el.id);
        $(el).attr("data-questionid", $(el).attr("data-questionid"));
        observer.observe($(paragraph)[0]);
      });

      $("#question").on("mouseover", "iframe", function () {

        var el = $("#" + $(this).attr("data-paragraph"));
        $(el).css("background-color", "#eeeeee70");

        return;
        var questionContent = $(this).contents().find(".qtext").text() + " " + $(this).contents().find(".answer").text();

        var paragraphContent = $(el).text();
        $.ajax({
          url: "http://localhost:8000/compare_similarity?text1=" + encodeURI(paragraphContent) + "&text2=" + encodeURI(questionContent),
          type: "POST",
          success: function (data) {
            //data has form [[start, length, similarity], [start, length, similarity], ...]
            data.forEach(function (entry) {
              var start = entry[0];
              var len = entry[1];
              var similarity = entry[2];
              //discretize similarity from (0, 1) into 4 levels [0, 25, 50, 75, 100]
              similarity = Math.floor(similarity * 4) * 25;

              $(el).markRanges([{ start: start, length: len }], {
                className: "highlight-" + similarity
              });

            });
          },
          error: function (error) {
            console.log(error);
          }
        });
      });

      $("#question").on("mouseleave", "iframe", function () {
        $("#question iframe").each(function (idx, iframe) {
          var el = $("#" + $(iframe).attr("data-paragraph"));
          $(el).css("background-color", "");
        });

        var el = $("#" + $(this).attr("data-paragraph"));
        $(el).unmark();
      });

      $("#nextQuestion").click(function () {
        var t = $("#longpage-main").scrollTop();
        $("#longpage-main .filter_embedquestion-iframe").each(function (i, el) {
          if (el.offsetTop > t + 500) {
            $("#longpage-main").animate({ scrollTop: el.offsetTop - 500 }, 'fast');
            return false;
          }
        })
      });

      $("#prevQuestion").click(function () {
        var t = $("#longpage-main").scrollTop();
        $($("#longpage-main .filter_embedquestion-iframe").get().reverse()).each(function (i, el) {
          if (el.offsetTop < t) {
            $("#longpage-main").animate({ scrollTop: el.offsetTop - 500 }, 'fast');
            return false;
          }
        })
      });

      $("#pinQuestion").on("click", function () {
        $(this).toggleClass("active");
        if (!$(this).hasClass("active")) {
          $("#pinQuestion").removeClass("autopin");
          if (Object.keys(observerStates).length > 0) {
            observerCall(Object.values(observerStates));
          }
        }
        observerStates = {};
      });

      function embedIframeCode(iframecode, btn, openEditMode = false) {
        if (iframecode == "error") {
          alert("Es ist ein Fehler aufgetreten. Bitte versuchen Sie es erneut.");
          return;
        }

        iframecode = $(iframecode);
        $(iframecode).attr("data-paragraph", $(btn).parent(".wrapper").children().first().attr("id")).prop('outerHTML');
        iframecode = $(iframecode).prop('outerHTML');

        if ($(btn).prev(".reading-comprehension").length > 0) {
          $(btn).parent(".wrapper").next().children().first().append(iframecode);
        }
        else {
          var wrapper = $("<div class='wrapper'><p>" + iframecode + "</p></div>");
          $(wrapper).height("0px")
          $(wrapper).css("padding", "0px");
          $(btn).parent(".wrapper").after(wrapper);
          observer.observe($(btn).parent(".wrapper")[0]);
        }

        get_reading_comprehension(function () {
          observerCall([{
            target: $(btn).parent(".wrapper")[0],
            isIntersecting: false,
          }, {
            target: $(btn).parent(".wrapper")[0],
            isIntersecting: true,
          }]);

          $("#carousel").carousel($("#question").children().length - 1);
          setTimeout(function () {
            $("#question .carousel-item.active").animate({ "margin-top": "+=20px" }, 200).animate({ "margin-top": "-=20px" }, 200);
            if(openEditMode) {
              $("#quickEditQuestion").click();
            }
          }, 1000);
        });

      }

      $("#id_embedform").on("change", function () {

        ajax.call([
          {
            methodname: "mod_longpage_embed_question",
            args: {
              longpageid: _this.context.longpageid,
              embedcode: $("#id_embedformeditable").text(),
              position: $("#id_embedformeditable").data("position")
            },
            done: function (data) {
              $(".mform").attr("data-form-dirty", "false");
              removePin();
              embedIframeCode(data.response, $(".embedQuestion").eq($("#id_embedformeditable").data("position")));
            },
            fail: function (e) {
              console.error("fail", e);
            },
          },
        ]);
      });

      $("#longpage-main").on("mouseover mouseout", ".wrapper", function () {
        if (!_this.$store.state.UserModule.userCanMod)
          return;
        $(this).find(".embedQuestion").css("opacity", event.type === "mouseover" ? "1" : "0");
      });

      $("#longpage-main").on("mouseenter mouseleave", ".embedQuestion", function () {
        if (!_this.$store.state.UserModule.userCanMod)
          return;
        $(this).css("opacity", event.type === "mouseenter" ? "1" : "0");
      });

      var hasPin = function (onlyIfAutoPin = false) {
        return $("#pinQuestion").hasClass("active") && (!onlyIfAutoPin || $("#pinQuestion").hasClass("autopin"));
      }

      var addPin = function () {
        if (!hasPin()) {
          $("#pinQuestion").click();
          $("#pinQuestion").addClass("autopin");
        }
      }

      var removePin = function (onlyIfAutoPin = false) {
        if (hasPin(onlyIfAutoPin)) {
          $("#pinQuestion").click();
        }
      }

      $(".embedExistingQuestion").on("click", function () {
        $("#id_embedform").val("");
        $("#id_embedformeditable").text("");
        $("#id_embedformeditable").data("position", $(this).parent().index(".embedQuestion"));
        $(".atto_embedquestion_button").click();
      });

      var modalAdded = false;
      var modalInterval = null;

      function addModalWait(title, btn, waitingMessages = ["Bitte warten..."]) {
        if(btn) {
          $(btn).addClass("disabled");
        }
        var message = waitingMessages[0];
        var modal = `<div class="modal" id="modal-wait" tabindex="-1" role="dialog" aria-labelledby="modal-wait-label" aria-hidden="true">
                      <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title" id="modal-wait-label">${title}</h5>
                          </div>
                          <div class="modal-body text-center">
                            <div class="spinner-border" role="status">                
                              <span class="sr-only">Bitte warten...</span>
                            </div>
                            <div class="mt-3 mb-2 small message">${message}</span>                            
                          </div>
                      </div>
                    </div>`;

        modalAdded = true;
        setTimeout(function () {
          if (modalAdded) {
            $(modal).modal({ backdrop: "static", keyboard: false });
          }
        }, 1000);
        if (waitingMessages.length > 0) {
          var i = 0;
          modalInterval = setInterval(function () {
            if (modalAdded) {
              $("#modal-wait .modal-body .message").text(waitingMessages[i]);
              i = (i + 1) % waitingMessages.length;
            }
          }, 5000);
        }        
      }

      function removeModalWait(btn) {
        modalAdded = false;
        $("#modal-wait").modal("hide").remove();
        if(btn) {
          $(btn).removeClass("disabled");
        }
        clearInterval(modalInterval);
      }

      function addToast(message) {
        var toast =
          `<div class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-delay="1500" style="position: absolute; top: 10%; left: 50%">
        <div class="toast-body">
          ${message}
          <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      </div>`;
        $(toast).appendTo("#sidebar-tab-quiz").toast("show");
      }

      $("#longpage-main").on("mousemove", ".wrapper", function (e) {
        if (!_this.$store.state.UserModule.userCanMod)
          return;

        if (e.ctrlKey || e.shiftKey) {
          $(this).css("cursor", "pointer");
        }
        else {
          $(this).css("cursor", "");
        }
      });

      $("#longpage-main").on("click", ".wrapper", function (e) {
        if (!_this.$store.state.UserModule.userCanMod)
          return;

        if (e.ctrlKey || e.shiftKey) {
          $(this).toggleClass("selected-paragraph");
        }
      });

      $("#longpage-main").on("dblclick", function (e) {
        if (!_this.$store.state.UserModule.userCanMod)
          return;

        if (e.ctrlKey) {
          e.preventDefault();
          $(".selected-paragraph").removeClass("selected-paragraph");
          return false;
        }
      });

      $(".embedNewAIQuestion, .embedNewEmptyQuestion").on("click", function () {
        var btn = $(this).parent();
        var selection = window.getSelection();
        var selectedText = "";
        if (!selection.isCollapsed) {
          var closest = selection.focusNode.parentElement.closest("#longpage-content");
          if (closest != null && closest.id == "longpage-content") {
            selectedText = selection.getRangeAt(0).toString();
          }
        }
        var useAI = $(this).hasClass("embedNewAIQuestion") ? true : false;
        addModalWait("Aufgabe wird generiert...", btn, ["Bitte warten...", "Das kann einen Moment dauern.", "Es werden mehrere Versionen erstellt, um die Qualität und Struktur der Aufgaben zu optimieren.", "Nach fünf erfolglosen Versuchen wird eine Fehlermeldung angezeigt. Versuchen Sie es in solchen Fällen bitte erneut."]);
        ajax.call([
          {
            methodname: "mod_longpage_create_question",
            args: {
              longpageid: _this.context.longpageid,
              position: $(btn).index(".embedQuestion"),
              useAI: useAI,
              existingQuestions: $(btn).parent().next().find(".filter_embedquestion-iframe").contents().find(".qtext").map(function () { return $(this).text(); }).get().join(", "),
              selectedText: selectedText,
              selectedParagraphs: $(".selected-paragraph .embedQuestion").map(function () { return $(this).index(".embedQuestion"); }).get().join(", "),
            },
            done: function (data) {
              let result = JSON.parse(data.response);
              removePin();
              removeModalWait(btn);
              embedIframeCode(result["iframecode"], btn, !useAI);
              console.log(result["log"]);
              addToast("Aufgabe wurde erstellt.");
            },
            fail: function (e) {
              removeModalWait(btn);
              if(_this.context.isAdmin) {
                alert(e.message);
              }
              else {
                alert("Es ist ein Fehler aufgetreten. Bitte versuchen Sie es erneut.");
              }
            }
          },
        ]);
      });

      $(document).on("click", "#removeQuestion", function () {
        var btn = $("#" + $("#question .carousel-item.active iframe").attr("data-paragraph")).next().next(".embedQuestion");
        if (btn.length == 0)
          return;
        var embedid = $("#question .carousel-item.active iframe").attr("id");
        if (embedid == undefined) {
          return;
        }
        addModalWait("Aufgabe wird entfernt...", btn);
        ajax.call([
          {
            methodname: "mod_longpage_remove_question",
            args: {
              longpageid: _this.context.longpageid,
              embedid: embedid,
              position: $(btn).index(".embedQuestion"),
            },
            done: function (reads) {
              removePin();
              $("#question .carousel-item.active").remove();

              var iframecontainer = $(btn).parent(".wrapper").next().children().first();
              if ($(iframecontainer).children().length > 1) {
                var idFixed = embedid.replace("/", "\\/");
                $(iframecontainer).find("#" + idFixed).remove();
              }
              else {
                $(btn).parent(".wrapper").next().remove();
                $(btn).prev(".reading-comprehension").css("opacity", "").removeClass("reading-comprehension")
                $(btn).parent(".wrapper").removeAttr("data-reading-comprehension-count");
              }
              get_reading_comprehension();
              observerCall();
              removeModalWait(btn);
              addToast("Aufgabe wurde entfernt.");
            },
            fail: function (e) {
              console.error("fail", e);
              removeModalWait(btn);
            },
          },
        ]);
      });

      function reloadAllIframesInQuiz() {
        $("#question .carousel-item.active iframe").contents().find("form").removeAttr("data-form-dirty");
        $("#question iframe").each(function (idx, iframe) {
          var src = $(iframe).attr("src");
          $(iframe).attr("src", "");
          $(iframe).attr("src", src);
        });
      }

      $(document).on("click", "#lockQuestion", function () {
        var active = $("#question .carousel-item.active iframe");
        var questionid = $(active).attr("data-questionid");
        if (questionid == undefined) {
          return;
        }
        var embedid = $(active).attr("id").replace("/", "\\/");
        addModalWait("Aufgabe wird geändert...", this);
        var __this = this;
        ajax.call([
          {
            methodname: "mod_longpage_lock_question",
            args: {
              longpageid: _this.context.longpageid,
              questionid: questionid
            },
            done: function (reads) {
              removePin();

              var iframe = $("#longpage-content #" + embedid);
              if (!$(iframe).attr("data-tags").includes("neu")) {
                $(iframe).attr("data-tags", $(iframe).attr("data-tags") + ($(iframe).attr("data-tags") != "" ? "," : "") + "neu");
                changeLockButton(false);
                addToast("Aufgabe wurde gesperrt.");
              }
              else {
                $(iframe).attr("data-tags", $(iframe).attr("data-tags").replace("neu", "").replace(/,$/, ""));
                changeLockButton(true);
                addToast("Aufgabe wurde freigegeben.");
              }

              $("#longpage-content iframe#" + embedid + ", #question iframe#" + embedid).each(function (idx, ifr) {
                $(ifr).attr("data-tags", $(iframe).attr("data-tags"));
              });

              reloadAllIframesInQuiz();
              removeModalWait(__this);
            
            },
            fail: function (e) {
              console.error("fail", e);
              removeModalWait(__this);
            },
          },
        ]);
      });

      function handleQuickEditQuestionResult(data) {
        var result = JSON.parse(data.response);
        var questionid = result["questionid"];
        $("#question .carousel-item.active iframe").attr("data-questionid", questionid);
        $("#longpage-content iframe#" + $("#question .carousel-item.active iframe").attr("id").replace("/", "\\/")).attr("data-questionid", questionid);
        var qubaid = result["qubaid"];
        $("#question .carousel-item.active iframe").contents().find("form").attr("action", $("#question .carousel-item.active iframe").contents().find("form").attr("action").replace(/qubaid=\d+/, "qubaid=" + qubaid));
        addToast("Änderungen wurden gespeichert.");
        return result;
      }

      $(document).on("click", "#quickEditQuestion", function () {
        var activeIframe = $("#question .carousel-item.active iframe");
        let questionid = $(activeIframe).attr("data-questionid");
        if (questionid == undefined) {
          return;
        }

        addPin();
        $(this).addClass("disabled");

        var form = $(activeIframe).contents().find("form");
        var formData = new FormData(form[0]);
        formData.append("fillwithcorrect", 1);

        fetch($(form).attr("action"), {
          method: "POST",
          body: formData
        })
          .then((response) => response.text())
          .then((data) => {
            var checked = $(data).find("input[type=radio]:checked");
            $(activeIframe).contents().find("input[type=radio][value='" + $(checked).val() + "']").parent().find("button[title='Option löschen']").attr("disabled", true);
            $(activeIframe).contents().find("input[type=radio][value='" + $(checked).val() + "']").replaceWith('<span class="ml-2"><i class="icon fa fa-check text-success fa-fw " title="Correct" role="img" aria-label="Correct"></i></span>');
            var sequencecheck = $(data).find("input[name$='sequencecheck']").val();
            $(activeIframe).contents().find("input[name$='sequencecheck']").val(sequencecheck);
          })
          .catch((error) => {
            console.error("Error:", error);
          });

        var editable = $(activeIframe).contents().find(".que .answer .flex-fill, .que .qtext");
        if ($(editable).attr("contenteditable") == "true") {
          reloadAllIframesInQuiz();
          $(activeIframe).contents().find("body").attr("data-autosave", "true");
          return;
        }
        else {
          autosavefun.cancel();
          $(activeIframe).contents().find("body").attr("data-autosave", "false");
        }

        $(activeIframe).contents().find(".im-controls, .qtype_multichoice_clearchoice, .validationerror").hide();
        $(activeIframe).contents().find("body").removeAttr("data-tags");
        $(activeIframe).contents().find("input[type=radio]").attr("disabled", true);
        $(activeIframe).contents().find("input[type=radio]").removeAttr("checked");

        $(editable).attr("contenteditable", true);

        for (var i = 0; i < editable.length; i++) {
          var text = $(editable[i]).text();
          $(editable[i]).attr("data-text", text);
        }

        $(editable).on("keydown", function (e) {
          if (e.key === "Escape") {
            $(this).text($(this).attr("data-text"));
            $(this).blur();
            return false;
          }
        });

        $(activeIframe).contents().find("body").on("keydown", function (e) {
          if (e.key === "Escape") {
            reloadAllIframesInQuiz();
            addToast("Bearbeitung wurde abgebrochen.");
            $("#quickEditQuestion").removeClass("disabled");
          }
        });

        $(editable).on("blur", function () {
            var text = $(this).text();
            if (text == $(this).attr("data-text")) {
              return;
            }

            var __this = this;

            var optionNumber = $(activeIframe).contents().find(".que .answer > div").index($(this).parents(".r0, .r1"));
            questionid = $(activeIframe).attr("data-questionid");
            var qubaid = new URLSearchParams($(activeIframe).contents().find("form").attr("action")).get("qubaid");

            addModalWait("Aufgabe wird aktualisiert...", this);
            ajax.call([
              {
                methodname: "mod_longpage_edit_question",
                args: {
                  longpageid: _this.context.longpageid,
                  questionid: questionid,
                  action: "edit",
                  qubaid: qubaid,
                  useAI: false,
                  text: text,
                  optionNumber: optionNumber,
                },
                done: function (data) {
                  $(__this).attr("data-text", text);                 
                  handleQuickEditQuestionResult(data);
                  removeModalWait(__this);
                  removePin(true);
                },
                fail: function (e) {
                  alert(e.message);
                  removeModalWait(__this);
                  removePin(true);
                },
              },
            ]);
          });

        var options = $(activeIframe).contents().find(".que .answer .flex-fill");
        $(options).parent().removeClass("w-auto").addClass("w-100");
        $(options).each(function (idx, option) {
          var removeOption = $("<button class='btn btn-danger' title='Option löschen'><i class='fa fa-trash' style='cursor:pointer;'></i></button>");
          $(removeOption).on("click", function () {
            addModalWait("Option wird gelöscht...", this);
            var __this = this;
            var qubaid = new URLSearchParams($(activeIframe).contents().find("form").attr("action")).get("qubaid");
            questionid = $(activeIframe).attr("data-questionid");
            ajax.call([
              {
                methodname: "mod_longpage_edit_question",
                args: {
                  longpageid: _this.context.longpageid,
                  questionid: questionid,
                  action: "remove",
                  qubaid: qubaid,
                  useAI: false,
                  text: "",
                  optionNumber: idx,
                },
                done: function (data) {
                  handleQuickEditQuestionResult(data);
                  $(option).parent().parent().remove();
                  removeModalWait(__this);
                },
                fail: function (e) {
                  alert(e.message);
                  removeModalWait(__this);
                },
              }]);
            return false;
          });
          $(removeOption).appendTo($(option).parent().parent());
        });

        var plusBlank = $("<button class='btn btn-success addBlankDistractor ml-2' title='Leeren Distraktor hinzufügen'><i class='fa fa-plus-circle fa-fw' style='cursor:pointer;'></i>Distraktor hinzufügen</button>");
        var plusAI = $("<button class='btn btn-success addAIDistractor' title='Neuen Distraktor mit KI generieren'><i class='fa fa-plus-square fa-fw' style='cursor:pointer;'></i>Distraktor mit KI generieren</button>");
        $(plusBlank).add(plusAI).on("click", function () {
          addModalWait("Distraktor wird hinzugefügt...", this);
          var qubaid = new URLSearchParams($(activeIframe).contents().find("form").attr("action")).get("qubaid");
          questionid = $(activeIframe).attr("data-questionid");
          var paragraph = $(activeIframe).attr("data-paragraph");
          var text = $("#longpage-main #" + paragraph).text();          
          var __this = this;
          ajax.call([
            {
              methodname: "mod_longpage_edit_question",
              args: {
                longpageid: _this.context.longpageid,
                questionid: questionid,
                action: "add",
                qubaid: qubaid,
                useAI: $(this).hasClass("addAIDistractor"),
                text: text,
                optionNumber: -1,
              },
              done: function (data) {
                handleQuickEditQuestionResult(data);
                $(activeIframe).one("load", function () {
                  removeModalWait(__this);
                  $("#quickEditQuestion").click();
                  addToast("Distraktor wurde hinzugefügt.");
                });
                reloadAllIframesInQuiz();
              },
              fail: function (e) {
                alert(e.message);
                removeModalWait(__this);
              },
            }]);
          return false;
        });
        var buttons = $("<div class='mt-3 mb-2 float-right'></div>");
        if (_this.context.tags.includes("noAI")) {
          $(plusBlank).appendTo($(buttons));
        }
        if (_this.context.tags.includes("AI")) {
          $(plusAI).appendTo($(buttons));
        }
        $(buttons).insertAfter($(options).last().parent().parent()); 

        var rephraseButton = $("<button class='btn btn-seccondary btn-sm rephrase float-right' contenteditable='false' title='Text mit KI umformulieren'><i class='fa fa-refresh' style='cursor:pointer;'></i></button>");
        $(rephraseButton).on("click", function () {
          var paragraph = $(activeIframe).attr("data-paragraph");
          var text = $("#longpage-main #" + paragraph).text();
          var optionNumber = $(activeIframe).contents().find(".que .answer > div").index($(this).parents(".r0, .r1"));
          var qubaid = new URLSearchParams($(activeIframe).contents().find("form").attr("action")).get("qubaid");
          questionid = $(activeIframe).attr("data-questionid");
          addModalWait("Text wird umformuliert...", this);
          var __this = this;
          ajax.call([
            {
              methodname: "mod_longpage_edit_question",
              args: {
                longpageid: _this.context.longpageid,
                questionid: questionid,
                action: "rephrase",
                qubaid: qubaid,
                useAI: true,
                text: text,
                optionNumber: optionNumber,
              },
              done: function (data) {
                handleQuickEditQuestionResult(data);
                $(activeIframe).one("load", function () {
                  $("#quickEditQuestion").click();
                  removeModalWait(__this);
                  addToast("Text wurde umformuliert.");
                });
                reloadAllIframesInQuiz();
              },
              fail: function (e) {
                alert(e.message);
                removeModalWait(__this);
              },
            },
          ]);
          return false;
        });
        
        $(editable).each(function (idx, el) {
          if (!_this.context.tags.includes("AI"))
            return;
          if ($(el).find("p").length > 0) {
            $(rephraseButton).clone(true, true).appendTo($(el).find("p"));
          }
          else {
            $(rephraseButton).clone(true, true).appendTo($(el));
          }
        });

        var quitButton = $("<button class='btn btn-primary mt-4' title='Bearbeitung beenden'><i class='fa fa-close fa-fw' style='cursor:pointer;'></i>Fertig</button>");
        $(quitButton).on("click", function (e) {
          e.preventDefault();
          $("#quickEditQuestion").removeClass("disabled");
          reloadAllIframesInQuiz();
          addToast("Bearbeitung beendet.");
        });
        $(quitButton).insertAfter($(buttons));
      });

      $("#editQuestion").on("click", function () {
        let questionid = $("#question .carousel-item.active iframe").attr("data-questionid");
        if (questionid == undefined) {
          return;
        }
        var editLink = moodleRelease < 400 ? "question/question.php" : "question/bank/editquestion/question.php";
        window.open(window.location.href.replace("mod/longpage/view.php", editLink).replace("id", "cmid") + "&id=" + questionid, '_blank');
      });

      $("#deleteQuestion").on("click", function () {
        let questionid = $("#question .carousel-item.active iframe").attr("data-questionid");
        if (questionid == undefined) {
          return;
        }
        $("#removeQuestion").click();
        var deleteLink = moodleRelease < 400 ? "question/edit.php" : "question/bank/deletequestion/delete.php";
        window.open(window.location.href.replace("mod/longpage/view.php", deleteLink).replace("id", "cmid") + "&deleteselected=" + questionid + "&q" + questionid + "=1&returnurl=" + encodeURIComponent(window.location.href), '_blank');
      });

      $("#openQuestionBank").on("click", function () {
        var link = $("#question .carousel-item.active iframe").contents().find(".filter_embedquestion-viewquestionbank a").attr("href");
        if (link != undefined) {
          window.open(link, '_blank');
        }
        else {
          window.open(window.location.href.replace("mod/longpage/view.php", "question/edit.php").replace("id", "cmid"), '_blank');
        }
      });

      $("#carousel").parent().removeClass("overflow-y-auto").css("overflow-y", "hidden");

      $("#carousel").on("slide.bs.carousel", function (ev) {
        var iframe = $(ev.relatedTarget).find("iframe");
        changeLockButton(!($(iframe).attr("data-tags") && $(iframe).attr("data-tags").includes("neu")));
      });

      $("#carousel").on("slid.bs.carousel", function (ev) {
        var iframe = $(ev.relatedTarget).find("iframe");
        adjustCarouselControltoHeight(iframe);
      });

      function adjustCarouselControltoHeight(iframe = null) {
        if (iframe == null) {
          iframe = $("#question .carousel-item.active iframe");
        }
        var top = $(iframe).contents().find(".que").height() / 2;
        if (top >= 50) {
          $(".carousel-control-prev, .carousel-control-next").css("top", top);
        }
      }
      

      $(document).on("click", ".reading-comprehension", function () {
        _this.toggleTab();
        $("#carousel").carousel($("#carousel").find("#" + $(this).parent(".wrapper").next().find("iframe").attr("id").replace("/", "\\/")).parent(".carousel-item").index())
      });

      $("#total-reading-comprehension").on("click", function () {
        window.open(window.location.href.replace("mod/longpage/view.php", "report/embedquestion/activity.php").replace("id", "cmid"), '_blank');
      });

      $("#longpage-main, #longpage-sidebar").on("mouseenter mouseleave", function (ev) {
        var logentry = {
          pageX: ev.pageX ? ev.pageX : 0,
          pageY: ev.pageY ? ev.pageY : 0,
          element: ev.delegateTarget.id,
          utc: new Date().getTime(),
          type: ev.type, 
        };
        log("moved", logentry);
      });

      function changeLockButton(lock)
      {
        if (lock) {
          $("#lockQuestion").find("i").removeClass("fa-lock").addClass("fa-unlock");
          $("#lockQuestion").attr("title", "Aufgabe freigeben");
        }
        else {
          $("#lockQuestion").find("i").removeClass("fa-unlock").addClass("fa-lock");
          $("#lockQuestion").attr("title", "Aufgabe sperren");
        }
      }

      if(_this.context.tags.includes('tour')) {
      
        var moveNextWhenReady = function (nextElementSelectorOrElementOrFunction, driverObj, afterOnNextClick) {
            var nextElementSelectorOrElement = typeof nextElementSelectorOrElementOrFunction === 'function' ? nextElementSelectorOrElementOrFunction() : nextElementSelectorOrElementOrFunction;
              
            if ($(nextElementSelectorOrElement).length > 0) {
              driverObj.moveNext();
              if (afterOnNextClick) {
                afterOnNextClick();
              }
            } else {
                setTimeout(function () {
                    moveNextWhenReady(nextElementSelectorOrElementOrFunction, driverObj, afterOnNextClick);
                }, 1000);
            }
        };

        var processedElements = new Map();

        function addStep(steps, element, title, description, nextElementSelectorOrElement, afterOnNextClick, popoverSide, popoverAlign) {
          var l = steps.length;
          steps.push({
            element: element,
            popover: {
              title: title,
              description: description,
              side: popoverSide || 'right',
              align: popoverAlign || 'start',
              onNextClick: () => {

                if(processedElements.has(l)) {
                  return;
                }

                if (nextElementSelectorOrElement == null) {
                  driverObj.moveNext();
                  if (afterOnNextClick) {
                    afterOnNextClick();
                  }
                  return;
                }

                moveNextWhenReady(nextElementSelectorOrElement, driverObj, afterOnNextClick);
                processedElements.set(l, true);                
              },
            }
          });
          return steps;
        }

        var steps = [];
        if (_this.context.tags.includes("noAI"))
        {
          addStep(steps, null, 'Willkommen zur Tour!', 'Diese geführte Tour dient zum Kennenlernen der Funktionalitäten und muss bis zum Ende durchgeführt werden.  Warten Sie einen Moment, bis die Seite geladen wurde und der Text erscheint.', "#longpage-content #paragraph-0:visible", () => $("#longpage-main").scrollTop(0));
          addStep(steps, '#longpage-content', 'Plus-Button', 'Fahren Sie mit der Maus über einen Absatz. Rechts oberhalb des Absatzes erscheint ein Plus-Button. Klicken Sie auf den Plus-Button, um eine Aufgabe hinzuzufügen.', "#quickEditQuestion:visible", null, 'right', 'center');
          addStep(steps, '#quickEditQuestion', 'Aufgabe bearbeiten', 'Klicken Sie auf den Editier-Button, um eine Aufgabe zu bearbeiten.', () => $("#question .carousel-item.active iframe").contents().find(".qtext[contenteditable=true]"));
          addStep(steps, "#longpage-sidebar", 'Aufgabe bearbeiten', 'Ändern Sie den Text einer Aufgabe, indem Sie auf den Text klicken und den Text bearbeiten. Klicken Sie außerhalb des Textes, um die Änderungen zu speichern.', ".toast-body:contains('Änderungen wurden gespeichert.')");
          addStep(steps, "#longpage-sidebar", 'Distraktor hinzufügen', 'Fügen Sie einen Distraktor hinzu, indem Sie auf den Button "Distraktor hinzufügen" klicken.', ".toast-body:contains('Distraktor wurde hinzugefügt.')");
          addStep(steps, "#longpage-sidebar", 'Option löschen', 'Löschen Sie eine Antwortmöglichkeit, indem Sie auf den Button "Option löschen" klicken. Nur möglich, wenn mehr als zwei Antwortmöglichkeiten vorhanden sind. Nur falsche Antwortmöglichkeiten können gelöscht werden.', ".toast-body:contains('Änderungen wurden gespeichert.'):nth(1)");
          addStep(steps, "#longpage-sidebar", 'Bearbeitung beenden', 'Klicken Sie auf den Button "Fertig", um die Bearbeitung zu beenden.', ".toast-body:contains('Bearbeitung beendet.')");
          addStep(steps, "#lockQuestion", 'Aufgabe freigeben / sperren', 'Klicken Sie auf den Button "Aufgabe freigeben / sperren", um die Aufgabe freizugeben.', ".toast-body:contains('Aufgabe wurde freigegeben.')");
          addStep(steps, "#removeQuestion", 'Einbettung entfernen', 'Klicken Sie auf den Button "Einbettung entfernen", um die Aufgabe zu entfernen.', ".toast-body:contains('Aufgabe wurde entfernt.')");
          addStep(steps, null, 'Fertig!', 'Sie haben die Tour erfolgreich abgeschlossen. Wenn Sie genügend Aufgaben angelegt haben, können Sie auf den Button am Anfang des Textes klicken, um mit der Studie fortzufahren.');
        }
        else if (_this.context.tags.includes("AI"))
        {
          addStep(steps, null, 'Willkommen zur Tour!', 'Diese geführte Tour dient zum Kennenlernen der Funktionalitäten und muss bis zum Ende durchgeführt werden. Warten Sie einen Moment, bis die Seite geladen wurde und der Text erscheint.', "#longpage-content #paragraph-0:visible", () => $("#longpage-main").scrollTop(0));
          addStep(steps, '#longpage-content', 'Absätze auswählen', 'Wählen Sie vor dem Absatz, zu dem Sie eine Aufgabe generieren wollen, andere Absätze aus, die der KI als Kontext dienen. Klicken Sie dazu mit gedrückter Strg- oder Umschalt-Taste auf den jeweiligen Absatz.', ".selected-paragraph", () => $("#longpage-main").scrollTop(0), 'right', 'center');
          addStep(steps, '#longpage-content', 'Text markieren', 'Markieren Sie einen Teil des Textes, um für die KI den Fokus der zu generierenden Aufgabe festzulegen.', () => window.getSelection().toString() !== "", () => $("#longpage-main").scrollTop(0), 'right', 'center');
          addStep(steps, '#longpage-content', 'Plus-Button', 'Fahren Sie mit der Maus über einen Absatz. Rechts oberhalb des Absatzes erscheint ein Plus-Button. Klicken Sie auf den Plus-Button, um eine Aufgabe hinzuzufügen. Das kann einen Moment dauern. Eine Fehlermeldung kann bedeuten, dass die KI keine Aufgabe generieren konnte. Probieren Sie es in diesem Fall mit einer anderen Markierung oder einem anderen Abschnitt.', "#quickEditQuestion:visible", () => $("#longpage-main").scrollTop(0), 'right', 'center');
          addStep(steps, '#quickEditQuestion', 'Aufgabe bearbeiten', 'Geschafft! Das Auswählen und Markieren von Text ist übrigens optional, um eine Aufgabe mit KI zu generieren. Klicken Sie auf den Editier-Button, um eine Aufgabe zu bearbeiten.', () => $("#question .carousel-item.active iframe").contents().find(".qtext[contenteditable=true]"));
          addStep(steps, "#longpage-sidebar", 'Text umformulieren', 'Lassen Sie den Text der Aufgabe oder einer Antwortmöglichkeit von der KI umformulieren. Klicken Sie dazu auf einen der kreisförmigen Pfeilsymbole auf der rechten Seite eines Textfeldes.', ".toast-body:contains('Text wurde umformuliert.')");
          addStep(steps, "#longpage-sidebar", 'Distraktor mit KI generieren', 'Fügen Sie einen Distraktor hinzu, indem Sie auf den Button "Distraktor mit KI generieren" klicken.', ".toast-body:contains('Distraktor wurde hinzugefügt.')");
          addStep(steps, null, 'Fertig!', 'Sie haben die Tour erfolgreich abgeschlossen. Wenn Sie genügend Aufgaben angelegt haben, können Sie auf den Button am Anfang des Textes klicken, um mit der Studie fortzufahren.');
        }        

        var showButtons = ["next"];
        var allowClose = _this.context.isAdmin || window.location.search.includes("admin=1");
        if (allowClose) {
          showButtons.push("close");
        }
        const driverObj = driver({
          showProgress: true,
          allowClose: allowClose,
          overlayOpacity: 0,
          doneBtnText: "Fertig",
          nextBtnText: "Weiter",
          showButtons: showButtons,
          steps: steps,
          smoothScroll: true,
          popoverClass: 'driverjs-theme'
        });
      
        driverObj.drive();
      }
    });
  }
};
</script>

