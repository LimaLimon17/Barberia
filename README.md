# Sistema de Gestión de Barbería ✂️

Sistema integral desarrollado con arquitectura Cliente-Servidor (Laravel API + Vue 3) para gestionar reservas, barberos, comisiones semanales, inventario de productos, reportes financieros y más.

## 🛠️ Requisitos Previos (Lo que necesitas tener instalado)

Para hacer funcionar el sistema en tu entorno local, asegúrate de tener instalados los siguientes programas:

1. **XAMPP / Laragon / WAMP:** O cualquier servidor de MySQL local.
2. **PHP 8.2+:** Asegúrate de que PHP esté en las variables de entorno de tu sistema (`php -v`).
3. **Composer:** Gestor de dependencias de PHP para el backend (`composer -v`).
4. **Node.js (v18+) y NPM:** Entorno de ejecución para el frontend (`node -v`, `npm -v`).
5. **Git:** Para control de versiones del proyecto.

### Dependencias y Librerías utilizadas en el proyecto
*El proyecto ya tiene configurado el uso de las siguientes tecnologías, las cuales se instalarán automáticamente al seguir la guía de instalación.*

**Backend (Laravel 12):**
- Laravel Sanctum (Autenticación por Tokens)
- Programación de Tareas (`routes/console.php` para consolidación de comisiones)

**Frontend (Vue 3 + Vite):**
- Vue Router (Enrutamiento)
- Pinia (Gestión de estado global)
- Tailwind CSS v4 (Diseño e interfaces modernas)
- Axios (Peticiones HTTP)
- **jsPDF y jsPDF-AutoTable:** Para la generación de reportes y comprobantes (Nota de Venta, Finanzas, Inventario, etc.) en formato PDF.
- **date-fns:** Para la manipulación avanzada de fechas.

---

## 🚀 Guía de Instalación y Ejecución

Sigue estos pasos en orden estricto para configurar tu entorno:

### 1. Configuración de la Base de Datos
1. Inicia tu servidor de MySQL (por ejemplo, encendiendo MySQL en el panel de control de XAMPP).
2. Abre phpMyAdmin o tu gestor de base de datos favorito (DBeaver, MySQL Workbench).
3. No necesitas crear la base de datos manualmente. Simplemente **importa y ejecuta** el archivo `database_setup.sql` que se encuentra en la carpeta raíz del proyecto.
   * *Este archivo contiene el script completo que crea automáticamente la base de datos `Barberia_bd`, las tablas necesarias, relaciones, procedimientos almacenados y los datos de prueba iniciales (clientes, barberos, productos, servicios, etc).*

### 2. Levantando el Backend (Laravel)
1. Abre una terminal y navega hasta la carpeta del backend:
   ```bash
   cd backend
   ```
2. Instala las dependencias de PHP (esto instalará todo lo declarado en el composer.json):
   ```bash
   composer install
   ```
3. Crea tu archivo de entorno y genera la clave de encriptación:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
4. Asegúrate de que el archivo `.env` tenga las siguientes credenciales para conectar a la base de datos:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=Barberia_bd
   DB_USERNAME=root
   DB_PASSWORD=
   ```
5. Levanta el servidor local de desarrollo:
   ```bash
   php artisan serve
   ```
   *La API estará corriendo en `http://localhost:8000` (déjalo abierto en esta terminal).*

**Nota sobre Tareas Programadas (Consolidación de Comisiones):**
El sistema consolida las comisiones todos los domingos a las 21:00 automáticamente. Para probar o mantener viva la ejecución de tareas programadas en tu entorno local, puedes abrir una terminal extra en la carpeta `backend` y ejecutar:
```bash
php artisan schedule:work
```

### 3. Levantando el Frontend (Vue 3 + Vite)
1. Abre **otra terminal nueva** y navega hasta la carpeta del frontend:
   ```bash
   cd frontend
   ```
2. Instala todas las dependencias de Node (esto instalará Vue, Tailwind, jsPDF, etc.):
   ```bash
   npm install
   ```
3. Levanta el servidor de desarrollo visual:
   ```bash
   npm run dev
   ```
   *La aplicación estará corriendo en `http://localhost:5173`.*

---

## 🔐 Usuarios de Prueba Pre-registrados

Una vez que tengas ambos servidores corriendo, entra a `http://localhost:5173` y puedes utilizar cualquiera de estos usuarios de prueba que ya vienen en la base de datos:

**1. Administrador (Control Total):**
* **Correo:** `admin@barberia.com`
* **Contraseña:** `123456`
* *(El administrador puede ver el Panel de Finanzas, Reportes de Ventas, Reportes de Inventario y el rendimiento general de los barberos).*

**2. Barbero (Perfil limitado):**
* **Correo:** `barbero1@barberia.com`
* **Contraseña:** `123456`
* *(Existen otros barberos registrados del `barbero1` hasta `barbero10` con la misma contraseña. Los barberos pueden ver su reporte de comisiones en vivo).*

---

## ✨ Características Principales Implementadas

- **Gestión de Barberos:** Panel completo de registro, edición y revisión de horarios.
- **Finanzas y Comisiones:** Panel financiero centralizado con desglose de ganancias por servicio, producto y anticipos (ausentes). Sistema de consolidación semanal (50% servicios/ausentes).
- **Control de Inventario:** Historial de productos vendidos con alertas visuales por bajo stock e ingresos acumulados.
- **Exportación en PDF:** Generación en tiempo real de "Notas de Venta", Reportes de Inventario, Reportes de Comisiones y Resúmenes Financieros (usando jsPDF).
- **Estética Moderna:** Interfaz con Tailwind CSS, incorporando fondos y tarjetas pulidas para brindar una experiencia de usuario (UX) ágil e intuitiva.
