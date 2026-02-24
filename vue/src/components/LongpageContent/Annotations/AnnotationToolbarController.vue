<template>
  <annotation-toolbar
    ref="annotationToolbarPopover"
    v-bind="annotationToolbarPopoverProps"
    class="longpage-highlights-always-on"
    @bookmark-clicked="createBookmark"
    @post-clicked="createPost"
    @highlight-clicked="createHighlight"
  />
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
import { ACT, GET, MUTATE } from "@/store/types";
import {
  AnnotationType,
  ArrowDirection,
  LONGPAGE_APP_ID,
  LONGPAGE_CONTENT_ID,
  LONGPAGE_MAIN_ID,
  SidebarTabKeys,
  LONGPAGE_APP_CONTAINER_ID,
} from "@/util/constants";
import { AnnotationTarget } from "@/types/annotation-target";
import AnnotationToolbar from "./AnnotationToolbar.vue";
import { AnnotationToolbarPopoverPositioner } from "@/lib/annotation/annotation-toolbar-popover-positioner"; // Interesting for architecture
import { SelectionListener } from "@/lib/annotation/selection-listening"; // Interesting for architecture
import { describe } from "@/lib/annotation/hypothesis/anchoring/html"; // Interesting for architecture
import { Anchoring } from "@/lib/annotation/anchoring"; // Interesting for architecture
import { mapActions, mapGetters, mapMutations } from "vuex";
import { setHighlightsVisible } from "@/lib/annotation/highlighting";
import throttle from "lodash/throttle";
import * as rangeUtil from "@/lib/annotation/hypothesis/range-util";
import { lazyModules } from "@/store";

export default {
  name: "AnnotationToolbarController",
  components: {
    AnnotationToolbar,
  },
  data() {
    return {
      anchoring: null,
      annotationToolbarPopoverProps: {
        arrowDirection: ArrowDirection.UP,
        highlightingOptions: [
          "bg-yellow",
          "bg-green",
          "bg-orange",
          "bg-pink",
          "underline",
          "underline-red",
        ],
        left: 0,
        showDelete: false,
        top: 0,
        visible: false,
        zIndex: undefined,
      },
      selectionListener: new SelectionListener(),
      selectedRanges: [],
      targetRoot: null,
      contentContainer: null, // used in constriction of annotationtoolbar
    };
  },
  computed: {
    ...mapGetters([GET.NEW_ANNOTATION]),
    ...mapGetters({ context: GET.LONGPAGE_CONTEXT }),
    annotationToolbarPopover() {
      return this.$refs.annotationToolbarPopover;
    },
    annotationToolbarPopoverPositioner() {
      return new AnnotationToolbarPopoverPositioner(
        document.body,
        this.annotationToolbarPopover.height.bind(
          this.annotationToolbarPopover,
        ),
        this.annotationToolbarPopover.width.bind(this.annotationToolbarPopover),
        this.annotationToolbarPopover.arrowHeight.bind(
          this.annotationToolbarPopover,
        ),
      );
    },
  },
  mounted() {
    this.$nextTick(() => {
      this.targetRoot = document.getElementById(LONGPAGE_CONTENT_ID);
      this.contentContainer = document.getElementById(LONGPAGE_MAIN_ID);
      this.selectionListener.subscribe(
        this.onSelection.bind(this),
        this.onClearSelection.bind(this),
      );
      this.anchoring = new Anchoring(this.targetRoot, this.$store);
      if (this.context.showhighlights) {
        setHighlightsVisible(document.getElementById(LONGPAGE_APP_ID), true);
      }

      // Ensure AnnotationModule is loaded before fetching annotations
      this.loadAnnotationModule().then(() => {
        this.$store.dispatch("annotation/" + ACT.FETCH_ANNOTATIONS);
      });

      // this part adds dynamic positioning to the AnnotationToolbar:
      //  sticks the toolbar to the selected text when scrolling
      //  rangeUtil is part of hypothesis.js lib, used to position the Toolbar when a selection is made
      document.getElementById(LONGPAGE_MAIN_ID).addEventListener(
        "scroll",
        throttle(
          () => {
            if (this.annotationToolbarPopoverProps.visible == true) {
              const selection = /** @type {Selection} */ (
                document.getSelection()
              );
              const isBackwards = rangeUtil.isSelectionBackwards(selection);
              const focusRect = rangeUtil.selectionFocusRect(selection);
              this.setPositionProps(
                this.annotationToolbarPopoverPositioner.calculatePositionProps(
                  focusRect,
                  isBackwards,
                ),
              );
            }
          },
          100, // throttled the calling frequency to 100ms, can be adjusted further for better UX
          { leading: false },
        ),
      );
    });
  },
  beforeUnmount() {
    this.anchoring.unsubscribe();
    this.selectionListener.unsubscribe();
  },
  methods: {
    ...mapActions("annotation", [ACT.CREATE_ANNOTATION]),
    ...mapMutations([MUTATE.RESET_SIDEBAR_TAB_OPENED_KEY]),
    async loadAnnotationModule() {
      // Ensure AnnotationModule is loaded
      if (!this.$store.hasModule("annotation")) {
        try {
          const module = await lazyModules.AnnotationModule();
          this.$store.registerModule("annotation", module.default);
        } catch (error) {
          console.error("Failed to load AnnotationModule:", error);
        }
      }
    },
    async createBookmark() {
      await this.loadAnnotationModule();
      this[ACT.CREATE_ANNOTATION]({
        target: await this.getAnnotationTarget(),
        type: AnnotationType.BOOKMARK,
      });
    },
    async createPost() {
      await this.loadAnnotationModule();
      this[MUTATE.RESET_SIDEBAR_TAB_OPENED_KEY](SidebarTabKeys.POSTS);
      this.$nextTick(async () => {
        this[ACT.CREATE_ANNOTATION]({
          target: await this.getAnnotationTarget(),
          type: AnnotationType.POST,
        });
      });
    },
    async createHighlight(styleClass) {
      await this.loadAnnotationModule();
      this[ACT.CREATE_ANNOTATION]({
        target: await this.getAnnotationTarget(styleClass),
        styleClass,
        type: AnnotationType.HIGHLIGHT,
      });
    },
    async getAnnotationTarget(styleClass) {
      const selectorsInSelectors = await Promise.all(
        this.selectedRanges.map(this.getSelectors),
      );
      this.cleanUpAnnotationText(selectorsInSelectors, this.selectedRanges[0]);
      return new AnnotationTarget({
        selectors: selectorsInSelectors[0],
        styleClass,
      });
    },
    getSelectors(range) {
      return describe(this.targetRoot, range);
    },
    // workaround method to clean up double Mathjax elements
    // why? Mathjax filter creates 2 elements: span and script element
    // hypothesis.js (for creating highlights and annotations) extracts all available text, creating duplicated text in annotation
    // example: (in html)...\(n-1\)... -> annotation then contains ...n-1n-1...
    cleanUpAnnotationText(selectors, range) {
      //console.log(document.getSelection().toString());
      let mathjaxcontents = [];
      let tempElement;
      let preAncestorStartContainer, preAncestorEndContainer;
      let walkingSiblings;
      let replacedExact = 0;
      //console.log(range);
      preAncestorStartContainer = range.startContainer; //.parentElement
      preAncestorEndContainer = range.endContainer;
      let i = 0;

      // range.commonAncestorContainer can sometimes contain all contents of longpage or is otherwise really large
      // so we try to find all elements between start and end container and look for class "MathJax_Preview"
      // startcontainer not already common ancestor ?
      if (preAncestorStartContainer !== range.commonAncestorContainer) {
        // find beginning edge of common ancestor
        while (
          preAncestorStartContainer.parentElement !==
          range.commonAncestorContainer
        ) {
          preAncestorStartContainer = preAncestorStartContainer.parentElement;
          i++;
          if (i > 10) break;
        }
      } else {
        preAncestorStartContainer = range.commonAncestorContainer;
      }

      i = 0;
      if (preAncestorEndContainer !== range.commonAncestorContainer) {
        // endcontainer not already common ancestor ?
        while (
          preAncestorEndContainer.parentElement !==
          range.commonAncestorContainer
        ) {
          preAncestorEndContainer = preAncestorEndContainer.parentElement;
          i++;
          if (i > 10) break;
        }
      } else {
        preAncestorEndContainer = range.commonAncestorContainer;
      }

      walkingSiblings = preAncestorStartContainer;
      i = 0;

      //console.dir(preAncestorStartContainer);
      //console.dir(preAncestorEndContainer);

      do {
        // nodeType 3 is a textnode
        if (walkingSiblings.nodeType != 3) {
          tempElement =
            walkingSiblings.getElementsByClassName("MathJax_Preview");

          // do an extra check and reset if walkingSiblings didnt have an element with that class
          if (tempElement.length != 0) {
            mathjaxcontents.push(tempElement);
          }
        }
        // have we already reached the end ?
        if (walkingSiblings == preAncestorEndContainer) {
          break;
        }
        ////console.log(walkingSiblings);
        // nextElementSibling: ignores non-element nodes (text or comment nodes)
        //  text nodes dont have other typical element methods, like getElementsbyClassName
        // UPDATE: changed it back to nextSibling because sometimes preAncestorEndContainer is a text node
        //  and thus we would miss our exit condition
        if (walkingSiblings.nextSibling) {
          walkingSiblings = walkingSiblings.nextSibling;
        }

        tempElement = 0;
        i++;
        if (i > 50) {
          //console.log("emergency break");
          break;
        }
        // end of tree
      } while (walkingSiblings.nextSibling != null);

      let patternfound = 0;
      //console.log(mathjaxcontents);

      // we have collected mathjax elements, now we extract their text contents and look for their appearance in
      //  selectors[0][2].exact, which contains the default extracted strings from which we remove the double mathjax

      // mathjaxcontents is an array containing HTMLCollections which themselves are also arrays...or objects
      for (let i = 0; i < mathjaxcontents.length; i++) {
        for (let j = 0; j < mathjaxcontents[i].length; j++) {
          //selectors[0][0].endOffset += 10;
          //selectors[0][1].end += 10;
          //console.log(mathjaxcontents[i][j].innerText + " " +
          //mathjaxcontents[i][j].nextSibling.innerText);
          if (
            mathjaxcontents[i][j].nextSibling &&
            mathjaxcontents[i][j].nextSibling.nodeName == "SCRIPT"
          ) {
          }
          if (mathjaxcontents[i][j].innerText.length > 1) {
            if (
              (patternfound =
                selectors[0][2].exact.indexOf(
                  mathjaxcontents[i][j].innerText +
                    mathjaxcontents[i][j].nextSibling.innerText,
                ) != -1)
            ) {
              selectors[0][2].exact = selectors[0][2].exact.replace(
                mathjaxcontents[i][j].innerText +
                  mathjaxcontents[i][j].nextSibling.innerText,
                mathjaxcontents[i][j].innerText, // + this.fillerspace(mathjaxcontents[i][j].nextSibling.innerText.length)
              );
            }

            // special case for mathjax with length = 1
          } else {
            if (
              (patternfound =
                selectors[0][2].exact.indexOf(
                  mathjaxcontents[i][j].innerText +
                    mathjaxcontents[i][j].nextSibling.innerText,
                ) != -1)
            ) {
              selectors[0][2].exact = selectors[0][2].exact.replace(
                mathjaxcontents[i][j].innerText +
                  mathjaxcontents[i][j].innerText,
                mathjaxcontents[i][j].innerText, //+ this.fillerspace(mathjaxcontents[i][j].nextSibling.innerText.length)
              );
            }
          }
          if (patternfound != -1) {
            //console.log(mathjaxcontents[i][j].nextSibling.innerText.length);
            // selectors[0][2].exact += this.fillerspace(
            //   mathjaxcontents[i][j].nextSibling.innerText.length
            // );
          }
          patternfound = 0;
        }
      }
    },
    fillerspace(size) {
      let fillerspace = " ";
      let returnstring = "";
      for (let i = 0; i < size; i++) {
        returnstring += fillerspace;
      }
      //console.log(returnstring.length);
      return returnstring;
    },
    onClearSelection() {
      this.selectedRanges = [];
      this.annotationToolbarPopoverProps.visible = false;
    },
    isInsideTarget(range) {
      return this.targetRoot.contains(range.commonAncestorContainer);
    },
    onSelection(range, focusRect, isBackwards) {
      if (!focusRect || !this.isInsideTarget(range)) {
        this.onClearSelection();
        return;
      }
      this.selectedRanges = [range];
      this.setPositionProps(
        this.annotationToolbarPopoverPositioner.calculatePositionProps(
          focusRect,
          isBackwards,
        ),
      );
      this.annotationToolbarPopoverProps.visible = true;
    },
    /**
     * Since AnnotationToolbar now stays with the selected text when scrolling,
     * we need these two functions to constrict AnnotationToolbar to the boundaries of the content window
     */
    offsetTopLimit() {
      let top = this.contentContainer.getBoundingClientRect().top;
      return top > 50 ? top : 50; // 50 is the height of the moodle navbar
    },
    offsetBottomLimit() {
      return this.contentContainer.getBoundingClientRect().bottom - 37; // 37 is the height of the AnnotationToolbar
    },
    setPositionProps({ arrowDirection, left, top, zIndex }) {
      this.annotationToolbarPopoverProps.arrowDirection = arrowDirection;
      this.annotationToolbarPopoverProps.left = left;
      if (top < this.offsetTopLimit()) top = this.offsetTopLimit();
      if (top > this.offsetBottomLimit()) top = this.offsetBottomLimit();
      this.annotationToolbarPopoverProps.top = top;
      this.annotationToolbarPopoverProps.zIndex = zIndex;
    },
  },
};
</script>
