<?php
ob_start();
$modelPath = '../models/';
$modelPath .= 'CompraFactura.php';
require_once $modelPath;
$nombreFichero = '';

switch ($_POST['ajusteDescuento']) {
    case '1':
        $nombreFichero = 'ajusteCP_';
        break;
    case '2':
        $nombreFichero = 'ajusteCI_';
        break;
    case '3':
        $nombreFichero = 'ajustePL_';
        break;
}

$factura = CompraFactura::getById($_POST['id_factura']);
$fichero = $nombreFichero.$factura['uuid'].".csv";

// var_dump($factura);

//Agregamos la cabecera para que el navegador entienda que es un archivo CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="'.$fichero.'"');
//Agregamos no cache
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
//Cabeceras de seguridad
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

$controllerPath = '../controllers/';

$controllerPath .= 'CompraFacturaProdController.php';

if (is_file($controllerPath)) {
    require_once $controllerPath;
}
else {
    echo json_encode(['error' => 'Controlador no encontrado.']);
    exit();
}

function limpiaModelo($modelo) {
    foreach ($modelo as $key) {
        $modelo[$key] = '';
    }
    return $modelo;
}

function calculaAjustePrecioLista($precio_lista, $costo_unitario_factura, $cantidad_facturada, $cantidad_rechazada) {
    if ($costo_unitario_factura > $precio_lista) {
        return ($costo_unitario_factura - $precio_lista) * ($cantidad_facturada - $cantidad_rechazada);
    } else {
        return 0;
    }
}

function calcularAjustesDescuentosAntesCP($descuentoAntesCP, $precio_lista, $cantidad_facturada, $cantidad_rechazada) {
    return ($descuentoAntesCP / 100) * $precio_lista * ($cantidad_facturada - $cantidad_rechazada);
}

function calcularAjustesDescuentosDespCP($descuentoDespCP, $precio_pactado, $cantidad_facturada, $cantidad_rechazada) {
    return ($descuentoDespCP / 100) * $precio_pactado * ($cantidad_facturada - $cantidad_rechazada);
}


function ajustePactadoExtra($suma_ajustes, $costo_unitario_factura, $precio_pactado, $cantidad_facturada, $cantidad_rechazada) {
    // Sumar los valores del array $ajustes (P2:S2)
     

    // Verificar la condición
    if ($suma_ajustes == 0 && $costo_unitario_factura > $precio_pactado) {
        return ($costo_unitario_factura - $precio_pactado) * ($cantidad_facturada - $cantidad_rechazada);
    } else {
        return 0;
    }
}

function calculaAjusteIngresoExtra($suma_ajustes,$suma_descuentos_despues_cp, $costo_unitario_factura, $costo_ingreso_pactado, $cantidad_facturada, $cantidad_rechazada) {
    // Verificar la condición
    if ($suma_ajustes == 0 && $suma_descuentos_despues_cp == 0 && $costo_unitario_factura > $costo_ingreso_pactado) {
        return ($costo_unitario_factura - $costo_ingreso_pactado) * ($cantidad_facturada - $cantidad_rechazada);
    } else {
        return 0;
    }
}


function ajusteCI(){
    //Creamos el array de argumentos de acuerdo al estadanar de la funcion
    $args = [
        'id_factura' => $_POST['id_factura'],
        'ajusteDescuento' => $_POST['ajusteDescuento']
    ];
    $compraFacturaProdController = new compraFacturaProdController();
    $response = $compraFacturaProdController->getProductosByFacturaValidacionCosto($args);

    $productos = $response['productos'];

    //Generamos las cabeceras del reporte
    $cabeceras1 = array('Nombre', 'Precio Lista');
    $nombreAjustesAntes = array();
    $nombreAjustesDesp = array();
    //Los descuentos son dinamicos, por lo que se generan las cabeceras de acuerdo a los descuentos que se obtienen de la factura
    foreach ($response['descuentosAntesCP'] as $descuento) {
        $cabeceras1[] = $descuento['nombre'];
        $nombreAjustesAntes[] = 'Ajuste'.$descuento['nombre'];
    }
    $cabeceras2 = array('Precio Pactado', 'Costo Unitario Factura', 'DIF');
    //Agregamos los descuentos despues de CP
    foreach ($response['descuentosDespCP'] as $descuento) {
        $cabeceras2[] = $descuento['nombre'];
        $nombreAjustesDesp[] = 'Ajuste'.$descuento['nombre'];
    }
    $cabeceras3 = array('Costo Ingreso Pactado', 'Costo Ingreso Factura', 'Cantidad Facturada', 'Cantidad Rechazada', 'Subtotal Factura', 'Ajuste Precio Lista');
    $cabeceras3 = array_merge($cabeceras3, $nombreAjustesAntes);
    $cabeceras3 = array_merge($cabeceras3, $nombreAjustesDesp);



    $cabeceras4 = array('Ajuste Rechazo', 'Ajuste Pactado Extra', 'Ajuste Ingreso Extra', 'Ajuste Total');
    $cabeceras = array_merge($cabeceras1, $cabeceras2, $cabeceras3, $cabeceras4);

    //Utlizando las cabeceras definimos el modelo base de 1 producto para calcular los ajustes
    $modeloProducto1 = array(
        'nombre' => '',
        'precio_lista' => 0
    );
    foreach ($response['descuentosAntesCP'] as $descuento) {
        $modeloProducto1[$descuento['nombre']] = 0;
    }
    $modeloProducto2 = array(
        'precio_pactado' => 0,
        'costo_unitario_factura' => 0,
        'dif' => 0
    );
    foreach ($response['descuentosDespCP'] as $descuento) {
        $modeloProducto2[$descuento['nombre']] = 0;
    }

    $modeloProducto3 = array(
        'costo_ingreso_pactado' => 0,
        'costo_ingreso_factura' => 0,
        'cantidad_facturada' => 0,
        'cantidad_rechazada' => 0,
        'subtotal_factura' => 0,
        'ajuste_precio_lista' => 0
    );

    foreach ($response['descuentosAntesCP'] as $descuento) {
        $modeloProducto3['Ajuste'.$descuento['nombre']] = 0;
    }
    foreach ($response['descuentosDespCP'] as $descuento) {
        $modeloProducto3['Ajuste'.$descuento['nombre']] = 0;
    }

    $modeloProducto4 = array(
        'ajuste_rechazo' => 0,
        'ajuste_pactado_extra' => 0,
        'ajuste_ingreso_extra' => 0,
        'ajuste_total' => 0
    );

    //Union de los modelos para hacer un solo modelo
    $modeloProducto = array_merge($modeloProducto1, $modeloProducto2, $modeloProducto3, $modeloProducto4);

    $data = array();
    //Recorremos los productos para generar el reporte
    foreach ($productos as $producto) {
        $sumaDescuentosAntesCP = 0;
        $sumaDescuentosDespCP = 0;
        //Comenzamos a llenar algunos camops del modelo
        $modeloProducto['nombre'] = $producto['comercial'];
        $modeloProducto['precio_lista'] = $producto['precioListaCatalogo'];
        $modeloProducto['precio_pactado'] = $producto['costoPactado'];
        $modeloProducto['costo_unitario_factura'] = $producto['costoFactura'];
        //Calculamos la diferencia
        $modeloProducto['dif'] = ($modeloProducto['costo_unitario_factura'] - $modeloProducto['precio_pactado'])/$modeloProducto['precio_lista']*100;
        //Llenamos los ajustes antes de CP
        foreach ($response['descuentosAntesCP'] as $descuento) {

            //Recorremos los descuentos antes de CP del producto
            foreach ($producto['descuentosAntesCP'] as $descuentoProducto) {
                if ($descuentoProducto['nombre'] == $descuento['nombre']) {
                    $modeloProducto[$descuento['nombre']] = $descuentoProducto['tasa'];
                    $sumaDescuentosAntesCP += $descuentoProducto['tasa'];
                }
            }
        }
        //Llenamos los ajustes despues de CP
        foreach ($response['descuentosDespCP'] as $descuento) {
            //Recorremos los descuentos despues de CP del producto
            foreach ($producto['descuentosDespCP'] as $descuentoProducto) {
                if ($descuentoProducto['nombre'] == $descuento['nombre']) {
                    $modeloProducto[$descuento['nombre']] = $descuentoProducto['tasa'];
                    $sumaDescuentosDespCP += $descuentoProducto['tasa'];
                }
            }
        }
        //Calculamos el costo de ingreso pactado sumando los descuentos despues de CP
        $modeloProducto['costo_ingreso_pactado'] = (1 - $sumaDescuentosDespCP/100) * $modeloProducto['precio_pactado'];
        //Calculamos el costo de ingreso facturado sumando los descuentos antes de CP
        $modeloProducto['costo_ingreso_factura'] = (1 - $sumaDescuentosDespCP/100) * $modeloProducto['costo_unitario_factura'];

        //Cantidades:
        $modeloProducto['cantidad_facturada'] = $producto['cantidad_facturada'];
        $modeloProducto['cantidad_rechazada'] = $producto['cantidad_rechazada'];
        //Calculamos el subtotal de la factura
        $modeloProducto['subtotal_factura'] = $modeloProducto['cantidad_facturada'] * $modeloProducto['costo_unitario_factura'];
        //Calculamos el ajuste de precio lista
        $modeloProducto['ajuste_precio_lista'] = calculaAjustePrecioLista($modeloProducto['precio_lista'], $modeloProducto['costo_unitario_factura'], $modeloProducto['cantidad_facturada'], $modeloProducto['cantidad_rechazada']);
        //Sumamos los ajustes
        $sumaAjustes = 0;
        $sumaAjustes = $modeloProducto['ajuste_precio_lista'];

        //Calculamos los ajustes antes de CP recorrinedo los descuentos
        foreach ($response['descuentosAntesCP'] as $descuento) {
            foreach ($producto['descuentosAntesCP'] as $descuentoProducto) {
                if ($descuentoProducto['nombre'] == $descuento['nombre']) {
                    $modeloProducto['Ajuste'.$descuento['nombre']] = calcularAjustesDescuentosAntesCP($descuentoProducto['tasa'], $modeloProducto['precio_lista'], $modeloProducto['cantidad_facturada'], $modeloProducto['cantidad_rechazada']);
                    // echo "El Valor de este ajuste es: ".$modeloProducto['Ajuste'.$descuento['nombre']]." por que: ".$descuentoProducto['tasa']."% de ".$modeloProducto['precio_lista']." * (".$modeloProducto['cantidad_facturada']." - ".$modeloProducto['cantidad_rechazada'].")<br>";
                    $sumaAjustes += $modeloProducto['Ajuste'.$descuento['nombre']];
                    // echo "Valor de la suma de ajustes: ".$sumaAjustes."<br>";
                }
            }
        }
        //Calculamos los ajustes despues de CP recorriendo los descuentos
        $sumaAjustesOff = 0;
        foreach ($response['descuentosDespCP'] as $descuento) {
            foreach ($producto['descuentosDespCP'] as $descuentoProducto) {
                if ($descuentoProducto['nombre'] == $descuento['nombre']) {
                    $modeloProducto['Ajuste'.$descuento['nombre']] = calcularAjustesDescuentosDespCP($descuentoProducto['tasa'], $modeloProducto['precio_pactado'], $modeloProducto['cantidad_facturada'], $modeloProducto['cantidad_rechazada']);
                    $sumaAjustesOff += $modeloProducto['Ajuste'.$descuento['nombre']];
                }
            }
        }
        //Calculamos ajuste rechazo
        $modeloProducto['ajuste_rechazo'] = $modeloProducto['cantidad_rechazada'] * $modeloProducto['costo_unitario_factura'];
        //Calculamos ajuste pactado extra
        $modeloProducto['ajuste_pactado_extra'] = ajustePactadoExtra($sumaAjustes, $modeloProducto['costo_unitario_factura'], $modeloProducto['precio_pactado'], $modeloProducto['cantidad_facturada'], $modeloProducto['cantidad_rechazada']);
        //Calculamos ajuste ingreso extra
        $modeloProducto['ajuste_ingreso_extra'] = calculaAjusteIngresoExtra($sumaAjustes, $sumaAjustesOff, $modeloProducto['costo_unitario_factura'], $modeloProducto['costo_ingreso_pactado'], $modeloProducto['cantidad_facturada'], $modeloProducto['cantidad_rechazada']);
        //Calculamos ajuste total
        $modeloProducto['ajuste_total'] = $modeloProducto['ajuste_rechazo'] + $modeloProducto['ajuste_pactado_extra'] + $modeloProducto['ajuste_ingreso_extra'] + $sumaAjustes + $sumaAjustesOff;
        
        //Agregamos a data el modelo
        $data[] = $modeloProducto;

        //Reiniciamos el modelo
        $modeloProducto = limpiaModelo($modeloProducto);
        //Reinicio de variables
        $sumaDescuentosAntesCP = 0;
        $sumaDescuentosDespCP = 0;
        $sumaAjustes = 0;
        $sumaAjustesOff = 0;

    }

    //Generamos el reporte
    $output = fopen('php://output', 'w');
    // echo implode(',', $cabeceras)."\n";
    fputcsv($output, $cabeceras);
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit();
    
    
}


function ajusteCP(){
    //Creamos el array de argumentos de acuerdo al estadanar de la funcion
    $args = [
        'id_factura' => $_POST['id_factura'],
        'ajusteDescuento' => $_POST['ajusteDescuento']
    ];
    $compraFacturaProdController = new compraFacturaProdController();
    $response = $compraFacturaProdController->getProductosByFacturaValidacionCosto($args);

    $productos = $response['productos'];
    
    //Generamos las cabeceras del reporte
    $cabeceras1 = array('Nombre', 'Precio Lista');
    $nombreAjustesAntes = array();
    $nombreAjustesDesp = array();
    //Los descuentos son dinamicos, por lo que se generan las cabeceras de acuerdo a los descuentos que se obtienen de la factura
    foreach ($response['descuentosAntesCP'] as $descuento) {
        $cabeceras1[] = $descuento['nombre'];
        $nombreAjustesAntes[] = 'Ajuste'.$descuento['nombre'];
    }
    $cabeceras2 = array('Precio Pactado', 'Costo Unitario Factura', 'DIF');
    //Agregamos los descuentos despues de CP
    foreach ($response['descuentosDespCP'] as $descuento) {
        $cabeceras2[] = $descuento['nombre'];
        $nombreAjustesDesp[] = 'Ajuste'.$descuento['nombre'];
    }
    $cabeceras3 = array('Costo Ingreso Pactado', 'Costo Ingreso Factura', 'Cantidad Facturada', 'Cantidad Rechazada', 'Subtotal Factura', 'Ajuste Precio Lista');
    $cabeceras3 = array_merge($cabeceras3, $nombreAjustesAntes);
    // $cabeceras3 = array_merge($cabeceras3, $nombreAjustesDesp);
    $cabeceras4 = array('Ajuste Rechazo', 'Ajuste Pactado Extra', 'Ajuste Total');

    $cabeceras = array_merge($cabeceras1, $cabeceras2, $cabeceras3, $cabeceras4);

    //Generamos el modelo base para calcular los ajustes
    $modeloProducto1 = array(
        'nombre' => '',
        'precio_lista' => 0
    );
    foreach ($response['descuentosAntesCP'] as $descuento) {
        $modeloProducto1[$descuento['nombre']] = 0;
    }
    $modeloProducto2 = array(
        'precio_pactado' => 0,
        'costo_unitario_factura' => 0,
        'dif' => 0
    );
    foreach ($response['descuentosDespCP'] as $descuento) {
        $modeloProducto2[$descuento['nombre']] = 0;
    }

    $modeloProducto3 = array(
        'costo_ingreso_pactado' => 0,
        'costo_ingreso_factura' => 0,
        'cantidad_facturada' => 0,
        'cantidad_rechazada' => 0,
        'subtotal_factura' => 0,
        'ajuste_precio_lista' => 0
    );

    foreach ($response['descuentosAntesCP'] as $descuento) {
        $modeloProducto3['Ajuste'.$descuento['nombre']] = 0;
    }
    // foreach ($response['descuentosDespCP'] as $descuento) {
    //     $modeloProducto3['Ajuste'.$descuento['nombre']] = 0;
    // }

    $modeloProducto4 = array(
        'ajuste_rechazo' => 0,
        'ajuste_pactado_extra' => 0,
        'ajuste_total' => 0
    );

    //Union de los modelos para hacer un solo modelo
    $modeloProducto = array_merge($modeloProducto1, $modeloProducto2, $modeloProducto3, $modeloProducto4);

    $data = array();
    //Recorremos los productos para generar el reporte
    foreach ($productos as $producto) {
        $sumaDescuentosAntesCP = 0;
        $sumaDescuentosDespCP = 0;
        //Comenzamos a llenar algunos camops del modelo
        $modeloProducto['nombre'] = $producto['comercial'];
        $modeloProducto['precio_lista'] = $producto['precioListaCatalogo'];
        $modeloProducto['precio_pactado'] = $producto['costoPactado'];
        $modeloProducto['costo_unitario_factura'] = $producto['costoFactura'];
        //Calculamos la diferencia
        $modeloProducto['dif'] = ($modeloProducto['costo_unitario_factura'] - $modeloProducto['precio_pactado'])/$modeloProducto['precio_lista']*100;
        //Llenamos los ajustes antes de CP
        foreach ($response['descuentosAntesCP'] as $descuento) {
            //Recorremos los descuentos antes de CP del producto
            foreach ($producto['descuentosAntesCP'] as $descuentoProducto) {
                if ($descuentoProducto['nombre'] == $descuento['nombre']) {
                    $modeloProducto[$descuento['nombre']] = $descuentoProducto['tasa'];
                    $sumaDescuentosAntesCP += $descuentoProducto['tasa'];
                }
            }
        }
        //Llenamos los ajustes despues de CP
        foreach ($response['descuentosDespCP'] as $descuento) {
            //Recorremos los descuentos despues de CP del producto
            foreach ($producto['descuentosDespCP'] as $descuentoProducto) {
                if ($descuentoProducto['nombre'] == $descuento['nombre']) {
                    $modeloProducto[$descuento['nombre']] = $descuentoProducto['tasa'];
                    $sumaDescuentosDespCP += $descuentoProducto['tasa'];
                }
            }
        }
        //Calculamos el costo de ingreso pactado sumando los descuentos despues de CP
        $modeloProducto['costo_ingreso_pactado'] = (1 - $sumaDescuentosDespCP/100) * $modeloProducto['precio_pactado'];
        //Calculamos el costo de ingreso facturado sumando los descuentos antes de CP
        $modeloProducto['costo_ingreso_factura'] = (1 - $sumaDescuentosDespCP/100) * $modeloProducto['costo_unitario_factura'];
        
        $modeloProducto['cantidad_facturada'] = $producto['cantidad_facturada'];
        $modeloProducto['cantidad_rechazada'] = $producto['cantidad_rechazada'];
        $modeloProducto['subtotal_factura'] = $modeloProducto['cantidad_facturada'] * $modeloProducto['costo_unitario_factura'];
        //Calculamos el ajustes de precio lista
        $modeloProducto['ajuste_precio_lista'] = calculaAjustePrecioLista($modeloProducto['precio_lista'], $modeloProducto['costo_unitario_factura'], $modeloProducto['cantidad_facturada'], $modeloProducto['cantidad_rechazada']);
        $sumaAjustes = 0;
        $sumaAjustes = $modeloProducto['ajuste_precio_lista'];
        foreach ($response['descuentosAntesCP'] as $descuento) {
            foreach ($producto['descuentosAntesCP'] as $descuentoProducto) {
                if ($descuentoProducto['nombre'] == $descuento['nombre']) {
                    $modeloProducto['Ajuste'.$descuento['nombre']] = calcularAjustesDescuentosAntesCP($descuentoProducto['tasa'], $modeloProducto['precio_lista'], $modeloProducto['cantidad_facturada'], $modeloProducto['cantidad_rechazada']);
                    $sumaAjustes += $modeloProducto['Ajuste'.$descuento['nombre']];
                }
            }
        }
        $modeloProducto['ajuste_rechazo'] = $modeloProducto['cantidad_rechazada'] * $modeloProducto['costo_unitario_factura'];
        $modeloProducto['ajuste_pactado_extra'] = ajustePactadoExtra($sumaAjustes, $modeloProducto['costo_unitario_factura'], $modeloProducto['precio_pactado'], $modeloProducto['cantidad_facturada'], $modeloProducto['cantidad_rechazada']);
        $modeloProducto['ajuste_total'] = $modeloProducto['ajuste_rechazo'] + $modeloProducto['ajuste_pactado_extra'] + $sumaAjustes;
        $data[] = $modeloProducto;
        $modeloProducto = limpiaModelo($modeloProducto);
        $sumaDescuentosAntesCP = 0;
        $sumaDescuentosDespCP = 0;
        $sumaAjustes = 0;


    }// Fin del foreach de productos

    //Generamos el reporte
    $output = fopen('php://output', 'w');
    fputcsv($output, $cabeceras);
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit();
    


}



function ajustePL(){
    //Creamos el array de argumentos de acuerdo al estadanar de la funcion
    $args = [
        'id_factura' => $_POST['id_factura'],
        'ajusteDescuento' => $_POST['ajusteDescuento']
    ];
    $compraFacturaProdController = new compraFacturaProdController();
    $response = $compraFacturaProdController->getProductosByFacturaValidacionCosto($args);

    $productos = $response['productos'];
    
    //Generamos las cabeceras del reporte
    $cabeceras1 = array('Nombre', 'Precio Lista');
    $nombreAjustesAntes = array();
    $nombreAjustesDesp = array();
    //Los descuentos son dinamicos, por lo que se generan las cabeceras de acuerdo a los descuentos que se obtienen de la factura
    foreach ($response['descuentosAntesCP'] as $descuento) {
        $cabeceras1[] = $descuento['nombre'];
        $nombreAjustesAntes[] = 'Ajuste'.$descuento['nombre'];
    }
    $cabeceras2 = array('Precio Pactado', 'Costo Unitario Factura', 'DIF');
    //Agregamos los descuentos despues de CP
    foreach ($response['descuentosDespCP'] as $descuento) {
        $cabeceras2[] = $descuento['nombre'];
        $nombreAjustesDesp[] = 'Ajuste'.$descuento['nombre'];
    }
    $cabeceras3 = array('Costo Ingreso Pactado', 'Costo Ingreso Factura', 'Cantidad Facturada', 'Cantidad Rechazada', 'Subtotal Factura', 'Ajuste Precio Lista');
    // $cabeceras3 = array_merge($cabeceras3, $nombreAjustesAntes);
    // $cabeceras3 = array_merge($cabeceras3, $nombreAjustesDesp);
    $cabeceras4 = array('Ajuste Rechazo', 'Ajuste Total');

    $cabeceras = array_merge($cabeceras1, $cabeceras2, $cabeceras3, $cabeceras4);

    //Generamos el modelo base para calcular los ajustes
    $modeloProducto1 = array(
        'nombre' => '',
        'precio_lista' => 0
    );
    foreach ($response['descuentosAntesCP'] as $descuento) {
        $modeloProducto1[$descuento['nombre']] = 0;
    }
    $modeloProducto2 = array(
        'precio_pactado' => 0,
        'costo_unitario_factura' => 0,
        'dif' => 0
    );
    foreach ($response['descuentosDespCP'] as $descuento) {
        $modeloProducto2[$descuento['nombre']] = 0;
    }

    $modeloProducto3 = array(
        'costo_ingreso_pactado' => 0,
        'costo_ingreso_factura' => 0,
        'cantidad_facturada' => 0,
        'cantidad_rechazada' => 0,
        'subtotal_factura' => 0,
        'ajuste_precio_lista' => 0,
        'ajuste_rechazo' => 0,
        'ajuste_total' => 0
    );

    foreach ($response['descuentosAntesCP'] as $descuento) {
        $modeloProducto3['Ajuste'.$descuento['nombre']] = 0;
    }
    //Hacemos merge de los modelos para hacer un solo modelo
    $modeloProducto = array_merge($modeloProducto1, $modeloProducto2, $modeloProducto3);

    $data = array();
    //Recorremos los productos para generar el reporte
    foreach ($productos as $producto) {
        $sumaDescuentosAntesCP = 0;
        $sumaDescuentosDespCP = 0;
        //Comenzamos a llenar algunos camops del modelo
        $modeloProducto['nombre'] = $producto['comercial'];
        $modeloProducto['precio_lista'] = $producto['precioListaCatalogo'];
        //Llenamos los ajustes antes de CP
        foreach ($response['descuentosAntesCP'] as $descuento) {
            //Recorremos los descuentos antes de CP del producto
            foreach ($producto['descuentosAntesCP'] as $descuentoProducto) {
                if ($descuentoProducto['nombre'] == $descuento['nombre']) {
                    $modeloProducto[$descuento['nombre']] = $descuentoProducto['tasa'];
                    $sumaDescuentosAntesCP += $descuentoProducto['tasa'];
                }
            }
        }
        $modeloProducto['precio_pactado'] = $producto['costoPactado'];
        $modeloProducto['costo_unitario_factura'] = $producto['costoFactura'];
        //Calculamos la diferencia
        $modeloProducto['dif'] = ($modeloProducto['costo_unitario_factura'] - $modeloProducto['precio_pactado'])/$modeloProducto['precio_lista']*100;
        //Llenamos los ajustes despues de CP
        foreach ($response['descuentosDespCP'] as $descuento) {
            //Recorremos los descuentos despues de CP del producto
            foreach ($producto['descuentosDespCP'] as $descuentoProducto) {
                if ($descuentoProducto['nombre'] == $descuento['nombre']) {
                    $modeloProducto[$descuento['nombre']] = $descuentoProducto['tasa'];
                    $sumaDescuentosDespCP += $descuentoProducto['tasa'];
                }
            }
        }
        //Calculamos el costo de ingreso pactado sumando los descuentos despues de CP
        $modeloProducto['costo_ingreso_pactado'] = (1 - $sumaDescuentosDespCP/100) * $modeloProducto['precio_pactado'];
        //Calculamos el costo de ingreso facturado 
        $modeloProducto['costo_ingreso_factura'] = (1 - $sumaDescuentosDespCP/100) * $modeloProducto['costo_unitario_factura'];
        $modeloProducto['cantidad_facturada'] = $producto['cantidad_facturada'];
        $modeloProducto['cantidad_rechazada'] = $producto['cantidad_rechazada'];
        $modeloProducto['subtotal_factura'] = $modeloProducto['cantidad_facturada'] * $modeloProducto['costo_unitario_factura'];
        //Calculamos el ajuste de precio lista
        $modeloProducto['ajuste_precio_lista'] = calculaAjustePrecioLista($modeloProducto['precio_lista'], $modeloProducto['costo_unitario_factura'], $modeloProducto['cantidad_facturada'], $modeloProducto['cantidad_rechazada']);
        //Calculamos ajuste rechazo
        $modeloProducto['ajuste_rechazo'] = $modeloProducto['cantidad_rechazada'] * $modeloProducto['costo_unitario_factura'];
        //Calculamos ajuste total
        $modeloProducto['ajuste_total'] = $modeloProducto['ajuste_rechazo'] + $modeloProducto['ajuste_precio_lista'];

        $data[] = $modeloProducto;
        $modeloProducto = limpiaModelo($modeloProducto);
        $sumaDescuentosAntesCP = 0;
        $sumaDescuentosDespCP = 0;

    }// Fin del foreach de productos

    //Generamos el reporte
    $output = fopen('php://output', 'w');
    fputcsv($output, $cabeceras);
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit();
}

//Generamos el reporte segun el tipo de ajuste
switch ($_POST['ajusteDescuento']) {
    case '1':
        ajusteCP();
        break;
    case '2':
        ajusteCI();
        break;
    case '3':
        ajustePL();
        break;
}
?>