<template>
  <header class="navbar">
    <div class="navbar__left">
      <h2 class="navbar__title">{{ pageTitle }}</h2>
    </div>
    <div class="navbar__right">
      <div class="navbar__user">
        <div class="navbar__avatar">
          {{ iniciales }}
        </div>
        <div class="navbar__info">
          <span class="navbar__name">{{ authStore.nombreCompleto }}</span>
          <span class="navbar__role">{{ authStore.rol }}</span>
        </div>
      </div>
      <button id="btn-logout" class="navbar__logout" @click="cerrarSesion" title="Cerrar sesión">
        🚪
      </button>
    </div>
  </header>
</template>
<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth.js'
const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const pageTitle = computed(() => route.meta.title || 'Dashboard')
const iniciales = computed(() => {
  const u = authStore.usuario
  if (!u) return '?'
  const n1 = u.nombre1 ? u.nombre1.charAt(0) : ''
  const a1 = u.apellido1 ? u.apellido1.charAt(0) : ''
  return (n1 + a1).toUpperCase()
})
async function cerrarSesion() {
  await authStore.logout()
  router.push({ name: 'Login' })
}
</script>
<style scoped>
.navbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 2rem;
  background: var(--color-bg-secondary);
  border-bottom: 1px solid var(--color-border);
  position: sticky;
  top: 0;
  z-index: 40;
  backdrop-filter: blur(12px);
}
.navbar__title {
  font-family: var(--font-heading);
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--color-text-primary);
}
.navbar__right {
  display: flex;
  align-items: center;
  gap: 1rem;
}
.navbar__user {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}
.navbar__avatar {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--color-gold-400), var(--color-gold-500));
  color: var(--color-bg-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-heading);
  font-weight: 700;
  font-size: 0.875rem;
}
.navbar__info {
  display: flex;
  flex-direction: column;
}
.navbar__name {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-text-primary);
  line-height: 1.2;
}
.navbar__role {
  font-size: 0.75rem;
  color: var(--color-gold-400);
  text-transform: capitalize;
}
.navbar__logout {
  background: none;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  padding: 0.5rem;
  font-size: 1.125rem;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}
.navbar__logout:hover {
  background: var(--color-bg-hover);
  border-color: var(--color-error);
}
@media (max-width: 768px) {
  .navbar {
    padding: 0.75rem 1rem;
  }
  .navbar__info {
    display: none;
  }
}
</style>
