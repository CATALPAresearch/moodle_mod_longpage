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
import { ArrowDirection, ARROW_H_MARGIN } from "../../util/constants";

/**
 * @typedef Target
 * @prop {number} left - Offset from left edge of viewport.
 * @prop {number} top - Offset from top edge of viewport.
 * @prop {ArrowDirection} arrowDirection - Direction of the AnnotationToolbar's arrow.
 */

/**
 * Return the closest ancestor of `el` which has been positioned.
 *
 * If no ancestor has been positioned, returns the root element.
 *
 * @param {Element} el
 * @return {Element}
 */
function nearestPositionedAncestor(el) {
  let parentEl = /** @type {Element} */ (el.parentElement);
  while (parentEl.parentElement) {
    if (getComputedStyle(parentEl).position !== "static") {
      break;
    }
    parentEl = parentEl.parentElement;
  }
  return parentEl;
}

/**
 * Container for the 'AnnotationToolbar' toolbar which provides controls for the user to
 * annotate and highlight the selected text.
 *
 * The toolbar implementation is split between this class, which is
 * the container for the toolbar that positions it on the page and isolates
 * it from the page's styles using shadow DOM, and the `AnnotationToolbarPopoverToolbar` Preact
 * component which actually renders the toolbar.
 */
export class AnnotationToolbarPopoverPositioner {
  /**
   * Create the toolbar's container and hide it.
   *
   * The AnnotationToolbar is initially hidden.
   *
   * @param {HTMLElement} container - The DOM element into which the AnnotationToolbar will be created
   * TODO
   */
  constructor(container, height, width, arrowHeight) {
    this._container = container;
    this._view = /** @type {Window} */ (container.ownerDocument.defaultView);
    this._height = height;
    this._width = width;
    this._arrowHeight = arrowHeight;
  }

  calculatePositionProps(selectionRect, isRTLselection) {
    const { left, top, arrowDirection } = this._calculateTarget(
      selectionRect,
      isRTLselection,
    );
    const zIndex = this._findZindex(left, top);
    return {
      left,
      top,
      arrowDirection,
      zIndex,
    };
  }

  /**
   *  Determine the best position for the AnnotationToolbar and its pointer-arrow.
   * - Position the pointer-arrow near the end of the selection (where the user's
   *   cursor/input is most likely to be)
   * - Position the AnnotationToolbar to center horizontally on the pointer-arrow
   * - Position the AnnotationToolbar below the selection (arrow pointing up) for LTR selections
   *   and above (arrow down) for RTL selections
   *
   * @param {DOMRect} selectionRect - The rect of text to target, in viewport
   *        coordinates.
   * @param {boolean} isRTLselection - True if the selection was made
   *        rigth-to-left, such that the focus point is mosty likely at the
   *        top-left edge of `targetRect`.
   * @return {Target}
   */
  _calculateTarget(selectionRect, isRTLselection) {
    // Set the initial arrow direction based on whether the selection was made
    // forwards/upwards or downwards/backwards.
    /** @type {ArrowDirection} */ let arrowDirection;
    if (isRTLselection) {
      arrowDirection = ArrowDirection.DOWN;
    } else {
      arrowDirection = ArrowDirection.UP;
    }
    let top;
    let left;

    // Position the AnnotationToolbar such that the arrow it is above or below the selection
    // and close to the end.
    const hMargin = Math.min(ARROW_H_MARGIN, selectionRect.width);
    if (isRTLselection) {
      left = selectionRect.left - this._width() / 2 + hMargin;
    } else {
      left =
        selectionRect.left + selectionRect.width - this._width() / 2 - hMargin;
    }

    // Flip arrow direction if AnnotationToolbar would appear above the top or below the
    // bottom of the viewport.
    if (
      selectionRect.top - this._height() < 0 &&
      arrowDirection === ArrowDirection.DOWN
    ) {
      arrowDirection = ArrowDirection.UP;
    } else if (selectionRect.top + this._height() > this._view.innerHeight) {
      arrowDirection = ArrowDirection.DOWN;
    }

    if (arrowDirection === ArrowDirection.UP) {
      top = selectionRect.top + selectionRect.height + this._arrowHeight();
    } else {
      top = selectionRect.top - this._height() - this._arrowHeight();
    }
    const { left: constrainedLeft, top: constrainedTop } =
      this.constrainPositionToViewport(left, top);
    return { left: constrainedLeft, top: constrainedTop, arrowDirection };
  }

  constrainPositionToViewport(left, top) {
    left = Math.max(left, 0);
    left = Math.min(left, this._view.innerWidth - this._width());

    top = Math.max(top, 0);
    top = Math.min(top, this._view.innerHeight - this._height());
    return { left, top };
  }

  /**
   * Find a Z index value that will cause the AnnotationToolbar to appear on top of any
   * content in the document when the AnnotationToolbar is shown at (left, top).
   *
   * @param {number} left - Horizontal offset from left edge of viewport.
   * @param {number} top - Vertical offset from top edge of viewport.
   * @return {number} - greatest zIndex (default value of 1)
   */
  _findZindex(left, top) {
    if (document.elementsFromPoint === undefined) {
      // In case of not being able to use `document.elementsFromPoint`,
      // default to the large arbitrary number (2^15)
      return 32768;
    }

    // Find the Z index of all the elements in the screen for five positions
    // around the AnnotationToolbar (left-top, left-bottom, middle-center, right-top,
    // right-bottom) and use the greatest.

    // Unique elements so `getComputedStyle` is called the minimum amount of times.
    const elements = new Set([
      ...document.elementsFromPoint(left, top),
      ...document.elementsFromPoint(left, top + this._height()),
      ...document.elementsFromPoint(
        left + this._width() / 2,
        top + this._height() / 2,
      ),
      ...document.elementsFromPoint(left + this._width(), top),
      ...document.elementsFromPoint(left + this._width(), top + this._height()),
    ]);

    const zIndexes = [...elements]
      .map((element) => +getComputedStyle(element).zIndex)
      .filter(Number.isInteger);

    // Make sure the array contains at least one element,
    // otherwise `Math.max(...[])` results in +Infinity
    zIndexes.push(0);

    return Math.max(...zIndexes) + 1;
  }

  /**
   * Translate the (left, top) viewport coordinates into positions relative to
   * the AnnotationToolbar's nearest positioned ancestor (NPA).
   *
   * Typically the AnnotationToolbar is a child of the `<body>` and the NPA is the root
   * `<html>` element. However page styling may make the `<body>` positioned.
   * See https://github.com/hypothesis/client/issues/487.
   *
   * @param {number} left - Horizontal offset from left edge of viewport.
   * @param {number} top - Vertical offset from top edge of viewport.
   */
  _toCoordsRelativeToNPA(left, top) {
    const positionedAncestor = nearestPositionedAncestor(this._container);
    const parentRect = positionedAncestor.getBoundingClientRect();
    return {
      left: left - parentRect.left,
      top: top - parentRect.top,
    };
  }
}
