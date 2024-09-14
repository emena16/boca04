<?php

//Array de modelos a incluir según sea necesario
$modelos = ['Compra','CompraFactura','ProdCompra','GmcvPrecio','GmcvDescuentoProducto','GmcvDescuento','GmcvDescuentoBodega','Almacen'];
include "header.php";

class CompraController {
    public function create($data) {
        $compra = new Compra();
        $compra->id_prov = $data['id_prov'];
        $compra->alta = $data['alta'];
        $compra->llegada = $data['llegada'];
        $compra->id_status = $data['id_status'];
        $compra->id_usr = $data['id_usr'];
        $compra->id_bodega = $data['id_bodega'];
        $compra->id_tipo_venta = $data['id_tipo_venta'];
        $compra->id_usr_alm = $data['id_usr_alm'];
        $compra->tipo = $data['tipo'];
        $compra->nota_orden = $data['nota_orden'];
        $compra->nota_entrada = $data['nota_entrada'];
        $compra->create();

        return db::insert_id();
    }

    public function read($id) {
        return Compra::getById($id);
    }


  

    public function getFacturasByCompraId($id_compra) {
        $compraFactura = new CompraFactura();
        return $compraFactura->getFacturasByCompraId($id_compra);
    }

    public function getProveedores() {
       $compra = new Compra();
       return $compra->getProveedores();
    }

    public function getBodegas() {
       $compra = new Compra();
       return $compra->getBodegas();
    }

    public function getProveedoresConOrdenes() {
       $compra = new Compra();
       return $compra->getProveedoresConOrdenes();
    }

    public function getBodegasConComprasPendientes($args=[]){
        $compra = new Compra();
        return $compra->getBodegasConComprasPendientes($args['id_prov'], $args['modoValidacion']);
    }


    //Creamos una funcion para obtener las compras pendientes de un proveedor según la bodega
    public function getComprasPendientes($args=[]){
        $compra = new Compra();
        return json_encode( $compra->getComprasPendientes($args['id_prov'], $args['id_bodega']));
    }

    //Creamos una funcion para obtener los productos restantes de una compra
    public function getProductosRestantesOrdenCompra($args=[]){
        $compra = new Compra();
        return json_encode( $compra->getProductosRestantesOrdenCompra($args['id_compra']));
    }

    //Agregamos una funcion para obtnerlos productos que podemos agregar a una orden de compra
    public function getProductosParaAgregarOrden($args=[]){
        $compra = new Compra();
        return json_encode( $compra->getProductosParaAgregarOrden($args['id_compra']));
    }

    //Agreamos un metodo para agregar un producto a una orden de compra
    public function addProductoToOrden($args =[]){
        $prodCompra = new ProdCompra();

        //Antes de agregar el producto verificamos si no tiene algo extraño como unidades negativas
        if ($args['cantidad'] <= 0) {
            return json_encode(['status' => false, 'message' => 'La cantidad debe ser mayor a 0']);
        }
        //Verificamos que el costo unitario sea mayor a 0.01
        // if ($args['costo_unitario'] <= 0) {
        //     return json_encode(['status' => false, 'message' => 'El costo unitario debe ser mayor a 0.01']);
        // }
        $prodCompra->id_compra = intval($args['id_compra']);
        $prodCompra->id_prod = intval($args['id_prod']);
        $prodCompra->sugerido = NULL;
        $prodCompra->cantidad = round($args['cantidad'], 4);
        $prodCompra->id_status = 4;
        $prodCompra->cant_solicitada = round($args['cantidad'], 4);
        $prodCompra->cant_no_recibida = 0.0000;
        $prodCompra->cant_ingresada = 0.0000;
        $prodCompra->costo_unitario = round($args['costo_unitario'], 4);
        $prodCompra->prod_agregado = 1;
        $prodCompra->escaner = 0;   
        
        //Guardamos el nuevo producto
        if($prodCompra->create()){
            return json_encode(['status' => true, 'message' => 'Producto agregado correctamente']);
        } else {
            return json_encode(['status' => false, 'message' => 'Error al agregar el producto, por favor re-intente más tarde']);
        }

        
    }

    //Creamos un metedo para cerrar una orden de compra (pasarla a status 23) para validarla mas adelante
    public function cerrarOrdenCompra($args = []){
        //Recibimos el id_compra que queremos cerrar
        $id_compra = $args['id_compra'];
        //Instaciamos un objeto de compra y uno compraFactura
        $compra = new Compra();
        $compraFactura = new CompraFactura();
        //Verificamos que la compra no tenga facturas pendientes por ingresar
        $facturas = $compraFactura->getFacturasByCompraId($id_compra);

        //Recorremos las fatuas para verificar si alguna tiene el campo: ingreso_almacen = 0
        foreach ($facturas as $factura) {
            if($factura['ingreso_almacen'] == 0){
                return json_encode(['status' => false, 'message' => 'La compra tiene facturas pendientes por ingresar']);
            }
        }
        //Verificamos que la compra no tenga productos pendientes por ingresar
        $productos = $compra->getProductosRestantesOrdenCompra($id_compra);
        $productosRestantes = array_values(array_filter($productos, function($producto){
            return $producto['cant_restante'] > 0;
        }));

        //Creamos un array de respuesta para hacer un paquete de respuesta
        $response = [
            'status' => true,
            'message' => 'Orden cerrada correctamente',
            'productosPendientes' => sizeof($productosRestantes) > 0 ? true : false, 
            'productos' => $productosRestantes
        ];

        return json_encode($response);        
    }

    public function confirmaCerrarOrdenCompra($args = []){
        //Recibimos el id_compra que queremos cerrar
        $id_compra = $args['id_compra'];
        $notaEntrada = $args['notaEntrada'];
        //Instaciamos un objeto de compra y uno compraFactura
        $compra = new Compra();
        $compraFactura = new CompraFactura();
        //Verificamos que la compra no tenga facturas pendientes por ingresar
        $facturas = $compraFactura->getFacturasByCompraId($id_compra);

        //Recorremos las facturas para verificar si alguna tiene el campo: ingreso_almacen = 0
        foreach ($facturas as $factura) {

            if($factura['ingreso_almacen'] == 0){
                return json_encode([
                    'status' => false, 
                    'message' => 'La compra tiene facturas pendientes por ingresar'
                ]);
            }
        }
        //Obtnemos los productos de la orden
        $productos = $compra->getProductosRestantesOrdenCompra($id_compra);

        //Actualizamos prod_compra en función a los productos de la orden
        foreach ($productos as $producto) {
            $ProdCompra = new ProdCompra();
            $prodCompra = $ProdCompra->getById($producto['id_prod_compra']); 
            if(!$prodCompra) return json_encode(['status' => false, 'message' => 'Error al cerrar la orden, por favor contacte a soporte']);
            $id_prod_compra = $producto['id_prod_compra'];
            $almacenAux = Almacen::getAlmacenByIdProdCompra($id_prod_compra);
            //Si no encontro nada en almacenAux quiere decir que no hay registros en almacen por lo que continuamos con el siguiente producto
            if(!$almacenAux) continue;
            // //Escribimos que contine almacenAux en un fichero
            // file_put_contents('almacenAux.txt', json_encode($almacenAux)." valor de id: ".$ProdCompra->id."\n", FILE_APPEND);
            // file_put_contents('almacenAux.txt', json_encode($producto)."\n", FILE_APPEND);
            $prodCompra->cant_no_recibida = max(0, round(floatval($producto['cant_restante']) + floatval($producto['total_cantidad_rechazada']), 4));
            $prodCompra->cant_ingresada = round(floatval($producto['total_cantidad_aceptada']), 4);
            $prodCompra->costo_unitario = $almacenAux['costo_unitario'];
            if(empty($prodCompra->sugerido)) $prodCompra->sugerido = null;
            $prodCompra->update();
        }

        //Actualizamos la orden de compra
        $compra = $compra->getByIdObject($id_compra); 
        $compra->id_status = 23;
        $compra->nota_entrada = $notaEntrada;
        $compra->id_usr_alm = $_SESSION['id'];
        $compra->llegada = date('Y-m-d H:i:s');
        $updateCompra = $compra->update(); 

        if($updateCompra){
            return json_encode([
                'status' => true, 
                'message' => 'Orden cerrada correctamente'
            ]);
        } else {
            return json_encode([
                'status' => false, 
                'message' => 'Error al cerrar la orden, por favor contacte a soporte'
            ]);
        }

    }

    ////////////////////////  VALIDACION DE COSTOS //////////////////////
    
    public function getProveedoresConOrdenesPorValidar() {
        $compra = new Compra();
        return $compra->getProveedoresConOrdenesPorValidar();
     }


    public function getComprasPendientesPorValidar($args=[]){
        $compra = new Compra();
        return json_encode( $compra->getComprasPendientesPorValidar($args['id_prov'], $args['id_bodega']));
    }


    public function confirmaCerrarValidacionOrdenCompra($args = []){
        //Recibimos el id_compra que queremos cerrar
        $id_compra = $args['id_compra'];
        //Instaciamos un objeto de compra y uno compraFactura
        $compra = new Compra();
        $compraFactura = new CompraFactura();
        //Verificamos que la compra no tenga facturas pendientes por ingresar
        $facturas = $compraFactura->getFacturasByCompraId($id_compra);

        //Recorremos las facturas para verificar si alguna tiene el campo: validada = 0
        foreach ($facturas as $factura) {
            if($factura['validada'] == 0){
                return json_encode([
                    'status' => false, 
                    'message' => 'La compra tiene facturas pendientes por validar'
                ]);
            }
        }
        //Obtnemos los productos de la orden
        $productos = $compra->getProductosRestantesOrdenCompra($id_compra);

        //Actualizamos prod_compra en función a los productos de la orden
        foreach ($productos as $producto) {
            $ProdCompra = new ProdCompra();
            $prodCompra = $ProdCompra->getById($producto['id_prod_compra']); 
            $prodCompra->cant_no_recibida = max(0, round(floatval($producto['cant_restante']) + floatval($producto['total_cantidad_rechazada']), 4));
            $prodCompra->cant_ingresada = round(floatval($producto['total_cantidad_aceptada']), 4);
            if(empty($prodCompra->sugerido)) $prodCompra->sugerido = null;
            $prodCompra->update();
        }

        //Actualizamos la orden de compra
        $compra = $compra->getByIdObject($id_compra); 
        $compra->id_status = 1;
        $updateCompra = $compra->update(); 

        if($updateCompra){
            return json_encode([
                'status' => true, 
                'message' => 'Orden cerrada correctamente'
            ]);
        } else {
            return json_encode([
                'status' => false, 
                'message' => 'Error al cerrar la orden, por favor contacte a soporte'
            ]);
        }

    }


    ///////////// PRECIOS DE LISTA GSV //////////////
    public function getBodegasByIdProveedor($args = []){
        $compra = new Compra();
        return $compra->getBodegasByIdProveedor($args['idProveedor']);
    }

    public function getProductosByProvBod($args = []){
        $compra = new Compra();
        //Verificamos que al menos haya una bodega seleccionada
        if(empty($args['bodegas'])) return http_error(500, 'No se ha seleccionado ninguna bodega');

        return $compra->getProductosByProvBod($args['idProveedor'], $args['bodegas'],$args['fecha']);
    }

    public function guardarGSV($args = []){
        /**
         * PARA AGREGAR LOS PRECIOS LO QUE VAMOS A HACER PRIMERO:
         * 1.- GMCV_PRECIO VAMOS A ELIMINAR LOS EXSITENTES DEL LA FECHA EN CUESTION, ES DECIR, PROTEJEMOS LA DB DE QUE SE PUEDAN DUPLICAR LOS PRECIOS A TRAVES DE LA FECHA
         * 2.- gmcv_descuento_producto VAMOS ELIMINAR/AGREAR LOS DEESCUENTOS EN FUNCION A LA BODEGA PARA QUE NO SE DUPLIQUEN O EN SU DEFECTO SE ACTUALICEN SUS TASAS SEGUN SEA EL CASO A TRAVES DE LA FECHA EN CUESTION
         * 3.- 
         */

        //Instanciamos los modelos necesarios
        $gmcvPrecio = new GmcvPrecio();
        $gmcvDescuentoProducto = new GmcvDescuentoProducto();

        //Antes de todo vamos a eliminar los precios de la fecha en cuestion, es probable que se dupliquen
        $gmcvPrecio->deletePrecioByProvBodegasFecha($args['id_prov'], $args['bodegas'], $args['fecha']);
        //Eliminamos los descuentos de la fecha en cuestion
        $gmcvDescuentoProducto->deleteByIdProvBodIni($args['id_prov'], $args['bodegas'], $args['fecha']);
        
        // Recorremos los productos para agregar los precios
        foreach ($args['productos'] as $producto) {
            // Verificamos si el array de descuentos existe y no está vacío
            if (!empty($producto['descuentos'])) {
                // Recorremos los descuentos del producto
                foreach ($producto['descuentos'] as $descuento) {
                    // Recorremos las bodegas para agregar los descuentos
                    $bodegasProducto = explode(',', $args['bodegas']);
                    
                    foreach ($bodegasProducto as $bodega) {
                        // Agregamos el precio
                        $gmcvPrecio->id_prov = $args['id_prov'];
                        $gmcvPrecio->id_bodega = $bodega;
                        $gmcvPrecio->id_prod = $producto['id'];
                        $gmcvPrecio->ini = $args['fecha'];
                        $gmcvPrecio->id_status = 4;
                        $gmcvPrecio->precio = $producto['precioLista'];
                        $gmcvPrecio->create();
                        
                        // Agregamos el descuento
                        $gmcvDescuentoProducto->id_descuento = $descuento['id'];
                        $gmcvDescuentoProducto->id_prov = $args['id_prov'];
                        $gmcvDescuentoProducto->id_bodega = $bodega;
                        $gmcvDescuentoProducto->id_prod = $producto['id'];
                        $gmcvDescuentoProducto->ini = $args['fecha'];
                        $gmcvDescuentoProducto->id_status = 4;
                        $gmcvDescuentoProducto->descuento = $descuento['tasa'];
                        $gmcvDescuentoProducto->create();
                    }
                }
            } else {
                // Solo agregamos el precio si no hay descuentos
                $bodegasProducto = explode(',', $args['bodegas']);
                
                foreach ($bodegasProducto as $bodega) {
                    $gmcvPrecio->id_prov = $args['id_prov'];
                    $gmcvPrecio->id_bodega = $bodega;
                    $gmcvPrecio->id_prod = $producto['id'];
                    $gmcvPrecio->ini = $args['fecha'];
                    $gmcvPrecio->id_status = 4;
                    $gmcvPrecio->precio = $producto['precioLista'];
                    $gmcvPrecio->create();
                }
            }
        }


        //Si llegamos hasta aquí es que todo salió bien
        return json_encode(['status' => true, 'message' => 'Precios guardados correctamente']);

    }
}
?>
