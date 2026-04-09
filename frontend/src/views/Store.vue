<template>
  <div class="min-h-screen bg-slate-50/50 pb-32">
    <header class="header-fintech">
      <div class="navbar-inner">
        <h1 class="text-lg font-bold text-slate-800">Coop Store</h1>
        <div class="flex items-center gap-2">
          <button class="relative p-2 hover:bg-slate-100 rounded-xl transition-colors" @click="toggleCart()">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-emerald-700"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
            <span v-if="totalQty" class="absolute top-0 right-0 w-5 h-5 bg-emerald-600 text-white text-[10px] font-black rounded-full flex items-center justify-center border-2 border-white">{{ totalQty }}</span>
          </button>
          <button class="p-2 hover:bg-slate-100 rounded-xl transition-colors" @click="$router.push('/store/orders')" title="Orders">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-600"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/></svg>
          </button>
          <button @click="$router.back()" class="p-2 hover:bg-slate-100 rounded-xl transition-colors" aria-label="Go back">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-600"><path d="m15 18-6-6 6-6"/></svg>
          </button>
        </div>
      </div>
    </header>

    <div class="p-4 space-y-4">
      <section class="card p-5">
        <div class="flex items-center justify-between mb-3">
          <h2 class="section-title">Available Products</h2>
          <span class="text-[10px] font-black uppercase tracking-widest text-emerald-700">Buy & Checkout</span>
        </div>
        <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-6">
          <div class="flex-1 relative">
            <input v-model="q" @keyup.enter="load(1)" type="search" placeholder="Search products…" class="inp pl-10" />
            <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <select v-model="selectedCategory" @change="load(1)" class="inp py-2 text-xs font-bold min-w-[120px]">
              <option :value="0">All Categories</option>
              <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
            <select v-model="sortBy" @change="load(1)" class="inp py-2 text-xs font-bold">
              <option value="newest">Newest</option>
              <option value="price_asc">Price: Low-High</option>
              <option value="price_desc">Price: High-Low</option>
              <option value="name_asc">A–Z</option>
            </select>
          </div>
        </div>

        <div v-if="loading" class="text-slate-500 text-sm">Loading…</div>
        <div v-else-if="error" class="text-rose-700 bg-rose-50 border border-rose-200 p-3 rounded-lg text-sm">{{ error }}</div>
        <div v-else>
          <div v-if="!items.length" class="text-slate-500 text-sm">No products found.</div>
          <ul class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <li v-for="p in items" :key="p.id" class="p-3 bg-white border rounded-xl shadow-sm flex gap-3">
              <img v-if="p.image_url" :src="getImageUrl(p.image_url)" alt="image" class="w-16 h-16 rounded object-cover" />
              <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-3 mb-1">
                  <div class="font-bold text-slate-800 truncate flex items-center gap-2">
                    <span class="truncate cursor-pointer hover:underline" @click="openQuick(p)">{{ p.name }}</span>
                    <span v-if="isNew(p.created_at)" class="text-[10px] font-black uppercase tracking-widest bg-amber-100 text-amber-700 px-2 py-0.5 rounded">New</span>
                  </div>
                  <div class="text-emerald-700 font-black text-sm whitespace-nowrap">₦ {{ money(p.selling_price) }}</div>
                </div>
                <div v-if="p.track_stock" class="mb-1">
                  <span v-if="p.stock_quantity > 0" class="text-[10px] text-slate-500 font-bold">In Stock: {{ p.stock_quantity }}</span>
                  <span v-else class="text-[10px] text-rose-600 font-black uppercase tracking-widest">Out of Stock</span>
                </div>
                <p class="text-[12px] text-slate-600 line-clamp-2 mb-2">{{ p.description || '—' }}</p>
                <div class="flex items-center justify-end gap-2">
                  <template v-if="cart[p.id]">
                    <button class="px-2 py-1 rounded-lg border border-slate-200" @click="decQty(p.id)">-</button>
                    <span class="text-sm font-bold">{{ cart[p.id].qty }}</span>
                    <button class="px-2 py-1 rounded-lg bg-emerald-600 text-white disabled:opacity-50" @click="incQty(p.id)" :disabled="p.track_stock && cart[p.id].qty >= p.stock_quantity">+</button>
                  </template>
                  <button v-else-if="!p.track_stock || p.stock_quantity > 0" class="px-3 py-2 rounded-lg bg-emerald-600 text-white text-xs font-bold" @click="addToCart(p)">Add to Cart</button>
                  <button v-else disabled class="px-3 py-2 rounded-lg bg-slate-200 text-slate-500 text-xs font-bold">Sold Out</button>
                  <button class="px-3 py-2 rounded-lg border border-slate-200 text-xs" @click="openQuick(p)">Quick View</button>
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

          <div class="mt-2">
            <label class="block text-[12px] font-bold text-slate-600 mb-1">Order Note (optional)</label>
            <textarea v-model="orderNote" rows="2" placeholder="Notes to the coop store…" class="w-full bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm"></textarea>
          </div>

          <div v-if="hasInsufficient" class="mt-2 text-xs text-amber-700 bg-amber-50 border border-amber-200 p-2 rounded space-y-2">
            <div>
              Insufficient Coop Balance. Short by ₦ {{ money(shortfall) }}.
              <button class="underline ml-2" @click="$router.push('/wallet')">Top up</button>
            </div>
            <div class="p-2 bg-white border border-amber-200 rounded text-slate-700">
              <div class="text-[11px] font-black uppercase tracking-widest text-amber-700 mb-2">Buy on Credit (Murabaha)</div>
              
              <div v-if="!canUseFinancing" class="p-2 bg-rose-50 border border-rose-200 rounded text-rose-700 text-[11px] font-bold">
                {{ financingReason }} Please complete your existing obligations first.
              </div>
              <template v-else>
                <div class="grid grid-cols-2 gap-2 items-end">
                  <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Tenor (months)</label>
                    <select v-model.number="creditMonths" class="w-full bg-white border border-slate-200 rounded-lg px-2 py-2 text-sm">
                      <option v-for="m in [6,7,8,9,10,11,12]" :key="m" :value="m">{{ m }} months</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Profit Rate</label>
                    <select v-model.number="creditProfit" class="w-full bg-white border border-slate-200 rounded-lg px-2 py-2 text-sm">
                      <option :value="0.10">10%</option>
                      <option :value="0.12">12%</option>
                      <option :value="0.15">15%</option>
                    </select>
                  </div>
                </div>
                <div class="mt-2 text-[11px] text-slate-500">
                  Est. total on credit: ₦ {{ money(creditEstimateTotal) }} • Est. monthly: ₦ {{ money(creditMonthly) }}
                  <div class="mt-1 font-bold text-emerald-700" v-if="eligData">Borrowing Limit: ₦ {{ money(eligData.limit) }}</div>
                </div>
                <div class="mt-2">
                  <button class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-xs font-bold disabled:opacity-50" :disabled="placing || !totalQty || !creditValid || exceedsLimit" @click="creditCheckout()">
                    Apply & Buy on Credit
                  </button>
                  <div v-if="exceedsLimit" class="mt-1 text-rose-600 text-[10px] font-black uppercase">
                    Cart total exceeds your borrowing limit (₦ {{ money(eligData?.limit) }})
                  </div>
                </div>
              </template>
            </div>
          </div>

          <div class="flex items-center justify-end mt-2">
            <button class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-3 rounded-xl text-sm font-bold disabled:opacity-50" :disabled="placing || !totalQty || hasInsufficient" @click="checkout()">
              <span v-if="placing && purchaseMode === 'cash'" class="inline-flex items-center gap-2"><svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a 8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg> Processing…</span>
              <span v-else-if="hasInsufficient">Insufficient Balance</span>
              <span v-else>Checkout</span>
            </button>
          </div>

          <div v-if="placeError" class="text-rose-700 bg-rose-50 border border-rose-200 p-3 rounded-lg text-sm">{{ placeError }}</div>
          <div v-if="placeSuccess" class="text-emerald-700 bg-emerald-50 border border-emerald-200 p-3 rounded-lg text-sm">{{ placeSuccess }}</div>
        </div>
      </section>
    </div>

    <!-- Quick View Modal -->
    <div v-if="selectedProduct" class="fixed inset-0 z-20 flex items-end sm:items-center justify-center">
      <div class="absolute inset-0 bg-black/40" @click="closeQuick()"></div>
      <div class="relative bg-white w-full sm:max-w-md sm:rounded-2xl sm:shadow-xl p-4 border-t sm:border rounded-t-2xl z-30">
        <div class="flex items-start gap-3">
          <img v-if="selectedProduct.image_url" :src="getImageUrl(selectedProduct.image_url)" alt="image" class="w-20 h-20 rounded object-cover" />
          <div class="flex-1 min-w-0">
            <div class="font-bold text-slate-800 truncate">{{ selectedProduct.name }}</div>
            <div class="text-emerald-700 font-black text-sm">₦ {{ money(selectedProduct.selling_price) }}</div>
            <div v-if="selectedProduct.track_stock" class="mt-1">
              <span v-if="selectedProduct.stock_quantity > 0" class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">In Stock: {{ selectedProduct.stock_quantity }}</span>
              <span v-else class="text-[10px] text-rose-600 font-black uppercase tracking-widest">Sold Out</span>
            </div>
            <p class="text-[12px] text-slate-600 mt-1">{{ selectedProduct.description || '—' }}</p>
          </div>
          <button class="text-slate-400 hover:text-slate-600" @click="closeQuick()">✕</button>
        </div>
        <div class="mt-4 flex items-center justify-between" v-if="!selectedProduct.track_stock || selectedProduct.stock_quantity > 0">
          <div class="flex items-center gap-2">
            <button class="px-3 py-2 rounded-lg border border-slate-200" @click="quickQty = Math.max(1, (Number(quickQty)||1)-1)">-</button>
            <input v-model.number="quickQty" type="number" min="1" :max="selectedProduct.track_stock ? selectedProduct.stock_quantity : undefined" class="w-16 text-center bg-white border border-slate-200 rounded-lg px-2 py-2 text-sm" />
            <button class="px-3 py-2 rounded-lg border border-slate-200" @click="quickQty = Math.min((selectedProduct.track_stock ? selectedProduct.stock_quantity : 999), (Number(quickQty)||1)+1)">+</button>
          </div>
          <div class="flex items-center gap-2">
            <button class="px-4 py-2 rounded-lg border border-slate-200 bg-white" @click="closeQuick()">Cancel</button>
            <button class="px-4 py-2 rounded-lg bg-emerald-600 text-white font-bold" @click="addQuickToCart()">Add to Cart</button>
          </div>
        </div>
        <div class="mt-4 flex items-center justify-center" v-else>
          <button class="w-full px-4 py-3 rounded-xl bg-slate-100 text-slate-500 font-bold text-sm uppercase tracking-widest cursor-not-allowed">Product Out of Stock</button>
        </div>
      </div>
    </div>

    <!-- PIN Prompt Modal -->
    <CustomNotice
      v-model="pinPrompt.visible"
      :type="'info'"
      :title="'Confirm Purchase'"
      :message="'Enter your 4-digit Transaction PIN to confirm checkout.'"
      :prompt="true"
      inputLabel="Transaction PIN (4 digits)"
      confirmText="Confirm"
      cancelText="Cancel"
      :busy="placing"
      @confirm="handlePinConfirm"
      @cancel="handlePinCancel"
    />

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
import getImageUrl from '../utils/image'
import { useRouter } from 'vue-router'
import CustomNotice from '../components/CustomNotice.vue'

const items = ref([])
const loading = ref(false)
const error = ref('')
const page = ref(1)
const lastPage = ref(1)
const q = ref('')
const selectedCategory = ref(0)
const categories = ref([])
const sortBy = ref('newest')

const walletBalance = ref(0)
const eligData = ref(null)

const showCart = ref(false)
const cart = ref({}) // { [id]: { id, name, selling_price, qty } }
const placing = ref(false)
const placeError = ref('')
const placeSuccess = ref('')
// Purchase mode: 'cash' or 'credit'
const purchaseMode = ref('cash')
// PIN prompt modal state
const pinPrompt = ref({ visible: false })

// Quick view modal state
const selectedProduct = ref(null)
const quickQty = ref(1)

// Optional order note
const orderNote = ref('')

const money = (val) => Number(val || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
const isNew = (dt) => {
  if (!dt) return false
  const d = new Date(dt)
  if (isNaN(d)) return false
  const now = Date.now()
  return (now - d.getTime()) <= (14 * 24 * 60 * 60 * 1000) // 14 days
}

const load = async (p = 1) => {
  loading.value = true
  error.value = ''
  try {
    page.value = p
    const { data } = await axios.get('/api/products', {
      params: { page: p, q: q.value || '', category_id: selectedCategory.value, sort: sortBy.value }
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

const loadCategories = async () => {
  try {
    const { data } = await axios.get('/api/products/categories')
    categories.value = data
  } catch (_) {}
}

const loadWallet = async () => {
  try {
    const { data } = await axios.get('/api/wallet')
    walletBalance.value = Number(data?.balance || 0)
  } catch (_) {}
}

const loadStoreEligibility = async () => {
  try {
    const { data } = await axios.get('/api/store/eligibility')
    eligData.value = data
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
const shortfall = computed(() => Math.max(0, Number(subtotal.value) - Number(walletBalance.value)))
const hasInsufficient = computed(() => shortfall.value > 0)

const canUseFinancing = computed(() => {
  if (!eligData.value) return true
  return !eligData.value.has_active_financing && !eligData.value.has_active_loan
})

const financingReason = computed(() => {
  if (!eligData.value) return ''
  if (eligData.value.has_active_financing) return 'You have an active store financing order.'
  if (eligData.value.has_active_loan) return 'You have an active loan (Qard Hasan).'
  return ''
})

const exceedsLimit = computed(() => {
  if (!eligData.value) return false
  return Number(subtotal.value) > Number(eligData.value.limit)
})

// Murabaha (credit) controls
const creditMonths = ref(12)
const creditProfit = ref(0.12) // 12% default within 10–15%
const creditEstimateTotal = computed(() => {
  const rate = Number(creditProfit.value || 0)
  const base = Number(subtotal.value || 0)
  return Math.max(0, Math.round(base * (1 + rate) * 100) / 100)
})
const creditMonthly = computed(() => {
  const months = Math.max(1, Number(creditMonths.value || 1))
  return Math.round((Number(creditEstimateTotal.value || 0) / months) * 100) / 100
})
const creditValid = computed(() => {
  const m = Number(creditMonths.value)
  const r = Number(creditProfit.value)
  return m >= 6 && m <= 12 && r >= 0.10 && r <= 0.15
})

const openQuick = (p) => {
  selectedProduct.value = p
  quickQty.value = 1
}
const closeQuick = () => { selectedProduct.value = null }
const addQuickToCart = () => {
  const p = selectedProduct.value
  if (!p) return
  const qty = Math.max(1, Number(quickQty.value) || 1)
  const existing = cart.value[p.id]
  if (existing) existing.qty += qty
  else cart.value[p.id] = { id: p.id, name: p.name, selling_price: Number(p.selling_price), qty }
  closeQuick()
  showCart.value = true
}

const checkout = () => {
  placeError.value = ''
  placeSuccess.value = ''
  purchaseMode.value = 'cash'
  if (!totalQty.value) return
  // Open custom PIN prompt modal
  pinPrompt.value.visible = true
}

const creditCheckout = () => {
  placeError.value = ''
  placeSuccess.value = ''
  purchaseMode.value = 'credit'
  if (!totalQty.value || !creditValid.value) return
  pinPrompt.value.visible = true
}

const handlePinConfirm = async (val) => {
  let pin = String(val || '').trim()
  if (!/^\d{4}$/.test(pin)) {
    alert('Please enter a valid 4-digit PIN')
    return
  }
  placing.value = true
  try {
    const payload = {
      items: cartList.value.map(it => ({ product_id: it.id, quantity: it.qty })),
      note: (orderNote.value || '').trim() || undefined,
      pin,
    }
    if (purchaseMode.value === 'credit') {
      payload.financing = {
        enabled: true,
        months: Number(creditMonths.value),
        profit_rate: Number(creditProfit.value),
      }
    }
    const { data } = await axios.post('/api/store/orders', payload)
    placeSuccess.value = data?.message || (purchaseMode.value === 'credit' ? 'Application submitted successfully' : 'Order placed successfully')
    const orderId = data?.order?.id
    clearCart()
    // Refresh wallet balance (may be unchanged for credit orders)
    try { await loadWallet() } catch (_) {}
    pinPrompt.value.visible = false
    if (orderId) {
      // slight delay for UX
      setTimeout(() => {
        // Navigate to receipt
        try { window?.navigator?.vibrate?.(30) } catch (_) {}
        routerPush(`/store/orders/${orderId}`)
      }, 300)
    }
  } catch (e) {
    pinPrompt.value.visible = false
    const status = e?.response?.status
    const msg = e?.response?.data?.message || e.message
    if (status === 409) {
      placeError.value = 'You need to set your Transaction PIN before making purchases. Go to Profile > Transaction PIN.'
    } else if (status === 403) {
      placeError.value = 'Invalid Transaction PIN. Please try again.'
    } else {
      placeError.value = msg
    }
  } finally {
    placing.value = false
    // reset mode back to cash for next action
    purchaseMode.value = 'cash'
  }
}

const handlePinCancel = () => {
  pinPrompt.value.visible = false
}

// Small helper to navigate without importing router explicitly in SFC setup
const routerPush = (path) => {
  try { window.location.href = `${import.meta.env.BASE_URL || '/'}${path.replace(/^\//,'')}` } catch (_) {}
}

onMounted(() => { restoreCart(); load(1); loadWallet(); loadCategories(); loadStoreEligibility() })
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
