<template>
  <div>
    <div
      ref="postTopIndicator"
      data-position="top"
      :data-post-id="post.id"
      :data-thread-id="post.threadId"
    />
    <div ref="postContent">
      {{ post.highlighted.content || post.content }}
    </div>
    <div
      ref="postBottomIndicator"
      data-position="bottom"
      :data-post-id="post.id"
      :data-thread-id="post.threadId"
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
import { applyMathjaxFilterToNodes } from "@/util/moodle";
import { Post } from "@/types/post";
import { PostReadingStatusEstimator } from "@/lib/annotation/post-reading-status-estimator";
import { THREAD_CONTAINER_ID } from "@/util/constants";

export default {
  name: "PostContent",
  props: {
    post: { type: Post, required: true },
  },
  data() {
    return {
      stopEstimating: undefined,
    };
  },
  watch: {
    "post.content": {
      handler() {
        this.$nextTick(() => {
          applyMathjaxFilterToNodes(this.$refs.postContent);
        });
      },
      immediate: true,
    },
    "post.readByUser": {
      handler(readByUser) {
        // TODO You can't mark something as unread
        if (!readByUser) {
          let scrolledOut = false;
          new IntersectionObserver(
            (entries, observer) => {
              if (scrolledOut) {
                this.stopEstimating =
                  PostReadingStatusEstimator.startEstimating(
                    this.$store, // TODO Init once with $store instead of multiple times
                    this.$refs.postTopIndicator,
                    this.$refs.postBottomIndicator,
                  );
                observer.disconnect();
              }
              scrolledOut = true;
            },
            { root: document.getElementById(THREAD_CONTAINER_ID) },
          ).observe(this.$refs.postContent);
        } else if (this.stopEstimating) this.stopEstimating();
      },
    },
  },
  mounted() {
    if (!this.post.readByUser) {
      this.stopEstimating = PostReadingStatusEstimator.startEstimating(
        this.$store,
        this.$refs.postTopIndicator,
        this.$refs.postBottomIndicator,
      );
    }
  },
  unmounted() {
    if (this.stopEstimating) this.stopEstimating();
  },
};
</script>
