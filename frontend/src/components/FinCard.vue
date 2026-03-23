<template>
  <section :class="wrapperClass">
    <header v-if="$slots.title || title" class="flex items-center justify-between px-4 sm:px-6 pt-4 sm:pt-6">
      <div class="flex items-center gap-2 min-w-0">
        <slot name="icon" />
        <h3 class="section-title truncate">{{ title }}</h3>
      </div>
      <div>
        <slot name="action" />
      </div>
    </header>
    <div :class="contentClass">
      <slot />
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  title: { type: String, default: '' },
  elevated: { type: Boolean, default: false },
  padded: { type: Boolean, default: true },
})

const wrapperClass = computed(() => [
  'card',
  props.elevated && 'card-elevated',
])

const contentClass = computed(() => [
  props.padded ? 'p-4 sm:p-6' : '',
])
</script>
