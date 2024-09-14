<?php
//Array de modelos a incluir según sea necesario
$modelos = ['GmcvDescuento', 'GmcvDescuentoBodega', 'Compra'];
include "header.php";

class GmcvDescuentoController {
    // public function create($data) {
    //     $descuento = new GmcvDescuento();
    //     $descuento->id_prov = $data['id_prov'];
    //     $descuento->nombre = $data['nombre'];
    //     $descuento->id_status = $data['id_status'];
    //     $descuento->posteriorCP = $data['posteriorCP'];
    //     $descuento->create();

    //     return db::insert_id();
    // }

    public function getDescuentoParaEdicion($args = []){
        $id_descuento = $args['id_descuento'];
        $id_prov = $args['id_prov'];
        //Instanciamos el modelo de descuento y compra
        $descuento = new GmcvDescuento();
        $compra = new Compra();

        //Obntenemos el descuento
        $descuentoInfo = $descuento->getById($id_descuento);
        //Bodegas Activas del descuento 
        $bodegasDescuento = GmcvDescuentoBodega::getBodegasByDescuento($id_descuento, $id_prov);
        //Obtenemos todas las bodegas del proveedor
        $bodegasProveedor = $compra->getBodegasByIdProveedor($id_prov);
        
        //Enviamos los datos a la vista
        return [
            'descuento' => $descuentoInfo,
            'bodegasDescuento' => $bodegasDescuento,
            'bodegasProveedor' => $bodegasProveedor
        ];
    }

    //Vamos a gestiornar la actualizacion de un descuento y sus bodegas
    public function updateDescuento($args = []){
        
        $id_descuento = $args['id_descuento'];
        $nombre = $args['nombre'];
        $estado = $args['id_status'] == 1 ? 4 : 3;
        $posteriorCP = $args['posteriorCP'];
        $bodegas = $args['bodegas'];
        $id_prov = $args['id_prov'];
        //Creamos el array de bodegas
        $bodegasArray = explode(',', $bodegas);
        //Response
        $response = array(
            'status' => 0,
            'message' => ''
        );

        //Instanciamos el modelo de descuento y bodega
        $descuento = new GmcvDescuento();
        $descuentoBodega = new GmcvDescuentoBodega();
        //Actualizamos el descuento
        $descuentoObj = $descuento->getByIdObject($id_descuento);
        $descuentoObj->nombre = $nombre;
        $descuentoObj->id_status = $estado;
        $descuentoObj->posteriorCP = $posteriorCP;
        if(!$descuentoObj->update()){
            $response['message'] = 'Error al actualizar el descuento';
            $response['status'] = 0;
            return $response;
        }

        //Eliminamos las bodegas actuales del descuento
        if($descuentoBodega->deleteDescuento($id_descuento)){
            //Recorremos las bodegas y las asignamos al descuento
            foreach($bodegasArray as $bodega){
                $descuentoBodega->id_descuento = $id_descuento;
                $descuentoBodega->id_bodega = $bodega;
                $descuentoBodega->create();
            }
        }else{
            $response['message'] = 'Error al actualizar las bodegas del descuento';
            $response['status'] = 0;
            return $response;
        }

        //Actualizamos el estado del descuento en gmcv_descuento_producto
        if(!GmcvDescuentoProducto::cambiarStatusDescuento($id_descuento, $id_prov, $estado)){
            $response['message'] = 'Error al actualizar el estado del descuento en las bodegas';
            $response['status'] = 0;
            return $response;
        }
        
        //Si llegamos hasta aqui es que todo salio bien
        $response['message'] = 'Descuento actualizado correctamente';
        $response['status'] = 1;
        return $response;
    } //Fin de la funcion updateDescuento

    // Metodo para obtener los descuentos inactivos
    public function getDescuentosInactivosByProveedor($args = []){
        $descInactivos =  GmcvDescuento::getDescuentosInactivosByProveedor($args['id_prov']);
        $compra = new Compra();
        $bodegasProveedor = $compra->getBodegasByIdProveedor($args['id_prov']);
        return [
            'descuentosInactivos' => $descInactivos,
            'bodegasProveedor' => $bodegasProveedor
        ];
    }

    // Metodo para activar un descuento
    public function reactivarDescuento($args = []){
        $response = array(
            'status' => 1,
            'message' => ''
        );

        //Activamos el descuento
        if(!GmcvDescuento::activarDescuento($args['id_descuento'])){
            return array(
                'status' => 0,
                'message' => 'Error al activar el descuento'
            );
        }

        //Activamos el descuento en gmcv_descuento_producto
        if(!GmcvDescuentoProducto::cambiarStatusDescuento($args['id_descuento'], $args['id_prov'], 4)){
            return array(
                'status' => 0,
                'message' => 'Error al activar el descuento en las bodegas'
            );
        }
            
        //Si llegamos hasta aqui es que todo salio bien
        $response['message'] = 'Descuento activado correctamente';

        return $response;

    } //Fin de la funcion activarDescuento


    //Metodo para crear un nuevo descuento, sus bodegas y asignarlo a los productos
    public function createDescuento($args = []){
        $nombre = $args['nombre'];
        $estado = $args['id_status'] == 1 ? 4 : 3;
        $posteriorCP = $args['posteriorCP'];
        $bodegas = $args['bodegas'];
        $id_prov = $args['id_prov'];
        $fechaDescuento = $args['fecha'];

        //Creamos el array de bodegas
        $bodegasArray = explode(',', $bodegas);
        //Response
        $response = array(
            'status' => 0,
            'message' => ''
        );

        //Instanciamos el modelo de descuento y bodega
        $descuento = new GmcvDescuento();
        $descuentoBodega = new GmcvDescuentoBodega();
        $compra = new Compra();

        //Buscamos el descuento por nombre
        if(GmcvDescuento::getDescuentosByNombre($nombre, $id_prov)){
            $response['message'] = 'Ya existe un descuento con ese nombre';
            $response['status'] = 0;
            return $response;
        }

        //Creamos el descuento
        // $descuento->id_prov = $id_prov;
        // $descuento->nombre = $nombre;
        // $descuento->id_status = $estado;
        // $descuento->posteriorCP = $posteriorCP;
        // if(!$descuento->create()){
        //     $response['message'] = 'Error al crear el descuento';
        //     $response['status'] = 0;
        //     return $response;
        // }else{
        //     //Obtnemos el id del descuento creado
        //     $id_descuento = db::insert_id();
        // }
        try {
            $descuento->id_prov = $id_prov;
            $descuento->nombre = $nombre;
            $descuento->id_status = $estado;
            $descuento->posteriorCP = $posteriorCP;

        
            if(!$descuento->create()) {
                $response['message'] = 'Error al crear el descuento';
                $response['status'] = 0;
                return $response;
            } else {
                // Obtenemos el id del descuento creado
                $id_descuento = db::insert_id();
            }
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) { // Código de error para entrada duplicada en MySQL con MySQLi
                $response['message'] = 'El nombre del descuento ya existe para el proveedor seleccionado.';
                $response['status'] = 0;
                return $response;
            } else {
                $response['message'] = 'Error inesperado: ' . $e->getMessage();
                $response['status'] = 0;
                return $response;
            }
        }
        
        //Obtnemos los productos del proveedor para agregar el nuevo descuento
        $productosProveedor = $compra->getProductosByProvBod($id_prov, $bodegas, date('Y-m-d'));

        //Escribimos el array en json en el archivo de debug: precioLista.txt
        //Recorremos los productos y les asignamos el nuevo descuento
        foreach($productosProveedor['productos'] as $producto){
            foreach($bodegasArray as $bodega){
                $descuentoProducto = new GmcvDescuentoProducto();
                $descuentoProducto->id_descuento = $id_descuento;
                $descuentoProducto->id_prov = $id_prov;
                $descuentoProducto->id_bodega = $bodega;
                $descuentoProducto->id_prod = $producto['id'];  
                $descuentoProducto->ini = $fechaDescuento;
                $descuentoProducto->id_status = $estado;
                $descuentoProducto->descuento = 0.0000;
                $descuentoProducto->create();
            }
        }
        //fclose($file);

        //Recorremos las bodegas y las asignamos al descuento
        foreach($bodegasArray as $bodega){
            $descuentoBodega->id_descuento = $id_descuento;
            $descuentoBodega->id_bodega = $bodega;
            $descuentoBodega->create();
        }

        //Si llegamos hasta aqui es que todo salio bien
        $response['message'] = 'Descuento creado correctamente';
        $response['status'] = 1;
        return $response;

    } //Fin de la funcion createDescuento

}
?>
