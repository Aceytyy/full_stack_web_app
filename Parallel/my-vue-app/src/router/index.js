import { createRouter, createWebHistory } from 'vue-router'
// Adjust these imports to match your actual file locations
import DashboardLayout from '../components/DashboardLayout.vue'
import StudentReport from '@/components/StudentReport.vue'
import AtRisk from '@/components/AtRisk.vue'
import Analytics from '@/components/Analytics.vue'
import Comparison from '@/components/Comparison.vue'
import Grades from '@/components/Grades.vue'

const routes = [
  {
    path: '/',
    component: DashboardLayout,
    children: [
      { path: '', redirect: 'report' },
      { path: 'report', component: StudentReport },
      { path: 'at-risk', component: AtRisk },
      { path: 'analytics', component: Analytics },
      { path: 'comparison', component: Comparison },
      { path: 'grades', component: Grades }
    ]
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router
