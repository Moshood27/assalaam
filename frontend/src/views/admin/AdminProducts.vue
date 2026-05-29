<template>
  <div class="min-h-screen bg-slate-50 pb-24">
    <header class="p-4 bg-white border-b flex items-center justify-between sticky top-0 z-10">
      <h1 class="text-lg sm:text-xl font-bold text-slate-800">Admin Ã¢â‚¬Â¢ Products</h1>
      <div class="flex items-center gap-2">
        <button class="text-sm font-bold text-slate-500" @click="$router.back()">Back</button>
      </div>
    </header>

    <div class="p-4 space-y-4">
      <section class="card p-5">
        <div class="flex items-center justify-between mb-3">
          <h2 class="section-title">Manage Product Images</h2>
          <span class="text-[10px] font-black uppercase tracking-widest text-blue-700">Max 10MB</span>
        </div>

        <div class="flex items-center gap-2 mb-4">
          <input v-model="q" @keyup.enter="load(1)" type="search" placeholder="Search productsÃ¢â‚¬Â¦" class="bg-white border border-slate-200 rounded-lg px-3 py-2 text-sm w-full" />
          <button class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-xs font-bold" @click="load(1)">Search</button>
        </div>

        <div v-if="loading" class="text-slate-500 text-sm">LoadingÃ¢â‚¬Â¦</div>
        <div v-else-if="error" class="text-rose-700 bg-rose-50 border border-rose-200 p-3 rounded-lg text-sm">{{ error }}</div>
        <div v-else>
          <div v-if="!items.length" class="text-slate-500 text-sm">No products found.</div>
          <ul class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <li v-for="p in items" :key="p.id" class="p-3 bg-white border rounded-xl shadow-sm flex gap-3">
              <div class="w-20 h-20 rounded-lg bg-slate-100 flex items-center justify-center overflow-hidden shrink-0">
                <img v-if="p.image_url" :src="getImageUrl(p.image_url) + cacheBust" alt="image" class="w-full h-full object-cover" />
                <div v-else class="text-slate-400 text-2xl">Ã°Å¸â€“Â¼Ã¯Â¸Â</div>
              </div>
              <div class="flex-1 min-w-0">
                <div class="font-bold text-slate-800 truncate">{{ p.name }}</div>
                <div class="text-[12px] text-slate-500 line-clamp-2">{{ p.description || 'Ã¢â‚¬â€' }}</div>

                <div class="mt-2 flex items-center gap-2">
                  <input class="hidden" :id="`file_${p.id}`" type="file" accept="image/*" capture="environment" @change="onPick($event, p)" />
                  <label :for="`file_${p.id}`" class="px-3 py-2 rounded-lg bg-blue-600 text-white text-xs font-bold inline-flex items-center gap-2 cursor-pointer">
                    <span>Change Image</span>
                  </label>
                  <button v-if="p.image_url && !removing[p.id]" class="px-3 py-2 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-xs font-bold" @click="removeImage(p)">Remove</button>
                  <span v-if="uploading[p.id]" class="text-xs text-slate-500">UploadingÃ¢â‚¬Â¦</span>
                  <span v-if="removing[p.id]" class="text-xs text-slate-500">RemovingÃ¢â‚¬Â¦</span>
                </div>
                <div v-if="perror[p.id]" class="text-[12px] text-rose-700 mt-1">{{ perror[p.id] }}</div>
              </div>
            </li>
          </ul>

          <div class="flex items-center justify-between mt-4 text-sm" v-if="lastPage > 1">
            <button class="px-3 py-2 rounded-lg border border-slate-200 bg-white disabled:opacity-50" :disabled="page <= 1 || loading" @click="load(page - 1)">Prev</button>
            <div class="text-slate-500">Page {{ page }} / {{ lastPage }}</div>
            <button class="px-3 py-2 rounded-lg border border-slate-200 bg-white disabled:opacity-50" :disabled="page >= lastPage || loading" @click="load(page + 1)">Next</button>
          </div>
        </div>
      </section>

      <p class="text-[12px] text-slate-500">
        Tip: On mobile, you can use your camera directly. Large images are automatically resized and compressed to fit within 10MB.
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from '../../http'
import getImageUrl from '../../utils/image'
import { compressImage } from '../../utils/compress'

const items = ref([])
const loading = ref(false)
const error = ref('')
const page = ref(1)
const lastPage = ref(1)
const q = ref('')
const uploading = ref({})
const removing = ref({})
const perror = ref({})

const cacheBust = ref('')
const bust = () => { cacheBust.value = `?t=${Date.now()}` }

const load = async (p = 1) => {
  loading.value = true
  error.value = ''
  try {
    page.value = p
    const { data } = await axios.get('/api/admin/products', { params: { page: p, q: q.value || '' } })
    const list = Array.isArray(data) ? data : (data?.data || [])
    items.value = list
    lastPage.value = Number(data?.last_page || 1)
  } catch (e) {
    error.value = e?.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
}

const onPick = async (ev, p) => {
  perror.value[p.id] = ''
  try {
    const file = ev.target.files?.[0]
    ev.target.value = '' // reset
    if (!file) return

    // Client-side size check and compression to meet <= 2000KB (soft limit)
    let blob = file
    if (blob.size > 2000 * 1024) {
      blob = await compressImage(file, { maxKB: 2000, maxWidth: 1920, maxHeight: 1920 })
    }
    if (blob.size > 10240 * 1024) {
      perror.value[p.id] = `Image too large (${Math.round(blob.size/1024/1024)}MB). Max 10MB allowed.`
      return
    }

    uploading.value[p.id] = true
    const fd = new FormData()
    // Preserve original filename when possible
    const filename = (file.name && file.name.includes('.')) ? file.name : `product_${p.id}.jpg`
    fd.append('image', new File([blob], filename, { type: blob.type || 'image/jpeg' }))
    await axios.post(`/api/admin/products/${p.id}/image`, fd)
    await load(page.value)
    bust()
  } catch (e) {
    perror.value[p.id] = e?.response?.data?.message || e.message
  } finally {
    uploading.value[p.id] = false
  }
}

const removeImage = async (p) => {
  perror.value[p.id] = ''
  if (!confirm('Remove product image?')) return
  try {
    removing.value[p.id] = true
    await axios.delete(`/api/admin/products/${p.id}/image`)
    await load(page.value)
    bust()
  } catch (e) {
    perror.value[p.id] = e?.response?.data?.message || e.message
  } finally {
    removing.value[p.id] = false
  }
}

load(1)
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


