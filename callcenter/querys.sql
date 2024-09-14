--  Tablas del menu: 
/*
Lo prime eo que hice fue ir a PUESTOS y crear 2 puestos nuevos: Operador y Supervisor
Luego fui a PERSONAL y cree 3 usuarios nuevos, 2 con el puesto de Operador y otro con el puesto de Supervisor
luego di de alta la seccion de Call Center y la opcion de Mis Rutas en el menu de la aplicacion
Cree las tablas de callcenter_incidencias, callcenter_usuariosruta, callcenter_incidenciasclientes, callcenter_incidenciaspedido, callcenter_pedidos
y agregue el campo id_usocfdi en la tabla cte_fact
    * Agregue un par de capturas de pantalla de como se ven los puestos y el edificio de call center

*/

-- Primero se inserta la seccion
INSERT INTO `sys_menu_secciones` (`id`, `nombre`, `id_icono`) VALUES (NULL, 'Call Center', '254'); -- ID: 32
-- Cambiar por el ID de de produccion
INSERT INTO `sys_menu_opciones` (`id`, `id_seccion`, `nombre`, `trayecto`) VALUES (NULL, '32', 'Mis Rutas', '	\r\n/mtto/admin/callcenter/misRutas.php'); -- ID 352

INSERT INTO `perfil_menu` (`id`, `id_perfil`, `id_opcion`) VALUES (NULL, '2', '352');

-- Agremos el nuevo tipo de pedido (ya no se requiere, pues implicaria cambiar el sistema de pedidos por lo que se opto por no hacerlo)
--INSERT INTO `sys_tipo_pedido` (`id`, `nombre`) VALUES (NULL, 'CC_enter');

--Agregamos el nuevo campo en la tabla de cte_fact
ALTER TABLE cte_fact ADD COLUMN id_usocfdi INT NULL, ADD FOREIGN KEY (id_usocfdi) REFERENCES sys_uso_cfdi(id);
-- Modificamos algunos campos de la tabla cte_fact
ALTER TABLE `cte_fact` CHANGE `calle` `calle` VARCHAR(60) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL;
ALTER TABLE `cte_fact` CHANGE `numext` `numext` VARCHAR(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL;
ALTER TABLE `cte_fact` CHANGE `numint` `numint` VARCHAR(25) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL;
ALTER TABLE `cte_fact` CHANGE `col` `col` VARCHAR(35) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL;
ALTER TABLE `cte_fact` CHANGE `cd` `cd` VARCHAR(35) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL;
ALTER TABLE `cte_fact` CHANGE `edo` `edo` VARCHAR(25) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL;


-- Cremos una tabla para almacener el catalogo de incidencias del call center
CREATE TABLE `callcenter_incidencias` (
    `id` smallint(6) NOT NULL AUTO_INCREMENT,
    `nombre` varchar(100) NOT NULL,
    PRIMARY KEY (`id`)
) COMMENT='Tabla para almacenar el catálogo de incidencias del call center' ENGINE=InnoDB DEFAULT CHARSET=utf8;
-- insertamos el catalogo de incidencias
INSERT INTO `callcenter_incidencias` (`id`, `nombre`) VALUES
(1, 'Sin Movimientos'),
(2, 'Pedido Realizado'),
(3, 'Marqué y no contesta'),
(4, 'Marque después'),
(5, 'No levanto pedido'),
(6, 'Número equivocado');

-- Creamos una tabla que almacene la relacion de rutas y los usuarios de tipo call center
CREATE TABLE `callcenter_usuariosruta` (
    `id` mediumint(6) NOT NULL AUTO_INCREMENT,
    `id_usuario` mediumint(6) NOT NULL,
    `id_ruta` smallint(6) NOT NULL,
    `fechaAlta` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ;
    PRIMARY KEY (`id`),
    FOREIGN KEY (`id_usuario`) REFERENCES `usr` (`id`),
    FOREIGN KEY (`id_ruta`) REFERENCES `ruta` (`id`)
) COMMENT='Tabla para almacenar la relacion de usuarios con rutas del sistema' ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Creamos una tabla que almacene que usuario levanto que incidencia en un cliente, en que fecha, tipo de incidencia y tiempo de atencion
CREATE TABLE `callcenter_incidenciasclientes` (
    `id` mediumint(6) NOT NULL AUTO_INCREMENT,
    `id_usuario` mediumint(6) NOT NULL,
    `id_cliente` int(10) NOT NULL,
    `id_incidencia` smallint(6) NOT NULL,
    `fecha` datetime NOT NULL,
    `tiempo` mediumint(6) NOT NULL, -- Tiempo en minutos en que se atendio la incidencia
    PRIMARY KEY (`id`),
    FOREIGN KEY (`id_usuario`) REFERENCES `usr` (`id`),
    FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id`),
    FOREIGN KEY (`id_incidencia`) REFERENCES `callcenter_incidencias` (`id`)
) COMMENT='Tabla para almacenar las incidencias de los clientes del call center por dia' ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `callcenter_incidenciaspedido` (
    `id` mediumint(6) NOT NULL AUTO_INCREMENT,
    `id_usuario` mediumint(6) NOT NULL,
    `id_pedido` varchar(25) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL,
    `id_cliente` int(10) NULL,
    `id_ruta` smallint(6) NOT NULL,
    `id_incidencia` smallint(6) NOT NULL,
    `fechaIncidencia` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `tiempo` text, 
    PRIMARY KEY (`id`),
    FOREIGN KEY (`id_usuario`) REFERENCES `usr` (`id`),
    FOREIGN KEY (`id_pedido`) REFERENCES `pedido` (`id`),
    FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id`),
    FOREIGN KEY (`id_ruta`) REFERENCES `ruta` (`id`),
    FOREIGN KEY (`id_incidencia`) REFERENCES `callcenter_incidencias` (`id`)
) COMMENT='Tabla para almacenar las incidencias de los pedidos del call center por dia' ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Creamos una tabla que almcene metricas de los usuarios del call center, como el tiempo que tardan en atender un pedido

-- IMPORTANTE! Metricas queda pendiente, aun no se define que metricas se van a almacenar.
-- CREATE TABLE `callcenter_metricas` (
--     `id` mediumint(6) NOT NULL AUTO_INCREMENT,
--     `id_usuario` mediumint(6) NOT NULL,
--     `id_pedido` varchar(25) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
--     `fecha` datetime NOT NULL,
--     `tiempo` mediumint(6) NOT NULL, -- Tiempo en minutos en que se atendio el pedido
--     PRIMARY KEY (`id`),
--     FOREIGN KEY (`id_usuario`) REFERENCES `usr` (`id`),
--     FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id`)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Creamos una tabla que almacene los pedidos que se generan desde el call center
CREATE TABLE `callcenter_pedidos` (
    `id` mediumint(6) NOT NULL AUTO_INCREMENT,
    `id_usuario` mediumint(6) NOT NULL,
    `id_cliente` int(10) NOT NULL,
    `id_ruta` smallint(6) NOT NULL,
    `id_pedido` varchar(25) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,    
    `fechaAltaPedido` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fechaTermino` datetime NULL DEFAULT CURRENT_TIMESTAMP,
    `factura` BOOLEAN NOT NULL DEFAULT FALSE
    PRIMARY KEY (`id`),
    FOREIGN KEY (`id_usuario`) REFERENCES `usr` (`id`),
    FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id`),
    FOREIGN KEY (`id_ruta`) REFERENCES `ruta` (`id`),
    FOREIGN KEY (`id_pedido`) REFERENCES `pedido` (`id`)
) COMMENT='Tabla para almacenar los pedidos generados desde el call center' ENGINE=InnoDB DEFAULT CHARSET=utf8;
--Campo agregado directo al codigo de la tabla
--ALTER TABLE `callcenter_pedidos` ADD `factura` BOOLEAN NOT NULL DEFAULT FALSE ;

-- Este query es para insertar usuarios en la tabla callcenter_usuariosruta y asignarles una ruta
-- INSERT INTO `callcenter_usuariosruta` (`id`, `id_usuario`, `id_ruta`, `fecha`) VALUES (NULL,2038,324,'2024-02-22');


-- Generamos el query que obtiene los clientes de la ruta de un usuario
-- SELECT c.id, c.nombre, c.direccion, c.colonia, c.cp, c.telefono, c.celular, c.email, c.latitud, c.longitud, c.id_ruta, c.id_usuario, c.id_tipo_cliente, c.id_tipo_pedido, c.id_tipo_pago, c.id_tipo_venta, c.id_tipo_envio, c.id_tipo_factura
-- FROM cliente c
-- JOIN callcenter_usuariosruta cu ON c.id_ruta = cu.id_ruta
-- WHERE cu.id_usuario = 2036

-- NUEVOS QUERYS PARA EL CALL CENTER (HECHO)
ALTER TABLE `callcenter_usuariosruta` ADD `fechaFinal` DATE NULL DEFAULT NULL;
ALTER TABLE `callcenter_usuariosruta` ADD `fechaInicial` DATE NULL DEFAULT NULL;





----------------------------------------------------------------------------------------------------
-- CALL CENTER v1.03

-- Agregamos un nuevo "nota" en incidencias pedido
ALTER TABLE `callcenter_incidenciaspedido` ADD `notaIncidencia` TEXT NULL DEFAULT NULL ;
ALTER TABLE `callcenter_pedidos` ADD `telefonoContacto` VARCHAR(255) NULL;

--Agregamos un nujevo campo en la tabla de callcenter_asignacionruta  para mantener un estado (1,0) encendido o apagado
ALTER TABLE `callcenter_usuariosruta` ADD `estadoAsignacion` TINYINT(1) NOT NULL DEFAULT '1';

-- Creamos una tabla para almacenar los numeros de telefeno que disponibles para ser usados por usuarios de call center
CREATE TABLE `callcenter_telefonos` (
    `id` mediumint(6) NOT NULL AUTO_INCREMENT,
    `numero` varchar(15) NOT NULL,
    `id_status` tinyint(1) NOT NULL DEFAULT '4',
    PRIMARY KEY (`id`),
    FOREIGN KEY (`id_status`) REFERENCES `sys_status` (`id`)
) COMMENT='Tabla para almacenar los numeros de telefono disponibles para el call center' ENGINE=InnoDB DEFAULT CHARSET=utf8;

--Insertamos telefonos fake de momento
INSERT INTO `callcenter_telefonos` (`id`, `numero`, `id_status`) VALUES
(1, '1234567890', 4),
(2, '0000000000', 4),
(3, '1111111111', 4),
(4, '2222222222', 4),
(5, '3333333333', 4),
(6, '4444444444', 4),
(7, '5555555555', 4),
(8, '6666666666', 4),
(9, '7777777777', 4);

-- Creamos una tabla para almacenar la relacion de numeros utilizados en el dia por un usuario de call center
CREATE TABLE `callcenter_telefonoasignado` (
    `id` mediumint(6) NOT NULL AUTO_INCREMENT,
    `id_telefono` mediumint(6) NOT NULL,
    `id_usuario` mediumint(6) NOT NULL,
    `fechaAsignacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`id_telefono`) REFERENCES `callcenter_telefonos` (`id`),
    FOREIGN KEY (`id_usuario`) REFERENCES `usr` (`id`)
) COMMENT='Tabla para almacenar los numeros de telefono utilizados por los usuarios del call center en una ruta' ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Creamos una tabla para almacenar un envio de mensajes de aviso a un cliente a partir de un telefono asignado de call center
CREATE TABLE `callcenter_mensajeaviso` (
    `id` mediumint(6) NOT NULL AUTO_INCREMENT,
    `id_telefono` mediumint(6) NOT NULL,
    `id_cliente` int(10) NOT NULL,
    `fechaEnvio` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`id_telefono`) REFERENCES `callcenter_telefonos` (`id`),
    FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id`)
) COMMENT='Tabla para almacenar los mensajes enviados a los clientes por el call center' ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Creamos una tabla para almacenar los numeros de telefono de los clientes que seran eliminados por acumular incidencias
CREATE TABLE `callcenter_telefonoeliminadocliente` (
    `id` mediumint(6) NOT NULL AUTO_INCREMENT,
    `id_usuario` mediumint(6) NOT NULL,
    `id_cliente` int(10) NOT NULL,
    `telefonoCliente` varchar(15) NULL,
    `fechaEliminacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`id_usuario`) REFERENCES `usr` (`id`),
    FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id`)
) COMMENT='Tabla para almacenar los numeros de telefono eliminados por acumular incidencias' ENGINE=InnoDB DEFAULT CHARSET=utf8; 



-- Agregamos una nueva opcion en el menu de call center
INSERT INTO `sys_menu_opciones` (`id`, `id_seccion`, `nombre`, `trayecto`) VALUES (NULL, '32', 'Telefonos Atn', '/mtto/admin/callcenter/gestionaTelefonoAtencion.php');




-- #########  QUERYS DE CALL CENTER v1.03 PARA OBTENER METRICAS ###### ---


-- Calculamos los pedidos hecho un el call center en un rango de fechas
SELECT
    *,
    (SELECT SUM( pdo_prod.precio * pdo_prod.cantidad) AS total FROM pdo_prod
    JOIN prod_medida ON pdo_prod.id_prod = prod_medida.id
    JOIN producto ON prod_medida.id_prod = producto.id
    JOIN sys_medida ON prod_medida.id_medida = sys_medida.id
    WHERE
        pdo_prod.id_pdo = callcenter_pedidos.id_pedido AND pdo_prod.id_status = 4
	) AS totalPedido,
    (SELECT SUM(pdo_prod.cantidad) as numProductos
    FROM
        pdo_prod
    JOIN prod_medida ON pdo_prod.id_prod = prod_medida.id
    JOIN producto ON prod_medida.id_prod = producto.id
    JOIN sys_medida ON prod_medida.id_medida = sys_medida.id
    WHERE
        pdo_prod.id_pdo = callcenter_pedidos.id_pedido AND pdo_prod.id_status = 4
	) AS numProductos
    
FROM
    callcenter_pedidos
INNER JOIN pedido ON pedido.id = callcenter_pedidos.id_pedido
INNER JOIN usr ON usr.id = callcenter_pedidos.id_usuario
WHERE
    callcenter_pedidos.fechaAltaPedido BETWEEN '2024-02-01' AND '2024-03-31';


-- Calculamos los pedidos hecho un el call center en un rango de fechas
SELECT 
    DATE(callcenter_pedidos.fechaAltaPedido) AS fecha, 
    COUNT(*) AS numero_de_pedidos
FROM
    callcenter_pedidos
INNER JOIN pedido ON pedido.id = callcenter_pedidos.id_pedido
INNER JOIN usr ON usr.id = callcenter_pedidos.id_usuario
WHERE
    callcenter_pedidos.fechaAltaPedido BETWEEN '2024-03-01' AND '2024-03-31'
GROUP BY
    fecha;

-- Calculamos la cantidad de pedidos tomados por cada ruta en un rango de fechas
SELECT 
	bodega.nombre AS Bodega,
	oficina.nombre AS uOperativa,
    ruta.nombre AS nombre_ruta, 
    COUNT(*) AS numero_de_pedidos
FROM
    callcenter_pedidos
INNER JOIN ruta ON ruta.id = callcenter_pedidos.id_ruta
INNER JOIN oficina on oficina.id = ruta.id_oficina
INNER JOIN bod_oficina ON oficina.id = bod_oficina.id_oficina
INNER JOIN bodega ON bodega.id = bod_oficina.id_bodega
WHERE
    callcenter_pedidos.fechaAltaPedido BETWEEN '2024-03-01' AND '2024-03-31'
GROUP BY
    nombre_ruta

-- Calculamos la venta de cada dia en un rango de fechas del call center
SELECT 
    DATE(callcenter_pedidos.fechaAltaPedido) AS fecha, 
    (SELECT SUM( pdo_prod.precio * pdo_prod.cantidad) AS total FROM pdo_prod
    JOIN prod_medida ON pdo_prod.id_prod = prod_medida.id
    JOIN producto ON prod_medida.id_prod = producto.id
    JOIN sys_medida ON prod_medida.id_medida = sys_medida.id
    WHERE
        pdo_prod.id_pdo = callcenter_pedidos.id_pedido
	) AS totalVenta
FROM
    callcenter_pedidos
INNER JOIN pedido ON pedido.id = callcenter_pedidos.id_pedido

WHERE
    callcenter_pedidos.fechaAltaPedido BETWEEN '2024-03-01' AND '2024-03-31'
GROUP BY
    fecha;



-- Calculamos el numero de incidencias por tipo en un rango de fechas
SELECT 
    callcenter_incidencias.nombre AS incidencia,
    COUNT(*) AS numero_de_incidencias
FROM
    callcenter_incidenciaspedido
INNER JOIN callcenter_incidencias ON callcenter_incidencias.id = callcenter_incidenciaspedido.id_incidencia
WHERE
    callcenter_incidenciaspedido.fechaIncidencia BETWEEN '2024-03-01' AND '2024-03-31'
GROUP BY
    incidencia;

-- Clientes atendidos
SELECT
    cp.id_cliente,
    COUNT(*) AS numero_de_ocurrencias
FROM
    callcenter_pedidos cp
WHERE
    cp.fechaAltaPedido BETWEEN '2024-03-01' AND '2024-03-31'
GROUP BY
    cp.id_cliente

-- Calculmos el tiempo promedio de los pedido lesvantados por el call center
SELECT
    AVG(TIMESTAMPDIFF(MINUTE, cp.fechaAltaPedido, cp.fechaTermino)) AS tiempo_medio_toma_pedido
FROM
    pedido p
INNER JOIN callcenter_pedidos cp ON p.id = cp.id_pedido
WHERE
    p.id_status = 4 AND
    cp.fechaTermino IS NOT NULL
    
--Calculamos el tiempo de los pedidos individualmente
SELECT
    p.id AS id_pedido,
    TIMESTAMPDIFF(MINUTE, cp.fechaAltaPedido, cp.fechaTermino) AS tiempo_toma_pedido_minutos
FROM
    pedido p
INNER JOIN callcenter_pedidos cp ON p.id = cp.id_pedido
WHERE
    p.id_status = 4
    AND cp.fechaTermino IS NOT NULL
ORDER BY
    tiempo_toma_pedido_minutos DESC;



--- #########  FIN DE LOS QUERYS DE CALL CENTER v1.03 ###### ---



----- ######### Version v1.04 ###### --------
ALTER TABLE `callcenter_incidenciaspedido` ADD `telefono` VARCHAR(255) NULL DEFAULT NULL ;
ALTER TABLE `cliente` ADD `telpreferente` VARCHAR(13) NULL DEFAULT NULL ;
ALTER TABLE `callcenter_mensajeaviso` ADD `telefonoCliente` VARCHAR(13) NULL DEFAULT NULL ;
INSERT INTO `callcenter_incidencias` (`id`, `nombre`) VALUES (NULL, 'No contactar por teléfono.');

