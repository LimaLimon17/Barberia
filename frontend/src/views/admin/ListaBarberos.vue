<template>
  <div class="lista animate-fade-in">
    <div class="lista__header">
      <h1 class="lista__title">Gestión de Barberos</h1>
      <span class="lista__count" v-if="barberos.length">
        {{ barberos.length }} barbero{{ barberos.length !== 1 ? 's' : '' }}
      </span>
    </div>
    <!-- Loading -->
    <div v-if="cargando" class="lista__loading">
      <div class="lista__spinner"></div>
      <p>Cargando barberos...</p>
    </div>
    <!-- Error -->
    <AlertMessage v-if="error" :mensaje="error" tipo="error" />
    <!-- Tabla de barberos -->
    <div v-if="!cargando && barberos.length" class="lista__table-wrapper glass-card">
      <table class="lista__table">
        <thead>
          <tr>
            <th>Barbero</th>
            <th>Correo</th>
            <th>Fecha Ingreso</th>
            <th>Antigüedad</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="barbero in barberos"
            :key="barbero.id_barbero"
            class="lista__row"
          >
            <td>
              <div class="lista__barbero-info">
                <div class="lista__avatar">
                  {{ barbero.nombre_completo.charAt(0) }}
                </div>
                <span>{{ barbero.nombre_completo }}</span>
              </div>
            </td>
            <td>{{ barbero.correo }}</td>
            <td>{{ formatearFechaCorta(barbero.fecha_ingreso) }}</td>
            <td>
              <span class="lista__antiguedad">{{ barbero.antiguedad_dias }} días</span>
            </td>
            <td>
              <span :class="barbero.estado_activo ? 'badge-active' : 'badge-inactive'">
                ● {{ barbero.estado }}
              </span>
            </td>
            <td>
              <div class="lista__actions">
                <router-link
                  :to="`/admin/barberos/${barbero.id_barbero}`"
                  class="btn-secondary lista__btn"
                  title="Ver perfil"
                >
                  👁️ Ver
                </router-link>
                <router-link
                  :to="`/admin/barberos/${barbero.id_barbero}/editar`"
                  class="btn-primary lista__btn"
                  title="Editar"
                >
                  ✏️ Editar
                </router-link>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <!-- Sin barberos -->
    <div v-if="!cargando && !barberos.length && !error" class="lista__empty glass-card">
      <p>No hay barberos registrados en el sistema.</p>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'
import barberoService from '../../services/barberoService.js'
import AlertMessage from '../../components/common/AlertMessage.vue'
import { formatearFechaCorta } from '../../utils/helpers.js'
const barberos = ref([])
const cargando = ref(true)
const error = ref('')
onMounted(async () => {
  try {
    const data = await barberoService.getBarberos()
    barberos.value = data.barberos
  } catch (err) {
    error.value = err.response?.data?.mensaje || 'Error al cargar los barberos'
  } finally {
    cargando.value = false
  }
})
</script>
<style scoped>
.lista {
  max-width: 1100px;
}
.lista__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
}
.lista__title {
  font-family: var(--font-heading);
  font-size: 1.5rem;
  font-weight: 700;
}
.lista__count {
  font-size: 0.8125rem;
  color: var(--color-text-secondary);
  background: var(--color-bg-card);
  padding: 0.375rem 0.875rem;
  border-radius: 9999px;
  border: 1px solid var(--color-border);
}
.lista__loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  padding: 3rem;
  color: var(--color-text-muted);
}
.lista__spinner {
  width: 32px;
  height: 32px;
  border: 3px solid var(--color-border);
  border-top-color: var(--color-gold-400);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}
.lista__table-wrapper {
  padding: 0;
  overflow-x: auto;
}
.lista__table {
  width: 100%;
  border-collapse: collapse;
}
.lista__table th {
  text-align: left;
  padding: 0.875rem 1.25rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--color-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-bottom: 1px solid var(--color-border);
  background: var(--color-bg-secondary);
}
.lista__table th:first-child {
  border-radius: var(--radius-xl) 0 0 0;
}
.lista__table th:last-child {
  border-radius: 0 var(--radius-xl) 0 0;
}
.lista__table td {
  padding: 0.875rem 1.25rem;
  font-size: 0.875rem;
  color: var(--color-text-primary);
  border-bottom: 1px solid var(--color-border);
}
.lista__row {
  transition: background 0.2s ease;
}
.lista__row:hover {
  background: var(--color-bg-hover);
}
.lista__row:last-child td {
  border-bottom: none;
}
.lista__barbero-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}
.lista__avatar {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--color-gold-400), var(--color-gold-500));
  color: var(--color-bg-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-heading);
  font-weight: 700;
  font-size: 0.8125rem;
  flex-shrink: 0;
}
.lista__antiguedad {
  color: var(--color-gold-400);
  font-weight: 600;
}
.lista__actions {
  display: flex;
  gap: 0.5rem;
}
.lista__btn {
  padding: 0.4rem 0.75rem;
  font-size: 0.8125rem;
}
.lista__empty {
  text-align: center;
  padding: 3rem;
  color: var(--color-text-muted);
}
</style>
