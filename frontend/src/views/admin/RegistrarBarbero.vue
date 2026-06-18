<template>
  <div class="registrar animate-fade-in">

    <!-- Encabezado -->
    <div class="registrar__header">
      <div>
        <h1 class="registrar__title">Registrar <span class="gold-text">nuevo barbero</span></h1>
        <p class="registrar__subtitle">Complete los datos y asigne el horario inicial obligatorio</p>
      </div>
      <button @click="$router.push('/admin/barberos')" class="btn-secondary">
        ← Volver
      </button>
    </div>

    <!-- Alerta error -->
    <div v-if="errorGeneral" class="registrar__alerta registrar__alerta--error">
      ⚠️ {{ errorGeneral }}
    </div>

    <!-- Alerta éxito -->
    <div v-if="exitoso" class="registrar__alerta registrar__alerta--exito">
      ✅ Barbero registrado correctamente. Redirigiendo...
    </div>

    <form @submit.prevent="registrar" class="registrar__form">

      <!-- SECCIÓN: Datos personales -->
      <div class="glass-card registrar__seccion">
        <h2 class="registrar__seccion-titulo">👤 Datos personales</h2>

        <div class="registrar__grid">
          <div class="registrar__campo">
            <label class="label">Primer nombre *</label>
            <input
              v-model="form.nombre1"
              type="text"
              placeholder="Ej: Carlos"
              class="input-field"
              :class="{ 'input-field--error': errores.nombre1 }"
            />
            <span v-if="errores.nombre1" class="registrar__error">{{ errores.nombre1 }}</span>
          </div>

          <div class="registrar__campo">
            <label class="label">Segundo nombre</label>
            <input
              v-model="form.nombre2"
              type="text"
              placeholder="Ej: Andres"
              class="input-field"
            />
          </div>

          <div class="registrar__campo">
            <label class="label">Primer apellido *</label>
            <input
              v-model="form.apellido1"
              type="text"
              placeholder="Ej: Mamani"
              class="input-field"
              :class="{ 'input-field--error': errores.apellido1 }"
            />
            <span v-if="errores.apellido1" class="registrar__error">{{ errores.apellido1 }}</span>
          </div>

          <div class="registrar__campo">
            <label class="label">Segundo apellido</label>
            <input
              v-model="form.apellido2"
              type="text"
              placeholder="Ej: Quispe"
              class="input-field"
            />
          </div>

          <div class="registrar__campo">
            <label class="label">Correo electrónico *</label>
            <input
              v-model="form.correo"
              type="email"
              placeholder="correo@ejemplo.com"
              class="input-field"
              :class="{ 'input-field--error': errores.correo }"
            />
            <span v-if="errores.correo" class="registrar__error">{{ errores.correo }}</span>
          </div>

          <div class="registrar__campo">
            <label class="label">Contraseña *</label>
            <input
              v-model="form.contrasena"
              type="password"
              placeholder="Mínimo 6 caracteres"
              class="input-field"
              :class="{ 'input-field--error': errores.contrasena }"
            />
            <span v-if="errores.contrasena" class="registrar__error">{{ errores.contrasena }}</span>
          </div>

          <div class="registrar__campo">
            <label class="label">Fecha de ingreso *</label>
            <input
              v-model="form.fecha_ingreso"
              type="date"
              :max="hoy"
              class="input-field"
              :class="{ 'input-field--error': errores.fecha_ingreso }"
            />
            <span v-if="errores.fecha_ingreso" class="registrar__error">{{ errores.fecha_ingreso }}</span>
          </div>
        </div>
      </div>

      <!-- SECCIÓN: Horario inicial -->
      <div class="glass-card registrar__seccion">
        <div class="registrar__seccion-header">
          <h2 class="registrar__seccion-titulo">🗓️ Horario inicial</h2>
          <p class="registrar__seccion-desc">
            Cada día laboral activo debe tener mínimo <strong>8 horas efectivas</strong> (se descuenta 1h de almuerzo automáticamente).
          </p>
        </div>

        <span v-if="errores.dias" class="registrar__error" style="display:block; margin-bottom: 1rem;">
          {{ errores.dias }}
        </span>

        <div class="registrar__dias">
          <div
            v-for="dia in diasSemana"
            :key="dia.key"
            class="registrar__dia"
            :class="{
              'registrar__dia--activo': dia.activo,
              'registrar__dia--inactivo': !dia.activo
            }"
          >
            <!-- Cabecera del día -->
            <div class="registrar__dia-header">
              <label class="registrar__dia-toggle">
                <input type="checkbox" v-model="dia.activo" />
                <span class="registrar__dia-nombre">{{ dia.nombre }}</span>
              </label>
              <span
                v-if="dia.activo && !dia.dia_descanso"
                class="registrar__dia-horas"
                :class="horasValidas(dia) ? 'registrar__dia-horas--ok' : 'registrar__dia-horas--mal'"
              >
                {{ calcularHoras(dia) }}
              </span>
              <span v-if="dia.activo && dia.dia_descanso" class="registrar__dia-badge">
                Descanso
              </span>
            </div>

            <!-- Horarios (solo si está activo) -->
            <div v-if="dia.activo" class="registrar__dia-body">
              <div class="registrar__dia-horas-inputs">
                <div class="registrar__campo-pequeño">
                  <label class="label">Entrada</label>
                  <input
                    v-model="dia.hora_entrada"
                    type="time"
                    class="input-field"
                    :disabled="dia.dia_descanso"
                  />
                </div>
                <span class="registrar__dia-separador">→</span>
                <div class="registrar__campo-pequeño">
                  <label class="label">Salida</label>
                  <input
                    v-model="dia.hora_salida"
                    type="time"
                    class="input-field"
                    :disabled="dia.dia_descanso"
                  />
                </div>
              </div>

              <label class="registrar__descanso-toggle">
                <input type="checkbox" v-model="dia.dia_descanso" />
                <span>Marcar como día de descanso</span>
              </label>
            </div>

            <!-- Mensaje si está inactivo -->
            <div v-else class="registrar__dia-inactivo-msg">
              No trabaja este día
            </div>
          </div>
        </div>
      </div>

      <!-- Botones finales -->
      <div class="registrar__footer">
        <button
          type="button"
          @click="$router.push('/admin/barberos')"
          class="btn-secondary"
        >
          Cancelar
        </button>
        <button
          type="submit"
          :disabled="cargando"
          class="btn-primary"
        >
          {{ cargando ? 'Registrando...' : '✅ Registrar barbero' }}
        </button>
      </div>

    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import barberoService from '../../services/barberoService.js'

const router       = useRouter()
const cargando     = ref(false)
const errorGeneral = ref('')
const exitoso      = ref(false)
const errores      = ref({})
const hoy          = new Date().toISOString().split('T')[0]

const form = ref({
  nombre1:       '',
  nombre2:       '',
  apellido1:     '',
  apellido2:     '',
  correo:        '',
  contrasena:    '',
  fecha_ingreso: '',
})

const diasSemana = ref([
  { key: 'Lunes',     nombre: 'Lunes',     activo: true,  hora_entrada: '09:00', hora_salida: '19:00', dia_descanso: false },
  { key: 'Martes',    nombre: 'Martes',    activo: true,  hora_entrada: '09:00', hora_salida: '19:00', dia_descanso: false },
  { key: 'Miércoles', nombre: 'Miércoles', activo: true,  hora_entrada: '09:00', hora_salida: '19:00', dia_descanso: false },
  { key: 'Jueves',    nombre: 'Jueves',    activo: true,  hora_entrada: '09:00', hora_salida: '19:00', dia_descanso:
