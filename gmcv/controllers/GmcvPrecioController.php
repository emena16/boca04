<?php
//Array de modelos a incluir según sea necesario
$modelos = ['GmcvPrecio', 'ProdOficina', 'Compra'];
include "header.php";

class GmcvPrecioController extends db {
    //Obnemos las oficina por id de proveedor y id de bodega
    public function getPreciosOficina($args=[]){
        //Instanciamos el modelo de GmcvPrecio
        $gmcvPrecio = new GmcvPrecio();
        //Obtenemos los precios de la oficina
        $precios = $gmcvPrecio->getPreciosOficina($args['uOperativas'], $args['bodegas'], $args['proveedores'], $args['fecha']);    
        
        return $precios;
    }


    //Creamos un mentodo para almacenar los precios de venta de un producto en función de la oficina y el id_producto
    public function setPrecioVentaOficina($args=[]){
        //Instanciamos el modelo de GmcvPrecio
        // $gmcvPrecio = new GmcvPrecio();

        $productos = $args['paquete'];
        $fecha = $args['fecha'];

        /*
        //Ejemplo de request de productos
        [
            {"id_prod":"8083","oficinas":["165","168","169"],"id_status":4,"fecha":"2024-08-09","venta":9.19,"margen":50},
            {"id_prod":"8084","oficinas":["165","168","169"],"id_status":4,"fecha":"2024-08-09","venta":0,"margen":50}
            {"id_prod":"1320","oficinas":["165","168","169"],"id_status":4,"fecha":"2024-08-09","venta":301.5,"margen":50},
            {"id_prod":"1321","oficinas":["165","168","169"],"id_status":4,"fecha":"2024-08-09","venta":602.99,"margen":50},
            {"id_prod":"1322","oficinas":["165","168","169"],"id_status":4,"fecha":"2024-08-09","venta":1205.98,"margen":50},
            {"id_prod":"5563","oficinas":["165","168","169"],"id_status":4,"fecha":"2024-08-09","venta":25.41,"margen":50}
        ]
        */

        //Instanciamos el modelo de pro_oficina
        $prodOficina = new ProdOficina();

        //Recorremos los productos
        foreach($productos as $producto){
            //Recorremos las oficinas del producto
            foreach($producto['oficinas'] as $oficina){
                //Obtenemos el producto de la oficina 
                $prodOficinaObj = ProdOficina::getByIdProdIdOficinaObject($producto['id_prod'], $oficina);
                //Si el producto no existe lo creamos
                if(!$prodOficinaObj){
                    $prodOficinaObj = new ProdOficina();
                    $prodOficinaObj->id_prod = $producto['id_prod'];
                    $prodOficinaObj->id_oficina = $oficina;
                    $prodOficinaObj->id_status = $producto['id_status'];
                    $prodOficinaObj->alta = date('Y-m-d');
                    $prodOficinaObj->venta = 0.0100;
                    $prodOficinaObj->fecha_cambio = $fecha;
                    $prodOficinaObj->venta_nva = $producto['venta'];                    
                    $prodOficinaObj->pendiente = 1;
                    $prodOficinaObj->create();
                    
                }else{
                    //Si el producto existe lo actualizamos 
                    $prodOficinaObj->id_prod = $producto['id_prod'];
                    $prodOficinaObj->id_oficina = $oficina;
                    $prodOficinaObj->id_status = $producto['id_status'];
                    $prodOficinaObj->venta = ($prodOficinaObj->venta_nva == null || $prodOficinaObj->venta_nva == 0) ? 0.0100 : $prodOficinaObj->venta_nva;
                    $prodOficinaObj->fecha_cambio = $fecha;
                    //Si el valor del producto es 0 no podemos vender a ese precio por lo que la pondremos en $0.01 
                    $prodOficinaObj->venta_nva = $producto['venta'] < 0.01 ? 0.01 : $producto['venta'];
                    $prodOficinaObj->pendiente = 1;
                    $prodOficinaObj->update();
                    //Destruimos el objeto
                    unset($prodOficinaObj);
                }
            } // Fin foreach oficinas
        } // Fin foreach productos

        //Si llegamos hasta aquí retornamos true y que se guardaron los precios de venta
        return array(
            'status' => true,
            'message' => 'Se guardaron los precios de venta correctamente'
        );

        
    }

    //Creamos un metodo que retorne costos de un producto
    public function getCostosProducto($args=[]){
        $compra = Compra::getByIdObject($args['id_compra']);
        //Instanciamos el modelo de GmcvPrecio
        $gmcvPrecio = GmcvPrecio::getPrecioListaByIdProd($args['id_prod'], $args['fechaFactura'], $compra->id_bodega);
        return $gmcvPrecio;        
    }
 
}
?>
