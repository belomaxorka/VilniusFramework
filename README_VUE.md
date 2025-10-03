# Vue 3 в вашем фреймворке 🚀

Полноценная интеграция Vue 3 с Composition API, как в Laravel!

## ⚡ Быстрый старт

### 1. Установка
```bash
npm install
```

### 2. Запуск
```bash
npm run dev
```

### 3. Использование
```twig
<div id="app">
    <example-component title="Hello!"></example-component>
    <counter :initial-value="10"></counter>
</div>
```

## 📚 Документация

- **[VUE_QUICKSTART.md](VUE_QUICKSTART.md)** - Быстрый старт за 3 минуты
- **[VUE_INTEGRATION.md](VUE_INTEGRATION.md)** - Полный обзор интеграции
- **[docs/Vue.md](docs/Vue.md)** - Детальная документация (500+ строк)

## 🧩 Что включено

### Компоненты
- ✅ `ExampleComponent.vue` - демо компонент
- ✅ `Counter.vue` - интерактивный счетчик
- ✅ `Alert.vue` - система уведомлений (4 типа)

### Composables
- ✅ `useApi` - HTTP запросы с CSRF
- ✅ `useForm` - управление формами + валидация

### Фичи
- ✅ Single File Components (.vue)
- ✅ Composition API
- ✅ Hot Module Replacement
- ✅ Scoped Styles
- ✅ Tailwind CSS интеграция
- ✅ CSRF Protection
- ✅ TypeScript ready

## 🎯 Примеры

### Компонент
```vue
<template>
  <div>
    <h2>{{ message }}</h2>
    <button @click="count++">{{ count }}</button>
  </div>
</template>

<script setup>
import { ref } from 'vue';
const message = ref('Hello Vue!');
const count = ref(0);
</script>
```

### API запрос
```javascript
import { useApi } from './composables/useApi';

const api = useApi('/api');
const users = await api.get('/users');
```

### Форма
```javascript
import { useForm } from './composables/useForm';

const { form, submit } = useForm({ 
  email: '' 
});

submit(async (data) => {
  await api.post('/subscribe', data);
});
```

## 📖 Детальные гайды

- [Создание компонентов](docs/Vue.md#создание-компонента)
- [Использование composables](docs/Vue.md#composables)
- [Интеграция с PHP](docs/Vue.md#интеграция-с-php)
- [Props, Events, Slots](docs/Vue.md#props-events-slots)
- [Best Practices](docs/Vue.md#best-practices)

## 🎉 Готово!

Все настроено и готово к работе! Приятной разработки! 💜

