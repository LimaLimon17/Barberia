<template>
  <div v-if="message" :class="['alert-message', type]">
    {{ message }}
  </div>
</template>

<script setup>
defineProps({
  message: { type: String, default: "" },
  type: { type: String, default: "info" },
});
</script>

<style scoped>
.alert-message {
  padding: 12px 14px;
  border-radius: 10px;
  margin: 12px 0;
  font-weight: 600;
}
.info { background: #eef5ff; color: #174ea6; }
.success { background: #ecfdf3; color: #027a48; }
.error { background: #fef3f2; color: #b42318; }
.warning { background: #fffaeb; color: #b54708; }
</style>
