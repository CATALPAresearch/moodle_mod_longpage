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
import { ACT, GET, MUTATE } from "../types";
import flatten from "lodash/flatten";
import pick from "lodash/pick";
import { AnnotationCompareFunction } from "@/util/comparators";
import ajax from "core/ajax";
import MappingService from "@/services/mapping-service";
import { MoodleWSMethods } from "@/util/constants";
import { Post } from "@/types/post";
import { Thread } from "@/types/thread";
import { ThreadFilter } from "@/util/filters/thread-filter";

export default {
  namespaced: true,
  state: {
    filteredThreads: null,
    postLastModified: null,
    selectedThreadSortingOption: "byRecommendation",
    threadFilter: ThreadFilter.DEFAULT,
    threads: [],
  },
  getters: {
    [GET.FILTERED_THREADS]: ({ filteredThreads }, getters) =>
      filteredThreads &&
      filteredThreads.sort(getters[GET.SELECTED_THREAD_COMP_FUNC_FOR_SORT]),
    [GET.NEW_POST]:
      (_, getters, rootState, rootGetters) =>
      (params = {}) => {
        const rememberablePropsOfPostLastModified = pick(
          getters[GET.POST_LAST_MODIFIED] || {},
          ["isPublic", "anonymous"],
        );
        return new Post({
          creatorId: rootGetters[GET.LONGPAGE_CONTEXT].userId,
          ...rememberablePropsOfPostLastModified,
          ...params,
        });
      },
    [GET.NEW_THREAD]:
      (_, getters) =>
      (params = {}) => {
        const thread = new Thread({ ...params });
        if (!thread.posts.length)
          thread.posts.push(getters[GET.NEW_POST]({ threadId: thread.id }));
        return thread;
      },
    [GET.POST]:
      ({ threads }) =>
      (postId, threadId) => {
        const thread = threads.find((t) => t.id === threadId);
        return thread && thread.posts.find((p) => p.id === postId);
      },
    [GET.POST_LAST_MODIFIED]: ({ postLastModified }) => postLastModified,
    [GET.POSTS]: ({ threads }) => {
      return flatten(threads.map(({ posts }) => posts));
    },
    [GET.SELECTED_THREAD_SORTING_OPTION]: ({ selectedThreadSortingOption }) =>
      selectedThreadSortingOption,
    [GET.SELECTED_THREAD_COMP_FUNC_FOR_SORT]: (_, getters) => {
      return getters[GET.THREAD_SORTING_OPTIONS][
        getters[GET.SELECTED_THREAD_SORTING_OPTION]
      ];
    },
    [GET.THREAD]:
      ({ threads }) =>
      (id) =>
        threads.find((t) => t.id === id),
    [GET.THREAD_FILTER]: ({ threadFilter }) => threadFilter,
    [GET.THREAD_SORTING_OPTIONS]: (_, getters, rootState, rootGetters) => ({
      byNovelty: (a, b) => b.timeModified - a.timeModified,
      byPosition: (a, b) => {
        const [annotationA, annotationB] = [a, b].map((t) =>
          rootGetters[`annotation/${GET.ANNOTATION}`](t.annotationId),
        );
        return AnnotationCompareFunction.BY_POSITION(annotationA, annotationB);
      },
      byRecommendation: (a, b) => b.recommendation - a.recommendation,
    }),
    [GET.THREADS]: ({ threads }, getters) =>
      threads.sort(getters[GET.SELECTED_THREAD_COMP_FUNC_FOR_SORT]),
    [GET.THREADS_FILTERED]: (_, getters) =>
      Boolean(getters[GET.FILTERED_THREADS]),
  },
  mutations: {
    [MUTATE.ADD_POSTS_TO_THREAD](state, { threadId, posts }) {
      const thread = state.threads.find((thread) => thread.id === threadId);
      thread.posts = [...thread.posts, ...posts];
      if (!state.filteredThreads) return;

      const threadDuplicate = state.filteredThreads.find(
        (thread) => thread.id === threadId,
      );
      threadDuplicate.posts = [...threadDuplicate.posts, ...posts];
    },
    [MUTATE.ADD_THREADS](state, threads) {
      state.threads.push(...threads);
    },
    [MUTATE.POST_LAST_MODIFIED](state, post) {
      state.postLastModified = post;
    },
    [MUTATE.REMOVE_POSTS_FROM_THREAD](state, { threadId, posts }) {
      const thread = state.threads.find((thread) => thread.id === threadId);
      thread.posts = thread.posts.filter(
        (p) => !posts.find((post) => post.id === p.id),
      );
      if (!state.filteredThreads) return;

      const threadDuplicate = state.filteredThreads.find(
        (thread) => thread.id === threadId,
      );
      threadDuplicate.posts = threadDuplicate.posts.filter(
        (p) => !posts.find((post) => post.id === p.id),
      );
    },
    [MUTATE.REMOVE_THREADS](state, threadsToRemove) {
      state.threads = state.threads.filter(
        (t) => !threadsToRemove.find((ttr) => ttr.id === t.id),
      );
      if (!state.filteredThreads) return;

      state.filteredThreads = state.filteredThreads.filter(
        (t) => !threadsToRemove.find((ttr) => ttr.id === t.id),
      );
    },
    [MUTATE.REPLACE_POST](state, post) {
      const thread = state.threads.find(
        (thread) => thread.id === post.threadId,
      );
      const posts = [...thread.posts];
      posts.splice(
        posts.findIndex((p) => p.id === post.id),
        1,
        post,
      );
      thread.posts = posts;
    },
    [MUTATE.SELECTED_THREAD_SORTING_OPTION](state, option) {
      state.selectedThreadSortingOption = option;
    },
    [MUTATE.SET_FILTERED_THREADS](state, filteredThreads) {
      state.filteredThreads = filteredThreads;
    },
    [MUTATE.SET_THREAD_FILTER](state, filter) {
      state.threadFilter = filter;
    },
    [MUTATE.SET_THREADS](state, threads) {
      state.threads = threads;
    },
    [MUTATE.UPDATE_POST](state, { threadId, postId, postUpdate }) {
      const thread = state.threads.find((thread) => thread.id === threadId);
      const post = thread.posts.find((post) => post.id === postId);
      Object.assign(post, postUpdate);
    },
    [MUTATE.UPDATE_THREAD](state, { id, threadUpdate }) {
      const thread = state.threads.find((thread) => thread.id === id);
      Object.assign(thread, threadUpdate);
    },
  },
  actions: {
    async [ACT.CREATE_POST](
      { commit, dispatch, getters, rootGetters },
      params = {},
    ) {
      const post =
        getters[GET.POST](params.id) || getters[GET.NEW_POST](params);
      dispatch(ACT.REPLACE_OR_ADD_POST, post);
      commit(MUTATE.POST_LAST_MODIFIED, post);
      if (!post.content) return;

      const thread = getters[GET.THREAD](post.threadId);
      if (!thread.created) {
        dispatch(
          `annotation/${ACT.CREATE_ANNOTATION}`,
          rootGetters[`annotation/${GET.ANNOTATION}`](thread.annotationId),
          { root: true },
        );
        return;
      }

      ajax.call([
        {
          methodname: MoodleWSMethods.CREATE_POST,
          args: MappingService.mapPostToArgs({
            ...post,
            longpageid: rootGetters[GET.LONGPAGE_CONTEXT].longpageid,
          }),
          done: (response) => {
            const postUpdate = MappingService.mapResponseToPost(response.post);
            commit(MUTATE.UPDATE_POST, {
              threadId: thread.id,
              postId: post.id,
              postUpdate,
            });
          },
          fail: (e) => {
            commit(MUTATE.REMOVE_POSTS_FROM_THREAD, {
              threadId: thread.id,
              posts: [post],
            });
            console.error(`"${MoodleWSMethods.CREATE_POST}" failed`, e);
          },
        },
      ]);
    },
    [ACT.DELETE_POST]({ commit, dispatch, getters, rootGetters }, post) {
      if (!post.created) return;

      const thread = getters[GET.THREAD](post.threadId);
      const postIsThreadRoot = thread.root.id === post.id;
      if (postIsThreadRoot) {
        dispatch(
          `annotation/${ACT.DELETE_ANNOTATION}`,
          rootGetters[`annotation/${GET.ANNOTATION}`](thread.annotationId),
          { root: true },
        );
        return;
      }

      commit(MUTATE.REMOVE_POSTS_FROM_THREAD, {
        threadId: thread.id,
        posts: [post],
      });
      ajax.call([
        {
          methodname: MoodleWSMethods.DELETE_POST,
          args: { id: post.id },
          done: () => {},
          fail: (e) => {
            commit(MUTATE.ADD_POSTS_TO_THREAD, {
              threadId: thread.id,
              posts: [post],
            });
            console.error(`"${MoodleWSMethods.DELETE_POST}" failed`, e);
          },
        },
      ]);
    },
    [ACT.FILTER_THREADS]({ commit, getters }, filter = null) {
      commit(MUTATE.SET_THREAD_FILTER, filter || ThreadFilter.DEFAULT);
      const filteredThreads =
        filter && new ThreadFilter(filter).applyTo(...getters[GET.THREADS]);
      commit(MUTATE.SET_FILTERED_THREADS, filteredThreads);
    },
    [ACT.REPLACE_OR_ADD_POST]({ commit, getters }, post) {
      if (getters[GET.POST](post.id, post.threadId)) {
        commit(MUTATE.UPDATE_POST, {
          threadId: post.threadId,
          postId: post.id,
          postUpdate: post,
        });
      } else {
        commit(MUTATE.ADD_POSTS_TO_THREAD, {
          threadId: post.threadId,
          posts: [post],
        });
      }
    },
    [ACT.REPLACE_OR_ADD_THREAD]({ commit, getters }, thread) {
      if (getters[GET.THREAD](thread.id)) {
        commit(MUTATE.UPDATE_THREAD, { id: thread.id, threadUpdate: thread });
      } else {
        commit(MUTATE.ADD_THREADS, [thread]);
        if (getters[GET.THREADS_FILTERED]) {
          commit(MUTATE.SET_FILTERED_THREADS, [
            thread,
            ...getters[GET.FILTERED_THREADS],
          ]);
        }
      }
    },
    [ACT.TOGGLE_POST_BOOKMARK]({ commit, getters }, { postId, threadId }) {
      const post = getters[GET.POST](postId, threadId);
      commit(MUTATE.UPDATE_POST, {
        threadId: post.threadId,
        postId: post.id,
        postUpdate: { ...post, bookmarkedByUser: !post.bookmarkedByUser },
      });
      const methodname = post.bookmarkedByUser
        ? MoodleWSMethods.CREATE_POST_BOOKMARK
        : MoodleWSMethods.DELETE_POST_BOOKMARK;
      ajax.call([
        {
          methodname,
          args: { postid: post.id },
          fail: (e) => {
            console.error(`"${methodname}" failed`, e);
            commit(MUTATE.UPDATE_POST, {
              threadId: post.threadId,
              postId: post.id,
              postUpdate: { ...post, bookmarkedByUser: !post.bookmarkedByUser },
            });
          },
        },
      ]);
    },
    [ACT.TOGGLE_POST_LIKE]({ commit, getters }, { postId, threadId }) {
      const post = getters[GET.POST](postId, threadId);
      commit(MUTATE.UPDATE_POST, {
        threadId: post.threadId,
        postId: post.id,
        postUpdate: { ...post, likedByUser: !post.likedByUser },
      });
      const methodname = post.likedByUser
        ? MoodleWSMethods.CREATE_POST_LIKE
        : MoodleWSMethods.DELETE_POST_LIKE;
      ajax.call([
        {
          methodname,
          args: { postid: post.id },
          fail: (e) => {
            console.error(`"${methodname}" failed`, e);
            commit(MUTATE.UPDATE_POST, {
              threadId: post.threadId,
              postId: post.id,
              postUpdate: { ...post, likedByUser: !post.likedByUser },
            });
          },
        },
      ]);
    },
    [ACT.TOGGLE_POST_READING]({ commit, getters }, { postId, threadId }) {
      const post = getters[GET.POST](postId, threadId);
      commit(MUTATE.UPDATE_POST, {
        threadId: post.threadId,
        postId: post.id,
        postUpdate: { ...post, readByUser: !post.readByUser },
      });
      const methodname = post.readByUser
        ? MoodleWSMethods.CREATE_POST_READING
        : MoodleWSMethods.DELETE_POST_READING;
      ajax.call([
        {
          methodname,
          args: { postid: post.id },
          fail: (e) => {
            console.error(`"${methodname}" failed`, e);
            commit(MUTATE.UPDATE_POST, {
              threadId: post.threadId,
              postId: post.id,
              postUpdate: { ...post, readByUser: !post.readByUser },
            });
          },
        },
      ]);
    },
    [ACT.TOGGLE_THREAD_SUBSCRIPTION]({ commit, getters }, threadId) {
      const thread = getters[GET.THREAD](threadId);
      commit(MUTATE.UPDATE_THREAD, {
        id: threadId,
        threadUpdate: {
          ...thread,
          subscribedToByUser: !thread.subscribedToByUser,
        },
      });
      const methodname = thread.subscribedToByUser
        ? MoodleWSMethods.CREATE_THREAD_SUBSCRIPTION
        : MoodleWSMethods.DELETE_THREAD_SUBSCRIPTION;
      ajax.call([
        {
          methodname,
          args: { threadid: thread.id },
          fail: (e) => {
            console.error(`"${methodname}" failed`, e);
            commit(MUTATE.UPDATE_THREAD, {
              id: threadId,
              threadUpdate: {
                ...thread,
                subscribedToByUser: !thread.subscribedToByUser,
              },
            });
          },
        },
      ]);
    },
    [ACT.UPDATE_POST]({ commit, getters }, postUpdate) {
      const post = { ...getters[GET.POST](postUpdate.id, postUpdate.threadId) };
      commit(MUTATE.UPDATE_POST, {
        threadId: post.threadId,
        postId: post.id,
        postUpdate,
      });
      commit(MUTATE.POST_LAST_MODIFIED, new Post({ ...post, ...postUpdate }));
      ajax.call([
        {
          methodname: MoodleWSMethods.UPDATE_POST,
          args: MappingService.mapPostUpdateToArgs(postUpdate),
          done: (response) => {
            const postUpdate = MappingService.mapResponseToPost(response.post);
            commit(MUTATE.UPDATE_POST, {
              threadId: post.threadId,
              postId: post.id,
              postUpdate,
            });
          },
          fail: (e) => {
            commit(MUTATE.UPDATE_POST, {
              threadId: post.threadId,
              postId: post.id,
              postUpdate: post,
            });
            console.error(`"${MoodleWSMethods.UPDATE_POST}" failed`, e);
          },
        },
      ]);
    },
  },
};
