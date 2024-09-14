<?
$ruta = "../../../";
include $ruta."sys/precarga_mysqli.php";

$permitidos = array(1,9,11,12,13); // SA y GC (correcto)
$acceso = validaAccesoAutorizado($permitidos);

if($acceso == 0)
    redirect("", "");

//Verificamos que hemos recibido los post necesarios
if(!isset($_POST['idCliente'])){
    redirect("misRutas.php", "");
}

include $ruta."sys/hf/header_v3.php";
include $ruta."sys/hf/banner_v3.php";
include $ruta."mtto/mods/menuGral_looknfeel_mysqli.php";


//Incluimos el ajax de productos para saber si hay productos disponibles para venta
include 'ajaxTodosProductos.php';


function formatearTelefono($numero) {
    if (strlen($numero) != 10) {
        return $numero;
    }
    return substr($numero, 0, 3) . '-' . substr($numero, 3, 3) . '-' . substr($numero, 6);
}

//Almacenamos el id de la ruta en una variable de sesion
$_SESSION['workRute_CC'] = $_POST['idRuta'];

//Obtenemos los datos del cliente para levantar el pedido
/*Datos que necesitamos: 
    - atiende
    - negocio - comercial
    - municipio
    - estado
    - tel1, tel2, tel3
    - ruta
    - idCliente

*/
$query = "SELECT 
    cliente.atiende,
    sys_negocio.nombre as negocio,
    cliente.comercial,
    sys_municipios.nombre as municipio,
    sys_estados.nombre as estado,
    cliente.tel1,
    cliente.tel2,
    cliente.tel3,
    cliente.telpreferente
from cliente
INNER JOIN sys_negocio ON cliente.id_negocio = sys_negocio.id
INNER JOIN sys_municipios ON cliente.id_ciudad = sys_municipios.id && sys_municipios.id_sys_est = cliente.id_edo
INNER JOIN sys_estados ON cliente.id_edo = sys_estados.id
WHERE cliente.id = ".$_POST['idCliente'];
if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);

$row_cliente = mysqli_fetch_array($result);

//Verificamos que el cliente tenga un pedido activo para la fecha de hoy si no, lo creamos
$query = "SELECT * FROM pedido WHERE id_cte =  ".$_POST['idCliente']." AND id_ruta = ".$_POST['idRuta']." AND DATE(fecha_modificacion) = '".date('Y-m-d')."'";
if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);

$edicion = false;
//Si no existe el pedido, lo creamos
if(mysqli_num_rows($result) == 0){
    $tiempo = strtotime(date("Y-m-d H:i:s"));
    $idPedido = str_replace(" ", "", $_SESSION['id_usr']."-".$tiempo);
    $query = "INSERT INTO 
    pedido (id, id_cte, id_usr, id_status, alta, salida, carga, sincroniza, id_ruta, id_tipo_pdo, id_carga_abordo, tiene_descuento, fecha_entra, fecha_entrega, fecha_modificacion) 
    VALUES 
    ('{$idPedido}', '{$_POST['idCliente']}', '{$_SESSION['id_usr']}', '5', '{$tiempo}', NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, '{$_POST['idRuta']}', '1', NULL, '0', NULL, NULL, CURRENT_TIMESTAMP)";
    if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);

    //insertamos el pedido en la tabla de callcenter_pedidos para identificarlo como un pedido creado por el callcenter
    $query = "INSERT INTO callcenter_pedidos (id_pedido, id_cliente, id_ruta, id_usuario, fechaAltaPedido, telefonoContacto) 
    VALUES ('{$idPedido}', '{$_POST['idCliente']}', '{$_POST['idRuta']}', '{$_SESSION['id_usr']}', CURRENT_TIMESTAMP, '{$row_cliente['telpreferente']}')";
    // echo $query;
    if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);

    //Obtenemos el pedido que acabamos de crear
    $query = "SELECT * FROM pedido WHERE id = '$idPedido'";
    if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
    $row_pedido = mysqli_fetch_array($result);
    
}else{
    //Si existe el pedido, lo obtenemos
    $row_pedido = mysqli_fetch_array($result);
    $edicion = true;
    
    //Consultamos el telefono de contacto del cliente en el pedido
    $query = "SELECT telefonoContacto FROM callcenter_pedidos WHERE id_pedido = '".$row_pedido['id']."'";
    if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
    $row_telefono = mysqli_fetch_array($result);
}



if($edicion){
    //Obtenemos los productos CALIENTES del pedido
    $query ="SELECT
    pdo_prod.id_prod,
    pdo_prod.cantidad,
    pdo_prod.precio,
    pdo_prod.precio_inicial,
    prod_medida.id as idProdMedida,
    producto.comercial as producto,
    sys_medida.nombre as medida
    FROM
    pdo_prod
    INNER JOIN prod_medida ON pdo_prod.id_prod = prod_medida.id
    INNER JOIN producto ON prod_medida.id_prod = producto.id
    INNER JOIN sys_medida ON prod_medida.id_medida = sys_medida.id
    WHERE pdo_prod.id_pdo = '".$row_pedido['id'] ."' AND pdo_prod.id_status = 4";
    // echo $query;
    
    if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);

}

//Obtenemos los productos que se pueden agregar al pedido
// $query = "SELECT
// prod_medida.id,
// prod_medida.id as idProdMedida,
// prod_oficina.venta,
// prod_gpo2.*,
// producto.comercial,
// producto.id as idProducto,
// sys_medida.nombre AS medida,
// FLOOR(
//     SUM(
//         almacen.cantidad / prod_medida.cant_unid_min
//     )
// ) AS disponible,
// prod_medida.id_tipo_venta
// FROM
// prod_medida
// JOIN prod_oficina ON prod_medida.id = prod_oficina.id_prod && prod_oficina.id_status = 4 && prod_oficina.id_oficina = ".$_POST['idOficina']."
// JOIN producto ON prod_medida.id_prod = producto.id /* && producto.id_status = 4 */
// JOIN prod_gpo2 ON producto.id_gpo2 = prod_gpo2.id && prod_gpo2.id_status = 4
// JOIN sys_medida ON prod_medida.id_medida = sys_medida.id
// JOIN prod_compra ON producto.id = prod_compra.id_prod
// JOIN almacen ON prod_compra.id = almacen.id_compra && almacen.id_status = 4 && almacen.cantidad >= prod_medida.cant_unid_min && almacen.id_bodega = '".$_POST['idBodega']."'
// -- left join bod_oficina on prod_oficina.id_oficina = bod_oficina.id_oficina && bod_oficina.id_status = 4
// LEFT JOIN(
// SELECT
//     promocion.id,
//     promocion.id_prod,
//     promocion_prod.id_oficina,
//     IFNULL(
//         promocion_condicion.recomendada,
//         0
//     ) AS recomendada
// FROM
//     promocion
// LEFT JOIN promocion_condicion ON promocion_condicion.id_promo = promocion.id
// JOIN promocion_prod ON promocion_prod.id_promo = promocion.id && promocion_prod.id_status = 4
// WHERE
//     promocion.id_status = 4
// GROUP BY
//     promocion.id_prod,
//     promocion_prod.id_oficina
// ) vt_promo
// ON
// vt_promo.id_prod = prod_oficina.id_prod && vt_promo.id_oficina = prod_oficina.id_oficina
// WHERE
// prod_medida.id_status = 4 &&(
//     prod_medida.id_tipo_venta = 1 || vt_promo.id IS NOT NULL
// )
// GROUP BY
// prod_medida.id
// HAVING
// disponible > 0
// ORDER BY
// prod_gpo2.nombre ASC,
// producto.comercial ASC,
// sys_medida.nombre ASC";


/* Query de productos CALIENTES */
$query = "SELECT
    pm.id,
    pm.id as idProdMedida,
    po.venta,
    pg.*,
    p.comercial,
    p.id as idProducto,
    sm.nombre medida,
    round(ifnull(alm.disp, 0)/pm.cant_unid_min,0) disp,
    round(ifnull(alm.disp, 0)/pm.cant_unid_min,0) disponible,
    pm.id_tipo_venta
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
// echo $query;
if(!$resultProductos = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);


?>
<link rel="stylesheet" type="text/css" href="css/estilos.css">
<style>
.dataTables_paginate {
    display: none;
}

.dt-right {
    text-align: right;
}

table.dataTable tbody th,
table.dataTable tbody td {
    padding: 3px 3px;
}

/*th, td { white-space: nowrap; }
    div.dataTables_wrapper {
        width: 900px;
        margin: 0 auto;
    }*/

/*tr { height: 50px; }*/
#productosTable {
    font-size: small;
}

.sticky-table {
  position: sticky;
  top: 0;
}

</style>




<div class="card card-principal" style="">
    <!-- Aquí va el contenido de tu tarjeta -->
    <!-- <h2 style="text-align: center;">Nuevo Pedido</h2> -->
    <div class="page-title">
        <h3 class="ml-2">Nuevo Pedido</h3>
    </div>
    <div class="card-body">
        <?php
        // //si el pedido esta en estatus 4 (Vigente, listo para despachar) regresamos un mensaje de error 
        // if($row_pedido['id_status'] == 4){
        //     echo "<div class='alert alert-danger' role='alert'>
        //     <h4 class='alert-heading'>Atención!</h4>
        //     <p>El pedido ya se encuentra en estatus de <b>Vigente</b>, por lo que no se puede editar.</p>
        //     <hr>
        //     <p class='mb-0'>Por favor, verifique el estatus del pedido y si es necesario, comuníquese con el administrador del sistema.</p>
        //     </div>
        //     <a href='misRutas.php' class='btn btn-primary btn-lg btn-block'>Regresar</a>"; 
        //     die();
            
            
        // }
    ?>
        <div class="row">
            <div class="col-md-3 col-sm-6">
                <label for="Cliente">Cliente:</label>
                <label class="form-control" id="nombreCliete"><?= ucwords($row_cliente['atiende']) ?></label>
            </div>
            <div class="col-md-4 col-sm-6">
                <label for="Establecimiento">Establecimiento:</label>
                <label id="establecimiento"
                    class="form-control"><?= ucwords($row_cliente['negocio']." - ".$row_cliente['comercial'] ) ?></label>
            </div>
            <div class="col-md-5 col-sm-6">
                <label for="Ubicacion">Ubicacion:</label>
                <label
                    class="form-control"><?= ucwords($row_cliente['municipio'].", ".$row_cliente['estado'] ) ?></label>
            </div>
        </div> <!--  fin del div de la clase row -->

        <div class="row">
            <div class="col-md-3 col-sm-6">
                <label for="Teléfono">Teléfono:</label>
                <label id="labelTelefonoContacto" class="form-control">
                    <?= formatearTelefono($row_cliente['telpreferente']) ?>
                </label>
            </div>
            <div class="col-md-3 col-sm-6">
                <label for="Ruta">Ruta:</label>
                <label class="form-control"><?= $_POST['nombreRuta'] ?></label>
            </div>
            <div class="col-md-2 col-sm-6">
                <label for="idCliente">ID Cliente:</label>
                <label class="form-control"><?= $_POST['idCliente'] ?></label>
            </div>
            <div class="col-md-2 col-sm-6">
                <label for="btnIncidencia">Regresar:</label>
                <a href="misRutas.php" class="btn btn-primary btn-lg btn-block">Regresar a mis rutas</a>
            </div>
            <div class="col-md-2 col-sm-6">
                <label for="btnIncidencia">Levantar incidencia:</label>
                <button class="form-control btn btn-warning mt-1 btnIncidencia">
                    Levantar Incidencia
                </button>
            </div>
        </div> <!--  fin del div de la clase row -->
        <div class="row">
            <div class="col-md-6 col-lg-6 col-sm-12">
                <div class="row">
                    <div class="col-12">
                        <h5 class="mt-4 mb-4" style="text-align: center;">Productos</h5>
                        <?php
                            //Verifiacmos si hay productos para venta
                            $botonesProductos = array(
                                'calientes' => sizeof(productosParaVenta($conexion_bd,'BtnCalientes')) > 0 ? true : false,
                                'promociones' => sizeof(productosParaVenta($conexion_bd,'btnPromociones')) > 0 ? true : false,
                                'recomendados' => sizeof(productosParaVenta($conexion_bd,'BtnRecomendados')) > 0 ? true : false,
                                'sugeridos' => sizeof(productosParaVenta($conexion_bd,'BtnSugeridos')) > 0 ? true : false,
                                'todos' => sizeof(productosParaVenta($conexion_bd,'BtnTodos')) > 0 ? true : false
                            );
                        ?>
                    </div>
                </div>
                <input type="text" redonly hidden value="<?= $_POST['idBodega'] ?>" name="idBodega" id="idBodega">
                <input type="text" redonly hidden value="<?= $_POST['idRuta'] ?>" name="idRuta" id="idRuta">
                <input type="text" redonly hidden value="<?= $_POST['idOficina'] ?>" name="idOficina" id="idOficina">
                <input type="text" redonly hidden value="<?= $row_pedido['id'] ?>" name="idPedido" id="idPedido">
                <input type="text" redonly hidden value="<?= $_POST['idCliente'] ?>" name="idCliente" id="idCliente">
                <div class="row">
                    <?php
                    echo $botonesProductos['calientes'] ? '<div class="col-auto">
                        <button id="BtnCalientes" class="btn btn-sm btn-info mb-3 btnListarProductos">
                            <span><i style=" color: #f6fcfb;" data-feather="truck"></i></span>
                            <span>Calientes</span>
                        </button>
                    </div>': '';

                    echo $botonesProductos['promociones'] ? '<div class="col-auto">
                        <button id="btnPromociones" class="btn btn-sm btn-success mb-3 btnListarProductos">
                            <span><i style=" color: #f6fcfb;" data-feather="truck"></i></span>
                            <span>Promociones</span>
                        </button>
                    </div>': '';

                    echo $botonesProductos['recomendados'] ? '<div class="col-auto">
                        <button id="BtnRecomendados" class="btn btn-sm btn-warning mb-3 btnListarProductos">
                            <span><i style=" color: #f6fcfb;" data-feather="truck"></i></span>
                            <span>Recomendados</span>
                        </button>
                    </div>': '';

                    echo $botonesProductos['sugeridos'] ? '<div class="col-auto">
                        <button id="BtnSugeridos" class="btn btn-sm btn-primary mb-3 btnListarProductos">
                            <span><i style=" color: #f6fcfb;" data-feather="truck"></i></span>
                            <span>Sugeridos</span>
                        </button>
                    </div>': '';
                    
                    echo $botonesProductos['todos'] ? '<div class="col-auto">
                        <button id="BtnTodos" class="btn btn-sm btn-secondary mb-3  btn-icon-split btnListarProductos">
                            <span><i style=" color: #f6fcfb;" data-feather="truck"></i></span>
                            <span>Todos</span>
                        </button>
                    </div>': '';
                    
                ?>
                </div>
            </div>
            <div class="col-md-6 col-lg-6 col-sm-12">
                <h5 class="mt-4 mb-4" style="text-align: center;">Pedido</h5>
            </div>
        </div>
        <!-- Este row va a contener 2 columnas, un datatable de los pedidos y un datatable de los productos que se pueden agregar al pedido -->
        <div class="row">
            <div class="col-md-6 col-lg-6 col-sm-12">
                <table id="productosTable" class="table table-hover non-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th><small>ID</small></th>
                            <th><small>Prod Medida</small></th>
                            <th><small>Grupo</small></th>
                            <th><small>Descripción</small></th>
                            <th><small>Precio</small></th>
                            <th>
                                <!-- <div class="row">
                                    <button class="mt-2 mb-3 btn btn-success">
                                        <i style=" color: #f6fcfb;" data-feather="shopping-cart"></i> Agregar
                                    </button>
                                </div> -->
                                <div class="row">
                                    <small>Cantidad</small>
                                </div>
                            </th>
                            <th><small>Disp.</small></th>
                            <th><small>Agregar</small></th>
                            <th><small>Búsqueda</small></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                    while($row = mysqli_fetch_array($resultProductos)){
                        echo "<tr>";
                        echo "<td>".$row['idProducto']."</td>";
                        echo "<td>".$row['idProdMedida']."</td>";
                        echo "<td>".$row['nombre']."</td>";
                        echo "<td>".$row['comercial']."</td>";
                        echo "<td> $".$row['venta']."</td>";
                        echo "<td><input idPromo='' type='number' id='input-".$row['idProdMedida']."' style='width: 60px;' class='inputCantidad' value='0' min='0' max='".$row['disponible']."'></td>";
                        echo "<td>".$row['disponible']." ".$row['medida']."</td>";
                        echo '<td><span id="'.$row['idProdMedida'].'" idProducto="'.$row['idProducto'].'" comercial="'.$row['comercial'].'" idPromo="" class="badge badge-success mr-1 btnAgregarProducto" style="cursor: pointer;"><i style=" color: #f6fcfb;" data-feather="plus"></i></span></td>';
                        echo "<td>".$row['nombre']." ".$row['comercial']."</td>";
                        echo "</tr>";
                    }
                    ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th><small>ID</small></th>
                            <th><small>Prod Medida</small></th>
                            <th><small>Grupo</small></th>
                            <th><small>Descripción</small></th>
                            <th><small>Precio</small></th>
                            <th>
                                <div class="row">
                                    <small>Cantidad</small>
                                </div>
                                <!-- <div class="row">
                                    <button class="mt-2 btn btn-success "><i style=" color: #f6fcfb;"
                                            data-feather="shopping-cart"></i> Agregar</button>
                                </div> -->
                            </th>
                            <th><small>Disp.</small></th>
                            <th><small>Agregar</small></th>
                            <th><small>Búsqueda</small></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div id="miDivFijo" class="col-md-6 col-lg-6 col-sm-12">
                <div class="row">
                    <div class="col-12">
                        <div id="alerta" class="alert alert-warning alert-dismissible fade show" style="display: none;"
                            role="alert">
                            <div id="message"></div>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    </div>
                </div>
                <table id="pedidosTable" class="table table-hover non-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th><small>ID</small></th>
                            <th>
                                <!-- <button class="mb-4 btn btn-info btnActualizar">
                                    Actualizar
                                    <i width="15" height="15" style=" color: #f6fcfb;" data-feather="refresh-cw"></i>
                                </button>-->
                                <small>Cantidad</small> 
                            </th>
                            <th><small>Descripción</small></th>
                            <th><small>Precio</small></th>
                            <th><small>Subtotal</small></th>
                            <th><small>Disponible</small></th>
                            <th class=" dt-no-sorting"><small>Eliminar</small></th>
                            <th><small>Búsqueda</small></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                    $totalPedido = 0;
                    if($edicion){

                        while($row = mysqli_fetch_array($result)){
                            echo "<tr>";
                            echo "<td>".$row['idProdMedida']."</td>";
                            $input = '<input type="number" id="'.$row['idProdMedida'].'" style="width: 60px;" class="inputCantidadPedido" min="1" value="'.$row['cantidad'].'">'.$row['medida'];
                            echo "<td>".$input."</td>";
                            echo "<td>".$row['producto']."</td>";
                            echo "<td> $".$row['precio']."</td>";
                            echo "<td> $".number_format($row['precio'] * $row['cantidad'], 2)."</td>";
                            echo "<td>".$row['medida']."</td>";
                            echo '<td><button type="button" id="'.$row['idProdMedida'].'" class="btn btn-danger mt-1 btnEliminarProducto">Eliminar</button></td>';
                            echo "<td>".$row['producto']."</td>";
                            echo "</tr>";
                            $totalPedido += $row['precio'] * $row['cantidad'];
                        }
                    }
                    ?>

                    </tbody>
                    <tfoot>
                        <tr>
                            <th><small>ID</small></th>
                            <th>

                                <small>Cantidad</small>
                                <!-- <button class="mt-2 btn btn-info btnActualizar">Actualizar<i style=" color: #f6fcfb;"
                                        data-feather="refresh-cw"></i></button> -->
                            </th>
                            <th><small>Descripción</small></th>
                            <th><small>Precio</small></th>
                            <th><small>Subtotal</small></th>
                            <th><small>Disponible</small></th>
                            <th class=" dt-no-sorting"><small>Eliminar</small></th>
                            <th><small>Búsqueda</small></th>
                        </tr>
                    </tfoot>
                </table>
                <hr>
                <!-- este row sera para preguntar si el cliente requiere factura y para el total del pedido -->
                <form id="formGeneraPedido" method="post" action="updatePedido.php">
                    <div class="row mt-4">
                        <div class="col-3">
                            <label for="Ruta">Total del pedido:</label>
                            <label id="totalPedido" class="form-control">
                                <?= '$' . number_format($totalPedido, 2, '.', ',') ?> </label>
                        </div>
                        <div class="col-5">
                            <label for="Factura">Cliente requiere factura?:</label>
                            <!-- Creamos un par de radio buttons para saber si el cliente requiere factura -->
                            <div class="form-check">
                                <div class="row">
                                    <div class="col-auto">
                                        <div class="row">
                                            <input class="form-check-input" type="radio" name="requiereFactura"
                                                id="requiereFactura0" checked value="0">
                                            <label class="form-check-label" for="requiereFactura2">No</label>
                                        </div>
                                        <div class="row">
                                            <input class="form-check-input" type="radio" name="requiereFactura"
                                                id="requiereFactura1" value="1">
                                            <label class="form-check-label" for="requiereFactura1">Si</label>
                                        </div>
                                    </div>
                                    <div id="btnDatosFiscales" class="col-auto"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <input type="text" readonly hidden name="idPedido" value=" <?= $row_pedido['id'] ?> ">
                            <input type="text" readonly hidden name="cliente"
                                value=" <?= $row_cliente['negocio']." - ".$row_cliente['comercial'] ?> ">
                            <input type="text" readonly hidden name="ruta" value=" <?= $_POST['nombreRuta'] ?> ">
                            <label for="Pedido">Confirmar pedido:</label>
                            <button type="button" id="btnGenerarPedido" class="form-control btn btn-success mt-1">Confirmar Pedido</button>
                        </div>
                    </div>
                </form>
                <hr>
                <!-- Creamos un row para mostrar un boton para regresar a mis rutas -->
                <div class="row">
                    <div class="col-12">
                        <!-- <a href="misRutas.php" class="btn btn-primary btn-lg btn-block">
                            <i style=" color: #f6fcfb;" data-feather="arrow-left-circle"></i>Regresar a mis rutas
                        </a> -->
                    </div>
                </div>
            </div>
        </div>
    </div> <!-- Aquí termina card-body -->
</div> <!-- Aquí termina card-principal -->
<!-- </div> div del container comentado -->


<!-- Modal de incidencia -->
<div class="modal fade" id="modalIncidencia">
    <div id="tamano" class="modal-dialog modal-lg">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <h4 class="modal-title"></h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <!-- Modal body -->
            <div class="modal-body bodyModal">
                <form method="POST" action="setIncidenciaPedido.php">
                    <input type="text" redonly hidden value="<?= $_POST['idBodega'] ?>" name="idBodega">
                    <input type="text" redonly hidden value="<?= $_POST['idRuta'] ?>" name="idRuta" >
                    <input type="text" redonly hidden value="<?= $_POST['idOficina'] ?>" name="idOficina">
                    <input type="text" redonly hidden value="<?= $row_pedido['id'] ?>" name="idPedido" >
                    <input type="text" redonly hidden value="<?= $_POST['idCliente'] ?>" name="idCliente">
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group mb-2">
                                <label>Seleccione la incidencia correspondiente al caso:</label>
                                <select class="form-control form-control-lg" required name="idIncidencia" id="idIncidencia">
                                    <option selected disabled value="">Seleccione una opción</option>
                                    <?php
                                        $query = "SELECT * FROM callcenter_incidencias WHERE id > 2";
                                        if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
                                        while($row = mysqli_fetch_array($result)){?>
                                            <option value="<?= $row['id'] ?>"><?= $row['nombre'] ?></option>
                                        <?php
                                        }// fin del while   
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col mb-4">
                            <!-- <label for="comentarios">Nota:</label> -->
                                <textarea class="form-control mt-2" name="notaIncidencia" id="notaIncidencia" rows="3" placeholder="Si necesitas agregar una nota puedes hacerlo aquí"></textarea>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6 align-self-center ">
                            <button type="submit" class="btn btn-warning mt-3">
                                <span><i style=" color: #f6fcfb;" data-feather="alert-triangle"></i></span>
                                <span>Levantar Incidencia</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <!-- Modal footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>






<!-- Modal de actualizacion de datos fiscales -->
<div class="modal fade" id="modalDatosFiscales">
    <div id="tamano" class="modal-dialog modal-xl">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <h4 class="modal-title"></h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <!-- Modal body -->
            <div class="modal-body bodyModal">
                <div class="row mb-2">
                    <div class="col-4">
                        <label for="rfc">RFC:</label>
                        <input class="form-control form-control-lg " type="text" name="rfc" id="rfc"
                            placeholder="Ingresa RFC del cliente"
                            pattern="^([A-Z&Ñ]{3,4})\d{6}([A-V1-9][A-Z1-9]\d{1})?$"
                            title="Por favor ingresa un RFC válido">
                    </div>
                    <div class="col-8">
                        <label for="razon_social">Razón social/Nombre completo:</label>
                        <input class="form-control form-control-lg" type="text" name="razon_social" id="razonSocial"
                            placeholder="Ingresa la razon social o nombre completo del cliente"
                            pattern="^[a-zA-Z0-9\s]{3,100}$"
                            title="Por favor ingresa una razon social válida">
                            <small class="form-text text-muted">No son necesarios los signos de puntuación (punto, coma, punto y coma, etc.) </small>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-3">
                        <label for="cp">Código Postal:</label>
                        <input class="form-control form-control-lg" placeholder="CP" id="cp" name="cp" type="text"
                            pattern="\d{5}" title="Por favor ingresa un código postal válido">
                    </div>
                    <div class="col-5">
                        <label for="regimen_fiscal">Régimen Fiscal:</label>
                        <select class="form-control form-control-lg" required name="regimenFiscal" id="regimenFiscal">
                            <option selected disabled value="">Seleccione una opción</option>
                            <?php
                $query = "SELECT * FROM sys_sat_reg_fiscal WHERE id_status = 4";
                if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
                while($row = mysqli_fetch_array($result)){?>
                            <option value="<?= $row['id'] ?>"><?= $row['nombre'] ?></option>
                            <?php
                }
                ?>
                        </select>
                    </div>
                    <div class="col-4">
                        <label for="uso_cfdi">Uso CFDI:</label>
                        <select class="form-control form-control-lg" required name="usoCFDI" id="usoCFDI">
                            <option selected disabled value="">Seleccione una opción</option>
                            <?php
                $query = "SELECT * FROM sys_uso_cfdi";
                if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
                while($row = mysqli_fetch_array($result)){?>
                            <option value="<?= $row['id'] ?>"><?= $row['nombre']." - ".$row['descripcion'] ?></option>
                            <?php
                }
                ?>
                        </select>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-6">
                        <label for="calle">Correo electrónico:</label>
                        <input class="form-control form-control-lg" type="email" name="email" id="email"
                            placeholder="Ingresa el correo electrónico del cliente"
                            pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$"
                            title="Por favor ingresa un correo electrónico válido">
                    </div>
                </div>
            </div>
            <!-- Modal footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-success btn-lg" id="btnGuardarDatosFiscales">Guardar</button>
                <button type="button" class="btn btn-secondary btn-lg" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>

</div> <!-- fin del modal de datos fiscales -->

<!-- Modal de Seleccion de telefono -->
<div class="modal fade" id="modalTelefono" tabindex="-1" role="dialog" aria-labelledby="modalTelefonosLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTelefonosLabel">Seleccione el numero de teléfono para atender a: <b><span id="nombreEstablecimiento"></span></b></h5>
            </div>
            <div class="modal-body">
                <div class="row mb-4">
                    <div class="col-12">
                        <label for="mensaje">Seleccione el numero de teléfono que usó para ponerse en contacto con el cliente:</label>
                        <select class="form-control" name="telefonosCliente" id="telefonosCliente">
                            <option value="" selected disabled>Selecciona un teléfono</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 text-center">
                        <button type="button" value="" class="btn btn-success mt-3" id="btnValidarTelefono">Continuar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Fin del modal de seleccion de telefono -->


<!-- Modal de Seleccion de combo -->
<div class="modal fade" id="modalCombo" tabindex="-1" role="dialog" aria-labelledby="modalCombosLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalComboLabel">Contenido del combo: <u><strong><span id="nombreProducto"></span></strong></u></h5>
            </div>
            <div class="modal-body">

            </div>
        </div>
    </div>
</div>
<!-- Fin del modal de seleccion de combo -->

<?
include $ruta."sys/hf/pie_v3.php";
?>
<script>
//Creamos una funcion asincron que muestre el mensaje de alerta en la pantalla y que se oculte después de 5 segundos
function muestraMensaje(tipo, mensaje) {
    $("#alerta").removeClass("alert-warning");
    $("#alerta").removeClass("alert-success");
    $("#alerta").removeClass("alert-danger");
    // $("#alerta").removeClass("alert-info");
    $("#alerta").addClass("alert-" + tipo);
    $("#message").html(mensaje);
    $("#alerta").show();
    setTimeout(function() {
        $("#alerta").hide();
    }, 6500);
}


function verificaTelefono() {
    $.ajax({
        url: "ajaxGetTelefonosCliente.php",
        type: "POST",
        data: {
            idCliente: $('#idCliente').val(),
            idPedido: $('#idPedido').val(),
            idRuta: $('#idRuta').val()
        },
        dataType: "json",
        success: function(response){           
            //Si solo hay un teléfono, no mostramos el modal y enviamos el formulario
            if(response.telefonos.length == 1 || response.verificado == '1'){
                var telefono = response.telefonos[0];
                //Enviamos el valor del teléfono a la DB
                setTelefono(telefono);
            }else{
                //Escribimos el nombre del establecimiento en el modal
                $("#nombreEstablecimiento").text($("#establecimiento").text());
                //Limpiamos el select de los teléfonos
                $("#telefonosCliente").html("<option value='' selected disabled>Selecciona un teléfono</option>");
                //Recorremos el array de teléfonos para agregarlos al select
                $.each(response.telefonos, function(i, item){
                    $("#telefonosCliente").append("<option value='"+item+"'>"+item+"</option>");
                });
                //Agregamos el id del cliente al campo oculto del modal
                $("#btnSubmitPedido").val($('#idCliente').val());
                //Abrimos el modal
                // $("#modalTelefono").modal("show");
                $('#modalTelefono').modal({
                    backdrop: 'static', 
                    keyboard: false, 
                    show: true 
                });
            }
        },
        error: function(){
            //Si hay un error, mostramos un mensaje de que no se pudieron obtener los teléfonos
            alert("No se pudieron obtener los teléfonos del cliente, intente de nuevo más tarde.");
        }
    }); // Fin de la llamada ajax
}


function setTelefono(telefono){
    //Enviamos el valor del teléfono a la DB
    $.ajax({
        url: "ajaxSetTelefonoCliente.php",
        type: "POST",
        data: {
            idCliente: $('#idCliente').val(),
            idPedido: $('#idPedido').val(),
            idRuta: $('#idRuta').val(),
            telefono: telefono
        },
        dataType: "json",
        success: function(response){
            //Actualizamos el label del teléfono
            $("#labelTelefonoContacto").text(telefono);

            //Si la respuesta es correcta, cerramos el modal
            if(response.status == 'ok'){
                $("#modalTelefono").modal("hide");
            }
        },
        error: function(){
            //Si hay un error, mostramos un mensaje de que no se pudieron obtener los teléfonos
            alert("No se pudo almacenar el teléfono del cliente, intente de nuevo más tarde.");
            $("#modalTelefono").modal("hide");
        }
    }); // Fin de la llamada ajax

}


$(document).ready(function() {

    // verificaTelefono();

    //Agregamos "todos" a la frase de búsqueda de la tabla de productos
    $('#productosTable_filter label').contents().filter(function() {
        return this.nodeType == 3;
    }).replaceWith('Buscar en calientes:');

    //Creamos un evento para almacenar en la DB el telefono que el usuario seleccionó en el modal
    $(document).on("click", "#btnValidarTelefono", function(){
        //Obtenemos el valor del select
        var telefono = $("#telefonosCliente").val();
        //Verificamos que el valor no sea nulo
        if(telefono == null){
            alert("Por favor selecciona un teléfono");
            return;
        }
        //Enviamos el valor del teléfono a la DB
        setTelefono(telefono);
    });


    $(".btnListarProductos").click(function() {
        datos = {
            idRuta: $("#idRuta").val(),
            idBodega: $("#idBodega").val(),
            idOficina: $("#idOficina").val(),
            idCliente: $("#idCliente").val(),
            query: $(this).attr("id")
        }
        $('#productosTable_filter label').contents().filter(function() {
            return this.nodeType == 3;
        }).replaceWith('Buscar en ' + $(this).text().toLowerCase() + ':');


        tabla = $("#productosTable").DataTable();
        //Eliminamos los datos de la tabla
        tabla.clear().draw();
        $.ajax({
            url: "ajaxTodosProductos.php",
            type: "POST",
            data: datos,
            dataType: "json",
            beforeSend: function() {
                $("#productosTable tbody").html(
                    "<tr><td colspan='4' class='text-center'> <img src='../../../sysimg/icoCarga.gif' class='img-fluid'> Cargando productos...</td></tr>"
                );
            },

            success: function(data) {
                //Escribimos en consola el JSON que recibimos
                // console.log(JSON.stringify(data));

                //Si no encontro productos, escribimos un mensaje en la tabla
                if (data.length == 0) {
                    $("#productosTable tbody").html(
                        "<tr><td colspan='5' class='text-center'> <b> No se encontraron productos...</b></td></tr>"
                    );

                }

                //Recorremos el JSON para agregar los datos a la tabla
                $.each(data, function(i, item) {
                    tabla.row.add([
                        item.id,
                        item.idProdMedida,
                        item.grupo,
                        item.producto,
                        item.venta,
                        item.cantidad,
                        item.disponible,
                        item.btnAgregar,
                        item.busqueda
                    ]).draw();
                });
                // Actualizamos los iconos de feather
                feather.replace();
            },
            error: function() {
                $("#productosTable tbody").html(
                    "<tr><td colspan='5' class='text-center'> <b> Error al cargar productos...</b></td></tr>"
                );
            }
        });
    });

});

//Creamos un evento para que al dar clic en el producto se habra el modal que muestre el contenido de un combo utilizando la clase btnVerContenidoCombo
$(document).on("click", ".btnVerContenidoCombo", function() {
    //Capturamos el id del producto
    var idProducto = $(this).attr("id");
    var promocion = $(this).attr("idPromo");
    //Buscamos el nombre del producto en la fila
    var nombreProducto = $(this).attr("nombreProducto");

    //Vamos a solicitar a traves de un ajax el contenido del combo
    $.ajax({
        url: "ajaxGetContenidoPromo.php",
        type: "POST",
        data: {
            idProducto: idProducto,
            idPromo: promocion
        },
        dataType: "json",
        beforeSend: function() {
            //Limpiamos el contenido del modal
            $("#modalCombo .modal-body").html("");
        },

        success: function(data) {
            //Escribimos en consola el JSON que recibimos
            // console.log(JSON.stringify(data));
            //Creamos una variable para almacenar el contenido del combo
            var contenidoCombo = "";
            var contenidoCombo = "<div class='row'>";
            contenidoCombo += "<div class='col-6'><strong>Producto</strong></div>";
            contenidoCombo += "<div class='col-3'><strong>Cantidad</strong></div>";
            contenidoCombo += "</div>";
            //Recorremos el JSON para agregar los datos a la tabla
            $.each(data.productos, function(i, item) {
                contenidoCombo += "<div class='row'>";
                contenidoCombo += "<div class='col-6'>";
                contenidoCombo += item.pza
                contenidoCombo += "</div>";
                contenidoCombo += "<div class='col-3'>";
                contenidoCombo += item.cant_prod_med
                contenidoCombo += "</div>";
                contenidoCombo += "</div>";
            });
            //Escribimos el contenido en el modal #modalCombo
            $("#modalCombo .modal-body").html(contenidoCombo);
            //Actualizamos el titulo del modal
            $("#modalComboLabel").text("Contenido del combo: " + nombreProducto);
            //Abrimos el modal
            $('#modalCombo').modal('show');
        },
        error: function() {
            $("#modalCombo .modal-body").html(
                "<div class='text-center'><b> Error al cargar contenido...</b></div>"
            );
            //Mostramos un mensaje de error en el modal
            $("#modalComboLabel").text("Error al cargar contenido del combo: " + nombreProducto);
            //Abrimos el modal
            $('#modalCombo').modal('show');
        }
    });

});



//Cramos un evento para seleccionar el contenido de la clase inputCantidad al hacer click en ella para facilitar la edición
$(document).on('click', '.inputCantidad', function(){
    $(this).select();
});

//Creamos una función para que al dar click en el botón de incidencia, se abra el modal de incidencia
$(document).on("click", ".btnIncidencia", function() {
    //Capturamos los parametros necesarios para la incidencia
    var idCliente = $("#idCliente").text();
    var nombreCliente = $("#nombreCliete").text();
    var idPedido = $("#idPedido").val();
    var idRuta = $("#idRuta").val();
    var establecimiento = $("#establecimiento").text();
    $('.modal-title').html('Levantar incidencia para el cliente: <b>' + establecimiento + '</b>');
    // $("#tamano").removeClass( "modal-xl" ).addClass( "modal-lg" );

    //Posicionamos el select en la primera opción
    $("#idIncidencia").prop("selectedIndex", 0);
    //Limipamos el textarea
    $("#notaIncidencia").val("");
    //Verificamos si el textarea tiene la clase d-none
    if(!$("#notaIncidencia").hasClass("d-none")){
        //Agregamos la clase d-none
        $("#notaIncidencia").addClass("d-none");
    }

    $('#modalIncidencia').modal({
        show: true
    });
});

//Creamos un evento que se activa cuando el selector #idIncidencia sufra un cambio
$(document).on("change", "#idIncidencia", function(){
    //Obtenemos el valor del select
    var idIncidencia = $(this).val();
    //Si el valor de la incidencias es 3 o 6, mostramos el textarea
    if(idIncidencia == 4 || idIncidencia == 6){
        // Verifiamo si el textarea ya tiene la clase d-none
        if($("#notaIncidencia").hasClass("d-none")){
            //Eliminamos la clase d-none
            $("#notaIncidencia").removeClass("d-none");
        }
    }else{
        // Verifiamo si el textarea ya tiene la clase d-none
        if(!$("#notaIncidencia").hasClass("d-none")){
            //Agregamos la clase d-none
            $("#notaIncidencia").addClass("d-none");
        }            
    }
});


// Creamos un evento para que al dar click en el botón de agregar producto, se agregue el producto al pedido
$(document).on("click", ".btnAgregarProducto", function() {
    var tabla = $('#pedidosTable').DataTable();
    var valores = [];
    var ids = [];
    var productos = [];
    var prodmedida = [];

    //Leemos los atributos del boton para obtener el id del producto y la cantidad
    var idProdMedida = $(this).attr("id");
    var cantidad = $(this).closest("tr").find(".inputCantidad").val();
    var comercial = $(this).attr("comercial");
    var idProducto = $(this).attr("idProducto");
    var valorInput = parseInt($("#input-" + idProdMedida).val());
    //Obtenemos el id de la promoción del input cantidad
    var idPromo = $("#input-" + idProdMedida).attr("idPromo");
    //Validamos que la cantidad no sea 0 o negativa
    if (cantidad < 1) {
        muestraMensaje("warning", "<b>Atención!</b> Ingresa una cantidad válida para el producto: <b>" + comercial+"<b>");
        return;
    }
    //Buscamos la fila donde el usuario dio click
    var filaIndex = tabla.row($(this).closest('tr')).index();
    
    valores.push(cantidad);
    ids.push(idProdMedida);
    productos.push(tabla.row(filaIndex).data());
    prodmedida.push(idProdMedida);
    //Enviamos los datos al servidor
    datos = {
        idCliente: $("#idCliente").text(),
        idRuta: $("#idRuta").val(),
        idPedido: $("#idPedido").val(),
        idOficina: $("#idOficina").val(),
        idPromo: idPromo ? idPromo : 'NULL',
        valores: valores,
        ids: ids,
        prodmedida: prodmedida,
        productos: productos
    }
    $.ajax({
        url: "ajaxAgregaProductoPedido.php",
        type: "POST",
        data: datos,
        dataType: "json",
        beforeSend: function() {
            $("#pedidosTable tbody").html(
                "<tr><td colspan='7' class='text-center'> <img src='../../../sysimg/icoCarga.gif' class='img-fluid'> Agregando producto...</td></tr>"
            );
        },
        success: function(data) {
            
            //Devolvemos a 0 el valor del input
            $("#input-" + idProdMedida).val(0);
            
            //Escribimos en consola el JSON que recibimos
            //console.log(JSON.stringify(data));
            var tablaPedido = $('#pedidosTable').DataTable();
            //Eliminamos los datos de la tabla
            tablaPedido.clear().draw();
            //Recorremos el JSON para agregar los datos a la tabla
            $.each(data.productos, function(i, item) {
                tablaPedido.row.add([
                    item.idProdMedida,
                    item.cantidad,
                    item.producto,
                    item.precio,
                    item.subtotal,
                    item.disponible,
                    item.acciones,
                    item.busqueda
                ]).draw();
            });


            //Actualiza el total del pedido
            $("#totalPedido").text(data.total);
            // // Verificamos si hubo algun mensaje para enviarlo
            if (data.status == 0) {
                muestraMensaje("success", "<b>Hecho!</b> Producto agregado al pedido!");
            } else {
                muestraMensaje("warning", data.mensaje);
            }
        },
        error: function() {
            $("#pedidosTable tbody").html(
                "<tr><td colspan='7' class='text-center'> <img src='../../../sysimg/icoCarga.gif' class='img-fluid'> Error al agregar producto por favor refresque la página.</td></tr>"
            );
        }
    });

});
//Creamos un evento para que al dar click en el botón de eliminar, se elimine el producto del pedido
$(document).on("click", ".btnEliminarProducto", function() {
    var idProdMedida = $(this).attr("id");
    //console.log("Eliminar: " + idProdMedida);
    //Enviamos los datos al servidor
    datos = {
        idCliente: $("#idCliente").text(),
        idRuta: $("#idRuta").val(),
        idPedido: $("#idPedido").val(),
        idOficina: $("#idOficina").val(),
        idProdMedida: idProdMedida
    }

    $.ajax({
        url: "ajaxEliminaProductoPedido.php",
        type: "POST",
        data: datos,
        dataType: "json",
        beforeSend: function() {
            $("#pedidosTable tbody").html(
                "<tr><td colspan='7' class='text-center'> <img src='../../../sysimg/icoCarga.gif' class='img-fluid'> Eliminando producto...</td></tr>"
            );
        },

        success: function(data) {
            //Escribimos en consola el JSON que recibimos
            //console.log(JSON.stringify(data));
            var tablaPedido = $('#pedidosTable').DataTable();
            //Eliminamos los datos de la tabla
            tablaPedido.clear().draw();
            //Recorremos el JSON para agregar los datos a la tabla
            $.each(data.productos, function(i, item) {
                tablaPedido.row.add([
                    item.idProdMedida,
                    item.cantidad,
                    item.producto,
                    item.precio,
                    item.subtotal,
                    item.disponible,
                    item.acciones,
                    item.busqueda
                ]).draw();
            });
            //Actualiza el total del pedido
            $("#totalPedido").text(data.total);
            // // Verificamos si hubo algun mensaje para enviarlo
            if (data.status == 0) {
                muestraMensaje("danger", "<b>Hecho!</b> Producto eliminado del pedido!");
            } else {
                muestraMensaje("warning", data.mensaje);
            }


        }
    });
});

//Creamos un evento cuando el usuario presiona el radio buuton "si" para que se muestre el boton de actualizar datos fiscales
$(document).on("click", "#requiereFactura1", function() {
    
    $("#btnDatosFiscales").html(
        '<button type="button" id="btnActualizaDatosFicales" class="btn btn-info mt-1" ><i style=" color: #f6fcfb;" data-feather="file-plus"></i>Datos Fiscales</button>'
    );
    feather.replace();
});
//Eliminamos el boton de datos fiscales si el usuario presiona el radio button "no"
$(document).on("click", "#requiereFactura0", function() {
    $("#btnDatosFiscales").html('');
});

//Creamos un evento para que al dar click en el botón de actualizar datos fiscales, se abra el modal de datos fiscales
$(document).on("click", "#btnActualizaDatosFicales", function() {
    var establecimiento = $("#establecimiento").text();
    //Enviamos los datos al servidor
    datos = {
        idCliente: $("#idCliente").val()
    }
    //Creamos un ajax para rellenar el formulario de datos fiscales
    $.ajax({
        url: "ajaxDatosFiscales.php",
        type: "POST",
        data: datos,
        dataType: "json",
        success: function(data) {
            //Escribimos en consola el JSON que recibimos
            //console.log("DatosFiscales Recividos en ajaxDatosFiscales: "+JSON.stringify(data));
            // Si encontramos datos fiscales, los mostramos en el formulario
            $('#rfc').val(data.rfc ? data.rfc : '');
            $('#razonSocial').val(data.rs ? data.rs : '');
            $('#cp').val(data.cp ? data.cp : '');
            $('#email').val(data.email ? data.email : '');
            $('#regimenFiscal').val(data.id_regimen ? data.id_regimen : '');
            $('#usoCFDI').val(data.id_usocfdi ? data.id_usocfdi : '');
            
        },
        error: function(data) {
            //console.log(JSON.stringify(data));
        }
    });


    $('.modal-title').html('Actualizar datos fiscales para el cliente: <b>' + establecimiento + '</b>');
    // $("#tamano").removeClass( "modal-xl" ).addClass( "modal-lg" );
    $('#modalDatosFiscales').modal({
        show: true
    });
});

//Creamos un avento para que cada vez que escriban en el input #rfc, se convierta a mayusculas
$(document).on("keyup", "#rfc", function() {
    $(this).val($(this).val().toUpperCase());
});

//Creamos un evento que al presionar ctrl+shift + s guarde el pedido
// $(document).keydown(function(e) {
//     if (e.ctrlKey && e.shiftKey && e.which == 83) {
//         e.preventDefault();
//         $("#btnGenerarPedido").click();
//     }
// });

//Creamos un evento para que al dar click en el botón de guardar datos fiscales, se guarden los datos fiscales
$(document).on("click", "#btnGuardarDatosFiscales", function() {
    //Validamos los campos primeramente
    var rfc = $('#rfc').val().toUpperCase();
    var razonSocial = $('#razonSocial').val();
    var cp = $('#cp').val();
    var correo = $('#email').val();   


    // Validar RFC
    if (!rfc.match(/^([A-Z&Ñ]{3,4})\d{6}([A-V1-9][A-Z1-9]\d{1})?$/)) {
        alert('Por favor ingresa un RFC válido.');
        event.preventDefault();
        return;
    }

    // Validar Razon Social
    if (!razonSocial.match(/^[a-zA-Z0-9\s]{3,100}$/)) {
        alert('Por favor ingresa una razón social válida.');
        event.preventDefault();
        return;
    }

    // Validar CP
    if (!cp.match(/^\d{5}$/)) {
        alert('Por favor ingresa un código postal válido.');
        event.preventDefault();
        return;
    }

    //Validamos que el usuario haya seleccionado un regimen fiscal
    if ($('#regimenFiscal').val() == null) {
        alert('Por favor selecciona un régimen fiscal.');
        event.preventDefault();
        return;
    }

    //Validamos que el usuario haya seleccionado un uso CFDI
    if ($('#usoCFDI').val() == null) {
        alert('Por favor selecciona un uso CFDI.');
        event.preventDefault();
        return;
    }

    // Validar Correo Electrónico
    if (!correo.match(/^[a-zA-Z0-9._+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/)) {
        alert('Por favor ingresa un correo electrónico válido.');
        event.preventDefault();
        return;
    }

    //Enviamos los datos al servidor
    datos = {
        idCliente: $("#idCliente").val(),
        idRuta: $("#idRuta").val(),
        idPedido: $("#idPedido").val(),
        idOficina: $("#idOficina").val(),
        rfc: $("#rfc").val(),
        razonSocial: $("#razonSocial").val(),
        cp: $("#cp").val(),
        id_regimen: $("#regimenFiscal").val(),
        id_usocfdi: $("#usoCFDI").val(),
        email: $("#email").val()
    }
    //console.log("datos enviados: " + JSON.stringify(datos));
    $.ajax({
        url: "ajaxActualizaDatosFiscales.php",
        type: "POST",
        data: datos,
        dataType: "json",
        // beforeSend: function() {
        //     $(".bodyModal").html(
        //         "<div class='text-center'> <img src='../../../sysimg/icoCarga.gif' class='img-fluid'> Guardando datos fiscales...</div>"
        //     );
        // },
        success: function(data) {
            // $('#modalDatosFiscales').modal('hide');
            //Escribimos en consola el JSON que recibimos
            //console.log(JSON.stringify(data));
            // // Verificamos si hubo algun mensaje para enviarlo
            if (data.status == 0) {
                alert('Datos fiscales del cliente actualizados actualizados!');
                // muestraMensaje("success",
                //     "<b>Hecho!</b> Datos fiscales del cliente actualizados actualizados!"
                // );
            } else {
                alert('Tuvimos un problema al actualizar los datos fiscales, por favor intentalo de nuevo. Este error puede facilitar el reconocimiento del problema: '+data.mensaje);
                //muestraMensaje("warning", data.mensaje);
            }

        },
        error: function(data) {
            //console.log(data);
            
        }
    });
});
</script>

<script>
$('#pedidosTable').DataTable({
    dom: 'frti',

    language: {
        "url": "js/spanish.js"
    },
    stripeClasses: [],
    columnDefs: [{
            "targets": [0, 5, 7],
            "visible": false,
            // "width": 30
        },
        {
            "targets": [1],
            "visible": true,
            "width": 30,
            "orderable": false,
            "className": "dt-right"
        }
    ],
    paging: false // Deshabilita la paginación
});


$('#productosTable').DataTable({
    dom: 'frti',

    language: {
        "url": "js/spanish.js"
    },
    stripeClasses: [],
    order: [
        [2, 'asc']
    ],
    columnDefs: [{
            "targets": [0, 1, 8],
            "visible": false
            // "width": 30
        },
        // {
        //     "targets": [0],
        //     "visible": true,
        //     "width": 30
        // },

        {
            //Columna de disponibles
            "targets": [6,4],
            "visible": true,
            // "width": 30,
            "className": "dt-right"
        },
        {
            //color de la columna de cantidad
            "targets": [5,7],
            "visible": true,
            "orderable": false,
            "className": "dt-right"

        }

    ],
    paging: false // Deshabilita la paginación
});

//Creamos un evento para que al dar click en el botón de agregar, se agregue el producto al pedido
$(document).on("click", ".btnAgregar", function() {
    var tabla = $('#productosTable').DataTable();
    var valores = [];
    var ids = [];
    var productos = [];
    var prodmedida = [];
    var bandera = false;
    tabla.rows().every(function() {
        var data = this.data();
        var id = data[0]; // Obtiene el ID de la columna oculta
        var valorInput = $(this.cell(this.index(), '5').node()).find('input').val();
        // Filtramos los valores que sean mayores a 0 
        if (valorInput > 0) {
            valores.push(valorInput);
            ids.push(id);
            prodmedida.push(data[1]);
            //Convertimos data a un JSON para poder manipularlo
            productos.push(data);
            bandera = true;
        }
    });
    if (!bandera) {
        muestraMensaje("warning", "<b>Atención!</b>  No se ha seleccionado ningún producto");
        return;
    }

    //console.log(valores); // Imprime los valores de los inputs y los IDs en la consola
    //console.log(ids);
    //console.log(prodmedida);
    //console.log(productos);


    //Enviamos los datos al servidor
    datos = {
        idCliente: $("#idCliente").text(),
        idRuta: $("#idRuta").val(),
        idPedido: $("#idPedido").val(),
        idOficina: $("#idOficina").val(),
        valores: valores,
        ids: ids,
        prodmedida: prodmedida,
        productos: productos
    }

    $.ajax({
        url: "ajaxAgregaProductoPedido.php",
        type: "POST",
        data: datos,
        dataType: "json",
        beforeSend: function() {
            $("#pedidosTable tbody").html(
                "<tr><td colspan='7' class='text-center'> <img src='../../../sysimg/icoCarga.gif' class='img-fluid'> Agregando productos...</td></tr>"
            );
        },
        success: function(data) {
            //Escribimos en consola el JSON que recibimos
            //console.log(JSON.stringify(data));
            var tablaPedido = $('#pedidosTable').DataTable();
            //Eliminamos los datos de la tabla
            tablaPedido.clear().draw();
            if (data.status == 0) {
                muestraMensaje("success", "<b>Hecho!</b> Productos agregados al pedido!");
            } else {
                muestraMensaje("warning", data.mensaje);
            }

            //Recorremos el JSON para agregar los datos a la tabla
            $.each(data.productos, function(i, item) {
                tablaPedido.row.add([
                    item.idProdMedida,
                    item.cantidad,
                    item.producto,
                    item.precio,
                    item.subtotal,
                    item.disponible,
                    item.acciones,
                    item.busqueda
                ]).draw();
            });
            feather.replace();
            //Limpiamos los inputs
            tabla.rows().every(function() {
                var valorInput = $(this.cell(this.index(), '5').node()).find(
                    'input').val(0);
            });
            //Actualiza el total del pedido
            $("#totalPedido").text(data.total);
            //Limpiamos todas las variables
            valores = [];
            ids = [];
            precios = [];
            productos = [];
            prodmedida = [];
        },
        error: function(data) {
            //console.log(data);
        }

    });
});

//Creamos un evento de confirmación para que al dar click en el botón de generar pedido, se genere el pedido
$(document).on("click", "#btnGenerarPedido", function() {

    //Contamos si hay productos en la tabla de pedidos por que si no, no se puede generar el pedido
    var tabla = $('#pedidosTable').DataTable();
    var contador = 0;
    tabla.rows().every(function() {
        contador++;
    });
    if (contador == 0) {
        muestraMensaje("warning", "<b>Atención!</b>  No no se puede <b>CONFIRMAR</b> un pedido sin productos.");
        return;
    }


    //Enviamos una confirmación para generar el pedido
    var r = confirm(
        "¿Confirma enviar el pedido a despacho? Si el cliente requiere factura, asegúrese de que los datos fiscales del cliente sean correctos."
    );
    if (r == false) {
        return;
    } else {
        //Hacemos submit del formulario
        $("#formGeneraPedido").submit();
    }
});


//Creamos un evento para que al dar click en el botón de actualizar, se actualice la cantidad del producto en el pedido
$(document).on("click", ".btnActualizar", function() {
    var tabla = $('#pedidosTable').DataTable();
    var valores = [];
    var ids = [];
    var bandera = false;
    tabla.rows().every(function() {
        var data = this.data();
        var id = data[0]; // Obtiene el ID de la columna oculta
        var valorInput = $(this.cell(this.index(), '1').node()).find('input').val();

        //Si encontramos valores negativos, mostramos un mensaje de error y detenemos la ejecución
        if (valorInput < 0) {
            muestraMensaje("danger",
                "<b>Atención!</b>  No se pueden agregar cantidades negativas");
            return;
        }

        // Filtramos los valores que sean mayores a 0
        if (valorInput > 0) {
            valores.push(valorInput);
            ids.push(id);
            bandera = true;
        }
    });
    if (!bandera) {
        muestraMensaje("warning", "<b>Atención!</b>  No se ha podido actualizar ningún producto");
        return;
    }

    //console.log(valores); // Imprime los valores de los inputs y los IDs en la consola
    //console.log(ids);

    //Enviamos los datos al servidor
    datos = {
        idCliente: $("#idCliente").text(),
        idRuta: $("#idRuta").val(),
        idPedido: $("#idPedido").val(),
        idOficina: $("#idOficina").val(),
        valores: valores,
        ids: ids
    }

    $.ajax({
        url: "ajaxActualizaProductoPedido.php",
        type: "POST",
        data: datos,
        dataType: "json",
        beforeSend: function() {
            $("#pedidosTable tbody").html(
                "<tr><td colspan='7' class='text-center'> <img src='../../../sysimg/icoCarga.gif' class='img-fluid'> Actualizando productos...</td></tr>"
            );
        },

        success: function(data) {
            //Escribimos en consola el JSON que recibimos
            //console.log(JSON.stringify(data));
            var tablaPedido = $('#pedidosTable').DataTable();
            //Eliminamos los datos de la tabla
            tablaPedido.clear().draw();
            if (data.status == 0) {
                muestraMensaje("success",
                    "<b>Hecho!</b> Productos actualizados en el pedido!");
            } else {
                muestraMensaje("warning", data.mensaje);
            }

            //Recorremos el JSON para agregar los datos a la tabla
            $.each(data.productos, function(i, item) {
                tablaPedido.row.add([
                    item.idProdMedida,
                    item.cantidad,
                    item.producto,
                    item.precio,
                    item.subtotal,
                    item.disponible,
                    item.acciones,
                    item.busqueda
                ]).draw();
            });
            //Actualiza el total del pedido
            $("#totalPedido").text(data.total);
            //Enviamos un mensaje de éxito
            muestraMensaje("success", "<b>Hecho!</b> Productos actualizados!");
        }
    });
});


//Creamos un evento para al notar un cambio en el input de cantidad, se actualice el pedido
$(document).on("change", ".inputCantidadPedido", function() {
    var tabla = $('#pedidosTable').DataTable();
    var valores = [];
    var ids = [];
    var bandera = false;
    tabla.rows().every(function() {
        var data = this.data();
        var id = data[0]; // Obtiene el ID de la columna oculta
        var valorInput = $(this.cell(this.index(), '1').node()).find('input').val();

        //Si encontramos valores negativos o vacios, mostramos un mensaje de error y detenemos la ejecución
        if (valorInput < 1 || valorInput == "") {
            muestraMensaje("danger",
                "<b>Atención!</b>  No se pueden agregar cantidades negativas");
            return;
        }

        // Filtramos los valores que sean mayores a 0
        if (valorInput > 0) {
            valores.push(valorInput);
            ids.push(id);
            bandera = true;
        }
    });
    if (!bandera) {
        muestraMensaje("warning", "<b>Atención!</b>  No se ha podido actualizar ningún producto");
        return;
    }

    //console.log(valores); // Imprime los valores de los inputs y los IDs en la consola
    //console.log(ids);

    //Enviamos los datos al servidor
    datos = {
        idCliente: $("#idCliente").text(),
        idRuta: $("#idRuta").val(),
        idPedido: $("#idPedido").val(),
        idOficina: $("#idOficina").val(),
        valores: valores,
        ids: ids
    }

    $.ajax({
        url: "ajaxActualizaProductoPedido.php",
        type: "POST",
        data: datos,
        dataType: "json",
        beforeSend: function() {
            $("#pedidosTable tbody").html(
                "<tr><td colspan='7' class='text-center'> <img src='../../../sysimg/icoCarga.gif' class='img-fluid'> Actualizando productos...</td></tr>"
            );
        },

        success: function(data) {
            //Escribimos en consola el JSON que recibimos
            //console.log(JSON.stringify(data));
            var tablaPedido = $('#pedidosTable').DataTable();
            //Eliminamos los datos de la tabla
            tablaPedido.clear().draw();
            if (data.status == 0) {
                muestraMensaje("success",
                    "<b>Hecho!</b> Productos actualizados en el pedido!");
            } else {
                muestraMensaje("warning", data.mensaje);
            }

            //Recorremos el JSON para agregar los datos a la tabla
            $.each(data.productos, function(i, item) {
                tablaPedido.row.add([
                    item.idProdMedida,
                    item.cantidad,
                    item.producto,
                    item.precio,
                    item.subtotal,
                    item.disponible,
                    item.acciones,
                    item.busqueda
                ]).draw();
            });
            //Actualiza el total del pedido
            $("#totalPedido").text(data.total);
            //Enviamos un mensaje de éxito
            muestraMensaje("success", "<b>Hecho!</b> Productos actualizados!");
        }
    });
});
</script>