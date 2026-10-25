-- Datos de demostración. Los marcadores __...__ los reemplaza instalar_linux_mint.sh (correos y hashes de contraseña).
INSERT IGNORE INTO roles (id, nombre) VALUES (1,'Administrador'),(2,'Vendedor'),(3,'Bodeguero');
INSERT INTO usuarios (id, nombre, email, password, id_rol, activo) VALUES
 (1,'Administrador General','__ADMIN_EMAIL__','__HASH_ADMIN__',1,1),
 (2,'Laura Martínez','__VENDEDOR_EMAIL__','__HASH_VENDEDOR__',2,1),
 (3,'Carlos Rojas','__BODEGUERO_EMAIL__','__HASH_BODEGUERO__',3,1);
INSERT INTO bodegas (id, nombre, direccion, activa) VALUES
 (1,'Bodega Central','Calle 10 # 5-20, Bogotá',1),
 (2,'Bodega Norte','Carrera 15 # 100-30, Bogotá',1),
 (3,'Bodega Sur','Autopista Sur # 45-12, Bogotá',1);
INSERT INTO productos (id, codigo_barras, nombre, descripcion, precio, stock_minimo) VALUES
 (1,'7701234500011','Laptop HP ProBook 450 G9','Intel Core i5, 16 GB RAM, SSD 512 GB',3450000,5),
 (2,'7701234500028','Mouse inalámbrico Logitech M170','Receptor USB, 2.4 GHz',45000,10),
 (3,'7701234500035','Teclado mecánico Redragon K552','Switch rojo, retroiluminado',189000,6),
 (4,'7701234500042','Monitor Samsung 24" Full HD','Panel IPS, HDMI',620000,4),
 (5,'7701234500059','Impresora Epson EcoTank L3250','Multifuncional con Wi-Fi',980000,3),
 (6,'7701234500066','Cable HDMI 2 m','Alta velocidad 4K',22000,15);
INSERT INTO stock_bodega (id_producto, id_bodega, cantidad) VALUES
 (1,1,9),(2,1,32),(3,2,3),(4,1,5),(4,2,4),(5,3,2),(6,1,60);
INSERT INTO movimientos (tipo, id_producto, id_bodega_origen, id_bodega_destino, cantidad, id_usuario, observacion, fecha) VALUES
 ('entrada',1,NULL,1,12,1,'Compra a proveedor HP, factura 0412',NOW() - INTERVAL 9 HOUR),
 ('entrada',2,NULL,1,40,1,'Compra a proveedor Logitech, factura 0413',NOW() - INTERVAL 8 HOUR),
 ('entrada',3,NULL,2,3,1,'Compra a proveedor Redragon, factura 0414',NOW() - INTERVAL 7 HOUR),
 ('entrada',4,NULL,1,9,1,'Compra a proveedor Samsung, factura 0415',NOW() - INTERVAL 6 HOUR),
 ('entrada',5,NULL,3,2,1,'Compra a proveedor Epson, factura 0416',NOW() - INTERVAL 5 HOUR),
 ('entrada',6,NULL,1,60,1,'Compra de insumos, factura 0417',NOW() - INTERVAL 4 HOUR),
 ('salida',2,1,NULL,8,1,'Venta a cliente Juan Pérez',NOW() - INTERVAL 3 HOUR),
 ('salida',1,1,NULL,3,1,'Venta a cliente Distribuciones Andina',NOW() - INTERVAL 2 HOUR),
 ('traslado',4,1,2,4,1,'Reposición sede Norte',NOW() - INTERVAL 1 HOUR);
