# 🚀 Vue 3 Quick Start

Начните использовать Vue 3 в вашем фреймворке за **3 минуты**!

---

## 📦 Шаг 1: Установка (1 мин)

```bash
npm install
```

Это установит:
- Vue 3
- @vitejs/plugin-vue
- @vue/compiler-sfc

---

## 🔥 Шаг 2: Запуск dev сервера (30 сек)

```bash
npm run dev
```

Vite запустится на порту 5173 с HMR.

---

## ✨ Шаг 3: Используйте в шаблоне! (1 мин)

Создайте или откройте любой `.twig` шаблон:

```twig
{% extends 'layout.twig' %}

{% block content %}
<div id="app">
    <!-- Демо компонент -->
    <example-component 
      title="Привет, Vue!" 
      initial-message="Это работает! 🎉"
    ></example-component>
    
    <!-- Счетчик -->
    <counter :initial-value="0"></counter>
    
    <!-- Уведомление -->
    <alert 
      type="success" 
      message="Vue 3 успешно интегрирован!"
    ></alert>
</div>
{% endblock %}
```

---

## ✅ Готово!

Откройте страницу в браузере — Vue работает с HMR! 🚀

---

## 🎯 Что дальше?

### Создайте свой компонент

```bash
# Создайте файл
touch resources/js/components/MyComponent.vue
```

```vue
<!-- resources/js/components/MyComponent.vue -->
<template>
  <div class="bg-white p-6 rounded-lg shadow">
    <h2>{{ message }}</h2>
    <button @click="count++" class="bg-blue-500 text-white px-4 py-2 rounded">
      Clicks: {{ count }}
    </button>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const message = ref('Мой первый компонент!');
const count = ref(0);
</script>
```

### Зарегистрируйте компонент

```javascript
// resources/js/app.js
import MyComponent from './components/MyComponent.vue';

const app = createApp({
  components: {
    ExampleComponent,
    Counter,
    MyComponent, // ← Добавьте сюда
  },
});
```

### Используйте в шаблоне

```twig
<div id="app">
    <my-component></my-component>
</div>
```

---

## 📚 Полезные ссылки

- **Полная документация:** [docs/Vue.md](docs/Vue.md)
- **Примеры:** [resources/views/vue-example.twig](resources/views/vue-example.twig)
- **Composables:** `resources/js/composables/`
- **Готовые компоненты:** `resources/js/components/`

---

## 🔧 Готовые Composables

### HTTP запросы

```javascript
import { useApi } from './composables/useApi';

const api = useApi('/api');
const users = await api.get('/users');
```

### Формы

```javascript
import { useForm } from './composables/useForm';

const { form, submit } = useForm({ email: '' });

submit(async (data) => {
  await api.post('/subscribe', data);
});
```

---

## 💡 Советы

1. **HMR работает автоматически** - изменения в `.vue` применяются мгновенно
2. **Tailwind CSS включен** - используйте утилиты прямо в компонентах
3. **CSRF токен** добавляется автоматически в `useApi`
4. **DevTools работают** - установите Vue DevTools для Chrome/Firefox

---

## 🎉 Готово!

Теперь у вас полноценный Vue 3 как в Laravel! 🚀

**Приятной разработки!** 💜

