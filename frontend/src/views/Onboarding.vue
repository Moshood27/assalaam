<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { Swiper, SwiperSlide } from 'swiper/vue'
import { Pagination } from 'swiper/modules'
import 'swiper/css'
import 'swiper/css/pagination'
import brand from '../brand'

const router = useRouter()
const route = useRoute()
const modules = [Pagination]

const finishOnboarding = () => {
  try { localStorage.setItem('has_seen_onboarding', 'true') } catch (_) {}
  const token = localStorage.getItem('token')
  const redirect = route.query.redirect || (token ? '/dashboard' : '/login')
  router.replace(String(redirect))
}

const slides = ref([
  {
    title: 'Manage Your Savings',
    desc: 'Save and track your contributions with ease. Withdraw to wallet when you need it.',
    icon: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-20 h-20 text-blue-600">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>`
  },
  {
    title: 'Request Loans',
    desc: 'Apply for halal-friendly Qard Hasan loans directly in the app.',
    icon: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-20 h-20 text-blue-600">
      <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
    </svg>`
  },
  {
    title: 'Instant Notifications',
    desc: 'Stay updated about approvals, disbursements, and account activity.',
    icon: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-20 h-20 text-blue-600">
      <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0M3.124 7.5A8.969 8.969 0 015.292 3m13.416 0a8.969 8.969 0 012.168 4.5" />
    </svg>`
  },
  {
    title: 'Bills, VTU, and Store',
    desc: 'Top-up airtime & data, pay bills, and shop products with your wallet.',
    icon: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-20 h-20 text-blue-600">
      <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
    </svg>`
  },
])

onMounted(() => {
  // If user already saw onboarding, skip quickly
  try {
    if (localStorage.getItem('has_seen_onboarding') === 'true') {
      const token = localStorage.getItem('token')
      router.replace(token ? '/dashboard' : '/login')
    }
  } catch (_) {}
})
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <!-- Header / Brand -->
    <div class="p-6 pt-10 flex items-center justify-center">
      <img :src="brand.logo" :alt="brand.name" class="h-10" />
    </div>

    <!-- Slides -->
    <div class="flex-1">
      <Swiper
        :modules="modules"
        :pagination="{ clickable: true }"
        class="h-full"
      >
        <SwiperSlide v-for="(s, i) in slides" :key="i">
          <div class="h-full flex flex-col items-center justify-center text-center px-8 gap-5">
            <div class="flex items-center justify-center mb-4" v-html="s.icon"></div>
            <h2 class="text-3xl font-black tracking-tighter text-slate-800">{{ s.title }}</h2>
            <p class="text-slate-500 max-w-sm leading-relaxed">{{ s.desc }}</p>
          </div>
        </SwiperSlide>
      </Swiper>
    </div>

    <!-- Footer CTA -->
    <div class="p-6 pb-10">
      <button class="btn-primary w-full py-3 text-base" @click="finishOnboarding">
        Get Started
      </button>
      <button class="btn-ghost w-full py-2 text-sm mt-2" @click="finishOnboarding">Skip</button>
    </div>
  </div>
</template>

<style scoped>
/* Tweak Swiper bullets for our theme */
:deep(.swiper-pagination-bullet) {
  background: rgb(203 213 225); /* slate-300 */
  opacity: 1;
}
:deep(.swiper-pagination-bullet-active) {
  background: rgb(16 185 129); /* blue-500 */
}
</style>


