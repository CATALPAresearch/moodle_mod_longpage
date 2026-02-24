<template>
  <div ref="postIndicators">
    <annotation-indicator
      v-for="(annotations, top) in indicatorTopToAnnotationsMap"
      :key="`${top}-${annotations.length}-${annotations.reduce((sum, annotation) => sum + (annotation.hasBody ? 1 : 0))}`"
      :annotations="annotations"
      :annotation-types="annotationTypesIndicated"
      :top="Number(top)"
    />
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
import AnnotationIndicator from "@/components/LongpageContent/Annotations/AnnotationIndicator";
import emitter from "tiny-emitter/instance";
import { AnnotationType, LONGPAGE_CONTENT_ID } from "@/util/constants";
import { ResizeObserver } from "@juggle/resize-observer";
import { mapGetters } from "vuex";
import { GET } from "@/store/types";

export default {
  name: "AnnotationIndicatorSidebar",
  components: {
    AnnotationIndicator,
  },
  data() {
    var types = [];
    if (this.$store.getters.LONGPAGE_CONTEXT.showposts) {
      types.push(AnnotationType.POST);
    }

    if (this.$store.getters.LONGPAGE_CONTEXT.showbookmarks) {
      types.push(AnnotationType.BOOKMARK);
    }

    return {
      anchors: [],
      annotationTypesIndicated: types,
      indicatorTopToAnnotationsMap: {},
      parentElement: null,
    };
  },
  computed: {
    ...mapGetters({ context: GET.LONGPAGE_CONTEXT }),
  },
  mounted() {
    this.parentElement = this.$refs.postIndicators;
    this.updateOnAnchorUpdates();
    this.updateOnResize(document.getElementById(LONGPAGE_CONTENT_ID));
  },
  methods: {
    getIndicatorTop(highlights, offset = 0) {
      return (
        highlights
          .map((highlight) => highlight.getBoundingClientRect().top)
          .reduce((minTop, top) => Math.min(minTop, top)) - offset
      );
    },
    getTopOffset() {
      return this.parentElement.getBoundingClientRect().top;
    },
    updateIndicatorTopToAnnotationsMap(anchors, map = {}) {
      return anchors
        .filter((anchor) =>
          this.annotationTypesIndicated.includes(anchor.annotation.type),
        )
        .map((anchor) => [
          this.getIndicatorTop(anchor.highlights, this.getTopOffset()),
          anchor.annotation,
        ])
        .reduce((result, [top, annotation]) => {
          result[top] = result[top] || [];
          result[top].push(annotation);
          return result;
        }, map);
    },
    updateOnAnchorUpdates() {
      emitter.on("anchors-updated", (anchors) => {
        this.anchors = anchors;
        this.indicatorTopToAnnotationsMap =
          this.updateIndicatorTopToAnnotationsMap(anchors);
      });
    },
    updateOnResize(container) {
      const ro = new ResizeObserver(() => {
        this.indicatorTopToAnnotationsMap =
          this.updateIndicatorTopToAnnotationsMap(this.anchors);
      });
      ro.observe(container);
    },
  },
};
</script>
