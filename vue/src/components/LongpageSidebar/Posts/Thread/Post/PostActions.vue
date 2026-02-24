<template>
  <div v-if="show" class="post-actions">
    <div class="float-left">
      <a
        href="javascript:void(0)"
        role="button"
        :class="{ disabled: userIsCreator }"
        @click="togglePostLike"
      >
        <i
          class="icon fa fa-fw mr-1"
          :title="
            userIsCreator
              ? undefined
              : $t(
                  postIntern.likedByUser
                    ? 'post.action.rmLike'
                    : 'post.action.like',
                )
          "
          :aria-label="
            userIsCreator
              ? undefined
              : $t(
                  postIntern.likedByUser
                    ? 'post.action.rmLike'
                    : 'post.action.like',
                )
          "
          :class="[postIntern.likedByUser ? 'fa-thumbs-up' : 'fa-thumbs-o-up']"
        />
      </a>
      <span
        class="mr-1"
        :class="{
          'font-weight-bold': postIntern.likedByUser,
          'text-primary': postIntern.likedByUser,
        }"
        >{{ postIntern.likesCount }}</span
      >
    </div>
    <div
      v-if="post === thread.root && post.isPublic"
      class="float-left ml-3"
      role="button"
      @click="toggleRepliesOrReply"
    >
      <a href="javascript:void(0)" class="text-dark">
        <i
          :title="$t('post.action.answer')"
          :aria-label="$t('post.action.answer')"
          class="icon fa fa-comment-o fa-fw mr-1"
        />
      </a>
      <span v-if="thread.replyCount">{{ thread.replyCount }}</span>
    </div>
    <div class="float-left ml-3">
      <a
        href="javascript:void(0)"
        class="text-dark"
        role="button"
        @click="toggleBookmark"
      >
        <i
          :title="
            post.bookmarkedByUser
              ? $t('post.action.rmMarkWithStar')
              : $t('post.action.markWithStar')
          "
          :aria-label="
            post.bookmarkedByUser
              ? $t('post.action.rmMarkWithStar')
              : $t('post.action.markWithStar')
          "
          class="icon fa fa-fw m-0"
          :class="[post.bookmarkedByUser ? 'fa-star' : 'fa-star-o']"
        />
      </a>
    </div>
    <div
      v-if="thread.root.id === post.id && thread.isPublic"
      class="float-left ml-3"
    >
      <a
        href="javascript:void(0)"
        class="text-dark"
        role="button"
        @click="toggleThreadSubscription"
      >
        <i
          :title="
            thread.subscribedToByUser
              ? $t('post.action.unsubscribe')
              : $t('post.action.subscribe')
          "
          :aria-label="
            thread.subscribedToByUser
              ? $t('post.action.unsubscribe')
              : $t('post.action.subscribe')
          "
          class="icon fa fa-fw m-0"
          :class="[thread.subscribedToByUser ? 'fa-bell' : 'fa-bell-o']"
        />
      </a>
    </div>
    <div
      v-if="dropdownMenuItems && dropdownMenuItems.length"
      class="dropdown"
      style="float: right"
    >
      <a
        href="javascript:void(0)"
        class="text-dark"
        role="button"
        data-toggle="dropdown"
        aria-haspopup="true"
        aria-expanded="false"
      >
        <i
          :title="$t('post.action.more')"
          :aria-label="$t('post.action.more')"
          class="icon fa fa-ellipsis-h fa-fw m-0"
        />
      </a>
      <div class="dropdown-menu">
        <a
          v-for="item in dropdownMenuItems"
          :key="item.text"
          class="dropdown-item"
          href="javascript:void(0)"
          role="button"
          @click="item.handler"
        >
          <i
            v-if="item.iconClasses && item.iconClasses.length"
            :class="item.iconClasses"
            class="icon"
          />
          {{ item.text }}
        </a>
      </div>
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
import { ACT, GET } from "@/store/types";
import { mapActions, mapGetters } from "vuex";
import { Post } from "@/types/post";
import { Thread } from "@/types/thread";
import cloneDeep from "lodash/cloneDeep";

export default {
  name: "PostActions",
  props: {
    post: { type: Post, required: true },
    thread: { type: Thread, required: true },
    show: { type: Boolean, default: true },
  },
  emits: ["edit-clicked", "toggle-replies"],
  computed: {
    ...mapGetters([GET.USER, GET.LONGPAGE_CONTEXT]),
    dropdownMenuItems() {
      const items = [];
      // items.push({           //diagnostic: prints post to console
      //       iconClasses: ["fa", "fa-trash", "fa-fw"],
      //       handler: this.info,
      //       text: "trashinfo",
      //     });
      if (this.post.id !== this.thread.root.id) {
        if (this.thread.subscribedToByUser) {
          items.push({
            iconClasses: ["fa", "fa-bell-o", "fa-fw"],
            handler: this.toggleThreadSubscription,
            text: this.$i18n.t("post.action.unsubscribe"),
          });
        } else {
          items.push({
            iconClasses: ["fa", "fa-bell", "fa-fw"],
            handler: this.toggleThreadSubscription,
            text: this.$i18n.t("post.action.subscribe"),
          });
        }
      }
      if (!this.$store.state.UserModule.userCanMod) {
        if (this.userIsCreator && !this.postIsLocked) {
          items.push({
            iconClasses: ["fa", "fa-pencil", "fa-fw"],
            handler: this.openEditor,
            text: this.$i18n.t("post.action.edit"),
          });

          if (this.postIsLastPostInThread) {
            items.push({
              iconClasses: ["fa", "fa-trash", "fa-fw"],
              handler: this.deletePost,
              text: this.$i18n.t("post.action.delete"),
            });
          }
        }
      }
      if (this.$store.state.UserModule.userCanMod) {
        if (!this.userIsCreator && this.post.isPublic) {
          items.push({
            iconClasses: ["fa", "fa-trash", "fa-fw"],
            handler: this.censorPost,
            text: this.$i18n.t("post.action.admindelete"),
          });
        } else {
          // } else if (!this.post.isPublic) {
          // case for private annotation+post written by unenrolled admin user
          items.push({
            iconClasses: ["fa", "fa-pencil", "fa-fw"],
            handler: this.openEditor,
            text: this.$i18n.t("post.action.edit"),
          });
          items.push({
            iconClasses: ["fa", "fa-trash", "fa-fw"],
            handler: this.deletePost,
            text: this.$i18n.t("post.action.delete"),
          });
        }
      }

      if (
        !this.userIsCreator &&
        !this.post.likedByUser &&
        !this.post.bookmarkedByUser &&
        (this.post !== this.thread.root || !this.thread.subscribedToByUser) &&
        !this.thread
          .getPostsAfter(this.post.id)
          .find((post) => post.creatorId === this.userId)
      ) {
        items.push({
          iconClasses: [
            "fa",
            this.post.readByUser ? "fa-eye-slash" : "fa-eye",
            "fa-fw",
          ],
          handler: this.togglePostReading,
          text: this.$i18n.t(
            `post.action.${
              this.post.readByUser ? "markAsUnread" : "markAsRead"
            }`,
          ),
        });
      }

      items.push({
        iconClasses: ["fa", "fa-link", "fa-fw"],
        handler: this.copyLink,
        text: this.$i18n.t("post.action.copyLink"),
      });

      return items;
    },
    postIntern() {
      return this.post;
    },
    postIsLastPostInThread() {
      return this.thread.lastPost.id === this.post.id;
    },
    postIsLocked() {
      if (this.post.islocked === true) {
        return true;
      }
      return false; // islocked is false or undefined
    },
    userId() {
      return this[GET.USER]() && this[GET.USER]().id;
    },
    userIsCreator() {
      if (
        typeof this.userId === "undefined" ||
        typeof this.post.creatorId === "undefined"
      ) {
        // impossible to determine if user is creator
        return false; // test needed because userIsCreator() = true if
      } // both Ids are undefined
      return this.userId === this.post.creatorId;
    },
  },
  mounted: function () {},
  methods: {
    ...mapActions("post", [
      ACT.CREATE_POST,
      ACT.DELETE_POST,
      ACT.UPDATE_POST,
      ACT.TOGGLE_POST_BOOKMARK,
      ACT.TOGGLE_POST_LIKE,
      ACT.TOGGLE_POST_READING,
      ACT.TOGGLE_THREAD_SUBSCRIPTION,
    ]),
    deletePost() {
      this[ACT.DELETE_POST](this.post);
    },
    info() {
      console.log(this.post);
      console.log(this.thread);
    },
    censorPost() {
      let postUpdate = cloneDeep(this.post);
      postUpdate.content = this.$i18n.t("post.action.censored");
      postUpdate.islocked = true;
      //postUpdate.creatorid = this.userId;
      this[ACT.UPDATE_POST](postUpdate);
    },
    openEditor() {
      this.$emit("edit-clicked");
    },
    toggleBookmark() {
      this[ACT.TOGGLE_POST_BOOKMARK]({
        postId: this.post.id,
        threadId: this.post.threadId,
      });
    },
    togglePostLike() {
      if (!this.userIsCreator)
        this[ACT.TOGGLE_POST_LIKE]({
          postId: this.post.id,
          threadId: this.post.threadId,
        });
    },
    togglePostReading() {
      this[ACT.TOGGLE_POST_READING]({
        postId: this.post.id,
        threadId: this.post.threadId,
      });
    },
    async toggleRepliesOrReply() {
      if (!this.thread.replyCount)
        await this[ACT.CREATE_POST]({ threadId: this.thread.id });
      this.$emit("toggle-replies");
    },
    toggleThreadSubscription() {
      this[ACT.TOGGLE_THREAD_SUBSCRIPTION](this.post.threadId);
    },
    copyLink() {
      navigator.clipboard.writeText(
        window.location.origin +
          window.location.pathname +
          window.location.search +
          "#post-" +
          this.post.id,
      );
      alert(this.$i18n.t("post.action.copyLinkMessage"));
    },
  },
};
</script>
