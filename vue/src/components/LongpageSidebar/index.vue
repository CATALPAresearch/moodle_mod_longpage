<template>
  <div
    :id="LONGPAGE_SIDEBAR_ID"
    class="row no-gutters vh-100-wo-nav max-w-80"
    :style="{
      width: tabs.length == 0 ? '0px' : sidebarWidth,
      'min-width': tabOpenedKey != undefined ? '400px' : '',
    }"
  >
    <div
      v-show="tabOpenedKey"
      :title="$t('sidebar.util.changeWidth')"
      :aria-label="$t('sidebar.util.changeWidth')"
      class="resize-handle--x h-100 col-auto border-left border-right"
    />
    <div
      v-show="tabOpenedKey"
      :id="LONGPAGE_SIDEBAR_TAB_CONTENT"
      class="col h-100"
    >
      <template v-for="tab in tabs" :key="tab.key">
        <component
          :is="tab.key"
          v-if="
            heavyComponentsReady || (tab.key !== 'quiz' && tab.key !== 'posts')
          "
          v-show="tab.key === tabOpenedKey"
          :id="tab.id"
          class="fade show h-100"
        />
      </template>
    </div>
    <div
      :id="LONGPAGE_SIDEBAR_TAB"
      class="col-auto border-left p-0 h-100 nav flex-column nav-pills"
      aria-orientation="vertical"
      v-if="tabs.length > 1"
    >
      <a
        v-for="tab in tabs"
        :key="tab.key"
        class="nav-link text-center"
        href="javascript:void(0)"
        :class="{
          active: tab.key === tabOpenedKey,
          'text-white': tab.key === tabOpenedKey,
          'text-dark': tab.key !== tabOpenedKey,
        }"
        @click="toggleTab(tab.key)"
      >
        <i
          :title="$t(`sidebar.tabMenu.titles.${tab.key}`)"
          :aria-label="$t(`sidebar.tabMenu.titles.${tab.key}`)"
          :class="tab.icon"
        />
        <span
          style="right: 5px; position: absolute; border-radius: 10rem"
          :title="tab.badgesTitle"
          class="badge badge-pill badge-warning"
          v-if="tab.badgesCount > 0"
          >{{ tab.badgesCount }}</span
        >
      </a>
    </div>
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
import {
  AnnotationType,
  LONGPAGE_APP_ID,
  SidebarTabKeys,
  SidebarEvents,
} from "@/util/constants";
import { GET, MUTATE } from "@/store/types";
import { mapGetters, mapMutations } from "vuex";
import Bookmarks from "@/components/LongpageSidebar/Bookmarks";
import { EventBus } from "@/lib/core/event-bus";
import Highlights from "@/components/LongpageSidebar/Highlights";
import Posts from "@/components/LongpageSidebar/Posts";
import debounce from "lodash/debounce";
import TableOfContents from "@/components/LongpageSidebar/TableOfContentsTab";
import Search from "@/components/LongpageSidebar/SearchTab";
import CourseRecommendation from "@/components/Features/CourseRecommendations";
import Quiz from "@/components/LongpageSidebar/Quiz";
import { lazyModules } from "@/store";

const LONGPAGE_SIDEBAR_ID = "longpage-sidebar";
const LONGPAGE_SIDEBAR_TAB = "longpage-sidebar-tab";
const LONGPAGE_SIDEBAR_TAB_CONTENT = "longpage-sidebar-tab-content";

const resizeData = {
  tracking: false,
  startWidth: undefined,
  startCursorScreenX: undefined,
  handleWidth: 10,
  resizeTarget: null,
  parentElement: null,
  maxWidth: undefined,
};

document.body.addEventListener("mousedown", (event) => {
  if (event.button !== 0 || !event.target.closest(".resize-handle--x")) return;

  const targetElement = document.getElementById(LONGPAGE_SIDEBAR_ID);
  resizeData.startWidth = targetElement.offsetWidth;
  resizeData.startCursorScreenX = event.screenX;
  resizeData.resizeTarget = targetElement;
  resizeData.parentElement = document.getElementById(LONGPAGE_APP_ID);
  resizeData.maxWidth =
    resizeData.parentElement.offsetWidth - resizeData.handleWidth;
  resizeData.tracking = true;
});

window.addEventListener(
  "mousemove",
  debounce((event) => {
    if (resizeData.tracking) {
      const cursorScreenXDelta = resizeData.startCursorScreenX - event.screenX;
      const newWidth = Math.min(
        resizeData.startWidth + cursorScreenXDelta,
        resizeData.maxWidth,
      );

      resizeData.resizeTarget.style.width = newWidth + "px";
    }
  }, 1),
);

window.addEventListener("mouseup", () => {
  if (resizeData.tracking) resizeData.tracking = false;
});

export default {
  name: "LongpageSidebar",
  components: {
    [SidebarTabKeys.BOOKMARKS]: Bookmarks,
    [SidebarTabKeys.HIGHLIGHTS]: Highlights,
    [SidebarTabKeys.POSTS]: Posts,
    [SidebarTabKeys.TOC]: TableOfContents,
    [SidebarTabKeys.SEARCH]: Search,
    [SidebarTabKeys.COURSE_RECOMMENDATIONS]: CourseRecommendation,
    [SidebarTabKeys.QUIZ]: Quiz,
  },
  data() {
    var tabs = [
      // {        // deactivated sidebar tab for now
      //   key: SidebarTabKeys.COURSE_RECOMMENDATIONS,
      //   id: "sidebar-tab-recomm",
      //   icon: ["fa", "fa-map", "fa-fw"],
      // },
    ];

    if (this.$store.getters.LONGPAGE_CONTEXT.showsearch) {
      tabs.push({
        key: SidebarTabKeys.SEARCH,
        id: "sidebar-tab-search",
        icon: ["fa", "fa-search", "fa-fw"],
      });
    }

    if (this.$store.getters.LONGPAGE_CONTEXT.showtableofcontents) {
      tabs.push({
        key: SidebarTabKeys.TOC,
        id: "sidebar-tab-table-of-contents",
        icon: ["fa", "fa-list", "fa-fw"],
      });
    }

    if (this.$store.getters.LONGPAGE_CONTEXT.showposts) {
      tabs.push({
        key: SidebarTabKeys.POSTS,
        id: "sidebar-tab-posts",
        icon: ["fa", "fa-comments-o", "fa-fw"],
      });
    }

    if (this.$store.getters.LONGPAGE_CONTEXT.showhighlights) {
      tabs.push({
        key: SidebarTabKeys.HIGHLIGHTS,
        id: "sidebar-tab-highlights",
        icon: ["fa", "fa-pencil", "fa-fw"],
      });
    }

    if (this.$store.getters.LONGPAGE_CONTEXT.showbookmarks) {
      tabs.push({
        key: SidebarTabKeys.BOOKMARKS,
        id: "sidebar-tab-bookmarks",
        icon: ["fa", "fa-bookmark-o", "fa-fw"],
      });
    }

    if (this.$store.getters.LONGPAGE_CONTEXT.showreadingcomprehension) {
      tabs.push({
        key: SidebarTabKeys.QUIZ,
        id: "sidebar-tab-quiz",
        icon: ["fa", "fa-dashboard", "fa-fw"],
      });
    }

    return {
      LONGPAGE_SIDEBAR_ID,
      LONGPAGE_SIDEBAR_TAB_CONTENT,
      SidebarEvents,
      tabs: tabs,
      sidebarWidth: "",
      heavyComponentsReady: false, // Defer Quiz/Posts for faster initial render
    };
  },
  computed: {
    ...mapGetters({ tabOpenedKey: GET.SIDEBAR_TAB_OPENED_KEY }),
  },
  mounted() {
    // Defer heavy components (Quiz has 2300+ lines) for faster initial render
    setTimeout(() => {
      this.heavyComponentsReady = true;
    }, 100);

    EventBus.subscribe("annotations-selected", ({ type }) => {
      switch (type) {
        case AnnotationType.HIGHLIGHT:
          this.toggleTab(SidebarTabKeys.HIGHLIGHTS);
          break;
        case AnnotationType.BOOKMARK:
          this.toggleTab(SidebarTabKeys.BOOKMARKS);
          break;
        case AnnotationType.POST:
          this.toggleTab(SidebarTabKeys.POSTS);
          break;
      }
    });
    EventBus.subscribe(SidebarEvents.TOGGLE_TABS, (type) => {
      this.toggleTab(type);
    });
    EventBus.subscribe(SidebarEvents.CHANGE_BADGES, (params) => {
      try {
        var tab = this.tabs.find((obj) => {
          return obj.key == params["type"];
        });
        tab.badgesCount = params["count"];
        tab.badgesTitle = params["title"];
        document
          .querySelectorAll(".badge.badge-pill.badge-warning")
          .forEach((el) => {
            // Dispose old tooltip if exists
            const tooltipInstance = bootstrap?.Tooltip?.getInstance(el);
            if (tooltipInstance) tooltipInstance.dispose();
            // Create new tooltip
            new bootstrap.Tooltip(el, { placement: "auto", html: true });
          });
      } catch {}
    });

    var tab = undefined;
    if (localStorage) {
      var tab = localStorage.getItem("sidebar-tab");
      if (tab === "undefined" || tab == null) {
        if (this.tabs.length == 1) {
          tab = this.tabs[0].key;
        }
      }
    }
    this.toggleTab(tab);

    this.$nextTick(function () {
      const observer = new MutationObserver(() => {
        const w = resizeData.resizeTarget?.offsetWidth;
        if (w && parseFloat(w) >= 400) {
          localStorage.setItem("sidebar-width", w + "px");
        }
      });

      const sidebarEl = document.getElementById("longpage-sidebar");
      if (sidebarEl) {
        observer.observe(sidebarEl, { attributes: true });
      }
    });
  },
  methods: {
    async toggleTab(tabKey) {
      if (tabKey === this.tabOpenedKey) {
        tabKey = undefined;
        this.sidebarWidth = "";
      } else if (tabKey != undefined && tabKey != null) {
        // Lazy load Vuex modules on-demand
        await this.loadModuleForTab(tabKey);

        var width;
        if (localStorage) {
          var w = localStorage.getItem("sidebar-width");
          if (w) {
            width = w;
          }
        }
        if (!width) {
          if (
            this.tabs.length == 1 &&
            this.$store.getters.LONGPAGE_CONTEXT.showreadingcomprehension
          ) {
            width = "50%";
          } else {
            width = "40%";
          }
        }
        this.sidebarWidth = width;
      }

      this.setTabOpened(tabKey);
      if (localStorage) {
        localStorage.setItem("sidebar-tab", tabKey);
      }

      EventBus.publish(SidebarEvents.CHANGE_BADGES, { type: tabKey, count: 0 });
    },
    async loadModuleForTab(tabKey) {
      // Map tabs to their required modules
      const tabModuleMap = {
        [SidebarTabKeys.BOOKMARKS]: ["annotation"],
        [SidebarTabKeys.HIGHLIGHTS]: ["annotation"],
        [SidebarTabKeys.POSTS]: ["annotation", "post"],
        [SidebarTabKeys.QUIZ]: ["questionBank"],
      };

      const requiredModules = tabModuleMap[tabKey];
      if (!requiredModules) return;

      // Load all required modules for this tab
      for (const moduleName of requiredModules) {
        // Check if module already registered
        if (this.$store.hasModule(moduleName)) continue;

        try {
          // Map module name to lazy loader
          const moduleLoaders = {
            annotation: "AnnotationModule",
            post: "PostModule",
            questionBank: "QuestionBankModule",
          };

          const loaderName = moduleLoaders[moduleName];
          const module = await lazyModules[loaderName]();
          this.$store.registerModule(moduleName, module.default);
        } catch (error) {
          console.error(`Failed to load ${moduleName}:`, error);
        }
      }
    },
    ...mapMutations({ setTabOpened: MUTATE.RESET_SIDEBAR_TAB_OPENED_KEY }),
  },
};
</script>

<style scoped lang="scss">
#longpage-sidebar .nav-link:hover {
  z-index: 1;
  color: #495057;
  text-decoration: none;
  background-color: #f8f9fa;
}

#longpage-sidebar .nav-link.active,
#longpage-sidebar .nav-link:focus {
  background-color: #0f6cbf;
  color: #fff !important;
}

.max-w-80 {
  max-width: 80%;
}

.w-xs-px {
  width: 576px;
}

.min-w-300-px {
  min-width: 300px;
}

$handle-size: 10px;
$handle-thickness: 1px;
$handle-distance: 2px;

.resize-handle--x {
  position: relative;
  box-sizing: border-box;
  width: 3px;
  cursor: ew-resize;

  -webkit-touch-callout: none;
  -webkit-user-select: none;
  -khtml-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;

  &:before {
    content: "";
    position: absolute;
    z-index: 1;
    top: 50%;
    right: 100%;
    height: $handle-size;
    width: $handle-distance;
    margin-top: calc(calc($handle-size) / -2);
    border-left-color: black;
    border-left-width: $handle-thickness;
    border-left-style: solid;
  }
  &:after {
    content: "";
    position: absolute;
    z-index: 1;
    top: 50%;
    left: 100%;
    height: $handle-size;
    width: $handle-distance;
    margin-top: calc(calc($handle-size) / -2);
    border-right-color: black;
    border-right-width: $handle-thickness;
    border-right-style: solid;
  }
}
</style>
