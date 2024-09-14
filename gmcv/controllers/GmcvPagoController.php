<?php
//Array de modelos a incluir según sea necesario
$modelos = ['GmcvPago','GmcvPagoFactura', 'CompraFactura'];
include "header.php";

class GmcvPagoController extends db {
    //Obnemos las oficina por id de proveedor y id de bodega
    public function getOficinasByidProvIdBod($args=[]){
        //Instanciamos el modelo de proveedor
        $proveedor = new Proveedor();       
        return $proveedor->getOficinasByidProvIdBod($args['proveedores'],$args['bodegas']);        
    }


    //Obtner los pagos por id de proveedor para el modulo de pagos
    public function getPagosByIdProv($args=[]){
        //Instanciamos el modelo de pagos 
        $pago = new GmcvPago();
        $response = array(
            'pagos' => [],
            'notas' => []
        );
        $pagos = $pago->getDocumentosConSaldoByProveedor($args['idProveedor']);
        //Recorremos los pagos para diferenciar docuemntos y notas de credito
        foreach ($pagos as $pago){
            //Verifiacmos si es un documento o una nota de credito a traves de tipo de documento
            if($pago['tipo_documento'] == 0){
                $response['pagos'][] = $pago;
            }else{
                $response['notas'][] = $pago;
            }
        }

        return $response;
    }

    //Funcion para abonar a una factura
    public function guardarAbonoFactura($args=[]){
        //Instanciamos el modelo de abono a factura
        $abono = new GmcvPagoFactura();
        $response = array(
            'status' => true,
            'message' => 'Abono guardado con exito'
        );
        //Buscamos si la factura ya tiene un abono con el mismo id de pago
        $abonoExistente = $abono->getByidPagoidFacturaObject($args['id_documento_pago'],$args['id_factura']);
        //Consultammos si el docummento puede tiene saldo disponible para abonar
        $saldoDisponible = GmcvPago::getSaldoDispPago($args['id_documento_pago']);
        //Si el saldo disponible es menor al monto del abono, retornamos un mensaje de error
        if($saldoDisponible['saldo_disponible'] < $args['monto_abono']){
            return array(
                'status' => false,
                'message' => 'El monto del abono es mayor al saldo disponible'
            );
        }
        //Si recibimos false no lo encontramos por lo que lo insertamos directamente
        if($abonoExistente == false){
            //Asignamos los valores a las propiedades del modelo
            $abono->id_pago = $args['id_documento_pago'];
            $abono->id_compra_factura = $args['id_factura'];
            $abono->monto = $args['monto_abono'];
            //Guardamos el abono
            $abono->create();
            //Revisamos que no haya ocurrido un error al guardar el abono
            if($abono->id == false){
                $response['status'] = false;
                $response['message'] = 'Error al guardar el abono';
            }
        }else{
            //Si encontramos un abono con el mismo id de pago y factura, actualizamos el monto
            $abonoExistente->monto = $abonoExistente->monto + $args['monto_abono'];
            //Actualizamos el abono
            $abonoExistente->update();
            //Revisamos que no haya ocurrido un error al guardar el abono
            if($abonoExistente->id == false){
                $response['status'] = false;
                $response['message'] = 'Error al guardar el abono';
            }
        }
        //Revisamos nuevamente el saldo disponible si ya no tinene saldo disponible lo consumido (status 25)
        $saldoDisponible = GmcvPago::getSaldoDispPago($args['id_documento_pago']);
        if($saldoDisponible['saldo_disponible'] == 0){
            $pago = GmcvPago::getByIdObject($args['id_documento_pago']);
            $pago->id_status = 25;
            $pago->update();
        }
        // //Si la factura ya no tiene saldo disponible la marcamos como pagada (status 25)
        // //Instanciamos el modelo de compra factura
        // $compraFactura = new CompraFactura();
        // $factura = $compraFactura->getByIdObject($args['id_factura']);
        // //Obtnemmos el saldo disponible de la factura
        // $saldoFactura = $compraFactura->getSaldoFactura($args['id_factura']);
        // //Si el saldo de la factura es igual a 0 la marcamos como pagada    
        // if($saldoFactura['saldoPendiente'] == 0){
        //     $factura->id_status = 25;
        //     $factura->update();
        // }
        $response = array(
            'status' => true,
            'message' => 'Abono guardado con exito'
        );

        return $response;
    }

    //Elimnar un abono
    public function eliminarAbonoFactura($args=[]){
        //Instanciamos el modelo de abono a factura
        $abono = new GmcvPagoFactura();
        //Buscamos por id de pago y id de factura
        $abonoExistente = $abono->getByidPagoidFacturaObject($args['id_documento_pago'],$args['id_factura']);
        //Si encontramos el abono lo eliminamos
        $response = array(
            'status' => true,
            'message' => 'Abono eliminado con éxito'
        );
        if($abonoExistente){
            $abonoExistente->delete();
        }else{
            $response['status'] = false;
            $response['message'] = 'No se encontró el abono';
        }
        return $response;
    }

    //alcenamos un nuevo pago
    public function nuevoDocumentoPago($args=[]){
        //Instanciamos el modelo de pago
        $pago = new GmcvPago();
        //Buscamos el uuid del documento para verificar que no exista
        $uuidExistente = $pago->getByUuid($args['uuid_documento']);
        //Si encontramos un documento con el mismo uuid, retornamos un mensaje de error
        if($uuidExistente){
            return array(
                'status' => false,
                'message' => 'Ya existe un documento con el mismo UUID'
            );
        }


        //Asignamos los valores a las propiedades del modelo
        $pago->id_proveedor = $args['id_proveedor'];
        $pago->monto = $args['monto_documento'];
        $pago->fecha = $args['fecha_documento'];
        $pago->uuid = $args['uuid_documento'];
        $pago->tipo = $args['documento'];
        $pago->id_status = 4;
        //Revisamos que no haya ocurrido un error al guardar el pago
        if($pago->create() == false){
            return array(
                'status' => false,
                'message' => 'Error al guardar el pago'
            );
        }
        return array(
            'status' => true,
            'message' => 'Pago guardado con éxito'
        );
    }

    public function getDocumentoPago($args=[]){
        return GmcvPago::getById($args['id_documento_pago']);
    }

    public function updateDocumentoPago($args=[]){
        //Instanciamos el modelo de pago
        $pago = GmcvPago::getByIdObject($args['id_documento_pago']);
        //Asignamos los valores a las propiedades del modelo
        $pago->monto = $args['monto_documento'];
        $pago->fecha = $args['fecha_documento'];
        $pago->uuid = $args['uuid_documento'];
        $pago->tipo = $args['tipo_documento'];
        //Guardamos el pago
        $pago->update();
        //Revisamos que no haya ocurrido un error al guardar el pago
        if($pago->id == false){
            return array(
                'status' => false,
                'message' => 'Error al guardar la edición del pago'
            );
        }
        return array(
            'status' => true,
            'message' => 'Pago actualizado con éxito'
        );


    }

    //Get pagos por fecha
    public function getDocumentosPagoRangoFechas($args=[]){
        return GmcvPago::getDocumentosByProveedorFechas($args['id_proveedor'],$args['fecha_inicio'],$args['fecha_fin'],$args['tipoDocumento']);
    }


}




?>
