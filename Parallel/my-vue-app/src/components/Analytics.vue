<template>
  <div class="analytics-root">
    <div class="analytics-container">
      <h1 class="title">Subject Analytics</h1>
      <!-- Stats Overview -->
      <div class="stats-overview">
        <div class="stat-card">
          <div class="stat-icon">📊</div>
          <div class="stat-content">
            <div class="stat-value">{{ total }}</div>
            <div class="stat-label">Total Subjects</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">📈</div>
          <div class="stat-content">
            <div class="stat-value">{{ averageGrade }}%</div>
            <div class="stat-label">Avg Grade</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">🎯</div>
          <div class="stat-content">
            <div class="stat-value">{{ averagePassingRate }}%</div>
            <div class="stat-label">Avg Pass Rate</div>
          </div>
        </div>
      </div>
      <!-- Advanced Filters -->
      <div class="advanced-filters">
        <div class="search-box">
          <span class="search-icon">🔍</span>
          <input v-model="searchSubject" @input="debouncedFetch" placeholder="Search subject code..." />
        </div>
        <div class="grade-range">
          <input type="number" v-model.number="minGrade" @input="debouncedFetch" placeholder="Min grade" min="0" max="100" />
          <span class="range-separator">-</span>
          <input type="number" v-model.number="maxGrade" @input="debouncedFetch" placeholder="Max grade" min="0" max="100" />
        </div>
      </div>
      <!-- Filters -->
      <div class="filters">
        <select v-model="selectedSemester" @change="onFilterChange" class="select-primary">
          <option value="all">All Semesters</option>
          <option v-for="sem in uniqueSemesters" :key="sem.id" :value="sem.id">{{ sem.label }}</option>
        </select>
        <select v-model="sortBy" @change="fetchAnalytics" class="select-primary">
          <option value="average_grade">Average Grade</option>
          <option value="passing_rate">Passing Rate</option>
          <option value="failing_rate">Failing Rate</option>
          <option value="at_risk_rate">At-Risk Rate</option>
        </select>
        <select v-model="sortOrder" @change="fetchAnalytics" class="select-primary">
          <option value="desc">Highest First</option>
          <option value="asc">Lowest First</option>
        </select>
      </div>
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <span>Loading analytics...</span>
      </div>
      <div v-else-if="error" class="error-state">
        <span class="error-icon">⚠️</span>
        <span>{{ error }}</span>
      </div>
      <div v-else>
        <div v-if="total > 0" class="results-info">
          Showing {{ (currentPage-1)*pageSize+1 }}–{{ Math.min(currentPage*pageSize, total) }} of {{ total }} results
        </div>
        <div class="table-container">
          <table class="analytics-table">
            <thead>
              <tr>
                <th @click="setSort('subject_code')" :class="{sortable: true, active: sortBy==='subject_code'}">
                  Subject Code
                  <span class="sort-indicator" v-if="sortBy==='subject_code'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
                </th>
                <th @click="setSort('average_grade')" :class="{sortable: true, active: sortBy==='average_grade'}">
                  Average Grade
                  <span class="sort-indicator" v-if="sortBy==='average_grade'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
                </th>
                <th @click="setSort('passing_rate')" :class="{sortable: true, active: sortBy==='passing_rate'}">
                  Passing Rate
                  <span class="sort-indicator" v-if="sortBy==='passing_rate'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
                </th>
                <th @click="setSort('failing_rate')" :class="{sortable: true, active: sortBy==='failing_rate'}">
                  Failing Rate
                  <span class="sort-indicator" v-if="sortBy==='failing_rate'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
                </th>
                <th @click="setSort('at_risk_rate')" :class="{sortable: true, active: sortBy==='at_risk_rate'}">
                  At-Risk Rate
                  <span class="sort-indicator" v-if="sortBy==='at_risk_rate'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
                </th>
                <th>Semester</th>
                <th>Grade Distribution</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="subject in analytics" :key="subject.subject_code + '-' + subject.semester_id" class="table-row">
                <td class="subject-code">{{ subject.subject_code }}</td>
                <td>
                  <div class="bar-container">
                    <div class="bar-bg">
                      <div class="bar-fill" :style="{ width: (subject.average_grade/100*100)+'%', background: getGradeColor(subject.average_grade) }"></div>
                    </div>
                    <span class="bar-label">{{ subject.average_grade }}</span>
                  </div>
                </td>
                <td>
                  <div class="progress-bar pass">
                    <div class="progress-fill" :style="{ width: (subject.passing_rate*100)+'%' }"></div>
                  </div>
                  <span class="progress-label">{{ (subject.passing_rate*100).toFixed(1) }}%</span>
                </td>
                <td>
                  <div class="progress-bar fail">
                    <div class="progress-fill" :style="{ width: (subject.failing_rate*100)+'%' }"></div>
                  </div>
                  <span class="progress-label">{{ (subject.failing_rate*100).toFixed(1) }}%</span>
                </td>
                <td>
                  <div class="progress-bar risk">
                    <div class="progress-fill" :style="{ width: (subject.at_risk_rate*100)+'%' }"></div>
                  </div>
                  <span class="progress-label">{{ (subject.at_risk_rate*100).toFixed(1) }}%</span>
                </td>
                <td>{{ getSemesterLabel(subject.semester_id) }}</td>
                <td>
                  <div class="mini-bar-group">
                    <div
                      v-for="range in ['100', '90-99', '80-89', '75-79', '<75']"
                      :key="range"
                      class="mini-bar"
                      :title="range + ': ' + (subject.grade_distribution ? subject.grade_distribution[range] : 0)"
                      :style="{
                        height: ((subject.grade_distribution && subject.grade_distribution[range] ? subject.grade_distribution[range] : 0) / maxGradeCount * 40 + 5) + 'px',
                        background: getDistColor(range),
                        width: '18px',
                        margin: '0 2px',
                        position: 'relative'
                      }"
                    >
                      <span
                        v-if="subject.grade_distribution && subject.grade_distribution[range] > 0"
                        class="bar-count"
                        :style="{ position: 'absolute', top: '-18px', left: '50%', transform: 'translateX(-50%)', fontSize: '0.8em', color: '#334155', fontWeight: 600 }"
                      >
                        {{ subject.grade_distribution[range] }}
                      </span>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="grade-legend">
          <span v-for="range in ['100', '90-99', '80-89', '75-79', '<75']" :key="range" class="legend-item">
            <span class="legend-color" :style="{ background: getDistColor(range) }"></span>
            {{ range }}
          </span>
        </div>
        <!-- Pagination Controls -->
        <div class="pagination-controls" v-if="total > pageSize">
          <button :disabled="currentPage === 1" @click="goToPage(currentPage - 1)" class="pagination-btn">
            <span class="btn-icon">←</span>
            Previous
          </button>
          <div class="page-numbers">
            <button v-for="page in displayedPages" 
                    :key="page" 
                    @click="goToPage(page)"
                    :class="['page-btn', { active: currentPage === page }]">
              {{ page }}
            </button>
          </div>
          <button :disabled="currentPage === Math.ceil(total / pageSize)" 
                  @click="goToPage(currentPage + 1)" 
                  class="pagination-btn">
            Next
            <span class="btn-icon">→</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Analytics',
  data() {
    return {
      analytics: [],
      semesters: [],
      selectedSemester: 'all',
      sortBy: 'average_grade',
      sortOrder: 'desc',
      loading: true,
      error: null,
      maxGradeCount: 1,
      currentPage: 1,
      pageSize: 10,
      total: 0,
      searchSubject: '',
      minGrade: null,
      maxGrade: null,
      debounceTimeout: null
    }
  },
  computed: {
    averageGrade() {
      if (!this.analytics.length) return 0;
      const sum = this.analytics.reduce((acc, curr) => acc + curr.average_grade, 0);
      return (sum / this.analytics.length).toFixed(1);
    },
    averagePassingRate() {
      if (!this.analytics.length) return 0;
      const sum = this.analytics.reduce((acc, curr) => acc + curr.passing_rate, 0);
      return (sum / this.analytics.length * 100).toFixed(1);
    },
    displayedPages() {
      const totalPages = Math.ceil(this.total / this.pageSize);
      const current = this.currentPage;
      const delta = 2;
      const range = [];
      for (let i = Math.max(1, current - delta); i <= Math.min(totalPages, current + delta); i++) {
        range.push(i);
      }
      if (current - delta > 1) {
        range.unshift(1);
        if (current - delta > 2) range.splice(1, 0, '...');
      }
      if (current + delta < totalPages) {
        if (current + delta < totalPages - 1) range.push('...');
        range.push(totalPages);
      }
      return range;
    },
    uniqueSemesters() {
      // Remove duplicates by id
      const seen = new Set();
      return this.semesters.filter(s => {
        if (seen.has(s.id)) return false;
        seen.add(s.id);
        return s.id !== 'all';
      });
    }
  },
  methods: {
    fetchAnalytics() {
      this.loading = true;
      this.error = null;
      let url = 'http://localhost/Parallel%20(3)/Parallel/subject_analytics.php'
        + `?page=${this.currentPage}&limit=${this.pageSize}`
        + `&sort_by=${this.sortBy}&sort_order=${this.sortOrder}`;
      if (this.selectedSemester !== 'all') {
        url += `&semester_id=${this.selectedSemester}`;
      }
      if (this.searchSubject) {
        url += `&subject_code=${encodeURIComponent(this.searchSubject)}`;
      }
      if (this.minGrade !== null && this.minGrade !== '') {
        url += `&min_grade=${this.minGrade}`;
      }
      if (this.maxGrade !== null && this.maxGrade !== '') {
        url += `&max_grade=${this.maxGrade}`;
      }
      fetch(url)
        .then(res => res.json())
        .then(data => {
          if (data.error) {
            this.error = data.message || 'Failed to load analytics';
            this.analytics = [];
          } else {
            this.analytics = data.data || [];
            let max = 1;
            this.analytics.forEach(s => {
              if (s.grade_distribution) {
                ['100', '90-99', '80-89', '75-79', '<75'].forEach(range => {
                  const v = s.grade_distribution[range] || 0;
                  if (v > max) max = v;
                });
              }
            });
            this.maxGradeCount = max;
            this.total = data.total;
          }
          this.loading = false;
        })
        .catch(err => {
          this.error = 'Failed to load analytics';
          this.loading = false;
        });
    },
    debouncedFetch() {
      clearTimeout(this.debounceTimeout);
      this.debounceTimeout = setTimeout(() => {
        this.currentPage = 1;
        this.fetchAnalytics();
      }, 400);
    },
    setSort(field) {
      if (this.sortBy === field) {
        this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortBy = field;
        this.sortOrder = 'desc';
      }
      this.fetchAnalytics();
    },
    getGradeColor(val) {
      if (val >= 90) return '#4ade80';
      if (val >= 80) return '#facc15';
      if (val >= 75) return '#f97316';
      return '#ef4444';
    },
    getDistColor(range) {
      if (range === '100') return '#4ade80';
      if (range === '90-99') return '#a3e635';
      if (range === '80-89') return '#facc15';
      if (range === '75-79') return '#f97316';
      return '#ef4444';
    },
    goToPage(page) {
      if (page < 1 || page > Math.ceil(this.total / this.pageSize)) return;
      this.currentPage = page;
      this.fetchAnalytics();
    },
    onFilterChange() {
      this.currentPage = 1;
      this.fetchAnalytics();
    },
    getSemesterLabel(id) {
      if (id === undefined || id === null) return '-';
      // Compare as numbers to avoid type mismatch
      const found = this.uniqueSemesters.find(s => Number(s.id) === Number(id));
      return found ? found.label : id;
    }
  },
  mounted() {
    fetch('http://localhost/Parallel%20(3)/Parallel/subject_analytics.php')
      .then(res => res.json())
      .then(data => {
        // Remove duplicates and keep 'all' at the top
        const seen = new Set();
        const unique = (data.semesters || []).filter(s => {
          if (seen.has(s.id)) return false;
          seen.add(s.id);
          return true;
        });
        this.semesters = [{ id: 'all', label: 'All Semesters' }].concat(unique);
      });
    this.fetchAnalytics();
  },
  watch: {
    sortBy() { this.fetchAnalytics(); },
    sortOrder() { this.fetchAnalytics(); }
  }
}
</script>

<style scoped>
.analytics-root {
  min-height: 100vh;
  background: linear-gradient(120deg, #f8fafc 0%, #e0e7ff 100%);
  padding: 40px 0;
}

.analytics-container {
  background: #fff;
  max-width: 1200px;
  margin: 40px auto;
  padding: 32px;
  border-radius: 24px;
  box-shadow: 0 4px 32px 0 rgba(60,60,120,0.10);
}

.title {
  text-align: center;
  font-size: 2.25rem;
  font-weight: 800;
  margin: 24px 0 32px 0;
  color: #1e293b;
  letter-spacing: -0.5px;
}

.stats-overview {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 24px;
  margin-bottom: 32px;
}

.stat-card {
  background: #f8fafc;
  border-radius: 16px;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 16px;
  transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.stat-icon {
  font-size: 2rem;
  background: #e0e7ff;
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
}

.stat-content {
  flex: 1;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1e293b;
  line-height: 1.2;
}

.stat-label {
  font-size: 0.875rem;
  color: #64748b;
  margin-top: 4px;
}

.advanced-filters {
  display: flex;
  gap: 16px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}

.search-box {
  flex: 1;
  min-width: 200px;
  position: relative;
}

.search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #64748b;
}

.search-box input {
  width: 100%;
  padding: 12px 12px 12px 40px;
  border: 1.5px solid #e2e8f0;
  border-radius: 12px;
  font-size: 1rem;
  background: #f8fafc;
  transition: all 0.2s;
}

.search-box input:focus {
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
  outline: none;
}

.grade-range {
  display: flex;
  align-items: center;
  gap: 8px;
}

.grade-range input {
  width: 100px;
  padding: 12px;
  border: 1.5px solid #e2e8f0;
  border-radius: 12px;
  font-size: 1rem;
  background: #f8fafc;
  transition: all 0.2s;
}

.grade-range input:focus {
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
  outline: none;
}

.range-separator {
  color: #64748b;
  font-weight: 500;
}

.filters {
  display: flex;
  gap: 16px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}

.select-primary {
  padding: 12px 16px;
  border: 1.5px solid #e2e8f0;
  border-radius: 12px;
  font-size: 1rem;
  background: #f8fafc;
  color: #1e293b;
  cursor: pointer;
  transition: all 0.2s;
  min-width: 180px;
}

.select-primary:focus {
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
  outline: none;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  padding: 48px 0;
  color: #64748b;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #e2e8f0;
  border-top-color: #6366f1;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.error-state {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 24px;
  background: #fee2e2;
  border-radius: 12px;
  color: #dc2626;
}

.error-icon {
  font-size: 1.5rem;
}

.results-info {
  text-align: right;
  margin-bottom: 16px;
  color: #64748b;
  font-size: 0.875rem;
}

.table-container {
  overflow-x: auto;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.analytics-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  background: #fff;
}

.analytics-table th {
  background: #f8fafc;
  color: #1e293b;
  font-weight: 600;
  padding: 16px;
  text-align: left;
  border-bottom: 2px solid #e2e8f0;
  white-space: nowrap;
}

.analytics-table td {
  padding: 16px;
  border-bottom: 1px solid #e2e8f0;
  color: #1e293b;
}

.table-row:hover {
  background: #f8fafc;
}

.subject-code {
  font-weight: 600;
  color: #6366f1;
}

.sortable {
  cursor: pointer;
  user-select: none;
  position: relative;
}

.sort-indicator {
  margin-left: 4px;
  color: #6366f1;
}

.bar-container {
  display: flex;
  align-items: center;
  gap: 12px;
}

.bar-bg {
  width: 120px;
  height: 8px;
  background: #e2e8f0;
  border-radius: 4px;
  overflow: hidden;
}

.bar-fill {
  height: 100%;
  border-radius: 4px;
  transition: width 0.3s ease;
}

.bar-label {
  font-weight: 600;
  min-width: 40px;
  text-align: right;
}

.progress-bar {
  width: 100px;
  height: 8px;
  background: #e2e8f0;
  border-radius: 4px;
  overflow: hidden;
  display: inline-block;
  margin-right: 8px;
}

.progress-bar.pass .progress-fill { background: #22c55e; }
.progress-bar.fail .progress-fill { background: #ef4444; }
.progress-bar.risk .progress-fill { background: #f59e0b; }

.progress-fill {
  height: 100%;
  border-radius: 4px;
  transition: width 0.3s ease;
}

.progress-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #64748b;
}

.mini-bar-group {
  display: flex;
  align-items: flex-end;
  gap: 4px;
  height: 55px;
  padding: 4px 0;
  min-width: 110px;
}

.mini-bar {
  border-radius: 4px 4px 0 0;
  transition: height 0.5s cubic-bezier(.4,2,.6,1);
  display: flex;
  align-items: flex-end;
  justify-content: center;
  position: relative;
}

.bar-count {
  pointer-events: none;
}

.pagination-controls {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 16px;
  margin-top: 32px;
}

.pagination-btn {
  padding: 8px 16px;
  background: #f8fafc;
  border: 1.5px solid #e2e8f0;
  border-radius: 8px;
  color: #1e293b;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 8px;
}

.pagination-btn:hover:not(:disabled) {
  background: #f1f5f9;
  border-color: #cbd5e1;
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-numbers {
  display: flex;
  gap: 8px;
}

.page-btn {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1.5px solid #e2e8f0;
  border-radius: 8px;
  background: #f8fafc;
  color: #1e293b;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.page-btn:hover:not(.active) {
  background: #f1f5f9;
  border-color: #cbd5e1;
}

.page-btn.active {
  background: #6366f1;
  border-color: #6366f1;
  color: #fff;
}

.btn-icon {
  font-size: 1.25rem;
  line-height: 1;
}

@media (max-width: 768px) {
  .analytics-container {
    margin: 20px;
    padding: 20px;
  }
  
  .stats-overview {
    grid-template-columns: 1fr;
  }
  
  .filters {
    flex-direction: column;
  }
  
  .select-primary {
    width: 100%;
  }
  
  .table-container {
    margin: 0 -20px;
    border-radius: 0;
  }
  
  .analytics-table th,
  .analytics-table td {
    padding: 12px;
  }
  
  .bar-bg {
    width: 80px;
  }
  
  .progress-bar {
    width: 60px;
  }
}

.no-dist {
  color: #64748b;
  font-size: 0.9em;
  font-style: italic;
}

.grade-legend {
  display: flex;
  gap: 18px;
  margin: 12px 0 0 0;
  font-size: 0.95em;
  align-items: center;
  color: #64748b;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 6px;
}

.legend-color {
  display: inline-block;
  width: 18px;
  height: 12px;
  border-radius: 3px;
  margin-right: 2px;
  border: 1px solid #e2e8f0;
}
</style>
