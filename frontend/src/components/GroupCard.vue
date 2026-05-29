<template>
  <div class="bg-white rounded-[2rem] border border-slate-100 shadow-sm overflow-hidden hover:shadow-md transition-shadow cursor-pointer p-5 flex items-center gap-4">
    <div class="w-14 h-14 bg-indigo-50 rounded-2xl flex items-center justify-center text-2xl flex-shrink-0">
      {{ group.icon || 'Ã°Å¸Â¤Â' }}
    </div>
    <div class="flex-1 min-w-0">
      <div class="flex items-center gap-2">
        <h3 class="font-bold text-slate-800 truncate">{{ group.name }}</h3>
        <span v-if="isInvitation" class="text-[8px] bg-indigo-100 text-indigo-600 px-1.5 py-0.5 rounded font-black uppercase tracking-widest">Invited</span>
      </div>
      <p class="text-[11px] text-slate-500 mt-0.5 font-medium truncate">Invested in: <span class="text-indigo-600 uppercase">{{ group.project?.name || 'General Projects' }}</span></p>
      <div class="flex items-center gap-2 mt-2">
        <span class="text-[10px] bg-slate-100 px-2 py-0.5 rounded-full font-bold text-slate-600 uppercase tracking-tighter">Ã¢â€šÂ¦ {{ formatMoney(group.monthly_contribution_amount) }}/mo</span>
        <span class="text-[10px] bg-blue-50 px-2 py-0.5 rounded-full font-bold text-blue-700 uppercase tracking-tighter">{{ group.active_members_count || 0 }} Members</span>
      </div>
    </div>
    <div class="text-slate-300">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
    </div>
  </div>
</template>

<script setup>
defineProps({
  group: {
    type: Object,
    required: true
  },
  isInvitation: {
    type: Boolean,
    default: false
  }
})

const formatMoney = (val) => {
  return Number(val || 0).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 })
}
</script>


