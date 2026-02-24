<template>
  <div v-if="show" class="text-right">
    <post-form-input
      v-model="postUpdate.content"
      @cancel="cancel"
      @submit="createOrUpdatePost"
    />
    <button
      type="button"
      class="btn btn-sm btn-secondary ml-2 my-2"
      @click="cancel"
    >
      <i class="icon fa fa-fw fa-times m-0" />
      {{ $t("post.form.action.cancel") }}
    </button>
    <div class="btn-group ml-2 my-2" role="group">
      <button
        type="button"
        class="btn btn-primary btn-sm"
        @click="createOrUpdatePost"
      >
        <i class="icon fa fa-fw m-0" :class="selectedSaveAction.iconClasses" />
        {{ $t(`post.form.action.${selectedSaveAction.key}`) }}
      </button>
      <button
        type="button"
        class="btn btn-outline-primary btn-sm dropdown-toggle dropdown-toggle-split"
        data-toggle="dropdown"
        aria-haspopup="true"
        aria-expanded="false"
      >
        <span class="sr-only">{{ $t("generic.toggleDropdown") }}</span>
      </button>
      <div class="dropdown-menu">
        <a
          v-for="action in saveActions"
          :key="action.key"
          class="dropdown-item"
          :class="action.accessClass"
          href="javascript:void(0)"
          @click="
            selectedSaveAction = action;
            createOrUpdatePost();
          "
        >
          <i class="icon fa fa-fw" :class="action.iconClasses" />
          {{ $t(`post.form.action.${action.key}`) }}
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
import { ACT, GET, MUTATE } from "@/store/types";
import { mapActions, mapGetters, mapMutations } from "vuex";
import cloneDeep from "lodash/cloneDeep";
import { Post } from "@/types/post";
import PostFormInput from "@/components/LongpageSidebar/Posts/Thread/PostForm/PostFormInput";
import { SAVE_ACTIONS } from "@/util/constants";
import { Thread } from "@/types/thread";

export default {
  name: "PostForm",
  components: { PostFormInput },
  props: {
    show: { type: Boolean, default: false },
    post: { type: Post, required: true },
    thread: { type: Thread, required: true },
  },
  emits: ["update:show"],
  data() {
    return {
      postUpdate: null,
      selectedSaveActionInternal: SAVE_ACTIONS[0],
    };
  },
  computed: {
    ...mapGetters("annotation", [GET.ANNOTATION]),
    saveActions() {
      return SAVE_ACTIONS.filter(({ key }) => {
        if (key === "save") {
          if (this.post === this.thread.root && this.thread.replyCount == 0)
            return true;
          else return false;
        }
        if (key === "saveDisabled") {
          if (this.post === this.thread.root && this.thread.replyCount > 0)
            return true;
          else return false;
        }
        return true;
      });
    },
    selectedSaveAction: {
      get() {
        if (this.postUpdate.anonymous) return this.saveActions[1];
        if (this.postUpdate.isPublic) return this.saveActions[0];
        return this.saveActions[2];
      },
      set(action) {
        this.postUpdate.anonymous = action.key === "publishAnonymously";
        this.postUpdate.isPublic = action.key !== "save";
        this.selectedSaveActionInternal = action;
      },
    },
    savingDisabled() {
      return this.thread.replyCount > 0 ? true : false;
    },
  },
  mounted() {
    this.postUpdate = cloneDeep(this.post);
    this.postUpdate.isPublic = this.thread.isPublic;
  },
  methods: {
    ...mapActions("annotation", [ACT.CREATE_ANNOTATION]),
    ...mapActions("post", [ACT.CREATE_POST, ACT.UPDATE_POST]),
    ...mapMutations("annotation", [MUTATE.REMOVE_ANNOTATIONS]),
    ...mapMutations("post", [
      MUTATE.REMOVE_POSTS_FROM_THREAD,
      MUTATE.REMOVE_THREADS,
    ]),
    cancel() {
      if (!this.thread.created) {
        this[MUTATE.REMOVE_THREADS]([this.thread]);
        this[MUTATE.REMOVE_ANNOTATIONS]([
          this[GET.ANNOTATION](this.thread.annotationId),
        ]);
      } else if (!this.post.created) {
        this[MUTATE.REMOVE_POSTS_FROM_THREAD]({
          threadId: this.thread.id,
          posts: [this.post],
        });
      }
      this.closeForm();
    },
    closeForm() {
      this.$emit("update:show");
    },
    async createOrUpdatePost() {
      if (!this.postUpdate.content) return;

      if (this.post.created) this[ACT.UPDATE_POST](this.postUpdate);
      else this[ACT.CREATE_POST](this.postUpdate);
      this.closeForm();
    },
  },
};
</script>
