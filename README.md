# Sistema de Gestión de Barbería ✂️

Sistema integral desarrollado con arquitectura **Cliente-Servidor** para gestionar barberos, horarios, reservas, comisiones, inventario y más.

---

## 🛠️ Requisitos Previos

Asegúrate de tener instalados los siguientes programas antes de continuar:

1. **XAMPP / Laragon / WAMP** — o cualquier servidor MySQL local.
2. **PHP 8.2+** — verifica con `php -v`.
3. **Composer** — gestor de dependencias de PHP (`composer -v`).
4. **Node.js (v18+) y NPM** — entorno de ejecución para el frontend (`node -v`, `npm -v`).
5. **MySQL Workbench 8.0** — para ejecutar el script de base de datos.

---

## 🚀 Guía de Instalación y Ejecución

Sigue estos pasos en orden estricto:

### 1. Configuración de la Base de Datos

1. Inicia tu servidor MySQL (por ejemplo, desde el panel de control de XAMPP).
2. Abre **MySQL Workbench 8.0**.
3. Ve a **File → Open SQL Script** y selecciona el archivo `Barberia_bd_completo.sql` ubicado en la raíz del proyecto.
4. Ejecuta el script completo con **Ctrl + Shift + Enter**.

> Este archivo crea automáticamente la base de datos `Barberia_bd`, las 16 tablas, sus relaciones, los procedimientos almacenados y los datos de catálogo iniciales (categorías, servicios, productos, lotes, horarios plantilla y el usuario administrador).

### 2. Levantando el Backend (Laravel)

Abre una terminal y ejecuta:

```bash
cd backend
composer install
cp .env.example .env
```

Verifica que el archivo `.env` tenga estas credenciales:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=Barberia_bd
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

Luego inicia el servidor:

```bash
php artisan config:clear
php artisan cache:clear
php artisan serve
```

La API quedará corriendo en `http://localhost:8000`. **Deja esta terminal abierta.**

### 3. Levantando el Frontend (Vue 3 + Vite)

Abre **otra terminal** y ejecuta:

```bash
cd frontend
npm install
npm run dev
```

La aplicación estará disponible en `http://localhost:5173`.

---

## 🔐 Usuario de Prueba

Una vez con ambos servidores corriendo, ingresa a `http://localhost:5173`:

| Rol           | Correo                  | Contraseña |
|---------------|-------------------------|------------|
| Administrador | `admin@barberia.com`    | `123456`   |

> Los barberos se registran directamente desde la aplicación en **Admin → Registrar Barbero**.

---

## 🗂️ Estructura del Proyecto

```
Barberia-main/
├── backend/                          # API Laravel
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── Auth/
│   │   │   │   │   └── LoginController.php       # Login y logout con Sanctum
│   │   │   │   ├── Admin/
│   │   │   │   │   ├── BarberoController.php     # CRUD barberos + activar/desactivar
│   │   │   │   │   ├── HorarioController.php     # Horarios por barbero
│   │   │   │   │   ├── HorarioSemanalController.php  # Vista semanal FIFO
│   │   │   │   │   └── AlmuerzoController.php    # Registros de entrada/salida
│   │   │   │   └── Barbero/
│   │   │   │       └── PerfilController.php      # Perfil del barbero autenticado
│   │   │   └── Middleware/
│   │   │       └── RoleMiddleware.php            # Control de acceso por rol
│   │   └── Models/
│   │       ├── User.php / Usuario.php
│   │       ├── Barbero.php
│   │       ├── Horario.php
│   │       └── HorarioSemanal.php                # Mapea tabla HorariosBarberos
│   └── routes/
│       └── api.php                              # Todas las rutas de la API
│
├── frontend/                         # SPA Vue 3 + Vite
│   └── src/
│       ├── views/
│       │   ├── public/
│       │   │   └── LoginView.vue
│       │   ├── admin/
│       │   │   ├── DashboardAdmin.vue
│       │   │   ├── ListaBarberos.vue
│       │   │   ├── PerfilBarberoAdmin.vue
│       │   │   ├── RegistrarBarbero.vue
│       │   │   ├── EditarBarbero.vue
│       │   │   ├── GestionHorarios.vue
│       │   │   ├── VerHorarioBarbero.vue
│       │   │   └── EditarHorarioBarbero.vue
│       │   └── barbero/
│       │       ├── DashboardBarbero.vue
│       │       └── PerfilBarbero.vue
│       ├── services/
│       │   ├── api.js                           # Instancia Axios con baseURL
│       │   ├── authService.js                   # Login / logout
│       │   └── barberoService.js                # Todos los endpoints de barberos y horarios
│       ├── stores/
│       │   └── auth.js                          # Estado global de autenticación (Pinia)
│       └── router/
│           └── index.js                         # Rutas + guards por rol
│
└── Barberia_bd_completo.sql          # Script completo de base de datos
```

---

## 🌐 Rutas de la API

### Públicas

| Método | Endpoint      | Descripción        |
|--------|---------------|--------------------|
| POST   | `/api/login`  | Iniciar sesión     |
| POST   | `/api/logout` | Cerrar sesión      |

### Administrador (`/api/admin/...`)

| Método | Endpoint                              | Descripción                        |
|--------|---------------------------------------|------------------------------------|
| GET    | `/barberos`                           | Listar todos los barberos          |
| POST   | `/barberos`                           | Registrar nuevo barbero            |
| GET    | `/barberos/{id}`                      | Ver perfil de un barbero           |
| PUT    | `/barberos/{id}`                      | Editar datos del barbero           |
| DELETE | `/barberos/{id}`                      | Desactivar barbero                 |
| PATCH  | `/barberos/{id}/activar`              | Reactivar barbero                  |
| GET    | `/barberos/{id}/horarios`             | Ver horarios de un barbero         |
| POST   | `/horarios`                           | Crear horario para un barbero      |
| PUT    | `/horarios/{id}`                      | Editar horario existente           |
| GET    | `/horarios-semana?semana=&ano=`       | Vista semanal FIFO de descansos    |
| GET    | `/barberos/{id}/almuerzos`            | Ver registros de entrada/salida    |
| POST   | `/barberos/{id}/almuerzos`            | Registrar salida a almuerzo        |
| PUT    | `/barberos/{id}/almuerzos/{idReg}`    | Registrar retorno de almuerzo      |

### Barbero (`/api/barbero/...`)

| Método | Endpoint   | Descripción                   |
|--------|------------|-------------------------------|
| GET    | `/perfil`  | Ver perfil propio del barbero |

---

## 🗃️ Estructura de la Base de Datos

**16 tablas** organizadas por módulo:

| Módulo               | Tablas                                                                 |
|----------------------|------------------------------------------------------------------------|
| Seguridad            | `Roles`, `Usuarios`, `AuditoriaGeneral`                               |
| Personal y Horarios  | `Barberos`, `Horarios`, `HorariosBarberos`, `Registros`               |
| Servicios y Reservas | `Categorias`, `Servicios`, `Clientes`, `Reservas`, `ReservaServicios` |
| Ventas y Comisiones  | `Ventas`, `DetalleVenta`, `Pagos`, `Comisiones`                       |
| Inventario           | `Productos`, `Lotes`, `HistorialPorcentajeProductos`                  |

### Procedimientos Almacenados

| Procedimiento               | Descripción                                          |
|-----------------------------|------------------------------------------------------|
| `sp_RegistrarAuditoria`     | Inserta un registro en `AuditoriaGeneral`            |
| `sp_AuditoriaLoginExitoso`  | Audita inicio de sesión correcto                     |
| `sp_AuditoriaLoginFallido`  | Audita intento de login fallido                      |
| `sp_RegistrarBarbero`       | Crea usuario + barbero en una transacción            |
| `sp_EditarPerfilBarbero`    | Edita datos del barbero con validaciones             |
| `sp_DesactivarBarbero`      | Desactiva usuario y barbero                          |
| `sp_ActivarBarbero`         | Reactiva usuario y barbero                           |
| `sp_AsignarHorarioSemanal`  | Asigna horario semanal por días en JSON              |

---

## ⚙️ Stack Tecnológico

| Capa       | Tecnología                          |
|------------|-------------------------------------|
| Backend    | PHP 8.2 · Laravel 11 · Sanctum      |
| Frontend   | Vue 3 · Vite · Pinia · Vue Router   |
| Base de datos | MySQL 8.0                        |
| Servidor local | XAMPP / Laragon               |
