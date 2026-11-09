-- ============================================================================
-- Consultas de demostración de la base de datos technosolutions_db
-- Uso:  mysql -u ts_app -p technosolutions_db --table < consultas_demo.sql
-- Todas son de solo lectura, salvo el bloque 8 que termina en ROLLBACK (no deja cambios).
-- ============================================================================

-- 1. Tablas de la base de datos
SHOW TABLES;

-- 2. Estructura de la tabla de productos
DESCRIBE productos;

-- 3. Usuarios con su rol (la contraseña se guarda cifrada: solo se muestra el inicio del hash)
SELECT u.nombre, r.nombre AS rol, u.activo, LEFT(u.password, 7) AS inicio_hash, LENGTH(u.password) AS largo_hash
FROM usuarios u JOIN roles r ON r.id = u.id_rol;

-- 4. Stock por producto y bodega (relación muchos a muchos resuelta en stock_bodega)
SELECT p.nombre AS producto, b.nombre AS bodega, s.cantidad
FROM stock_bodega s
JOIN productos p ON p.id = s.id_producto
JOIN bodegas b   ON b.id = s.id_bodega
ORDER BY p.nombre, b.nombre;

-- 5. Stock total y estado de cada producto (alerta de stock bajo)
SELECT p.nombre, p.stock_minimo AS minimo, COALESCE(SUM(s.cantidad), 0) AS stock_total,
       CASE WHEN COALESCE(SUM(s.cantidad), 0) = 0 THEN 'Sin stock'
            WHEN COALESCE(SUM(s.cantidad), 0) <= p.stock_minimo THEN 'Stock bajo'
            ELSE 'Normal' END AS estado
FROM productos p LEFT JOIN stock_bodega s ON s.id_producto = p.id
WHERE p.activo = 1 GROUP BY p.id ORDER BY p.nombre;

-- 6. Últimos movimientos con el usuario que los registró
SELECT m.id, m.tipo, p.nombre AS producto, m.cantidad, u.nombre AS usuario
FROM movimientos m
JOIN productos p ON p.id = m.id_producto
JOIN usuarios u  ON u.id = m.id_usuario
ORDER BY m.id DESC LIMIT 5;

-- 7. Verificación de integridad: el stock actual debe coincidir con lo que dice el historial
SELECT p.nombre,
       (SELECT COALESCE(SUM(CASE m.tipo WHEN 'entrada' THEN m.cantidad WHEN 'salida' THEN -m.cantidad ELSE 0 END), 0)
          FROM movimientos m WHERE m.id_producto = p.id) AS segun_historial,
       (SELECT COALESCE(SUM(s.cantidad), 0) FROM stock_bodega s WHERE s.id_producto = p.id) AS stock_actual
FROM productos p ORDER BY p.nombre;
SELECT COUNT(*) AS filas_con_stock_negativo FROM stock_bodega WHERE cantidad < 0;

-- 8. Transacción de ejemplo: se descuenta 1 unidad, se consulta y se DESHACE (no queda ningún cambio)
START TRANSACTION;
UPDATE stock_bodega SET cantidad = cantidad - 1 WHERE id_producto = 1 AND id_bodega = 1;
SELECT 'dentro de la transacción' AS momento, cantidad FROM stock_bodega WHERE id_producto = 1 AND id_bodega = 1;
ROLLBACK;
SELECT 'después del ROLLBACK' AS momento, cantidad FROM stock_bodega WHERE id_producto = 1 AND id_bodega = 1;
