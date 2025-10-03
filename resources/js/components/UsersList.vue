<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-2xl font-bold text-gray-800">
        <span class="bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-transparent">
          База данных
        </span>
      </h2>
      <div class="flex items-center space-x-2 px-4 py-2 bg-green-100 rounded-full">
        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
        </svg>
        <span class="text-sm font-semibold text-green-700">{{ usersList.length }} пользователей</span>
      </div>
    </div>

    <!-- Users List -->
    <div v-if="usersList.length > 0" class="space-y-3">
      <transition-group name="user-list">
        <div
          v-for="user in displayedUsers"
          :key="user.id"
          class="flex items-center space-x-3 p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors"
        >
          <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-purple-500 rounded-full flex items-center justify-center text-white font-bold">
            {{ getInitial(user.name) }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 truncate">
              {{ user.name }}
            </p>
            <p class="text-xs text-gray-500 truncate">
              {{ user.email }}
            </p>
          </div>
          <div class="flex items-center space-x-2">
            <div v-if="user.email_verified_at" class="flex-shrink-0">
              <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
              </svg>
            </div>
            <button
              @click="deleteUser(user.id)"
              :disabled="deleting === user.id"
              class="group relative p-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed"
              :title="'Удалить ' + user.name"
            >
              <svg v-if="deleting !== user.id" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
              <svg v-else class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </button>
          </div>
        </div>
      </transition-group>

      <div v-if="usersList.length > 5" class="text-center pt-2">
        <span class="text-sm text-gray-500">...и еще {{ usersList.length - 5 }} пользователей</span>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-8">
      <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
      </svg>
      <p class="text-gray-500 text-sm">Пользователи не найдены</p>
      <p class="text-xs text-gray-400 mt-1">Запустите миграции: php vilnius migrate</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  users: {
    type: Array,
    default: () => []
  }
});

// Локальное состояние списка пользователей
const usersList = ref([...props.users]);
const deleting = ref(null);

// Показываем только первых 5 пользователей
const displayedUsers = computed(() => {
  return usersList.value.slice(0, 5);
});

// Получить первую букву имени
const getInitial = (name) => {
  return name ? name.charAt(0).toUpperCase() : '?';
};

// Получить CSRF токен
const getCsrfToken = () => {
  const metaTag = document.querySelector('meta[name="csrf-token"]');
  return metaTag ? metaTag.getAttribute('content') : '';
};

// Удалить пользователя
const deleteUser = async (userId) => {
  if (!confirm('Вы уверены, что хотите удалить этого пользователя?')) {
    return;
  }

  deleting.value = userId;

  try {
    // Используем POST с _method=DELETE для совместимости
    const response = await fetch(`/api/users/${userId}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': getCsrfToken()
      },
      body: JSON.stringify({
        _method: 'DELETE'
      })
    });

    const data = await response.json();

    if (response.ok && data.success) {
      // Удаляем пользователя из списка
      usersList.value = usersList.value.filter(user => user.id !== userId);
      
      // Можно показать уведомление об успехе
      console.log('✅', data.message);
    } else {
      console.error('Server error:', data);
      alert('Ошибка: ' + (data.message || 'Неизвестная ошибка'));
    }
  } catch (error) {
    console.error('Ошибка при удалении пользователя:', error);
    alert('Произошла ошибка при удалении пользователя: ' + error.message);
  } finally {
    deleting.value = null;
  }
};
</script>

<style scoped>
/* Анимация для списка */
.user-list-enter-active,
.user-list-leave-active {
  transition: all 0.3s ease;
}

.user-list-enter-from {
  opacity: 0;
  transform: translateX(-30px);
}

.user-list-leave-to {
  opacity: 0;
  transform: translateX(30px);
}

.user-list-move {
  transition: transform 0.3s ease;
}
</style>

