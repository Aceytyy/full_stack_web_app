<template>
  <canvas ref="canvas"></canvas>
</template>
<script setup>
import { ref, watch, onMounted } from 'vue'
import { Chart, LineController, LineElement, PointElement, CategoryScale, LinearScale, Tooltip, Legend, registerables, Filler } from 'chart.js'
Chart.register(LineController, LineElement, PointElement, CategoryScale, LinearScale, Tooltip, Legend, ...registerables, Filler)

const props = defineProps({ labels: Array, data: Array })
const canvas = ref(null)
let chart

const render = () => {
  if (chart) chart.destroy()
  chart = new Chart(canvas.value, {
    type: 'line',
    data: {
      labels: props.labels,
      datasets: [{
        label: 'Passing Rate (%)',
        data: props.data.map(x => x * 100),
        borderColor: '#6366f1',
        backgroundColor: 'rgba(99,102,241,0.1)',
        fill: true,
        tension: 0.3,
        pointRadius: 5,
        pointBackgroundColor: '#6366f1'
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: true, max: 100 }
      }
    }
  })
}
onMounted(render)
watch(() => [props.labels, props.data], render)
</script>
