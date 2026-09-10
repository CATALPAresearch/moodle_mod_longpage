<template>
  <div class="teacher-dashboard-container">
    <button
      v-if="!hideButton"
      type="button"
      class="btn btn-secondary btn-sm d-flex align-items-center"
      :aria-label="$t('features.teacherDashboard.label') || 'Teacher Dashboard'"
      :title="$t('features.teacherDashboard.label') || 'Teacher Dashboard'"
      @click="openDashboard"
    >
      <i class="fa fa-bar-chart mr-1" aria-hidden="true" />
      <span>{{ $t("features.teacherDashboard.button") || "Analytics" }}</span>
    </button>

    <!-- Modal -->
    <div
      v-if="isOpen"
      class="dashboard-modal-overlay"
      @click.self="closeDashboard"
    >
      <div class="dashboard-modal">
        <div class="dashboard-header">
          <h3>
            {{ $t("features.teacherDashboard.title") || "Teacher Dashboard" }}
          </h3>
          <div class="dashboard-controls">
            <select
              v-model="selectedSemester"
              class="form-control form-control-sm"
              @change="loadAnalytics"
            >
              <option v-for="sem in semesters" :key="sem.label" :value="sem">
                {{ sem.label }}
              </option>
            </select>
            <button
              type="button"
              class="btn btn-sm btn-secondary ml-2"
              @click="closeDashboard"
            >
              <i class="fa fa-times" aria-hidden="true" />
            </button>
          </div>
        </div>

        <div
          v-if="courseLongpages.length > 1"
          class="dashboard-instance-selector"
        >
          <label class="instance-selector-label mb-0 mr-2">
            {{
              $t("features.teacherDashboard.instancesLabel") ||
              "Longpage instances"
            }}
          </label>

          <div ref="instanceDropdown" class="dropdown instance-dropdown">
            <input
              v-model="instanceSearchQuery"
              type="text"
              class="form-control form-control-sm"
              :placeholder="
                $t('features.teacherDashboard.instancesPlaceholder') ||
                'Type to search instances…'
              "
              @focus="instanceDropdownOpen = true"
            />
            <div
              class="dropdown-menu instance-dropdown-menu"
              :class="{ show: instanceDropdownOpen }"
            >
              <div
                v-if="filteredLongpages.length"
                role="listbox"
                :aria-label="
                  $t('features.teacherDashboard.instancesLabel') ||
                  'Longpage instances'
                "
              >
                <div
                  v-for="lp in filteredLongpages"
                  :key="lp.id"
                  class="form-check px-3 py-1 mb-0"
                >
                  <input
                    :id="'instance-option-' + lp.id"
                    type="checkbox"
                    class="form-check-input"
                    :checked="selectedLongpageIds.includes(lp.id)"
                    @change="toggleLongpageSelection(lp.id)"
                  />
                  <label
                    class="form-check-label"
                    :for="'instance-option-' + lp.id"
                  >
                    {{ lp.name }}
                  </label>
                </div>
              </div>
              <p v-else class="px-3 py-1 mb-0 text-muted">
                {{
                  $t("features.teacherDashboard.instancesNotFound") ||
                  "No matching instance"
                }}
              </p>
            </div>
          </div>

          <div class="instance-pills">
            <span
              v-for="lp in selectedLongpageObjects"
              :key="lp.id"
              class="badge badge-pill instance-pill"
            >
              {{ lp.name }}
              <button
                type="button"
                class="instance-pill-remove"
                :aria-label="
                  $t('features.teacherDashboard.removeInstance') || 'Remove'
                "
                @click="toggleLongpageSelection(lp.id)"
              >
                &times;
              </button>
            </span>
          </div>

          <button
            type="button"
            class="btn btn-sm btn-outline-secondary ml-2"
            @click="selectAllLongpages"
          >
            {{
              $t("features.teacherDashboard.selectAllInstances") ||
              "Select all"
            }}
          </button>
        </div>

        <div v-if="isLoading" class="dashboard-loading">
          <i class="fa fa-spinner fa-spin fa-2x" aria-hidden="true" />
          <p>
            {{
              $t("features.teacherDashboard.loading") || "Loading analytics..."
            }}
          </p>
        </div>

        <div v-else-if="error" class="dashboard-error alert alert-danger">
          {{ error }}
        </div>

        <div
          v-else-if="selectedLongpageIds.length === 0"
          class="dashboard-error alert alert-warning"
        >
          {{
            $t("features.teacherDashboard.noInstanceSelected") ||
            "Select at least one longpage instance to see analytics."
          }}
        </div>

        <div v-else class="dashboard-content">
          <!-- Chart 1: Weekly Activity -->
          <div class="chart-container">
            <h4>
              {{
                $t("features.teacherDashboard.weeklyActivityTitle") ||
                "Weekly Activity"
              }}
            </h4>
            <canvas ref="weeklyActivityChart"></canvas>
          </div>

          <!-- Chart 2: User Engagement -->
          <div class="chart-container">
            <h4>
              {{
                $t("features.teacherDashboard.userEngagementTitle") ||
                "User Engagement"
              }}
            </h4>
            <canvas ref="userEngagementChart"></canvas>
          </div>

          <!-- Chart 3: Reading Distribution -->
          <div class="chart-container">
            <h4>
              {{
                $t("features.teacherDashboard.readingDistributionTitle") ||
                "Reading Position Distribution"
              }}
            </h4>
            <canvas ref="readingDistributionChart"></canvas>
          </div>

          <!-- Chart 4: Reading Behavior Over the Semester -->
          <div class="chart-container">
            <h4>
              {{
                $t("features.teacherDashboard.readingBehaviorTrendTitle") ||
                "Reading Behavior Over the Semester"
              }}
            </h4>
            <canvas ref="readingBehaviorTrendChart"></canvas>
          </div>

          <!-- Per-student reading behavior table -->
          <div class="chart-container">
            <h4>
              {{
                $t("features.teacherDashboard.readingBehaviorByStudentTitle") ||
                "Reading Behavior by Student"
              }}
            </h4>
            <table
              v-if="
                analyticsData.readingbehaviorbystudent &&
                analyticsData.readingbehaviorbystudent.length
              "
              class="table table-sm table-hover reading-behavior-table"
            >
              <thead>
                <tr>
                  <th
                    v-for="col in studentTableColumns"
                    :key="col.key"
                    role="button"
                    tabindex="0"
                    :title="col.help"
                    @click="sortStudentsBy(col.key)"
                    @keydown.enter="sortStudentsBy(col.key)"
                  >
                    {{ col.label
                    }}<i
                      v-if="col.help"
                      class="fa fa-info-circle ml-1 text-muted"
                      aria-hidden="true"
                    /><i
                      v-if="studentSortKey === col.key"
                      class="fa ml-1"
                      :class="studentSortAsc ? 'fa-caret-up' : 'fa-caret-down'"
                      aria-hidden="true"
                    />
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="student in sortedStudentBehavior"
                  :key="student.userid"
                >
                  <td>{{ student.fullname }}</td>
                  <td>{{ student.scan }}%</td>
                  <td>{{ student.read }}%</td>
                  <td>{{ student.study }}%</td>
                  <td>{{ student.regression }}%</td>
                  <td>{{ student.preview }}%</td>
                  <td>{{ student.avgcoverage }}%</td>
                  <td>{{ student.highlights }}</td>
                  <td>{{ student.bookmarks }}</td>
                  <td>{{ student.notes }}</td>
                  <td>{{ student.comments }}</td>
                  <td>{{ formatLastActive(student.lastactive) }}</td>
                </tr>
              </tbody>
            </table>
            <p v-else class="text-muted mb-0">
              {{
                $t("features.teacherDashboard.noReadingBehaviorData") ||
                "No reading-behavior data yet for this semester."
              }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { GET } from "@/store/types";
import { mapGetters } from "vuex";
import moodleAjax from "core/ajax";
import { Chart, registerables } from "chart.js";

// Register all Chart.js components
Chart.register(...registerables);

// Draws a vertical reference line (+ small label) at week indices where a
// calendar year or a semester begins — there's no bundled Chart.js
// annotation plugin in this project, and a couple of dozen lines of canvas
// drawing is simpler than adding chartjs-plugin-annotation as a dependency
// for this one feature.
const boundaryLinePlugin = {
  id: "boundaryLines",
  afterDraw(chart, _args, opts) {
    const boundaries = opts && opts.boundaries;
    if (!boundaries || !boundaries.length) return;

    const { ctx, chartArea, scales } = chart;
    const xScale = scales.x;
    if (!xScale || !chartArea) return;

    // Uniform category width, used to place the line at the LEFT edge of
    // the boundary's bar/tick rather than through its center.
    const categoryWidth = xScale.getPixelForValue(1) - xScale.getPixelForValue(0);

    ctx.save();
    boundaries.forEach(({ index, label, type }) => {
      const x = xScale.getPixelForValue(index) - categoryWidth / 2;
      ctx.beginPath();
      ctx.lineWidth = type === "year" ? 1.5 : 1;
      ctx.strokeStyle = type === "year" ? "#495057" : "#adb5bd";
      ctx.setLineDash(type === "year" ? [] : [4, 3]);
      ctx.moveTo(x, chartArea.top);
      ctx.lineTo(x, chartArea.bottom);
      ctx.stroke();

      ctx.setLineDash([]);
      ctx.fillStyle = type === "year" ? "#495057" : "#868e96";
      ctx.font = "10px sans-serif";
      ctx.textAlign = "left";
      ctx.textBaseline = "top";
      ctx.fillText(label, x + 3, chartArea.top + 2);
    });
    ctx.restore();
  },
};
Chart.register(boundaryLinePlugin);

export default {
  name: "TeacherDashboard",
  props: {
    hideButton: { type: Boolean, default: false },
  },
  data() {
    return {
      isOpen: false,
      isLoading: false,
      error: null,
      semesters: [],
      selectedSemester: null,
      courseLongpages: [],
      selectedLongpageIds: [],
      instanceSearchQuery: "",
      instanceDropdownOpen: false,
      analyticsData: null,
      charts: {
        weeklyActivity: null,
        userEngagement: null,
        readingDistribution: null,
        readingBehaviorTrend: null,
      },
      studentSortKey: "fullname",
      studentSortAsc: true,
    };
  },
  computed: {
    ...mapGetters({ context: GET.LONGPAGE_CONTEXT }),
    canViewDashboard() {
      return this.context?.isAdmin || this.context?.canModAnnotations;
    },
    filteredLongpages() {
      const query = this.instanceSearchQuery.trim().toLowerCase();
      if (!query) return this.courseLongpages;
      return this.courseLongpages.filter((lp) =>
        lp.name.toLowerCase().includes(query),
      );
    },
    // Always resolved from courseLongpages, never from a raw id, so a
    // selected instance's pill can only ever show its name.
    selectedLongpageObjects() {
      return this.courseLongpages.filter((lp) =>
        this.selectedLongpageIds.includes(lp.id),
      );
    },
    // A computed property (not static data()) so column labels/explanations
    // go through $t() — same dotted-path -> underscore lang-string
    // convention as everywhere else in this component.
    studentTableColumns() {
      const label = (key, fallback) =>
        this.$t(`features.teacherDashboard.chartLabels.${key}`) || fallback;
      const help = (key, fallback) =>
        this.$t(`features.teacherDashboard.columnHelp.${key}`) || fallback;
      return [
        { key: "fullname", label: label("student", "Student"), help: null },
        {
          key: "scan",
          label: label("scan", "Scan"),
          help: help("scan", "Share of dwell time spent briefly scanning past this content."),
        },
        {
          key: "read",
          label: label("read", "Read"),
          help: help("read", "Share spent reading at a normal pace."),
        },
        {
          key: "study",
          label: label("study", "Study"),
          help: help("study", "Share spent studying closely, well above normal reading pace."),
        },
        {
          key: "regression",
          label: label("regression", "Regression"),
          help: help("regression", "Share spent scrolling back up to re-read — often a sign of confusion or double-checking."),
        },
        {
          key: "preview",
          label: label("preview", "Preview"),
          help: help("preview", "Share classified as a brief preview glance, not sustained reading."),
        },
        {
          key: "avgcoverage",
          label: label("avgCoverage", "Ø Coverage"),
          help: help("avgCoverage", "On average, how much of each element's own height was actually scrolled into view."),
        },
        {
          key: "highlights",
          label: label("highlights", "Highlights"),
          help: help("highlights", "Number of text passages the student highlighted."),
        },
        {
          key: "bookmarks",
          label: label("bookmarks", "Bookmarks"),
          help: help("bookmarks", "Number of page-location bookmarks the student set."),
        },
        {
          key: "notes",
          label: label("notes", "Notes"),
          help: help("notes", "Number of personal, non-public notes the student wrote."),
        },
        {
          key: "comments",
          label: label("comments", "Comments"),
          help: help("comments", "Number of public comments the student posted."),
        },
        {
          key: "lastactive",
          label: label("lastActive", "Last active"),
          help: help("lastActive", "Date of the student's most recent classified reading activity."),
        },
      ];
    },
    sortedStudentBehavior() {
      const rows = this.analyticsData?.readingbehaviorbystudent || [];
      const key = this.studentSortKey;
      const dir = this.studentSortAsc ? 1 : -1;
      return [...rows].sort((a, b) => {
        if (typeof a[key] === "string") {
          return a[key].localeCompare(b[key]) * dir;
        }
        return (a[key] - b[key]) * dir;
      });
    },
  },
  methods: {
    async openDashboard() {
      if (!this.canViewDashboard) {
        return;
      }
      this.isOpen = true;
      this.isLoading = true;
      this.error = null;

      try {
        await Promise.all([this.loadSemesters(), this.loadCourseLongpages()]);
        if (this.semesters.length > 0) {
          this.selectedSemester = this.semesters[this.semesters.length - 1];
          await this.loadAnalytics();
        }
      } catch (err) {
        console.error("Failed to load dashboard:", err);
        this.error =
          this.$t("features.teacherDashboard.errorLoading") ||
          "Failed to load analytics data.";
      } finally {
        this.isLoading = false;
      }
    },
    closeDashboard() {
      this.isOpen = false;
      this.destroyCharts();
    },
    async loadSemesters() {
      const request = {
        methodname: "mod_longpage_get_available_semesters",
        args: {
          longpageid: this.context.longpageid,
        },
      };
      this.semesters = await moodleAjax.call([request])[0];
    },
    // Populates the multiselect at the top of the dashboard with every
    // longpage instance in the current course; defaults to just the
    // instance the teacher opened the dashboard from.
    async loadCourseLongpages() {
      const request = {
        methodname: "mod_longpage_get_course_longpages",
        args: {
          courseid: this.context.courseId,
        },
      };
      this.courseLongpages = await moodleAjax.call([request])[0];
      this.selectedLongpageIds = [this.context.longpageid];
    },
    selectAllLongpages() {
      this.selectedLongpageIds = this.courseLongpages.map((lp) => lp.id);
      this.loadAnalytics();
    },
    toggleLongpageSelection(id) {
      this.selectedLongpageIds = this.selectedLongpageIds.includes(id)
        ? this.selectedLongpageIds.filter((existing) => existing !== id)
        : [...this.selectedLongpageIds, id];
      this.loadAnalytics();
    },
    closeInstanceDropdown() {
      this.instanceDropdownOpen = false;
    },
    handleInstanceDropdownOutsideClick(event) {
      const container = this.$refs.instanceDropdown;
      if (container && !container.contains(event.target)) {
        this.closeInstanceDropdown();
      }
    },
    async loadAnalytics() {
      if (!this.selectedSemester || this.selectedLongpageIds.length === 0) {
        return;
      }

      this.isLoading = true;
      this.error = null;

      try {
        const request = {
          methodname: "mod_longpage_get_dashboard_analytics",
          args: {
            longpageids: this.selectedLongpageIds,
            semesterstart: this.selectedSemester.start,
            semesterend: this.selectedSemester.end,
          },
        };
        this.analyticsData = await moodleAjax.call([request])[0];
      } catch (err) {
        console.error("Failed to load analytics:", err);
        this.error =
          this.$t("features.teacherDashboard.errorLoading") ||
          "Failed to load analytics data.";
      } finally {
        this.isLoading = false;
        // Wait for DOM to update after isLoading becomes false, then render charts
        this.$nextTick(() => {
          if (this.analyticsData && !this.error) {
            this.renderCharts();
          }
        });
      }
    },
    destroyCharts() {
      Object.values(this.charts).forEach((chart) => {
        if (chart) {
          chart.destroy();
        }
      });
      this.charts = {
        weeklyActivity: null,
        userEngagement: null,
        readingDistribution: null,
        readingBehaviorTrend: null,
      };
    },
    sortStudentsBy(key) {
      if (this.studentSortKey === key) {
        this.studentSortAsc = !this.studentSortAsc;
      } else {
        this.studentSortKey = key;
        this.studentSortAsc = true;
      }
    },
    formatLastActive(timestamp) {
      if (!timestamp) return "-";
      return new Date(timestamp * 1000).toLocaleDateString();
    },
    // Backend week keys are "YYYY-WW" (ISO week). Just the week number is
    // shown on the tick — year/semester context is carried by the vertical
    // boundary lines (see boundaryLinePlugin) instead of repeating the year
    // as text on every single tick.
    formatWeekLabels(data) {
      return data.map((d) => {
        const [, week] = String(d.week || "").split("-");
        const weeknum = parseInt(week, 10);
        return Number.isNaN(weeknum) ? week : String(weeknum);
      });
    },
    // For each week (after the first), detect whether it starts a new
    // calendar year and/or a new semester (Apr 1 / Oct 1) compared to the
    // previous week — used to draw boundaryLinePlugin's reference lines. A
    // simultaneous year+semester change (e.g. New Year's Day inside a
    // winter semester) only draws the year line, since that's the bigger
    // boundary and showing both would just duplicate the marker.
    computeTimeBoundaries(data) {
      const semesterOf = (date) => {
        const year = date.getFullYear();
        const month = date.getMonth() + 1;
        if (month >= 10) {
          return { key: `WS${year}`, label: `WS ${year}/${year + 1}` };
        }
        if (month >= 4) {
          return { key: `SS${year}`, label: `SS ${year}` };
        }
        return { key: `WS${year - 1}`, label: `WS ${year - 1}/${year}` };
      };

      const boundaries = [];
      let prevYear = null;
      let prevSemesterKey = null;
      data.forEach((d, index) => {
        const date = new Date(d.weekstart * 1000);
        const year = date.getFullYear();
        const semester = semesterOf(date);
        if (index > 0) {
          if (year !== prevYear) {
            boundaries.push({ index, label: String(year), type: "year" });
          } else if (semester.key !== prevSemesterKey) {
            boundaries.push({ index, label: semester.label, type: "semester" });
          }
        }
        prevYear = year;
        prevSemesterKey = semester.key;
      });
      return boundaries;
    },
    renderCharts() {
      this.destroyCharts();

      if (!this.analyticsData) {
        console.warn("TeacherDashboard: No analytics data to render");
        return;
      }

      console.log(
        "TeacherDashboard: Rendering charts with data",
        this.analyticsData,
      );
      console.log("TeacherDashboard: Canvas refs", {
        weeklyActivity: this.$refs.weeklyActivityChart,
        userEngagement: this.$refs.userEngagementChart,
        readingDistribution: this.$refs.readingDistributionChart,
      });

      this.renderWeeklyActivityChart();
      this.renderUserEngagementChart();
      this.renderReadingDistributionChart();
      this.renderReadingBehaviorTrendChart();
    },
    renderWeeklyActivityChart() {
      const ctx = this.$refs.weeklyActivityChart;
      if (!ctx) return;

      const data = this.analyticsData.weeklyactivity;
      const labels = this.formatWeekLabels(data);

      this.charts.weeklyActivity = new Chart(ctx, {
        type: "bar",
        data: {
          labels,
          datasets: [
            {
              label:
                this.$t("features.teacherDashboard.chartLabels.views") ||
                "Page Views",
              data: data.map((d) => d.views),
              backgroundColor: "#007bff",
            },
            {
              label:
                this.$t("features.teacherDashboard.chartLabels.searches") ||
                "Searches",
              data: data.map((d) => d.searches),
              backgroundColor: "#28a745",
            },
            {
              label:
                this.$t("features.teacherDashboard.chartLabels.tocuses") ||
                "TOC Uses",
              data: data.map((d) => d.tocuses),
              backgroundColor: "#ffc107",
            },
            {
              label:
                this.$t("features.teacherDashboard.chartLabels.quizattempts") ||
                "Quiz Attempts",
              data: data.map((d) => d.quizattempts),
              backgroundColor: "#dc3545",
            },
            {
              label:
                this.$t("features.teacherDashboard.chartLabels.highlights") ||
                "Highlights",
              data: data.map((d) => d.highlights),
              backgroundColor: "#e8c840",
            },
            {
              label:
                this.$t("features.teacherDashboard.chartLabels.posts") ||
                "Posts",
              data: data.map((d) => d.posts),
              backgroundColor: "#17a2b8",
            },
            {
              label:
                this.$t("features.teacherDashboard.chartLabels.bookmarks") ||
                "Bookmarks",
              data: data.map((d) => d.bookmarks),
              backgroundColor: "#6f42c1",
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          interaction: { mode: "index", intersect: false },
          scales: {
            x: {
              stacked: true,
              ticks: { maxRotation: 0, minRotation: 0 },
              title: {
                display: true,
                text:
                  this.$t("features.teacherDashboard.chartLabels.calendarWeek") ||
                  "Calendar Week",
              },
            },
            y: {
              stacked: true,
              beginAtZero: true,
            },
          },
          plugins: {
            boundaryLines: { boundaries: this.computeTimeBoundaries(data) },
          },
        },
      });
    },
    renderUserEngagementChart() {
      const ctx = this.$refs.userEngagementChart;
      if (!ctx) return;

      const data = this.analyticsData.userengagement;
      const labels = this.formatWeekLabels(data);

      this.charts.userEngagement = new Chart(ctx, {
        type: "bar",
        data: {
          labels,
          datasets: [
            {
              label:
                this.$t("features.teacherDashboard.chartLabels.uniqueUsers") ||
                "Unique Users",
              data: data.map((d) => d.uniqueusers),
              backgroundColor: "rgba(0, 123, 255, 0.6)",
              yAxisID: "y",
            },
            {
              label:
                this.$t(
                  "features.teacherDashboard.chartLabels.avgTimeMinutes",
                ) || "Avg Time (min)",
              data: data.map((d) => Math.round(d.avgtimespent / 60)),
              backgroundColor: "rgba(40, 167, 69, 0.6)",
              yAxisID: "y1",
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            x: {
              ticks: { maxRotation: 0, minRotation: 0 },
              title: {
                display: true,
                text:
                  this.$t("features.teacherDashboard.chartLabels.calendarWeek") ||
                  "Calendar Week",
              },
            },
            y: {
              type: "linear",
              display: true,
              position: "left",
              beginAtZero: true,
              // Users is always a whole number — without this, Chart.js's
              // auto tick step for a small max (e.g. a single user) divides
              // the axis into fractions (0.2, 0.5, ...), which is nonsense
              // for a count of people.
              ticks: {
                precision: 0,
              },
              title: {
                display: true,
                text:
                  this.$t("features.teacherDashboard.chartLabels.users") ||
                  "Users",
              },
            },
            y1: {
              type: "linear",
              display: true,
              position: "right",
              beginAtZero: true,
              title: {
                display: true,
                text:
                  this.$t("features.teacherDashboard.chartLabels.minutes") ||
                  "Minutes",
              },
              grid: {
                drawOnChartArea: false,
              },
            },
          },
          plugins: {
            boundaryLines: { boundaries: this.computeTimeBoundaries(data) },
          },
        },
      });
    },
    renderReadingDistributionChart() {
      const ctx = this.$refs.readingDistributionChart;
      if (!ctx) return;

      const data = this.analyticsData.readingdistribution;
      const labels = data.map((d) => d.position);

      // Same fixed behavior-category colors as the "Reading Behavior Over
      // the Semester" chart, so the same category reads the same color in
      // both charts.
      const series = [
        { key: "scan", solid: "#6c757d" },
        { key: "read", solid: "#28a745" },
        { key: "study", solid: "#17a2b8" },
        { key: "regression", solid: "#ffc107" },
        { key: "preview", solid: "#6f42c1" },
      ];

      this.charts.readingDistribution = new Chart(ctx, {
        type: "bar",
        data: {
          labels,
          datasets: series.map(({ key, solid }) => ({
            label:
              this.$t(`features.teacherDashboard.chartLabels.${key}`) || key,
            data: data.map((d) => d[key]),
            backgroundColor: solid,
          })),
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          interaction: { mode: "index", intersect: false },
          scales: {
            x: {
              stacked: true,
              title: {
                display: true,
                text:
                  this.$t(
                    "features.teacherDashboard.chartLabels.textPosition",
                  ) || "Text Position",
              },
            },
            y: {
              stacked: true,
              beginAtZero: true,
              ticks: {
                precision: 0,
              },
              title: {
                display: true,
                text:
                  this.$t("features.teacherDashboard.chartLabels.eventCount") ||
                  "Event Count",
              },
            },
          },
        },
      });
    },
    renderReadingBehaviorTrendChart() {
      const ctx = this.$refs.readingBehaviorTrendChart;
      if (!ctx) return;

      const data = this.analyticsData.readingbehaviortrend;
      const labels = this.formatWeekLabels(data);

      // Fixed, non-cycled colors, one per behavior category — scan/read/study
      // (Carver's reading-depth gears) get adjacent hues already used
      // elsewhere in this dashboard for consistency; regression/preview are
      // a different axis (not "more/less engaged") and get visually distinct
      // hues.
      const series = [
        { key: "scan", solid: "#6c757d" },
        { key: "read", solid: "#28a745" },
        { key: "study", solid: "#17a2b8" },
        { key: "regression", solid: "#ffc107" },
        { key: "preview", solid: "#6f42c1" },
      ];

      // The backend returns each category's share of ALL classified events
      // that week — kept here, untouched, so a legend toggle can renormalize
      // the still-visible categories back to 100% and be undone without
      // drift. (share_i / total original % of currently-visible categories,
      // algebraically equal to count_i / count_of_visible_categories * 100.)
      const originalShares = series.map(({ key }) => data.map((d) => d[key]));

      const recomputeVisibleShares = (chart) => {
        data.forEach((_d, weekIndex) => {
          const visibleTotal = series.reduce((sum, _s, i) => {
            const isVisible = !chart.getDatasetMeta(i).hidden;
            return sum + (isVisible ? originalShares[i][weekIndex] : 0);
          }, 0);
          series.forEach((_s, i) => {
            const isVisible = !chart.getDatasetMeta(i).hidden;
            const share = originalShares[i][weekIndex];
            chart.data.datasets[i].data[weekIndex] =
              isVisible && visibleTotal > 0
                ? Math.round((share / visibleTotal) * 1000) / 10
                : 0;
          });
        });
      };

      this.charts.readingBehaviorTrend = new Chart(ctx, {
        type: "bar",
        data: {
          labels,
          datasets: series.map(({ key, solid }, index) => ({
            label:
              this.$t(`features.teacherDashboard.chartLabels.${key}`) || key,
            data: [...originalShares[index]],
            backgroundColor: solid,
          })),
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          interaction: { mode: "index", intersect: false },
          scales: {
            x: {
              stacked: true,
              ticks: { maxRotation: 0, minRotation: 0 },
              title: {
                display: true,
                text:
                  this.$t("features.teacherDashboard.chartLabels.calendarWeek") ||
                  "Calendar Week",
              },
            },
            y: {
              stacked: true,
              min: 0,
              max: 100,
              title: {
                display: true,
                text:
                  this.$t("features.teacherDashboard.chartLabels.sharePercent") ||
                  "Share of classified reading events (%)",
              },
            },
          },
          plugins: {
            boundaryLines: { boundaries: this.computeTimeBoundaries(data) },
            legend: {
              onClick: (_evt, legendItem, legend) => {
                const chart = legend.chart;
                const index = legendItem.datasetIndex;
                const meta = chart.getDatasetMeta(index);
                // Toggle: if visible now, hide it; if hidden, show it again —
                // the standard Chart.js idiom for a custom stacked-chart
                // legend click (default onClick doesn't renormalize).
                meta.hidden = chart.isDatasetVisible(index);
                recomputeVisibleShares(chart);
                chart.update();
              },
            },
            tooltip: {
              callbacks: {
                label: (item) => `${item.dataset.label}: ${item.formattedValue}%`,
              },
            },
          },
        },
      });
    },
  },
  mounted() {
    document.addEventListener("click", this.handleInstanceDropdownOutsideClick);
  },
  beforeUnmount() {
    document.removeEventListener(
      "click",
      this.handleInstanceDropdownOutsideClick,
    );
    this.destroyCharts();
  },
};
</script>

<style scoped>
.teacher-dashboard-container {
  display: inline-block;
}

.dashboard-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding-top: 50px;
  z-index: 9999;
}

.dashboard-modal {
  background: white;
  border-radius: 8px;
  width: 90%;
  max-width: 1200px;
  max-height: calc(100vh - 70px);
  overflow-y: auto;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 20px;
  border-bottom: 1px solid #ddd;
  position: sticky;
  top: 0;
  background: white;
  z-index: 1;
}

.dashboard-header h3 {
  margin: 0;
  font-size: 1.25rem;
}

.dashboard-controls {
  display: flex;
  align-items: center;
}

.dashboard-controls select {
  min-width: 150px;
}

.dashboard-instance-selector {
  display: flex;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 4px;
  padding: 10px 20px;
  border-bottom: 1px solid #eee;
  background: #f8f9fa;
}

.instance-selector-label {
  font-size: 0.85rem;
  font-weight: 600;
  color: #495057;
  white-space: nowrap;
  padding-top: 6px;
}

.instance-dropdown {
  position: relative;
  width: 220px;
  flex: 0 0 auto;
}

.instance-dropdown-menu {
  max-height: 260px;
  overflow-y: auto;
  width: 100%;
}

.instance-pills {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 4px;
  flex: 1 1 auto;
  min-height: 32px;
}

.instance-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 8px;
  background: #e9ecef;
  color: #212529;
  font-size: 0.8rem;
  font-weight: normal;
  border-radius: 999px;
}

.instance-pill-remove {
  border: none;
  background: transparent;
  padding: 0;
  line-height: 1;
  color: #6c757d;
  cursor: pointer;
}

.instance-pill-remove:hover {
  color: #212529;
}

.dashboard-loading,
.dashboard-error {
  padding: 40px;
  text-align: center;
}

.dashboard-content {
  padding: 20px;
}

.chart-container {
  margin-bottom: 30px;
  padding: 15px;
  border: 1px solid #eee;
  border-radius: 8px;
  background: #fafafa;
  position: relative;
  min-height: 350px;
  width: 100%;
}

.chart-container h4 {
  margin-top: 0;
  margin-bottom: 15px;
  font-size: 1rem;
  color: #333;
}

.chart-container canvas {
  width: 100% !important;
  height: 300px !important;
}

.reading-behavior-table th[role="button"] {
  cursor: pointer;
  user-select: none;
  white-space: nowrap;
}
</style>
