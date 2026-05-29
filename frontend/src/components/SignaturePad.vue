<template>
  <div class="w-full">
    <label v-if="label" class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">
      {{ label }}
    </label>
    <div class="relative group">
      <div 
        class="border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50/50 p-2 transition-all duration-200 focus-within:ring-2 focus-within:ring-blue-500/20"
        :class="{ 'border-blue-500/40 bg-blue-50/20': isDrawing }"
      >
        <canvas 
          ref="canvas" 
          class="w-full h-32 cursor-crosshair touch-none"
          @mousedown="startDrawing"
          @mousemove="draw"
          @mouseup="stopDrawing"
          @mouseleave="stopDrawing"
          @touchstart="handleTouch"
          @touchmove="handleTouch"
          @touchend="stopDrawing"
        ></canvas>
        <div class="absolute right-4 bottom-4 flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
          <button 
            type="button" 
            @click="clear" 
            class="p-2 bg-white rounded-xl shadow-sm border border-slate-100 text-slate-400 hover:text-rose-500 hover:border-rose-100 transition-colors"
            title="Clear"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
          </button>
        </div>
      </div>
      <p v-if="hint" class="text-[10px] text-slate-400 mt-2 ml-1 italic">{{ hint }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
  modelValue: String,
  label: String,
  hint: String
});

const emit = defineEmits(['update:modelValue']);

const canvas = ref(null);
const ctx = ref(null);
const isDrawing = ref(false);
const hasDrawing = ref(false);

const startDrawing = (e) => {
  isDrawing.value = true;
  const { x, y } = getCoordinates(e);
  ctx.value.beginPath();
  ctx.value.moveTo(x, y);
};

const draw = (e) => {
  if (!isDrawing.value) return;
  const { x, y } = getCoordinates(e);
  ctx.value.lineTo(x, y);
  ctx.value.stroke();
  hasDrawing.value = true;
};

const stopDrawing = () => {
  if (isDrawing.value) {
    isDrawing.value = false;
    ctx.value.closePath();
    save();
  }
};

const handleTouch = (e) => {
  if (e.touches.length > 1) return;
  e.preventDefault();
  const touch = e.touches[0];
  const type = e.type === 'touchstart' ? 'mousedown' : 'mousemove';
  const mouseEvent = new MouseEvent(type, {
    clientX: touch.clientX,
    clientY: touch.clientY
  });
  
  if (type === 'mousedown') startDrawing(mouseEvent);
  else draw(mouseEvent);
};

const getCoordinates = (e) => {
  const rect = canvas.value.getBoundingClientRect();
  const scaleX = canvas.value.width / rect.width;
  const scaleY = canvas.value.height / rect.height;
  return {
    x: (e.clientX - rect.left) * scaleX,
    y: (e.clientY - rect.top) * scaleY
  };
};

const clear = () => {
  ctx.value.clearRect(0, 0, canvas.value.width, canvas.value.height);
  hasDrawing.value = false;
  emit('update:modelValue', null);
};

const save = () => {
  if (!hasDrawing.value) return;
  // Convert canvas to base64
  const dataUrl = canvas.value.toDataURL('image/png');
  emit('update:modelValue', dataUrl);
};

onMounted(() => {
  ctx.value = canvas.value.getContext('2d');
  
  // Set canvas resolution for better quality
  const rect = canvas.value.getBoundingClientRect();
  canvas.value.width = rect.width * window.devicePixelRatio;
  canvas.value.height = rect.height * window.devicePixelRatio;
  
  // Basic context settings
  ctx.value.lineWidth = 2 * window.devicePixelRatio;
  ctx.value.lineCap = 'round';
  ctx.value.lineJoin = 'round';
  ctx.value.strokeStyle = '#000';
});

// If we need to resize
const handleResize = () => {
  if (!canvas.value) return;
  const rect = canvas.value.getBoundingClientRect();
  const temp = ctx.value.getImageData(0, 0, canvas.value.width, canvas.value.height);
  canvas.value.width = rect.width * window.devicePixelRatio;
  canvas.value.height = rect.height * window.devicePixelRatio;
  ctx.value.putImageData(temp, 0, 0);
  ctx.value.lineWidth = 2 * window.devicePixelRatio;
  ctx.value.lineCap = 'round';
  ctx.value.lineJoin = 'round';
};

window.addEventListener('resize', handleResize);
onUnmounted(() => window.removeEventListener('resize', handleResize));
</script>


