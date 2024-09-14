<?php
require_once 'db.php';
require_once 'Compra.php';
require_once 'CompraFactura.php';
require_once 'ProdCompra.php';
require_once 'GmcvDescuento.php';
require_once 'GmcvDescuentoBodega.php';
require_once 'GmcvDescuentoProducto.php';

// Crear una nueva compra
$compra = new Compra();
$compra->id_prov = 1;
$compra->alta = '2024-05-29 12:00:00';
$compra->llegada = '2024-05-30 12:00:00';
$compra->id_status = 1;
$compra->id_usr = 1;
$compra->id_bodega = 1;
$compra->id_tipo_venta = 1;
$compra->id_usr_alm = null;
$compra->tipo = 1;
$compra->nota_orden = 'Nota de orden';
$compra->nota_entrada = 'Nota de entrada';
$compra->create();

// Obtener el ID de la compra recién creada
$compra_id = db::insert_id();

// Crear un nuevo producto relacionado con la compra
$prodCompra = new ProdCompra();
$prodCompra->id_compra = $compra_id;
$prodCompra->id_prod = 1;
$prodCompra->sugerido = 10;
$prodCompra->cantidad = 5.0000;
$prodCompra->id_status = 1;
$prodCompra->cant_solicitada = 5.0000;
$prodCompra->cant_no_recibida = 0.0000;
$prodCompra->cant_ingresada = 5.0000;
$prodCompra->costo_unitario = 100.0000;
$prodCompra->prod_agregado = 0;
$prodCompra->escaner = 0;
$prodCompra->create();

// Crear una nueva factura relacionada con la compra
$compraFactura = new CompraFactura();
$compraFactura->id_compra = $compra_id;
$compraFactura->id_status = 1;
$compraFactura->uuid = 'some-uuid';
$compraFactura->fecha = '2024-05-29';
$compraFactura->tipo = 1;
$compraFactura->ajuste = 0.0000;
$compraFactura->id_status_pago = 0;
$compraFactura->validada = 0;
$compraFactura->fecha_compromiso = '2024-06-01';
$compraFactura->fecha_llegada = '2024-06-10';
$compraFactura->fecha_alerta = '2024-06-05';
$compraFactura->create();

// Obtener el ID de la factura recién creada
$factura_id = db::insert_id();

// Crear un nuevo descuento
$descuento = new GmcvDescuento();
$descuento->id_prov = 1;
$descuento->nombre = 'Descuento especial';
$descuento->id_status = 1;
$descuento->posteriorCP = 0;
$descuento->create();

// Obtener el ID del descuento recién creado
$descuento_id = db::insert_id();

// Relacionar el descuento con la factura
$gmcvDescuentoFactura = new GmcvDescuentoFactura();
$gmcvDescuentoFactura->id_factura = $factura_id;
$gmcvDescuentoFactura->id_descuento = $descuento_id;
$gmcvDescuentoFactura->create();

// Obtener todas las facturas relacionadas con una compra específica
$facturas = CompraFactura::getFacturasByCompraId($compra_id);
foreach ($facturas as $factura) {
    echo "Factura ID: " . $factura['id'] . "\n";
    echo "UUID: " . $factura['uuid'] . "\n";
    echo "Fecha: " . $factura['fecha'] . "\n";
    // Puedes mostrar otros campos según sea necesario

    // Obtener los descuentos relacionados con esta factura
    $descuentos = GmcvDescuento::getDescuentosByFacturaId($factura['id']);
    foreach ($descuentos as $descuento) {
        echo "Descuento ID: " . $descuento['id'] . "\n";
        echo "Nombre: " . $descuento['nombre'] . "\n";
        echo "Descuento: " . $descuento['descuento'] . "\n";
        // Puedes mostrar otros campos según sea necesario
    }
}

// Obtener todos los productos relacionados con una compra específica
$productos = ProdCompra::getProductosByCompraId($compra_id);
foreach ($productos as $producto) {
    echo "Producto ID: " . $producto['id'] . "\n";
    echo "ID Producto: " . $producto['id_prod'] . "\n";
    echo "Cantidad: " . $producto['cantidad'] . "\n";
    // Puedes mostrar otros campos según sea necesario
}




?>