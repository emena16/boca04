<?php

//Array de modelos a incluir según sea necesario
$modelos = ['CompraFacturaProd', 'Compra', 'CompraFactura'];
include 'header.php';

class CompraFacturaProdController {
    public function create($data) {
        $compraFacturaProd = new CompraFacturaProd();
        $compraFacturaProd->id_compra_factura = $data['id_compra_factura'];
        $compraFacturaProd->id_prod_compra = $data['id_prod_compra'];
        $compraFacturaProd->caducidad = $data['caducidad'];
        $compraFacturaProd->cantidad_aceptada = $data['cantidad_aceptada'];
        $compraFacturaProd->cantidad_rechazada = $data['cantidad_rechazada'];
        $compraFacturaProd->descuento = $data['descuento'];
        $compraFacturaProd->descuento_porcentaje = $data['descuento_porcentaje'];
        $compraFacturaProd->ajuste_bruto = $data['ajuste_bruto'];
        $compraFacturaProd->costo_unitario_bruto = $data['costo_unitario_bruto'];
        $compraFacturaProd->create();

        return db::insert_id();
    }

    public function read($id) {
        return CompraFacturaProd::getById($id);
    }

    public function update($id, $data) {
        $compraFacturaProd = CompraFacturaProd::getById($id);
        if (!$compraFacturaProd) {
            return false;
        }
        $compraFacturaProd->id_compra_factura = $data['id_compra_factura'];
        $compraFacturaProd->id_prod_compra = $data['id_prod_compra'];
        $compraFacturaProd->caducidad = $data['caducidad'];
        $compraFacturaProd->cantidad_aceptada = $data['cantidad_aceptada'];
        $compraFacturaProd->cantidad_rechazada = $data['cantidad_rechazada'];
        $compraFacturaProd->descuento = $data['descuento'];
        $compraFacturaProd->descuento_porcentaje = $data['descuento_porcentaje'];
        $compraFacturaProd->ajuste_bruto = $data['ajuste_bruto'];
        $compraFacturaProd->costo_unitario_bruto = $data['costo_unitario_bruto'];
        $compraFacturaProd->update();

        return true;
    }

    public function delete($id) {
        $compraFacturaProd = CompraFacturaProd::getById($id);
        if (!$compraFacturaProd) {
            return false;
        }
        $compraFacturaProd->delete();

        return true;
    }

    //Obtenemos los productos de una factura por id de factura
    public function getProductosByFactura($args = []) {
        $response = array();

        $compraFacturaProd = new CompraFacturaProd();
        $compra = new Compra();
        //Obtnemos los productos de una factura por id de factura
        $prodFactura =  $compraFacturaProd->getProductsByFacturaId($args['id_factura']);

        //Obtnemos los productos de una orden de compra por id de compra
        $productos = $compra->getProductosRestantesOrdenCompra($args['id_prod_compra']);

        $productosRequest = array();

        //Recorremos los productos de la orden de compra e igualamos a 0 la cantidad aceptada, rechazada, restante, total_cantidad_aceptada y total_cantidad_rechazada
        foreach ($productos as &$producto) {
            $producto['cant_aceptada'] = 0;
            $producto['cant_rechazada'] = 0;
            $producto['restante'] = $producto['cantidad'];
            $producto['total_cantidad_aceptada'] = 0;
            $producto['total_cantidad_rechazada'] = 0;
            $producto['caducidad'] = "";
            //Recorremos los productos de la factura y si el id de producto de la orden de compra es igual al id de producto de la factura, igualamos los valores de cantidad aceptada y rechazada
            foreach ($prodFactura as $prodFact) {
                if ($producto['id_prod'] == $prodFact['id_prod']) {
                    $producto['cant_aceptada'] = $prodFact['total_cantidad_aceptada'];
                    $producto['cant_rechazada'] = $prodFact['total_cantidad_rechazada'];
                    $producto['restante'] = 0;
                    $producto['total_cantidad_aceptada'] = round($prodFact['total_cantidad_aceptada'], 2);
                    $producto['total_cantidad_rechazada'] = round($prodFact['total_cantidad_rechazada'], 2);
                    $producto['caducidad'] = $prodFact['caducidad'];
                }
            }

        }

        //Obntenemos los datos de la factura por id de factura
        $factura = CompraFactura::getById($args['id_factura']);

        $response['productos'] = $productos;
        $response['factura'] = $factura;
        return json_encode($response);
    }

    //Obtenemos las facturas de una compra por id de compra
    public function getFacturasByCompraId($args = []) {
        $compraFacturaProd = new CompraFacturaProd();
        return $compraFacturaProd->getFacturasByCompraId($args['id_compra']);
    }

    //////// VALICACION DE Entrada ////////

    //Obtenemos los productos de una factura por id de factura
    public function getProductosByFacturaValidacion($args = []) {
        $response = array();
        //Instanciamos los modelos que vamos a utilizar
        $compraFacturaProd = new CompraFacturaProd();
        //Obtnemos los productos de una factura por id de factura
        $prodFactura =  $compraFacturaProd->getProductsByFacturaIdValidate($args['id_factura']);

        //Obntenemos los datos de la factura por id de factura
        $factura = CompraFactura::getById($args['id_factura']);
        //Calculamos los dias de alerta de la factura entre la fecha de llegada y la fecha de alerta
        
        // Crear objetos DateTime para cada fecha   
        $date1 = new DateTime($factura['fecha_llegada']);
        $date2 = new DateTime($factura['fecha_alerta']);
        // Calcular la diferencia
        $interval = $date1->diff($date2);
        // Obtener la diferencia en días
        $differenceInDays = $interval->days;
        $factura['dias_alerta'] = $differenceInDays;
        //Version corta del calculo de dias, se crean objetos DateTime y se calcula la diferencia en dias
        // $factura['dias_alerta'] = (new DateTime($factura['fecha_llegada']))->diff(new DateTime($factura['fecha_alerta']))->days;

        $response['productos'] = $prodFactura;
        $response['factura'] = $factura;
        return json_encode($response);
    }

    /////// VALIDACION DE COSTO ///////
    
    public function getProductosByFacturaValidacionCosto($args = []) {
        $response = array();
        //Instanciamos los modelos que vamos a utilizar
        $compraFacturaProd = new CompraFacturaProd();
        //Obtenemos los productos de una factura por id de factura
        $prodFactura =  $compraFacturaProd->getProductsByFacturaIdValidateCost($args['id_factura'], $args['ajusteDescuento']);

        //Obtenemos los datos de la factura por id de factura
        $factura = CompraFactura::getById($args['id_factura']);
        $bodegaFact = CompraFactura::getBodegaByFactura($args['id_factura']);
        $statusFact = CompraFactura::getStatusByFactura($args['id_factura']);

        $factura['nombreBodega'] = $bodegaFact['nombreBodega'];
        $factura['status'] = $statusFact['id'];
        $factura['nombreStatus'] = $statusFact['nombreStatus'];
        //Obtenemos los descuentos de la factura
        $response['productos'] = $prodFactura['productos'];
        $response['descuentosAntesCP'] = $prodFactura['descuentosAntesCP'];
        $response['descuentosDespCP'] = $prodFactura['descuentosDespCP'];
        $response['factura'] = $factura;
        $response['nombreDescuentoAjuste'] = $prodFactura['nombreDescuentoAjuste'];
        $response['siglaDescuentoAjuste'] = $prodFactura['siglaDescuentoAjuste'];

        return $response;
    }

    

}
?>
