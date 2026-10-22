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
DB_SSLMODE=require
```

### Migraciones y datos base automaticos en Neon

En Render gratuito, `docker-entrypoint.sh` ejecuta antes de iniciar Apache:

```bash
php artisan migrate --force --no-interaction
php artisan db:seed --class=ProductionSeeder --force --no-interaction
```

Con el despliegue automatico de la rama `main`, cada push aplica las migraciones pendientes y agrega los datos base faltantes. Tambien se ejecuta al reiniciar el contenedor. Si un comando falla, el script termina y no inicia Apache; el error aparece en los logs de Render.

Configura en Render la conexion a Neon (`DB_CONNECTION=pgsql`, las variables `DB_*` anteriores y `DB_SSLMODE=require`, o `DB_URL` con SSL). Si existe `DB_URL`, sus valores pueden prevalecer sobre las variables individuales. El `.env` local no se incluye en Docker. Deja **Docker Command** vacio para usar el `CMD` del Dockerfile.

`ProductionSeeder` ejecuta `DatabaseSeeder` dentro de una transaccion. Este llama, en orden, a `UsuariosSeeder` y `DatosSeeder`, incluyendo todos los datos iniciales actuales:

- Roles, permisos y relaciones base, sin quitar permisos adicionales.
- Cuentas `admin` y `mecanico` activas, y `suspendido` inactiva, con la contraseña inicial `12345678` definida por el seeder existente.
- Propietario, vehiculo, mantenimiento, factura, repuesto, notificacion e historial de demostracion.

Los seeders usan `firstOrCreate`: si un registro ya existe segun su clave de busqueda, conservan sus valores, contraseña, estado y fechas. Las relaciones base eliminadas se vuelven a agregar al desplegar. Si se elimina un registro inicial, se crea nuevamente en el siguiente arranque. El historial reconoce tambien la entrada equivalente creada por el SQL de Neon.

Las claves de busqueda son username, nombre de rol/permiso, documento del propietario, placa, vehiculo y fecha de servicio, numero de factura, codigo de pieza, destinatario y asunto, y tipo/descripcion del historial. Editar una de estas claves puede hacer que se cree de nuevo el registro inicial; no son un historial de ejecuciones como las migraciones. Para nuevos seeders, agregarlos a `DatabaseSeeder` en orden de dependencias y mantener el comportamiento de insercion solo si faltan.

#### Transicion desde los SQL manuales

`database/neon_schema_seed.sql` es la bitacora historica del esquema con datos demo. Registra en `migrations` las migraciones hasta el modulo de clientes. `database/neon_clientes.sql` agrega ese modulo a una base existente y registra sus dos migraciones. No ejecutar estos SQL como parte del arranque: el script completo restablece cuentas demo y no incluye necesariamente los cambios posteriores.

Si Neon refleja estos scripts, Laravel omite las migraciones registradas y aplica la nueva de `tipo_servicio`. Antes del primer despliegue, se puede comprobar el historial en el editor SQL de Neon:

```sql
SELECT migration, batch FROM migrations ORDER BY migration;
SELECT column_name FROM information_schema.columns
WHERE table_schema = 'public' AND table_name = 'mantenimientos';
```

Laravel usa el historial, no la existencia de una tabla, para decidir que ejecutar. Si se hicieron cambios manuales adicionales sin registrar su migracion, hay que verificar que coincidan completamente con ella y reconciliar el historial antes de automatizar. No se marcan migraciones como ejecutadas ni se ignoran errores automaticamente.

Para cambios futuros, crear una migracion nueva; no editar una ya aplicada. Para nuevos datos iniciales, ampliar los seeders llamados por `DatabaseSeeder` manteniendo la insercion solo si faltan. No es necesario volver a ejecutar los SQL historicos.

Este arranque esta pensado para un unico contenedor web. Si se escala a varias instancias, centralizar las migraciones en un paso de despliegue o usar un bloqueo compartido para evitar ejecuciones simultaneas.
