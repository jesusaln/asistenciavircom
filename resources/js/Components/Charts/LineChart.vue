<template>
  <div style="position: relative; height: 100%; width: 100%;">
    <Line :data="chartData" :options="chartOptions" />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Line } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js'

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
)

const props = defineProps({
  labels: {
    type: Array,
    required: true
  },
  data: {
    type: Array,
    required: true
  },
  label: {
    type: String,
    default: 'Datos'
  },
  borderColor: {
    type: String,
    default: 'rgb(59, 130, 246)'
  },
  backgroundColor: {
    type: String,
    default: 'rgba(59, 130, 246, 0.1)'
  },
  fill: {
    type: Boolean,
    default: true
  },
  showCurrency: {
    type: Boolean,
    default: true
  }
})

const chartData = computed(() => ({
  labels: props.labels,
  datasets: [{
    label: props.label,
    data: props.data,
    borderColor: props.borderColor,
    backgroundColor: props.backgroundColor,
    fill: props.fill,
    tension: 0.4
  }]
}))

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: true,
      position: 'top'
    },
    tooltip: {
      mode: 'index',
      intersect: false
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        callback: function(value) {
          if (props.showCurrency) {
            return '$' + value.toLocaleString('es-MX')
          } else {
            return value.toLocaleString('es-MX')
          }
        }
      }
    }
  }
}))
</script>

