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
import { AnnotationType, MoodleWSMethods } from "@/config/constants";
import { GET, ACT, MUTATE } from "../types";
import { Annotation } from "@/types/annotation";
import { AnnotationCompareFunction } from "@/util/comparing";
import ajax from "core/ajax";
import MappingService from "@/services/mapping-service";
import { AnnotationFilter } from "@/util/filters/annotation-filter";

export default {
  state: {
    annotations: [],
    annotationFilter: AnnotationFilter.DEFAULT,
    filteredAnnotations: null,
  },
  getters: {
    [GET.ANNOTATION]: (_, getters) => (id) =>
      getters[GET.ANNOTATIONS].find((a) => a.id === id),
    [GET.ANNOTATION_FILTER]: ({ annotationFilter }) => annotationFilter,
    [GET.ANNOTATION_WITH_CONTEXT]: (_, getters) => (annotation) => ({
      ...annotation,
      longpageid: getters[GET.LONGPAGE_CONTEXT].longpageid,
    }),
    [GET.ANNOTATIONS]: ({ annotations }) => annotations,
    [GET.BOOKMARKS]: (_, getters) => {
      return getters[GET.ANNOTATIONS]
        .filter((a) => a.type === AnnotationType.BOOKMARK)
        .sort(AnnotationCompareFunction.BY_POSITION);
    },
    [GET.FILTERED_ANNOTATIONS]: ({ filteredAnnotations }) =>
      filteredAnnotations,
    [GET.HIGHLIGHTS]: (_, getters) => {
      return getters[GET.ANNOTATIONS]
        .filter((a) => a.type === AnnotationType.HIGHLIGHT)
        .sort(AnnotationCompareFunction.BY_POSITION);
    },
    [GET.NEW_ANNOTATION]:
      (_, getters) =>
      (params = {}) => {
        const annotation = new Annotation({
          creatorId: getters[GET.LONGPAGE_CONTEXT].userId,
          longpageid: getters[GET.LONGPAGE_CONTEXT].longpageid,
          ...params,
        });
        annotation.body =
          params.type === AnnotationType.POST && !params.body
            ? getters[GET.NEW_THREAD]({ annotationId: annotation.id })
            : undefined;
        return annotation;
      },
  },
  mutations: {
    [MUTATE.ADD_ANNOTATIONS](state, annotations) {
      state.annotations = [...state.annotations, ...annotations];
    },
    [MUTATE.REMOVE_ANNOTATIONS](state, annotationsToRemove) {
      state.annotations = state.annotations.filter(
        (a) => !annotationsToRemove.find((atr) => atr.id === a.id),
      );
      if (state.filteredAnnotations) {
        state.filteredAnnotations = state.filteredAnnotations.filter(
          (a) => !annotationsToRemove.find((atr) => atr.id === a.id),
        );
      }
    },
    [MUTATE.SET_ANNOTATION_FILTER](state, filter) {
      state.annotationFilter = filter;
    },
    [MUTATE.SET_ANNOTATIONS](state, annotations) {
      state.annotations = annotations;
    },
    [MUTATE.SET_FILTERED_ANNOTATIONS](state, annotations) {
      state.filteredAnnotations = annotations;
    },
    [MUTATE.UPDATE_ANNOTATION](state, { id, annotationUpdate }) {
      const annotation = state.annotations.find(
        (annotation) => annotation.id === id,
      );
      Object.assign(annotation, annotationUpdate);
    },
  },
  actions: {
    [ACT.REPLACE_OR_ADD_ANNOTATION]({ commit, getters }, annotation) {
      if (getters[GET.ANNOTATION](annotation.id)) {
        commit(MUTATE.UPDATE_ANNOTATION, {
          id: annotation.id,
          annotationUpdate: annotation,
        });
      } else {
        commit(MUTATE.ADD_ANNOTATIONS, [annotation]);
        if (getters[GET.FILTERED_ANNOTATIONS]) {
          commit(
            MUTATE.SET_FILTERED_ANNOTATIONS,
            [annotation, ...getters[GET.FILTERED_ANNOTATIONS]].sort(
              AnnotationCompareFunction.BY_POSITION,
            ),
          );
        }
      }
    },
    [ACT.CREATE_ANNOTATION]({ commit, dispatch, getters }, params = {}) {
      const annotation =
        getters[GET.ANNOTATION](params.id) ||
        getters[GET.NEW_ANNOTATION](params);
      dispatch(ACT.REPLACE_OR_ADD_ANNOTATION, annotation);
      if (annotation.type === AnnotationType.POST) {
        dispatch(ACT.REPLACE_OR_ADD_THREAD, annotation.body);
        annotation.isPublic = annotation.body.isPublic;
        commit(MUTATE.UPDATE_ANNOTATION, {
          id: annotation.id,
          annotationUpdate: annotation,
        });
        if (!annotation.body.root.content) return;
      }

      ajax.call([
        {
          methodname: MoodleWSMethods.CREATE_ANNOTATION,
          args: MappingService.mapAnnotationToArgs(annotation),
          done: (response) => {
            const annotationUpdate = MappingService.mapResponseToAnnotation(
              response.annotation,
            );
            if (annotationUpdate.type === AnnotationType.POST) {
              commit(MUTATE.UPDATE_THREAD, {
                id: annotation.body.id,
                threadUpdate: annotationUpdate.body,
              });
            }
            commit(MUTATE.UPDATE_ANNOTATION, {
              id: annotation.id,
              annotationUpdate,
            });
          },
          fail: (e) => {
            commit(MUTATE.REMOVE_THREADS, [annotation.body]);
            commit(MUTATE.REMOVE_ANNOTATIONS, [annotation]);
            console.error(`"${MoodleWSMethods.CREATE_ANNOTATION}" failed`, e);
          },
        },
      ]);
    },
    [ACT.DELETE_ANNOTATION]({ commit }, annotation) {
      if (!annotation.created) return;

      if (annotation.body) commit(MUTATE.REMOVE_THREADS, [annotation.body]);
      commit(MUTATE.REMOVE_ANNOTATIONS, [annotation]);

      ajax.call([
        {
          methodname: MoodleWSMethods.DELETE_ANNOTATION,
          args: { id: annotation.id },
          done: () => {},
          fail: (e) => {
            commit(MUTATE.ADD_ANNOTATIONS, [annotation]);
            if (annotation.body) commit(MUTATE.ADD_THREADS, [annotation.body]);
            console.error(`"${MoodleWSMethods.DELETE_ANNOTATION}" failed`, e);
          },
        },
      ]);
    },
    [ACT.FETCH_ANNOTATIONS]({ commit, getters }, userid = null) {
      ajax.call([
        {
          methodname: MoodleWSMethods.GET_ANNOTATIONS,
          args: {
            parameters: {
              longpageid: getters[GET.LONGPAGE_CONTEXT].longpageid,
            },
          },
          done: (response) => {
            const annotations = MappingService.mapResponseToAnnotations(
              response.annotations,
            );
            commit(MUTATE.SET_ANNOTATIONS, annotations);
            commit(
              MUTATE.SET_THREADS,
              annotations.filter((a) => Boolean(a.body)).map((a) => a.body),
            );
          },
          fail: (e) => {
            console.error(`"${MoodleWSMethods.GET_ANNOTATIONS}" failed`, e);
          },
        },
      ]);
    },
    [ACT.FILTER_ANNOTATIONS]({ commit, dispatch, getters }, filter = null) {
      commit(MUTATE.SET_ANNOTATION_FILTER, filter || AnnotationFilter.DEFAULT);
      const filteredAnnotations =
        filter &&
        new AnnotationFilter(filter)
          .applyTo(...getters[GET.ANNOTATIONS])
          .sort(AnnotationCompareFunction.BY_POSITION);
      commit(MUTATE.SET_FILTERED_ANNOTATIONS, filteredAnnotations);
      dispatch(ACT.FILTER_THREADS, filter && filter.body);
    },
  },
};
