<template>
  <div :id="id" class="thread border-secondary card d-block">
    <post
      :post="thread.root"
      :thread="thread"
      class="card-body text-dark thread-root"
      @toggle-replies="toggleReplies"
    />
    <div v-if="showReplies && thread.replies.length" class="card-footer">
      <post
        v-for="reply in thread.replies"
        :key="reply.id"
        class="my-3"
        :thread="thread"
        :post="reply"
      />
      <reply-form
        v-if="thread.lastReply.created"
        class="my-3"
        :thread="thread"
      />
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
import { SCROLL_INTO_VIEW_OPTIONS, SidebarTabKeys } from "@/util/constants";
import { EventBus } from "@/lib/core/event-bus";
import { getDOMIdOfThread } from "@/util/dom/annotation";
import { MUTATE } from "@/store/types";
import { mapMutations } from "vuex";
import Post from "./Post";
import ReplyForm from "@/components/LongpageSidebar/Posts/Thread/ReplyForm";
import { Thread } from "@/types/thread";
import scrollIntoView from "scroll-into-view-if-needed";

export default {
  name: "Thread",
  components: {
    ReplyForm,
    Post,
  },
  props: {
    thread: { type: Thread, required: true },
  },
  data() {
    return {
      showReplies: false,
    };
  },
  computed: {
    id() {
      return getDOMIdOfThread(this.thread.id);
    },
  },
  mounted() {
    EventBus.subscribe("post-selected-by-url-hash", ({ postId, postDOMId }) => {
      if (this.thread.posts.findIndex((post) => post.id === postId) < 0) return;

      this.setTabOpened(SidebarTabKeys.POSTS);
      this.showReplies = true;
      this.$nextTick(() => {
        scrollIntoView(document.getElementById(postDOMId), {
          ...SCROLL_INTO_VIEW_OPTIONS,
          boundary: document.getElementById("sidebar-tab-posts"),
        });
      });
    });

    EventBus.subscribe(
      "thread-selected-by-url-hash",
      ({ threadId, threadDOMId }) => {
        if (this.thread.id !== threadId) return;

        this.setTabOpened(SidebarTabKeys.POSTS);
        this.showReplies = true;
        this.$nextTick(() => {
          scrollIntoView(document.getElementById(threadDOMId), {
            ...SCROLL_INTO_VIEW_OPTIONS,
            boundary: document.getElementById("sidebar-tab-posts"),
          });
        });
      },
    );

    this.showReplies = this.thread.includesUnreadReply;
  },
  methods: {
    ...mapMutations([MUTATE.REMOVE_POSTS_FROM_THREAD]),
    ...mapMutations({ setTabOpened: MUTATE.RESET_SIDEBAR_TAB_OPENED_KEY }),
    toggleReplies() {
      if (
        this.showReplies &&
        !this.thread.lastReply.created &&
        !this.thread.lastReply.content.trim()
      ) {
        this[MUTATE.REMOVE_POSTS_FROM_THREAD]({
          threadId: this.thread.id,
          posts: [this.thread.lastReply],
        });
      }
      this.showReplies = !this.showReplies;
    },
  },
};
</script>
