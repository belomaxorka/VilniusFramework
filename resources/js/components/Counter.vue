<template>
  <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
    <h3 class="text-xl font-bold mb-4">Интерактивный счетчик</h3>
    
    <div class="flex items-center justify-center mb-6">
      <div class="text-6xl font-bold">
        {{ count }}
      </div>
    </div>

    <div class="flex gap-3 justify-center">
      <button
        @click="decrement"
        class="bg-white bg-opacity-20 hover:bg-opacity-30 backdrop-blur-sm px-6 py-3 rounded-lg font-semibold transition-all duration-200 hover:scale-105"
        :disabled="count <= 0"
        :class="{ 'opacity-50 cursor-not-allowed': count <= 0 }"
      >
        -
      </button>
      
      <button
        @click="reset"
        class="bg-white bg-opacity-20 hover:bg-opacity-30 backdrop-blur-sm px-6 py-3 rounded-lg font-semibold transition-all duration-200 hover:scale-105"
      >
        Сброс
      </button>
      
      <button
        @click="increment"
        class="bg-white bg-opacity-20 hover:bg-opacity-30 backdrop-blur-sm px-6 py-3 rounded-lg font-semibold transition-all duration-200 hover:scale-105"
      >
        +
      </button>
    </div>

    <div class="mt-4 text-center text-sm opacity-80">
      <p v-if="count === 0">Начните считать!</p>
      <p v-else-if="count < 10">Так держать! 👍</p>
      <p v-else-if="count < 50">Отличный результат! 🎉</p>
      <p v-else>Вы мастер счета! 🏆</p>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';

// Props
const props = defineProps({
  initialValue: {
    type: Number,
    default: 0,
  },
  step: {
    type: Number,
    default: 1,
  },
});

// Emits
const emit = defineEmits(['update:count', 'reset']);

// State
const count = ref(props.initialValue);

// Watch for changes
watch(count, (newValue) => {
  emit('update:count', newValue);
});

// Methods
const increment = () => {
  count.value += props.step;
};

const decrement = () => {
  if (count.value > 0) {
    count.value -= props.step;
  }
};

const reset = () => {
  count.value = 0;
  emit('reset');
};
</script>

