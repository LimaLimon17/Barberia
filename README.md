# Sistema de Gestión de Barbería ✂️

Sistema integral desarrollado con arquitectura Cliente-Servidor para gestionar reservas, barberos, comisiones, inventario y más.

---

## 🛠️ Requisitos Previos

Para hacer funcionar el sistema en tu entorno local, asegúrate de tener instalados los siguientes programas:

1. **XAMPP / Laragon / WAMP:** O cualquier servidor de MySQL local.
2. **PHP 8.2+:** Asegúrate de que PHP esté en las variables de entorno de tu sistema (`php -v`).
3. **Composer:** Gestor de dependencias de PHP (`composer -v`).
4. **Node.js (v18+) y NPM:** Entorno de ejecución para el frontend (`node -v`, `npm -v`).
5. **Git:** Para control de versiones.

---

## 🚀 Guía de Instalación y Ejecución

Sigue estos pasos en orden estricto para configurar tu entorno.

### 1. Configuración de la Base de Datos

1. Inicia tu servidor de MySQL (por ejemplo, encendiendo MySQL en el panel de control de XAMPP).
2. Abre phpMyAdmin o tu gestor de base de datos favorito (DBeaver, MySQL Workbench).
3. No necesitas crear la base de datos manualmente. Simplemente **importa y ejecuta** el archivo `barberia_bd.sql` que se encuentra en la carpeta raíz del proyecto.

> *Este archivo contiene el script completo que crea automáticamente la base de datos `Barberia_bd`, las 20 tablas, relaciones, roles, procedimientos almacenados y los datos de prueba iniciales (clientes, barberos, productos, servicios, etc).*

---

### 2. Backend (Laravel)

1. Abre una terminal y navega hasta la carpeta del backend:
   ```bash
   cd backend
   ```

2. Instala las dependencias:
   ```bash
   composer install
   ```

3. Crea tu archivo de entorno (si no existe):
   ```bash
   cp .env.example .env
   ```

4. Configura las credenciales de la base de datos en el `.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=Barberia_bd
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. Levanta el servidor de desarrollo:
   ```bash
   php artisan serve
   ```

> *La API estará corriendo en `http://localhost:8000` — déjalo abierto.*

---

### 3. Frontend (Vue 3 + Vite)

1. Abre **otra terminal nueva** y navega hasta la carpeta del frontend:
   ```bash
   cd frontend
   ```

2. Instala las dependencias de Node:
   ```bash
   npm install
   ```

3. Instala la librería para generación de comprobantes en PDF:
   ```bash
   npm install html2pdf.js
   ```

4. Levanta el servidor de desarrollo:
   ```bash
   npm run dev
   ```

> *La aplicación estará corriendo en `http://localhost:5173`.*

---

## 🔐 Usuarios de Prueba

Una vez con ambos servidores corriendo, entra a `http://localhost:5173` y utiliza cualquiera de estos usuarios:

| Rol | Correo | Contraseña |
|-----|--------|------------|
| Administrador | `admin@barberia.com` | `12345678` |
| Barbero | `barbero1@barberia.com` | `12345678` |

> *Existen barberos del `barbero1` al `barbero10`, todos con contraseña `12345678`.*

---

## ✨ Estructura de la Base de Datos

El sistema maneja la siguiente arquitectura relacional:

- **Seguridad:** `Usuarios`, `Roles`, `AuditoriaGeneral`
- **Personal y Horarios:** `Barberos`, `Horarios`, `HorariosBarberos`, `Registros`
- **Servicios y Reservas:** `Categorias`, `Servicios`, `Clientes`, `Reservas`, `ReservaServicios`
- **Ventas y Comisiones:** `Ventas`, `DetalleVenta`, `Pagos`, `Comisiones`
- **Inventario:** `Productos`, `Lotes`, `HistorialPorcentajeProductos`

Todos los controladores del backend están ajustados para consultar estas tablas mediante procedimientos almacenados (auditorías, edición de perfiles, registro de barberos, generación de comprobantes, etc).