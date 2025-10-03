# 🎉 Vue 3 Integration Complete!

Полноценная интеграция Vue 3 в ваш PHP фреймворк — как в Laravel, но еще лучше! 🚀

---

## ✅ Что было сделано

### 📦 Установлены пакеты
- ✅ **Vue 3** (v3.4.15) - современный реактивный фреймворк
- ✅ **@vitejs/plugin-vue** - Vite плагин для Vue SFC
- ✅ **@vue/compiler-sfc** - компилятор Single File Components

### 🔧 Настроена конфигурация
- ✅ **vite.config.js** - добавлен Vue плагин
- ✅ **app.js** - настроена инициализация Vue приложения
- ✅ HMR работает "из коробки"

### 🧩 Созданы компоненты
- ✅ **ExampleComponent.vue** - демо компонент с Composition API
- ✅ **Counter.vue** - интерактивный счетчик
- ✅ **Alert.vue** - система уведомлений (4 типа)

### 🔄 Созданы Composables
- ✅ **useApi** - HTTP запросы (GET, POST, PUT, DELETE) с CSRF
- ✅ **useForm** - управление формами с валидацией

### 📝 Документация
- ✅ **docs/Vue.md** - полная документация (500+ строк)
- ✅ **vue-example.twig** - пример страницы с Vue компонентами

---

## 🚀 Быстрый старт (3 шага)

### 1. Установите зависимости

```bash
npm install
```

### 2. Запустите dev сервер

```bash
npm run dev
```

### 3. Используйте в шаблонах!

```twig
{# resources/views/my-page.twig #}
{% extends 'layout.twig' %}

{% block content %}
<div id="app">
    <example-component title="Привет, Vue!"></example-component>
    <counter :initial-value="10"></counter>
    <alert type="success" message="Vue работает! 🎉"></alert>
</div>
{% endblock %}
```

**Готово!** Vue 3 полностью интегрирован! ✨

---

## 📁 Структура файлов

```
resources/
├── js/
│   ├── app.js                       # ✨ Инициализация Vue
│   ├── components/                  # ✨ Vue компоненты
│   │   ├── ExampleComponent.vue    # Демо компонент
│   │   ├── Counter.vue             # Счетчик
│   │   └── Alert.vue               # Уведомления
│   └── composables/                 # ✨ Переиспользуемая логика
│       ├── useApi.js               # HTTP запросы
│       └── useForm.js              # Работа с формами
├── css/
│   └── app.css                      # Tailwind CSS
└── views/
    └── vue-example.twig             # ✨ Пример страницы

package.json                         # ✨ Обновлен с Vue зависимостями
vite.config.js                       # ✨ Настроен Vue плагин
docs/Vue.md                          # ✨ Полная документация
```

---

## 🎯 Основные возможности

### 1️⃣ Single File Components (.vue)

```vue
<template>
  <div class="card">
    <h2>{{ title }}</h2>
    <button @click="count++">Clicks: {{ count }}</button>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
  title: String
});

const count = ref(0);
</script>

<style scoped>
.card { padding: 1rem; }
</style>
```

### 2️⃣ Composition API

```javascript
import { ref, computed, watch, onMounted } from 'vue';

const count = ref(0);
const doubled = computed(() => count.value * 2);

watch(count, (newVal) => {
  console.log('Count changed:', newVal);
});

onMounted(() => {
  console.log('Component ready!');
});
```

### 3️⃣ Composables (как Laravel Traits)

```javascript
import { useApi } from './composables/useApi';

const api = useApi('/api');

// GET
const users = await api.get('/users');

// POST
await api.post('/users', { name: 'John' });

// Reactive state
console.log(api.loading.value);  // true/false
console.log(api.error.value);    // error message
```

### 4️⃣ Form Management

```javascript
import { useForm, rules } from './composables/useForm';

const { form, errors, submit } = useForm({
  email: '',
  password: ''
});

const register = () => {
  submit(async (data) => {
    return await fetch('/api/register', {
      method: 'POST',
      body: JSON.stringify(data)
    });
  });
};
```

---

## 📝 Готовые компоненты

### ExampleComponent

Демонстрационный компонент с реактивностью:

```vue
<example-component 
  title="Мой заголовок"
  initial-message="Начальное сообщение"
></example-component>
```

**Возможности:**
- ✅ Props для настройки
- ✅ Реактивное состояние
- ✅ События клика
- ✅ Анимации

### Counter

Интерактивный счетчик:

```vue
<counter 
  :initial-value="10"
  :step="5"
  @update:count="handleUpdate"
  @reset="handleReset"
></counter>
```

**Возможности:**
- ✅ Кнопки +/- с шагом
- ✅ Сброс значения
- ✅ События для родителя
- ✅ Условный рендеринг сообщений

### Alert

Красивые уведомления:

```vue
<alert 
  type="success" 
  title="Успех!" 
  message="Операция выполнена"
  :dismissible="true"
  :auto-close="5000"
></alert>
```

**Типы:** `success`, `error`, `warning`, `info`

**Возможности:**
- ✅ 4 типа уведомлений
- ✅ Иконки и цвета
- ✅ Авто-закрытие
- ✅ Кнопка закрытия
- ✅ Анимации появления/исчезновения

---

## 🔄 Composables

### useApi - HTTP запросы

```javascript
import { useApi } from './composables/useApi';

const api = useApi('/api');

// GET запрос
const fetchData = async () => {
  try {
    const data = await api.get('/users');
    console.log(data);
  } catch (error) {
    console.error(api.error.value);
  }
};

// POST запрос
await api.post('/users', { 
  name: 'John',
  email: 'john@example.com' 
});

// PUT запрос
await api.put('/users/1', { name: 'Jane' });

// DELETE запрос
await api.delete('/users/1');

// Reactive state
if (api.loading.value) {
  console.log('Loading...');
}

if (api.hasError.value) {
  console.error(api.error.value);
}
```

**Фичи:**
- ✅ Автоматический CSRF токен
- ✅ JSON заголовки
- ✅ Реактивное состояние загрузки
- ✅ Обработка ошибок
- ✅ TypeScript дружественный

### useForm - Управление формами

```javascript
import { useForm, rules } from './composables/useForm';

const { 
  form,          // Reactive form data
  errors,        // Validation errors
  processing,    // Loading state
  submit,        // Submit handler
  hasError,      // Check field error
  getError,      // Get field error
  validate       // Validate field
} = useForm({
  name: '',
  email: '',
  password: ''
});

// Validation
validate('email', [
  rules.required('Email обязателен'),
  rules.email('Неверный формат email')
]);

// Submit
const handleSubmit = () => {
  submit(async (data) => {
    const response = await fetch('/api/register', {
      method: 'POST',
      body: JSON.stringify(data)
    });
    return await response.json();
  });
};
```

**Доступные правила валидации:**
- `rules.required(message)`
- `rules.email(message)`
- `rules.minLength(min, message)`
- `rules.maxLength(max, message)`
- `rules.min(value, message)`
- `rules.max(value, message)`
- `rules.confirmed(field, message)`

---

## 🌐 Интеграция с PHP

### CSRF Protection

В `layout.twig`:

```twig
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
```

CSRF токен **автоматически** добавляется ко всем запросам через `useApi`.

### Передача данных в Vue

#### Метод 1: Через props

```twig
<div id="app">
    <user-dashboard 
      :user-id="{{ user.id }}"
      :user-name="'{{ user.name }}'"
      :is-admin="{{ user.isAdmin ? 'true' : 'false' }}"
    ></user-dashboard>
</div>
```

#### Метод 2: Через window объект

```twig
<script>
    window.APP_DATA = {
        user: {{ user|json_encode|raw }},
        config: {{ config|json_encode|raw }},
        translations: {{ translations|json_encode|raw }}
    };
</script>
```

В Vue:

```javascript
const userData = window.APP_DATA?.user;
const config = window.APP_DATA?.config;
```

### API Routes

В `routes/web.php`:

```php
// API endpoints для Vue
$router->get('/api/users', [UserController::class, 'index']);
$router->post('/api/users', [UserController::class, 'store']);
$router->put('/api/users/{id}', [UserController::class, 'update']);
$router->delete('/api/users/{id}', [UserController::class, 'destroy']);
```

---

## 🎨 Стилизация

### Tailwind CSS

Все компоненты используют Tailwind CSS утилиты:

```vue
<template>
  <div class="bg-white rounded-lg shadow-md p-6">
    <h2 class="text-2xl font-bold text-gray-800 mb-4">
      Title
    </h2>
    <button class="bg-blue-500 hover:bg-blue-700 text-white px-4 py-2 rounded">
      Click me
    </button>
  </div>
</template>
```

### Scoped Styles

```vue
<style scoped>
/* Стили применяются только к этому компоненту */
.custom-class {
  color: red;
}
</style>
```

---

## 🧪 Примеры использования

### Простая форма

```vue
<template>
  <form @submit.prevent="handleSubmit">
    <input v-model="form.email" type="email" />
    <span v-if="hasError('email')">{{ getError('email') }}</span>
    
    <button :disabled="processing">Submit</button>
  </form>
</template>

<script setup>
import { useForm, rules } from './composables/useForm';
import { useApi } from './composables/useApi';

const { form, errors, processing, submit, hasError, getError } = useForm({
  email: ''
});

const api = useApi();

const handleSubmit = () => {
  submit(async (data) => {
    return await api.post('/subscribe', data);
  });
};
</script>
```

### Список с загрузкой

```vue
<template>
  <div>
    <div v-if="api.loading.value">Loading...</div>
    <div v-else-if="api.hasError.value">{{ api.error.value }}</div>
    <div v-else>
      <div v-for="user in users" :key="user.id">
        {{ user.name }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useApi } from './composables/useApi';

const api = useApi();
const users = ref([]);

onMounted(async () => {
  users.value = await api.get('/users');
});
</script>
```

---

## 📚 Документация

- **Полная документация:** [docs/Vue.md](docs/Vue.md)
- **Vite интеграция:** [docs/Vite.md](docs/Vite.md)
- **Vite настройка:** [docs/ViteSetup.md](docs/ViteSetup.md)
- **Пример страницы:** [resources/views/vue-example.twig](resources/views/vue-example.twig)

---

## 🎯 Что дальше?

### Рекомендуемые пакеты

1. **Vue Router** - для SPA навигации
   ```bash
   npm install vue-router@4
   ```

2. **Pinia** - state management (замена Vuex)
   ```bash
   npm install pinia
   ```

3. **VueUse** - коллекция composables
   ```bash
   npm install @vueuse/core
   ```

4. **Headless UI** - доступные компоненты
   ```bash
   npm install @headlessui/vue
   ```

### Создание своих composables

```javascript
// resources/js/composables/useAuth.js
import { ref } from 'vue';

export function useAuth() {
  const user = ref(null);
  const isAuthenticated = ref(false);
  
  const login = async (credentials) => {
    // Login logic
  };
  
  const logout = async () => {
    // Logout logic
  };
  
  return {
    user,
    isAuthenticated,
    login,
    logout
  };
}
```

---

## ✨ Возможности

| Фича | Статус | Описание |
|------|--------|----------|
| Single File Components | ✅ | `.vue` файлы с template, script, style |
| Composition API | ✅ | Современный API Vue 3 |
| Hot Module Replacement | ✅ | Мгновенное обновление без перезагрузки |
| TypeScript | ⚠️ | Поддерживается, но не настроен |
| Vue DevTools | ✅ | Работает из коробки |
| Scoped Styles | ✅ | CSS только для компонента |
| Tailwind CSS | ✅ | Интегрирован по умолчанию |
| CSRF Protection | ✅ | Автоматически в useApi |
| Form Validation | ✅ | Через useForm + rules |
| HTTP Client | ✅ | useApi composable |

---

## 🎉 Заключение

Теперь у вас есть полноценная Vue 3 интеграция с:

- ✅ Современным Composition API
- ✅ Single File Components
- ✅ Hot Module Replacement
- ✅ Готовыми компонентами
- ✅ Переиспользуемыми composables
- ✅ Интеграцией с PHP/Backend
- ✅ Подробной документацией

**Все как в Laravel, но даже лучше!** 🚀

Приятной разработки! 💜

