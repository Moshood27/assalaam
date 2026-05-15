<template>
  <div class="min-h-screen bg-slate-50/50 pb-32">
    <AppHeader title="Coop Store" :showBack="true">
      <template #right>
        <div class="flex items-center gap-1">
          <button class="relative p-2 hover:bg-slate-100 rounded-xl transition-colors" @click="toggleCart()">
            <span class="i-mdi-cart-outline text-2xl text-emerald-700"></span>
            <span v-if="totalQty" class="absolute top-0 right-0 w-5 h-5 bg-emerald-600 text-white text-[10px] font-black rounded-full flex items-center justify-center border-2 border-white">{{ totalQty }}</span>
          </button>
          <button class="p-2 hover:bg-slate-100 rounded-xl transition-colors" @click="$router.push('/store/orders')" title="Orders">
            <span class="i-mdi-file-document-outline text-2xl text-slate-600"></span>
          </button>
          <button v-if="vendor && vendor.id" class="p-2 hover:bg-emerald-50 rounded-xl transition-colors" @click="$router.push('/vendor/dashboard')" title="Vendor Portal">
            <span class="i-mdi-store-outline text-2xl text-emerald-700"></span>
          </button>
          <button v-else class="p-2 hover:bg-slate-100 rounded-xl transition-colors" @click="$router.push('/vendor/apply')" title="Become a Vendor">
            <span class="i-mdi-store-plus-outline text-2xl text-slate-400"></span>
          </button>
        </div>
      </template>
    </AppHeader>

    <div class="max-w-5xl mx-auto p-4 space-y-6">
      <!-- Search & Filters -->
      <section class="bg-white rounded-[2rem] p-6 shadow-sm border border-slate-100">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
          <div>
            <h2 class="text-2xl font-black text-slate-800 uppercase leading-tight">Coop Store</h2>
            <p class="text-[10px] font-black uppercase tracking-widest text-emerald-600">Premium member shopping</p>
          </div>
          <div class="flex items-center gap-2">
            <button class="p-2 hover:bg-emerald-50 rounded-xl transition-colors text-emerald-700" @click="toggleCart()" title="View Cart">
              <span class="i-mdi-cart-outline text-2xl"></span>
              <span v-if="totalQty" class="absolute -top-1 -right-1 w-5 h-5 bg-emerald-600 text-white text-[10px] font-black rounded-full flex items-center justify-center border-2 border-white">{{ totalQty }}</span>
            </button>
          </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
          <div class="flex-1 relative group">
            <input v-model="q" @keyup.enter="load(1)" type="search" placeholder="Search for products, electronics..." class="w-full bg-slate-50 border-2 border-transparent focus:border-emerald-500/20 focus:bg-white p-4 pl-12 rounded-[1.5rem] outline-none transition-all font-medium text-slate-800 shadow-inner" />
            <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-emerald-500 transition-colors">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </div>
          </div>
          <div class="flex items-center gap-2 overflow-x-auto pb-1 sm:pb-0 hide-scrollbar">
            <select v-model="selectedCategory" @change="load(1)" class="h-14 px-4 bg-slate-50 border border-slate-100 rounded-[1.5rem] text-[10px] font-black uppercase tracking-widest text-slate-600 outline-none focus:border-emerald-500 transition-colors appearance-none min-w-[140px]">
              <option :value="0">All Categories</option>
              <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
            <select v-model="sortBy" @change="load(1)" class="h-14 px-4 bg-slate-50 border border-slate-100 rounded-[1.5rem] text-[10px] font-black uppercase tracking-widest text-slate-600 outline-none focus:border-emerald-500 transition-colors appearance-none min-w-[120px]">
              <option value="newest">Newest</option>
              <option value="price_asc">Price: Low-High</option>
              <option value="price_desc">Price: High-Low</option>
              <option value="name_asc">A–Z</option>
            </select>
          </div>
        </div>
      </section>

      <!-- Products Grid -->
      <section>
        <div v-if="loading" class="flex flex-col items-center justify-center py-20 gap-4">
          <div class="w-12 h-12 border-4 border-emerald-500/20 border-t-emerald-600 rounded-full animate-spin"></div>
          <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Updating catalog...</p>
        </div>
        <div v-else-if="error" class="bg-rose-50 border border-rose-100 p-6 rounded-[2rem] text-center">
           <p class="text-rose-700 font-bold">{{ error }}</p>
           <button @click="load(1)" class="mt-4 px-6 py-2 bg-rose-600 text-white rounded-xl text-xs font-bold uppercase tracking-widest">Retry</button>
        </div>
        <div v-else>
          <div v-if="!items.length" class="bg-white rounded-[2rem] p-20 text-center border border-dashed border-slate-200">
            <div class="text-5xl mb-4">🛍️</div>
            <h3 class="text-lg font-black text-slate-800 uppercase mb-1">No products found</h3>
            <p class="text-sm text-slate-500">Try adjusting your search or category filters.</p>
          </div>
          
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="p in items" :key="p.id" class="group bg-white rounded-[2rem] border border-slate-100 shadow-sm hover:shadow-xl hover:shadow-emerald-900/5 transition-all duration-300 overflow-hidden flex flex-col">
              <!-- Product Image Area -->
              <div class="aspect-square relative overflow-hidden bg-slate-50" @click="openQuick(p)">
                <img v-if="p.image_url" :src="getImageUrl(p.image_url)" alt="image" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" />
                <div v-else class="w-full h-full flex items-center justify-center text-slate-200 text-6xl">📦</div>
                
                <!-- Badges overlay -->
                <div class="absolute top-4 left-4 flex flex-col gap-2">
                  <span v-if="isNew(p.created_at)" class="bg-emerald-600 text-white text-[8px] font-black uppercase tracking-widest px-3 py-1 rounded-full shadow-lg shadow-emerald-900/20">New</span>
                  <span v-if="!p.is_approved" class="bg-amber-500 text-white text-[8px] font-black uppercase tracking-widest px-3 py-1 rounded-full shadow-lg shadow-amber-900/20">Pending</span>
                </div>
                
                <!-- Quick action overlay -->
                <div class="absolute inset-0 bg-emerald-900/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                   <button @click.stop="openQuick(p)" class="w-12 h-12 bg-white text-slate-800 rounded-full flex items-center justify-center shadow-xl hover:scale-110 transition-transform">
                      <span class="i-mdi-eye-outline text-xl"></span>
                   </button>
                   <button v-if="!cart[p.id] && (!p.track_stock || p.stock_quantity > 0)" @click.stop="addToCart(p)" class="w-12 h-12 bg-emerald-600 text-white rounded-full flex items-center justify-center shadow-xl hover:scale-110 transition-transform">
                      <span class="i-mdi-cart-plus text-xl"></span>
                   </button>
                </div>
              </div>

              <!-- Product Info -->
              <div class="p-6 flex-1 flex flex-col">
                <div class="flex items-center justify-between gap-4 mb-2">
                  <span class="text-[9px] font-black text-emerald-600 uppercase tracking-widest truncate">{{ p.category?.name || 'General' }}</span>
                  <div class="text-lg font-black text-slate-900 whitespace-nowrap">₦ {{ money(p.selling_price) }}</div>
                </div>
                
                <h3 class="text-base font-bold text-slate-800 mb-1 group-hover:text-emerald-700 transition-colors cursor-pointer line-clamp-1" @click="openQuick(p)">{{ p.name }}</h3>
                
                <div v-if="p.vendor" class="flex items-center gap-2 mb-3">
                  <div class="w-4 h-4 rounded-full bg-slate-100 flex items-center justify-center text-[8px] text-slate-400 font-bold">V</div>
                  <span class="text-[10px] text-slate-500 font-bold truncate">{{ p.vendor.name }}</span>
                </div>

                <p class="text-xs text-slate-500 line-clamp-2 mb-4 h-8">{{ p.description || 'No description available for this item.' }}</p>
                
                <div class="mt-auto space-y-3">
                   <div v-if="p.track_stock" class="flex items-center gap-2">
                      <div class="flex-1 h-1 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-emerald-500 rounded-full" :style="{ width: Math.min(100, (p.stock_quantity / 10) * 100) + '%' }"></div>
                      </div>
                      <span v-if="p.stock_quantity > 0" class="text-[9px] font-black text-slate-400 uppercase">{{ p.stock_quantity }} Left</span>
                      <span v-else class="text-[9px] font-black text-rose-500 uppercase">Out of Stock</span>
                   </div>

                   <div class="flex items-center gap-2 pt-2 border-t border-slate-50">
                      <template v-if="cart[p.id]">
                        <div class="flex-1 flex items-center justify-between bg-slate-50 rounded-xl p-1 px-3">
                          <button class="w-8 h-8 flex items-center justify-center text-slate-400 hover:text-emerald-600 transition-colors" @click="decQty(p.id)">
                            <span class="i-mdi-minus text-sm"></span>
                          </button>
                          <span class="text-sm font-black text-slate-800">{{ cart[p.id].qty }}</span>
                          <button class="w-8 h-8 flex items-center justify-center text-slate-400 hover:text-emerald-600 transition-colors disabled:opacity-30" @click="incQty(p.id)" :disabled="p.track_stock && cart[p.id].qty >= p.stock_quantity">
                            <span class="i-mdi-plus text-sm"></span>
                          </button>
                        </div>
                      </template>
                      <button v-else-if="!p.track_stock || p.stock_quantity > 0" class="flex-1 h-12 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black uppercase tracking-widest transition-all active:scale-95 shadow-lg shadow-emerald-900/10" @click="addToCart(p)">Add to Cart</button>
                      <button v-else disabled class="flex-1 h-12 bg-slate-100 text-slate-400 rounded-xl text-xs font-black uppercase tracking-widest cursor-not-allowed">Sold Out</button>
                      
                      <div v-if="isAdmin && (!p.is_approved || p.vendor_id)" class="flex gap-1">
                        <button v-if="!p.is_approved" @click="approveProduct(p)" class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center hover:bg-emerald-600 hover:text-white transition-all" title="Approve">
                          <span class="i-mdi-check text-xl"></span>
                        </button>
                         <button v-if="p.is_approved && p.vendor_id" @click="rejectProduct(p)" class="w-10 h-10 bg-rose-50 text-rose-600 rounded-xl flex items-center justify-center hover:bg-rose-600 hover:text-white transition-all" title="Reject">
                          <span class="i-mdi-close text-xl"></span>
                        </button>
                      </div>
                   </div>
                </div>
              </div>
            </div>
          </div>

          <div class="flex items-center justify-between mt-4 text-sm">
            <button class="px-3 py-2 rounded-lg border border-slate-200 bg-white disabled:opacity-50" :disabled="page <= 1 || loading" @click="load(page - 1)">Prev</button>
            <div class="text-slate-500">Page {{ page }} / {{ lastPage }}</div>
            <button class="px-3 py-2 rounded-lg border border-slate-200 bg-white disabled:opacity-50" :disabled="page >= lastPage || loading" @click="load(page + 1)">Next</button>
          </div>
        </div>
      </section>

      <section v-if="showCart" class="bg-white rounded-[2.5rem] p-6 shadow-xl border border-slate-100 animate-in slide-in-from-bottom duration-500">
        <div class="flex items-center justify-between mb-6">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl">
              <span class="i-mdi-cart-outline"></span>
            </div>
            <div>
              <h2 class="text-xl font-black text-slate-800 uppercase leading-tight">Your Cart</h2>
              <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ cartList.length }} items selected</p>
            </div>
          </div>
          <button class="px-4 py-2 rounded-xl bg-rose-50 text-rose-600 text-xs font-black uppercase tracking-widest transition-colors hover:bg-rose-100" @click="clearCart()" :disabled="!totalQty">Clear</button>
        </div>

        <div v-if="!totalQty" class="py-12 text-center border-2 border-dashed border-slate-100 rounded-[2rem]">
          <p class="text-slate-400 text-sm font-medium">Your shopping cart is empty.</p>
        </div>
        <div v-else class="space-y-4">
          <div class="max-h-[40vh] overflow-y-auto pr-2 space-y-3 hide-scrollbar">
            <div v-for="ci in cartList" :key="ci.id" class="flex items-center gap-4 p-4 rounded-[1.5rem] bg-slate-50 border border-slate-100 group transition-all">
              <div class="w-16 h-16 rounded-xl bg-white overflow-hidden border border-slate-200 shrink-0">
                <img v-if="ci.image_url" :src="getImageUrl(ci.image_url)" class="w-full h-full object-cover" />
                <div v-else class="w-full h-full flex items-center justify-center text-slate-200">📦</div>
              </div>
              <div class="flex-1 min-w-0">
                <div class="font-bold text-slate-800 truncate mb-0.5">{{ ci.name }}</div>
                <div class="text-sm font-black text-emerald-700">₦ {{ money(ci.selling_price) }}</div>
              </div>
              <div class="flex flex-col items-end gap-2">
                <div class="flex items-center bg-white rounded-xl p-1 border border-slate-200">
                  <button class="w-7 h-7 flex items-center justify-center text-slate-400 hover:text-emerald-600" @click="decQty(ci.id)">-</button>
                  <span class="w-8 text-center text-xs font-black text-slate-800">{{ ci.qty }}</span>
                  <button class="w-7 h-7 flex items-center justify-center text-slate-400 hover:text-emerald-600" @click="incQty(ci.id)">+</button>
                </div>
                <button class="text-[10px] font-black text-rose-500 uppercase tracking-widest hover:underline" @click="remove(ci.id)">Remove</button>
              </div>
            </div>
          </div>

          <div class="p-6 bg-slate-900 rounded-[2rem] text-white">
            <div class="flex items-center justify-between mb-4">
              <span class="text-slate-400 text-xs font-bold uppercase tracking-widest">Subtotal</span>
              <span class="text-2xl font-black">₦ {{ money(subtotal) }}</span>
            </div>

            <div class="space-y-4">
              <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Order Note (optional)</label>
                <textarea v-model="orderNote" rows="2" placeholder="Instructions for the vendor..." class="w-full bg-white/10 border border-white/10 rounded-2xl px-4 py-3 text-sm text-white placeholder:text-slate-500 outline-none focus:border-emerald-500 transition-colors"></textarea>
              </div>

              <!-- Financing Option -->
              <div v-if="hasInsufficient" class="p-4 rounded-2xl bg-white/5 border border-white/10 space-y-4">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-lg bg-amber-500 text-white flex items-center justify-center text-lg">💳</div>
                  <div>
                    <p class="text-xs font-black uppercase tracking-widest text-amber-500">Murabaha Financing</p>
                    <p class="text-[10px] text-slate-400">Insufficient balance. Buy on credit.</p>
                  </div>
                </div>

                <div v-if="!canUseFinancing" class="p-3 bg-rose-500/10 border border-rose-500/20 rounded-xl text-rose-400 text-[10px] font-bold">
                  {{ financingReason }}
                </div>
                <template v-else>
                  <div class="grid grid-cols-2 gap-3">
                    <div>
                      <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1 ml-1">Tenor</label>
                      <select v-model.number="creditMonths" class="w-full bg-white/10 border border-white/10 rounded-xl px-3 py-2 text-xs text-white outline-none">
                        <option v-for="m in [6,7,8,9,10,11,12]" :key="m" :value="m" class="text-slate-900">{{ m }} months</option>
                      </select>
                    </div>
                    <div>
                      <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1 ml-1">Profit Rate</label>
                      <select v-model.number="creditProfit" class="w-full bg-white/10 border border-white/10 rounded-xl px-3 py-2 text-xs text-white outline-none">
                        <option :value="0.10" class="text-slate-900">10%</option>
                        <option :value="0.12" class="text-slate-900">12%</option>
                        <option :value="0.15" class="text-slate-900">15%</option>
                      </select>
                    </div>
                  </div>
                  
                  <div class="text-[10px] text-slate-400 leading-relaxed px-1">
                    Est. Total: <span class="text-white font-bold">₦ {{ money(creditEstimateTotal) }}</span> • 
                    Monthly: <span class="text-white font-bold">₦ {{ money(creditMonthly) }}</span>
                    <div v-if="eligData" class="mt-1 text-emerald-400 font-bold uppercase tracking-widest">Limit: ₦ {{ money(eligData.limit) }}</div>
                  </div>

                  <div class="flex items-start gap-3 p-2 bg-white/5 rounded-xl">
                    <input type="checkbox" v-model="agreedToTerms" class="mt-1 rounded border-white/20 text-emerald-500 focus:ring-emerald-500 bg-transparent" />
                    <label class="text-[9px] text-slate-400 leading-tight">I agree to the Murabahah Financing Terms and authorize the Coop to purchase for resale.</label>
                  </div>

                  <button @click="creditCheckout()" :disabled="placing || !totalQty || !creditValid || exceedsLimit || !agreedToTerms" class="w-full h-12 bg-amber-500 hover:bg-amber-600 text-white rounded-xl font-black uppercase tracking-wider transition-all disabled:opacity-30 disabled:grayscale">
                    Apply & Buy on Credit
                  </button>
                  <p v-if="exceedsLimit" class="text-center text-rose-500 text-[9px] font-black uppercase mt-1">Exceeds limit (₦{{ money(eligData?.limit) }})</p>
                </template>
              </div>

              <!-- Standard Checkout -->
              <button @click="checkout()" :disabled="placing || !totalQty || hasInsufficient" class="w-full h-16 bg-emerald-600 hover:bg-emerald-700 text-white rounded-[1.5rem] font-black uppercase tracking-wider shadow-lg shadow-emerald-900/20 transition-all active:scale-95 disabled:opacity-30 flex items-center justify-center gap-3">
                <span v-if="placing && purchaseMode === 'cash'" class="i-mdi-loading animate-spin text-2xl"></span>
                <span v-else class="i-mdi-check-circle-outline text-2xl"></span>
                {{ hasInsufficient ? 'Insufficient Balance' : 'Confirm & Pay Now' }}
              </button>
            </div>
          </div>
          
          <div v-if="placeError" class="p-4 rounded-2xl bg-rose-50 border border-rose-100 text-rose-600 text-xs font-bold text-center">{{ placeError }}</div>
          <div v-if="placeSuccess" class="p-4 rounded-2xl bg-emerald-50 border border-emerald-100 text-emerald-700 text-xs font-bold text-center">{{ placeSuccess }}</div>
        </div>
      </section>
    </div>

    <!-- Quick View Modal (Enhanced Bottom Sheet) -->
    <div v-if="selectedProduct" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 overflow-hidden">
      <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closeQuick()"></div>
      <div class="relative bg-white w-full sm:max-w-lg rounded-t-[2.5rem] sm:rounded-[2.5rem] shadow-2xl animate-in slide-in-from-bottom duration-300 max-h-[90vh] overflow-y-auto z-10">
        
        <!-- Pull bar for mobile -->
        <div class="sm:hidden flex justify-center py-3">
          <div class="w-12 h-1.5 bg-slate-200 rounded-full"></div>
        </div>

        <div class="p-6 pt-2 sm:pt-6">
          <div class="flex items-start justify-between mb-6">
            <div class="flex-1">
              <span class="text-[10px] font-black text-emerald-600 uppercase tracking-widest mb-1 block">{{ selectedProduct.category?.name || 'Product Details' }}</span>
              <h2 class="text-2xl font-black text-slate-800 leading-tight">{{ selectedProduct.name }}</h2>
            </div>
            <button @click="closeQuick()" class="w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 hover:bg-rose-50 hover:text-rose-500 transition-colors">
              <span class="i-mdi-close text-xl"></span>
            </button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="aspect-square rounded-3xl bg-slate-50 overflow-hidden border border-slate-100">
               <img v-if="selectedProduct.image_url" :src="getImageUrl(selectedProduct.image_url)" alt="image" class="w-full h-full object-cover" />
               <div v-else class="w-full h-full flex items-center justify-center text-slate-200 text-5xl">📦</div>
            </div>
            
            <div class="flex flex-col">
              <div class="bg-emerald-50 p-4 rounded-2xl border border-emerald-100 mb-4">
                <p class="text-[10px] font-black text-emerald-600 uppercase tracking-widest mb-1">Selling Price</p>
                <p class="text-3xl font-black text-emerald-700">₦ {{ money(selectedProduct.selling_price) }}</p>
              </div>

              <div class="space-y-4">
                <div v-if="selectedProduct.track_stock" class="flex items-center justify-between px-2">
                   <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Availability</span>
                   <span v-if="selectedProduct.stock_quantity > 0" class="badge-success">{{ selectedProduct.stock_quantity }} In Stock</span>
                   <span v-else class="bg-rose-100 text-rose-600 badge px-2 py-1">Sold Out</span>
                </div>
                
                <div v-if="selectedProduct.vendor" class="flex items-center justify-between px-2">
                   <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Sold By</span>
                   <span class="text-xs font-black text-slate-800">{{ selectedProduct.vendor.name }}</span>
                </div>
              </div>
            </div>
          </div>

          <div class="mb-8">
            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Product Description</h3>
            <div class="p-4 bg-slate-50 rounded-2xl text-sm text-slate-600 leading-relaxed border border-slate-100">
              {{ selectedProduct.description || 'No detailed description available for this product.' }}
            </div>
          </div>

          <!-- Admin Actions -->
          <div v-if="isAdmin" class="flex items-center gap-3 mb-6 p-4 bg-amber-50 rounded-2xl border border-amber-100">
            <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center text-xl">🛡️</div>
            <div class="flex-1">
              <p class="text-[10px] font-black text-amber-600 uppercase tracking-widest">Admin Control</p>
              <div class="flex gap-2 mt-1">
                <button v-if="!selectedProduct.is_approved" class="text-[10px] font-black uppercase text-emerald-700 hover:underline" @click="approveProduct(selectedProduct)">Approve Now</button>
                <button v-else-if="selectedProduct.vendor_id" class="text-[10px] font-black uppercase text-rose-700 hover:underline" @click="rejectProduct(selectedProduct)">Mark Pending</button>
              </div>
            </div>
          </div>

          <!-- Cart Action -->
          <div v-if="!selectedProduct.track_stock || selectedProduct.stock_quantity > 0" class="flex items-center gap-4">
            <div class="flex items-center bg-slate-100 rounded-2xl p-1 shrink-0">
              <button class="w-12 h-12 flex items-center justify-center text-slate-500 hover:text-emerald-600 transition-colors" @click="quickQty = Math.max(1, (Number(quickQty)||1)-1)">
                <span class="i-mdi-minus text-xl"></span>
              </button>
              <input v-model.number="quickQty" type="number" min="1" :max="selectedProduct.track_stock ? selectedProduct.stock_quantity : undefined" class="w-12 text-center bg-transparent font-black text-slate-800 outline-none" />
              <button class="w-12 h-12 flex items-center justify-center text-slate-500 hover:text-emerald-600 transition-colors" @click="quickQty = Math.min((selectedProduct.track_stock ? selectedProduct.stock_quantity : 999), (Number(quickQty)||1)+1)">
                <span class="i-mdi-plus text-xl"></span>
              </button>
            </div>
            
            <button class="flex-1 h-14 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl font-black uppercase tracking-wider shadow-lg shadow-emerald-900/20 transition-all active:scale-95 flex items-center justify-center gap-2" @click="addQuickToCart()">
              <span class="i-mdi-cart-plus text-xl"></span>
              Add to Cart
            </button>
          </div>
          <div v-else>
            <button disabled class="w-full h-14 bg-slate-200 text-slate-400 rounded-2xl font-black uppercase tracking-wider cursor-not-allowed">Product Out of Stock</button>
          </div>
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

    <AppBottomNav />
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import AppHeader from '../components/AppHeader.vue'
import AppBottomNav from '../components/AppBottomNav.vue'
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

const vendor = ref(null)
const isAdmin = ref(false)
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
const agreedToTerms = ref(false)

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

const loadVendor = async () => {
  try {
    const { data } = await axios.get('/api/vendor/profile')
    if (data && data.id) {
      vendor.value = data
    }
  } catch (_) {}
}

const loadAdminStatus = async () => {
  try {
    const { data } = await axios.get('/api/dashboard')
    isAdmin.value = !!data.is_admin
  } catch (_) {}
}

const approveProduct = async (p) => {
  if (!confirm(`Approve "${p.name}"?`)) return
  try {
    await axios.post(`/api/admin/products/${p.id}/approve`)
    p.is_approved = true
    alert('Product approved successfully')
  } catch (e) {
    alert(e?.response?.data?.message || 'Failed to approve product')
  }
}

const rejectProduct = async (p) => {
  if (!confirm(`Mark "${p.name}" as pending?`)) return
  try {
    await axios.post(`/api/admin/products/${p.id}/reject`)
    p.is_approved = false
    alert('Product marked as pending')
  } catch (e) {
    alert(e?.response?.data?.message || 'Failed to update status')
  }
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

onMounted(() => { restoreCart(); load(1); loadWallet(); loadCategories(); loadStoreEligibility(); loadVendor(); loadAdminStatus() })
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
