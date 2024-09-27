<?php

//Array de modelos a incluir según sea necesario
$modelos = ['CompraFactura', 'CompraFacturaProd', 'Compra', 'ProdCompra', 'Almacen', 'ProdMedidaCompra', 'GmcvPrecio','ProveDias', 'GmcvPago','GmcvCompraFactDescuento'];
include "header.php";

class CompraFacturaController {
    public function create($data) {
        $compraFactura = new CompraFactura();
        $compraFactura->id_compra = $data['id_compra'];
        $compraFactura->id_status = $data['id_status'];
        $compraFactura->uuid = $data['uuid'];
        $compraFactura->fecha = $data['fecha'];
        $compraFactura->tipo = $data['tipo'];
        $compraFactura->ajuste = $data['ajuste'];
        $compraFactura->id_status_pago = $data['id_status_pago'];
        $compraFactura->validada = $data['validada'];
        $compraFactura->fecha_compromiso = $data['fecha_compromiso'];
        $compraFactura->fecha_llegada = $data['fecha_llegada'];
        $compraFactura->fecha_alerta = $data['fecha_alerta'];
        $compraFactura->create();

        return db::insert_id();
    }

    public function read($id) {
        return CompraFactura::getById($id);
    }

    public function getFacturasByCompraId($args=[]) {
        return json_encode(CompraFactura::getFacturasByCompraId($args['id_compra']));
    }

    public function checkUuid($args=[]) {
        return json_encode(CompraFactura::checkUuid($args['uuid']));
    }
    
    //Vamos a crear una factura nueva e insertar productos en ella
    public function addFacturaToOrden($args=[]) {
        //Revisamos que no vengan datos vacios antes de ingresar la factura
        if (empty($args['factura']) || empty($args['productos'])) {
            return json_encode(array(
                'status' => false, 
                'message' => 'Error, No se han enviado datos'
            ));
        }
        //Verificamos que los campos de la factura no estén vacíos
        //Si el campo id_compra está vacío, no hacemos nada
        if (empty($args['factura']['id_compra'])) {
            return json_encode(array(
                'status' => false, 
                'message' => 'Error de memoria, por favor recargue la página'
            ));
        }

        //Si el campo uuid está vacío, no hacemos nada
        if (empty($args['factura']['uuid'])) {
            return json_encode(array(
                'status' => false, 
                'message' => 'Por favor ingrese el UUID de la factura'
            ));
        }
        //Si el campo fecha_factura está vacío, no hacemos nada
        if (empty($args['factura']['fecha_factura']) || strtotime($args['factura']['fecha_factura']) > strtotime(date('Y-m-d'))) {
            return json_encode(array(
                'status' => false, 
                'message' => 'Por favor revise la fecha de la factura'
            ));
        }

        //Si el campo fecha_llegada está vacío, no hacemos nada
        if (empty($args['factura']['fecha_llegada']) || strtotime($args['factura']['fecha_llegada']) > strtotime(date('Y-m-d'))) {
            return json_encode(array(
                'status' => false, 
                'message' => 'Por favor revise la fecha de llegada de la factura'
            ));
        }

        //Si el campo productos está vacío, no hacemos nada
        if (empty($args['productos'])) {
            return json_encode(array(
                'status' => false, 
                'message' => 'Error, No hay productos en la factura'
            ));
        }

        //No puede haber caducidades vacías y tampoco puede haber mas unidades rechazadas que unidades facturadas
        foreach ($args['productos'] as $producto) {

            if (empty($producto['caducidad']) || strtotime($producto['caducidad']) < strtotime(date('Y-m-d'))) {
                return json_encode(array(
                    'status' => false, 
                    'message' => 'Por favor verifique las caducidades de los productos'
                ));
            }
            if ($producto['unidadesRechazadas'] > $producto['unidadesFacturadas']) {
                return json_encode(array(
                    'status' => false, 
                    'message' => 'La cantidad rechazada de un producto no puede ser mayor a la cantidad facturada, por favor verifique los datos'
                ));
            }


        }
        //Verificamos que el uuid de la factura no exista
        $compraFactura = new CompraFactura();
        $compra = Compra::getByIdObject($args['factura']['id_compra']);
        if (!$compra) {
            return json_encode(array(
                'status' => false, 
                'message' => 'Compra no encontrada, si el problema persiste, por favor contacte a soporte'
            ));
        }

        $uuidExiste = $compraFactura->checkUuid($args['factura']['uuid']);
        if($uuidExiste['exists']){
            return json_encode(array(
                'status' => false, 
                'message' => 'El UUID de la factura ya existe'
            ));
        }


        $factura = $args['factura'];
        $productos = $args['productos'];
        $productosRequest = array();
        $error = false;
        foreach ($productos as $producto) {
            $producto['id_prod_compra'] = $producto['id_prod_compra'];
            $producto['caducidad'] = $producto['caducidad'];
            $producto['cantidad_aceptada'] = round($producto['unidadesFacturadas']-$producto['unidadesRechazadas'], 4);
            $producto['cantidad_rechazada'] = round($producto['unidadesRechazadas'], 4);
            $producto['cantidad_facturada'] = round($producto['unidadesFacturadas'], 4);
            $producto['descuento'] = isset($producto['descuento']) ? $producto['descuento'] : 0;
            $producto['descuento_porcentaje'] = isset($producto['descuento_porcentaje']) ? $producto['descuento_porcentaje'] : 0;
            $producto['ajuste_bruto'] = isset($producto['ajuste_bruto']) ? $producto['ajuste_bruto'] : 0;
            //Obtenemos el costo unitario bruto a traves de GmcvPrecio en funcion a la fecha de la factura y la bodega
            $costosDB = GmcvPrecio::getPrecioListaByIdProd($producto['id_prod'], $factura['fecha_factura'], $compra->id_bodega);
            if ($costosDB['costoPactadoDB'] > 0) {
                $producto['costo_unitario_bruto'] = $costosDB['costoPactadoDB'];
            }else{
                $producto['costo_unitario_bruto'] = isset($producto['costo_unitario']) ? $producto['costo_unitario'] : 0;
            }

            //No puede haber unidades facturadas negativas
            if ($producto['cantidad_facturada'] < 0) {
                $error = true;
                break;
            }

            //No puede haber costos menores a $0.01 por lo que si los encontramos lo igualamos al minimo
            if ($producto['costo_unitario_bruto'] < 0.01) {
                $producto['costo_unitario_bruto'] = 0.01;
            }

            //Agregamos el producto al array de productos
            $productosRequest[] = $producto;
        }

        if ($error) {
            return json_encode(array(
                'status' => false, 
                'message' => 'La cantidad facturada no puede ser negativa'
            ));
        }

        //Calculamos la fecha comprmiso y la fecha alerta instanciamos el modelo de provdias para obtner los dias de alerta
        $compra = Compra::getByIdObject($factura['id_compra']);

        $proveDias = ProveDias::getByProvBod($compra->id_prov, $compra->id_bodega);
        $factura['fecha_compromiso'] = date('Y-m-d', strtotime($factura['fecha_llegada'] . ' + ' . $proveDias['dias_compromiso'] . ' days'));
        $factura['fecha_alerta'] = date('Y-m-d', strtotime($factura['fecha_llegada'] . ' + 2 weeks'));
        //Si la fecha de alerta es mayor a la fecha compromiso, la igualamos a la fecha compromiso
        if ($factura['fecha_alerta'] > $factura['fecha_compromiso']) {
            $factura['fecha_alerta'] = $factura['fecha_compromiso'];
        }
        
        //Preparamos la factura de acuerdo a las entidades de la base de datos para insertarla de acuerdo a lo comentado arriba
        $factura = [
            'id_compra' => $factura['id_compra'],
            'id_status' => 4,
            'uuid' => $factura['uuid'],
            'fecha' => $factura['fecha_factura'],
            'tipo' => 2,
            'ajuste' => isset($factura['ajuste']) ? $factura['ajuste'] : 0,
            'id_status_pago' => 0,
            'validada' => 0,
            'fecha_compromiso' => $factura['fecha_compromiso'],
            'fecha_llegada' => $factura['fecha_llegada'],
            'fecha_alerta' => $factura['fecha_alerta'],
        ];
        
        //Escribimos una copia de la factura en un archivo de texto
        // file_put_contents('add_factura.txt', json_encode($factura)."\n");

        //Creamos la factura instanciando el modelo
        $this->create($factura);  
        //Obtenemos el id de la factura recién creada
        $id_factura = db::insert_id();
        //Insertamos los productos en la factura a través de su modelo, instanciamos el modelo de factura producto
        $compraFacturaProd = new CompraFacturaProd();

        //Obtnemos el id_prod_compra

        // Llamamos al método addProductsToFactura que recibe el id de la factura y los productos a insertar
        $addProduct = $compraFacturaProd->addProductsToFactura($id_factura, $productosRequest);

        $response = array(
            'id_factura' => $id_factura,
            'status' => $addProduct
        );
        return json_encode($response);
    } //addFacturaToOrden


    //Actualizamos una factura ya existente
    public function actualizaFacturaEntrada($args = []) {
        $factura = $args['factura'];
        $productos = $args['productos'];

        //Si productos esta vacio, no hacemos nada
        if (empty($productos)) {
            return json_encode(array(
                'status' => false, 
                'message' => 'Error, No hay productos en la factura'
            ));
        }

        $productosRequest = array();
        $error = false;
        foreach ($productos as $producto) {
            $producto['id_prod_compra'] = $producto['id_prod_compra'];
            $producto['caducidad'] = $producto['caducidad'];
            $producto['cantidad_aceptada'] = round($producto['unidadesFacturadas']-$producto['unidadesRechazadas'], 4);
            $producto['cantidad_rechazada'] = round($producto['unidadesRechazadas'], 4);
            $producto['cantidad_facturada'] = round($producto['unidadesFacturadas'], 4);
            $producto['descuento'] = isset($producto['descuento']) ? $producto['descuento'] : 0;
            $producto['descuento_porcentaje'] = isset($producto['descuento_porcentaje']) ? $producto['descuento_porcentaje'] : 0;
            $producto['ajuste_bruto'] = isset($producto['ajuste_bruto']) ? $producto['ajuste_bruto'] : 0;
            $producto['costo_unitario_bruto'] = isset($producto['costo_unitario']) ? $producto['costo_unitario'] : 0;           

            //No puede haber unidades facturadas negativas
            if ($producto['cantidad_facturada'] < 0) {
                $error = true;
                break;
            }
            //Agregamos el producto al array de productos
            $productosRequest[] = $producto;
        }
        if ($error) {
            return json_encode(array(
                'status' => false, 
                'message' => 'La cantidad facturada no puede ser negativa'
            ));
        }

        //Actualizamos la factura, para ello instanciamos el modelo de factura
        $compraFactura = new CompraFactura();
        $compraFactura->id = $factura['id'];
        $compraFactura->fecha = $factura['fecha_factura'];
        $compraFactura->fecha_llegada = $factura['fecha_llegada'];
        $compraFactura->uuid = $factura['uuid'];
        // Actualizamos la factura
        if (!$compraFactura->updateFacturaEntrada()) {
            return json_encode(array(
                'status' => false, 
                'message' => 'Error al actualizar la factura, por favor verifique los datos'
            ));
        }

        //Vaciamos los productos de la factura
        $compraFacturaProd = new CompraFacturaProd();
        $compraFacturaProd->deleteProductsByFacturaId($factura['id']);

        //Agregamos los productos a la factura
        $compraFacturaProd->addProductsToFactura($factura['id'], $productosRequest);

        return json_encode(array(
            'status' => true, 
            'message' => 'Factura actualizada correctamente'
        ));

    }

    //Creamos un metodo para ingresar la mercancia de una factura en almacen, para ello recibimos el id de la factura y el id de compra
    public function ingresarFacturaAlmacen($args=[]){
        $id_factura = $args['id_factura'];
        $id_compra = $args['id_compra'];

        // Actualizamos la factura, Instanciamos el modelo de compraFactura y obtenemos el objeto a traves del id_factura
        $compraFactura = new CompraFactura();
        $compraFacturaObject = $compraFactura->getByIdObject($id_factura);
        
        if(!$compraFacturaObject){
            return json_encode(array(
                'status' => false, 
                'message' => 'Error al actualizar la factura.'
            ));
        }

        //Si la factura ya fue ingresada a almacén, no hacemos nada
        if ($compraFacturaObject->ingreso_almacen == 1) {
            return json_encode(array(
                'status' => false, 
                'message' => 'La factura ya fue ingresada a almacén'
            ));
        }
        
        //Actualizamos la factura
        $compraFacturaObject->ingreso_almacen = 1;
        $compraFacturaObject->update();


        //Obtenemos la compra a la que pertenece la factura
        $compra = Compra::getById($id_compra);
        if (!$compra) {
            return json_encode(array(
                'status' => false, 
                'message' => 'Compra no encontrada'
            ));
        }

        //Obtenemos los productos de la factura 
        $compraFacturaProd = new CompraFacturaProd();
        $productosFactura = $compraFacturaProd->getProductsByFacturaId($id_factura);
        

        //Recorremos los productos de la factura para irlos insertando en almacen
        foreach ($productosFactura as $producto) {
            //Obtenemos el producto de la compra
            $ProdCompra = ProdCompra::getById($producto['id_prod_compra']);
            if (!$ProdCompra) {
                return json_encode(array(
                    'status' => false, 
                    'message' => 'Producto de compra no encontrado'.$producto['id_prod_compra']." - ".$producto['id_prod']." - ".$producto['id_factura']
                ));
            }

            //Si no hay cantidad aceptada continua con el siguiente producto
            if ($producto['total_cantidad_aceptada'] < 1) {
                continue;
            }

            //Obtnemos la unidad de medida de compra de la tabla prod_med_comp,
            $prodMedidaCompra = ProdMedidaCompra::getByIdProd($producto['id_prod']);
            

            //Antes de insertar primero verificamos si ya existe un registros en la DB
            $almacenAuxiliar = new Almacen();
            $almacenAuxiliar = $almacenAuxiliar->getAlmacenByIdCompraObject($producto['id_prod_compra']);
            if ($almacenAuxiliar) {
                //Si ya existe un registro en almacen, actualizamos la cantidad
                $almacenAuxiliar->cantidad = $almacenAuxiliar->cantidad + intval($producto['total_cantidad_aceptada'] * $prodMedidaCompra[0]['cantidad']);
                $almacenAuxiliar->ucompra = $almacenAuxiliar->ucompra + floatval($producto['total_cantidad_aceptada']);
                $almacenAuxiliar->factura = $almacenAuxiliar->factura . ', ' . $compraFacturaObject->uuid;
                $almacenAuxiliar->update();
                //Destruimos el objeto
                unset($almacenAuxiliar);
                continue;
            }
            //Insertamos el producto en almacen
            $almacen = new Almacen();
            $almacen->id_compra = $producto['id_prod_compra'];
            $almacen->cantidad = intval($producto['total_cantidad_aceptada'] * $prodMedidaCompra[0]['cantidad']); //Cantidad aceptada * cantidad de la medida de compra
            $almacen->id_status = 4;
            $almacen->ucompra = floatval($producto['total_cantidad_aceptada']);
            $almacen->alta = date('Y-m-d H:i:s');
            $almacen->id_bodega = $compra['id_bodega'];
            $costoIngresoCatalogo = GmcvPrecio::getPrecioListaByIdProd($producto['id_prod'], $compraFacturaObject->fecha, $almacen->id_bodega);
            // file_put_contents('costoIngresoCatalogo.txt', json_encode($costoIngresoCatalogo)."\n");
            if ($costoIngresoCatalogo['precioListaCatalogo'] < 0.01) {
                //LOG
                $almacen->costo_unitario = floatval($producto['costo_unitario']);
            }else{
                $almacen->costo_unitario = floatval($costoIngresoCatalogo['costoIngresoDB']);
            }
            $almacen->caducidad = $producto['caducidad'];
            $almacen->extraido = 0;
            $almacen->factura = $compraFacturaObject->uuid;
            //Insertamos el producto en almacen
            $almacen->create();

            //Destruimos el objeto
            unset($almacen);
            // unset($prodMedidaCompra);
        }

        //Si llegamos aquí, todo salió bien
        return json_encode(array(
            'status' => true, 
            'debug' => $prodMedidaCompra,
            'message' => 'Factura ingresada a almacén correctamente'
        ));
    }//Fin de ingresarFacturaAlmacen

    //Creamos un metodo para actualizar la mercancia de una factura en almacen, para ello recibimos el id de la factura y el id de compra
    public function actualizarFacturaAlmacen($args=[]){
        /*
        En caso de error: por que la cantidad extraida supere la cantidad nueva en el almacen,
        se debe avisar con un mensaje describiendo que la cantidad extraida es menor a la cantidad nueva en el almacen la compra, la factura y el id de producto al usuario por lo que 
        tiene que avisar a soporte para que se haga una revisión manual de la cantidad extraida.
        IMPORTANTE!!!
        Los totales en prod_compra aun no se actualizan, lo hacen hasta que se cierre la orden de compra de compra en la vista validarEntrada
        a traves del metodo CompraFactura->confirmaCerrarValidacionOrdenCompra
        */

        $id_factura = $args['id_factura'];
        $id_compra = $args['id_compra'];

        //Obtenemos la compra a la que pertenece la factura
        $compra = Compra::getById($id_compra);

        // Actualizamos la factura, Instanciamos el modelo de compraFactura y obtenemos el objeto a traves del id_factura
        $compraFactura = new CompraFactura();
        $compraFacturaObject = $compraFactura->getByIdObject($id_factura);
        

        if(!$compraFacturaObject){
            return json_encode(array(
                'status' => false, 
                'message' => 'Error al actualizar la factura al status: ingresada a almacén.'
            ));
        }

        //Si la factura ya fue ingresada a almacén, no hacemos nada
        // if ($compraFacturaObject->ingreso_almacen == 1) {
        //     return json_encode(array(
        //         'status' => false, 
        //         'message' => 'Esta factura aún no ha sido ingresada a almacén, por favor ingrese la factura primero en: ENTRADA POR PROVEEDORES'
        //     ));
        // }
        //Actualizamos la factura
        $compraFacturaObject->validada = 1;
        $compraFacturaObject->fecha_compromiso = $args['factura']['fecha_compromiso'];
        $compraFacturaObject->fecha_llegada = $args['factura']['fecha_llegada'];    
        //Calculamos la fecha alerta sumando los días de alerta a la fecha compromiso
        $compraFacturaObject->fecha_alerta = date('Y-m-d', strtotime($args['factura']['fecha_compromiso'] . ' - ' . $args['factura']['dias_alerta'] . ' days'));
        $compraFacturaObject->ingreso_almacen = 1;
        //Actualizamos los impuestos trasladados
        //Actualizamos la factura
        $facturaActualizada =  $compraFacturaObject->update();
        //Verificamos que la facturar a sido actualizada
        if(!$facturaActualizada){
            return json_encode(array(
                'status' => false, 
                'message' => 'Error al actualizar la factura, por favor verifique los datos'
            ));
        }
        //Instanciamos el modelo de factura producto
        $compraFacturaProd = new CompraFacturaProd();
        /**
         * En este foreach vamos a recorrer los productos de la factura y vamos a actualizar el almacen en función a la cantidad aceptada
         * es decir, vamos a actualizar la factura y el almacen en función a la cantidad aceptada de cada producto que viene de la validación
         * de la factura
         * 1.- Obtenemos el producto de la factura
         * 2.- Obtenemos el producto de almacen
         * 3.- Actualizamos el almacen en función a id_prod_compra y a los productos que recibimos de la validacion
         * 4.- Actualizamos el producto de la factura
         * 5.- Continuamos con el siguiente producto
         * 6.- Si no existe el producto en almacen, avisamos que hubo un error interno
         */
        
        foreach ($args['productos'] as $producto) {
            //Verificamo que el producto exista en la factura
            $prodFactura = $compraFacturaProd->getByIdObject($producto['id_cfp']);
            if (!$prodFactura) {
                return json_encode(array(
                    'status' => false, 
                    'message' => 'Producto: '.$producto['id_prod']." - ProdCompra: ".$producto['id_prod_compra']." - Factura: ".$producto['id_cfp']." no encontrado"
                ));
            }
            
            //Actualizamos el almacen en funcion a id_prod_compra y a los productos que recibimos de la validacion
            $almacen = new Almacen();
            //Obtenemos el producto de almacen
            $almacen = $almacen->getAlmacenByIdCompraObject($producto['id_prod_compra']);
            //Obtnemos la unidad medida de compra
            $prodMedidaCompra = ProdMedidaCompra::getByIdProd($producto['id_prod']);
            if ($almacen) {
                //Obtnemos la fila en gmcv_compra_factura_prod para saber la cantidad aceptada
                $prodFacturaAux = $compraFacturaProd->getByIdObject($producto['id_cfp']);
                //Antes verificamos que el campo  almacen->extraido no sea mayor a la cantidad nueva en el almacen por que si es asi, no podemos hacer nada y declaramos un error, detallando la compra, la factura y el id de producto
                if ($almacen->extraido > intval($prodFacturaAux->cantidad_aceptada * $prodMedidaCompra[0]['cantidad']) + intval($producto['cantidad_aceptada'] * $prodMedidaCompra[0]['cantidad'])) {
                    return json_encode(array(
                        'status' => false,
                        'message' => 'La cantidad extraída es mayor a la cantidad nueva en el almacen, por favor contacte a soporte. Compra: '.$id_compra.' - Factura: '.$id_factura.' - Producto: '.$producto['id_prod']." - ProdCompra: ".$producto['id_prod_compra']
                    ));
                }
                // La cantidad la recalculamos en función a la cantidad aceptada antes de actualizar por las nuevas cantidades que vienen de la valiccion
                $almacen->cantidad = max(0, ($almacen->cantidad - intval($prodFacturaAux->cantidad_aceptada * $prodMedidaCompra[0]['cantidad']) ) + intval($producto['cantidad_aceptada'] * $prodMedidaCompra[0]['cantidad']));
                //Recalculamos la cantidad de unidades de compra
                $almacen->ucompra = max(0, $almacen->ucompra - floatval($prodFacturaAux->cantidad_aceptada) + floatval($producto['cantidad_aceptada']));
                // $almacen->factura = $almacen->factura . ', ' . $compraFacturaObject->uuid;
                
                //Eliminamos todos los espacios en blanco de factura
                $almacen->factura = preg_replace('/\s+/', '', $almacen->factura);
                $arrayFacturas = explode(',', $almacen->factura);
                //Buscamos la factura en el array de facturas
                if (!in_array(str_replace(' ', '', $args['factura']['uuid']), $arrayFacturas)) {
                    //Si no existe la factura en el array, la agregamos
                    $arrayFacturas[] = $compraFacturaObject->uuid;
                    $almacen->factura = implode(',', $arrayFacturas);
                }
                //Actualizamos el costo unitario en funcion al GmcvPrecio a traves de la fecha de la factura y la bodega
                $costoIngresoCatalogo = GmcvPrecio::getPrecioListaByIdProd($producto['id_prod'], $compraFacturaObject->fecha, $compra['id_bodega']);
                if ($costoIngresoCatalogo['precioListaCatalogo'] < 0.01) {
                    //LOG por que no se encontró el costo en el catalogo
                    $almacen->costo_unitario = floatval($producto['costo_unitario']);
                }else{
                    $almacen->costo_unitario = floatval($costoIngresoCatalogo['costoIngresoDB']);
                }
                $almacen->update();
                //Destruimos el objeto
                unset($almacen);
            }else{
                // Para este caso vamos a verificar si aun la cantidad aceptada es 0 y si es asi, no hacemos nada en caso contrario vamos a insertar el producto en almacen
                if ($producto['cantidad_aceptada'] == 0) {
                    continue;
                }
                // Si no lo encontró quiere decir que durante la entrada no hubo nada que ingresar, es decir, no hubo cantidad aceptada
                //Instanciamos un nuevo modelo de almacen para insertar el producto
                $addRowAlmacen = new Almacen();
                $addRowAlmacen->id_compra = $producto['id_prod_compra'];
                $addRowAlmacen->cantidad = intval($producto['cantidad_aceptada'] * $prodMedidaCompra[0]['cantidad']); //Cantidad aceptada * cantidad de la medida de compra 
                $addRowAlmacen->id_status = 4;
                $addRowAlmacen->ucompra = floatval($producto['cantidad_aceptada']);
                $addRowAlmacen->alta = date('Y-m-d H:i:s');
                $addRowAlmacen->id_bodega = $compra['id_bodega'];
                //Actualizamos el costo unitario en funcion al GmcvPrecio a traves de la fecha de la factura y la bodega
                $costoIngresoCatalogo = GmcvPrecio::getPrecioListaByIdProd($producto['id_prod'], $compraFacturaObject->fecha, $compra['id_bodega']);
                if ($costoIngresoCatalogo['precioListaCatalogo'] < 0.01) {
                    //LOG por que no se encontró el costo en el catalogo
                    $addRowAlmacen->costo_unitario = floatval($producto['costo_unitario']);
                }else{
                    $addRowAlmacen->costo_unitario = floatval($costoIngresoCatalogo['costoIngresoDB']);
                }
                $addRowAlmacen->caducidad = $producto['caducidad'];
                $addRowAlmacen->extraido = 0;
                $addRowAlmacen->factura = $args['factura']['uuid'];
                //Insertamos el producto en almacen
                $addRowAlmacen->create();
                //Destruimos el objeto
                unset($addRowAlmacen);
            } // Fin if-else almacen

            // //Actualizamos el producto de la factura
            $prodFactura->caducidad = $producto['caducidad'];
            $prodFactura->cantidad_aceptada = $producto['cantidad_aceptada'];
            $prodFactura->cantidad_rechazada = $producto['cantidad_rechazada'];
            $prodFactura->cantidad_facturada = $producto['cantidad_facturada'];
            $prodFactura->descuento = $producto['descuento'];
            $prodFactura->costo_unitario_bruto = round(floatval($producto['costo_unitario_bruto']),4);
            $prodFactura->update();

        } // Fin foreach productos

        //Si llegamos aquí, todo salió bien
        return json_encode(array(
            'status' => true, 
            'message' => 'Factura validada correctamente'
        ));

    }

    //Obtnemos el resumen de la factura en funcion a un intervalo de fechas y un proveedor
    public function getResumenFacturasValidadas($args=[]){
        $resumen = (new CompraFactura())->getResumenFacturasValidadas($args['fechaInicio'], $args['fechaFin'], $args['idProveedor']);
        return json_encode($resumen);
    }


    //Creamos un metodo para actualizar los ajustes de una factura y los descuentos que se aplican a los productos
    public function actualizarAjustesFactura($args=[]){
        $factura = $args['factura'];
        // $productos = $args['productos'];
        //Comanzamos por llamar al modelo de factura
        $compraFactura = new CompraFactura();
        //Obtenemos la factura
        $compraFacturaObject = $compraFactura->getByIdObject($factura['id_factura']);
        //Si no existe la factura, no hacemos nada
        if (!$compraFacturaObject) {
            return json_encode(array(
                'status' => false, 
                'message' => 'Factura no encontrada'
            ));
        }
        //Actualizamos los descuentos de la factura
        $compraFacturaObject->id_status = 1;
        $compraFacturaObject->tipoAjuste = $factura['tipoAjuste'];
        //Instanciamos el modelo de gmcvcompraFactDescuento para obtener los descuentos de la factura
        $compraFactDescuento = new GmcvCompraFactDescuento();
        //Recorremos los descuentos globales y los insertamos en la tabla gmcv_compra_fact_descuento
        foreach ($factura['descuentosGlobales'] as $descuento) {
            $compraFactDescuento->id_compra_factura = $factura['id_factura'];
            $compraFactDescuento->descuento = $descuento['valor'];
            $compraFactDescuento->nota = $descuento['comentario'];
            $compraFactDescuento->create();
        }
        //Actualizamos el ajuste de la factura
        $compraFacturaObject->ajuste = $factura['ajusteDescuento'];
        $compraFacturaObject->update();

        //Recorremos los productos de la factura para actualizar los ajustes
        $compraFacturaProd = new CompraFacturaProd();
        foreach ($args['productos'] as $producto) {
            //Obtenemos el producto de la factura
            $prodFactura = $compraFacturaProd->getByIdObject($producto['id_cfp']);
            if (!$prodFactura) {
                return json_encode(array(
                    'status' => false, 
                    'message' => 'Error al actualizar la factura, por favor verifique los datos'
                ));
            }
            //Actualizamos el producto de la factura
            // $prodFactura->descuento = $producto['descuento'];
            // $prodFactura->descuento_porcentaje = $producto['descuento_porcentaje'];
            $prodFactura->ajuste_bruto = $producto['ajusteDescuento'];
            $prodFactura->update();
        }

        //Si llegamos aquí, todo salió bien
        return json_encode(array(
            'status' => true, 
            'message' => 'Factura actualizada correctamente'
        ));       

    }


    ///// Conciliación de facturas /////
    public function getFacturasConciliacion($args=[]) {
        // $facturas = CompraFactura::getFacturasConciliar($args['id_proveedor'], $args['fecha_inicio'], $args['fecha_fin'], $args['status']);
        // Procesamos la informacion de la facturas para obtener los productos de cada factura
        // $facturasResposne = array();
        // $facturaAux = array();
        // foreach ($facturas as $factura) {
        //     //Procesamos la informacion de la factura a traves de su copia
        // }
        $response = CompraFactura::getFacturasConciliar($args['proveedor'], $args['fechaInicio'], $args['fechaFin'], $args['estadoFactura']);
        return $response;
    }

    public function getSaldoFactura($args=[]) {
        //Instanciamos el modelo de CompraFactura
        $compraFactura = new CompraFactura();
        $saldoFactura = $compraFactura->getSaldoFactura($args['id_factura']);
        //Obtnemos los documentos del proveedor
        $gmcvPago = new GmcvPago();
        $documentos = $gmcvPago->getDocumentosConSaldoByProveedor($args['id_proveedor']);
        $response = array(
            'saldoFactura' => $saldoFactura,
            'documentos' => $documentos
        );

        return $response;
        

    }




        
}
?>
