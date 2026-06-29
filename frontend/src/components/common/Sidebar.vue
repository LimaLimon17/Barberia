<template>
  <aside class="sidebar">
    <div class="sidebar__header">
      <div class="sidebar__logo">
        <span class="sidebar__logo-icon">✂️</span>
        <div>
          <h1 class="sidebar__brand">Barbería</h1>
          <span class="sidebar__role-badge">{{ titulo }}</span>
        </div>
      </div>
    </div>
    <nav class="sidebar__nav">
      <router-link
        v-for="item in items"
        :key="item.ruta"
        :to="item.ruta"
        class="sidebar__link"
        :class="{ 'sidebar__link--active': isActive(item.ruta) }"
      >
        <span class="sidebar__link-icon">{{ item.icono }}</span>
        <span class="sidebar__link-text">{{ item.nombre }}</span>
      </router-link>
    </nav>
    <div class="sidebar__footer">
      <p class="sidebar__version">v1.0.0</p>
    </div>
  </aside>
</template>
<script setup>
import { useRoute } from 'vue-router'
const props = defineProps({
  items: {
    type: Array,
    required: true,
  },
  titulo: {
    type: String,
    default: 'Panel',
  },
})
const route = useRoute()
function isActive(ruta) {
  return route.path.startsWith(ruta)
}
</script>
<style scoped>
.sidebar {
  position: fixed;
  top: 0;
  left: 0;
  width: 260px;
  height: 100vh;
  background: var(--color-bg-secondary);
  border-right: 1px solid var(--color-border);
  display: flex;
  flex-direction: column;
  z-index: 50;
  animation: slideInLeft 0.4s ease-out;
}
.sidebar__header {
  padding: 1.5rem;
  border-bottom: 1px solid var(--color-border);
}
.sidebar__logo {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}
.sidebar__logo-icon {
  font-size: 2rem;
}
.sidebar__brand {
  font-family: var(--font-heading);
  font-size: 1.375rem;
  font-weight: 800;
  background: linear-gradient(135deg, var(--color-gold-400), var(--color-gold-300));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  line-height: 1.2;
}
.sidebar__role-badge {
  font-size: 0.6875rem;
  font-weight: 600;
  color: var(--color-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.08em;
}
.sidebar__nav {
  flex: 1;
  padding: 1rem 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  overflow-y: auto;
}
.sidebar__link {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  border-radius: var(--radius-md);
  color: var(--color-text-secondary);
  text-decoration: none;
  font-size: 0.9375rem;
  font-weight: 500;
  transition: all 0.2s ease;
}
.sidebar__link:hover {
  background: var(--color-bg-hover);
  color: var(--color-text-primary);
}
.sidebar__link--active {
  background: rgba(232, 184, 17, 0.1);
  color: var(--color-gold-400);
  font-weight: 600;
}
.sidebar__link--active::before {
  content: '';
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  width: 3px;
  height: 24px;
  background: var(--color-gold-400);
  border-radius: 0 3px 3px 0;
}
.sidebar__link-icon {
  font-size: 1.25rem;
  width: 28px;
  text-align: center;
}
.sidebar__footer {
  padding: 1rem 1.5rem;
  border-top: 1px solid var(--color-border);
}
.sidebar__version {
  font-size: 0.75rem;
  color: var(--color-text-muted);
}
@media (max-width: 768px) {
  .sidebar {
    transform: translateX(-100%);
  }
}
</style>
