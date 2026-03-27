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
    icon: '💰'
  },
  {
    title: 'Request Loans',
    desc: 'Apply for halal-friendly Qard Hasan loans directly in the app.',
    icon: '📄'
  },
  {
    title: 'Get Instant Notifications',
    desc: 'Stay updated about approvals, disbursements, and account activity.',
    icon: '🔔'
  },
  {
    title: 'Bills, VTU, and Store',
    desc: 'Top-up airtime & data, pay bills, and shop products with your wallet.',
    icon: '🛒'
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
            <div class="text-6xl">{{ s.icon }}</div>
            <h2 class="text-2xl font-black tracking-tight">{{ s.title }}</h2>
            <p class="text-slate-600 max-w-sm">{{ s.desc }}</p>
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
  background: rgb(16 185 129); /* emerald-500 */
}
</style>
