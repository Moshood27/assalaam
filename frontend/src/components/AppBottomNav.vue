<template>
  <nav class="fixed bottom-0 left-0 right-0 z-50 bg-white/80 backdrop-blur-xl border-t border-slate-200/60 pb-[env(safe-area-inset-bottom)] shadow-[0_-4px_12px_rgba(0,0,0,0.03)]">
    <div class="flex justify-around items-center h-16 max-w-lg mx-auto px-2">
      <button 
        v-for="item in navItems" 
        :key="item.path"
        @click="router.push(item.path)"
        class="flex flex-col items-center justify-center flex-1 h-full gap-1 transition-all relative"
        :class="[isActive(item.path) ? 'text-emerald-700' : 'text-slate-400']"
      >
        <div class="relative">
          <span :class="[isActive(item.path) ? item.activeIcon : item.icon, 'text-2xl transition-transform duration-300', isActive(item.path) ? 'scale-110' : '']"></span>
          <div v-if="isActive(item.path)" class="absolute -bottom-1 left-1/2 -translateX-1/2 w-1 h-1 bg-emerald-700 rounded-full"></div>
        </div>
        <span class="text-[10px] font-bold uppercase tracking-wider leading-none">{{ item.label }}</span>
      </button>
    </div>
  </nav>
</template>

<script setup>
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route = useRoute()

const navItems = [
  { label: 'Home', path: '/dashboard', icon: 'i-mdi-home-outline', activeIcon: 'i-mdi-home' },
  { label: 'Wallet', path: '/wallet', icon: 'i-mdi-wallet-outline', activeIcon: 'i-mdi-wallet' },
  { label: 'Passbook', path: '/passbook', icon: 'i-mdi-book-open-variant', activeIcon: 'i-mdi-book-open-variant' },
  { label: 'Profile', path: '/profile', icon: 'i-mdi-account-outline', activeIcon: 'i-mdi-account' },
]

const isActive = (path) => {
  if (path === '/dashboard') return route.path === '/dashboard'
  return route.path.startsWith(path)
}
</script>

<style scoped>
</style>
