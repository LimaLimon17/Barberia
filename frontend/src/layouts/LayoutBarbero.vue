<template>
  <div class="layout-panel">
    <Sidebar :items="menuItems" :titulo="'Barbero'" />
    <div class="layout-panel__main">
      <Navbar />
      <main class="layout-panel__content">
        <router-view />
      </main>
    </div>
  </div>
</template>
<script setup>
import Navbar from '../components/common/Navbar.vue'
import Sidebar from '../components/common/Sidebar.vue'
const menuItems = [
  {
    nombre: 'Mi Agenda',
    ruta: '/barbero/dashboard',
    icono: '📅',
  },
  {
    nombre: 'Mi Perfil',
    ruta: '/barbero/perfil',
    icono: '👤',
  },
  {
    nombre: 'Reportes y Comisiones',
    ruta: '/barbero/reportes',
    icono: '💰',
  },
]
</script>
<style scoped>
.layout-panel {
  display: flex;
  min-height: 100vh;
  background: var(--color-bg-primary);
}
.layout-panel__main {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
  margin-left: 260px;
}
.layout-panel__content {
  flex: 1;
  padding: 2rem;
  animation: fadeIn 0.4s ease-out;
}
@media (max-width: 768px) {
  .layout-panel__main {
    margin-left: 0;
  }
  .layout-panel__content {
    padding: 1rem;
  }
}
</style>