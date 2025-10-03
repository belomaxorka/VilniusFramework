<template>
  <div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">
      {{ title }}
    </h2>
    <p class="text-gray-600 mb-4">
      {{ message }}
    </p>
    <button
      @click="updateMessage"
      class="bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 px-4 rounded transition-colors duration-200"
    >
      {{ buttonText }}
    </button>
    <p v-if="clickCount > 0" class="mt-4 text-sm text-gray-500">
      Вы нажали {{ clickCount }} раз
    </p>
  </div>
</template>

<script setup>
import { ref } from 'vue';

// Props
const props = defineProps({
  title: {
    type: String,
    default: 'Vue 3 Component Example',
  },
  initialMessage: {
    type: String,
    default: 'Это пример Vue 3 компонента в вашем фреймворке!',
  },
});

// Reactive state
const message = ref(props.initialMessage);
const buttonText = ref('Изменить сообщение');
const clickCount = ref(0);

const messages = [
  'Vue 3 работает отлично! 🎉',
  'Composition API - это просто супер! ✨',
  'Reactivity в лучшем виде! ⚡',
  'Tailwind CSS + Vue = ❤️',
  props.initialMessage,
];

// Methods
const updateMessage = () => {
  clickCount.value++;
  const randomIndex = Math.floor(Math.random() * messages.length);
  message.value = messages[randomIndex];
};
</script>

<style scoped>
/* Component-specific styles */
button:active {
  transform: scale(0.98);
}
</style>

