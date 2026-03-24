<template>
  <div class="min-h-screen bg-slate-50 pb-32">
    <header class="p-4 bg-white border-b flex items-center justify-between sticky top-0 z-10">
      <h1 class="text-lg sm:text-xl font-bold text-slate-800">Coop Store</h1>
      <div class="flex items-center gap-3">
        <span class="hidden sm:inline text-xs font-bold text-slate-600">Balance: <span class="text-slate-800">₦ {{ money(walletBalance) }}</span></span>
        <button class="relative text-sm font-bold text-emerald-700 flex items-center gap-2" @click="toggleCart()">
          <span>Cart</span>
          <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-emerald-600 text-white text-[10px] font-black">{{ totalQty }}</span>
        </button>
        <button class="text-sm font-bold text-emerald-700" @click="$router.push('/store/orders')">Orders</button>
        <button class="text-sm font-bold text-slate-500" @click="$router.back()">Back</button>
      </div>
    </header>

    <div class="p-4 space-y-4">
      <section class="card p-5">
        <div class="flex items-center justify-between mb-3">
          <h2 class="section-title">Available Products</h2>
          <span class="text-[10px] font-black uppercase tracking-widest text-emerald-700">Buy & Checkout</span>
        </div>
        <div class="flex items-center gap-2 mb-4">
          <input v-model="q" @keyup.enter="load(1)" type="search" placeholder="Search products…" class="bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm w-full" />
          <button class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-2 rounded-lg text-xs font-bold" @click="load(1)">Search</button>
        </div>

        <div v-if="loading" class="text-slate-500 text-sm">Loading…</div>
        <div v-else-if="error" class="text-rose-700 bg-rose-50 border border-rose-200 p-3 rounded-lg text-sm">{{ error }}</div>
        <div v-else>
          <div v-if="!items.length" class="text-slate-500 text-sm">No products found.</div>
          <ul class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <li v-for="p in items" :key="p.id" class="p-3 bg-white border rounded-xl shadow-sm flex gap-3">
              <img v-if="p.image_url" :src="p.image_url" alt="image" class="w-16 h-16 rounded object-cover" />
              <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-3 mb-1">
                  <div class="font-bold text-slate-800 truncate">{{ p.name }}</div>
                  <div class="text-emerald-700 font-black text-sm whitespace-nowrap">₦ {{ money(p.selling_price) }}</div>
                </div>
                <p class="text-[12px] text-slate-600 line-clamp-2 mb-2">{{ p.description || '—' }}</p>
                <div class="flex items-center justify-end gap-2">
                  <template v-if="cart[p.id]">
                    <button class="px-2 py-1 rounded-lg border border-slate-200" @click="decQty(p.id)">-</button>
                    <span class="text-sm font-bold">{{ cart[p.id].qty }}</span>
                    <button class="px-2 py-1 rounded-lg bg-emerald-600 text-white" @click="incQty(p.id)">+</button>
                  </template>
                  <button v-else class="px-3 py-2 rounded-lg bg-emerald-600 text-white text-xs font-bold" @click="addToCart(p)">Add to Cart</button>
                </div>
              </div>
            </li>
          </ul>

          <div class="flex items-center justify-between mt-4 text-sm">
            <button class="px-3 py-2 rounded-lg border border-slate-200 bg-white disabled:opacity-50" :disabled="page <= 1 || loading" @click="load(page - 1)">Prev</button>
            <div class="text-slate-500">Page {{ page }} / {{ lastPage }}</div>
            <button class="px-3 py-2 rounded-lg border border-slate-200 bg-white disabled:opacity-50" :disabled="page >= lastPage || loading" @click="load(page + 1)">Next</button>
          </div>
        </div>
      </section>

      <section v-if="showCart" class="card p-5">
        <div class="flex items-center justify-between mb-3">
          <h2 class="section-title">Your Cart</h2>
          <button class="text-slate-500 text-xs font-bold" @click="clearCart()" :disabled="!totalQty">Clear</button>
        </div>
        <div v-if="!totalQty" class="text-slate-500 text-sm">Your cart is empty.</div>
        <div v-else class="space-y-3">
          <div v-for="ci in cartList" :key="ci.id" class="flex items-center justify-between gap-3 p-3 border rounded-lg bg-white">
            <div class="min-w-0">
              <div class="font-bold text-slate-800 truncate">{{ ci.name }}</div>
              <div class="text-xs text-slate-500">₦ {{ money(ci.selling_price) }} x {{ ci.qty }}</div>
            </div>
            <div class="flex items-center gap-2">
              <button class="px-2 py-1 rounded-lg border border-slate-200" @click="decQty(ci.id)">-</button>
              <span class="text-sm font-bold">{{ ci.qty }}</span>
              <button class="px-2 py-1 rounded-lg border border-slate-200" @click="incQty(ci.id)">+</button>
              <button class="px-2 py-1 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-xs font-bold" @click="remove(ci.id)">Remove</button>
            </div>
          </div>
          <div class="flex items-center justify-between pt-2">
            <div class="text-slate-500 text-sm">Subtotal</div>
            <div class="text-lg font-extrabold text-slate-800">₦ {{ money(subtotal) }}</div>
          </div>
          <div class="flex items-center justify-end">
            <button class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-3 rounded-xl text-sm font-bold disabled:opacity-50" :disabled="placing || !totalQty" @click="checkout()">
              <span v-if="placing" class="inline-flex items-center gap-2"><svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg> Processing…</span>
              <span v-else>Checkout</span>
            </button>
          </div>
          <div v-if="placeError" class="text-rose-700 bg-rose-50 border border-rose-200 p-3 rounded-lg text-sm">{{ placeError }}</div>
          <div v-if="placeSuccess" class="text-emerald-700 bg-emerald-50 border border-emerald-200 p-3 rounded-lg text-sm">{{ placeSuccess }}</div>
        </div>
      </section>
    </div>

    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t p-4 flex justify-around items-center">
      <button class="text-slate-400 flex flex-col items-center gap-1" @click="$router.push('/dashboard')">
        <span class="text-xl">🏠</span>
        <span class="text-[10px] font-bold">Home</span>
      </button>
      <button class="text-emerald-700 flex flex-col items-center gap-1">
        <span class="text-xl">🛒</span>
        <span class="text-[10px] font-bold">Store</span>
      </button>
      <button class="text-slate-400 flex flex-col items-center gap-1" @click="$router.push('/reports')">
        <span class="text-xl">📈</span>
        <span class="text-[10px] font-bold">Reports</span>
      </button>
    </nav>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import axios from '../http'
import { useRouter } from 'vue-router'

const items = ref([])
const loading = ref(false)
const error = ref('')
const page = ref(1)
const lastPage = ref(1)
const q = ref('')

const walletBalance = ref(0)

const showCart = ref(false)
const cart = ref({}) // { [id]: { id, name, selling_price, qty } }
const placing = ref(false)
const placeError = ref('')
const placeSuccess = ref('')

const money = (val) => Number(val || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })

const load = async (p = 1) => {
  loading.value = true
  error.value = ''
  try {
    page.value = p
    const { data } = await axios.get('/api/products', {
      params: { page: p, q: q.value || '' }
    })
    const list = Array.isArray(data) ? data : (data?.data || [])
    items.value = list
    lastPage.value = Number(data?.last_page || 1)
  } catch (e) {
    error.value = e?.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
}

const loadWallet = async () => {
  try {
    const { data } = await axios.get('/api/wallet')
    walletBalance.value = Number(data?.balance || 0)
  } catch (_) {}
}

const CART_KEY = 'coop_store_cart_v1'
const persistCart = () => {
  try { localStorage.setItem(CART_KEY, JSON.stringify(cart.value)) } catch (_) {}
}
const restoreCart = () => {
  try {
    const raw = localStorage.getItem(CART_KEY)
    if (!raw) return
    const obj = JSON.parse(raw)
    if (obj && typeof obj === 'object') {
      cart.value = obj
    }
  } catch (_) {}
}

const toggleCart = () => { showCart.value = !showCart.value }
const addToCart = (p) => {
  const existing = cart.value[p.id]
  if (existing) existing.qty += 1
  else cart.value[p.id] = { id: p.id, name: p.name, selling_price: Number(p.selling_price), qty: 1 }
  showCart.value = true
}
const incQty = (id) => { if (cart.value[id]) cart.value[id].qty += 1 }
const decQty = (id) => {
  if (!cart.value[id]) return
  cart.value[id].qty -= 1
  if (cart.value[id].qty <= 0) delete cart.value[id]
}
const remove = (id) => { if (cart.value[id]) delete cart.value[id] }
const clearCart = () => { cart.value = {} }

watch(cart, persistCart, { deep: true })

const cartList = computed(() => Object.values(cart.value))
const totalQty = computed(() => cartList.value.reduce((s, it) => s + (it.qty || 0), 0))
const subtotal = computed(() => cartList.value.reduce((s, it) => s + (Number(it.selling_price || 0) * (it.qty || 0)), 0))

const checkout = async () => {
  placeError.value = ''
  placeSuccess.value = ''
  if (!totalQty.value) return
  placing.value = true
  try {
    const payload = {
      items: cartList.value.map(it => ({ product_id: it.id, quantity: it.qty }))
    }
    const { data } = await axios.post('/api/store/orders', payload)
    placeSuccess.value = data?.message || 'Order placed successfully'
    const orderId = data?.order?.id
    clearCart()
    // Refresh wallet balance after debit
    try { await loadWallet() } catch (_) {}
    if (orderId) {
      // slight delay for UX
      setTimeout(() => {
        // Navigate to receipt
        try { window?.navigator?.vibrate?.(30) } catch (_) {}
        routerPush(`/store/orders/${orderId}`)
      }, 300)
    }
  } catch (e) {
    placeError.value = e?.response?.data?.message || e.message
  } finally {
    placing.value = false
  }
}

// Small helper to navigate without importing router explicitly in SFC setup
const routerPush = (path) => {
  try { window.location.href = `${import.meta.env.BASE_URL || '/'}${path.replace(/^\//,'')}` } catch (_) {}
}

onMounted(() => { restoreCart(); load(1); loadWallet() })
</script>

<style scoped>
.card { background: #fff; border: 1px solid #e5e7eb; border-radius: 1rem; }
.section-title { font-weight: 800; color: #0f172a; }
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
