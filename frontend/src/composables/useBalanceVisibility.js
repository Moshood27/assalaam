import { ref, watch } from 'vue'

// Global hide/show balances preference with localStorage persistence
const STORAGE_KEY = 'hide_balances'

const initial = (() => {
  try {
    const v = localStorage.getItem(STORAGE_KEY)
    return v === '1' // default false if not set
  } catch (_) {
    return false
  }
})()

const hideBalances = ref(initial)

watch(hideBalances, (v) => {
  try {
    localStorage.setItem(STORAGE_KEY, v ? '1' : '0')
  } catch (_) {}
}, { immediate: true })

function toggleBalances() {
  hideBalances.value = !hideBalances.value
}

export function useBalanceVisibility() {
  return { hideBalances, toggleBalances }
}


