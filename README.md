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

### Migraciones automaticas en Neon

El `Dockerfile` inicia `docker-entrypoint.sh`, que ejecuta este comando antes de Apache en cada arranque (incluidos deploys y reinicios):

```bash
php artisan migrate --force --no-interaction
```

Usa la conexion de base de datos configurada en Render. Para Neon configura tambien `DB_SSLMODE=require`. Si una migracion falla, el contenedor se detiene y el error aparece en los logs; Apache solo inicia si el comando termina correctamente. No se ejecutan seeders automaticamente.

Laravel consulta `migrations` y aplica solamente las pendientes. Para futuros cambios crea archivos nuevos en `database/migrations`; editar una migracion ya aplicada no vuelve a ejecutarla. Este arranque esta pensado para un servicio con una instancia; con varias instancias ejecuta las migraciones una sola vez en un paso de predeploy.

La bitacora `database/neon_schema_seed.sql` ya registra las migraciones del esquema creado manualmente. La migracion de facturas usa ahora una sola extension `.php`, coincidiendo con esa bitacora. Si otra base fue creada con Artisan y registro `2026_06_02_225717_create_facturas_table.php`, corrige ese registro al nombre sin `.php` antes de desplegar alli.

El modulo de clientes se aplica automaticamente en el siguiente deploy si sus dos migraciones siguen pendientes. `database/neon_clientes.sql` contiene solamente esa actualizacion para quien necesite aplicarla manualmente: tambien registra las dos migraciones. No es necesario ejecutarlo si el despliegue automatico ya las aplico, ni ejecutarlo mientras el despliegue esta migrando. El archivo completo incluye datos demo y no debe usarse como actualizacion de produccion.
