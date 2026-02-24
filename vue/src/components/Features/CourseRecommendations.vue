<template>
  <div>
    <button
      type="button"
      class="close ml-auto align-self-center d-block"
      aria-label="Close"
      v-on:click="$emit('hideTabContent')"
    >
      <span aria-hidden="true">&times;</span>
    </button>
    <div class="dropdown show">
      <a
        class="btn btn-secondary dropdown-toggle"
        href="#"
        role="button"
        id="dropdownMenuLink"
        data-toggle="dropdown"
        aria-haspopup="true"
        aria-expanded="false"
      >
        Dropdown link
      </a>

      <div class="dropdown-menu" aria-labelledby="dropdownMenuLink">
        <a class="dropdown-item" href="#">Action</a>
        <a class="dropdown-item" href="#">Another action</a>
        <a class="dropdown-item" href="#">Something else here</a>
      </div>
    </div>
    <ul>
      <li v-for="course in courses">
        {{ course.course }}: {{ course.unit }} ({{ course.confidence }})
      </li>
    </ul>
  </div>
</template>

<script>
import { LONGPAGE_CONTENT_ID } from "@/util/constants";

export default {
  name: "CourseRecommendation",
  data: () => {
    return {
      serverPath: "http://127.0.0.1:8080/course-recommender",
      parantSelector: "#" + LONGPAGE_CONTENT_ID,
      courses: [],
      recommendations: [],
      blacklist: [
        1144, 2333, 2337, 2351, 2355, 2353, 2366, 1662, 1664, 1666, 1671, 1661,
        1672, 1675, 1665, 1810, 1602, 1603, 1358,
      ],
    };
  },
  mounted: function () {
    //console.log('Limit to certain pages: ' + this.$store.getters.getPageId, this.$store.getters.getCourseId, !(this.$store.getters.getPageId === 15 && this.$store.getters.getCourseId === 5))
    let show = true;
    if (
      M.cfg.wwwroot === "https://aple.fernuni-hagen.de" &&
      this.$store.getters.getPageId === "12" &&
      this.$store.getters.getCourseId === "5"
    ) {
      show = true;
    } else if (
      M.cfg.wwwroot === "http://127.0.0.1/moodle" &&
      this.$store.getters.getPageId === "15" &&
      this.$store.getters.getCourseId === "5"
    ) {
      show = true;
    }
    if (!show) {
      return;
    }

    let _this = this;
    // BUG: cross-origin policy problem because python server and moodle are on the same IP with different hosts
    // this.getRelatedUnits('Rechnernetze', '#longpage-paragraph-7');

    // Mock
    this.recommendations = {
      ke1: [
        [
          [
            "Fakultaet Mathematik und informatik/Datenbanksysteme für neue Anwendungen/1671 Datenbanken I/KE2_t.pickle",
            0.7672185897827148,
            17,
          ],
          [
            "Fakultaet Mathematik und informatik/Datenbanksysteme für neue Anwendungen/1665 Datenbansysteme/KE3_t.pickle",
            0.7663917541503906,
            15,
          ],
          [
            "Fakultaet Mathematik und informatik/Datenbanksysteme für neue Anwendungen/1675 Moving Objects Databases/KE7_t.pickle",
            0.7663782835006714,
            19,
          ],
          [
            "Fakultaet Mathematik und informatik/Datenbanksysteme für neue Anwendungen/1810 Übersetzerbau/KE7_t.pickle",
            0.7639803886413574,
            20,
          ],
          [
            "Fakultaet Mathematik und informatik/Datenbanksysteme für neue Anwendungen/1672 Datenbanken II/KE2_t.pickle",
            0.7611325979232788,
            18,
          ],
        ],

        [
          [
            "Fakultaet Mathematik und informatik/Datenbanksysteme für neue Anwendungen/1672 Datenbanken II/KE2_t.pickle",
            0.6827952265739441,
            18,
          ],
          [
            "Fakultaet Mathematik und informatik/Datenbanksysteme für neue Anwendungen/1664 Implementierungskonzepte für Datenbanksysteme/KE2_t.pickle",
            0.6818767786026001,
            14,
          ],
          [
            "Fakultaet Mathematik und informatik/Datenbanksysteme für neue Anwendungen/1810 Übersetzerbau/KE5_t.pickle",
            0.6803094148635864,
            20,
          ],
          [
            "Fakultaet Mathematik und informatik/Datenbanksysteme für neue Anwendungen/1665 Datenbansysteme/KE1_t.pickle",
            0.680269718170166,
            15,
          ],
          [
            "Fakultaet Mathematik und informatik/Datenbanksysteme für neue Anwendungen/1675 Moving Objects Databases/KE7_t.pickle",
            0.6792938113212585,
            19,
          ],
        ],
        [
          [
            "Fakultaet Mathematik und informatik/Datenbanksysteme für neue Anwendungen/1664 Implementierungskonzepte für Datenbanksysteme/KE2_t.pickle",
            0.5843449831008911,
            14,
          ],
          [
            "Fakultaet Mathematik und informatik/Datenbanksysteme für neue Anwendungen/1664 Implementierungskonzepte für Datenbanksysteme/KE1_t.pickle",
            0.5839802026748657,
            14,
          ],
          [
            "Fakultaet Mathematik und informatik/Stabsstelle Elektro- und Informationstechnik/2366 Robotertechnik II/KE6_t.pickle",
            0.5799764394760132,
            72,
          ],
          [
            "Fakultaet Mathematik und informatik/Mikro- und Nanophotonic/2337 Optische Nachrichtentechnik II/KE2_t.pickle",
            0.579115092754364,
            40,
          ],
          [
            "Fakultaet Mathematik und informatik/Datenbanksysteme für neue Anwendungen/1662 Datenstrukturen II/KE3_t.pickle",
            0.5789429545402527,
            13,
          ],
        ],
        [
          [
            "Fakultaet Mathematik und informatik/Datenbanksysteme für neue Anwendungen/1665 Datenbansysteme/KE3_t.pickle",
            0.731177806854248,
            15,
          ],
          [
            "Fakultaet Mathematik und informatik/Mikro- und Nanophotonic/2337 Optische Nachrichtentechnik II/KE2_t.pickle",
            0.7252232432365417,
            40,
          ],
          [
            "Fakultaet Mathematik und informatik/Datenbanksysteme für neue Anwendungen/1675 Moving Objects Databases/KE7_t.pickle",
            0.7211812734603882,
            19,
          ],
          [
            "Fakultaet Mathematik und informatik/Datenbanksysteme für neue Anwendungen/1661 Datenstrukturen I/KE4_t.pickle",
            0.7203837037086487,
            12,
          ],
          [
            "Fakultaet Mathematik und informatik/Datenbanksysteme für neue Anwendungen/1666 Datenbanken in Rechnernetzen/KE1_t.pickle",
            0.719031572341919,
            16,
          ],
        ],
        [
          [
            "Fakultaet Mathematik und informatik/Datenbanksysteme für neue Anwendungen/1664 Implementierungskonzepte für Datenbanksysteme/KE2_t.pickle",
            0.6771007180213928,
            14,
          ],
          [
            "Fakultaet Mathematik und informatik/Mikro- und Nanophotonic/2337 Optische Nachrichtentechnik II/KE1_t.pickle",
            0.6752136945724487,
            40,
          ],
          [
            "Fakultaet Mathematik und informatik/Datenbanksysteme für neue Anwendungen/1665 Datenbansysteme/KE2_t.pickle",
            0.6744593381881714,
            15,
          ],
          [
            "Fakultaet Mathematik und informatik/Datenbanksysteme für neue Anwendungen/1671 Datenbanken I/KE2_t.pickle",
            0.6740410327911377,
            17,
          ],
          [
            "Fakultaet Mathematik und informatik/Datenbanksysteme für neue Anwendungen/1810 Übersetzerbau/KE5_t.pickle",
            0.6717040538787842,
            20,
          ],
        ],
        [
          [
            "Fakultaet Mathematik und informatik/Datenbanksysteme für neue Anwendungen/1665 Datenbansysteme/KE3_t.pickle",
            0.6748839616775513,
            15,
          ],
          [
            "Fakultaet Mathematik und informatik/Datenbanksysteme für neue Anwendungen/1664 Implementierungskonzepte für Datenbanksysteme/KE1_t.pickle",
            0.6729978322982788,
            14,
          ],
          [
            "Fakultaet Mathematik und informatik/Datenbanksysteme für neue Anwendungen/1675 Moving Objects Databases/KE7_t.pickle",
            0.663782000541687,
            19,
          ],
          [
            "Fakultaet Mathematik und informatik/Datenbanksysteme für neue Anwendungen/1662 Datenstrukturen II/KE3_t.pickle",
            0.6634088754653931,
            13,
          ],
          [
            "Fakultaet Mathematik und informatik/Mikro- und Nanophotonic/2337 Optische Nachrichtentechnik II/KE3_t.pickle",
            0.6597745418548584,
            40,
          ],
        ],
        [
          [
            "Fakultaet Mathematik und informatik/Analysis/1144 Analysis/KE4_t.pickle",
            0.7985951900482178,
            5,
          ],
          [
            "Fakultaet Mathematik und informatik/Datenbanksysteme für neue Anwendungen/1666 Datenbanken in Rechnernetzen/KE1_t.pickle",
            0.7982405424118042,
            16,
          ],
          [
            "Fakultaet Mathematik und informatik/Datenbanksysteme für neue Anwendungen/1666 Datenbanken in Rechnernetzen/KE4_t.pickle",
            0.7982274889945984,
            16,
          ],
          [
            "Fakultaet Mathematik und informatik/Datenbanksysteme für neue Anwendungen/1675 Moving Objects Databases/KE7_t.pickle",
            0.7962169647216797,
            19,
          ],
          [
            "Fakultaet Mathematik und informatik/Datenbanksysteme für neue Anwendungen/1810 Übersetzerbau/KE5_t.pickle",
            0.7949098348617554,
            20,
          ],
        ],
        [
          [
            "Fakultaet Mathematik und informatik/Stabsstelle Elektro- und Informationstechnik/2366 Robotertechnik II/KE7_t.pickle",
            0.7601817846298218,
            72,
          ],
          [
            "Fakultaet Mathematik und informatik/Mikro- und Nanophotonic/2337 Optische Nachrichtentechnik II/KE5_t.pickle",
            0.7598010301589966,
            40,
          ],
          [
            "Fakultaet Mathematik und informatik/Mikro- und Nanophotonic/2337 Optische Nachrichtentechnik II/KE3_t.pickle",
            0.759118914604187,
            40,
          ],
          [
            "Fakultaet Mathematik und informatik/Datenbanksysteme für neue Anwendungen/1810 Übersetzerbau/KE5_t.pickle",
            0.7589130401611328,
            20,
          ],
          [
            "Fakultaet Mathematik und informatik/Datenbanksysteme für neue Anwendungen/1666 Datenbanken in Rechnernetzen/KE1_t.pickle",
            0.7580037117004395,
            16,
          ],
        ],
        [
          [
            "Fakultaet Mathematik und informatik/Datenbanksysteme für neue Anwendungen/1664 Implementierungskonzepte für Datenbanksysteme/KE1_t.pickle",
            0.7496606111526489,
            14,
          ],
          [
            "Fakultaet Mathematik und informatik/Datenbanksysteme für neue Anwendungen/1662 Datenstrukturen II/KE2_t.pickle",
            0.7483944892883301,
            13,
          ],
          [
            "Fakultaet Mathematik und informatik/Datenbanksysteme für neue Anwendungen/1665 Datenbansysteme/KE1_t.pickle",
            0.7477037906646729,
            15,
          ],
          [
            "Fakultaet Mathematik und informatik/Datenbanksysteme für neue Anwendungen/1671 Datenbanken I/KE2_t.pickle",
            0.7459629774093628,
            17,
          ],
          [
            "Fakultaet Mathematik und informatik/Datenbanksysteme für neue Anwendungen/1666 Datenbanken in Rechnernetzen/KE6_t.pickle",
            0.7458089590072632,
            16,
          ],
        ],
      ],
      ke2: [],
      ke3: [],
      ke4: [],
    };

    this.getModulesbyCourse();
  },
  methods: {
    getRelatedUnits: function (text, selector) {
      let _this = this;

      $.ajax({
        method: "POST",
        url: this.serverPath,
        data: { text: text },
        dataType: "json",
        crossDomain: true,
        success: function (data) {
          //console.log(data)
          _this.courses = _this.clean(data);
        },
        error: function (e) {
          console.error(e);
        },
      });
    },

    getModulesbyCourse: function (text, selector) {
      let _this = this;

      $.ajax({
        method: "POST",
        url: M.cfg.wwwroot + "/mod/longpage/data/modules-by-course.json",
        data: { text: text },
        dataType: "json",
        crossDomain: true,
        success: function (data) {
          _this.modulesbyCourse = data;
          _this.renderRecommendations();
        },
        error: function (e) {
          console.error(e);
        },
      });
    },

    renderRecommendations: function () {
      const parentEl = document.querySelector(this.parantSelector);
      if (!parentEl) return;

      let numerOfHeadings = parentEl.querySelectorAll("h3").length;

      // add a dummy heading at the end.
      const dummyHeading = document.createElement("h3");
      dummyHeading.style.cssText = "display:inline; color:#fff; font-size:7;";
      dummyHeading.className = "dummy-heading";
      dummyHeading.textContent = ".";
      parentEl.appendChild(dummyHeading);

      // iterate over all headings and determine the text length and number of images
      for (var j = 0; j < numerOfHeadings; j++) {
        var data = this.recommendations["ke1"][j];
        if (data === undefined) {
          break;
        }

        const courseRecommendationList = document.createElement("span");
        courseRecommendationList.className = "dropdown-menu p-1";
        courseRecommendationList.setAttribute(
          "aria-labelledby",
          "dropdownMenuLink" + j,
        );

        var course = "";
        for (var i = 0; i < data.length; i++) {
          course = data[i][0].replace("_t.pickle", "").split("/");
          if (
            this.blacklist.indexOf(parseInt(course[2].substr(0, 4), 10)) !== -1
          ) {
            let url = "#";
            if (this.modulesbyCourse[course[2].substr(0, 4)] !== undefined) {
              url = this.modulesbyCourse[course[2].substr(0, 4)].url;
            }

            const link = document.createElement("a");
            link.className = "dropdown-item px-0 py-1";
            link.href = url;
            link.textContent =
              course[2] + ": " + course[3] + " (" + data[i][1].toFixed(2) + ")";
            courseRecommendationList.appendChild(link);
          }
        }

        const courseRecommendations = document.createElement("a");
        courseRecommendations.href = "#";
        courseRecommendations.className = "badge dropdown-toggle p-0";
        courseRecommendations.id = "dropdownMenuLink" + j;
        courseRecommendations.setAttribute("role", "button");
        courseRecommendations.setAttribute("data-toggle", "dropdown");
        courseRecommendations.setAttribute("aria-haspopup", "true");
        courseRecommendations.setAttribute("aria-expanded", "false");
        courseRecommendations.style.cssText =
          "background-color: transparent; font-size: 0.8em; color: #333333; font-weight:100;";
        courseRecommendations.textContent = "Semantisch ähnliche Kurseinheiten";

        const wrapper = document.createElement("span");
        wrapper.className = "dropdown d-inline show";
        wrapper.appendChild(courseRecommendations);
        wrapper.appendChild(courseRecommendationList);

        const heading = parentEl.querySelectorAll("h3")[j];
        heading.style.position = "relative";
        const wrapperParent = heading.closest(".wrapper");
        if (wrapperParent && wrapperParent.nextElementSibling) {
          const nextSpan = wrapperParent.nextElementSibling;
          const br = document.createElement("br");
          nextSpan.after(br);
          br.after(wrapper);

          const separator = document.createElement("span");
          separator.className = "mx-3 d-inline";
          separator.textContent = "|";
          wrapper.after(separator);
        }
      }
    },

    /**
     * Util function to convert a given JSON file
     * @param {} text
     * @param {*} selector
     */
    getCourseModules: function (text, selector) {
      let _this = this;

      $.ajax({
        method: "POST",
        url: M.cfg.wwwroot + "/mod/page/data/course-modules.json",
        data: { text: text },
        dataType: "json",
        crossDomain: true,
        success: function (data) {
          let arr = {};
          for (var i = 0; i < data.length; i++) {
            for (var j = 0; j < data[i].course_ids.length; j++) {
              arr[data[i].course_ids[j]] = {
                url: data[i].url,
                module: data[i].id,
                name: data[i].name,
              };
            }
          }
          //console.log('moddds', arr);
        },
        error: function (e) {
          console.error(e);
        },
      });
    },

    clean: function (data) {
      //console.log(data)
      let _this = this;
      let courses = [];
      let course = "";
      for (var i = 0; i < data.length; i++) {
        course = data[i][0].replace("_t.pickle", "").split("/");
        if (
          _this.blacklist.indexOf(parseInt(course[2].substr(0, 4), 10)) === -1
        ) {
          let c = {
            course: course[2],
            unit: course[3],
            confidence: data[i][1].toFixed(2),
          };
          courses.push(c);
        } else {
          //console.log('blacklisted ' + course[2], course[2].substr(0, 4));
        }
      }
      return courses;
    },
  },
};
</script>

<style></style>
