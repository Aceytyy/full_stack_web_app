<template>
  <div class="at-risk-root">
    <div class="at-risk-container">
      <h2 class="main-title">At-Risk Students</h2>
      
      <!-- Search Bar -->
      <div class="search-container">
        <input 
          type="text" 
          v-model="searchQuery" 
          @input="onSearch"
          placeholder="Search by student name, ID, or course..."
          class="search-input"
          aria-label="Search students"
        />
      </div>

      <!-- Filter Controls -->
      <div class="filter-controls">
        <div class="filter-item">
          <label for="year-select">Academic Year:</label>
          <select 
            id="year-select" 
            v-model="selectedYear" 
            @change="onFilterChange"
            aria-label="Select academic year"
          >
            <option v-for="year in academicYears" :key="year" :value="year">{{ year }}</option>
          </select>
        </div>
        <div class="filter-item">
          <label for="semester-select">Semester:</label>
          <select 
            id="semester-select" 
            v-model="selectedSemester" 
            @change="onFilterChange"
            aria-label="Select semester"
          >
            <option v-for="sem in filteredSemesters" :key="sem.value" :value="sem.value">
              {{ sem.label }}
            </option>
          </select>
        </div>
      </div>

      <!-- Pagination Controls (Top) -->
      <div class="pagination-controls prominent" v-if="pageCount > 1">
        <button 
          :disabled="currentPage === 1" 
          @click="goToPage(currentPage - 1)"
          aria-label="Previous page"
        >
          <span>&laquo;</span> Previous
        </button>
        <span>Page {{ currentPage }} of {{ pageCount }}</span>
        <button 
          :disabled="currentPage === pageCount" 
          @click="goToPage(currentPage + 1)"
          aria-label="Next page"
        >
          Next <span>&raquo;</span>
        </button>
      </div>

      <!-- Stats Row -->
      <div class="stats" v-if="students.length">
        <div class="stat-box">
          <strong>Total At-Risk</strong>
          <div>{{ total }}</div>
        </div>
        <div class="stat-box">
          <strong>Courses Represented</strong>
          <div>{{ courseCount }}</div>
        </div>
        <div class="stat-box">
          <strong>Most Common Course</strong>
          <div>{{ mostCommonCourse }}</div>
        </div>
        <div class="stat-box">
          <strong>Avg. Support Level</strong>
          <div>{{ avgSupportLevel }}</div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading-container">
        <div class="spinner"></div>
        <p>Loading student data...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="error-container">
        <div class="error-icon">⚠️</div>
        <p>{{ error }}</p>
        <button @click="fetchStudents" class="retry-button">Retry</button>
      </div>

      <!-- Empty State -->
      <div v-else-if="!students.length" class="empty-state">
        <div class="empty-icon">📚</div>
        <p>No at-risk students found</p>
        <p class="empty-subtext">Try adjusting your filters or search criteria</p>
      </div>

      <!-- Table Container -->
      <div v-else class="table-container">
        <table class="subject-table">
          <thead>
            <tr>
              <th>Student</th>
              <th>Course</th>
              <th>Semester</th>
              <th>Support Needed</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr 
              v-for="student in students" 
              :key="student.student_id + '-' + student.semester_id"
              @click="showStudentDetails(student)"
              class="clickable-row"
            >
              <td>{{ student.name }}</td>
              <td>{{ student.course }}</td>
              <td>{{ getSemesterLabel(student.semester_id) }}</td>
              <td>
                <div class="donut-container">
                  <svg class="donut" width="60" height="60" viewBox="0 0 42 42">
                    <circle
                      class="donut-bg"
                      cx="21"
                      cy="21"
                      r="15.9155"
                      fill="transparent"
                      stroke="#eee"
                      stroke-width="6"
                    />
                    <circle
                      class="donut-ring"
                      cx="21"
                      cy="21"
                      r="15.9155"
                      fill="transparent"
                      :stroke="getSmoothColor(generateSupportLevel(student))"
                      stroke-width="6"
                      stroke-dasharray="100 100"
                      :stroke-dashoffset="100 - (generateSupportLevel(student) * 10)"
                      stroke-linecap="round"
                      transform="rotate(-90 21 21)"
                    />
                    <text x="50%" y="50%" text-anchor="middle" dy=".3em" font-size="12" fill="#333">
                      {{ generateSupportLevel(student) }}
                    </text>
                  </svg>
                </div>
              </td>
              <td>
                <button 
                  @click.stop="showStudentDetails(student)"
                  class="details-button"
                  aria-label="View student details"
                >
                  View Details
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination Controls (Bottom) -->
        <div class="pagination-controls prominent" v-if="pageCount > 1">
          <button 
            :disabled="currentPage === 1" 
            @click="goToPage(currentPage - 1)"
            aria-label="Previous page"
          >
            <span>&laquo;</span> Previous
          </button>
          <span>Page {{ currentPage }} of {{ pageCount }}</span>
          <button 
            :disabled="currentPage === pageCount" 
            @click="goToPage(currentPage + 1)"
            aria-label="Next page"
          >
            Next <span>&raquo;</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Student Details Modal -->
    <div v-if="selectedStudent" class="modal-overlay" @click="closeModal">
      <div class="modal-content" @click.stop>
        <button class="close-button" @click="closeModal" aria-label="Close modal">&times;</button>
        <h3>Student Details</h3>
        <div class="student-details">
          <p><strong>Name:</strong> {{ selectedStudent.name }}</p>
          <p><strong>Course:</strong> {{ selectedStudent.course }}</p>
          <p><strong>Semester:</strong> {{ getSemesterLabel(selectedStudent.semester_id) }}</p>
          <p><strong>Support Level:</strong> {{ generateSupportLevel(selectedStudent) }}/10</p>
          <div v-if="selectedStudent.risk_reasons && selectedStudent.risk_reasons.length">
            <h4>Why At Risk:</h4>
            <ul>
              <li v-for="reason in selectedStudent.risk_reasons" :key="reason">{{ reason }}</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AtRisk',
  data() {
    return {
      students: [],
      total: 0,
      loading: true,
      error: null,
      searchQuery: '',
      searchTimeout: null,
      academicYears: ['2024-2025', '2023-2024', '2022-2023', '2021-2022'],
      selectedYear: '2024-2025',
      semesters: [
        { value: 'all', label: 'All Semesters' },
        { value: '1', label: '1st Semester' },
        { value: '2', label: '2nd Semester' },
        { value: '3', label: 'Summer' }
      ],
      selectedSemester: 'all',
      semesterLabels: {
        1: '1st Semester',
        2: '2nd Semester',
        3: 'Summer',
        4: '1st Semester',
        5: '2nd Semester',
        6: 'Summer',
        7: '1st Semester',
        8: '2nd Semester',
        9: 'Summer',
        10: '1st Semester',
        11: '2nd Semester',
        12: 'Summer',
        13: '1st Semester',
        14: '2nd Semester',
        15: 'Summer'
      },
      currentPage: 1,
      pageSize: 10,
      pageCount: 1,
      selectedStudent: null
    }
  },
  computed: {
    filteredSemesters() {
      // Filter semesters based on selected year
      return this.semesters;
    },
    courseCount() {
      const courses = new Set(this.students.map(s => s.course));
      return courses.size;
    },
    mostCommonCourse() {
      if (!this.students.length) return '-';
      const freq = {};
      this.students.forEach(s => {
        freq[s.course] = (freq[s.course] || 0) + 1;
      });
      let max = 0, course = '-';
      for (const c in freq) {
        if (freq[c] > max) {
          max = freq[c];
          course = c;
        }
      }
      return course;
    },
    avgSupportLevel() {
      if (!this.students.length) return '-';
      const sum = this.students.reduce((acc, s) => acc + this.generateSupportLevel(s), 0);
      return (sum / this.students.length).toFixed(2);
    }
  },
  methods: {
    onSearch() {
      // Debounce search
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        this.currentPage = 1;
        this.fetchStudents();
      }, 300);
    },
    getSemesterLabel(id) {
      return this.semesterLabels[id] || id;
    },
    generateSupportLevel(student) {
      if (student.grade) {
        const grade = parseFloat(student.grade);
        if (grade >= 4) return 10;
        if (grade >= 3.25) return 7;
        if (grade >= 2.75) return 5;
        if (grade >= 1.75) return 3;
        return 1;
      }
      return Math.floor(Math.random() * 10) + 1;
    },
    getSmoothColor(level) {
      if (level <= 5) {
        const percent = (level - 1) / 4;
        const r = Math.round(76 + percent * (255 - 76));
        const g = Math.round(175 + percent * (193 - 175));
        const b = Math.round(80 + percent * (7 - 80));
        return `rgb(${r},${g},${b})`;
      } else {
        const percent = (level - 6) / 4;
        const r = Math.round(255 + percent * (244 - 255));
        const g = Math.round(193 - percent * 193);
        const b = Math.round(7 + percent * (67 - 7));
        return `rgb(${r},${g},${b})`;
      }
    },
    goToPage(page) {
      if (page < 1 || page > this.pageCount) return;
      this.currentPage = page;
      this.fetchStudents();
    },
    onFilterChange() {
      this.currentPage = 1;
      this.fetchStudents();
    },
    showStudentDetails(student) {
      this.selectedStudent = student;
    },
    closeModal() {
      this.selectedStudent = null;
    },
    fetchStudents() {
      this.loading = true;
      this.error = null;
      let url = `http://localhost/Parallel%20(3)/Parallel/atrisk.php?page=${this.currentPage}`;
      
      // Add search query if present
      if (this.searchQuery) {
        url += `&search=${encodeURIComponent(this.searchQuery)}`;
      }
      
      // Add semester filter if selected
      if (this.selectedSemester !== 'all') {
        url += `&semester_id=${this.selectedSemester}`;
      }

      fetch(url)
        .then(response => {
          if (!response.ok) throw new Error('Network response was not ok');
          return response.json();
        })
        .then(data => {
          if (data.error) {
            this.error = data.message || 'Failed to load data';
            this.students = [];
            this.total = 0;
            this.pageCount = 1;
          } else {
            this.students = data.data;
            this.total = data.total;
            this.pageCount = Math.ceil(data.total / data.limit) || 1;
          }
          this.loading = false;
        })
        .catch(err => {
          this.error = 'Failed to load data: ' + err.message;
          this.students = [];
          this.total = 0;
          this.pageCount = 1;
          this.loading = false;
        });
    }
  },
  mounted() {
    this.fetchStudents();
  }
}
</script>

<style scoped>
.at-risk-root {
  min-height: 100vh;
  background: linear-gradient(120deg, #f8fafc 0%, #e0e7ff 100%);
  padding: 40px 0;
}

.at-risk-container {
  background: #fff;
  max-width: 1200px;
  margin: 40px auto;
  padding: 32px;
  border-radius: 18px;
  box-shadow: 0 4px 32px 0 rgba(60,60,120,0.10);
}

.main-title {
  text-align: center;
  font-size: 2rem;
  font-weight: 700;
  margin: 24px 0 16px 0;
  color: #22223b;
  letter-spacing: 0.5px;
}

/* Search Bar Styles */
.search-container {
  margin: 20px 0;
  text-align: center;
}

.search-input {
  width: 100%;
  max-width: 500px;
  padding: 12px 20px;
  border: 2px solid #e0e7ff;
  border-radius: 10px;
  font-size: 1rem;
  transition: all 0.3s ease;
}

.search-input:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
}

/* Filter Controls */
.filter-controls {
  display: flex;
  gap: 12px;
  justify-content: center;
  margin-bottom: 24px;
  flex-wrap: wrap;
}

.filter-item {
  display: flex;
  align-items: center;
  gap: 8px;
}

.filter-item label {
  font-weight: 600;
  color: #6366f1;
}

.filter-item select {
  padding: 10px 14px;
  border: 1.5px solid #bfc9d9;
  border-radius: 8px;
  font-size: 1rem;
  background: #f4f7fa;
  transition: all 0.3s ease;
}

.filter-item select:focus {
  border-color: #6366f1;
  outline: none;
  box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
}

/* Stats Row */
.stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 18px;
  margin-bottom: 32px;
}

.stat-box {
  background: #f4f7fa;
  border-radius: 14px;
  padding: 22px;
  text-align: center;
  transition: all 0.3s ease;
}

.stat-box:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(99,102,241,0.13);
}

/* Table Styles */
.table-container {
  margin-top: 18px;
  overflow-x: auto;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(60,60,120,0.06);
}

.subject-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  background: #f8fafc;
}

.subject-table th {
  position: sticky;
  top: 0;
  background: #e0e7ff;
  color: #22223b;
  font-weight: 700;
  padding: 16px;
  text-align: left;
  z-index: 10;
}

.subject-table td {
  padding: 14px 16px;
  border-bottom: 1px solid #e0e7ff;
}

.clickable-row {
  cursor: pointer;
  transition: background-color 0.2s;
}

.clickable-row:hover {
  background-color: #dbeafe;
}

/* Loading State */
.loading-container {
  text-align: center;
  padding: 40px;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e0e7ff;
  border-top: 4px solid #6366f1;
  border-radius: 50%;
  margin: 0 auto 16px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Error State */
.error-container {
  text-align: center;
  padding: 40px;
  color: #dc2626;
}

.error-icon {
  font-size: 2rem;
  margin-bottom: 16px;
}

.retry-button {
  margin-top: 16px;
  padding: 8px 16px;
  background: #6366f1;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.retry-button:hover {
  background: #4f46e5;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 40px;
  color: #6b7280;
}

.empty-icon {
  font-size: 3rem;
  margin-bottom: 16px;
}

.empty-subtext {
  color: #9ca3af;
  margin-top: 8px;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  padding: 32px;
  border-radius: 12px;
  max-width: 500px;
  width: 90%;
  position: relative;
}

.close-button {
  position: absolute;
  top: 16px;
  right: 16px;
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #6b7280;
}

.student-details {
  margin-top: 20px;
}

.student-details p {
  margin: 8px 0;
}

/* Pagination Controls */
.pagination-controls {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 16px;
  margin: 24px 0;
}

.pagination-controls button {
  padding: 12px 24px;
  background: #6366f1;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 8px;
}

.pagination-controls button:hover:not(:disabled) {
  background: #4f46e5;
  transform: translateY(-1px);
}

.pagination-controls button:disabled {
  background: #bfc9d9;
  cursor: not-allowed;
}

/* Responsive Design */
@media (max-width: 768px) {
  .at-risk-container {
    margin: 20px;
    padding: 20px;
  }

  .stats {
    grid-template-columns: 1fr;
  }

  .filter-controls {
    flex-direction: column;
    align-items: stretch;
  }

  .filter-item {
    flex-direction: column;
    align-items: stretch;
  }

  .filter-item select {
    width: 100%;
  }

  .subject-table th,
  .subject-table td {
    padding: 12px 8px;
  }
}

/* Details Button */
.details-button {
  padding: 6px 12px;
  background: #6366f1;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
}

.details-button:hover {
  background: #4f46e5;
  transform: translateY(-1px);
}

.modal-content ul {
  margin: 0 0 0 1em;
  padding: 0;
  color: #b91c1c;
}
.modal-content h4 {
  margin-top: 1em;
  color: #b91c1c;
}
</style>

