<template>
  <div class="lamplight-raiz">

    <!-- ══ NAVBAR ══════════════════════════════════════════════ -->
    <nav class="lamplight-nav">
      <div class="lamplight-nav-inner">
        <div @click="seccionActiva = 'inicio'" class="lamplight-brand">
          <div class="lamplight-logo-container">
            <img src="../../assets/logo.png" alt="The Lamplight Logo" class="lamplight-logo-img">
            <div class="lamplight-brand-text">
          <span class="lamplight-brand-name">The Lamplight</span>
          <span class="lamplight-brand-sub">Barber Shop · La Paz</span>
        </div>
        </div>
          </div>

        <div class="lamplight-menu">
          <button
            v-for="menu in opcionesMenu"
            :key="menu.id"
            @click="seccionActiva = menu.id"
            :class="['lamplight-menu-btn', seccionActiva === menu.id ? 'activo' : '']">
            {{ menu.label }}
          </button>
        </div>
      </div>
    </nav>

    <!-- ══ CONTENIDO PRINCIPAL ══════════════════════════════════ -->
    <main class="lamplight-main">
      <transition name="fade-seccion" mode="out-in">

        <!-- INICIO ─────────────────────────────────────────── -->
        <section v-if="seccionActiva === 'inicio'" key="inicio" class="seccion-inicio">
          <div class="inicio-inner">
            <p class="eyebrow">La Paz · Bolivia · Desde 2024</p>

            <h1 class="titulo-hero">The<br>Lamplight</h1>

            <div class="separador-ornamental">
              <span class="linea-bronce"></span>
              <span class="texto-ornamental">Corte · Barba · Tradición</span>
              <span class="linea-bronce"></span>
            </div>

            <p class="descripcion-hero">
              Revivimos la era dorada de las barberías. Técnicas clásicas y modernas
              de corte para el confort del caballero moderno.
            </p>

            <div class="inicio-ctas">
              <router-link to="/reservar" class="cta-primario">
                Agenda tu Cita
              </router-link>
              <button @click="seccionActiva = 'servicios'" class="cta-secundario">
                Ver servicios →
              </button>
            </div>

            
          </div>
        </section>

        <!-- SERVICIOS ──────────────────────────────────────── -->
        <section v-else-if="seccionActiva === 'servicios'" key="servicios" class="seccion-generica">
          <div class="seccion-header">
            <p class="eyebrow">Lo que hacemos</p>
            <h2 class="titulo-seccion">Servicios</h2>
          </div>

          <!-- Filtros -->
          <div class="filtros-wrap">
            <button
              @click="categoriaFiltro = ''"
              :class="['filtro-btn', !categoriaFiltro ? 'activo' : '']">
              Todo
            </button>
            <button
              v-for="cat in barberiaStore.categorias"
              :key="cat.IdCategoria"
              @click="categoriaFiltro = cat.IdCategoria"
              :class="['filtro-btn', categoriaFiltro === cat.IdCategoria ? 'activo' : '']">
              {{ cat.Nombre }}
            </button>
          </div>

          <!-- Cargando -->
          <div v-if="barberiaStore.cargando" class="servicios-grid">
            <div v-for="n in 6" :key="n" class="skeleton-card">
              <div class="skeleton-img"></div>
              <div class="skeleton-body">
                <div class="skeleton-line ancho"></div>
                <div class="skeleton-line corto"></div>
              </div>
            </div>
          </div>

          <!-- Grid servicios -->
          <div v-else class="servicios-grid">
            <article
              v-for="servicio in serviciosFiltrados"
              :key="servicio.IdServicio"
              class="servicio-card">
              <div class="servicio-img-wrap">
                <img
                  :src="servicio.FotoURL || `https://placehold.co/480x220/1a1a2e/c9a84c?text=${encodeURIComponent(servicio.Nombre)}`"
                  :alt="servicio.Nombre"
                  class="servicio-img">
                <span class="badge-duracion">{{ servicio.DuracionMinutos }} min</span>
              </div>
              <div class="servicio-info">
                <h3 class="servicio-nombre">{{ servicio.Nombre }}</h3>
                <span class="servicio-precio">
                  {{ Number(servicio.Precio).toFixed(0) }}<small> Bs.</small>
                </span>
              </div>
            </article>
          </div>

          <p v-if="!barberiaStore.cargando && serviciosFiltrados.length === 0" class="vacio-msg">
            No hay servicios en esta categoría por el momento.
          </p>
        </section>

        <!-- BARBEROS ───────────────────────────────────────── -->
        <section v-else-if="seccionActiva === 'barberos'" key="barberos" class="seccion-generica">
          <div class="seccion-header">
            <p class="eyebrow">El equipo</p>
            <h2 class="titulo-seccion">Los Maestros</h2>
            <p class="subtitulo-seccion">
              Artesanos del cabello formados en las técnicas más exigentes de la barbería clásica.
            </p>
          </div>

          <div v-if="barberiaStore.cargando" class="barberos-grid">
            <div v-for="n in 3" :key="n" class="skeleton-barbero">
              <div class="skeleton-foto"></div>
              <div class="skeleton-texto">
                <div class="skeleton-line ancho"></div>
                <div class="skeleton-line corto"></div>
                <div class="skeleton-line ancho"></div>
              </div>
            </div>
          </div>

          <div v-else class="barberos-grid">
            <article
              v-for="(barbero, idx) in barberosConDatosLocales"
              :key="barbero.IdBarbero"
              class="barbero-card">
              <div class="barbero-numero">{{ String(idx + 1).padStart(2, '0') }}</div>
              <div class="barbero-foto-wrap">
                <img :src="barbero.fotoLocal" :alt="barbero.Nombre1" class="barbero-foto">
              </div>
              <div class="barbero-info">
                <span class="barbero-rol">Especialista Tradicional</span>
                <h3 class="barbero-nombre">{{ barbero.Nombre1 }} {{ barbero.Apellido1 }}</h3>
                <p class="barbero-bio">{{ barbero.bioLocal }}</p>
              </div>
            </article>
          </div>
        </section>

        <!-- CONTACTO ───────────────────────────────────────── -->
        <section v-else-if="seccionActiva === 'contacto'" key="contacto" class="seccion-generica">
          <div class="seccion-header">
            <p class="eyebrow">Dónde estamos</p>
            <h2 class="titulo-seccion">Contacto</h2>
          </div>

          <div class="contacto-grid">
            <div class="contacto-datos">
              <div v-for="(item, i) in datosContacto" :key="i" class="dato-fila">
                <span class="dato-label">{{ item.label }}</span>
                <a v-if="item.href" :href="item.href" class="dato-valor enlace">{{ item.valor }}</a>
                <span v-else class="dato-valor">{{ item.valor }}</span>
              </div>
              <router-link to="/reservar" class="cta-contacto">
                Reservar Turno
              </router-link>
            </div>

            <div class="contacto-mapa">
              <iframe
                width="100%"
                height="100%"
                frameborder="0"
                scrolling="no"
                 src="https://www.openstreetmap.org/export/embed.html?bbox=-68.1745,-16.5808,-68.1645,-16.5708&layer=mapnik&marker=-16.5757709,-68.1694978">
                style="border:0; filter:sepia(0.2) contrast(1.05); display:block; min-height: 320px;">
              </iframe>
            </div>
          </div>
        </section>

      </transition>
    </main>

    <!-- ══ FOOTER ═══════════════════════════════════════════════ -->
    <footer class="lamplight-footer">
      <span class="footer-copy">© {{ new Date().getFullYear() }} The Lamplight · Todos los derechos reservados.</span>
    </footer>

  </div>
</template>

<script>
import { useBarberiaStore } from '../../stores/barberia.js';

const REPOSITORIO_FOTOS = [
  'https://images.unsplash.com/photo-1503951914875-452162b0f3f1?w=500&h=500&fit=crop&q=80', 
  'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=500&h=500&fit=crop&q=80', 
  'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=500&h=500&fit=crop&q=80', 
  'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=500&h=500&fit=crop&q=80', 
  'https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?w=500&h=500&fit=crop&q=80', 
  'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=500&h=500&fit=crop&q=80', 
  'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=500&h=500&fit=crop&q=80', 
  'https://images.unsplash.com/photo-1560250097-0b93528c311a?w=500&h=500&fit=crop&q=80', 
  'https://images.unsplash.com/photo-1492562080023-ab3db95bfbce?w=500&h=500&fit=crop&q=80', 
  'https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?w=500&h=500&fit=crop&q=80'  
];

const BIOGRAFIAS = [
  'Maestro barbero con más de 10 años de experiencia. Especialista certificado en cortes Pompadour modernos y afeitados premium con toallas calientes.',
  'Artesano del cabello especializado en perfilado de barbas largas, técnicas de navaja libre y rituales capilares hidratantes.',
  'Experto en degradados clásicos, cortes ejecutivos y asesoramiento personalizado de estilo masculino.',
];

export default {
  name: 'LandingPage',
  setup() {
    const barberiaStore = useBarberiaStore();
    return { barberiaStore };
  },
  data() {
    return {
      seccionActiva: 'inicio',
      categoriaFiltro: '',
      opcionesMenu: [
        { id: 'inicio',    label: 'Inicio'    },
        { id: 'servicios', label: 'Servicios' },
        { id: 'barberos',  label: 'El Equipo' },
        { id: 'contacto',  label: 'Contacto'  },
      ],
      datosContacto: [
        { label: 'Dirección', valor: 'Av. 13 de Noviembre, Zona Huaynasi, Achocalla, La Paz.' },
        { label: 'Teléfono',  valor: '+591 76543210 / (2) 224-4668' },
        { label: 'Horarios',  valor: 'Lunes a Domingo · 10:00 – 22:00' },
        { label: 'Correo',    valor: 'benigno@thelamplight.com', href: 'mailto:benigno@thelamplight.com' },
      ],
    };
  },
  computed: {
    serviciosFiltrados() {
      if (!this.categoriaFiltro) return this.barberiaStore.servicios;
      return this.barberiaStore.servicios.filter(s => s.IdCategoria === this.categoriaFiltro);
    },
    barberosConDatosLocales() {
      return this.barberiaStore.barberos.map((b, idx) => ({
        ...b,
        fotoLocal: REPOSITORIO_FOTOS[idx % REPOSITORIO_FOTOS.length],
        bioLocal:  BIOGRAFIAS[idx % BIOGRAFIAS.length],
      }));
    },
  },
  mounted() {
    this.barberiaStore.cargarDatosCatalogo();
  },
};
</script>

<style scoped>
/* ════════════════════════════════════════════════════════════
   VARIABLES LOCALES
════════════════════════════════════════════════════════════ */
:root {
  --azul:   #1a1a2e;
  --crema:  #F4F0E6;
  --bronce: #c9a84c;
  --gris:   #e5e2da;
}

/* ════════════════════════════════════════════════════════════
   ESTRUCTURA BASE — ocupa exactamente el viewport, sin scroll muerto
════════════════════════════════════════════════════════════ */
.lamplight-raiz {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  width: 100%;
  background-color: #F4F0E6;
  color: #1a1a2e;
  font-family: inherit;
}

.lamplight-main {
  flex: 1;                   /* empuja el footer al fondo sin espacio muerto */
  display: flex;
  flex-direction: column;
}

/* ════════════════════════════════════════════════════════════
   NAVBAR
════════════════════════════════════════════════════════════ */
.lamplight-nav {
  background: #1a1a2e;
  border-bottom: 1px solid rgba(201,168,76,0.25);
  position: sticky;
  top: 0;
  z-index: 50;
  width: 100%;
}

.lamplight-nav-inner {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 2rem;
  height: 110px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1.5rem;
}

.lamplight-brand {
  cursor: pointer;
  display: flex;
  flex-direction: column;
  line-height: 1.2;
}

.lamplight-brand-name {
  font-family: var(--font-vintage, serif);
  color: #fff;
  font-size: 1.15rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.2em;
  transition: color 0.2s;
}

.lamplight-brand:hover .lamplight-brand-name { color: #c9a84c; }

.lamplight-brand-sub {
  font-size: 0.6rem;
  color: rgba(201,168,76,0.7);
  text-transform: uppercase;
  letter-spacing: 0.15em;
}

.lamplight-menu {
  display: flex;
  gap: 0.25rem;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.lamplight-menu-btn {
  font-family: var(--font-vintage, serif);
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  font-weight: 700;
  color: rgba(255,255,255,0.5);
  padding: 0.5rem 0.9rem;
  border: none;
  border-bottom: 2px solid transparent;
  background: transparent;
  cursor: pointer;
  transition: color 0.2s, border-color 0.2s;
  white-space: nowrap;
}

.lamplight-menu-btn:hover        { color: #fff; }
.lamplight-menu-btn.activo       { color: #c9a84c; border-bottom-color: #c9a84c; }


.lamplight-logo-container {
  display: flex;
  align-items: center;
  gap: 2rem; /* Espacio entre la imagen y el texto */
}

.lamplight-logo-img {
  width: 110px;  /* Altura y ancho controlado para que quepa en los 68px del nav */
  height: 110px;
  object-fit: contain;
 
}

.lamplight-brand-text {
  display: flex;
  flex-direction: column;
  line-height: 1.2;
}
/* ════════════════════════════════════════════════════════════
   SECCIÓN INICIO
════════════════════════════════════════════════════════════ */
.seccion-inicio {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem 3rem;
}

.inicio-inner {
  width: 100%;
  max-width: 640px;
  margin: 0 auto;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0;
}

.eyebrow {
  font-family: var(--font-vintage, serif);
  font-size: 0.65rem;
  text-transform: uppercase;
  letter-spacing: 0.55em;
  color: #c9a84c;
  font-weight: 700;
  margin-bottom: 1.5rem;
}

.titulo-hero {
  font-family: var(--font-vintage, serif);
  font-size: clamp(3.5rem, 10vw, 7rem);
  font-weight: 900;
  text-transform: uppercase;
  line-height: 0.92;
  color: #1a1a2e;
  letter-spacing: -0.01em;
  margin-bottom: 1.75rem;
}

.separador-ornamental {
  display: flex;
  align-items: center;
  gap: 1rem;
  width: 100%;
  max-width: 400px;
  margin-bottom: 1.75rem;
}

.linea-bronce {
  flex: 1;
  height: 1px;
  background: rgba(201,168,76,0.4);
}

.texto-ornamental {
  font-size: 0.65rem;
  text-transform: uppercase;
  letter-spacing: 0.35em;
  color: #c9a84c;
  white-space: nowrap;
}

.descripcion-hero {
  font-size: 0.9rem;
  line-height: 1.8;
  color: #555;
  max-width: 420px;
  margin-bottom: 2.5rem;
}

.inicio-ctas {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  width: 100%;
  margin-bottom: 3.5rem;
}

@media (min-width: 480px) {
  .inicio-ctas { flex-direction: row; justify-content: center; }
}

.cta-primario {
  font-family: var(--font-vintage, serif);
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.2em;
  font-weight: 700;
  padding: 1rem 2.5rem;
  background: #1a1a2e;
  color: #F4F0E6;
  text-decoration: none;
  transition: background 0.25s, color 0.25s;
  display: inline-block;
  min-width: 200px;
  text-align: center;
}

.cta-primario:hover {
  background: #c9a84c;
  color: #1a1a2e;
}

.cta-secundario {
  font-family: var(--font-vintage, serif);
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.15em;
  font-weight: 700;
  color: rgba(26,26,46,0.45);
  background: transparent;
  border: none;
  cursor: pointer;
  padding: 0.5rem 0;
  transition: color 0.2s;
}

.cta-secundario:hover { color: #c9a84c; }

/* Stats */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1px;
  width: 100%;
  max-width: 480px;
  border: 1px solid #e5e2da;
  background: #e5e2da;
}

@media (min-width: 480px) {
  .stats-grid { grid-template-columns: repeat(4, 1fr); }
}

.stat-item {
  background: #F4F0E6;
  padding: 1.25rem 0.75rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.3rem;
}

.stat-valor {
  font-family: var(--font-vintage, serif);
  font-size: 1.6rem;
  font-weight: 900;
  color: #c9a84c;
  line-height: 1;
}

.stat-label {
  font-size: 0.6rem;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: #888;
  text-align: center;
}

/* ════════════════════════════════════════════════════════════
   SECCIONES GENÉRICAS (Servicios, Barberos, Contacto)
════════════════════════════════════════════════════════════ */
.seccion-generica {
  flex: 1;
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;
  padding: 3.5rem 2rem 3rem;
}

.seccion-header {
  margin-bottom: 2.75rem;
}

.titulo-seccion {
  font-family: var(--font-vintage, serif);
  font-size: clamp(2.25rem, 6vw, 3.5rem);
  font-weight: 900;
  text-transform: uppercase;
  color: #1a1a2e;
  line-height: 1;
  margin-bottom: 0.5rem;
}

.subtitulo-seccion {
  font-size: 0.875rem;
  color: #666;
  margin-top: 0.75rem;
  max-width: 480px;
  line-height: 1.7;
}

/* ════════════════════════════════════════════════════════════
   FILTROS
════════════════════════════════════════════════════════════ */
.filtros-wrap {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 2.5rem;
}

.filtro-btn {
  font-family: var(--font-vintage, serif);
  font-size: 0.68rem;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  font-weight: 700;
  padding: 0.55rem 1.25rem;
  border: 1px solid #d5d0c6;
  background: transparent;
  color: #1a1a2e;
  cursor: pointer;
  transition: all 0.2s;
}

.filtro-btn:hover { border-color: #1a1a2e; }
.filtro-btn.activo { background: #1a1a2e; color: #F4F0E6; border-color: #1a1a2e; }

/* ════════════════════════════════════════════════════════════
   GRID SERVICIOS
════════════════════════════════════════════════════════════ */
.servicios-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 1.5rem;
}

.servicio-card {
  background: #fff;
  border: 1px solid #e8e4da;
  overflow: hidden;
  transition: transform 0.25s, box-shadow 0.25s;
}

.servicio-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 12px 32px rgba(26,26,46,0.1);
}

.servicio-img-wrap {
  position: relative;
  width: 100%;
  height: 200px;
  overflow: hidden;
  background: #eee;
}

.servicio-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  filter: grayscale(0.3);
  transition: filter 0.4s, transform 0.4s;
}

.servicio-card:hover .servicio-img {
  filter: grayscale(0);
  transform: scale(1.04);
}

.badge-duracion {
  position: absolute;
  top: 0.75rem;
  right: 0.75rem;
  background: rgba(26,26,46,0.88);
  color: #F4F0E6;
  font-family: var(--font-vintage, serif);
  font-size: 0.6rem;
  text-transform: uppercase;
  letter-spacing: 0.15em;
  padding: 0.3rem 0.65rem;
}

.servicio-info {
  padding: 1.1rem 1.25rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  border-top: 1px solid #f0ece2;
}

.servicio-nombre {
  font-family: var(--font-vintage, serif);
  font-size: 0.95rem;
  font-weight: 700;
  color: #1a1a2e;
  line-height: 1.3;
}

.servicio-precio {
  font-family: var(--font-vintage, serif);
  font-size: 1.3rem;
  font-weight: 900;
  color: #c9a84c;
  white-space: nowrap;
}

.servicio-precio small {
  font-size: 0.7rem;
  font-weight: 400;
  margin-left: 1px;
}

/* ════════════════════════════════════════════════════════════
   BARBEROS
════════════════════════════════════════════════════════════ */
.barberos-grid {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.barbero-card {
  display: flex;
  align-items: stretch;
  background: #fff;
  border: 1px solid #e8e4da;
  overflow: hidden;
  transition: border-color 0.25s;
}

.barbero-card:hover { border-color: rgba(201,168,76,0.5); }

.barbero-numero {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 3.5rem;
  min-height: 100%;
  background: #f8f5ef;
  border-right: 1px solid #e8e4da;
  font-family: var(--font-vintage, serif);
  font-size: 1.5rem;
  font-weight: 900;
  color: rgba(26,26,46,0.12);
  flex-shrink: 0;
  transition: color 0.25s;
}

.barbero-card:hover .barbero-numero { color: rgba(201,168,76,0.35); }

.barbero-foto-wrap {
  width: 130px;
  min-height: 150px;
  flex-shrink: 0;
  overflow: hidden;
  background: #eee;
}

@media (max-width: 520px) {
  .barbero-foto-wrap { width: 100px; }
  .barbero-numero    { width: 2.5rem; }
}

.barbero-foto {
  width: 100%;
  height: 100%;
  object-fit: cover;
  filter: sepia(0.25);
  transition: filter 0.4s;
}

.barbero-card:hover .barbero-foto { filter: sepia(0); }

.barbero-info {
  padding: 1.5rem 1.75rem;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 0.4rem;
  flex: 1;
}

.barbero-rol {
  font-size: 0.6rem;
  text-transform: uppercase;
  letter-spacing: 0.2em;
  color: #c9a84c;
  font-weight: 700;
}

.barbero-nombre {
  font-family: var(--font-vintage, serif);
  font-size: 1.35rem;
  font-weight: 800;
  color: #1a1a2e;
  line-height: 1.2;
}

.barbero-bio {
  font-size: 0.82rem;
  color: #666;
  line-height: 1.75;
  max-width: 520px;
  margin-top: 0.25rem;
}

/* ════════════════════════════════════════════════════════════
   CONTACTO
════════════════════════════════════════════════════════════ */
.contacto-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.5rem;
}

@media (min-width: 900px) {
  .contacto-grid { grid-template-columns: 5fr 7fr; }
}

.contacto-datos {
  background: #fff;
  border: 1px solid #e8e4da;
  padding: 2.25rem 2rem;
  display: flex;
  flex-direction: column;
  gap: 0;
}

.dato-fila {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
  padding: 1.1rem 0;
  border-bottom: 1px solid #f0ece2;
}

.dato-fila:last-of-type { border-bottom: none; }

.dato-label {
  font-family: var(--font-vintage, serif);
  font-size: 0.6rem;
  text-transform: uppercase;
  letter-spacing: 0.22em;
  color: #c9a84c;
  font-weight: 700;
}

.dato-valor {
  font-size: 0.88rem;
  color: #333;
  line-height: 1.55;
}

.dato-valor.enlace {
  color: #1a1a2e;
  text-decoration: none;
  transition: color 0.2s;
}

.dato-valor.enlace:hover { color: #c9a84c; }

.cta-contacto {
  display: block;
  margin-top: 1.75rem;
  padding: 0.95rem 1.5rem;
  background: #1a1a2e;
  color: #F4F0E6;
  text-align: center;
  font-family: var(--font-vintage, serif);
  font-size: 0.72rem;
  text-transform: uppercase;
  letter-spacing: 0.2em;
  font-weight: 700;
  text-decoration: none;
  transition: background 0.25s, color 0.25s;
}

.cta-contacto:hover {
  background: #c9a84c;
  color: #1a1a2e;
}

.contacto-mapa {
  border: 1px solid #e8e4da;
  overflow: hidden;
  min-height: 320px;
}

.contacto-mapa iframe {
  display: block;
  width: 100%;
  height: 100%;
  min-height: 320px;
}

/* ════════════════════════════════════════════════════════════
   FOOTER
════════════════════════════════════════════════════════════ */
.lamplight-footer {
  background: #1a1a2e;
  border-top: 1px solid rgba(255,255,255,0.07);
  padding: 1.25rem 2rem;
  text-align: center;
  width: 100%;
}

.footer-copy {
  font-size: 0.7rem;
  color: rgba(255,255,255,0.25);
  letter-spacing: 0.05em;
}

/* ════════════════════════════════════════════════════════════
   SKELETONS
════════════════════════════════════════════════════════════ */
.skeleton-card {
  background: #fff;
  border: 1px solid #e8e4da;
  overflow: hidden;
  animation: pulse 1.4s ease-in-out infinite;
}

.skeleton-img {
  width: 100%;
  height: 200px;
  background: #ede9df;
}

.skeleton-body {
  padding: 1rem 1.25rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.skeleton-line {
  height: 12px;
  background: #ede9df;
  border-radius: 3px;
}

.skeleton-line.ancho  { flex: 1; }
.skeleton-line.corto  { width: 60px; flex-shrink: 0; }

.skeleton-barbero {
  display: flex;
  gap: 1.25rem;
  padding: 1.25rem;
  background: #fff;
  border: 1px solid #e8e4da;
  animation: pulse 1.4s ease-in-out infinite;
}

.skeleton-foto {
  width: 130px;
  height: 140px;
  flex-shrink: 0;
  background: #ede9df;
}

.skeleton-texto {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
  padding: 0.5rem 0;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50%       { opacity: 0.6; }
}

/* ════════════════════════════════════════════════════════════
   ESTADO VACÍO
════════════════════════════════════════════════════════════ */
.vacio-msg {
  text-align: center;
  font-size: 0.85rem;
  color: #aaa;
  padding: 3rem 0;
}

/* ════════════════════════════════════════════════════════════
   TRANSICIONES
════════════════════════════════════════════════════════════ */
.fade-seccion-enter-active,
.fade-seccion-leave-active {
  transition: opacity 0.18s ease, transform 0.18s ease;
}

.fade-seccion-enter-from {
  opacity: 0;
  transform: translateY(8px);
}

.fade-seccion-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}
</style>