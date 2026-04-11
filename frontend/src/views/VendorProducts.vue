<template>
  <div class="min-h-screen bg-slate-50 pb-32">
    <header class="header-fintech">
      <div class="navbar-inner">
        <div class="flex items-center gap-3">
          <button @click="$router.back()" class="p-2 -ml-2 rounded-full active:bg-slate-100 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <h1 class="text-lg font-bold text-slate-800">My Products</h1>
        </div>
        <button @click="openCreateModal" class="bg-emerald-700 text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-emerald-800 transition-colors">Add New</button>
      </div>
    </header>

    <div class="p-4 space-y-4">
      <div v-if="loading" class="text-center py-12">
        <p class="text-slate-400 font-bold uppercase tracking-widest text-[10px]">Loading products...</p>
      </div>
      
      <div v-else-if="products.length === 0" class="bg-white rounded-[2rem] p-12 text-center border border-dashed border-slate-200">
        <div class="text-4xl mb-4">📦</div>
        <h3 class="text-sm font-bold text-slate-800 mb-1">No products yet</h3>
        <p class="text-xs text-slate-500 mb-6">Start listing your products to sell to members.</p>
        <button @click="openCreateModal" class="px-6 py-3 rounded-2xl bg-emerald-700 text-white font-bold text-xs uppercase tracking-wider">Add your first product</button>
      </div>

      <div v-else class="grid gap-4">
        <div v-for="p in products" :key="p.id" class="bg-white p-4 rounded-3xl shadow-sm border border-slate-100 flex gap-4 relative overflow-hidden group">
          <div class="w-24 h-24 rounded-2xl bg-slate-50 overflow-hidden flex-shrink-0 border border-slate-100">
            <img v-if="p.image_url" :src="getImageUrl(p.image_url)" class="w-full h-full object-cover" />
            <div v-else class="w-full h-full flex items-center justify-center text-slate-300 text-2xl">🖼️</div>
          </div>
          <div class="flex-1 min-w-0 flex flex-col justify-center">
            <div class="flex items-center justify-between">
              <p class="text-[9px] font-black text-emerald-600 uppercase tracking-widest mb-1">{{ p.category?.name || 'General' }}</p>
              <div :class="p.is_approved ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'" class="px-2 py-0.5 rounded-full text-[8px] font-black uppercase">
                {{ p.is_approved ? 'Approved' : 'Pending Approval' }}
              </div>
            </div>
            <h3 class="text-sm font-bold text-slate-800 truncate mb-1">{{ p.name }}</h3>
            <div class="flex items-baseline gap-2 mb-1">
              <p class="text-lg font-black text-slate-900">₦{{ formatMoney(p.selling_price) }}</p>
              <p class="text-[10px] text-slate-400 font-bold">Cost: ₦{{ formatMoney(p.cost_price) }}</p>
            </div>
            <div class="flex items-center gap-2">
              <span :class="p.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'" class="px-2 py-0.5 rounded-md text-[8px] font-black uppercase">
                {{ p.is_active ? 'Active' : 'Inactive' }}
              </span>
              <span :class="p.stock_quantity <= 5 ? 'text-rose-600 font-black' : 'text-slate-400 font-medium'" class="text-[10px]">
                Stock: {{ p.stock_quantity || 0 }}
              </span>
            </div>
          </div>
          <div class="flex flex-col gap-2 justify-center">
            <button @click="openEditModal(p)" class="p-2 rounded-xl bg-slate-50 text-slate-600 hover:bg-emerald-50 hover:text-emerald-700 transition-colors">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
              </svg>
            </button>
            <button @click="confirmDelete(p)" class="p-2 rounded-xl bg-slate-50 text-rose-600 hover:bg-rose-50 transition-colors">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Product Modal (Create/Edit) -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
      <div class="bg-white w-full max-w-lg rounded-[2.5rem] overflow-hidden shadow-2xl animate-in fade-in zoom-in duration-200">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
          <h2 class="text-xl font-black text-slate-800 uppercase">{{ editingId ? 'Edit Product' : 'Add New Product' }}</h2>
          <button @click="showModal = false" class="p-2 rounded-full hover:bg-slate-100 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        
        <div class="p-6 max-h-[70vh] overflow-y-auto space-y-4">
          <div>
            <label class="text-[10px] text-slate-400 font-bold uppercase tracking-widest ml-1">Product Name</label>
            <input v-model="form.name" type="text" class="w-full mt-1 px-4 py-3 rounded-2xl bg-slate-50 border border-slate-100 outline-none focus:border-emerald-500 font-bold text-slate-800" placeholder="e.g. iPhone 15 Pro Max" />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="text-[10px] text-slate-400 font-bold uppercase tracking-widest ml-1">Category</label>
              <select v-model="form.category_id" class="w-full mt-1 px-4 py-3 rounded-2xl bg-slate-50 border border-slate-100 outline-none focus:border-emerald-500 font-bold text-slate-800 appearance-none">
                <option value="">Select Category</option>
                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </div>
            <div>
              <label class="text-[10px] text-slate-400 font-bold uppercase tracking-widest ml-1">Cost Price (₦)</label>
              <input v-model="form.cost_price" type="number" step="0.01" class="w-full mt-1 px-4 py-3 rounded-2xl bg-slate-50 border border-slate-100 outline-none focus:border-emerald-500 font-bold text-slate-800" placeholder="0.00" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="text-[10px] text-slate-400 font-bold uppercase tracking-widest ml-1">Markup (%)</label>
              <input v-model="form.markup_percent" type="number" step="0.1" class="w-full mt-1 px-4 py-3 rounded-2xl bg-slate-50 border border-slate-100 outline-none focus:border-emerald-500 font-bold text-slate-800" placeholder="10.0" />
            </div>
            <div class="flex flex-col justify-end pb-1">
              <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest ml-1">Selling Price (Est.)</p>
              <p class="text-lg font-black text-emerald-700 ml-1">₦{{ formatMoney(calculatedSellingPrice) }}</p>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="text-[10px] text-slate-400 font-bold uppercase tracking-widest ml-1">Stock Quantity</label>
              <input v-model="form.stock_quantity" type="number" class="w-full mt-1 px-4 py-3 rounded-2xl bg-slate-50 border border-slate-100 outline-none focus:border-emerald-500 font-bold text-slate-800" placeholder="0" />
            </div>
            <div class="flex items-center gap-2 mt-4 ml-1">
              <input type="checkbox" v-model="form.track_stock" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
              <label class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Track Stock</label>
            </div>
          </div>

          <div>
            <label class="text-[10px] text-slate-400 font-bold uppercase tracking-widest ml-1">Product Image</label>
            <div class="mt-2 flex items-center gap-4">
              <div class="w-20 h-20 rounded-2xl bg-slate-50 border border-slate-100 overflow-hidden flex-shrink-0">
                <img v-if="imagePreview" :src="imagePreview" class="w-full h-full object-cover" />
                <div v-else class="w-full h-full flex items-center justify-center text-2xl">🖼️</div>
              </div>
              <div class="flex-1">
                <input type="file" ref="fileInput" @change="handleFileChange" accept="image/*" class="hidden" />
                <button @click="$refs.fileInput.click()" type="button" class="px-4 py-2 rounded-xl bg-white border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition-colors">
                  Choose Image
                </button>
                <p class="text-[9px] text-slate-400 mt-2 font-medium">JPG, PNG or WEBP. Max 2MB.</p>
              </div>
            </div>
          </div>

          <div>
            <label class="text-[10px] text-slate-400 font-bold uppercase tracking-widest ml-1">Status</label>
            <div class="mt-2 flex items-center gap-4">
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" :value="true" v-model="form.is_active" class="sr-only peer" />
                <div class="w-4 h-4 rounded-full border-2 border-slate-200 peer-checked:border-emerald-500 peer-checked:bg-emerald-500 transition-all shadow-inner" />
                <span class="text-xs font-bold text-slate-700 uppercase tracking-widest">Active & Visible</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" :value="false" v-model="form.is_active" class="sr-only peer" />
                <div class="w-4 h-4 rounded-full border-2 border-slate-200 peer-checked:border-rose-500 peer-checked:bg-rose-500 transition-all shadow-inner" />
                <span class="text-xs font-bold text-slate-700 uppercase tracking-widest">Hidden</span>
              </label>
            </div>
          </div>

          <div>
            <label class="text-[10px] text-slate-400 font-bold uppercase tracking-widest ml-1">Description</label>
            <textarea v-model="form.description" rows="3" class="w-full mt-1 px-4 py-3 rounded-2xl bg-slate-50 border border-slate-100 outline-none focus:border-emerald-500 font-bold text-slate-800" placeholder="Describe your product..."></textarea>
          </div>
          
        </div>

        <div class="p-6 bg-slate-50 border-t border-slate-100">
          <button @click="saveProduct" :disabled="saving" class="w-full h-14 rounded-2xl bg-emerald-700 text-white font-black uppercase tracking-wider shadow-lg shadow-emerald-700/20 active:scale-95 transition-all disabled:bg-slate-300">
            {{ saving ? 'Saving...' : 'Save Product' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from '../http'
import getImageUrl from '../utils/image'

const products = ref([])
const categories = ref([])
const loading = ref(true)
const showModal = ref(false)
const saving = ref(false)
const editingId = ref(null)
const imagePreview = ref(null)
const selectedFile = ref(null)

const form = ref({
  name: '',
  description: '',
  cost_price: '',
  markup_percent: 10,
  stock_quantity: 0,
  track_stock: true,
  is_active: true,
  image_url: '',
  category_id: ''
})

const calculatedSellingPrice = computed(() => {
  const cost = Number(form.value.cost_price || 0)
  const markup = Number(form.value.markup_percent || 0)
  return cost + (cost * (markup / 100))
})

const formatMoney = (val) => {
  return Number(val || 0).toLocaleString('en-NG', { minimumFractionDigits: 2 })
}

const loadData = async () => {
  loading.value = true
  try {
    const [pRes, cRes] = await Promise.all([
      axios.get('/api/vendor/products'),
      axios.get('/api/products/categories')
    ])
    products.value = pRes.data.data || pRes.data
    categories.value = cRes.data
  } catch (err) {
    console.error('Failed to load products', err)
  } finally {
    loading.value = false
  }
}

const openCreateModal = () => {
  editingId.value = null
  imagePreview.value = null
  selectedFile.value = null
  form.value = {
    name: '',
    description: '',
    cost_price: '',
    markup_percent: 10,
    stock_quantity: 0,
    track_stock: true,
    is_active: true,
    image_url: '',
    category_id: ''
  }
  showModal.value = true
}

const openEditModal = (p) => {
  editingId.value = p.id
  imagePreview.value = p.image_url ? getImageUrl(p.image_url) : null
  selectedFile.value = null
  form.value = {
    name: p.name,
    description: p.description,
    cost_price: p.cost_price,
    markup_percent: p.markup_percent,
    stock_quantity: p.stock_quantity,
    track_stock: !!p.track_stock,
    is_active: !!p.is_active,
    image_url: p.image_url,
    category_id: p.category_id
  }
  showModal.value = true
}

const handleFileChange = (e) => {
  const file = e.target.files[0]
  if (file) {
    selectedFile.value = file
    imagePreview.value = URL.createObjectURL(file)
  }
}

const saveProduct = async () => {
  saving.value = true
  try {
    const formData = new FormData()
    Object.keys(form.value).forEach(key => {
      if (form.value[key] !== null && form.value[key] !== undefined) {
        formData.append(key, form.value[key])
      }
    })
    
    if (selectedFile.value) {
      formData.append('image', selectedFile.value)
    }

    if (editingId.value) {
      // Use POST with _method=PUT to handle multipart/form-data for update
      formData.append('_method', 'PUT')
      await axios.post(`/api/vendor/products/${editingId.value}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
    } else {
      await axios.post('/api/vendor/products', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
    }
    showModal.value = false
    loadData()
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to save product')
  } finally {
    saving.value = false
  }
}

const confirmDelete = async (p) => {
  if (confirm(`Are you sure you want to delete ${p.name}?`)) {
    try {
      await axios.delete(`/api/vendor/products/${p.id}`)
      loadData()
    } catch (err) {
      alert('Failed to delete product')
    }
  }
}

onMounted(loadData)
</script>
