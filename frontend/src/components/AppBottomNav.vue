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
/* Ensure icons are centered and correctly sized */
[class^="i-mdi-"] {
  display: inline-block;
  width: 1.25em;
  height: 1.25em;
  contain: strict;
  fill: currentColor;
  -webkit-mask-repeat: no-repeat;
  mask-repeat: no-repeat;
  -webkit-mask-size: 100% 100%;
  mask-size: 100% 100%;
  background-color: currentColor;
}

.i-mdi-home-outline { -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 5.69l5 4.5V18h-2v-6H9v6H7v-7.81l5-4.5M12 3L2 12h3v8h6v-6h2v6h6v-8h3L12 3z"/></svg>'); }
.i-mdi-home { -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>'); }
.i-mdi-wallet-outline { -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21 7.28V5c0-1.1-.9-2-2-2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2v-2.28c.59-.35 1-.98 1-1.72V9c0-.74-.41-1.37-1-1.72M20 9v6h-7V9h7M5 19V5h14v2h-6c-1.1 0-2 .9-2 2v6c0 1.1.9 2 2 2h6v2H5z"/></svg>'); }
.i-mdi-wallet { -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21 18v1c0 1.1-.9 2-2 2H5c-1.1 0-2-.9-2-2V5c0-1.1.9-2 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.1 0-2 .9-2 2v6c0 1.1.9 2 2 2h9m0-9v4h-7V9h7z"/></svg>'); }
.i-mdi-book-open-variant { -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21 5c-1.11-.35-2.33-.5-3.5-.5-1.95 0-4.05.4-5.5 1.5-1.45-1.1-3.55-1.5-5.5-1.5S2.45 4.9 1 6v14.65c0 .25.25.5.5.5.1 0 .15-.05.25-.05C3.1 20.45 5.05 20 6.5 20c1.95 0 4.05.4 5.5 1.5 1.35-.85 3.1-1.5 5.5-1.5 1.65 0 3.35.3 4.75 1.05.1.05.15.05.25.05.25 0 .5-.25.5-.5V6c-.6-.45-1.25-.75-2-1m0 13.5c-1.1-.35-2.3-.5-3.5-.5-1.7 0-3.45.3-5 1V7c1.55-.7 3.3-1 5-1 1.2 0 2.4.15 3.5.5v11m-10 1c-1.55-.7-3.3-1-5-1-1.2 0-2.4.15-3.5.5V7c1.1-.35 2.3-.5 3.5-.5 1.7 0 3.45.3 5 1v11z"/></svg>'); }
.i-mdi-account-outline { -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 5.9c1.16 0 2.1.94 2.1 2.1s-.94 2.1-2.1 2.1S9.9 9.16 9.9 8s.94-2.1 2.1-2.1m0 9c2.97 0 6.1 1.46 6.1 2.1v1.1H5.9V17c0-.64 3.13-2.1 6.1-2.1M12 4C9.79 4 8 5.79 8 8s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4m0 9c-2.67 0-8 1.34-8 4v3h16v-3c0-2.66-5.33-4-8-4z"/></svg>'); }
.i-mdi-account { -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 4a4 4 0 014 4 4 4 0 01-4 4 4 4 0 01-4-4 4 4 0 014-4m0 10c4.42 0 8 1.79 8 4v2H4v-2c0-2.21 3.58-4 8-4z"/></svg>'); }
</style>
