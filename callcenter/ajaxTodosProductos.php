<?
$ruta = "../../../";
include $ruta."sys/precarga_mysqli.php";
//include $ruta."sys/fcn/fcnSelects.php";

$permitidos = array(1, 8, 9, 10, 13);
$acceso = validaAccesoAutorizado($permitidos);

if($acceso == 0)
	redirect("", "");



function limpiaCadena($cadena) {
    // Reemplazamos los acentos
    $sinAcentos = str_replace(
        ['á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú'],
        ['a', 'e', 'i', 'o', 'u', 'A', 'E', 'I', 'O', 'U'],
        $cadena
    );

    // Eliminamos las comas y los guiones
    $sinComasNiGuiones = str_replace(['-', ','], '', $sinAcentos);

    return $sinComasNiGuiones;
}
 // Función para obtener los productos más frecuentes, recibe un array y la cantidad de resultados que se desean
function productosFrecuentes($array, $cantidadResultados) {
    // Contar la frecuencia de cada elemento en el array
    $frecuencia = array_count_values($array);
    // Ordenar el array por valores de mayor a menor
    arsort($frecuencia);
    // Convertir el array asociativo en uno indexado
    $frecuenciaIndexado = array_keys($frecuencia);
    // Verificar si la cantidad solicitada es mayor que la longitud del array
    $cantidadResultados = min($cantidadResultados, count($frecuencia));
    // Obtener los elementos que más se repiten
    $resultados = array_slice($frecuenciaIndexado, 0, $cantidadResultados);
    // Devolver los resultados
    return $resultados;
}


function productosParaVenta($conexion_bd,$boton = null){
    //Si recibimos un boton, lo asignamos a la variable global
    if($boton != null){
        $_POST['query'] = $boton;
    }
    // echo "Soy la funcion productosParaVenta recibí el boton: ".$_POST['query']."<br>";

    // Cremos el array de respuesta
    $response = array();
    // Obtenemos el dia de la semana
    $diaHoy = date('N');
    // Creamos un array auxiliar para almacenar los productos
    $aux = array();
    // Obtenemos los producto segun el lo requiere el usuario del tipo de query que recibimos
    switch ($_POST['query']) {

        case 'btnPromociones':
            // -- PROMOCIONES DE PRODUCTOS INDIDUALES DE UNA RUTA DADA
            $query = "SELECT 
            pg.nombre as grupo, p.comercial, sm.nombre unidad, 
            po.id_prod as id_prod_med, po.venta, pm.id id_prod_venta,
            FLOOR(SUM(almacen.cantidad / pm.cant_unid_min)) AS disponible,  p.id as idProducto
            from ruta r 
            join prod_oficina po on r.id_oficina = po.id_oficina 
                && po.id_status = 4
            join prod_medida pm on po.id_prod = pm.id
            join sys_medida sm on pm.id_medida = sm.id
            join convenio_oficina co on r.id_oficina = co.id_oficina 
                && co.id_prod = po.id_prod && co.id_status = 4
            join convenio c on co.id_convenio = c.id  && c.id_status = 4    
            join producto p on pm.id_prod = p.id
            join prod_gpo pg on p.id_gpo = pg.id
            join almacen on almacen.id = po.id_prod
            where r.id = ".$_POST['idRuta']."
            group by pg.nombre, p.comercial, sm.nombre, po.id_prod, po.venta
            order by pg.nombre, p.comercial";
            if(!$resultIndividuales = mysqli_query($conexion_bd,$query)) {
                errores(mysqli_errno($conexion_bd), 0);
            }

            // -- PROMOCIONES DE COMBOS (PAQUETES) DE UNA RUTA DADA
            $query = "SELECT
            pg.nombre as grupo, p.comercial, sm.nombre unidad, 
            po.id_prod as id_prod_med, po.venta,
            FLOOR(SUM(almacen.cantidad / pm.cant_unid_min)) AS disponible, p.id as idProducto, promo.id as promo_id, pm.id id_prod_venta
            from ruta r 
            join prod_oficina po on r.id_oficina = po.id_oficina 
                && po.id_status = 4
            join promocion promo on po.id_prod = promo.id_prod && promo.id_status = 4    
            join promocion_prod pp on promo.id = pp.id_promo && po.id_oficina = pp.id_oficina 
            join prod_medida pm on po.id_prod = pm.id
            join sys_medida sm on pm.id_medida = sm.id
            join producto p on pm.id_prod = p.id
            join prod_gpo pg on p.id_gpo = pg.id
            join almacen on almacen.id = po.id_prod
            where r.id = ".$_POST['idRuta']."
            group by promo.id, pg.nombre, p.comercial, sm.nombre, po.id_prod, po.venta
            order by pg.nombre, p.comercial";
            if(!$resultCombos = mysqli_query($conexion_bd,$query)) errores(mysqli_errno($conexion_bd), 0);

            //Individuales
            //Recorremos los productos para enviarlos al datatable
            while($row = mysqli_fetch_array($resultIndividuales)){
                //No podemos vender productos que se venden por debajo de 1 peso
                if($row['venta'] < 1) continue;
                $aux['id'] = $row['idProducto'];
                $aux['idProdMedida'] = $row['id_prod_venta'];
                $aux['id_promo'] = isset($row['promo_id']) ? $row['promo_id'] : '';
                $aux['grupo'] = $row['grupo'];
                // Convertirmos el producto en url que llame a un modal con la informacion del contenido de producto
                $aux['producto'] = isset($row['promo_id']) ? "<a href='javascript:void(0)' class='btnVerContenidoCombo' nombreProducto='".$row['comercial']."' id='".$row['idProducto']."' idPromo='".$row['promo_id']."'><u>".$row['comercial']."</u></a>": $row['comercial'];
                $aux['venta'] = "$ ".number_format($row['venta'], 2);
                $aux['cantidad'] = "<input idPromo='".$aux['id_promo']."' id='input-".$row['id_prod_venta']."' type='number' class=' inputCantidad' value='0' min='0' max='".$row['disponible']."' style='width: 60px;' > ";
                $aux['disponible'] = $row['disponible']." ".$row['unidad'];
                $aux['btnAgregar'] = '<span id="'.$row['id_prod_venta'].'" idProducto="'.$row['idProducto'].'" comercial="'.$row['comercial'].'"  class="badge badge-success mr-1 btnAgregarProducto" style="cursor: pointer;"><i style=" color: #f6fcfb;" data-feather="plus"></i></span>';
                //Creamos un campo que vamos a usar como contenido indexado para busqueda y ordenamiento
                $aux['busqueda'] = limpiaCadena($aux['grupo']." ".$aux['producto']." ".$aux['venta']." ".$aux['unidad']);
                // Obtenida la información, la agregamos al array de respuesta
                array_push($response, $aux);
                //Vaciamos el array auxiliar para la siguiente iteración
                $aux = array();
            }
            //Combos
            while($row = mysqli_fetch_array($resultCombos)){
                //No podemos vender productos que se venden por debajo de 1 peso
                if($row['venta'] < 1) continue;
                $aux['id'] = $row['idProducto'];
                $aux['idProdMedida'] = $row['id_prod_venta'];
                $aux['grupo'] = $row['grupo'];
                $aux['id_promo'] = isset($row['promo_id']) ? $row['promo_id'] : '';
                $aux['producto'] = isset($row['promo_id']) ? "<a href='javascript:void(0)' class='btnVerContenidoCombo' id='".$row['idProducto']."' nombreProducto='".$row['comercial']."' idPromo='".$row['promo_id']."'><u>".$row['comercial']."</u></a>": $row['comercial'];
                $aux['venta'] = "$ ".number_format($row['venta'], 2);
                $aux['cantidad'] = "<input idPromo='".$aux['id_promo']."' id='input-".$row['id_prod_venta']."' type='number' class=' inputCantidad' value='0' min='0' max='".$row['disponible']."' style='width: 60px;' > ";
                $aux['disponible'] = $row['disponible']." ".$row['unidad'];
                $aux['btnAgregar'] = '<span id="'.$row['id_prod_venta'].'" idProducto="'.$row['idProducto'].'" comercial="'.$row['comercial'].'"  class="badge badge-success mr-1 btnAgregarProducto" style="cursor: pointer;"><i style=" color: #f6fcfb;" data-feather="plus"></i></span>';
                //Creamos un campo que vamos a usar como contenido indexado para busqueda y ordenamiento
                $aux['busqueda'] = limpiaCadena($aux['grupo']." ".$aux['producto']." ".$aux['venta']." ".$aux['unidad']);
                // Obtenida la información, la agregamos al array de respuesta
                array_push($response, $aux);
                //Vaciamos el array auxiliar para la siguiente iteración
                $aux = array();
            }

            //vamos a ordenar el response por grupo y producto
            usort($response, function($a, $b) {
                if ($a['grupo'] == $b['grupo']) {
                    if ($a['producto'] == $b['producto']) {
                        return 0;
                    }
                    return ($a['producto'] < $b['producto']) ? -1 : 1;
                }
                return ($a['grupo'] < $b['grupo']) ? -1 : 1;
            });

            break;
        case 'BtnTodos':
            // Este query es para obtener todos los productos de la ruta seleccionada
            $query = "SELECT
            prod_medida.id,
            prod_medida.id AS idProdMedida,
            prod_oficina.venta,
            prod_gpo2.*,
            producto.comercial,
            producto.id AS idProducto,
            sys_medida.nombre AS medida,
            FLOOR(
                SUM(
                    almacen.cantidad / prod_medida.cant_unid_min
                )
            ) AS disponible,
            prod_medida.id_tipo_venta,
            
            vt_promo.promo_id /* Nuevo campo agregado */
        FROM
            prod_medida
        JOIN prod_oficina ON prod_medida.id = prod_oficina.id_prod AND prod_oficina.id_status = 4 AND prod_oficina.id_oficina = ".$_POST['idOficina']."
        JOIN producto ON prod_medida.id_prod = producto.id /* && producto.id_status = 4 */
        JOIN prod_gpo2 ON producto.id_gpo2 = prod_gpo2.id AND prod_gpo2.id_status = 4
        JOIN sys_medida ON prod_medida.id_medida = sys_medida.id
        JOIN prod_compra ON producto.id = prod_compra.id_prod
        JOIN almacen ON prod_compra.id = almacen.id_compra AND almacen.id_status = 4 AND almacen.cantidad >= prod_medida.cant_unid_min AND almacen.id_bodega = ".$_POST['idBodega']."
        -- left join bod_oficina on prod_oficina.id_oficina = bod_oficina.id_oficina && bod_oficina.id_status = 4
        LEFT JOIN(
            SELECT
                promocion.id,
                promocion.id_prod,
                promocion_prod.id_oficina,
                IFNULL(
                    promocion_condicion.recomendada,
                    0
                ) AS recomendada,
                promocion.id AS promo_id /* Nuevo campo agregado */
            FROM
                promocion
            LEFT JOIN promocion_condicion ON promocion_condicion.id_promo = promocion.id
            JOIN promocion_prod ON promocion_prod.id_promo = promocion.id AND promocion_prod.id_status = 4
            WHERE
                promocion.id_status = 4
            GROUP BY
                promocion.id_prod,
                promocion_prod.id_oficina
        ) vt_promo ON vt_promo.id_prod = prod_oficina.id_prod AND vt_promo.id_oficina = prod_oficina.id_oficina
        WHERE
            prod_medida.id_status = 4 AND (prod_medida.id_tipo_venta = 1 OR vt_promo.id IS NOT NULL) /* Corrección de operador lógico OR */
        GROUP BY
            prod_medida.id
        HAVING
            disponible > 0
        ORDER BY
            prod_gpo2.nombre ASC,
            producto.comercial ASC,
            sys_medida.nombre ASC";
            
            if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
            //Recorremos los productos para enviarlos al datatable
            while($row = mysqli_fetch_array($result)){
                $aux['id'] = $row['idProducto'];
                $aux['idProdMedida'] = $row['idProdMedida'];
                $aux['id_promo'] = isset($row['promo_id']) ? $row['promo_id'] : '';
                $aux['grupo'] = $row['nombre'];
                $aux['producto'] = isset($row['promo_id']) ? "<a href='javascript:void(0)' class='btnVerContenidoCombo' id='".$row['idProducto']."' nombreProducto='".$row['comercial']."' idPromo='".$row['promo_id']."'><u>".$row['comercial']."</u></a>": $row['comercial'];
                $aux['venta'] = "$ ".number_format($row['venta'], 2);
                $aux['cantidad'] = "<input idPromo='".$aux['id_promo']."' id='input-".$row['idProdMedida']."' type='number' class=' inputCantidad' value='0' min='0' max='".$row['disponible']."' style='width: 60px;' >";
                $aux['disponible'] = $row['disponible']." ".$row['medida'];
                $aux['btnAgregar'] = '<span id="'.$row['idProdMedida'].'" idProducto="'.$row['idProducto'].'" comercial="'.$row['comercial'].'"  class="badge badge-success mr-1 btnAgregarProducto" style="cursor: pointer;"><i style=" color: #f6fcfb;" data-feather="plus"></i></span>';
                //Creamos un campo que vamos a usar como contenido indexado para busqueda y ordenamiento
                $aux['busqueda'] = limpiaCadena($row['nombre']." ".$row['comercial']." ".$row['venta']);
                // Obtenida la información, la agregamos al array de respuesta
                array_push($response, $aux);
                //Vaciamos el array auxiliar para la siguiente iteración
                $aux = array();
            }


            break;
        case 'BtnRecomendados':
            //Query para obtener los productos recomendados
            //LISTA DE RECOMENDACIONES PARA UNA RUTA DADA CON INNER JOIN DE LAS RECOMENDACIONES DEL CLIENTE DADO
            //PRECIO DE LAS RECOMENDACIONES DE LA RUTA DADA

            //Esta query es para obtener los productos recomendados de la ruta seleccionada
            $query = "SELECT p.id as idProducto, 
            r.id id_ruta, pg.nombre grupo, pm.id id_prod_venta, p.comercial, 
            sm.nombre unidad, po.venta, round(ifnull(alm.disp, 0)/pm.cant_unid_min,0) disp
        from recomendacion_jornada rj 
        join recomendacion_empate re on rj.id_recomendacion = re.id_reco_jornada
        join prod_medida pm on re.id_prod_med = pm.id
        join producto p on pm.id_prod = p.id
        join sys_medida sm on pm.id_medida = sm.id
        join prod_gpo pg on p.id_gpo = pg.id
        join prod_oficina po on re.id_prod_med = po.id_prod 
        join ruta r on po.id_oficina = r.id_oficina && r.id = {$_POST['idRuta']}
        join (
            -- RECOMENDACIONES DE UN CLIENTE DADO
            select rs.id_prod 
            FROM recomendacion_jornada rj
            join recomendacion_segmento rs on rj.id_recomendacion = rs.id_recomendacion 
            join recomendacion_cte reco_cte on rs.id_segmento = reco_cte.id_segmento && reco_cte.id_cte = {$_POST['idCliente']}
            where rj.id_status = 4 && rj.inicio <= CURRENT_DATE && rj.fin >= CURRENT_DATE
        ) recXcte on re.id_prod_med = recXcte.id_prod
        left join (
            select id_prod, sum(a.cantidad) disp 
            from ruta r 
            join oficina o on r.id_oficina = o.id 
            join bod_oficina bo on o.id = bo.id_oficina
            join almacen a on bo.id_bodega = a.id_bodega && a.id_status = 4 && a.cantidad > 0
            join prod_compra pc on a.id_compra = pc.id
            where r.id = {$_POST['idRuta']}
            group by pc.id_prod
        ) alm on pm.id_prod = alm.id_prod
        where rj.id_status = 4 && rj.inicio <= CURRENT_DATE && rj.fin >= CURRENT_DATE";
            if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
            
            //Recorremos los productos para enviarlos al datatable
            while($row = mysqli_fetch_array($result)){
                //No podemos vender productos que se venden por debajo de 1 peso
                if($row['venta'] < 1) continue;
                $aux['id'] = $row['idProducto'];
                $aux['idProdMedida'] = $row['id_prod_venta'];
                $aux['id_promo'] = isset($row['promo_id']) ? $row['promo_id'] : '';
                $aux['grupo'] = $row['grupo'];
                $aux['producto'] = $row['comercial'];
                $aux['venta'] = "$ ".number_format($row['venta'], 2);
                $aux['cantidad'] = "<input idPromo='".$aux['id_promo']."' id='input-".$row['id_prod_venta']."' type='number' class=' inputCantidad' value='0' min='0' max='".$row['disp']."' style='width: 60px;' > ";
                $aux['disponible'] = $row['disp']." ".$row['unidad'];
                $aux['btnAgregar'] = '<span id="'.$row['id_prod_venta'].'" idProducto="'.$row['idProducto'].'" comercial="'.$row['comercial'].'"  class="badge badge-success mr-1 btnAgregarProducto" style="cursor: pointer;"><i style=" color: #f6fcfb;" data-feather="plus"></i></span>';
                //Creamos un campo que vamos a usar como contenido indexado para busqueda y ordenamiento
                $aux['busqueda'] = limpiaCadena($aux['grupo']." ".$aux['producto']." ".$aux['venta']." ".$aux['unidad']);
                // Obtenida la información, la agregamos al array de respuesta
                array_push($response, $aux);
                //Vaciamos el array auxiliar para la siguiente iteración
                $aux = array();
            }
            break;
        case 'BtnSugeridos':
            //Buscamos los ultinmos 5 pedidos del cliente de la ruta seleccionada
            $arrayTodosProductosRuta = array();
            $query = "SELECT
            prod_medida.id,
            prod_medida.id as idProdMedida,
            prod_oficina.venta,
            prod_gpo2.*,
            producto.comercial,
            producto.id as idProducto,
            sys_medida.nombre AS medida,
            FLOOR(
                SUM(
                    almacen.cantidad / prod_medida.cant_unid_min
                )
            ) AS disponible,
            prod_medida.id_tipo_venta
            FROM
            prod_medida
            JOIN prod_oficina ON prod_medida.id = prod_oficina.id_prod && prod_oficina.id_status = 4 && prod_oficina.id_oficina = ".$_POST['idOficina']."
            JOIN producto ON prod_medida.id_prod = producto.id /* && producto.id_status = 4 */
            JOIN prod_gpo2 ON producto.id_gpo2 = prod_gpo2.id && prod_gpo2.id_status = 4
            JOIN sys_medida ON prod_medida.id_medida = sys_medida.id
            JOIN prod_compra ON producto.id = prod_compra.id_prod
            JOIN almacen ON prod_compra.id = almacen.id_compra && almacen.id_status = 4 && almacen.cantidad >= prod_medida.cant_unid_min && almacen.id_bodega = '".$_POST['idBodega']."'
            -- left join bod_oficina on prod_oficina.id_oficina = bod_oficina.id_oficina && bod_oficina.id_status = 4
            LEFT JOIN(
            SELECT
                promocion.id,
                promocion.id_prod,
                promocion_prod.id_oficina,
                IFNULL(
                    promocion_condicion.recomendada,
                    0
                ) AS recomendada
            FROM
                promocion
            LEFT JOIN promocion_condicion ON promocion_condicion.id_promo = promocion.id
            JOIN promocion_prod ON promocion_prod.id_promo = promocion.id && promocion_prod.id_status = 4
            WHERE
                promocion.id_status = 4
            GROUP BY
                promocion.id_prod,
                promocion_prod.id_oficina
            ) vt_promo
            ON
            vt_promo.id_prod = prod_oficina.id_prod && vt_promo.id_oficina = prod_oficina.id_oficina
            WHERE
            prod_medida.id_status = 4 &&(
                prod_medida.id_tipo_venta = 1 || vt_promo.id IS NOT NULL
            )
            GROUP BY
                prod_medida.id
            HAVING
                disponible > 0
            ORDER BY
                prod_gpo2.nombre ASC,
                producto.comercial ASC,
                sys_medida.nombre ASC";
                    
            //Obtenemos todos los productos de la ruta para filtrar los que mas se han pedido en los ultimos 5 pedidos
            if(!$resultTodosProductos = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
            //Buscamos los ultimos 5 pedidos del cliente de la ruta seleccionada
            $query = "SELECT
            pedido.id,
            pedido.id_cte,
            pedido.id_ruta,
            pedido.id_status
            FROM pedido
            WHERE pedido.id_cte = {$_POST['idCliente']} && pedido.id_ruta = {$_POST['idRuta']} && pedido.id_status = 4
            ORDER BY pedido.fecha_modificacion DESC
            LIMIT 5";
            if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
            //Recorremos los pedidos para obtener los productos que mas se han pedido
            while($row = mysqli_fetch_array($result)){
                //Buscamos los productos de cada pedido
                $query = "SELECT id_prod
                FROM pdo_prod
                WHERE pdo_prod.id_pdo ='{$row['id']}' ";
                if(!$result2 = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
                //Recorremos los productos de cada pedido para obtener los productos que mas se han pedido
                while($row2 = mysqli_fetch_array($result2)){
                    //Ingresamos en el array de todos los productos de la ruta para buscar los productos que mas se han pedido
                    array_push($arrayTodosProductosRuta, $row2['id_prod']);
                }
            }
            //Procesamos el array de productos para obtener los productos que mas se han pedido
            $productosFrecuentes = productosFrecuentes($arrayTodosProductosRuta, 15);
            //Recorremos todos los productos de la ruta y filtramos los que mas se han pedido
            while($row = mysqli_fetch_array($resultTodosProductos)){
                //Verificamos si el producto esta en el array de productos frecuentes
                if(in_array($row['idProdMedida'],$productosFrecuentes)){
                    //No podemos vender productos que se venden por debajo de 1 peso
                    if($row['venta'] < 1) continue;
                    $aux['id'] = $row['idProducto'];
                    $aux['idProdMedida'] = $row['idProdMedida'];
                    $aux['id_promo'] = isset($row['promo_id']) ? $row['promo_id'] : '';
                    $aux['grupo'] = $row['nombre'];
                    $aux['producto'] = $row['comercial'];
                    $aux['venta'] = "$ ".number_format($row['venta'], 2);
                    $aux['cantidad'] = "<input idPromo='".$aux['id_promo']."' id='input-".$row['idProdMedida']."' type='number' class=' inputCantidad' value='0' min='0' max='".$row['disponible']."' style='width: 60px;' >";
                    $aux['disponible'] = $row['disponible']." ".$row['medida'];
                    //Creamos un campo que vamos a usar como contenido indexado para busqueda y ordenamiento
                    $aux['busqueda'] = limpiaCadena($row['nombre']." ".$row['comercial']." ".$row['venta']);
                    $aux['btnAgregar'] = '<span id="'.$row['idProdMedida'].'" idProducto="'.$row['idProducto'].'" comercial="'.$row['comercial'].'"  class="badge badge-success mr-1 btnAgregarProducto" style="cursor: pointer;"><i style=" color: #f6fcfb;" data-feather="plus"></i></span>';
                    // Obtenida la información, la agregamos al array de respuesta
                    array_push($response, $aux);
                    //Vaciamos el array auxiliar para la siguiente iteración
                    $aux = array();
                }
            }
        break;

        case 'BtnCalientes':
        $query = "SELECT o.nombre, p.id as idProducto,
            r.id id_ruta, pg.nombre grupo, pm.id id_prod_venta, 
            p.comercial, sm.nombre unidad, po.venta, 
            round(ifnull(alm.disp, 0)/pm.cant_unid_min,0) disp
        from ruta r 
        join prod_oficina po on r.id_oficina = po.id_oficina && po.id_status = 4
        join oficina o on po.id_oficina = o.id
        join prod_medida pm on po.id_prod = pm.id
        join bod_oficina bo on po.id_oficina = bo.id_oficina
        join prod_etiqueta pe on bo.id_bodega = pe.id_bod && pe.id_etiqueta = 2 
            && pe.id_status = 4 && pm.id_prod = pe.id_prod
        join producto p on pm.id_prod = p.id
        join sys_medida sm on pm.id_medida = sm.id
        join prod_gpo pg on p.id_gpo = pg.id
        left join (
            select id_prod, sum(a.cantidad) disp 
            from ruta r 
            join oficina o on r.id_oficina = o.id 
            join bod_oficina bo on o.id = bo.id_oficina
            join almacen a on bo.id_bodega = a.id_bodega && a.id_status = 4 && a.cantidad > 0
            join prod_compra pc on a.id_compra = pc.id
            where r.id = {$_POST['idRuta']}
            group by pc.id_prod
        ) alm on pm.id_prod = alm.id_prod
        where r.id = {$_POST['idRuta']} AND disp > 0
        group by po.id_prod
        order by pg.nombre, p.comercial, pm.cant_unid_min";
        if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
        //Recorremos los productos para enviarlos al datatable
        while($row = mysqli_fetch_array($result)){
            //No podemos vender productos que se venden por debajo de 1 peso
            if($row['venta'] < 1) continue;
            $aux['id'] = $row['idProducto'];
            $aux['idProdMedida'] = $row['id_prod_venta'];
            $aux['id_promo'] = isset($row['promo_id']) ? $row['promo_id'] : '';
            $aux['grupo'] = $row['grupo'];
            $aux['producto'] = $row['comercial'];
            $aux['venta'] = "$ ".number_format($row['venta'], 2);
            $aux['cantidad'] = "<input idPromo='".$aux['id_promo']."' id='input-".$row['id_prod_venta']."' type='number' class=' inputCantidad' value='0' min='0' max='".$row['disp']."' style='width: 60px;' > ";
            $aux['disponible'] = $row['disp']." ".$row['unidad'];
            //Creamos un campo que vamos a usar como contenido indexado para busqueda y ordenamiento
            $aux['busqueda'] = limpiaCadena($aux['grupo']." ".$aux['producto']." ".$aux['venta']." ".$aux['unidad']);
            $aux['btnAgregar'] = '<span id="'.$row['id_prod_venta'].'" idProducto="'.$row['idProducto'].'" comercial="'.$row['comercial'].'"  class="badge badge-success mr-1 btnAgregarProducto" style="cursor: pointer;"><i style=" color: #f6fcfb;" data-feather="plus"></i></span>';
            // Obtenida la información, la agregamos al array de respuesta
            array_push($response, $aux);
            //Vaciamos el array auxiliar para la siguiente iteración
            $aux = array();
        }
        break;


    } // end switch


    //Devolvemos el array de respuesta
    return $response;
}


echo json_encode(productosParaVenta($conexion_bd));
?>
