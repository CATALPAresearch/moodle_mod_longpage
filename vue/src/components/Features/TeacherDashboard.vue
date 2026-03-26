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
      analyticsData: null,
      charts: {
        weeklyActivity: null,
        userEngagement: null,
        readingDistribution: null,
      },
    };
  },
  computed: {
    ...mapGetters({ context: GET.LONGPAGE_CONTEXT }),
    canViewDashboard() {
      return this.context?.isAdmin || this.context?.canModAnnotations;
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
        await this.loadSemesters();
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
    async loadAnalytics() {
      if (!this.selectedSemester) {
        return;
      }

      this.isLoading = true;
      this.error = null;

      try {
        const request = {
          methodname: "mod_longpage_get_dashboard_analytics",
          args: {
            longpageid: this.context.longpageid,
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
      };
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
    },
    renderWeeklyActivityChart() {
      const ctx = this.$refs.weeklyActivityChart;
      if (!ctx) return;

      const data = this.analyticsData.weeklyactivity;
      const labels = data.map((d) => d.week);

      this.charts.weeklyActivity = new Chart(ctx, {
        type: "line",
        data: {
          labels,
          datasets: [
            {
              label:
                this.$t("features.teacherDashboard.chartLabels.views") ||
                "Page Views",
              data: data.map((d) => d.views),
              borderColor: "#007bff",
              backgroundColor: "rgba(0, 123, 255, 0.1)",
              fill: true,
            },
            {
              label:
                this.$t("features.teacherDashboard.chartLabels.searches") ||
                "Searches",
              data: data.map((d) => d.searches),
              borderColor: "#28a745",
              backgroundColor: "rgba(40, 167, 69, 0.1)",
              fill: true,
            },
            {
              label:
                this.$t("features.teacherDashboard.chartLabels.tocuses") ||
                "TOC Uses",
              data: data.map((d) => d.tocuses),
              borderColor: "#ffc107",
              backgroundColor: "rgba(255, 193, 7, 0.1)",
              fill: true,
            },
            {
              label:
                this.$t("features.teacherDashboard.chartLabels.quizattempts") ||
                "Quiz Attempts",
              data: data.map((d) => d.quizattempts),
              borderColor: "#dc3545",
              backgroundColor: "rgba(220, 53, 69, 0.1)",
              fill: true,
            },
            {
              label:
                this.$t("features.teacherDashboard.chartLabels.highlights") ||
                "Highlights",
              data: data.map((d) => d.highlights),
              borderColor: "#ffe47c",
              backgroundColor: "rgba(255, 228, 124, 0.3)",
              fill: false,
            },
            {
              label:
                this.$t("features.teacherDashboard.chartLabels.posts") ||
                "Posts",
              data: data.map((d) => d.posts),
              borderColor: "#17a2b8",
              backgroundColor: "rgba(23, 162, 184, 0.1)",
              fill: false,
            },
            {
              label:
                this.$t("features.teacherDashboard.chartLabels.bookmarks") ||
                "Bookmarks",
              data: data.map((d) => d.bookmarks),
              borderColor: "#6f42c1",
              backgroundColor: "rgba(111, 66, 193, 0.1)",
              fill: false,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true,
            },
          },
        },
      });
    },
    renderUserEngagementChart() {
      const ctx = this.$refs.userEngagementChart;
      if (!ctx) return;

      const data = this.analyticsData.userengagement;
      const labels = data.map((d) => d.week);

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
            y: {
              type: "linear",
              display: true,
              position: "left",
              beginAtZero: true,
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
        },
      });
    },
    renderReadingDistributionChart() {
      const ctx = this.$refs.readingDistributionChart;
      if (!ctx) return;

      const data = this.analyticsData.readingdistribution;
      const labels = data.map((d) => d.position);

      this.charts.readingDistribution = new Chart(ctx, {
        type: "bar",
        data: {
          labels,
          datasets: [
            {
              label:
                this.$t(
                  "features.teacherDashboard.chartLabels.readingEvents",
                ) || "Reading Events",
              data: data.map((d) => d.count),
              backgroundColor: "rgba(23, 162, 184, 0.6)",
              borderColor: "rgba(23, 162, 184, 1)",
              borderWidth: 1,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            x: {
              title: {
                display: true,
                text:
                  this.$t(
                    "features.teacherDashboard.chartLabels.textPosition",
                  ) || "Text Position",
              },
            },
            y: {
              beginAtZero: true,
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
  },
  beforeUnmount() {
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
</style>
