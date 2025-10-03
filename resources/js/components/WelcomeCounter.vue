<template>
  <div class="text-center">
    <div class="mb-6">
      <p class="text-sm font-medium text-gray-500 mb-2">Interactive Counter</p>
      <div class="inline-flex items-center justify-center w-32 h-32 bg-gradient-to-br from-blue-100 to-purple-100 rounded-2xl shadow-inner">
        <span class="text-6xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
          {{ count }}
        </span>
      </div>
    </div>
    
    <div class="flex gap-3 justify-center">
      <button
        @click="count--"
        class="group relative w-14 h-14 bg-white hover:bg-gray-50 border-2 border-gray-200 hover:border-gray-300 rounded-xl text-2xl font-semibold text-gray-700 transition-all shadow-sm hover:shadow-md"
      >
        <span class="group-hover:scale-110 inline-block transition-transform">−</span>
      </button>
      <button
        @click="count++"
        class="group relative w-14 h-14 bg-gradient-to-br from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 rounded-xl text-2xl font-semibold text-white transition-all shadow-md hover:shadow-lg"
      >
        <span class="group-hover:scale-110 inline-block transition-transform">+</span>
      </button>
    </div>

    <p class="mt-4 text-xs text-gray-400">Powered by Vue 3</p>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
  initialCount: {
    type: Number,
    default: 0,
  },
});

const count = ref(props.initialCount);
</script>

