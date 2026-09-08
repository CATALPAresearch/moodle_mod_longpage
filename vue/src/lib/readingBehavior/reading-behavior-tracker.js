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
 * @copyright  2026 Niels Seidel <niels.seidel@fernuni-hagen.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// =============================================================================
// TUNABLE CONSTANTS — this is the one place to adjust reading-behavior
// detection. Nothing below this block (and nothing in ReadingProgress.vue)
// hard-codes any of these numbers directly; change a value here and every
// classifier that uses it picks it up.
// =============================================================================

/**
 * Data-point labels: the classification of a single "baseline crossing"
 * (how long, and how, one element held the vertical center of the viewport).
 * Intentionally only ONE fast-pace label ("scan") is used, i.e. Carver's
 * "scanning" and "skimming" gears are NOT distinguished here — telling them
 * apart reliably from dwell time alone is questionable (see the discussion
 * of conflating reading *speed* with reading *intent*); splitting them would
 * add complexity without a clear payoff, so this is a deliberate simplification.
 */
export const DATA_POINT_LABELS = {
  SCAN: "scan", // faster than the element's own "skimming" pace estimate
  READ: "read", // within normal ("rauding") reading pace
  STUDY: "study", // slower than normal reading (Carver's learning/memorizing gears)
  REGRESSION: "regression", // net backward (upward) scroll while dwelling — re-reading
  PREVIEW: "preview", // fast burst across many, mostly-heading elements (structure overview)
};

/** Session-level labels: the dominant data-point label across one reading session. */
export const SESSION_LABELS = {
  SCANNING: "scanning",
  READING: "reading",
  STUDYING: "studying",
  REVIEWING: "reviewing",
  PREVIEWING: "previewing",
  UNSPECIFIC: "unspecific", // no label reached SESSION_DOMINANCE_RATIO
};

/** User-profile labels: the dominant session label across a user's sessions (this page load). */
export const USER_PROFILE_LABELS = {
  PICKING: "picking", // mostly "scanning" sessions — picks out fragments
  READING: "reading",
  ATTENTIVE: "attentive", // mostly "studying" sessions
  REVIEWING: "reviewing",
  ORIENTING: "orienting", // mostly "previewing" sessions
  UNSPECIFIC: "unspecific",
};

export const READING_BEHAVIOR_CONFIG = {
  // ---------------------------------------------------------------------
  // Language
  // ---------------------------------------------------------------------
  // Reading speed is language-dependent (see WPM_GEARS_BY_LANGUAGE below).
  // Longpage has no per-page *content*-language detection wired up yet —
  // resolveLanguage() below uses the <html lang="..."> attribute as a cheap
  // proxy (it happens to already be set correctly, see main.js), but if
  // that ever isn't set/reliable, this constant is the fallback. Treat it as
  // config, not as "automatic detection" — until real content-language
  // detection exists, this default IS what gets used most of the time.
  DEFAULT_LANGUAGE: "de",

  // ---------------------------------------------------------------------
  // Reading-speed gears (Carver's Rauding Theory: R.P. Carver, "Reading
  // Rate: A Review of Research and Theory", 1990) — words per minute for
  // five gears, fastest to slowest. Carver's numbers are for English; the
  // "de" row is an approximation and should be recalibrated once enough
  // longpage_reading_behavior_events rows have accumulated to fit real
  // data instead of guessing.
  // ---------------------------------------------------------------------
  WPM_GEARS_BY_LANGUAGE: {
    de: {
      scanning: 550,
      skimming: 400,
      reading: 260,
      learning: 180,
      memorizing: 120,
    },
    // Carver's original English-based values — used for any language not
    // explicitly listed above.
    default: {
      scanning: 600,
      skimming: 450,
      reading: 300,
      learning: 200,
      memorizing: 138,
    },
  },

  // Non-text elements (images, tables) don't have a word count, so the WPM
  // gears above don't apply to them. Instead a single "avg" dwell estimate
  // is computed (see below) and the min/max band is derived from it.
  IMAGE_SECONDS_PER_1000PX_DIAGONAL: 10, // sqrt(width^2 + height^2), thesis heuristic
  TABLE_SECONDS_PER_CELL: 1, // per <th>/<td>, thesis heuristic
  EMBEDDED_QUESTION_SECONDS: 15, // an interactive question, not prose — see EMBEDDED_QUESTION_PATTERN
  NON_TEXT_FAST_FACTOR: 0.4, // "scan" boundary = avg * this
  NON_TEXT_SLOW_FACTOR: 3, // "study" reaches up to avg * this

  // intersectionRatio = visible area / total element area, so for an
  // element TALLER than the viewport it can never reach anywhere near 1.0 —
  // its geometric maximum is capped at (viewportHeight / elementHeight),
  // reached only while the viewport sits entirely inside the element. A raw
  // ratio threshold would therefore misclassify a fully, attentively read
  // long paragraph as barely-glimpsed. So this is checked against
  // peakRatio normalized by that element-specific achievable maximum (see
  // _achievableMaxRatio), not against the raw ratio.
  MIN_PEAK_RATIO_FOR_READ: 0.5,

  // Coverage = how much of the element's OWN height has, cumulatively, ever
  // been inside the viewport during this dwell (see _updateCoverage) —
  // catches "read the first quarter of a long paragraph, then left" even
  // when peakRatio alone would look fine, and vice versa. Below this share,
  // the data point is forced to "scan" regardless of dwell time or peak.
  MIN_COVERAGE_FOR_READ: 0.6,

  // Regression (re-reading): net scroll movement during one baseline-dwell
  // interval that is upward by at least this many pixels marks the data
  // point as a "regression", overriding the timing-based label.
  REGRESSION_MIN_SCROLL_UP_PX: 40,

  // Previewing/mapping: a short burst of many, mostly-heading elements, each
  // barely glimpsed, checked over the last PREVIEW_WINDOW_SIZE data points
  // (including the current one).
  PREVIEW_WINDOW_SIZE: 6,
  PREVIEW_MIN_DISTINCT_ELEMENTS: 4,
  PREVIEW_MIN_HEADING_SHARE: 0.5, // fraction of the window that must be headings
  PREVIEW_MAX_PEAK_RATIO: 0.4, // average peak ratio across the window
  PREVIEW_MAX_WINDOW_SECONDS: 8, // the whole burst must be this quick

  // Disengagement / session boundaries — models the GAP *between* two
  // elements: by the time a NEW element becomes the baseline, the previous
  // one has already finished, so a hard cap here is fine (a long-enough gap
  // really does mean "was away"). If the gap since the previous data point
  // is longer than (that new element's own "memorizing" time * multiplier)
  // — clamped between a floor (so a two-word heading doesn't trigger a
  // false session break) and an absolute cap — a new session starts.
  DISENGAGEMENT_MULTIPLIER: 3,
  DISENGAGEMENT_MIN_SECONDS: 5,
  DISENGAGEMENT_MAX_SECONDS: 60,

  // "Still reading" cutoff — deliberately SEPARATE from DISENGAGEMENT_*
  // above, and used instead by the heartbeat (see HEARTBEAT_INTERVAL_MS).
  // This answers a different question: how long can dwelling on ONE
  // element continue before we checkpoint it, without assuming the person
  // left? A hard 60s-style cap would be wrong here — a long, dense
  // passage can legitimately take much longer than that to actually read,
  // so this scales with the element's own "memorizing" estimate too, but
  // with a much more generous cap (and no session break — see
  // _checkHeartbeat, this only checkpoints, it never ends the session).
  STILL_READING_MULTIPLIER: 2,
  STILL_READING_MIN_SECONDS: 10,
  STILL_READING_MAX_SECONDS: 300,

  // A data point is normally finalized when the baseline moves to the NEXT
  // element — which never happens for the last element on the page (there
  // is nothing further to scroll to), or while someone lingers a long time
  // on one (possibly long) element. This heartbeat periodically checks the
  // STILL_READING_* cutoff above and, if crossed, checkpoints the ongoing
  // dwell instead of waiting forever for a transition that may never come.
  HEARTBEAT_INTERVAL_MS: 3000,

  // Aggregation thresholds: a label needs at least this share of the
  // underlying data points / sessions to become the dominant label;
  // otherwise the result is "unspecific".
  SESSION_DOMINANCE_RATIO: 0.5,
  USER_PROFILE_DOMINANCE_RATIO: 0.5,
};

const HEADING_TAGS = new Set(["h2", "h3", "h4", "h5"]);

// filter_embedquestion (see filter/embedquestion/classes/text_filter.php)
// leaves raw "{Q{...}Q}" shortcode text in the DOM alongside/instead of the
// rendered question iframe. That text is not prose — counting it as "words"
// for the reading-speed model below would produce a meaningless estimate —
// so such elements get the same fixed avg/min/max treatment as images/tables
// instead of a word count.
const EMBEDDED_QUESTION_PATTERN = /\{Q\{[\s\S]*?\}Q\}/;

/**
 * Strip embedquestion shortcode tokens from a text preview, replacing each
 * with a short placeholder — used for the debug console output so a preview
 * never shows raw "{Q{...}Q}" (or a mid-token truncation of it).
 *
 * @param {string} text
 * @returns {string}
 */
export function cleanTextPreview(text) {
  return (text || "").replace(new RegExp(EMBEDDED_QUESTION_PATTERN, "g"), "[Frage]");
}

/**
 * Cheap content-language guess: the <html lang> attribute, normalised to its
 * primary subtag (e.g. "de-DE" -> "de"). Falls back to
 * READING_BEHAVIOR_CONFIG.DEFAULT_LANGUAGE when unset or not in
 * WPM_GEARS_BY_LANGUAGE — see the DEFAULT_LANGUAGE comment above.
 *
 * @param {typeof READING_BEHAVIOR_CONFIG} config
 * @returns {string}
 */
export function resolveLanguage(config) {
  const raw = (document.documentElement.lang || "").split(/[-_]/)[0].toLowerCase();
  return Object.prototype.hasOwnProperty.call(config.WPM_GEARS_BY_LANGUAGE, raw)
    ? raw
    : config.DEFAULT_LANGUAGE;
}

/**
 * Per-element expected reading time, in seconds, at each of the five gears
 * (or, for images/tables, a single avg estimate spread into a fast/slow band).
 *
 * @param {Element} el
 * @param {string} language
 * @param {typeof READING_BEHAVIOR_CONFIG} config
 * @returns {{words:number, scanningTime:number, skimmingTime:number, readingTime:number, learningTime:number, memorizingTime:number}}
 */
export function estimateReadingTime(el, language, config) {
  const tag = el.tagName.toLowerCase();

  if (tag === "img") {
    const diagonal = Math.sqrt(el.offsetWidth ** 2 + el.offsetHeight ** 2);
    const avg = (diagonal / 1000) * config.IMAGE_SECONDS_PER_1000PX_DIAGONAL;
    return nonTextEstimate(avg, config);
  }

  if (tag === "table") {
    const cellCount = el.querySelectorAll("th, td").length || 1;
    const avg = cellCount * config.TABLE_SECONDS_PER_CELL;
    return nonTextEstimate(avg, config);
  }

  if (
    el.querySelector(".filter_embedquestion-iframe") ||
    EMBEDDED_QUESTION_PATTERN.test(el.textContent || "")
  ) {
    return nonTextEstimate(config.EMBEDDED_QUESTION_SECONDS, config);
  }

  const words = (el.textContent || "").trim().split(/\s+/).filter(Boolean).length;
  const gears =
    config.WPM_GEARS_BY_LANGUAGE[language] || config.WPM_GEARS_BY_LANGUAGE.default;
  const secondsAt = (wpm) => (words / Math.max(1, wpm)) * 60;

  return {
    words,
    scanningTime: secondsAt(gears.scanning),
    skimmingTime: secondsAt(gears.skimming),
    readingTime: secondsAt(gears.reading),
    learningTime: secondsAt(gears.learning),
    memorizingTime: secondsAt(gears.memorizing),
  };
}

function nonTextEstimate(avgSeconds, config) {
  return {
    words: 0,
    scanningTime: avgSeconds * config.NON_TEXT_FAST_FACTOR,
    skimmingTime: avgSeconds * config.NON_TEXT_FAST_FACTOR,
    readingTime: avgSeconds,
    learningTime: avgSeconds * config.NON_TEXT_SLOW_FACTOR,
    memorizingTime: avgSeconds * config.NON_TEXT_SLOW_FACTOR,
  };
}

function classifyDwell(dwellSeconds, estimate) {
  if (dwellSeconds <= estimate.skimmingTime) {
    return DATA_POINT_LABELS.SCAN;
  }
  if (dwellSeconds <= estimate.readingTime) {
    return DATA_POINT_LABELS.READ;
  }
  return DATA_POINT_LABELS.STUDY;
}

function generateSessionId() {
  if (window.crypto && typeof window.crypto.randomUUID === "function") {
    return window.crypto.randomUUID();
  }
  // Fallback for browsers without crypto.randomUUID (not cryptographically
  // strong, but this is only ever used to group log rows, not as a secret).
  return "sid-" + Date.now().toString(36) + "-" + Math.random().toString(36).slice(2, 10);
}

/**
 * Tracks which observed element currently sits at the vertical midpoint of
 * the viewport ("baseline crossing"), turns each baseline change into a
 * classified data point (scan/read/study/regression/preview), groups data
 * points into disengagement-bounded sessions, and aggregates session/user
 * labels — a three-tier pipeline (data point -> session -> user profile).
 *
 * All detection thresholds live in READING_BEHAVIOR_CONFIG above.
 */
export class ReadingBehaviorTracker {
  /**
   * @param {object} options
   * @param {() => Element} options.getViewportContainer  element whose visible
   *   height defines the viewport used to find the baseline midpoint
   *   (falls back to `window` if it returns a falsy value)
   * @param {(dataPoint: object) => void} [options.onDataPoint]  called once
   *   per finalized data point
   * @param {(reason: string) => void} [options.onSessionStart]
   * @param {(raw: object) => void} [options.onRawEntry]  called for EVERY
   *   IntersectionObserver entry of every observed element, independent of
   *   classification — one point per threshold crossing, including the
   *   intersectionRatio ("percentage of the element visible on screen").
   *   Intended for a raw, unopinionated telemetry log (see
   *   ReadingProgress.vue's persistRawIntersectionEvent) that lets an
   *   offline analysis (R/Python) recompute its own metrics instead of being
   *   limited to whatever this file's classification constants produce.
   * @param {typeof READING_BEHAVIOR_CONFIG} [options.config]
   */
  constructor({ getViewportContainer, onDataPoint, onSessionStart, onBaselineEnter, onRawEntry, config }) {
    this.getViewportContainer = getViewportContainer;
    this.onDataPoint = onDataPoint || (() => {});
    this.onSessionStart = onSessionStart || (() => {});
    // A data point is only emitted once the baseline element is LEFT again
    // (see _finalizeCurrentBaseline) — correct for classification, but it
    // means nothing gets logged while someone dwells on the very first
    // element, or on any one element for a long time. onBaselineEnter fires
    // immediately whenever tracking moves onto a (new) element, purely so
    // there is visible confirmation that the tracker is alive; it carries no
    // classification and is never persisted.
    this.onBaselineEnter = onBaselineEnter || (() => {});
    this.onRawEntry = onRawEntry || (() => {});
    this.config = config || READING_BEHAVIOR_CONFIG;
    this.language = resolveLanguage(this.config);

    /** @type {Map<string, {el:Element, tag:string, rect:DOMRect, ratio:number, peakRatio:number, estimate:object}>} */
    this.elements = new Map();

    this.currentBaselineId = null;
    this.baselineEnteredAt = null;
    this.baselineScrollTopAtEnter = null;

    /** Rolling history of finalized data points, for preview-burst detection. */
    this.recentPoints = [];
    /** All finalized data points of the CURRENT page load, for aggregation. */
    this.allPoints = [];
    /** sessionId -> array of data points, for session/user aggregation. */
    this.sessionsById = new Map();

    this._boundOnFocus = () => this.startNewSession("window-focus");
    window.addEventListener("focus", this._boundOnFocus);
    // Deliberately NOT listening to visibilitychange (tab switch / screen
    // lock): an earlier attempt at this (in a related thesis project) was
    // tried and reverted as unreliable, so it is not reproduced here.

    this.startNewSession("initial");

    // See HEARTBEAT_INTERVAL_MS above: without this, dwelling on the LAST
    // element on the page (nothing to transition to) or simply going idle
    // without triggering a new intersection entry would never finalize a
    // data point at all.
    this._heartbeatInterval = setInterval(() => {
      this._checkHeartbeat();
    }, this.config.HEARTBEAT_INTERVAL_MS);
  }

  _checkHeartbeat() {
    if (!this.currentBaselineId || !this.baselineEnteredAt) {
      return;
    }
    const state = this.elements.get(this.currentBaselineId);
    if (!state) {
      return;
    }
    const dwellSoFar = (Date.now() - this.baselineEnteredAt) / 1000;
    if (dwellSoFar > this._stillReadingCutoffSeconds(state.estimate)) {
      // Checkpoint only — NOT a new session. Genuine "went away" is caught
      // separately by _isDisengagementGap once a genuinely new element
      // becomes the baseline (see DISENGAGEMENT_* vs STILL_READING_*
      // comments above); this just stops one dwell from growing forever.
      this.flushCurrentBaseline();
    }
  }

  /**
   * Finalize whatever is currently being dwelled on right now, without
   * waiting for a baseline transition — used by the heartbeat above and by
   * a page-unload handler (see ReadingProgress.vue), so the LAST interval of
   * a reading session is never silently dropped. Dwell tracking on the same
   * element then restarts from now, in case reading continues afterwards.
   *
   * Callers don't need to pass anything — the resulting data point flows
   * through the normal onDataPoint callback like any other, already visible
   * via the debug console log wired up in ReadingProgress.vue without this
   * method needing its own logging. This never itself starts a new session
   * (see _checkHeartbeat) — it only checkpoints an ongoing dwell.
   */
  flushCurrentBaseline() {
    if (!this.currentBaselineId) {
      return;
    }
    this._finalizeCurrentBaseline();
    this.baselineEnteredAt = Date.now();
    this.baselineScrollTopAtEnter = this._getScrollTop();
  }

  /** Call once with the current element registered for observation. */
  registerElement(el) {
    const id = el.id;
    this.elements.set(id, {
      el,
      tag: el.tagName.toLowerCase(),
      rect: el.getBoundingClientRect(),
      ratio: 0,
      peakRatio: 0,
      // Coverage tracking (see _updateCoverage/MIN_COVERAGE_FOR_READ): the
      // range of the element's OWN height (0 = its top, height = its
      // bottom) that has been inside the viewport at any point during the
      // CURRENT dwell. Reset after each finalize, so it reflects "this
      // visit", not the element's whole lifetime on the page.
      minVisibleLocal: Infinity,
      maxVisibleLocal: -Infinity,
      estimate: estimateReadingTime(el, this.language, this.config),
    });
  }

  /**
   * Widen [minVisibleLocal, maxVisibleLocal] with the slice of the element
   * (in the element's own coordinates, 0 = top) that is visible right now.
   */
  _updateCoverage(state, rect) {
    const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
    const localTop = Math.max(0, -rect.top);
    const localBottom = Math.min(rect.height, viewportHeight - rect.top);
    if (localBottom <= localTop) {
      return;
    }
    state.minVisibleLocal = Math.min(state.minVisibleLocal, localTop);
    state.maxVisibleLocal = Math.max(state.maxVisibleLocal, localBottom);
  }

  /**
   * How visible an element could EVER get, given its own height vs. the
   * viewport — intersectionRatio is capped at this for anything taller
   * than the viewport (see MIN_PEAK_RATIO_FOR_READ above).
   */
  _achievableMaxRatio(elementHeight) {
    const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
    return elementHeight > 0 ? Math.min(1, viewportHeight / elementHeight) : 1;
  }

  /** Feed one IntersectionObserver callback's entries into the tracker. */
  handleIntersections(entries) {
    const scrollTop = this._getScrollTop();
    for (const entry of entries) {
      const id = entry.target.id;
      const state = this.elements.get(id);
      if (!state) {
        continue;
      }
      state.rect = entry.boundingClientRect;
      // A ratio of 0 (fully left the viewport) naturally excludes this
      // element from the midpoint search in _computeBaselineId below, while
      // its accumulated peakRatio is kept around in `this.elements` in case
      // it is still the current baseline (_finalizeCurrentBaseline reads it).
      state.ratio = entry.intersectionRatio;
      state.peakRatio = Math.max(state.peakRatio, entry.intersectionRatio);
      if (entry.intersectionRatio > 0) {
        this._updateCoverage(state, entry.boundingClientRect);
      }

      // Raw, unopinionated telemetry — one point per threshold crossing,
      // independent of the baseline/session/label pipeline below.
      this.onRawEntry({
        id,
        tag: state.tag,
        ratio: entry.intersectionRatio,
        rect: entry.boundingClientRect,
        viewportHeight: window.innerHeight,
        scrollTop,
        wordCount: state.estimate.words,
        sessionId: this.sessionId,
        timestamp: Date.now(),
      });
    }
    this._maybeTransitionBaseline();
  }

  _getScrollTop() {
    const container = this.getViewportContainer && this.getViewportContainer();
    return container ? container.scrollTop : window.scrollY;
  }

  _getViewportMidpointY() {
    const container = this.getViewportContainer && this.getViewportContainer();
    if (container) {
      const rect = container.getBoundingClientRect();
      return rect.top + rect.height / 2;
    }
    return window.innerHeight / 2;
  }

  _computeBaselineId() {
    const midY = this._getViewportMidpointY();
    let straddling = null;
    let closest = null;
    let closestDistance = Infinity;

    for (const [id, state] of this.elements) {
      if (state.ratio <= 0) {
        continue;
      }
      if (state.rect.top <= midY && midY <= state.rect.bottom) {
        straddling = id;
        break;
      }
      const center = (state.rect.top + state.rect.bottom) / 2;
      const distance = Math.abs(center - midY);
      if (distance < closestDistance) {
        closestDistance = distance;
        closest = id;
      }
    }

    return straddling || closest;
  }

  _maybeTransitionBaseline() {
    const nextId = this._computeBaselineId();
    if (!nextId || nextId === this.currentBaselineId) {
      return;
    }

    if (this.currentBaselineId) {
      this._finalizeCurrentBaseline();
    }

    this.currentBaselineId = nextId;
    this.baselineEnteredAt = Date.now();
    this.baselineScrollTopAtEnter = this._getScrollTop();

    const state = this.elements.get(nextId);
    if (state) {
      this.onBaselineEnter({ id: nextId, tag: state.tag });
    }
  }

  _finalizeCurrentBaseline() {
    const state = this.elements.get(this.currentBaselineId);
    if (!state) {
      return;
    }

    const now = Date.now();
    const dwellSeconds = (now - this.baselineEnteredAt) / 1000;
    const gapSeconds = this.allPoints.length
      ? (this.baselineEnteredAt - this.allPoints[this.allPoints.length - 1].finalizedAt) / 1000
      : 0;

    if (this._isDisengagementGap(gapSeconds, state.estimate)) {
      this.startNewSession("disengagement");
    }

    const netScrollPx = this._getScrollTop() - this.baselineScrollTopAtEnter;

    const elementHeight = state.rect.height || 1;
    const coverageRatio =
      state.maxVisibleLocal > state.minVisibleLocal
        ? Math.min(1, (state.maxVisibleLocal - state.minVisibleLocal) / elementHeight)
        : 0;
    // Coverage is "this visit", not the element's whole lifetime — reset so
    // a later re-visit (e.g. after a regression) starts fresh.
    state.minVisibleLocal = Infinity;
    state.maxVisibleLocal = -Infinity;

    const dataPoint = {
      id: this.currentBaselineId,
      tag: state.tag,
      words: state.estimate.words,
      dwellSeconds,
      peakRatio: state.peakRatio,
      achievableMaxRatio: this._achievableMaxRatio(elementHeight),
      coverageRatio,
      estimate: state.estimate,
      enteredAt: this.baselineEnteredAt,
      finalizedAt: now,
      sessionId: this.sessionId,
      language: this.language,
    };

    dataPoint.label = this._classifyDataPoint(dataPoint, netScrollPx);

    this.recentPoints.push(dataPoint);
    if (this.recentPoints.length > this.config.PREVIEW_WINDOW_SIZE) {
      this.recentPoints.shift();
    }
    this.allPoints.push(dataPoint);
    this.sessionsById.get(this.sessionId).push(dataPoint);

    this.onDataPoint(dataPoint);
  }

  _disengagementCutoffSeconds(estimate) {
    return Math.min(
      this.config.DISENGAGEMENT_MAX_SECONDS,
      Math.max(
        estimate.memorizingTime * this.config.DISENGAGEMENT_MULTIPLIER,
        this.config.DISENGAGEMENT_MIN_SECONDS,
      ),
    );
  }

  _isDisengagementGap(gapSeconds, estimate) {
    return gapSeconds > 0 && gapSeconds > this._disengagementCutoffSeconds(estimate);
  }

  _stillReadingCutoffSeconds(estimate) {
    return Math.min(
      this.config.STILL_READING_MAX_SECONDS,
      Math.max(
        estimate.memorizingTime * this.config.STILL_READING_MULTIPLIER,
        this.config.STILL_READING_MIN_SECONDS,
      ),
    );
  }

  _classifyDataPoint(dataPoint, netScrollPx) {
    if (netScrollPx <= -this.config.REGRESSION_MIN_SCROLL_UP_PX) {
      return DATA_POINT_LABELS.REGRESSION;
    }
    if (this._isPreviewBurst(dataPoint)) {
      return DATA_POINT_LABELS.PREVIEW;
    }
    // Normalize peakRatio against what was even geometrically achievable
    // for this element's height (see _achievableMaxRatio) — otherwise a
    // long paragraph could never pass this gate at all. Coverage catches
    // the complementary case: high peak, but only a fraction of a long
    // element was ever scrolled through (e.g. abandoned before the last
    // quarter) before this dwell ended.
    const normalizedPeak =
      dataPoint.achievableMaxRatio > 0 ? dataPoint.peakRatio / dataPoint.achievableMaxRatio : 0;
    if (
      normalizedPeak < this.config.MIN_PEAK_RATIO_FOR_READ ||
      dataPoint.coverageRatio < this.config.MIN_COVERAGE_FOR_READ
    ) {
      return DATA_POINT_LABELS.SCAN;
    }
    return classifyDwell(dataPoint.dwellSeconds, dataPoint.estimate);
  }

  _isPreviewBurst(currentPoint) {
    const window_ = this.recentPoints
      .slice(-(this.config.PREVIEW_WINDOW_SIZE - 1))
      .concat([currentPoint]);

    const distinctIds = new Set(window_.map((p) => p.id));
    if (distinctIds.size < this.config.PREVIEW_MIN_DISTINCT_ELEMENTS) {
      return false;
    }

    const headingShare =
      window_.filter((p) => HEADING_TAGS.has(p.tag)).length / window_.length;
    if (headingShare < this.config.PREVIEW_MIN_HEADING_SHARE) {
      return false;
    }

    const avgPeakRatio =
      window_.reduce((sum, p) => sum + p.peakRatio, 0) / window_.length;
    if (avgPeakRatio > this.config.PREVIEW_MAX_PEAK_RATIO) {
      return false;
    }

    const windowSeconds = (currentPoint.finalizedAt - window_[0].enteredAt) / 1000;
    return windowSeconds <= this.config.PREVIEW_MAX_WINDOW_SECONDS;
  }

  /** Start a fresh reading session (new UUID), e.g. after disengagement or refocus. */
  startNewSession(reason) {
    this.sessionId = generateSessionId();
    this.sessionsById.set(this.sessionId, []);
    this.recentPoints = [];
    this.onSessionStart(reason);
  }

  /**
   * Dominant data-point label -> session label, for one session.
   *
   * @param {string} [sessionId]  defaults to the current session
   */
  getSessionLabel(sessionId = this.sessionId) {
    const points = this.sessionsById.get(sessionId) || [];
    const dominant = dominantLabel(
      points.map((p) => p.label),
      this.config.SESSION_DOMINANCE_RATIO,
    );
    return (
      {
        [DATA_POINT_LABELS.SCAN]: SESSION_LABELS.SCANNING,
        [DATA_POINT_LABELS.READ]: SESSION_LABELS.READING,
        [DATA_POINT_LABELS.STUDY]: SESSION_LABELS.STUDYING,
        [DATA_POINT_LABELS.REGRESSION]: SESSION_LABELS.REVIEWING,
        [DATA_POINT_LABELS.PREVIEW]: SESSION_LABELS.PREVIEWING,
      }[dominant] || SESSION_LABELS.UNSPECIFIC
    );
  }

  /**
   * Dominant session label -> user-profile label.
   *
   * Note: this only ever sees the sessions from the CURRENT page load — a
   * profile that holds across visits/page loads would need a read endpoint
   * that re-fetches and aggregates a user's past longpage_reading_behavior_
   * events rows from the server, which is a natural next step but out of
   * scope here (see mod_longpage_log_reading_behavior_event, write-only).
   */
  getUserProfileLabel() {
    const sessionLabels = [...this.sessionsById.keys()].map((id) => this.getSessionLabel(id));
    const dominant = dominantLabel(sessionLabels, this.config.USER_PROFILE_DOMINANCE_RATIO);
    return (
      {
        [SESSION_LABELS.SCANNING]: USER_PROFILE_LABELS.PICKING,
        [SESSION_LABELS.READING]: USER_PROFILE_LABELS.READING,
        [SESSION_LABELS.STUDYING]: USER_PROFILE_LABELS.ATTENTIVE,
        [SESSION_LABELS.REVIEWING]: USER_PROFILE_LABELS.REVIEWING,
        [SESSION_LABELS.PREVIEWING]: USER_PROFILE_LABELS.ORIENTING,
      }[dominant] || USER_PROFILE_LABELS.UNSPECIFIC
    );
  }

  destroy() {
    window.removeEventListener("focus", this._boundOnFocus);
    clearInterval(this._heartbeatInterval);
  }
}

function dominantLabel(labels, dominanceRatio) {
  if (!labels.length) {
    return null;
  }
  const counts = new Map();
  for (const label of labels) {
    counts.set(label, (counts.get(label) || 0) + 1);
  }
  let best = null;
  let bestCount = 0;
  for (const [label, count] of counts) {
    if (count > bestCount) {
      best = label;
      bestCount = count;
    }
  }
  return bestCount / labels.length >= dominanceRatio ? best : null;
}
