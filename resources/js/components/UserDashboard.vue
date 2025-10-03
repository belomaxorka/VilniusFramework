<template>
  <div class="bg-white rounded-xl shadow-lg overflow-hidden">
    <!-- Header -->
    <div class="px-6 py-4 bg-gradient-to-r from-gray-50 to-blue-50 border-b">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <h3 class="text-2xl font-bold text-gray-900">👥 Управление пользователями</h3>
        <button
          @click="showCreateModal = true"
          class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors flex items-center gap-2"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Добавить пользователя
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="px-6 py-5 bg-gradient-to-r from-gray-50 to-blue-50 border-b border-gray-200">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Search -->
        <div class="relative">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
          <input
            v-model="search"
            @input="onSearch"
            type="text"
            placeholder="Поиск по имени или email..."
            class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
          />
        </div>

        <!-- Role Filter -->
        <div class="relative">
          <select
            v-model="filterRole"
            @change="fetchUsers"
            class="block w-full pl-3 pr-10 py-2.5 text-base border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent rounded-lg bg-white transition-all"
          >
            <option value="">🎭 Все роли</option>
            <option value="admin">👑 Администратор</option>
            <option value="moderator">🛡️ Модератор</option>
            <option value="user">👤 Пользователь</option>
          </select>
        </div>

        <!-- Status Filter -->
        <div class="relative">
          <select
            v-model="filterStatus"
            @change="fetchUsers"
            class="block w-full pl-3 pr-10 py-2.5 text-base border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent rounded-lg bg-white transition-all"
          >
            <option value="">📊 Все статусы</option>
            <option value="active">✅ Активные</option>
            <option value="inactive">❌ Неактивные</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="px-6 py-12 text-center">
      <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      <p class="mt-4 text-gray-600">Загрузка...</p>
    </div>

    <!-- Users Table -->
    <div v-else>
      <!-- Empty State -->
      <div v-if="users.length === 0" class="text-center py-12 px-6">
        <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
        <h3 class="mt-4 text-lg font-medium text-gray-900">Пользователи не найдены</h3>
        <p class="mt-2 text-sm text-gray-500">Попробуйте изменить параметры фильтрации или добавьте нового пользователя</p>
      </div>

      <!-- Table -->
      <div v-else class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-200 bg-gray-50">
              <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                Пользователь
              </th>
              <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                Email
              </th>
              <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                Роль
              </th>
              <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                Статус
              </th>
              <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                Посты
              </th>
              <th class="px-6 py-4 text-right text-xs font-semibold text-gray-700 uppercase tracking-wider">
                Действия
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-100">
            <tr 
              v-for="user in users" 
              :key="user.id" 
              class="hover:bg-blue-50 transition-colors duration-150"
            >
              <td class="px-6 py-4">
                <div class="flex items-center space-x-3">
                  <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center bg-gradient-to-br from-blue-100 to-purple-100 rounded-full text-2xl">
                    {{ user.avatar }}
                  </div>
                  <div>
                    <div class="text-sm font-semibold text-gray-900">{{ user.name }}</div>
                    <div class="text-xs text-gray-500">ID: {{ user.id }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm text-gray-900">{{ user.email }}</div>
              </td>
              <td class="px-6 py-4">
                <span 
                  :class="getRoleBadgeClass(user.role)" 
                  class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold"
                >
                  {{ getRoleLabel(user.role) }}
                </span>
              </td>
              <td class="px-6 py-4">
                <span 
                  :class="getStatusBadgeClass(user.status)" 
                  class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold"
                >
                  <span 
                    :class="user.status === 'active' ? 'bg-green-500' : 'bg-gray-400'" 
                    class="w-2 h-2 rounded-full mr-2"
                  ></span>
                  {{ user.status === 'active' ? 'Активен' : 'Неактивен' }}
                </span>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm font-medium text-gray-900">{{ user.posts_count }}</div>
              </td>
              <td class="px-6 py-4 text-right space-x-2">
                <button
                  @click="editUser(user)"
                  class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                >
                  <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                  Изменить
                </button>
                <button
                  @click="deleteUserConfirm(user)"
                  class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors"
                >
                  <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                  Удалить
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div 
      v-if="showCreateModal || showEditModal" 
      class="fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4"
      @click.self="closeModals"
    >
      <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md transform transition-all">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-4 rounded-t-xl">
          <h3 class="text-xl font-bold text-white">
            {{ showCreateModal ? '➕ Добавить пользователя' : '✏️ Редактировать пользователя' }}
          </h3>
        </div>

        <!-- Form -->
        <form @submit.prevent="submitForm" class="p-6 space-y-5">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Имя</label>
            <input
              v-model="formData.name"
              type="text"
              required
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
              placeholder="Введите имя"
            />
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
            <input
              v-model="formData.email"
              type="email"
              required
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
              placeholder="user@example.com"
            />
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Аватар (emoji)</label>
            <div class="flex items-center space-x-3">
              <div class="w-12 h-12 flex items-center justify-center bg-gradient-to-br from-blue-100 to-purple-100 rounded-full text-2xl">
                {{ formData.avatar || '👤' }}
              </div>
              <input
                v-model="formData.avatar"
                type="text"
                placeholder="👤"
                class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
              />
            </div>
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Роль</label>
            <select
              v-model="formData.role"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-white"
            >
              <option value="user">👤 Пользователь</option>
              <option value="moderator">🛡️ Модератор</option>
              <option value="admin">👑 Администратор</option>
            </select>
          </div>

          <div v-if="showEditModal">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Статус</label>
            <select
              v-model="formData.status"
              class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-white"
            >
              <option value="active">✅ Активен</option>
              <option value="inactive">❌ Неактивен</option>
            </select>
          </div>

          <!-- Buttons -->
          <div class="flex gap-3 pt-4 border-t">
            <button
              type="submit"
              :disabled="submitting"
              class="flex-1 bg-gradient-to-r from-blue-600 to-purple-600 text-white px-6 py-3 rounded-lg font-semibold hover:from-blue-700 hover:to-purple-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-md hover:shadow-lg"
            >
              {{ submitting ? '⏳ Сохранение...' : '💾 Сохранить' }}
            </button>
            <button
              type="button"
              @click="closeModals"
              :disabled="submitting"
              class="flex-1 bg-gray-100 text-gray-700 px-6 py-3 rounded-lg font-semibold hover:bg-gray-200 disabled:opacity-50 transition-all"
            >
              ❌ Отмена
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useApi } from '../composables/useApi';

const api = useApi();

// State
const users = ref([]);
const loading = ref(false);
const search = ref('');
const filterRole = ref('');
const filterStatus = ref('');
const showCreateModal = ref(false);
const showEditModal = ref(false);
const submitting = ref(false);
const formData = ref({
  id: null,
  name: '',
  email: '',
  avatar: '👤',
  role: 'user',
  status: 'active',
});

let searchTimeout = null;

// Methods
const fetchUsers = async () => {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (search.value) params.append('search', search.value);
    if (filterRole.value) params.append('role', filterRole.value);
    if (filterStatus.value) params.append('status', filterStatus.value);

    const result = await api.get(`/users?${params.toString()}`);
    users.value = result.users;
  } catch (error) {
    console.error('❌ Error fetching users:', error);
  } finally {
    loading.value = false;
  }
};

const onSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    fetchUsers();
  }, 300);
};

const editUser = (user) => {
  formData.value = { ...user };
  showEditModal.value = true;
};

const deleteUserConfirm = async (user) => {
  if (confirm(`Удалить пользователя ${user.name}?`)) {
    try {
      const result = await api.del(`/users/${user.id}`);
      
      if (result.success) {
        await fetchUsers();
      } else {
        throw new Error(result.message || 'Неизвестная ошибка');
      }
    } catch (error) {
      console.error('❌ Error deleting user:', error);
      alert('Ошибка при удалении пользователя: ' + (error.message || error));
    }
  }
};

const submitForm = async () => {
  submitting.value = true;
  try {
    if (showCreateModal.value) {
      await api.post('/users', formData.value);
    } else {
      await api.put(`/users/${formData.value.id}`, formData.value);
    }
    await fetchUsers();
    closeModals();
  } catch (error) {
    console.error('❌ Error submitting form:', error);
    alert('Ошибка при сохранении');
  } finally {
    submitting.value = false;
  }
};

const closeModals = () => {
  showCreateModal.value = false;
  showEditModal.value = false;
  formData.value = {
    id: null,
    name: '',
    email: '',
    avatar: '👤',
    role: 'user',
    status: 'active',
  };
};

const getRoleLabel = (role) => {
  const labels = {
    admin: 'Администратор',
    moderator: 'Модератор',
    user: 'Пользователь',
  };
  return labels[role] || role;
};

const getRoleBadgeClass = (role) => {
  const classes = {
    admin: 'bg-red-100 text-red-800',
    moderator: 'bg-purple-100 text-purple-800',
    user: 'bg-blue-100 text-blue-800',
  };
  return classes[role] || 'bg-gray-100 text-gray-800';
};

const getStatusBadgeClass = (status) => {
  return status === 'active'
    ? 'bg-green-100 text-green-800'
    : 'bg-gray-100 text-gray-800';
};

// Load initial data
onMounted(() => {
  // Use data from PHP if available
  if (window.DASHBOARD_DATA?.users) {
    users.value = window.DASHBOARD_DATA.users;
  } else {
    fetchUsers();
  }
});
</script>

