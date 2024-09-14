-- NUEVOS QUERYS PARA EL CALL CENTER (PENDIENTES)
ALTER TABLE `callcenter_usuariosruta` ADD `fechaFinal` DATE NULL DEFAULT NULL;
ALTER TABLE `callcenter_usuariosruta` ADD `fechaInicial` DATE NULL DEFAULT NULL;

-- Agregamos un nuevo "nota" en incidencias pedido
ALTER TABLE `callcenter_incidenciaspedido` ADD `notaIncidencia` TEXT NULL DEFAULT NULL ;
ALTER TABLE `callcenter_pedidos` ADD `telefonoContacto` VARCHAR(255) NULL;
