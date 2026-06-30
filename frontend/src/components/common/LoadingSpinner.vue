<template>
  <div v-if="show" class="loading">Cargando...</div>
</template>

<script setup>
defineProps({
  show: { type: Boolean, default: false },
});
</script>

<style scoped>
.loading {
  padding: 14px;
  border-radius: 10px;
  background: #f8fafc;
  color: #475467;
}
</style>
