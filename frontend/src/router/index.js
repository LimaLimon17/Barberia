import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth.js'
// Layouts
import LayoutPublico from '../layouts/LayoutPublico.vue'
import LayoutBarbero from '../layouts/LayoutBarbero.vue'
import LayoutAdmin from '../layouts/LayoutAdmin.vue'
// Vistas públicas
import LoginView from '../views/public/LoginView.vue'
import HomeView from '../views/public/HomeView.vue'
import ReservarView from '../views/public/ReservarView.vue'
// Vistas del barbero
import DashboardBarbero from '../views/barbero/DashboardBarbero.vue'
import PerfilBarbero from '../views/barbero/PerfilBarbero.vue'
import VentaProductosView from '../views/barbero/VentaProductosView.vue'

import ReportesBarbero from '../views/barbero/ReportesBarbero.vue'
// Vistas del administrador
import DashboardAdmin from '../views/admin/DashboardAdmin.vue'
import ListaBarberos from '../views/admin/ListaBarberos.vue'
import PerfilBarberoAdmin from '../views/admin/PerfilBarberoAdmin.vue'
import EditarBarbero from '../views/admin/EditarBarbero.vue'
import FinanzasAdmin from '../views/admin/Finanzas.vue'
import ReportesVentasAdmin from '../views/admin/ReportesVentas.vue'
import ReportesInventarioAdmin from '../views/admin/ReportesInventario.vue'
import RegistrarBarbero from '../views/admin/RegistrarBarbero.vue'
import GestionHorarios from '../views/admin/GestionHorarios.vue'
import RegistroAlmuerzos from '../views/admin/RegistroAlmuerzos.vue'
import VerHorarioBarbero   from '../views/admin/VerHorarioBarbero.vue'
import EditarHorarioBarbero from '../views/admin/EditarHorarioBarbero.vue'

// Vistas del módulo catálogo, inventario y productos
import ServiciosView from '../views/admin/ServiciosView.vue'
import InventarioView from '../views/admin/InventarioView.vue'
import PorcentajesView from '../views/admin/PorcentajesView.vue'
import AuditoriaView from '../views/admin/AuditoriaView.vue'

const routes = [
  // ==========================================
  // RUTAS PÚBLICAS
  // ==========================================
  {
    path: '/',
    redirect: '/login',
  },
  {
    path: '/login',
    component: LayoutPublico,
    children: [
      {
        path: '',
        name: 'Login',
        component: LoginView,
        meta: { requiresAuth: false, title: 'Iniciar Sesión' },
      },
    ],
  },

  //RUTA PAGINA PUBLICA
  {
    path: '/inicio',
    name: 'Home',
    component: HomeView,
    meta: { title: 'The Lamplight Barber Shop' }
  },
 // RUTA PUBLICA - WIZARD DE RESERVA (HU-04 / HU-05)
  {
    path: '/reservar',
    name: 'Reservar',
    component: ReservarView,
    meta: { title: 'Reservar Cita' },
  },

  // ==========================================
  // RUTAS DEL BARBERO (Rol = 2)
  // ==========================================
  {
    path: '/barbero',
    component: LayoutBarbero,
    meta: { requiresAuth: false, rol: 'barbero' },
    children: [
      {
        path: '',
        redirect: '/barbero/dashboard',
      },
      {
        path: 'dashboard',
        name: 'DashboardBarbero',
        component: DashboardBarbero,
        meta: { title: 'Mi Agenda' },
      },
      {
        path: 'perfil',
        name: 'PerfilBarbero',
        component: PerfilBarbero,
        meta: { title: 'Mi Perfil' },
      },
      {
        path: 'venta-productos',
        name: 'VentaProductosBarbero',
        component: VentaProductosView,
        meta: { title: 'Venta de Productos' },
      },
      {
        path: 'reportes',
        name: 'ReportesBarbero',
        component: ReportesBarbero,
        meta: { title: 'Mis Reportes y Comisiones' },
      },
    ],
  },
  // ==========================================
  // RUTAS DEL ADMINISTRADOR (Rol = 1)
  // ==========================================
  {
    path: '/admin',
    component: LayoutAdmin,
    meta: { requiresAuth: false, rol: 'admin' },
    children: [
      {
        path: '',
        redirect: '/admin/dashboard',
      },
      {
        path: 'dashboard',
        name: 'DashboardAdmin',
        component: DashboardAdmin,
        meta: { title: 'Panel de Administración' },
      },
      {
        path: 'finanzas',
        name: 'FinanzasAdmin',
        component: FinanzasAdmin,
        meta: { title: 'Finanzas' },
      },
      {
        path: 'reportes/ventas',
        name: 'ReportesVentasAdmin',
        component: ReportesVentasAdmin,
        meta: { title: 'Reporte de Ventas' },
      },
      {
        path: 'reportes/inventario',
        name: 'ReportesInventarioAdmin',
        component: ReportesInventarioAdmin,
        meta: { title: 'Reporte de Inventario' },
      },
      {
        path: 'barberos',
        name: 'ListaBarberos',
        component: ListaBarberos,
        meta: { title: 'Gestión de Barberos' },
      },
      {
        path: 'barberos/nuevo',
        name: 'RegistrarBarbero',
        component: RegistrarBarbero,
        meta: { title: 'Registrar Barbero' },
      },
      {
        path: 'barberos/:id',
        name: 'PerfilBarberoAdmin',
        component: PerfilBarberoAdmin,
        meta: { title: 'Perfil del Barbero' },
        props: true,
      },
      {
        path: 'barberos/:id/editar',
        name: 'EditarBarbero',
        component: EditarBarbero,
        meta: { title: 'Editar Barbero' },
        props: true,
      },
      {
        path: 'horarios',
        name: 'GestionHorarios',
        component: GestionHorarios,
        meta: { title: 'Gestión de Horarios' },
      },
        props: true,
      },
    ],
  },
  // Ruta 404
  {
    path: '/:pathMatch(.*)*',
    redirect: '/login',
  },
]
const router = createRouter({
  history: createWebHistory(),
  routes,
})
// ==========================================
// GUARD DE NAVEGACIÓN - Protección por rol
// ==========================================
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()
  // Actualizar título de la página
  document.title = to.meta.title
    ? `${to.meta.title} | Barbería`
    : 'Barbería - Sistema de Gestión'
  // Si la ruta requiere autenticación
  if (to.matched.some((record) => record.meta.requiresAuth)) {
    if (!authStore.isAuthenticated) {
      return next({ name: 'Login' })
    }
    // Verificar rol
    const rolRequerido = to.matched.find((record) => record.meta.rol)?.meta.rol

    if (rolRequerido) {
      if (rolRequerido === 'admin' && !authStore.esAdmin) {
        return next({ name: 'DashboardBarbero' })
      }

      if (rolRequerido === 'barbero' && !authStore.esBarbero) {
        return next({ name: 'DashboardAdmin' })
      }
    }
  }
  // Si ya está autenticado y va al login, redirigir al dashboard
  if (to.name === 'Login' && authStore.isAuthenticated) {
    if (authStore.esAdmin) {
      return next({ name: 'DashboardAdmin' })
    }

    if (authStore.esBarbero) {
      return next({ name: 'DashboardBarbero' })
    }
  }
  next()
})
export default router