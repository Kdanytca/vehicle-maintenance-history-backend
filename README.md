# Vehicle Maintenance History Backend

Backend Laravel 12 convertido a API para ser consumido por el frontend Vue separado.

## Requisitos

- PHP 8.2+
- Composer
- PostgreSQL

## Instalacion

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

La API queda disponible por defecto en `http://127.0.0.1:8000/api`.

## Autenticacion

El frontend usa tokens Bearer de Laravel Sanctum:

- `POST /api/login`
- `GET /api/me`
- `POST /api/logout`

Configura CORS/URL del frontend segun el dominio donde corra Vue.

Variables importantes para despliegue:

```env
DB_CONNECTION=pgsql
FRONTEND_URL=https://tu-frontend.vercel.app
```

## Despliegue en Render

El repo incluye `Dockerfile` para desplegar el backend como Web Service Docker en Render. Configura las variables de entorno reales en Render, no subas `.env` al repositorio.

Variables mínimas:

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...
APP_URL=https://tu-backend.onrender.com
FRONTEND_URL=https://tu-frontend.vercel.app
DB_CONNECTION=pgsql
DB_HOST=...
DB_PORT=5432
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...
```

Después del primer deploy ejecuta:

```bash
php artisan migrate --force --seed
```
