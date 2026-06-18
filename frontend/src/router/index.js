import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth.js'

// Layouts
import LayoutPublico from '../layouts/LayoutPublico.vue'
import LayoutBarbero from '../layouts/LayoutBarbero.vue'
import LayoutAdmin from '../layouts/LayoutAdmin.vue'

// Vistas públicas
import LoginView from '../views/public/LoginView.vue'

// Vistas del barbero
import DashboardBarbero from '../views/barbero/DashboardBarbero.vue'
import PerfilBarbero from '../views/barbero/PerfilBarbero.vue'

// Vistas del administrador
import DashboardAdmin from '../views/admin/DashboardAdmin.vue'
import ListaBarberos from '../views/admin/ListaBarberos.vue'
import PerfilBarberoAdmin from '../views/admin/PerfilBarberoAdmin.vue'
import EditarBarbero from '../views/admin/EditarBarbero.vue'
import RegistrarBarbero from '../views/admin/RegistrarBarbero.vue'

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

  // ==========================================
  // RUTAS DEL BARBERO (Rol = 2)
  // ==========================================
  {
    path: '/barbero',
    component: LayoutBarbero,
    meta: { requiresAuth: true, rol: 'barbero' },
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
    ],
  },

  // ==========================================
  // RUTAS DEL ADMINISTRADOR (Rol = 1)
  // ==========================================
  {
    path: '/admin',
    component: LayoutAdmin,
    meta: { requiresAuth: true, rol: 'admin' },
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
        path: 'barberos',
        name: 'ListaBarberos',
        component: ListaBarberos,
        meta: { title: 'Gestión de Barberos' },
      },
      {
        path: 'barberos/:id',
        name: 'PerfilBarberoAdmin',
        component: PerfilBarberoAdmin,
        meta: { title: 'Perfil del Barbero' },
        props: true,
      },
      {
  path: 'barberos/nuevo',
  name: 'RegistrarBarbero',
  component: RegistrarBarbero,
  meta: { title: 'Registrar Barbero' },
},
      {
        path: 'barberos/:id/editar',
        name: 'EditarBarbero',
        component: EditarBarbero,
        meta: { title: 'Editar Barbero' },
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
