<template>
  <canvas ref="canvas"></canvas>
</template>
<script setup>
import { ref, watch, onMounted } from 'vue'
import { Chart, BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend } from 'chart.js'
Chart.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend)

const props = defineProps({ labels: Array, distribution: Object })
const canvas = ref(null)
let chart

const colors = ['#6366f1', '#f59e42', '#22c55e', '#ef4444']
const render = () => {
  if (chart) chart.destroy()
  chart = new Chart(canvas.value, {
    type: 'bar',
    data: {
      labels: props.labels,
      datasets: Object.keys(props.distribution).map((range, i) => ({
        label: range,
        data: props.distribution[range],
        backgroundColor: colors[i % colors.length]
      }))
    },
    options: {
      responsive: true,
      plugins: { legend: { position: 'top' } },
      scales: {
        y: { beginAtZero: true }
      }
    }
  })
}
onMounted(render)
watch(() => [props.labels, props.distribution], render)
</script>
