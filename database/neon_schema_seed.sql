BEGIN;

CREATE TABLE IF NOT EXISTS migrations (
    id BIGSERIAL PRIMARY KEY,
    migration VARCHAR(255) NOT NULL,
    batch INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS roles (
    id_rol BIGSERIAL PRIMARY KEY,
    nombre_rol VARCHAR(50) NOT NULL,
    descripcion VARCHAR(255),
    created_at TIMESTAMP(0),
    updated_at TIMESTAMP(0)
);

CREATE UNIQUE INDEX IF NOT EXISTS roles_nombre_rol_unique ON roles (nombre_rol);

CREATE TABLE IF NOT EXISTS permisos (
    id_permiso BIGSERIAL PRIMARY KEY,
    nombre_permiso VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255),
    created_at TIMESTAMP(0),
    updated_at TIMESTAMP(0)
);

CREATE UNIQUE INDEX IF NOT EXISTS permisos_nombre_permiso_unique ON permisos (nombre_permiso);

CREATE TABLE IF NOT EXISTS propietarios (
    id_propietario BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    documento_identidad VARCHAR(20) NOT NULL,
    telefono VARCHAR(20),
    correo VARCHAR(100),
    created_at TIMESTAMP(0),
    updated_at TIMESTAMP(0)
);

CREATE UNIQUE INDEX IF NOT EXISTS propietarios_documento_identidad_unique ON propietarios (documento_identidad);

CREATE TABLE IF NOT EXISTS notificaciones (
    id_notificacion BIGSERIAL PRIMARY KEY,
    destinatario VARCHAR(100) NOT NULL,
    asunto VARCHAR(150) NOT NULL,
    mensaje TEXT NOT NULL,
    tipo_envio VARCHAR(20) NOT NULL,
    fecha_envio TIMESTAMP(0),
    created_at TIMESTAMP(0),
    updated_at TIMESTAMP(0)
);

CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario BIGSERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100),
    estado_activo BOOLEAN NOT NULL DEFAULT TRUE,
    id_rol BIGINT,
    created_at TIMESTAMP(0),
    updated_at TIMESTAMP(0),
    CONSTRAINT usuarios_id_rol_foreign FOREIGN KEY (id_rol) REFERENCES roles(id_rol) ON DELETE SET NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS usuarios_username_unique ON usuarios (username);

CREATE TABLE IF NOT EXISTS roles_permisos (
    id_rol BIGINT NOT NULL,
    id_permiso BIGINT NOT NULL,
    PRIMARY KEY (id_rol, id_permiso),
    CONSTRAINT roles_permisos_id_rol_foreign FOREIGN KEY (id_rol) REFERENCES roles(id_rol) ON DELETE CASCADE,
    CONSTRAINT roles_permisos_id_permiso_foreign FOREIGN KEY (id_permiso) REFERENCES permisos(id_permiso) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS vehiculos (
    id_vehiculo BIGSERIAL PRIMARY KEY,
    placa VARCHAR(15) NOT NULL,
    marca VARCHAR(50) NOT NULL,
    modelo VARCHAR(50) NOT NULL,
    anio INTEGER,
    id_propietario BIGINT,
    created_at TIMESTAMP(0),
    updated_at TIMESTAMP(0),
    CONSTRAINT vehiculos_id_propietario_foreign FOREIGN KEY (id_propietario) REFERENCES propietarios(id_propietario) ON DELETE SET NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS vehiculos_placa_unique ON vehiculos (placa);

CREATE TABLE IF NOT EXISTS mantenimientos (
    id_mantenimiento BIGSERIAL PRIMARY KEY,
    fecha_servicio DATE NOT NULL,
    descripcion_falla TEXT NOT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'Pendiente',
    costo_mano_obra NUMERIC(10, 2) NOT NULL DEFAULT 0.00,
    id_vehiculo BIGINT,
    id_usuario_encargado BIGINT NOT NULL,
    created_at TIMESTAMP(0),
    updated_at TIMESTAMP(0),
    CONSTRAINT mantenimientos_id_vehiculo_foreign FOREIGN KEY (id_vehiculo) REFERENCES vehiculos(id_vehiculo) ON DELETE CASCADE,
    CONSTRAINT mantenimientos_id_usuario_encargado_foreign FOREIGN KEY (id_usuario_encargado) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS facturas (
    id_factura BIGSERIAL PRIMARY KEY,
    numero_factura VARCHAR(50) NOT NULL,
    fecha_emision DATE NOT NULL,
    monto_total NUMERIC(10, 2) NOT NULL,
    ruta_pdf_almacenamiento VARCHAR(255) NOT NULL,
    id_vehiculo BIGINT NOT NULL,
    created_at TIMESTAMP(0),
    updated_at TIMESTAMP(0),
    CONSTRAINT facturas_id_vehiculo_foreign FOREIGN KEY (id_vehiculo) REFERENCES vehiculos(id_vehiculo) ON DELETE CASCADE
);

CREATE UNIQUE INDEX IF NOT EXISTS facturas_numero_factura_unique ON facturas (numero_factura);

CREATE TABLE IF NOT EXISTS repuestos (
    id_repuesto BIGSERIAL PRIMARY KEY,
    nombre_pieza VARCHAR(100) NOT NULL,
    codigo_pieza VARCHAR(50),
    costo_unitario NUMERIC(10, 2) NOT NULL,
    id_mantenimiento BIGINT,
    id_factura BIGINT,
    created_at TIMESTAMP(0),
    updated_at TIMESTAMP(0),
    CONSTRAINT repuestos_id_mantenimiento_foreign FOREIGN KEY (id_mantenimiento) REFERENCES mantenimientos(id_mantenimiento) ON DELETE SET NULL,
    CONSTRAINT repuestos_id_factura_foreign FOREIGN KEY (id_factura) REFERENCES facturas(id_factura) ON DELETE SET NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS repuestos_codigo_pieza_unique ON repuestos (codigo_pieza) WHERE codigo_pieza IS NOT NULL;

CREATE TABLE IF NOT EXISTS historial_cambios (
    id_auditoria BIGSERIAL PRIMARY KEY,
    tipo_evento VARCHAR(50) NOT NULL,
    descripcion_evento TEXT NOT NULL,
    direccion_ip VARCHAR(45),
    id_usuario BIGINT,
    created_at TIMESTAMP(0),
    updated_at TIMESTAMP(0),
    CONSTRAINT historial_cambios_id_usuario_foreign FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id BIGINT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    payload TEXT NOT NULL,
    last_activity INTEGER NOT NULL
);

CREATE INDEX IF NOT EXISTS sessions_user_id_index ON sessions (user_id);
CREATE INDEX IF NOT EXISTS sessions_last_activity_index ON sessions (last_activity);

CREATE TABLE IF NOT EXISTS cache (
    key VARCHAR(255) PRIMARY KEY,
    value TEXT NOT NULL,
    expiration BIGINT NOT NULL
);

CREATE INDEX IF NOT EXISTS cache_expiration_index ON cache (expiration);

CREATE TABLE IF NOT EXISTS cache_locks (
    key VARCHAR(255) PRIMARY KEY,
    owner VARCHAR(255) NOT NULL,
    expiration BIGINT NOT NULL
);

CREATE INDEX IF NOT EXISTS cache_locks_expiration_index ON cache_locks (expiration);

CREATE TABLE IF NOT EXISTS personal_access_tokens (
    id BIGSERIAL PRIMARY KEY,
    tokenable_type VARCHAR(255) NOT NULL,
    tokenable_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    token VARCHAR(64) NOT NULL,
    abilities TEXT,
    last_used_at TIMESTAMP(0),
    expires_at TIMESTAMP(0),
    created_at TIMESTAMP(0),
    updated_at TIMESTAMP(0)
);

CREATE INDEX IF NOT EXISTS personal_access_tokens_tokenable_type_tokenable_id_index ON personal_access_tokens (tokenable_type, tokenable_id);
CREATE UNIQUE INDEX IF NOT EXISTS personal_access_tokens_token_unique ON personal_access_tokens (token);

INSERT INTO migrations (migration, batch)
SELECT migration, 1
FROM (VALUES
    ('2026_05_27_225714_create_roles_table'),
    ('2026_05_27_225715_create_permisos_table'),
    ('2026_05_27_225715_create_propietarios_table'),
    ('2026_05_27_225717_create_notificaciones_table'),
    ('2026_05_27_225718_create_roles_permisos_table'),
    ('2026_05_27_225718_create_usuarios_table'),
    ('2026_05_27_225718_create_vehiculos_table'),
    ('2026_05_27_225719_create_mantenimientos_table'),
    ('2026_05_27_225720_create_repuestos_table'),
    ('2026_05_27_225723_create_historial_cambios_table'),
    ('2026_06_02_000516_create_sessions_table'),
    ('2026_06_02_004805_create_cache_table'),
    ('2026_06_02_225717_create_facturas_table'),
    ('2026_08_25_000000_create_personal_access_tokens_table')
) AS seed(migration)
WHERE NOT EXISTS (
    SELECT 1 FROM migrations WHERE migrations.migration = seed.migration
);

INSERT INTO roles (nombre_rol, descripcion, created_at, updated_at) VALUES
    ('admin', 'Administrador del sistema', NOW(), NOW()),
    ('mecanico', 'Mecánico del taller', NOW(), NOW())
ON CONFLICT (nombre_rol) DO UPDATE SET
    descripcion = EXCLUDED.descripcion,
    updated_at = NOW();

INSERT INTO permisos (nombre_permiso, descripcion, created_at, updated_at) VALUES
    ('gestionar_usuarios', 'Crear y administrar usuarios del sistema', NOW(), NOW()),
    ('gestionar_roles', 'Crear roles y asignar permisos', NOW(), NOW()),
    ('gestionar_vehiculos', 'Registrar y consultar vehículos', NOW(), NOW()),
    ('gestionar_mantenimientos', 'Registrar mantenimientos y consultar historial', NOW(), NOW()),
    ('gestionar_repuestos', 'Cargar facturas, repuestos y alertas', NOW(), NOW())
ON CONFLICT (nombre_permiso) DO UPDATE SET
    descripcion = EXCLUDED.descripcion,
    updated_at = NOW();

INSERT INTO roles_permisos (id_rol, id_permiso)
SELECT roles.id_rol, permisos.id_permiso
FROM roles
CROSS JOIN permisos
WHERE roles.nombre_rol = 'admin'
ON CONFLICT DO NOTHING;

INSERT INTO roles_permisos (id_rol, id_permiso)
SELECT roles.id_rol, permisos.id_permiso
FROM roles
CROSS JOIN permisos
WHERE roles.nombre_rol = 'mecanico'
AND permisos.nombre_permiso IN ('gestionar_vehiculos', 'gestionar_mantenimientos', 'gestionar_repuestos')
ON CONFLICT DO NOTHING;

INSERT INTO usuarios (username, password_hash, estado_activo, id_rol, created_at, updated_at)
SELECT 'admin', '$2y$12$92pgk0wal5DqgcQej0XnZ.9MTzeLGdupTtY/R0JFwlgCpTTuV5Y72', TRUE, id_rol, NOW(), NOW()
FROM roles WHERE nombre_rol = 'admin'
ON CONFLICT (username) DO UPDATE SET
    password_hash = EXCLUDED.password_hash,
    estado_activo = TRUE,
    id_rol = EXCLUDED.id_rol,
    updated_at = NOW();

INSERT INTO usuarios (username, password_hash, estado_activo, id_rol, created_at, updated_at)
SELECT 'mecanico', '$2y$12$92pgk0wal5DqgcQej0XnZ.9MTzeLGdupTtY/R0JFwlgCpTTuV5Y72', TRUE, id_rol, NOW(), NOW()
FROM roles WHERE nombre_rol = 'mecanico'
ON CONFLICT (username) DO UPDATE SET
    password_hash = EXCLUDED.password_hash,
    estado_activo = TRUE,
    id_rol = EXCLUDED.id_rol,
    updated_at = NOW();

INSERT INTO usuarios (username, password_hash, estado_activo, id_rol, created_at, updated_at)
SELECT 'suspendido', '$2y$12$92pgk0wal5DqgcQej0XnZ.9MTzeLGdupTtY/R0JFwlgCpTTuV5Y72', FALSE, id_rol, NOW(), NOW()
FROM roles WHERE nombre_rol = 'mecanico'
ON CONFLICT (username) DO UPDATE SET
    password_hash = EXCLUDED.password_hash,
    estado_activo = FALSE,
    id_rol = EXCLUDED.id_rol,
    updated_at = NOW();

INSERT INTO propietarios (nombre, documento_identidad, telefono, correo, created_at, updated_at) VALUES
    ('Carlos Martinez', '01234567-8', '7777-1111', 'carlos.demo@example.com', NOW(), NOW())
ON CONFLICT (documento_identidad) DO UPDATE SET
    nombre = EXCLUDED.nombre,
    telefono = EXCLUDED.telefono,
    correo = EXCLUDED.correo,
    updated_at = NOW();

INSERT INTO vehiculos (placa, marca, modelo, anio, id_propietario, created_at, updated_at)
SELECT 'P-123456', 'Toyota', 'Corolla', 2020, id_propietario, NOW(), NOW()
FROM propietarios WHERE documento_identidad = '01234567-8'
ON CONFLICT (placa) DO UPDATE SET
    marca = EXCLUDED.marca,
    modelo = EXCLUDED.modelo,
    anio = EXCLUDED.anio,
    id_propietario = EXCLUDED.id_propietario,
    updated_at = NOW();

INSERT INTO mantenimientos (fecha_servicio, descripcion_falla, estado, costo_mano_obra, id_vehiculo, id_usuario_encargado, created_at, updated_at)
SELECT DATE '2026-09-09', 'Revision general de demostracion', 'En Proceso', 60.00, vehiculos.id_vehiculo, usuarios.id_usuario, NOW(), NOW()
FROM vehiculos
CROSS JOIN usuarios
WHERE vehiculos.placa = 'P-123456'
AND usuarios.username = 'mecanico'
AND NOT EXISTS (
    SELECT 1 FROM mantenimientos WHERE id_vehiculo = vehiculos.id_vehiculo AND fecha_servicio = DATE '2026-09-09'
);

INSERT INTO facturas (numero_factura, fecha_emision, monto_total, ruta_pdf_almacenamiento, id_vehiculo, created_at, updated_at)
SELECT 'FAC-DEMO-001', DATE '2026-09-09', 38.50, 'facturas/demo-fac-001.pdf', id_vehiculo, NOW(), NOW()
FROM vehiculos WHERE placa = 'P-123456'
ON CONFLICT (numero_factura) DO UPDATE SET
    fecha_emision = EXCLUDED.fecha_emision,
    monto_total = EXCLUDED.monto_total,
    ruta_pdf_almacenamiento = EXCLUDED.ruta_pdf_almacenamiento,
    id_vehiculo = EXCLUDED.id_vehiculo,
    updated_at = NOW();

INSERT INTO repuestos (nombre_pieza, codigo_pieza, costo_unitario, id_mantenimiento, id_factura, created_at, updated_at)
SELECT 'Filtro de Aceite Demo', 'REP-DEMO-001', 38.50, mantenimientos.id_mantenimiento, facturas.id_factura, NOW(), NOW()
FROM mantenimientos
CROSS JOIN facturas
JOIN vehiculos ON vehiculos.id_vehiculo = mantenimientos.id_vehiculo
WHERE vehiculos.placa = 'P-123456'
AND mantenimientos.fecha_servicio = DATE '2026-09-09'
AND facturas.numero_factura = 'FAC-DEMO-001'
ON CONFLICT (codigo_pieza) WHERE codigo_pieza IS NOT NULL DO UPDATE SET
    nombre_pieza = EXCLUDED.nombre_pieza,
    costo_unitario = EXCLUDED.costo_unitario,
    id_mantenimiento = EXCLUDED.id_mantenimiento,
    id_factura = EXCLUDED.id_factura,
    updated_at = NOW();

INSERT INTO notificaciones (destinatario, asunto, mensaje, tipo_envio, fecha_envio, created_at, updated_at)
SELECT correo, 'Servicio de demostracion registrado', 'Su vehiculo P-123456 tiene un mantenimiento de demostracion en proceso.', 'AUTOMÁTICO', NOW(), NOW(), NOW()
FROM propietarios
WHERE documento_identidad = '01234567-8'
AND NOT EXISTS (
    SELECT 1 FROM notificaciones WHERE destinatario = propietarios.correo AND asunto = 'Servicio de demostracion registrado'
);

INSERT INTO historial_cambios (tipo_evento, descripcion_evento, direccion_ip, id_usuario, created_at, updated_at)
SELECT 'LOG_SISTEMA', 'Datos de demostracion cargados para el vehiculo P-123456 desde Neon SQL.', '127.0.0.1', id_usuario, NOW(), NOW()
FROM usuarios
WHERE username = 'mecanico'
AND NOT EXISTS (
    SELECT 1 FROM historial_cambios WHERE tipo_evento = 'LOG_SISTEMA' AND descripcion_evento = 'Datos de demostracion cargados para el vehiculo P-123456 desde Neon SQL.'
);

COMMIT;

SELECT 'Tablas y datos demo creados correctamente' AS resultado;
SELECT username, estado_activo, roles.nombre_rol
FROM usuarios
LEFT JOIN roles ON roles.id_rol = usuarios.id_rol
ORDER BY usuarios.id_usuario;
