<script setup>
import { ref, onMounted } from 'vue'
import { brand } from '../brand'
import { useRouter } from 'vue-router'

const router = useRouter()
const isMenuOpen = ref(false)
const scrolled = ref(false)

const features = [
  {
    title: 'Interest-Free Loans',
    description: 'Access Qard Hasan (benevolent loans) without the burden of interest, designed for your growth and needs.',
    icon: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>`
  },
  {
    title: 'Ethical Savings',
    description: 'Grow your wealth through Shariah-compliant savings plans, including Gold savings and targeted goals.',
    icon: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 0 0-2.25-2.25H15a3 3 0 1 1-6 0H5.25A2.25 2.25 0 0 0 3 12m18 0v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 9m18 0V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v3" /></svg>`
  },
  {
    title: 'Takaful Protection',
    description: 'Shared responsibility and mutual protection through our community-based Islamic insurance alternative.',
    icon: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-5.7L18.75 5.25v6.75C18.75 17.052 14.826 21.15 12 21.15c-2.826 0-6.75-4.098-6.75-9.15V5.25L12 4.05Z" /></svg>`
  },
  {
    title: 'Community Investment',
    description: 'Participate in profitable projects through Mudarabah and Musharakah investment opportunities.',
    icon: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.307a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941" /></svg>`
  }
]

const stats = [
  { label: 'Active Members', value: '10k+' },
  { label: 'Interest-Free Loans', value: '$5M+' },
  { label: 'Community Projects', value: '150+' },
  { label: 'Member Satisfaction', value: '99%' }
]

onMounted(() => {
  window.addEventListener('scroll', () => {
    scrolled.value = window.scrollY > 20
  })
})

function navigateTo(route) {
  router.push(route)
}
</script>

<template>
  <div class="min-h-screen bg-white font-sans text-slate-900 selection:bg-brand-100 selection:text-brand-900">
    <!-- Navigation -->
    <nav 
      class="fixed top-0 w-full z-50 transition-all duration-300"
      :class="scrolled ? 'bg-white/80 backdrop-blur-md shadow-sm py-3' : 'bg-transparent py-5'"
    >
      <div class="container mx-auto px-6 flex items-center justify-between">
        <div class="flex items-center gap-2 cursor-pointer" @click="navigateTo('/')">
          <img :src="brand.logo" :alt="brand.name" class="h-10 w-auto" />
          <span class="font-bold text-xl tracking-tight hidden sm:block uppercase">{{ brand.name }}</span>
        </div>

        <!-- Desktop Menu -->
        <div class="hidden md:flex items-center gap-8 font-medium text-sm">
          <a href="#features" class="hover:text-brand-600 transition-colors">Features</a>
          <a href="#about" class="hover:text-brand-600 transition-colors">About</a>
          <a href="#contact" class="hover:text-brand-600 transition-colors">Contact</a>
          <div class="h-4 w-[1px] bg-slate-200"></div>
          <button @click="navigateTo('/login')" class="hover:text-brand-600 transition-colors">Log in</button>
          <button 
            @click="navigateTo('/register')"
            class="bg-brand-600 text-white px-5 py-2.5 rounded-full hover:bg-brand-700 transition-all shadow-lg shadow-brand-200 active:scale-95"
          >
            Get Started
          </button>
        </div>

        <!-- Mobile Menu Button -->
        <button class="md:hidden p-2" @click="isMenuOpen = !isMenuOpen">
          <svg v-if="!isMenuOpen" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
          </svg>
          <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Mobile Menu -->
      <transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 -translate-y-4"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-4"
      >
        <div v-if="isMenuOpen" class="md:hidden bg-white border-t border-slate-100 absolute w-full shadow-xl">
          <div class="flex flex-col p-6 gap-4">
            <a href="#features" @click="isMenuOpen = false" class="text-lg font-medium">Features</a>
            <a href="#about" @click="isMenuOpen = false" class="text-lg font-medium">About</a>
            <a href="#contact" @click="isMenuOpen = false" class="text-lg font-medium">Contact</a>
            <hr class="border-slate-100" />
            <button @click="navigateTo('/login')" class="text-lg font-medium text-left">Log in</button>
            <button 
              @click="navigateTo('/register')"
              class="bg-brand-600 text-white px-5 py-3 rounded-xl font-bold text-center"
            >
              Get Started
            </button>
          </div>
        </div>
      </transition>
    </nav>

    <!-- Hero Section -->
    <header class="relative pt-32 pb-20 md:pt-48 md:pb-32 overflow-hidden">
      <!-- Background Elements -->
      <div class="absolute top-0 right-0 -translate-y-1/4 translate-x-1/4 w-[600px] h-[600px] bg-brand-50 rounded-full blur-3xl opacity-50 -z-10"></div>
      <div class="absolute bottom-0 left-0 translate-y-1/4 -translate-x-1/4 w-[400px] h-[400px] bg-brand-100 rounded-full blur-3xl opacity-30 -z-10"></div>

      <div class="container mx-auto px-6">
        <div class="max-w-4xl mx-auto text-center">
          <div class="inline-flex items-center gap-2 bg-brand-50 text-brand-700 px-4 py-2 rounded-full text-sm font-bold mb-8 animate-bounce">
            <span class="relative flex h-2 w-2">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-2 w-2 bg-brand-500"></span>
            </span>
            Modern Shariah-Compliant Finance
          </div>
          <h1 class="text-5xl md:text-7xl font-extrabold tracking-tight mb-8 leading-[1.1]">
            Ethical banking for a <span class="text-brand-600">better future.</span>
          </h1>
          <p class="text-xl text-slate-600 mb-10 max-w-2xl mx-auto leading-relaxed">
            Join thousands of members in a transparent, interest-free cooperative designed to empower your financial growth while staying true to your values.
          </p>
          <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <button 
              @click="navigateTo('/register')"
              class="w-full sm:w-auto bg-brand-600 text-white px-8 py-4 rounded-full text-lg font-bold hover:bg-brand-700 transition-all shadow-xl shadow-brand-200 active:scale-95"
            >
              Start Saving Now
            </button>
            <button 
              @click="navigateTo('/login')"
              class="w-full sm:w-auto bg-white text-slate-900 border border-slate-200 px-8 py-4 rounded-full text-lg font-bold hover:bg-slate-50 transition-all active:scale-95"
            >
              Member Login
            </button>
          </div>

          <!-- App Preview Placeholder -->
          <div class="mt-20 relative">
            <div class="absolute inset-0 bg-gradient-to-t from-white via-transparent to-transparent z-10"></div>
            <div class="bg-slate-900 rounded-2xl p-4 shadow-2xl mx-auto max-w-5xl overflow-hidden border border-slate-800">
               <div class="bg-slate-800 h-6 w-full rounded-t-lg mb-4 flex items-center px-4 gap-1.5">
                  <div class="w-2.5 h-2.5 rounded-full bg-red-500"></div>
                  <div class="w-2.5 h-2.5 rounded-full bg-amber-500"></div>
                  <div class="w-2.5 h-2.5 rounded-full bg-blue-500"></div>
               </div>
               <div class="aspect-[16/9] bg-slate-950 rounded-b-lg flex items-center justify-center p-8">
                  <div class="grid grid-cols-3 gap-6 w-full h-full opacity-50">
                      <div class="bg-slate-800 rounded-xl animate-pulse"></div>
                      <div class="bg-slate-800 rounded-xl animate-pulse delay-75"></div>
                      <div class="bg-slate-800 rounded-xl animate-pulse delay-150"></div>
                      <div class="col-span-2 bg-slate-800 rounded-xl animate-pulse delay-100"></div>
                      <div class="bg-slate-800 rounded-xl animate-pulse delay-200"></div>
                  </div>
                  <div class="absolute inset-0 flex items-center justify-center z-20">
                      <img :src="brand.logo" class="h-24 w-auto drop-shadow-2xl" />
                  </div>
               </div>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Stats Section -->
    <section class="bg-slate-50 py-20 border-y border-slate-100">
      <div class="container mx-auto px-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
          <div v-for="stat in stats" :key="stat.label" class="text-center">
            <div class="text-4xl md:text-5xl font-extrabold text-brand-600 mb-2">{{ stat.value }}</div>
            <div class="text-slate-500 font-medium">{{ stat.label }}</div>
          </div>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-24">
      <div class="container mx-auto px-6">
        <div class="text-center max-w-3xl mx-auto mb-20">
          <h2 class="text-3xl md:text-5xl font-bold mb-6">Financial services built on trust and ethics.</h2>
          <p class="text-xl text-slate-600">Everything you need from a modern financial institution, without the interest-based debt traps.</p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
          <div 
            v-for="feature in features" 
            :key="feature.title"
            class="p-8 rounded-3xl border border-slate-100 hover:border-brand-200 hover:bg-brand-50/50 transition-all group"
          >
            <div class="w-14 h-14 bg-brand-100 text-brand-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform" v-html="feature.icon"></div>
            <h3 class="text-xl font-bold mb-4">{{ feature.title }}</h3>
            <p class="text-slate-600 leading-relaxed">{{ feature.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="py-24 container mx-auto px-6">
      <div class="bg-brand-600 rounded-[3rem] p-12 md:p-20 text-center relative overflow-hidden">
        <!-- Decoration -->
        <div class="absolute top-0 right-0 w-64 h-64 bg-brand-500 rounded-full -translate-y-1/2 translate-x-1/2 opacity-50"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 bg-brand-700 rounded-full translate-y-1/2 -translate-x-1/2 opacity-30"></div>
        
        <div class="relative z-10">
          <h2 class="text-4xl md:text-6xl font-bold text-white mb-8">Ready to grow your wealth ethically?</h2>
          <p class="text-xl text-brand-50 mb-12 max-w-2xl mx-auto">
            Join {{ brand.name }} today and experience a transparent, community-driven approach to finance.
          </p>
          <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <button 
              @click="navigateTo('/register')"
              class="w-full sm:w-auto bg-white text-brand-600 px-10 py-5 rounded-full text-xl font-bold hover:bg-brand-50 transition-all shadow-xl active:scale-95"
            >
              Get Started for Free
            </button>
            <button 
              @click="navigateTo('/support')"
              class="w-full sm:w-auto bg-brand-700/50 text-white border border-brand-400/30 px-10 py-5 rounded-full text-xl font-bold hover:bg-brand-700/70 transition-all active:scale-95"
            >
              Contact Support
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="bg-slate-950 text-slate-400 py-20">
      <div class="container mx-auto px-6">
        <div class="grid md:grid-cols-4 gap-12 mb-16">
          <div class="col-span-2">
            <div class="flex items-center gap-2 mb-8">
              <img :src="brand.logo" class="h-10 w-auto brightness-0 invert" />
              <span class="text-white font-bold text-xl uppercase">{{ brand.name }}</span>
            </div>
            <p class="max-w-md text-lg leading-relaxed">
              {{ brand.name }} is a community-driven cooperative committed to providing ethical, Shariah-compliant financial solutions for modern living.
            </p>
          </div>
          <div>
            <h4 class="text-white font-bold mb-6">Quick Links</h4>
            <ul class="space-y-4">
              <li><a href="#features" class="hover:text-white transition-colors">Features</a></li>
              <li><button @click="navigateTo('/register')" class="hover:text-white transition-colors text-left">Join Us</button></li>
              <li><button @click="navigateTo('/login')" class="hover:text-white transition-colors text-left">Member Portal</button></li>
              <li><button @click="navigateTo('/sharia-board')" class="hover:text-white transition-colors text-left">Shariah Board</button></li>
            </ul>
          </div>
          <div>
            <h4 class="text-white font-bold mb-6">Legal & Support</h4>
            <ul class="space-y-4">
              <li><button @click="navigateTo('/privacy')" class="hover:text-white transition-colors text-left">Privacy Policy</button></li>
              <li><button @click="navigateTo('/policy')" class="hover:text-white transition-colors text-left">Terms of Service</button></li>
              <li><button @click="navigateTo('/support')" class="hover:text-white transition-colors text-left">Help Center</button></li>
              <li><button @click="navigateTo('/transparency')" class="hover:text-white transition-colors text-left">Transparency</button></li>
            </ul>
          </div>
        </div>
        <div class="pt-8 border-t border-slate-900 flex flex-col md:flex-row justify-between items-center gap-6">
          <p>&copy; {{ new Date().getFullYear() }} {{ brand.name }}. All rights reserved.</p>
          <div class="flex items-center gap-6">
            <!-- Social Icons Placeholder -->
            <a href="#" class="hover:text-white transition-colors">Twitter</a>
            <a href="#" class="hover:text-white transition-colors">LinkedIn</a>
            <a href="#" class="hover:text-white transition-colors">Facebook</a>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.container {
  max-width: 1280px;
}
</style>


