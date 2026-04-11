<template>
  <div class="w-full">
    <apexchart
      v-if="isReady && hasData"
      type="area"
      height="160"
      :options="options"
      :series="seriesSafe"
    />
    <div v-else class="h-[160px] rounded-xl bg-slate-100/60 border border-slate-200 flex items-center justify-center text-slate-400">
      <span class="text-xs">No activity yet</span>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'

const props = defineProps({
  series: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
  currency: { type: String, default: '₦' },
})

const isReady = ref(false)

onMounted(() => {
  // avoid SSR/hydration issues
  isReady.value = true
})

const seriesSafe = computed(() => Array.isArray(props.series) ? props.series : [])
const hasData = computed(() => {
  const s = seriesSafe.value
  return s.length > 0 && Array.isArray(s[0].data) && s[0].data.length > 0
})

const options = computed(() => ({
  chart: {
    id: 'trend',
    toolbar: { show: false },
    sparkline: { enabled: true },
    animations: { enabled: true },
  },
  dataLabels: { enabled: false },
  stroke: { curve: 'smooth', width: 3 },
  fill: {
    type: 'gradient',
    gradient: {
      shadeIntensity: 1,
      opacityFrom: 0.35,
      opacityTo: 0.05,
      stops: [0, 90, 100],
    },
  },
  grid: { show: false },
  xaxis: {
    categories: props.categories,
    labels: { show: false },
    axisBorder: { show: false },
    axisTicks: { show: false },
    tooltip: { enabled: false },
  },
  yaxis: { show: false },
  colors: ['#059669'],
  tooltip: {
    theme: 'light',
    y: {
      formatter: (val) => `${props.currency} ${Number(val || 0).toLocaleString(undefined, { minimumFractionDigits: 2 })}`,
    },
  },
}))
</script>
