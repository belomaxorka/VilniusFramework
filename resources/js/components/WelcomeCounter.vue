<template>
  <div class="text-center">
    <p class="text-sm text-gray-500 mb-3">Счётчик</p>
    
    <div class="text-6xl font-bold text-gray-900 mb-6">
      {{ count }}
    </div>
    
    <div class="flex gap-3 justify-center">
      <button
        @click="count--"
        class="w-12 h-12 bg-gray-200 hover:bg-gray-300 rounded-lg text-xl transition-colors"
      >
        −
      </button>
      <button
        @click="count++"
        class="w-12 h-12 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xl transition-colors"
      >
        +
      </button>
    </div>
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

