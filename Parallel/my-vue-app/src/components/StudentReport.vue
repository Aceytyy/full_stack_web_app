<template>
  <div class="student-report-root">
    <div class="student-report-container">
      <div class="search-controls">
        <input
          v-model="studentIdInput"
          type="number"
          placeholder="Search student ID"
          @keyup.enter="onSearch"
        />
        <button @click="onSearch">Search</button>
        <select v-if="semesters.length" v-model="selectedSemester" @change="onSemesterChange">
          <option v-for="sem in semesters" :key="sem.semester_id" :value="sem.semester_id">
            {{ sem.semester }} - {{ sem.school_year }}
          </option>
        </select>
      </div>
      <h2 class="main-title">Student Performance Report</h2>
      <div v-if="loading" class="placeholder">Loading...</div>
      <div v-else-if="error" class="placeholder">{{ error }}</div>
      <div v-else-if="student">
        <div class="student-id">Student ID: <span>{{ student.student_id }}</span></div>
        <div class="stats">
          <div class="stat-box">
            <strong>GPA</strong>
            <div>{{ student.gpa }}</div>
          </div>
          <div class="stat-box" :class="{ negative: student.zscore < 0 }">
            <strong>Z Score</strong>
            <div>{{ student.zscore }}</div>
          </div>
          <div class="stat-box" :class="{ negative: gpaVsClassAvg < 0 }">
            <strong>GPA vs Class Average</strong>
            <div>{{ gpaVsClassAvg }}</div>
          </div>
        </div>
        <div class="table-container">
          <table class="subject-table">
            <thead>
              <tr>
                <th>Subject Code</th>
                <th>Description</th>
                <th>Student Grade</th>
                <th>Class Average</th>
                <th>Rate</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in student.subjects" :key="row.code">
                <td>{{ row.code }}</td>
                <td>{{ row.description }}</td>
                <td>{{ row.grade }}</td>
                <td>{{ row.class_average }}</td>
                <td>{{ row.rate }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'StudentReport',
  data() {
    return {
      studentIdInput: '',
      student: null,
      semesters: [],
      selectedSemester: null,
      loading: false,
      error: null
    };
  },
  computed: {
    gpaVsClassAvg() {
      if (!this.student) return 0;
      return (this.student.gpa - this.student.class_average).toFixed(2);
    }
  },
  methods: {
    async fetchSemesters() {
      if (!this.studentIdInput) return;
      this.loading = true;
      this.error = null;
      try {
        const res = await fetch('http://localhost/Parallel%20(3)/Parallel/get_student_semesters.php?student_id=' + this.studentIdInput);
        const data = await res.json();
        this.semesters = data;
        if (this.semesters.length > 0) {
          this.selectedSemester = this.semesters[0].semester_id;
          await this.fetchStudent(this.selectedSemester);
        }
      } catch (err) {
        this.error = 'Failed to load semesters.';
      } finally {
        this.loading = false;
      }
    },
    async fetchStudent(semesterId = null) {
      if (!this.studentIdInput) {
        this.error = "Please enter a student ID.";
        return;
      }
      this.loading = true;
      this.error = null;
      let url = 'http://localhost/Parallel%20(3)/Parallel/student_details.php?student_id=' + this.studentIdInput;
      if (semesterId) url += `&semester_id=${semesterId}`;
      try {
        const res = await fetch(url);
        const data = await res.json();
        if (data.error) {
          this.error = data.message || "Student not found or error fetching data.";
          this.student = null;
        } else {
          this.student = data;
        }
      } catch (err) {
        this.error = "Student not found or error fetching data.";
        this.student = null;
      } finally {
        this.loading = false;
      }
    },
    onSemesterChange() {
      this.fetchStudent(this.selectedSemester);
    },
    onSearch() {
      this.fetchSemesters();
    }
  }
};
</script>

<style scoped>
.student-report-root {
  min-height: 100vh;
  background: linear-gradient(120deg, #f8fafc 0%, #e0e7ff 100%);
  padding: 40px 0;
}
.student-report-container {
  background: #fff;
  max-width: 900px;
  margin: 40px auto;
  padding: 32px 32px 40px 32px;
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
.search-controls {
  display: flex;
  gap: 12px;
  justify-content: center;
  margin-bottom: 24px;
}
input[type=\"number\"], select {
  padding: 10px 14px;
  border: 1.5px solid #bfc9d9;
  border-radius: 8px;
  font-size: 1rem;
  background: #f4f7fa;
  transition: border 0.2s;
}
input[type=\"number\"]:focus, select:focus {
  border: 1.5px solid #6366f1;
  outline: none;
}
button {
  padding: 10px 22px;
  background: #6366f1;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  font-size: 1rem;
  cursor: pointer;
  transition: background 0.2s, box-shadow 0.2s;
  box-shadow: 0 2px 8px 0 rgba(99,102,241,0.08);
}
button:hover {
  background: #4f46e5;
  box-shadow: 0 4px 16px 0 rgba(99,102,241,0.15);
}
.student-id {
  text-align: center;
  font-size: 1.1rem;
  margin-bottom: 18px;
  color: #6366f1;
  font-weight: 600;
}
.stats {
  display: flex;
  gap: 18px;
  justify-content: center;
  margin-bottom: 32px;
  flex-wrap: wrap;
}
.stat-box {
  background: #f4f7fa;
  border-radius: 14px;
  box-shadow: 0 2px 8px 0 rgba(99,102,241,0.06);
  padding: 22px 32px;
  min-width: 180px;
  text-align: center;
  font-size: 1.1rem;
  font-weight: 500;
  color: #22223b;
  transition: box-shadow 0.2s, background 0.2s;
}
.stat-box:hover {
  background: #e0e7ff;
  box-shadow: 0 4px 16px 0 rgba(99,102,241,0.13);
}
.stat-box.negative {
  color: #d90429;
}
.table-container {
  margin-top: 18px;
  overflow-x: auto;
}
.subject-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  background: #f8fafc;
  border-radius: 12px;
  box-shadow: 0 2px 8px 0 rgba(60,60,120,0.06);
  overflow: hidden;
}
.subject-table th, .subject-table td {
  padding: 14px 18px;
  text-align: left;
}
.subject-table th {
  background: #e0e7ff;
  color: #22223b;
  font-weight: 700;
  font-size: 1rem;
}
.subject-table tr {
  transition: background 0.2s;
}
.subject-table tbody tr:hover {
  background: #dbeafe;
}
.subject-table td {
  font-size: 0.98rem;
  color: #22223b;
}
.placeholder {
  color: #888;
  font-style: italic;
  margin: 20px 0;
  text-align: center;
}
@media (max-width: 700px) {
  .student-report-container {
    padding: 12px 2vw 24px 2vw;
  }
  .stats {
    flex-direction: column;
    gap: 10px;
  }
  .stat-box {
    min-width: 0;
    width: 100%;
  }
  .subject-table th, .subject-table td {
    padding: 10px 6px;
  }
}
</style>
