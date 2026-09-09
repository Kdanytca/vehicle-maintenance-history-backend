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
