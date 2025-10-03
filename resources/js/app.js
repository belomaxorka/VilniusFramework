// Import CSS
import '../css/app.css';

// Import Vue
import { createApp } from 'vue';

// Import components
import UserDashboard from './components/UserDashboard.vue';

// Create Vue app
const app = createApp({});

// Register components globally
app.component('UserDashboard', UserDashboard);

// Global configuration
app.config.errorHandler = (err, instance, info) => {
  console.error('Vue Error:', err);
  console.error('Component:', instance);
  console.error('Info:', info);
};

// Mount Vue app to #app element
app.mount('#app');

console.log('✅ Vilnius Framework with Vue 3 Loaded');
