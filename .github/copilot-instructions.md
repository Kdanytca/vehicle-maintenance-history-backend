# Contexto del backend

- API de mantenimiento de vehículos con Laravel 12, PHP >= 8.2 y autenticación Laravel Sanctum.
- Revisa las rutas existentes en `routes/api.php`, los controladores en `app/Http/Controllers` y los modelos en `app/Models` antes de proponer código.
- Conserva los nombres de dominio en español, las claves primarias personalizadas, las relaciones Eloquent y los contratos JSON existentes. Comprueba los nombres de columnas en las migraciones; no los deduzcas del nombre del modelo.
- Utiliza las convenciones de validación, autorización y manejo de errores del controlador relacionado.
- Propón cambios pequeños y localizados. No reformatees archivos ajenos a la tarea ni ejecutes migraciones automáticamente.
- El formato PHP lo gestiona Laravel Pint y `.editorconfig`. Mantén cuatro espacios.
- El frontend Vue está en el repositorio hermano `../vehicle-maintenance-history-frontend`; revisa los consumidores antes de cambiar una respuesta de la API.
