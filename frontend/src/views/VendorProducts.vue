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
        <button @click="vendor.is_approved ? openCreateModal() : alert('Your vendor profile is pending approval. You cannot add products yet.')" 
                :class="vendor.is_approved ? 'bg-blue-700 hover:bg-blue-800' : 'bg-slate-400 cursor-not-allowed'"
                class="text-white px-4 py-2 rounded-xl text-xs font-bold transition-colors">Add New</button>
      </div>
    </header>

    <div class="p-4 space-y-6">
      <div v-if="loading" class="flex flex-col items-center justify-center py-20 gap-4">
        <div class="w-12 h-12 border-4 border-blue-500/20 border-t-blue-600 rounded-full animate-spin"></div>
        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Loading catalog...</p>
      </div>
      
      <div v-else-if="products.length === 0" class="bg-white rounded-[2.5rem] p-16 text-center border-2 border-dashed border-slate-100">
        <div class="w-20 h-20 bg-slate-50 rounded-3xl flex items-center justify-center text-4xl mx-auto mb-6">ðŸ“¦</div>
        <h3 class="text-xl font-black text-slate-800 uppercase mb-2">No products yet</h3>
        <p class="text-sm text-slate-500 mb-8 max-w-[240px] mx-auto">Start listing your products to grow your business with our members.</p>
        <button v-if="vendor.is_approved" @click="openCreateModal" class="px-8 py-4 rounded-2xl bg-blue-700 text-white font-black text-xs uppercase tracking-widest shadow-lg shadow-blue-700/20 active:scale-95 transition-all">Add your first product</button>
        <div v-else class="p-4 bg-amber-50 border border-amber-100 rounded-2xl text-amber-700 text-[10px] font-black uppercase tracking-widest">
          Approval Required to list products
        </div>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div v-for="p in products" :key="p.id" class="group bg-white p-4 rounded-[2rem] shadow-sm border border-slate-100 flex gap-5 hover:shadow-xl hover:shadow-blue-900/5 transition-all duration-300">
          <div class="w-28 h-28 rounded-3xl bg-slate-50 overflow-hidden shrink-0 border border-slate-100 relative">
            <img v-if="p.image_url" :src="getImageUrl(p.image_url)" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" />
            <div v-else class="w-full h-full flex items-center justify-center text-slate-200 text-3xl">ðŸ–¼ï¸</div>
            
            <div class="absolute inset-0 bg-black/5 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
               <button @click="openEditModal(p)" class="w-8 h-8 bg-white rounded-full flex items-center justify-center text-slate-600 shadow-lg scale-0 group-hover:scale-100 transition-transform duration-300">
                  <span class="i-mdi-pencil text-sm"></span>
               </button>
            </div>
          </div>
          <div class="flex-1 min-w-0 flex flex-col justify-between py-1">
            <div class="space-y-1">
              <div class="flex items-center justify-between">
                <span class="text-[9px] font-black text-blue-600 uppercase tracking-widest truncate">{{ p.category?.name || 'General' }}</span>
                <div :class="p.is_approved ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700'" class="px-2 py-0.5 rounded-full text-[7px] font-black uppercase tracking-tighter">
                  {{ p.is_approved ? 'Approved' : 'Pending' }}
                </div>
              </div>
              <h3 class="text-sm font-black text-slate-800 truncate">{{ p.name }}</h3>
              <div class="flex items-baseline gap-2">
                <p class="text-base font-black text-slate-900">â‚¦{{ formatMoney(p.selling_price) }}</p>
                <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">Markup: {{ p.markup_percentage }}%</p>
              </div>
            </div>
            
            <div class="flex items-center justify-between mt-2">
              <div class="flex items-center gap-2">
                <span :class="p.is_active ? 'bg-blue-50 text-blue-600' : 'bg-slate-50 text-slate-400'" class="px-2 py-1 rounded-lg text-[8px] font-black uppercase tracking-widest">
                  {{ p.is_active ? 'Active' : 'Hidden' }}
                </span>
                <span :class="p.stock_quantity <= 5 ? 'text-rose-600 font-black' : 'text-slate-500 font-bold'" class="text-[9px] uppercase tracking-widest">
                   Stock: {{ p.stock_quantity || 0 }}
                </span>
              </div>
              <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                <button @click="openEditModal(p)" class="p-2 rounded-xl text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-all">
                   <span class="i-mdi-pencil text-lg"></span>
                </button>
                <button @click="confirmDelete(p)" class="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-all">
                   <span class="i-mdi-delete-outline text-lg"></span>
                </button>
              </div>
            </div>
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
            <input v-model="form.name" type="text" class="w-full mt-1 px-4 py-3 rounded-2xl bg-slate-50 border border-slate-100 outline-none focus:border-blue-500 font-bold text-slate-800" placeholder="e.g. iPhone 15 Pro Max" />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="text-[10px] text-slate-400 font-bold uppercase tracking-widest ml-1">Category</label>
              <select v-model="form.category_id" class="w-full mt-1 px-4 py-3 rounded-2xl bg-slate-50 border border-slate-100 outline-none focus:border-blue-500 font-bold text-slate-800 appearance-none">
                <option value="">Select Category</option>
                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </div>
            <div>
              <label class="text-[10px] text-slate-400 font-bold uppercase tracking-widest ml-1">Cost Price (â‚¦)</label>
              <input v-model="form.cost_price" type="number" step="0.01" class="w-full mt-1 px-4 py-3 rounded-2xl bg-slate-50 border border-slate-100 outline-none focus:border-blue-500 font-bold text-slate-800" placeholder="0.00" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="text-[10px] text-slate-400 font-bold uppercase tracking-widest ml-1">Markup (%)</label>
              <input v-model="form.markup_percent" type="number" step="0.1" class="w-full mt-1 px-4 py-3 rounded-2xl bg-slate-50 border border-slate-100 outline-none focus:border-blue-500 font-bold text-slate-800" placeholder="10.0" />
            </div>
            <div class="flex flex-col justify-end pb-1">
              <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest ml-1">Selling Price (Est.)</p>
              <p class="text-lg font-black text-blue-700 ml-1">â‚¦{{ formatMoney(calculatedSellingPrice) }}</p>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="text-[10px] text-slate-400 font-bold uppercase tracking-widest ml-1">Stock Quantity</label>
              <input v-model="form.stock_quantity" type="number" class="w-full mt-1 px-4 py-3 rounded-2xl bg-slate-50 border border-slate-100 outline-none focus:border-blue-500 font-bold text-slate-800" placeholder="0" />
            </div>
            <div class="flex items-center gap-2 mt-4 ml-1">
              <input type="checkbox" v-model="form.track_stock" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
              <label class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Track Stock</label>
            </div>
          </div>

          <div>
            <label class="text-[10px] text-slate-400 font-bold uppercase tracking-widest ml-1">Product Image</label>
            <div class="mt-2 flex items-center gap-4">
              <div class="w-20 h-20 rounded-2xl bg-slate-50 border border-slate-100 overflow-hidden flex-shrink-0">
                <img v-if="imagePreview" :src="imagePreview" class="w-full h-full object-cover" />
                <div v-else class="w-full h-full flex items-center justify-center text-2xl">ðŸ–¼ï¸</div>
              </div>
              <div class="flex-1">
                <input type="file" ref="fileInput" @change="handleFileChange" accept="image/*" class="hidden" />
                <button @click="$refs.fileInput.click()" type="button" class="px-4 py-2 rounded-xl bg-white border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition-colors">
                  Choose Image
                </button>
                <p class="text-[9px] text-slate-400 mt-2 font-medium">JPG, PNG or WEBP. Max 10MB.</p>
              </div>
            </div>
          </div>

          <div>
            <label class="text-[10px] text-slate-400 font-bold uppercase tracking-widest ml-1">Status</label>
            <div class="mt-2 flex items-center gap-4">
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" :value="true" v-model="form.is_active" class="sr-only peer" />
                <div class="w-4 h-4 rounded-full border-2 border-slate-200 peer-checked:border-blue-500 peer-checked:bg-blue-500 transition-all shadow-inner" />
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
            <textarea v-model="form.description" rows="3" class="w-full mt-1 px-4 py-3 rounded-2xl bg-slate-50 border border-slate-100 outline-none focus:border-blue-500 font-bold text-slate-800" placeholder="Describe your product..."></textarea>
          </div>
          
        </div>

        <div class="p-6 bg-slate-50 border-t border-slate-100">
          <button @click="saveProduct" :disabled="saving" class="w-full h-14 rounded-2xl bg-blue-700 text-white font-black uppercase tracking-wider shadow-lg shadow-blue-700/20 active:scale-95 transition-all disabled:bg-slate-300">
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
import { compressImage } from '../utils/compress'

const products = ref([])
const categories = ref([])
const loading = ref(true)
const showModal = ref(false)
const saving = ref(false)
const editingId = ref(null)
const imagePreview = ref(null)
const selectedFile = ref(null)
const vendor = ref({ is_approved: false })

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
    const vRes = await axios.get('/api/vendor/profile')
    vendor.value = vRes.data

    if (vendor.value.is_approved) {
      const [pRes, cRes] = await Promise.all([
        axios.get('/api/vendor/products'),
        axios.get('/api/products/categories')
      ])
      products.value = pRes.data.data || pRes.data
      categories.value = cRes.data
    }
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

const handleFileChange = async (e) => {
  const file = e.target.files[0]
  if (file) {
    let blob = file
    if (blob.size > 2000 * 1024) {
      blob = await compressImage(file, { maxKB: 2000, maxWidth: 1920, maxHeight: 1920 })
    }
    if (blob.size > 10240 * 1024) {
      alert(`Image too large (${Math.round(blob.size/1024/1024)}MB). Max 10MB allowed.`)
      e.target.value = ''
      return
    }

    selectedFile.value = new File([blob], file.name || 'product.jpg', { type: blob.type })
    imagePreview.value = URL.createObjectURL(blob)
  }
}

const saveProduct = async () => {
  saving.value = true
  try {
    const formData = new FormData()
    Object.keys(form.value).forEach(key => {
      let val = form.value[key]
      if (val !== null && val !== undefined) {
        if (typeof val === 'boolean') {
          val = val ? 1 : 0
        }
        formData.append(key, val)
      }
    })
    
    if (selectedFile.value) {
      formData.append('image', selectedFile.value)
    }

    if (editingId.value) {
      // Use POST with _method=PUT to handle multipart/form-data for update
      formData.append('_method', 'PUT')
      await axios.post(`/api/vendor/products/${editingId.value}`, formData)
    } else {
      await axios.post('/api/vendor/products', formData)
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


