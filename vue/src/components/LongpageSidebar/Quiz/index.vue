<template>
  <sidebar-tab>
  <template #body> 
    <div class="row pr-3">
      <h3
          class="col m-0 tab-title" style="width: 70%"
        >
          {{$t('sidebar.tabs.quiz.heading')}}
        </h3>
      
        <button class="btn dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-if="this.$store.state.UserModule.userCanMod">
          <i class="fa fa-cog fa-fw fa-lg" /> 
        </button>
        <div class="dropdown-menu dropdown-menu-right" style="min-width: 15rem;" v-show="this.$store.state.UserModule.userCanMod">
          <a class="dropdown-item" id="editQuestion" href="javascript:void(0)"><i class="fa fa-pencil fa-fw" /> Frage editieren</a>
          <a class="dropdown-item" id="lockQuestion" href="javascript:void(0)"><i class="fa fa-unlock fa-fw" /> Frage freigeben / sperren</a>
          <!-- <a class="dropdown-item" id="changeQuestion" href="javascript:void(0)"><i class="fa fa-cog fa-fw" /> Einbettung editieren</a> -->
          <a class="dropdown-item" id="removeQuestion" href="javascript:void(0)"><i class="fa fa-minus-square fa-fw" />Einbettung entfernen</a>
          <a class="dropdown-item" id="deleteQuestion" href="javascript:void(0)"><i class="fa fa-trash fa-fw" />Frage löschen</a>
          <a class="dropdown-item" :href="'/question/edit.php?courseid=' + this.context.courseId" target="_blank"><i class="fa fa-question fa-fw" />Fragensammlung öffnen</a>
        </div>
      
      <div class="col-auto px-0 offset-md-1">
        <a href="javascript:void(0)" id="total-reading-comprehension" title="Frage oben halten" data-toggle="tooltip"><i class="fa fa-battery-0 fa-fw fa-lg" /></a>
      </div>
      <div class="col-auto px-0">
        <a href="javascript:void(0)" id="pinQuestion" title="Frage oben halten" data-toggle="tooltip"><i class="fa fa-thumb-tack fa-fw fa-lg" /></a>
      </div>
      <div class="col-auto px-0">
        <a href="javascript:void(0)" id="prevQuestion" title="Vorherige Frage" data-toggle="tooltip"><i class="fa fa-arrow-up fa-fw fa-lg" /></a> 
      </div>
      <div class="col-auto px-0">
        <a href="javascript:void(0)" id="nextQuestion" title="Nächste Frage" data-toggle="tooltip"><i class="fa fa-arrow-down fa-fw fa-lg" /></a>
      </div>
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
      <a href="javascript:void(0)" class="embedNewQuestion" v-show="this.$store.state.UserModule.userCanMod">
        <i class="fa fa-plus fa-fw" title="Neue Frage für Abschnitt mit KI generieren und einbetten (Text auswählen, um Thema einzuschränken)" data-toggle="tooltip"/>
      </a>
      <a href="javascript:void(0)" class="embedExistingQuestion" v-show="this.$store.state.UserModule.userCanMod">
        <i class="fa fa-plus-square fa-fw" title="Vorhandene Frage einbetten" data-toggle="tooltip"/>
      </a>
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
  top: 200px;
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
  padding-left: 90%;
  margin-top: -20px !important;
}

.embedNewQuestion, .embedExistingQuestion
{
  background-color: white;
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
  mounted() 
  {
    let _this = this;
    const _ = require('lodash');

    let moodleRelease = $("#longpage-app-container").data("moodle-release").split(".");
    moodleRelease = parseInt(moodleRelease[0]) * 100 + parseInt(moodleRelease[1]);

    function get_reading_comprehension()
    {
      ajax.call([
        {
          methodname: "mod_longpage_get_reading_comprehension",
          args: {
            longpageid: _this.context.longpageid,
          },
          done: function (reads) {
            try {
              let data = JSON.parse(reads.response);
            
              for (const [id, entry] of Object.entries(data)) 
              {
                var value = entry["value"];
                var level = entry["level"];
                var tags = Object.values(entry["tags"]).join(", ");
                var idFixed = id.replace("/", "\\/");
                var iframe = $("#longpage-content #" + idFixed);
                var paragraph = $(iframe).parents(".wrapper").prev();
                $(paragraph).each(function(index, p)
                {
                  if(!$(p).attr("data-reading-comprehension-count"))
                  {
                    $(p).attr("data-reading-comprehension-count", level);
                    $(p).attr("data-reading-comprehension-sum", level*value);
                  }
                  else
                  {
                    $(p).attr("data-reading-comprehension-count", parseInt($(p).attr("data-reading-comprehension-count"))+level);
                    $(p).attr("data-reading-comprehension-sum", parseFloat($(p).attr("data-reading-comprehension-sum"))+(level*value));
                  }
                });

                $(iframe).attr("data-embedid", idFixed);
                $(iframe).attr("data-questionid", entry["id"]);  
                $(iframe).attr("data-tags", tags);              
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
                $(progress).attr("title", $(progress).attr("data-original-title"));
                $(progress)
                  .attr(
                    "title",
                    (_this.context.showreadingprogress ? ($(progress).attr("title").substr(0, $(progress).attr("title").indexOf("gelesen.") + 8) + "<br>") : "") +
                    "Ihr geschätztes Leseverständnis beträgt " +
                      (100*value).toFixed(2) +
                      "%."
                  ).css("opacity", Math.max(0.1, value)).addClass("reading-comprehension");
                  $(paragraph).attr("data-reading-comprehension-count", "");
              });

              $(".reading-progress").tooltip("dispose").tooltip({ "placement": "auto", "html": true });
              
              var rc = 0;
              if (len > 0)
              {
                rc = (100 * sum / len).toFixed(0);
              }

              if(rc <= 50)
              {
                EventBus.publish(SidebarEvents.CHANGE_BADGES, { type: SidebarTabKeys.QUIZ, count: repeat, title: "Ihr geschätztes Leseverständnis für die ganze Seite \nund " + repeat + " Fragen beträgt weniger als 50%." });
              }             

              $("#sidebar-tab-quiz #total-reading-comprehension").attr("title", "Ihr geschätztes Leseverständnis für die ganze Seite <br>beträgt: " + rc + " %.<br>Klicken Sie für eine Übersicht der Fragen.").tooltip({"placement":"auto", "html":true, "title":""}).attr("title", "");
              $("#sidebar-tab-quiz #total-reading-comprehension i").attr("class", "fa fa-fw fa-lg fa-battery-" + Math.floor(rc / 25));
              $("#sidebar-tab-quiz #total-reading-comprehension").show();
              
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

    function isElementInViewport(element, index, array)
    {
      // Special bonus for those using jQuery
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

    function isElementBottomInViewport(element)
    {
      // Special bonus for those using jQuery
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

    $(document).ready(function () {
      var readfun = _.debounce(function () {
        get_reading_comprehension();
      }, 2000);

      get_reading_comprehension();

      $(".reading-progress").not($("h1, h2, h3, h4, h5, h6").next().add($(".filter_embedquestion-iframe").parent().next())).parent()
        .append($("#embedQuestion").clone().removeAttr("id").addClass("embedQuestion"));
      $(".embedNewQuestion, .embedExistingQuestion").show();

      //let previousY = 0;
      let directionUp = false;
      //let currentY = 0;

      var observerStates = {};

      function observerCall(entries = []) {
        if ($("#pinQuestion").hasClass("active")) {
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

              if ($(target).attr("data-tags") && $(target).attr("data-tags").includes("neu") && !_this.$store.state.UserModule.userCanMod)
              {
                return;
              }

              target.classList.add("last-visible");
              added[idFixed] = 1;

              var div = $(`<div class="carousel-item"></div>`);
              var iframeCloned = $($("#longpage-main " + idFixed)[0]).clone(true);
              var src = $(iframeCloned).attr("src");
              $(iframeCloned).attr("src", "");
              $(iframeCloned).appendTo(div);

              let questionid = $(iframeCloned).attr("data-questionid");

              var obs = new IntersectionObserver((entries, o) => {
                var spinner = `<div id="quiz-spinner" class="row no-gutters vh-50">
                <div class="spinner-border m-auto" role="status">
                  <span class="sr-only" />
                </div>
                </div>`;

                entries.forEach((entry) => {
                  if (entry.isIntersecting) {
                    o.unobserve(entry.target);
                    $("#question").css("opacity", 0.2);
                    $("#quiz-spinner").remove();
                    $(spinner).appendTo("#sidebar-tab-quiz");
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
                if ($("#pinQuestion").hasClass("autopin")) {
                  $("#pinQuestion").click();
                }

                $("#question iframe" + idFixed).contents().find("body").attr("data-tags", $(this).attr("data-tags"));

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
                  if (M.util.js_pending() && cnt < 10) {
                    setTimeout(waitPending, 500, cnt + 1);
                  }
                  else {
                    $("#quiz-spinner").remove();
                    $("#question").css("opacity", 1);
                  }
                }

                waitPending();

                $(this).contents().find("body").on('click', function (ev) {
                  if (!$("#pinQuestion").hasClass("active")) {
                    $("#pinQuestion").addClass("autopin");
                    $("#pinQuestion").click();
                  }
                  var logentry = {
                    longpageid: _this.context.longpageid,
                    pageX: ev.pageX,
                    pageY: ev.pageY,
                    embedid: target.id,
                    questionid: questionid
                  };
                  ajax.call([
                    {
                      methodname: "mod_longpage_log",
                      args: {
                        data: {
                          entry: JSON.stringify(logentry),
                          action: "clicked",
                          utc: Math.ceil(new Date().getTime() / 1000),
                          courseid: _this.context.courseId
                        },
                      },
                      done: function (reads) {
                      },
                      fail: function (e) {
                        console.error("fail", e);
                      },
                    },
                  ]);
                });

                var autosavefun = _.debounce(function () {
                  ajax.call([
                    {
                      methodname: "mod_longpage_autosave",
                      args: {
                        data: {
                          qubaid: new URLSearchParams($(this).contents().find("form").attr("action")).get("qubaid"),
                          form: JSON.stringify(Object.fromEntries(new FormData($(this).contents().find("form")[0])))
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

                $(this).contents().find("body").on('click keyup', autosavefun);

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
          $("#carousel-indicators").children().remove();
        }
      }

      var observer = new IntersectionObserver(observerCall, { rootMargin: "-100px 0px -100px 0px", threshold: 0, root: document.querySelector('#longpage-main') });


      $("#longpage-main .filter_embedquestion-iframe").each(function (i, el) {
        var paragraph = $(el).parents(".wrapper").prev();
        $(el).attr("data-paragraph", $(paragraph).children().first().attr("id"));
        observer.observe($(paragraph)[0]);
      });

      $("#question").on("mouseover", "iframe", function () {
        return;
        var el = $("#" + $(this).attr("data-paragraph"));
        // $(el).css("background-color", "#eee");
        // setTimeout(function () {
        //   $(el).css("background-color", "#fff");
        // }, 3000);

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
          if(Object.keys(observerStates).length > 0)
          {
            observerCall(Object.values(observerStates)); 
          }
        }
        observerStates = {};
      });

      function embedIframeCode(iframecode, btn)
      {
        if (iframecode == "error")
        {
          alert("Es ist ein Fehler aufgetreten. Bitte versuchen Sie es erneut.");
          return;
        }

        iframecode = $(iframecode);
        $(iframecode).attr("data-paragraph", $(btn).parent(".wrapper").children().first().attr("id")).prop('outerHTML');
        iframecode = $(iframecode).prop('outerHTML');

        if ($(btn).prev(".reading-comprehension").length > 0)
        {
          $(btn).parent(".wrapper").next().children().first().append(iframecode);
        }
        else
        {
          var wrapper = $("<div class='wrapper'><p>" + iframecode + "</p></div>");
          $(wrapper).height("0px")
          $(wrapper).css("padding", "0px"); 
          $(btn).parent(".wrapper").after(wrapper);
          observer.observe($(btn).parent(".wrapper")[0]);        
        }

        get_reading_comprehension(); 
        observerCall([{
            target: $(btn).parent(".wrapper")[0],
            isIntersecting: false,
          },{
            target: $(btn).parent(".wrapper")[0],
            isIntersecting: true,
          }]);

        $("#carousel").carousel($("#question").children().length - 1);       
        setTimeout(function () {
          $("#question .carousel-item.active").animate({ "margin-top": "+=20px" }, 200).animate({ "margin-top": "-=20px" }, 200);          
        }, 1000);          
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

      // Toggle visibility of embedQuestion on mouseover and mouseout
      $("#longpage-main").on("mouseover mouseout", ".wrapper", function () {
        $(this).find(".embedQuestion").css("opacity", event.type === "mouseover" ? "1" : "0");
      });

      // Toggle visibility of embedQuestion on mouseenter and mouseleave
      $("#longpage-main").on("mouseenter mouseleave", ".embedQuestion", function () {
        $(this).css("opacity", event.type === "mouseenter" ? "1" : "0");
      });

      var removePin = function () {
        if ($("#pinQuestion").hasClass("active")) {
              $("#pinQuestion").click();
        }
      }

      $(".embedExistingQuestion").on("click", function () {
        $("#id_embedform").val("");
        $("#id_embedformeditable").text("");
        $("#id_embedformeditable").data("position", $(this).parent().index(".embedQuestion"));
        $(".atto_embedquestion_button").click();
      });

      $(".embedNewQuestion").on("click", function () {

        //_this.$parent.$parent.pageReady = false;
        var modal = `<div class="modal" id="modal-wait" tabindex="-1" role="dialog" aria-labelledby="modal-wait-label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="modal-wait-label">Frage wird generiert</h5>
            </div>
            <div class="modal-body text-center">
              <div class="spinner-border" role="status">                
                <span class="sr-only">Bitte warten...</span>
              </div>
            </div>
          </div>
        </div>`;

        $(modal).modal({ backdrop: "static", keyboard: false });

        var btn = $(this).parent();
        var text = $(btn).prev(".reading-comprehension").prev().text();

        var start = -1;
        var len = 0;
        var selection = window.getSelection();
        if (!selection.isCollapsed)
        {
          var selectedText = selection.getRangeAt(0).toString()
          var closest = selection.focusNode.parentElement.closest("#longpage-content");
          if (closest != null && closest.id == "longpage-content") {
            var start = text.indexOf(selectedText);
            var len = selectedText.length; 
          }
        }
        
        ajax.call([
          {
            methodname: "mod_longpage_create_question",
            args: {
              longpageid: _this.context.longpageid,
              position: $(btn).index(".embedQuestion"),
              startIndex: start,
              length: len,
            },
            done: function (data) {
              //_this.$parent.$parent.pageReady = true; 
              removePin();
              $("#modal-wait").modal("hide");
              embedIframeCode(data.response, btn);                        
            },
            fail: function (e) {
              //_this.$parent.$parent.pageReady = true;
              $("#modal-wait").modal("hide");
              alert(e.message);
            }
          } ,
        ]);        
      });
      
      $("#removeQuestion").on("click", function () {
        var btn = $("#" + $("#question .carousel-item.active iframe").attr("data-paragraph")).next().next(".embedQuestion");
        if (btn.length == 0)
          return;
        var embedid = $("#question .carousel-item.active iframe").attr("id");
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
              //remove carousel item
              $("#question .carousel-item.active").remove();

              //remove embedQuestion
              var iframecontainer = $(btn).parent(".wrapper").next().children().first();
              if ($(iframecontainer).children().length > 1)
              {
                //remove iframe with embedid
                var idFixed = embedid.replace("/", "\\/");
                $(iframecontainer).find("#" + idFixed).remove();
              }
              else
              {
                $(btn).parent(".wrapper").next().remove();
                $(btn).prev(".reading-comprehension").css("opacity", "").removeClass("reading-comprehension")
                $(btn).parent(".wrapper").removeAttr("data-reading-comprehension-count");
              }             
              get_reading_comprehension();
              observerCall();

            },
            fail: function (e) {
              console.error("fail", e);
            },
          },
        ]);
      });

      $("#lockQuestion").on("click", function () {
        var active = $("#question .carousel-item.active iframe");
        var questionid = $(active).attr("data-questionid");
        var embedid = $(active).attr("id").replace("/", "\\/");
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
              if (!$(iframe).attr("data-tags").includes("neu"))
              {
                $(iframe).attr("data-tags", $(iframe).attr("data-tags") + ($(iframe).attr("data-tags") != "" ? "," : "") + "neu");
              }
              else
              {
                $(iframe).attr("data-tags", $(iframe).attr("data-tags").replace("neu", "").replace(/,$/, ""));
              }

              $("#longpage-content iframe#" + embedid + ", #question iframe#" + embedid).each(function (idx, ifr) {
                $(ifr).attr("data-tags", $(iframe).attr("data-tags"));
              });


              //reload all iframes in quiz
              $("#question iframe").each(function (idx, iframe) {
                var src = $(iframe).attr("src");
                $(iframe).attr("src", "");
                $(iframe).attr("src", src);
              });
            },
            fail: function (e) {
              console.error("fail", e);
            },
          },
        ]);
      });

      $("#editQuestion").on("click", function () {
        let questionid = $("#question .carousel-item.active iframe").attr("data-questionid");
        if (questionid == undefined)
        {
          return;
        }
        var editLink = moodleRelease < 400 ? "question/question.php" : "question/bank/editquestion/question.php";
        window.open(window.location.href.replace("mod/longpage/view.php", editLink).replace("id", "cmid") + "&id=" + questionid, '_blank');
      });

      $("#deleteQuestion").on("click", function () {
        let questionid = $("#question .carousel-item.active iframe").attr("data-questionid");
        if (questionid == undefined)
        {
          return;
        }
        $("#removeQuestion").click();
        var deleteLink = moodleRelease < 400 ? "question/edit.php" : "question/bank/deletequestion/delete.php";
        window.open(window.location.href.replace("mod/longpage/view.php", deleteLink).replace("id", "cmid") + "&deleteselected=" + questionid + "&q" + questionid + "=1&returnurl=" + encodeURIComponent(window.location.href), '_blank');
      });

      $("#carousel").parent().removeClass("overflow-y-auto").css("overflow-y", "hidden");


      $(document).on("click", ".reading-comprehension", function()
      {
        _this.toggleTab();
        $("#carousel").carousel($("#carousel").find("#" + $(this).parent(".wrapper").next().find("iframe").attr("id").replace("/", "\\/")).parent(".carousel-item").index()) 
      });

      $("#total-reading-comprehension").on("click", function () {
        window.open(window.location.href.replace("mod/longpage/view.php", "report/embedquestion/activity.php").replace("id", "cmid"), '_blank');
      });
    });
  }
};
</script>

