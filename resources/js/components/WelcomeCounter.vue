<template>
  <div class="text-center">
    <!-- Counter Display -->
    <div class="mb-6">
      <div class="inline-flex items-center justify-center w-40 h-40 bg-gradient-to-br from-blue-100 to-purple-100 rounded-2xl shadow-inner">
        <span class="text-7xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
          {{ count }}
        </span>
      </div>
    </div>
    
    <!-- Buttons -->
    <div class="flex gap-4 justify-center mb-4">
      <button
        @click="count--"
        class="group relative w-16 h-16 bg-white hover:bg-gray-50 border-2 border-gray-200 hover:border-gray-300 rounded-xl text-3xl font-semibold text-gray-700 transition-all shadow-sm hover:shadow-md"
      >
        <span class="group-hover:scale-110 inline-block transition-transform">−</span>
      </button>
      <button
        @click="count++"
        class="group relative w-16 h-16 bg-gradient-to-br from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 rounded-xl text-3xl font-semibold text-white transition-all shadow-md hover:shadow-lg"
      >
        <span class="group-hover:scale-110 inline-block transition-transform">+</span>
      </button>
    </div>

    <p class="text-xs text-gray-400">⚡ Powered by Vue 3</p>
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

