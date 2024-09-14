-- Tablas utilizadas en el modulo

-- Historial de precios por (bodega, prod_medida, fecha)
CREATE TABLE IF NOT EXISTS gmcv_precio (
    id_prov MEDIUMINT (6) NOT NULL,
    id_bodega SMALLINT(2) NOT NULL,
    id_prod MEDIUMINT(6) NOT NULL,
    ini DATE NOT NULL,
    id_status TINYINT(2) NOT NULL DEFAULT 0,
    precio DECIMAL(10,4) UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id_prov, id_bodega, id_prod, ini),
    CONSTRAINT `gmcvpfk_1` FOREIGN KEY (`id_prov`) REFERENCES `proveedor` (`id`) ON UPDATE CASCADE,
    CONSTRAINT `gmcvpfk_2` FOREIGN KEY (`id_bodega`) REFERENCES `bodega` (`id`) ON UPDATE CASCADE,
    CONSTRAINT `gmcvpfk_3` FOREIGN KEY (`id_prod`) REFERENCES `producto` (`id`) ON UPDATE CASCADE,
    CONSTRAINT `gmcvpfk_4` FOREIGN KEY (`id_status`) REFERENCES `sys_status` (`id`) ON UPDATE CASCADE
)ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
-- DROP TABLE gmcv_precio;
-- TRUNCATE TABLE gmcv_precio;

-- Catalogo de Descuentos 
CREATE TABLE IF NOT EXISTS gmcv_descuento (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_prov MEDIUMINT (6) NOT NULL,
    nombre VARCHAR(30),
    id_status TINYINT  NOT NULL DEFAULT 0,
    posteriorCP TINYINT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT `gmcvpfk_5` FOREIGN KEY (`id_prov`) REFERENCES `proveedor` (`id`) ON UPDATE CASCADE,
    CONSTRAINT `gmcvpfk_6` FOREIGN KEY (`id_status`) REFERENCES `sys_status` (`id`) ON UPDATE CASCADE,
    CONSTRAINT `uk_nombre_proveedor` UNIQUE (`id_prov`, `nombre`) -- Para que no permita nombres repetidos.
)ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
-- DROP TABLE gmcv_descuento;
-- TRUNCATE TABLE gmcv_descuento;

-- Relacion descuento con bodegas (Ahora son muchos a muchos)
CREATE TABLE IF NOT EXISTS gmcv_descuento_bodega (
    id_descuento INT UNSIGNED,
    id_bodega SMALLINT(2) NOT NULL,
    PRIMARY KEY (id_descuento, id_bodega),
    CONSTRAINT `gmcvpfk_7` FOREIGN KEY (`id_descuento`) REFERENCES `gmcv_descuento` (`id`) ON UPDATE CASCADE,
    CONSTRAINT `gmcvpfk_8` FOREIGN KEY (`id_bodega`) REFERENCES `bodega` (`id`) ON UPDATE CASCADE
)ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
-- DROP TABLE gmcv_descuento_bodega;
-- TRUNCATE TABLE gmcv_descuento_bodega;

-- Historial Descuentos (por descuento, por proveedor, bodega, prod_medida, fecha) 
CREATE TABLE IF NOT EXISTS gmcv_descuento_producto (
    id_descuento INT UNSIGNED NOT NULL,
    id_prov MEDIUMINT (6) NOT NULL,
    id_bodega SMALLINT(2) NOT NULL,
    id_prod MEDIUMINT(6) NOT NULL,
    ini DATE NOT NULL,
    id_status TINYINT(2) NOT NULL DEFAULT 0,
    descuento DECIMAL(8,4) UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id_descuento, id_prov, id_bodega, id_prod, ini),
    CONSTRAINT `gmcvdpfk_1` FOREIGN KEY (`id_descuento`) REFERENCES `gmcv_descuento` (`id`) ON UPDATE CASCADE,
    CONSTRAINT `gmcvdpfk_2` FOREIGN KEY (`id_prov`) REFERENCES `proveedor` (`id`) ON UPDATE CASCADE,
    CONSTRAINT `gmcvdpfk_3` FOREIGN KEY (`id_bodega`) REFERENCES `bodega` (`id`) ON UPDATE CASCADE,
    CONSTRAINT `gmcvdpfk_4` FOREIGN KEY (`id_prod`) REFERENCES `producto` (`id`) ON UPDATE CASCADE,
    CONSTRAINT `gmcvdpfk_5` FOREIGN KEY (`id_status`) REFERENCES `sys_status` (`id`) ON UPDATE CASCADE
)ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
-- DROP TABLE gmcv_descuento_producto;
-- TRUNCATE TABLE gmcv_descuento_producto;

-- ///////////////////////////////////// Conciliacion de Pagos
-- Catalogo de Pago 
CREATE TABLE IF NOT EXISTS gmcv_pago (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_proveedor MEDIUMINT (6) NOT NULL,
    uuid VARCHAR(36) NOT NULL, -- Usamos UUID
    fecha DATE NOT NULL,
    monto DECIMAL(12,4) NOT NULL DEFAULT 0, -- Monto total de pago
    id_status TINYINT  NOT NULL DEFAULT 0,
    tipo TINYINT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT `gmcvpagofk_1` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedor` (`id`) ON UPDATE CASCADE, 
    CONSTRAINT `gmcvpagofk_2` FOREIGN KEY (`id_status`) REFERENCES `sys_status` (`id`) ON UPDATE CASCADE
)ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
-- DROP TABLE gmcv_pago;
-- TRUNCATE TABLE gmcv_pago;

-- Catalogo de Facturas de Ordenes de compra.
CREATE TABLE IF NOT EXISTS gmcv_compra_factura (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_compra INT (4) UNSIGNED NOT NULL,
    id_status TINYINT  NOT NULL DEFAULT 0 ,
    uuid VARCHAR(36) NOT NULL, -- Usamos UUID
    fecha DATE NOT NULL,
    tipo TINYINT UNSIGNED NULL DEFAULT NULL,
    ajuste DECIMAL(12,4) NOT NULL DEFAULT '0',
    id_status_pago TINYINT(1) NOT NULL DEFAULT '0',
    validada TINYINT(1) NOT NULL DEFAULT '0',
    fecha_compromiso DATE NULL,
    fecha_llegada DATE NULL,
    fecha_alerta DATE NULL,
    CONSTRAINT `gmcvfacord_1` FOREIGN KEY (`id_compra`) REFERENCES `compra` (`id`) ON UPDATE CASCADE,
    CONSTRAINT `gmcvfacord_2` FOREIGN KEY (`id_status`) REFERENCES `sys_status` (`id`) ON UPDATE CASCADE
)ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
-- DROP TABLE gmcv_compra_factura;
-- TRUNCATE TABLE gmcv_compra_factura;

-- Relacion factura-pago con monto // Factura = Compra
CREATE TABLE IF NOT EXISTS gmcv_pago_factura (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_pago INT UNSIGNED NOT NULL,
    id_compra_factura INT  UNSIGNED NOT NULL,
    monto DECIMAL(12,4) NOT NULL DEFAULT 0, -- Monto asignado a factura
    CONSTRAINT `gmcvpagofk_3` FOREIGN KEY (`id_pago`) REFERENCES `gmcv_pago` (`id`) ON UPDATE CASCADE, 
    CONSTRAINT `gmcvpagofk_4` FOREIGN KEY (`id_compra_factura`) REFERENCES `gmcv_compra_factura` (`id`) ON UPDATE CASCADE
)ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
-- DROP TABLE gmcv_pago_factura;
-- TRUNCATE TABLE gmcv_pago_factura;


-- Relacion Factura de Orden de Compra -> prod_compra
CREATE TABLE IF NOT EXISTS gmcv_compra_factura_prod (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_compra_factura INT  UNSIGNED NOT NULL,
    id_prod_compra INT (4) UNSIGNED NOT NULL,
    caducidad DATE NOT NULL,
    cantidad_aceptada DECIMAL(12,4) UNSIGNED NOT NULL,
    cantidad_rechazada DECIMAL(12,4) UNSIGNED NOT NULL,
    descuento DECIMAL(12,4) UNSIGNED NOT NULL DEFAULT 0,
    descuento_porcentaje DECIMAL(12,4) UNSIGNED NOT NULL DEFAULT 0,
    ajuste_bruto DECIMAL(12,4) NOT NULL DEFAULT 0,
    costo_unitario_bruto DECIMAL(12,4) UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT `gmcvfacprod_1` FOREIGN KEY (`id_compra_factura`) REFERENCES `gmcv_compra_factura` (`id`) ON UPDATE CASCADE,
    CONSTRAINT `gmcvfacprod_2` FOREIGN KEY (`id_prod_compra`) REFERENCES `prod_compra` (`id`) ON UPDATE CASCADE
)ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
-- DROP TABLE gmcv_compra_factura_prod;
-- TRUNCATE TABLE gmcv_compra_factura_prod;

-- Descuentos Globales para gmcv_compra_factura
CREATE TABLE IF NOT EXISTS gmcv_compra_factura_desc (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_compra_factura INT  UNSIGNED NOT NULL,
    descuento DECIMAL(12,4) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Valor en monto bruto',
    nota text COLLATE utf8_unicode_ci,
    CONSTRAINT `gmcvfacdesc_1` FOREIGN KEY (`id_compra_factura`) REFERENCES `gmcv_compra_factura` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
COMMENT 'Descuentos Globales para gmcv_compra_factura';
-- DROP TABLE gmcv_compra_factura_desc;
-- TRUNCATE TABLE gmcv_compra_factura_desc;

-- Para calcular fecha_compromiso
ALTER TABLE `prove_dias` ADD COLUMN `dias_compromiso` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0' AFTER `dias`;
-- Para colocar la cantidad no recibida y por lo tanto no facturada de un producto
ALTER TABLE `prod_compra` ADD COLUMN `cant_no_recibida` DECIMAL(12,4) UNSIGNED NOT NULL DEFAULT 0 AFTER `cant_solicitada`;
ALTER TABLE `prod_compra` ADD COLUMN `prod_agregado` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `costo_unitario`;

-- Agregamos las opciones al menú
INSERT INTO `sys_menu_secciones` (`id`, `nombre`) VALUES (29, 'Gestión costos'); 
INSERT INTO `sys_menu_opciones` (`id`, `id_seccion`, `nombre`, `trayecto`) VALUES (NULL, '29', 'Costo Pactado', '/mtto/admin/gmcv/declararDescuentosGSV.php'); 
INSERT INTO `sys_menu_opciones` (`id`, `id_seccion`, `nombre`, `trayecto`) VALUES (NULL, '29', 'Precio venta', '/mtto/admin/gmcv/planificadorPrecios.php');
INSERT INTO `sys_menu_opciones` (`id`, `id_seccion`, `nombre`, `trayecto`) VALUES (NULL, '29', 'Verificar costos compras', '/mtto/admin/gmcv/validacionDeCostos.php');
INSERT INTO `sys_menu_opciones` (`id`, `id_seccion`, `nombre`, `trayecto`) VALUES (NULL, '29', 'Notas crédito', '/mtto/admin/gmcv/notasDeCredito.php');
INSERT INTO `sys_menu_opciones` (`id`, `id_seccion`, `nombre`, `trayecto`) VALUES (NULL, '29', 'Conciliación', '/mtto/admin/gmcv/conciliacionDePagos.php'); 
UPDATE `sys_menu_opciones` SET `nombre`='Entrada por proveedores V2',`trayecto`='/mtto/admin/compra/entradaPorProveedores.php?modoIngreso' WHERE id = 80;
UPDATE `sys_menu_opciones` SET `nombre`='Validar compra V2',`trayecto`='/mtto/admin/compra/entradaPorProveedores.php' WHERE id = 129;

-- 45 Dias de Compromiso para todas las Bodegas de 'La Morena'
UPDATE prove_dias SET dias_compromiso = 44 WHERE id_prov = 6; -- 15 cambios

--/////////////////////////////////////////////////////////////////////////////
--\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
--/////////////////////////////////////////////////////////////////////////////

-- Queries para borrar gmcv
-- DROP TABLE IF EXISTS gmcv_precio;
-- DROP TABLE IF EXISTS gmcv_descuento_bodega;
-- DROP TABLE IF EXISTS gmcv_descuento_producto;
-- DROP TABLE IF EXISTS gmcv_descuento;
-- DROP TABLE IF EXISTS gmcv_pago_factura;
-- DROP TABLE IF EXISTS gmcv_pago;
-- DROP TABLE IF EXISTS gmcv_compra_factura_prod;
-- DROP TABLE IF EXISTS gmcv_compra_factura;

-- SET FOREIGN_KEY_CHECKS = 0;
-- TRUNCATE TABLE gmcv_precio;
-- TRUNCATE TABLE gmcv_descuento_bodega;
-- TRUNCATE TABLE gmcv_descuento_producto;
-- TRUNCATE TABLE gmcv_descuento;
-- TRUNCATE TABLE gmcv_pago_factura;
-- TRUNCATE TABLE gmcv_pago;
-- TRUNCATE TABLE gmcv_compra_factura_prod;
-- TRUNCATE TABLE gmcv_compra_factura;
-- SET FOREIGN_KEY_CHECKS = 1;


------------------------- QUERY para llenar precios lista desde precios Venta
-- Proveedor 22, Bodega 14
-- Local P2, B1

-- Servidor local
SET @proveedor = 2; -- Henkel
SET @bodega = 1;
SET @fecha_precio = '2023-02-01';
SET @descuento = 0.99;

-- INSERT INTO gmcv_precio (id_prov, id_bodega, id_prod, ini, id_status, precio)
SELECT 
    p.id_prov,
    b.id as id_bodega,
    p.id,
    @fecha_precio,
    4,
--    po.venta,
    (((po.venta / (1 + si.iva)) - ifnull(p.litros,0) * si.iepsxl)/(1 + si.ieps)) * @descuento * pmc.cantidad/pm.cant_unid_min as poVentaBruto
FROM prod_medida pm
JOIN prod_oficina po ON (pm.id = po.id_prod && po.id_status = 4)
JOIN bod_oficina bo ON (po.id_oficina = bo.id_oficina)
JOIN bodega b ON (bo.id_bodega = b.id && b.id_status = 4 && b.id IN (@bodega))
JOIN producto p ON (p.id = pm.id_prod AND p.id_tipo_venta = 1 AND p.id_prov IN (@proveedor))
JOIN prod_medida_compra pmc ON (pmc.id_prod = pm.id_prod)
JOIN sys_impuestos si on (p.id_impuesto = si.id)
WHERE pm.id_status = 4 && pm.cant_unid_min = 1
GROUP BY b.id, pm.id_prod;

-- Version 2

-- Toma el valor de las compras de todas las bodegas y le reduce los descuentos pronosticados
-- Aun cuando el el producto esté relacionado a una bodega, es probable que no venga con precio.

-- Servidor test
SET @proveedor = 22; -- Unilever
SET @ini = '2023-02-01';
SET @fin = '2023-03-31 23:59:59';
SET @fecha_precio = '2023-02-01';
SET @descuento = 0.93; -- Agregar descuentos 3, 1, 3.... in un off de 2

-- Servidor local
SET @proveedor = 2; -- Henkel
SET @ini = '2019-01-01';
SET @fin = '2019-10-31 23:59:59';
SET @fecha_precio = '2019-11-01';
SET @descuento = 0.93; -- Agregar descuentos 3, 1, 3.... in un off de 2

-- INSERT INTO gmcv_precio
SELECT 
    c.id_prov,
    c.id_bodega,
    pc.id_prod,
    @fecha_precio,
    4,
    (((pc.costo_unitario / (1 + si.iva)) - ifnull(p.litros,0) * si.iepsxl)/(1 + si.ieps)) /@descuento
FROM `compra` c 
join prod_compra pc on c.id = pc.id_compra 
JOIN producto p ON (p.id = pc.id_prod)
JOIN sys_impuestos si on (p.id_impuesto = si.id)
WHERE c.llegada between @ini and @fin
    && c.id_prov = @proveedor && c.id_status in (1,4,23) && c.tipo in (1,4) 
group by pc.id_prod, c.id_bodega;



-- ///////////////////////////////////////////////////////////////////////////// Para homologar las facturas

SET @fecha = '2022-10-01'; -- Testing
SET @fecha = '2019-01-01'; -- Local

select c.id, concat(upper(trim(left(prov.corto,3))),RIGHT(concat('0000000',c.id),7)) ni from 
-- update 
compra c 
join prod_compra pc on c.id = pc.id_compra
join almacen a on pc.id = a.id_compra
join proveedor prov on c.id_prov = prov.id
-- set a.factura = concat(upper(trim(left(prov.corto,3))),RIGHT(concat('0000000',c.id),7))
where c.alta > @fecha && c.tipo in (1,4);


-- /////////////////////////////////////////////////////////// Inicializamos las fechas compromiso
-- Asignamos entre 20 y 40 dias random para compromiso por Bodega.
SELECT * from prove_dias;
UPDATE prove_dias set dias_compromiso = (20 + RAND() * 20) limit 1000;

-- Revisamos las fechas actuales de compromiso
SELECT compra.llegada, compra.fecha_compromiso, pd.dias_compromiso, DATE_ADD(compra.llegada, INTERVAL pd.dias_compromiso DAY) as nueva_fecha_compromiso
FROM compra
JOIN prove_dias pd ON (pd.id_bodega = compra.id_bodega && pd.id_prov = compra.id_prov);

-- Actualizamos las fechas de compromiso.
UPDATE compra 
JOIN prove_dias pd ON (pd.id_bodega = compra.id_bodega && pd.id_prov = compra.id_prov)
set fecha_compromiso = DATE_ADD(compra.llegada, INTERVAL pd.dias_compromiso DAY);



-- /////////////////////////////////////////////////////////////////////////////////Para re-setear la db gmcv
-- SET FOREIGN_KEY_CHECKS = 0; 
-- TRUNCATE TABLE gmcv_precio;
-- TRUNCATE TABLE gmcv_descuento;
-- TRUNCATE TABLE gmcv_descuento_bodega;
-- TRUNCATE TABLE gmcv_descuento_producto;
-- TRUNCATE TABLE gmcv_nota_credito; 
-- TRUNCATE TABLE gmcv_nota_credito_factura;
-- TRUNCATE TABLE gmcv_pago;
-- TRUNCATE TABLE gmcv_pago_factura;
-- SET FOREIGN_KEY_CHECKS = 1; 


