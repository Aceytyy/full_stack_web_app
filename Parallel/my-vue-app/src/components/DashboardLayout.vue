<template>
  <div class="dashboard-container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="profile">
        <div class="profile-img"></div>
        <div class="profile-name">LOREM IPSUM</div>
      </div>
      <router-link to="/report" class="menu-item" active-class="active">Student Performance Report</router-link>
      <router-link to="/at-risk" class="menu-item" active-class="active">At-Risk Student List</router-link>
      <router-link to="/analytics" class="menu-item" active-class="active">Subject Analytics</router-link>
      <router-link to="/comparison" class="menu-item" active-class="active">School Year Comparison Report</router-link>
      <router-link to="/grades" class="menu-item" active-class="active">Real-Time Grade Update Stream</router-link>
    </aside>

    <!-- Main Content -->
    <main class="content">
      <router-view /> <!-- This is where page components will be injected -->
    </main>
  </div>
</template>

<script>
export default {
  name: 'DashboardLayout'
}
</script>

<style scoped>
.dashboard-container {
  display: flex;
  height: 100vh;
}
.sidebar {
  width: 230px;
  border-right: 1px solid #ddd;
  background-color: #fff;
  display: flex;
  flex-direction: column;
}
.profile {
  display: flex;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #ddd;
}
.profile-img {
  width: 40px;
  height: 40px;
  background-color: #3498db;
  border-radius: 50%;
  margin-right: 10px;
}
.profile-name {
  font-weight: bold;
  color: #2c3e50;
}
.menu-item {
  padding: 15px 20px;
  border-bottom: 1px solid #ddd;
  cursor: pointer;
  text-decoration: none;
  color: #2c3e50;
  transition: background-color 0.3s ease;
}
.menu-item:hover {
  background-color: #f5f5f5;
}
.menu-item.active {
  background-color: #3498db;
  color: white;
  font-weight: bold;
}
.content {
  flex-grow: 1;
  padding: 20px;
  overflow-y: auto;
}
</style>
