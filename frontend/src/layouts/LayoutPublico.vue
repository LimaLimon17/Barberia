<template>
  <div class="layout-publico">
    <router-view />
  </div>
</template>
<script setup>
// LayoutPublico - Diseño para páginas públicas (Login)
// Sin sidebar ni navbar, diseño de pantalla completa
</script>
<style scoped>
.layout-publico {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--color-bg-primary);
  position: relative;
  overflow: hidden;
}
/* Fondo con efecto decorativo sutil */
.layout-publico::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -30%;
  width: 600px;
  height: 600px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(232, 184, 17, 0.06) 0%, transparent 70%);
  pointer-events: none;
}
.layout-publico::after {
  content: '';
  position: absolute;
  bottom: -40%;
  left: -20%;
  width: 500px;
  height: 500px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(232, 184, 17, 0.04) 0%, transparent 70%);
  pointer-events: none;
}
</style>