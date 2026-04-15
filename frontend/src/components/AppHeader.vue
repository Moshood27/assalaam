<template>
  <header class="sticky top-0 z-40 bg-white/80 backdrop-blur-xl border-b border-slate-200/60 pt-[env(safe-area-inset-top)] shadow-sm">
    <div class="h-16 flex items-center justify-between px-4 max-w-5xl mx-auto relative">
      <div class="flex items-center gap-3 min-w-0 flex-1 z-10">
        <slot name="left">
          <button v-if="showBack" @click="router.back()" class="p-2 -ml-2 rounded-full active:bg-slate-100 transition-colors" aria-label="Go back">
            <span class="i-mdi-arrow-left text-2xl text-slate-700"></span>
          </button>
          <button v-else-if="user" @click="router.push('/profile')" class="flex items-center gap-3 min-w-0 group" aria-label="View profile">
            <div class="w-10 h-10 rounded-full overflow-hidden bg-emerald-700 flex items-center justify-center text-white font-bold text-xl shrink-0 shadow-sm group-active:scale-95 transition-transform border border-emerald-600/20">
              <img v-if="user.passport_url" :src="getImageUrl(user.passport_url)" alt="Profile" class="w-10 h-10 object-cover" />
              <span v-else>{{ (user.full_name || 'M')[0] }}</span>
            </div>
            <div class="text-left min-w-0">
              <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider leading-none mb-1 opacity-80">Welcome back,</p>
              <h2 class="text-sm font-black text-slate-800 uppercase truncate max-w-[120px] sm:max-w-[200px]">{{ user.full_name }}</h2>
            </div>
          </button>
        </slot>
      </div>

      <div class="absolute left-1/2 -translate-x-1/2 pointer-events-none text-center px-4 w-1/3">
        <h1 v-if="title" class="text-base font-bold text-slate-800 truncate">{{ title }}</h1>
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

const router = useRouter()

const props = defineProps({
  title: String,
  user: Object,
  showBack: { type: Boolean, default: false },
  showSettings: { type: Boolean, default: false },
})

</script>

<style scoped>
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
.i-mdi-arrow-left { -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>'); }
.i-mdi-cog-outline { -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 15.5A3.5 3.5 0 018.5 12 3.5 3.5 0 0112 8.5a3.5 3.5 0 013.5 3.5 3.5 3.5 0 01-3.5 3.5m7.43-2.53c.04-.32.07-.64.07-.97 0-.33-.03-.66-.07-1l2.11-1.63c.19-.15.24-.42.12-.64l-2-3.46c-.12-.22-.39-.31-.61-.22l-2.49 1c-.52-.39-1.06-.73-1.69-.98l-.37-2.65c-.04-.24-.25-.42-.5-.42h-4c-.25 0-.46.18-.5.42l-.37 2.65c-.63.25-1.17.59-1.69.98l-2.49-1c-.22-.09-.49 0-.61.22l-2 3.46c-.13.22-.07.49.12.64L4.57 11c-.04.34-.07.67-.07 1 0 .33.03.65.07.97l-2.11 1.66c-.19.15-.25.42-.12.64l2 3.46c.12.22.39.3.61.22l2.49-1.01c.52.4 1.06.74 1.69.99l.37 2.65c.04.24.25.42.5.42h4c.25 0 .46-.18.5-.42l.37-2.65c.63-.26 1.17-.59 1.69-.99l2.49 1.01c.22.08.49-.01.61-.22l2-3.46c.12-.22.07-.49-.12-.64l-2.11-1.66z"/></svg>'); }
.i-mdi-cart-outline { -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M15.5 19a1.5 1.5 0 100 3 1.5 1.5 0 000-3zM7 19a1.5 1.5 0 100 3 1.5 1.5 0 000-3zM18.1 7.1L19 4H5L4.1 1h-3v2h2l3.6 11.6c.2.6.8 1 1.4 1h10.3l3.6-6c.3-.5.3-1.1 0-1.6zM17.3 9l-2.3 4h-7l-1.3-4h10.6z"/></svg>'); }
.i-mdi-file-document-outline { -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zM6 20V4h7v5h5v11H6z"/></svg>'); }
.i-mdi-store-outline { -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18.36 9l.6 3H5.04l.6-3h12.72M20 4H4v2h16V4zm1.85 6.33c-.11-.54-.58-.93-1.13-.93h-.12l-.6-3c-.11-.54-.58-.93-1.13-.93H5.13c-.55 0-1.02.39-1.13.93l-.6 3h-.12c-.55 0-1.02.39-1.13.93-.11.53.09 1.07.5 1.45.05.05.11.09.16.13V19c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-6.17c.05-.04.11-.08.16-.13.41-.38.61-.92.5-1.45zM17 18H7v-6h10v6z"/></svg>'); }
.i-mdi-store-plus-outline { -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18 13.08c-.33 0-.64.05-.94.13-.04-.04-.08-.08-.13-.13-.41-.38-.61-.92-.5-1.45l.11-.53c.11-.54.58-.93 1.13-.93h.12l.6-3c.11-.54.58-.93 1.13-.93H5.13c-.55 0-1.02.39-1.13.93l-.6 3h-.12c-.55 0-1.02.39-1.13.93-.11.53.09 1.07.5 1.45.05.05.11.09.16.13V19c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-.92c-.8.15-1.64-.1-2.22-.68-.58-.58-.83-1.42-.68-2.22.08-.3.13-.61.13-.94l-.01-.16zM17 18H7v-6h10v6zM18.36 9l.6 3H5.04l.6-3h12.72M20 4H4v2h16V4zm2 14v2h-2v2h-2v-2h-2v-2h2v-2h2v2h2z"/></svg>'); }
.i-mdi-history { -webkit-mask-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M13.5 8H12v5l4.28 2.54.72-1.21-3.5-2.08V8M13 3c-4.97 0-9 4.03-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42C8.27 19.99 10.51 21 13 21c4.97 0 9-4.03 9-9s-4.03-9-9-9z"/></svg>'); }
</style>
