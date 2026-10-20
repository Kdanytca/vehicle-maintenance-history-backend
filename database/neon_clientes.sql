-- Alternativa manual al despliegue automatico: solo el modulo de clientes.
-- Requiere la base existente creada con neon_schema_seed.sql.
BEGIN;

CREATE TABLE IF NOT EXISTS clientes (
    id BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    apellido VARCHAR(255) NOT NULL,
    dui VARCHAR(10) NOT NULL,
    telefono VARCHAR(15) NOT NULL,
    correo_electronico VARCHAR(255) NOT NULL,
    created_at TIMESTAMP(0),
    updated_at TIMESTAMP(0)
);

CREATE UNIQUE INDEX IF NOT EXISTS clientes_dui_unique ON clientes (dui);
CREATE UNIQUE INDEX IF NOT EXISTS clientes_correo_electronico_unique ON clientes (correo_electronico);

ALTER TABLE vehiculos ADD COLUMN IF NOT EXISTS cliente_id BIGINT;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint
        WHERE conrelid = 'vehiculos'::regclass
          AND conname = 'vehiculos_cliente_id_foreign'
    ) THEN
        ALTER TABLE vehiculos ADD CONSTRAINT vehiculos_cliente_id_foreign
            FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE;
    END IF;
END $$;

INSERT INTO migrations (migration, batch)
SELECT seed.migration, (SELECT COALESCE(MAX(batch), 0) + 1 FROM migrations)
FROM (VALUES
    ('2026_10_17_024940_create_clientes_table'),
    ('2026_10_17_025856_add_cliente_id_to_vehiculos_table')
) AS seed(migration)
WHERE NOT EXISTS (
    SELECT 1 FROM migrations WHERE migrations.migration = seed.migration
);

COMMIT;
