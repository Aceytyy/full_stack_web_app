<template>
  <div class="grades-container">
    <div class="header">
      <h2>Student Grades</h2>
      <div class="filters">
        <div class="filter-group">
          <label>Student ID:</label>
          <input type="text" v-model="studentId" placeholder="Enter Student ID" />
          <button @click="onSearch">Search</button>
        </div>
        <div class="filter-group" v-if="semesters.length">
          <label>Semester:</label>
          <select v-model="selectedSemester" @change="onSemesterChange">
            <option v-for="sem in semesters" :key="sem.semester_id" :value="sem.semester_id">
              {{ sem.semester }} - {{ sem.school_year }}
            </option>
          </select>
        </div>
      </div>
    </div>

    <div v-if="student">
      <h3>Student ID: {{ student.student_id }} | Name: {{ student.name || 'N/A' }}</h3>
      <div class="metrics">
        <div><strong>GPA</strong><br>{{ student.gpa ?? 'N/A' }}</div>
        <div><strong>Z Score</strong><br>{{ student.zscore ?? 'N/A' }}</div>
        <div>
          <strong>GPA vs Class Avg</strong><br>
          <div class="bar-bg">
            <div class="bar-fill" :style="{ width: barWidth + '%' }"></div>
          </div>
        </div>
      </div>
      <table class="grades-table">
        <thead>
          <tr>
            <th>Subject</th>
            <th>Description</th>
            <th>Grade</th>
            <th>Class Average</th>
            <th>Rate</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="subject in student.subjects" :key="subject.code">
            <td>{{ subject.code }}</td>
            <td>{{ subject.description }}</td>
            <td>{{ subject.grade }}</td>
            <td>{{ subject.class_average ?? 'N/A' }}</td>
            <td>{{ subject.rate ?? 'N/A' }}</td>
          </tr>
          <tr v-if="!student.subjects || student.subjects.length === 0">
            <td colspan="5" class="no-data">No grades found</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'Grades',
  data() {
    return {
      studentId: '',
      semesters: [],
      selectedSemester: null,
      student: null
    }
  },
  computed: {
    barWidth() {
      if (!this.student) return 0;
      const gpa = this.student.gpa || 0;
      const avg = this.student.class_average || 1;
      return Math.min(100, Math.max(0, (gpa / avg) * 100));
    }
  },
  methods: {
    async fetchSemesters() {
      if (!this.studentId) return;
      try {
        const res = await axios.get(`http://localhost/Parallel/get_student_semesters.php?student_id=${this.studentId}`);
        this.semesters = res.data;
        if (this.semesters.length > 0) {
          this.selectedSemester = this.semesters[0].semester_id;
          await this.fetchStudent(this.selectedSemester);
        } else {
          this.selectedSemester = null;
          this.student = null;
        }
      } catch (err) {
        this.semesters = [];
        this.selectedSemester = null;
        this.student = null;
      }
    },
    async fetchStudent(semesterId = null) {
      if (!this.studentId) return alert("Please enter a student ID.");
      let url = `http://localhost/Parallel/student_details.php?student_id=${this.studentId}`;
      if (semesterId) url += `&semester_id=${semesterId}`;
      try {
        const res = await axios.get(url);
        this.student = res.data;
      } catch (err) {
        this.student = null;
      }
    },
    onSemesterChange() {
      this.fetchStudent(this.selectedSemester);
    },
    onSearch() {
      this.fetchSemesters();
    }
  }
}
</script>

<style scoped>
.grades-container {
  padding: 20px;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.header {
  margin-bottom: 20px;
}

.header h2 {
  margin: 0 0 20px 0;
  color: #2c3e50;
}

.filters {
  display: flex;
  gap: 20px;
  flex-wrap: wrap;
}

.filter-group {
  display: flex;
  align-items: center;
  gap: 8px;
}

.filter-group label {
  font-weight: 500;
  color: #666;
}

.filter-group select,
.filter-group input {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
  min-width: 150px;
}

.metrics {
  display: flex;
  justify-content: space-between;
  margin-top: 20px;
  margin-bottom: 20px;
}

.metrics div {
  width: 33%;
  text-align: center;
}

.bar-bg {
  background: #ddd;
  height: 20px;
  width: 100%;
  position: relative;
  border-radius: 4px;
  overflow: hidden;
}

.bar-fill {
  background: #4caf50;
  height: 100%;
  transition: width 0.5s;
}

.grades-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 20px;
}

.grades-table th,
.grades-table td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid #eee;
}

.grades-table th {
  background-color: #f8f9fa;
  font-weight: 600;
  color: #2c3e50;
}

.no-data {
  text-align: center;
  color: #666;
  padding: 20px;
}

@media (max-width: 768px) {
  .filters {
    flex-direction: column;
    gap: 10px;
  }

  .filter-group {
    width: 100%;
  }

  .filter-group select,
  .filter-group input {
    width: 100%;
  }
}
</style> 