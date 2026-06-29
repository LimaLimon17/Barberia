<template>
  <router-view v-slot="{ Component }">
    <transition name="page" mode="out-in">
      <component :is="Component" />
    </transition>
  </router-view>
</template>
<script setup>
// App.vue - Componente raíz con transiciones de página
</script>
