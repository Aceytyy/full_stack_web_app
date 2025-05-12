<template>
  <div class="comparison-root">
    <div class="comparison-container">
      <h1 class="title">Yearly Statistics Comparison Report</h1>
      <div class="summary-cards">
        <div class="summary-card">
          <div class="summary-title">Avg. Grade</div>
          <div class="summary-value">{{ overallAverageGrade }}</div>
        </div>
        <div class="summary-card">
          <div class="summary-title">Passing Rate</div>
          <div class="summary-value">{{ overallPassingRate }}%</div>
        </div>
        <div class="summary-card">
          <div class="summary-title">Semesters</div>
          <div class="summary-value">{{ semesters.length }}</div>
        </div>
      </div>
      <div class="year-select">
        <label for="year">School Year:</label>
        <select v-model="selectedYear" @change="fetchYearData">
          <option v-for="year in schoolYears" :key="year" :value="year">{{ year }}</option>
        </select>
      </div>
      <div class="charts-row">
        <div class="chart-card">
          <h2>Average Grade per Semester</h2>
          <BarChart v-if="semesters.length" :labels="semesterNames" :data="averageGrades" />
        </div>
        <div class="chart-card">
          <h2>Passing Rate per Semester</h2>
          <LineChart v-if="semesters.length" :labels="semesterNames" :data="passingRates" />
        </div>
        <div class="chart-card">
          <h2>At-Risk Rate per Semester</h2>
          <LineChart v-if="semesters.length" :labels="semesterNames" :data="atRiskRates" color="#ef4444" />
        </div>
      </div>
      <div class="semester-table-container" v-if="semesters.length">
        <h2>Semester Breakdown</h2>
        <div class="table-container">
          <table>
            <thead>
              <tr>
                <th>Semester</th>
                <th>Average Grade</th>
                <th>Passing Rate (%)</th>
                <th>At-Risk Rate (%)</th>
                <th>Top Grade</th>
                <th>90-100</th>
                <th>80-89</th>
                <th>70-79</th>
                <th>&lt;70</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in semesters" :key="row.semester">
                <td>{{ row.semester }}</td>
                <td>{{ row.average_grade }}</td>
                <td>{{ (row.passing_rate * 100).toFixed(2) }}</td>
                <td>{{ (row.at_risk_rate * 100).toFixed(2) }}</td>
                <td>{{ row.top_grade }}</td>
                <td>{{ row.count_90_100 }}</td>
                <td>{{ row.count_80_89 }}</td>
                <td>{{ row.count_70_79 }}</td>
                <td>{{ row.count_lt_70 }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import BarChart from './charts/BarChart.vue'
import LineChart from './charts/LineChart.vue'

const schoolYears = ref([])
const selectedYear = ref(null)
const semesters = ref([])

const semesterNames = computed(() => semesters.value.map(s => s.semester))
const averageGrades = computed(() => semesters.value.map(s => s.average_grade))
const passingRates = computed(() => semesters.value.map(s => s.passing_rate * 100))
const atRiskRates = computed(() => semesters.value.map(s => s.at_risk_rate * 100))

const overallAverageGrade = computed(() =>
  semesters.value.length
    ? (semesters.value.reduce((sum, s) => sum + (s.average_grade ?? 0), 0) / semesters.value.length).toFixed(2)
    : '-'
)
const overallPassingRate = computed(() =>
  semesters.value.length
    ? (semesters.value.reduce((sum, s) => sum + (s.passing_rate ?? 0), 0) / semesters.value.length * 100).toFixed(2)
    : '-'
)

const fetchSchoolYears = async () => {
  const res = await fetch('http://localhost/Parallel%20(3)/Parallel/year_comparison.php')
  const data = await res.json()
  schoolYears.value = data.school_years
  selectedYear.value = data.school_years[data.school_years.length - 1] // default to latest year
  await fetchYearData()
}

const fetchYearData = async () => {
  if (!selectedYear.value) return
  const res = await fetch(`http://localhost/Parallel%20(3)/Parallel/comparison.php?school_year=${selectedYear.value}`)
  const data = await res.json()
  semesters.value = data.semesters
}

onMounted(fetchSchoolYears)
</script>

<style scoped>
.comparison-root {
  min-height: 100vh;
  background: #f8fafc;
  padding: 40px 0;
}
.comparison-container {
  max-width: 1200px;
  margin: 0 auto;
  background: #fff;
  border-radius: 18px;
  box-shadow: 0 4px 32px 0 rgba(60,60,120,0.10);
  padding: 32px;
}
.title {
  text-align: center;
  font-size: 2.2rem;
  font-weight: 800;
  margin-bottom: 32px;
  color: #22223b;
  letter-spacing: -1px;
}
.summary-cards {
  display: flex;
  gap: 24px;
  justify-content: center;
  margin-bottom: 32px;
}
.summary-card {
  background: #f8fafc;
  border-radius: 14px;
  box-shadow: 0 2px 8px 0 rgba(60,60,120,0.06);
  padding: 20px 32px;
  min-width: 160px;
  text-align: center;
  transition: box-shadow 0.2s;
}
.summary-card:hover {
  box-shadow: 0 4px 16px 0 rgba(60,60,120,0.10);
}
.summary-title {
  font-size: 1rem;
  color: #64748b;
  margin-bottom: 8px;
  font-weight: 600;
}
.summary-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #22223b;
}
.year-select {
  margin-bottom: 32px;
  text-align: left;
}
.year-select label {
  font-size: 1.2rem;
  font-weight: 600;
  margin-right: 16px;
  color: #22223b;
}
.year-select select {
  font-size: 1rem;
  padding: 8px;
  border: 1px solid #ccc;
  border-radius: 4px;
}
.charts-row {
  display: flex;
  gap: 32px;
  margin-bottom: 32px;
  flex-wrap: wrap;
  justify-content: center;
}
.chart-card {
  background: #f8fafc;
  border-radius: 14px;
  box-shadow: 0 2px 8px 0 rgba(60,60,120,0.06);
  padding: 24px;
  flex: 1 1 320px;
  min-width: 320px;
  max-width: 400px;
  margin-bottom: 24px;
}
.chart-card h2 {
  font-size: 1.1rem;
  font-weight: 700;
  margin-bottom: 16px;
  color: #22223b;
}
@media (max-width: 900px) {
  .charts-row {
    flex-direction: column;
    gap: 24px;
  }
  .chart-card {
    max-width: 100%;
  }
  .summary-cards {
    flex-direction: column;
    gap: 16px;
    align-items: center;
  }
}
.semester-table-container {
  margin-top: 32px;
}
.semester-table {
  width: 100%;
  border-collapse: collapse;
  background: #f8fafc;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 8px 0 rgba(60,60,120,0.06);
}
.semester-table th, .semester-table td {
  padding: 12px 16px;
  border-bottom: 1px solid #e2e8f0;
  text-align: center;
}
.semester-table th {
  background: #e0e7ff;
  color: #22223b;
  font-weight: 700;
}
.semester-table tr:last-child td {
  border-bottom: none;
}
.table-container {
  margin: 24px 0;
  background: #f8fafc;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(60,60,120,0.06);
  overflow-x: auto;
}
table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
}
th, td {
  padding: 12px 16px;
  text-align: center;
}
thead tr {
  background: #e0e7ff;
}
tbody tr:nth-child(even) {
  background: #f1f5f9;
}
</style>

