-- Agregar campo cant_facturada a la tabla gmcv_compra_factura_prod para saber la cantidad facturada de un producto en una factura
ALTER TABLE gmcv_compra_factura_prod ADD cantidad_facturada DECIMAL(12,4) NOT NULL DEFAULT '0.0000' ;
-- Hacemos unico el campo uuid en la tabla gmcv_compra_factura_prod
ALTER TABLE gmcv_compra_factura ADD UNIQUE (uuid);
-- Agregamos un campo que nos permita saber si ya tuvo un ingreso parcial de mercancia
ALTER TABLE gmcv_compra_factura ADD ingreso_almacen tinyint(1) NOT NULL DEFAULT '0';
-- Agregamos campos para separar los ajustes de la factura con respecto a los descuentos
ALTER TABLE gmcv_compra_factura ADD tipoAjuste VARCHAR(50) NOT NULL DEFAULT '';




-- Agregamos las opciones al menu de gestion de costos
INSERT INTO sys_menu_opciones (id, id_seccion, nombre, trayecto) VALUES (NULL, '29', 'Entrada por proveedores v3', '/mtto/admin/gmcv/entradasPP.php');
INSERT INTO sys_menu_opciones (id, id_seccion, nombre, trayecto) VALUES (NULL, '29', 'Validación de Entradas v3', '/mtto/admin/gmcv/validaEntradas.php');
INSERT INTO sys_menu_opciones (id, id_seccion, nombre, trayecto) VALUES (NULL, '29', 'Validación de Costos v3', '/mtto/admin/gmcv/validaCostos.php');
INSERT INTO sys_menu_opciones (id, id_seccion, nombre, trayecto) VALUES (NULL, '29', 'Planificador Precio Lista v3', '/mtto/admin/gmcv/preciosLista.php');
INSERT INTO sys_menu_opciones (id, id_seccion, nombre, trayecto) VALUES (NULL, '29', 'Planificador Precio Venta v3', '/mtto/admin/gmcv/preciosVenta.php');
INSERT INTO sys_menu_opciones (id, id_seccion, nombre, trayecto) VALUES (NULL, '29', 'Conciliacion de Pagos v2', '/mtto/admin/gmcv/conciliacionPagos.php');





-- Columnas que fueron agregadas a la tabla gmcv_compra_factura y seran eliminadas
--Agregamos nuevos campos, estos contendran los totales de la factura
ALTER TABLE gmcv_compra_factura ADD total DECIMAL(12,4) DEFAULT '0.0000' AFTER ajuste;
ALTER TABLE gmcv_compra_factura ADD total_iva DECIMAL(12,4) DEFAULT '0.0000' AFTER total;
ALTER TABLE gmcv_compra_factura ADD total_ieps DECIMAL(12,4)  DEFAULT '0.0000' AFTER total_iva;


-- Agregamos campos para separar los ajustes de la factura con respecto a los descuentos
ALTER TABLE gmcv_compra_factura  
ADD subTotalBruto DECIMAL(12,4) NOT NULL DEFAULT '0.0000' AFTER total_ieps,
ADD subTotalNeto DECIMAL(12,4) NOT NULL DEFAULT '0.0000' AFTER subTotalBruto,
ADD descRechazo DECIMAL(12,4) NOT NULL DEFAULT '0.0000' AFTER subTotalNeto,
ADD descGlobal DECIMAL(12,4) NOT NULL DEFAULT '0.0000' AFTER descRechazo,
ADD descGlobalJSON TEXT NULL AFTER descGlobal,
ADD ivaTraslado DECIMAL(12,4) NOT NULL DEFAULT '0.0000' AFTER descGlobalJSON,
ADD iepsTraslado DECIMAL(12,4) NOT NULL DEFAULT '0.0000' AFTER ivaTraslado,
ADD totalAPagar DECIMAL(12,4) NOT NULL DEFAULT '0.0000' AFTER iepsTraslado;



--Query para eliminar las columnas que no se usaran
ALTER TABLE gmcv_compra_factura 
DROP COLUMN total,
DROP COLUMN total_iva,
DROP COLUMN total_ieps,
DROP COLUMN subTotalBruto,
DROP COLUMN subTotalNeto,
DROP COLUMN descRechazo,
DROP COLUMN descGlobal,
DROP COLUMN descGlobalJSON,
DROP COLUMN ivaTraslado,
DROP COLUMN iepsTraslado,
DROP COLUMN totalAPagar;