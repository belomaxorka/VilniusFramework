// Import CSS
import '../css/app.css';

// Import Vue
import { createApp } from 'vue';

// Import components
import WelcomeCounter from './components/WelcomeCounter.vue';
import UsersList from './components/UsersList.vue';

// Функция для монтирования Vue компонентов на конкретные элементы
function mountComponent(selector, component) {
  const elements = document.querySelectorAll(selector);
  
  elements.forEach((element) => {
    // Получаем props из атрибутов элемента
    const props = {};
    for (const attr of element.attributes) {
      if (attr.name.startsWith(':') || attr.name.startsWith('v-bind:')) {
        const propName = attr.name.replace(/^(:|v-bind:)/, '');
        // Пытаемся распарсить значение как JSON
        try {
          props[propName] = JSON.parse(attr.value);
        } catch {
          props[propName] = attr.value;
        }
      }
    }
    
    // Создаём отдельное Vue приложение для каждого компонента
    const app = createApp(component, props);
    
    app.config.errorHandler = (err, instance, info) => {
      console.error('Vue Error:', err);
      console.error('Component:', instance);
      console.error('Info:', info);
    };
    
    app.mount(element);
  });
}

// Монтируем компоненты после загрузки DOM
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    mountComponent('welcome-counter', WelcomeCounter);
    mountComponent('users-list', UsersList);
    console.log('✅ Vilnius Framework with Vue 3 Loaded');
  });
} else {
  mountComponent('welcome-counter', WelcomeCounter);
  mountComponent('users-list', UsersList);
  console.log('✅ Vilnius Framework with Vue 3 Loaded');
}
