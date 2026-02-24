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
import { FilterBase } from "@/util/filters/filter-base";
import { ThreadFilter } from "@/util/filters/thread-filter";
import { AnnotationType } from "@/util/constants";
import { Annotation } from "@/types/annotation";

export class AnnotationFilter extends FilterBase {
  constructor(filterParams = {}) {
    super();
    this.threadFilter = new ThreadFilter(filterParams.body);
    Object.assign(this, filterParams);
  }

  applyTo(...annotations) {
    return annotations.reduce((result, annotation) => {
      if (annotation.type !== AnnotationType.POST)
        return [...result, annotation];

      const [filteredThread] = this.threadFilter.applyTo(annotation.body);
      if (filteredThread)
        return [
          ...result,
          new Annotation({ ...annotation, body: filteredThread }),
        ];

      return result;
    }, []);
  }

  static get DEFAULT() {
    return {
      body: {
        subscribedToByUser: undefined,
        gotRequestedReply: undefined,
        posts: {
          query: "",
          likedByUser: undefined,
          bookmarkedByUser: undefined,
          readByUser: undefined,
          likesCount: { min: undefined, max: undefined },
          timeCreated: { min: undefined, max: undefined },
          timeModified: { min: undefined, max: undefined },
          creator: [],
        },
      },
    };
  }
}
