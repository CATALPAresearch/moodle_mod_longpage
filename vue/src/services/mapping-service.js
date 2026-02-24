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
import { Annotation } from "@/types/annotation";
import { deepLowerCaseKeys } from "@/util/misc";
import { MoodleWSMethods, SelectorType } from "@/util/constants";
import invert from "lodash/invert";
import pick from "lodash/pick";
import { AnnotationTarget } from "@/types/annotation-target";
import { Thread } from "@/types/thread";
import { Post } from "@/types/post";
import { User } from "@/types/user";
import { UserRole } from "@/types/user-role";

const SELECTOR_TYPE_ARGS_MAPPING = {
  [SelectorType.TEXT_QUOTE_SELECTOR]: 0,
  [SelectorType.TEXT_POSITION_SELECTOR]: 1,
  [SelectorType.RANGE_SELECTOR]: 2,
};

const SELECTOR_TYPE_RESPONSE_MAPPING = invert(SELECTOR_TYPE_ARGS_MAPPING);

const MappingService = {
  _mapSelectorsArgs(selectors) {
    return selectors.map((selector) => {
      const type = this._mapSelectorTypeArgs(selector.type);
      if (selector.type === SelectorType.TEXT_POSITION_SELECTOR) {
        return {
          endposition: selector.end,
          startposition: selector.start,
          type,
        };
      }
      return { ...selector, type };
    });
  },
  _mapSelectorTypeArgs(selectorType) {
    return SELECTOR_TYPE_ARGS_MAPPING[selectorType];
  },
  _mapSelectorTypeResponse(selectorType) {
    return SELECTOR_TYPE_RESPONSE_MAPPING[selectorType];
  },
  _mapTimeResponse(timeInS) {
    const timeInMs = timeInS * 1000;
    return new Date(timeInMs);
  },
  mapThreadToArgs(thread) {
    return deepLowerCaseKeys({
      ...pick(thread.root, ["content", "anonymous"]),
      ...pick(thread, ["replyRequested"]),
      ispublic: thread.isPublic,
    });
  },
  mapAnnotationToArgs(annotation) {
    return deepLowerCaseKeys({
      annotation: {
        ...pick(annotation, ["longpageid", "type", "isPublic"]),
        target: {
          selectors: this._mapSelectorsArgs(annotation.target.selectors),
          styleClass: annotation.target.styleClass,
        },
        body: annotation.body && this.mapThreadToArgs(annotation.body),
      },
    });
  },
  mapPostToArgs(post) {
    return deepLowerCaseKeys({
      post: {
        ...pick(post, [
          "threadId",
          "anonymous",
          "content",
          "isPublic",
          "longpageid",
        ]),
      },
    });
  },
  mapPostUpdateToArgs(postUpdate) {
    return deepLowerCaseKeys({
      postUpdate: {
        ...pick(postUpdate, [
          "id",
          "anonymous",
          "content",
          "isPublic",
          "creatorid",
          "islocked",
        ]),
      },
    });
  },
  mapResponseToAnnotation(response) {
    return new Annotation({
      ...pick(response, ["id", "type"]),
      creatorId: response.creatorid,
      isPublic: response.ispublic,
      islocked: response.islocked,
      longpageid: response.longpageid,
      target: this.mapResponseToAnnotationTarget(response.target),
      timeCreated: this._mapTimeResponse(response.timecreated),
      timeModified: this._mapTimeResponse(response.timemodified),
      body: response.body && this.mapResponseToThread(response.body),
    });
  },
  mapResponseToAnnotations(response) {
    return response.map(this.mapResponseToAnnotation.bind(this));
  },
  mapResponseToAnnotationTarget(response) {
    return new AnnotationTarget({
      id: response.id,
      selectors: response.selectors.map((s) => {
        const type = this._mapSelectorTypeResponse(s.type);
        switch (type) {
          case SelectorType.RANGE_SELECTOR:
            return {
              type,
              endContainer: s.endcontainer,
              endOffset: s.endoffset,
              startContainer: s.startcontainer,
              startOffset: s.startoffset,
            };
          case SelectorType.TEXT_POSITION_SELECTOR:
            return {
              type,
              end: s.endposition,
              start: s.startposition,
            };
          default:
            return { ...s, type };
        }
      }),
      styleClass: response.styleclass,
    });
  },
  mapResponseToPost(response) {
    return new Post({
      ...pick(response, ["id", "anonymous", "content", "recommendation"]),
      creatorId: response.creatorid,
      isPublic: response.ispublic,
      islocked: response.islocked,
      bookmarkedByUser: response.bookmarkedbyuser,
      likedByUser: response.likedbyuser,
      likesCount: response.likescount,
      readByUser: response.readbyuser,
      readingsCount: response.readingscount,
      threadId: response.threadid,
      timeCreated: this._mapTimeResponse(response.timecreated),
      timeModified: this._mapTimeResponse(response.timemodified),
    });
  },
  mapResponseToPosts(response) {
    return response.map((post) => this.mapResponseToPost(post));
  },
  mapResponseToThread(response) {
    return new Thread({
      ...pick(response, ["id"]),
      annotationId: response.annotationid,
      posts: this.mapResponseToPosts(response.posts),
      replyId: response.replyid,
      replyRequested: response.replyrequested,
      subscribedToByUser: response.subscribedtobyuser,
    });
  },
  mapResponseToUser(response) {
    return new User({
      id: response.id,
      firstName: response.firstname,
      fullName: response.fullname,
      lastName: response.lastname,
      imageAlt: response.imagealt,
      profileImage: response.profileimage,
      profileLink: response.profilelink,
      roles: response.roles,
    });
  },
  mapResponseToUserRole(response) {
    return new UserRole({
      id: response.id,
      localName: response.localname,
      shortName: response.shortname,
    });
  },
};

export default MappingService;
