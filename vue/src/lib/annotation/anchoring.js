// Copyright (c) 2013-2019 Hypothes.is Project and contributors
//
// Redistribution and use in source and binary forms, with or without
// modification, are permitted provided that the following conditions are met:
//
// 1. Redistributions of source code must retain the above copyright notice, this
//    list of conditions and the following disclaimer.
// 2. Redistributions in binary form must reproduce the above copyright notice,
//    this list of conditions and the following disclaimer in the documentation
//    and/or other materials provided with the distribution.
//
//     THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
// ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
// WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
// DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
// ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
// (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
// LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
// ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
// (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
// SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
import debounce from "lodash/debounce";
import remove from "lodash/remove";
import { highlightRange, removeHighlights } from "./highlighting";
import { anchor } from "./hypothesis/anchoring/html";
import emitter from "tiny-emitter/instance";
import { GET } from "@/store/types";
import { sniff } from "./hypothesis/anchoring/range";

const emitAnchorsUpdate = debounce((anchors) => {
  emitter.emit("anchors-updated", anchors);
}, 500);

export class Anchoring {
  /**
   * @param {HTMLElement} root
   * @param {Anchor[]} anchors
   */
  constructor(root, store, anchors = []) {
    this.anchoring = { anchor };
    this.root = root;
    this.anchors = anchors;
    this.anchoringPromise = Promise.resolve();
    this.unsubscribe = store.watch(
      (_, getters) =>
        getters["annotation/" + GET.FILTERED_ANNOTATIONS] ||
        getters["annotation/" + GET.ANNOTATIONS],
      (newAnnotations) => {
        this.detachAllAnnotations();
        this.anchorAnnotations(newAnnotations);
      },
    );
  }

  _chainToAnchoringPromise(onFulfilled) {
    this.anchoringPromise = this.anchoringPromise.then(onFulfilled).then(() => {
      this.sync();
    });
  }

  /**
   * Anchor (locate) an annotation's selectors in the document.
   * Used after new annotation have been loaded & after new annotation has been created.
   *
   * @param {Annotation} annotation
   * @return {Promise<Anchor[]>}
   */
  anchorAnnotation(annotation) {
    this._chainToAnchoringPromise(() => {
      let anchor;

      // Anchors for all annotations are in the `anchors` instance property. These
      // are anchors for this annotation only. After all the targets have been
      // processed these will be appended to the list of anchors known to the
      // instance. Anchors hold an annotation, a target of that annotation, a
      // document range for that target and an Array of highlights.
      const anchors = [];

      // The targets that are already anchored. This function consults this to
      // determine which targets can be left alone.
      const anchoredTargets = [];

      // These are the highlights for existing anchors of this annotation with
      // targets that have since been removed from the annotation. These will
      // be removed by this function.
      let deadHighlights = [];

      /**
       * Locate the region of the current document that the annotation refers to.
       *
       * @param {Target} target
       */
      const locate = (target) => {
        // Check that the anchor has a TextQuoteSelector -- without a
        // TextQuoteSelector we have no basis on which to verify that we have
        // reanchored correctly and so we shouldn't even try.
        //
        // Returning an anchor without a range will result in this annotation being
        // treated as an orphan (assuming no other targets anchor).
        if (
          !target.selectors ||
          !target.selectors.some((s) => s.type === "TextQuoteSelector")
        ) {
          return Promise.resolve({ annotation, target });
        }

        // Find a target using the anchoring module.
        return this.anchoring
          .anchor(this.root, target.selectors)
          .then((range) => ({
            annotation,
            target,
            range,
          }))
          .catch(() => ({
            annotation,
            target,
          }));
      };

      /**
       * Highlight the range for an anchor.
       *
       * @param {Anchor} anchor
       */
      const highlight = (anchor) => {
        if (!anchor.range) {
          return anchor;
        }
        const range = sniff(anchor.range);
        const normedRange = range.normalize(this.root);
        const highlights = /** @type {AnnotationHighlight[]} */ (
          highlightRange(normedRange, anchor.target.styleClass)
        );
        // You need to put some information on the highlight so when it is clicked later on we can identify the annotation
        highlights.forEach((h) => {
          h._annotation = anchor.annotation;
        });
        anchor.highlights = highlights;
        return anchor;
      };

      const getAnnotationsAnchoringState = (anchors) => {
        let hasAnchorableTargets = false;
        let hasAnchoredTargets = false;
        for (let anchor of anchors) {
          if (anchor.target.selectors) {
            hasAnchorableTargets = true;
            if (anchor.range) {
              hasAnchoredTargets = true;
              break;
            }
          }
        }
        return { hasAnchorableTargets, hasAnchoredTargets };
      };

      const markOrphans = (anchors) => {
        // An annotation is considered to be an orphan if it has at least one
        // target with selectors, and all targets with selectors failed to anchor
        // (i.e. we didn't find it in the page and thus it has no range).
        let { hasAnchorableTargets, hasAnchoredTargets } =
          getAnnotationsAnchoringState(anchors);
        annotation.$orphan = hasAnchorableTargets && !hasAnchoredTargets;
        return anchors;
      };

      // Remove all the anchors for this annotation from the instance storage.
      for (anchor of this.anchors.splice(0, this.anchors.length)) {
        if (
          anchor.annotation === annotation ||
          anchor.annotation.id === annotation.id
        ) {
          // Anchors are valid as long as they still have a range and their target
          // is still in the list of targets for this annotation.
          if (
            anchor.range &&
            (annotation.target === anchor.target ||
              annotation.target.id === anchor.target.id)
          ) {
            anchors.push(anchor);
            anchoredTargets.push(anchor.target);
          } else if (anchor.highlights) {
            // These highlights are no longer valid and should be removed.
            deadHighlights = deadHighlights.concat(anchor.highlights);
            delete anchor.highlights;
            delete anchor.range;
          }
        } else {
          // These can be ignored, so push them back onto the new list.
          this.anchors.push(anchor);
        }
      }

      // Remove all the highlights that have no corresponding target anymore.
      requestAnimationFrame(() => removeHighlights(deadHighlights));

      // Actual work going on
      // Anchor the target of this annotation that is not anchored already.
      if (
        !anchoredTargets.includes(annotation.target) ||
        !anchoredTargets.map(({ id }) => id).includes(annotation.target.id)
      ) {
        anchor = locate(annotation.target).then(highlight);
        anchors.push(anchor);
      }
      return Promise.all(anchors)
        .then(markOrphans)
        .then((anchors) => {
          this.anchors.push(...anchors);
        });
    });
  }

  anchorAnnotations(annotations) {
    annotations.forEach((a) => {
      this.anchorAnnotation(a);
    });
  }

  detachAllAnnotations() {
    this._chainToAnchoringPromise(() => {
      this.removeHighlights(this.getAllHighlights());
      this.anchors = [];
    });
  }

  /**
   * Remove the anchors and associated highlights for an annotation from the document.
   *
   * @param {Annotation} annotation
   */
  detachAnnotation(annotation) {
    this._chainToAnchoringPromise(() => {
      const anchorsOfAnnotation = remove(this.anchors, (anchor) => {
        return anchor.annotation.id === annotation.id;
      });
      const highlightsOfAnnotation = anchorsOfAnnotation.reduce(
        (highlights, anchor) => highlights.concat(anchor.highlights || []),
        [],
      );
      this.removeHighlights(highlightsOfAnnotation);
    });
  }

  detachAnnotations(annotations) {
    annotations.forEach((annotation) => {
      this.detachAnnotation(annotation);
    });
  }

  getAllHighlights() {
    return this.anchors.reduce((highlights, anchor) => {
      highlights.push(...(anchor.highlights || []));
      return highlights;
    }, []);
  }

  removeHighlights(highlights) {
    requestAnimationFrame(() => {
      removeHighlights(highlights);
    });
  }

  /**
   * Inform other parts of the application about
   * the results of anchoring.
   */
  sync() {
    emitAnchorsUpdate([...this.anchors]);
  }
}
