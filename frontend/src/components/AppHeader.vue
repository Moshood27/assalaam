<template>
  <header class="sticky top-0 z-40 bg-white/80 backdrop-blur-xl border-b border-slate-200/60 pt-[env(safe-area-inset-top)] shadow-sm">
    <div class="h-16 flex items-center justify-between px-4 max-w-5xl mx-auto relative">
      <div class="flex items-center gap-3 min-w-0 flex-1 z-10">
        <slot name="left">
          <button v-if="showBack" @click="router.back()" class="p-2 -ml-2 rounded-full active:bg-slate-100 transition-colors" aria-label="Go back">
            <span class="i-mdi-arrow-left text-2xl text-slate-700"></span>
          </button>
          <button v-else-if="user" @click="router.push('/profile')" class="flex items-center gap-3 min-w-0 group" aria-label="View profile">
            <div class="w-10 h-10 rounded-full overflow-hidden bg-emerald-50 flex items-center justify-center text-emerald-700 font-bold text-xl shrink-0 shadow-sm group-active:scale-95 transition-transform border border-emerald-600/20">
              <img v-if="user && user.passport_url" :src="getImageUrl(user.passport_url)" alt="Profile" class="w-10 h-10 object-cover" />
              <img v-else :src="brand.logo" alt="Logo" class="w-6 h-6 object-contain opacity-80" />
            </div>
            <div class="text-left min-w-0">
              <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider leading-none mb-1 opacity-80">Welcome back,</p>
              <h2 class="text-sm font-black text-slate-800 uppercase truncate max-w-[120px] sm:max-w-[200px]">{{ user.full_name || 'Member' }}</h2>
            </div>
          </button>
        </slot>
      </div>

      <div class="absolute left-1/2 -translate-x-1/2 pointer-events-none text-center px-4 w-1/2 flex items-center justify-center gap-2">
        <img :src="brand.logo" class="h-6 sm:h-7 w-auto object-contain" :alt="brand.name" />
        <h1 v-if="title" class="text-sm sm:text-base font-bold text-slate-800 truncate">{{ title }}</h1>
        <slot name="center"></slot>
      </div>

      <div class="flex items-center justify-end gap-2 flex-1 z-10">
        <slot name="right">
          <button v-if="showSettings" @click="router.push('/settings')" class="p-2 rounded-full active:bg-slate-100 transition-colors" aria-label="Settings">
            <span class="i-mdi-cog-outline text-2xl text-slate-600"></span>
          </button>
          <div v-else class="w-8 h-8"></div>
        </slot>
      </div>
    </div>
  </header>
</template>

<script setup>
import { useRouter } from 'vue-router'
import getImageUrl from '../utils/image'
import brand from '../brand'

const router = useRouter()

const props = defineProps({
  title: String,
  user: Object,
  showBack: { type: Boolean, default: false },
  showSettings: { type: Boolean, default: false },
})

</script>

<style scoped>
</style>
