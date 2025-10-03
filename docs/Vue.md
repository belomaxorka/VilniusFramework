# Vue 3 Integration

Полноценная интеграция Vue 3 с Composition API, Single File Components (SFC) и Hot Module Replacement.

## 📦 Установка

### 1. Установите зависимости

```bash
npm install
```

Это установит:
- `vue` (^3.4.15) - Vue 3 framework
- `@vitejs/plugin-vue` (^5.0.3) - Vite плагин для Vue
- `@vue/compiler-sfc` (^3.4.15) - Компилятор Single File Components

### 2. Запустите dev сервер

```bash
npm run dev
```

### 3. Готово!

Vue 3 готов к использованию с HMR и всеми современными возможностями.

---

## 🚀 Быстрый старт

### Создание Vue приложения

В вашем шаблоне Twig создайте элемент с `id="app"`:

```twig
{# resources/views/my-page.twig #}
{% extends 'layout.twig' %}

{% block content %}
<div id="app">
    <example-component title="Привет, Vue!"></example-component>
    <counter :initial-value="5"></counter>
</div>
{% endblock %}
```

Vue автоматически монтируется на `#app` элемент.

---

## 📁 Структура проекта

```
resources/
├── js/
│   ├── app.js                    # Точка входа Vue
│   ├── components/               # Vue компоненты
│   │   ├── ExampleComponent.vue
│   │   ├── Counter.vue
│   │   └── Alert.vue
│   └── composables/              # Переиспользуемая логика
│       ├── useApi.js
│       └── useForm.js
└── css/
    └── app.css                   # Стили (Tailwind)
```

---

## 🧩 Компоненты

### Создание компонента

Создайте файл `.vue` в `resources/js/components/`:

```vue
<!-- resources/js/components/HelloWorld.vue -->
<template>
  <div class="bg-white p-6 rounded-lg shadow-md">
    <h2 class="text-2xl font-bold mb-4">{{ greeting }}</h2>
    <button 
      @click="count++" 
      class="bg-blue-500 text-white px-4 py-2 rounded"
    >
      Нажато: {{ count }}
    </button>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
  greeting: {
    type: String,
    default: 'Hello, World!'
  }
});

const count = ref(0);
</script>

<style scoped>
/* Локальные стили */
</style>
```

### Регистрация компонента

В `resources/js/app.js`:

```javascript
import { createApp } from 'vue';
import HelloWorld from './components/HelloWorld.vue';

const app = createApp({
  components: {
    HelloWorld,
  },
});

app.mount('#app');
```

### Использование в шаблоне

```twig
<div id="app">
    <hello-world greeting="Привет, Vue!"></hello-world>
</div>
```

---

## 🎯 Composition API

### Основы

```vue
<script setup>
import { ref, computed, watch, onMounted } from 'vue';

// Reactive state
const count = ref(0);
const message = ref('Hello');

// Computed property
const doubleCount = computed(() => count.value * 2);

// Watcher
watch(count, (newValue, oldValue) => {
  console.log(`Count changed from ${oldValue} to ${newValue}`);
});

// Lifecycle hook
onMounted(() => {
  console.log('Component mounted!');
});

// Methods
const increment = () => {
  count.value++;
};
</script>
```

---

## 🔧 Composables

Composables - это переиспользуемые функции с логикой (аналог Laravel traits для Vue).

### useApi - HTTP запросы

```javascript
import { useApi } from './composables/useApi';

const api = useApi('/api');

// GET request
const fetchUsers = async () => {
  try {
    const users = await api.get('/users');
    console.log(users);
  } catch (error) {
    console.error(api.error.value);
  }
};

// POST request
const createUser = async (userData) => {
  const result = await api.post('/users', userData);
  return result;
};

// PUT request
await api.put('/users/1', { name: 'John' });

// DELETE request
await api.delete('/users/1');

// Reactive state
console.log(api.loading.value);  // true/false
console.log(api.error.value);    // error message or null
console.log(api.data.value);     // response data
```

### useForm - Работа с формами

```vue
<script setup>
import { useForm, rules } from './composables/useForm';

const { form, errors, processing, submit, hasError, getError } = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
});

const submitForm = () => {
  submit(async (data) => {
    // Отправка данных на сервер
    const response = await fetch('/api/register', {
      method: 'POST',
      body: JSON.stringify(data),
    });
    
    if (!response.ok) {
      throw new Error('Registration failed');
    }
    
    return await response.json();
  });
};

// Validation
const validateName = () => {
  validate('name', [
    rules.required('Name is required'),
    rules.minLength(3, 'Name must be at least 3 characters'),
  ]);
};
</script>

<template>
  <form @submit.prevent="submitForm">
    <div>
      <input 
        v-model="form.name" 
        @blur="validateName"
        :class="{ 'border-red-500': hasError('name') }"
      />
      <span v-if="hasError('name')" class="text-red-500">
        {{ getError('name') }}
      </span>
    </div>
    
    <button :disabled="processing">
      {{ processing ? 'Отправка...' : 'Отправить' }}
    </button>
  </form>
</template>
```

---

## 📝 Готовые компоненты

### ExampleComponent

Демонстрационный компонент с кнопкой и счетчиком.

```vue
<example-component 
  title="Мой заголовок"
  initial-message="Начальное сообщение"
></example-component>
```

### Counter

Интерактивный счетчик с кнопками +/-.

```vue
<counter 
  :initial-value="10"
  :step="5"
  @update:count="handleUpdate"
  @reset="handleReset"
></counter>
```

### Alert

Уведомления разных типов.

```vue
<alert 
  type="success" 
  title="Успех!" 
  message="Операция выполнена"
  :dismissible="true"
  :auto-close="5000"
></alert>

<!-- Типы: success, error, warning, info -->
```

---

## 🎨 Props, Events, Slots

### Props (входные параметры)

```vue
<script setup>
const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  count: {
    type: Number,
    default: 0,
  },
  items: {
    type: Array,
    default: () => [],
  },
});
</script>
```

### Events (события)

```vue
<script setup>
const emit = defineEmits(['update', 'delete', 'custom-event']);

const handleClick = () => {
  emit('update', { id: 1, name: 'John' });
};
</script>

<template>
  <button @click="handleClick">Click</button>
</template>
```

Использование:

```vue
<my-component 
  @update="handleUpdate"
  @delete="handleDelete"
></my-component>
```

### Slots (слоты)

```vue
<!-- MyCard.vue -->
<template>
  <div class="card">
    <div class="card-header">
      <slot name="header">Default Header</slot>
    </div>
    <div class="card-body">
      <slot>Default Content</slot>
    </div>
    <div class="card-footer">
      <slot name="footer"></slot>
    </div>
  </div>
</template>
```

Использование:

```vue
<my-card>
  <template #header>
    <h2>Custom Header</h2>
  </template>
  
  <p>Main content goes here</p>
  
  <template #footer>
    <button>Close</button>
  </template>
</my-card>
```

---

## 🔄 Reactivity

### ref vs reactive

```javascript
import { ref, reactive } from 'vue';

// ref - для примитивов (доступ через .value)
const count = ref(0);
count.value++;  // Нужен .value

// reactive - для объектов
const user = reactive({
  name: 'John',
  age: 30
});
user.name = 'Jane';  // Без .value
```

### computed

```javascript
import { ref, computed } from 'vue';

const firstName = ref('John');
const lastName = ref('Doe');

// Readonly computed
const fullName = computed(() => {
  return `${firstName.value} ${lastName.value}`;
});

// Writable computed
const fullNameWritable = computed({
  get() {
    return `${firstName.value} ${lastName.value}`;
  },
  set(value) {
    const parts = value.split(' ');
    firstName.value = parts[0];
    lastName.value = parts[1];
  }
});
```

### watch

```javascript
import { ref, watch } from 'vue';

const count = ref(0);

// Simple watch
watch(count, (newValue, oldValue) => {
  console.log(`Changed from ${oldValue} to ${newValue}`);
});

// Watch multiple sources
watch([firstName, lastName], ([newFirst, newLast], [oldFirst, oldLast]) => {
  console.log('Name changed');
});

// Deep watch for objects
const user = reactive({ name: 'John', profile: { age: 30 } });

watch(user, (newValue, oldValue) => {
  console.log('User changed');
}, { deep: true });
```

---

## 🌐 Интеграция с PHP/Backend

### CSRF Token

CSRF токен автоматически добавляется в запросы через `useApi`:

```twig
{# В layout.twig #}
<meta name="csrf-token" content="{{ csrf_token() }}">
```

```javascript
// Автоматически включается в useApi
const api = useApi('/api');
await api.post('/users', { name: 'John' });
// X-CSRF-TOKEN заголовок добавляется автоматически
```

### Передача данных из PHP в Vue

#### Через props в шаблоне:

```twig
<div id="app">
    <user-profile 
      :user-id="{{ user.id }}"
      :user-name="'{{ user.name }}'"
    ></user-profile>
</div>
```

#### Через window объект:

```twig
<script>
    window.APP_DATA = {
        user: {{ user|json_encode|raw }},
        settings: {{ settings|json_encode|raw }},
    };
</script>
```

```javascript
// В Vue компоненте
const userData = window.APP_DATA?.user;
```

---

## 🎯 Best Practices

### 1. Именование компонентов

```
✅ PascalCase для файлов:    UserProfile.vue
✅ kebab-case в шаблонах:    <user-profile>
```

### 2. Композиция

```javascript
// ✅ Хорошо - переиспользуемая логика
import { useAuth } from './composables/useAuth';
import { useApi } from './composables/useApi';

const { user, logout } = useAuth();
const api = useApi();

// ❌ Плохо - всё в одном компоненте
```

### 3. Props validation

```javascript
// ✅ Хорошо
defineProps({
  userId: {
    type: Number,
    required: true,
  }
});

// ❌ Плохо
defineProps(['userId']);
```

### 4. Computed vs Methods

```javascript
// ✅ Используйте computed для вычисляемых значений
const total = computed(() => items.value.reduce((sum, item) => sum + item.price, 0));

// ❌ Не используйте methods для вычислений
const calculateTotal = () => items.value.reduce((sum, item) => sum + item.price, 0);
```

---

## 🧪 Примеры использования

### Форма регистрации

```vue
<template>
  <form @submit.prevent="register" class="max-w-md mx-auto">
    <div class="mb-4">
      <label class="block mb-2">Email</label>
      <input 
        v-model="form.email" 
        type="email"
        class="w-full px-4 py-2 border rounded"
        :class="{ 'border-red-500': hasError('email') }"
      />
      <span v-if="hasError('email')" class="text-red-500 text-sm">
        {{ getError('email') }}
      </span>
    </div>

    <div class="mb-4">
      <label class="block mb-2">Password</label>
      <input 
        v-model="form.password" 
        type="password"
        class="w-full px-4 py-2 border rounded"
      />
    </div>

    <button 
      type="submit"
      :disabled="processing"
      class="bg-blue-500 text-white px-6 py-2 rounded"
    >
      {{ processing ? 'Регистрация...' : 'Зарегистрироваться' }}
    </button>
  </form>
</template>

<script setup>
import { useForm } from './composables/useForm';
import { useApi } from './composables/useApi';

const { form, errors, processing, submit, hasError, getError } = useForm({
  email: '',
  password: '',
});

const api = useApi();

const register = () => {
  submit(async (data) => {
    return await api.post('/register', data);
  });
};
</script>
```

---

## 🐛 Troubleshooting

### Компоненты не рендерятся

1. Проверьте что `#app` элемент существует
2. Убедитесь что компонент зарегистрирован в `app.js`
3. Проверьте консоль браузера на ошибки

### HMR не работает

1. Убедитесь что `npm run dev` запущен
2. Проверьте настройки Vite в `vite.config.js`
3. См. [ViteSetup.md](./ViteSetup.md)

### Ошибки компиляции `.vue` файлов

1. Проверьте что установлен `@vitejs/plugin-vue`
2. Убедитесь что плагин подключен в `vite.config.js`

---

## 📚 Дополнительные ресурсы

- [Официальная документация Vue 3](https://vuejs.org/)
- [Composition API Guide](https://vuejs.org/guide/extras/composition-api-faq.html)
- [Vue 3 Examples](https://vuejs.org/examples/)
- [Vite + Vue](https://vitejs.dev/guide/features.html#vue)

---

## 🎉 Что дальше?

1. **Vue Router** - для SPA навигации
2. **Pinia** - state management (замена Vuex)
3. **VueUse** - коллекция готовых composables
4. **Tailwind UI** - готовые компоненты на Tailwind

Приятной разработки! 🚀

