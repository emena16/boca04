<?
$ruta = "../../../";
include $ruta."sys/precarga_mysqli.php";
//include $ruta."sys/fcn/fcnSelects.php";

$permitidos = array(1, 8, 9, 10, 13);
$acceso = validaAccesoAutorizado($permitidos);

if($acceso == 0)
	redirect("", "");


function formatearTelefono($numero) {
    if (strlen($numero) != 10) {
        return $numero;
    }
    return substr($numero, 0, 3) . '-' . substr($numero, 3, 3) . '-' . substr($numero, 6);
}

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

//Creamos una funcion que calcule el total de productos en el pedido
function totalPeidido($conexion_bd,$idPedido){
    //Obtenemos los productos del pedido
    $query ="
    SELECT
        SUM(pdo_prod.precio * pdo_prod.cantidad) as total
    FROM
        pdo_prod
    JOIN prod_medida ON pdo_prod.id_prod = prod_medida.id
    JOIN producto ON prod_medida.id_prod = producto.id
    JOIN sys_medida ON prod_medida.id_medida = sys_medida.id
    WHERE pdo_prod.id_pdo = '".$idPedido."' AND pdo_prod.id_status = 4";
    //Ejecutamos el query
    $result = mysqli_query($conexion_bd,$query);
    if(!$result) {
        return 0;
    }

    if(!mysqli_num_rows($result)) {
        return 0;
    }

    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
    return $row['total'];

}
//Obtenemos el dia de la semana
$diaHoy = date('N');
$response = array();
$aux = array();
$montoTotal = 0;
//Telefonos falsos
$fakePhones = array(
    '0000000000',
    '1111111111',
    '2222222222',
    '3333333333',
    '4444444444',
    '5555555555',
    '6666666666',
    '7777777777',
    '8888888888',
    '9999999999'
);


//Antes de todo, verificamos que la ruta este disponible
$fechaHoy = date('Y-m-d');
//-- DETERMINAR SI UNA RUTA ESTA BLOQUEADA (SI NO HAY RESULTADO SE CONSIDERA DESBLOQUEADA)
$querybloqueoPdo = "SELECT bloqueo
from  bloqueo_pdo bp 
where bp.id_ruta = {$_POST['idRuta']} && bp.id_status = 4 && bp.fecha_solicitud = '".$fechaHoy."'";
if(!$result = mysqli_query($conexion_bd,$querybloqueoPdo))errores(mysqli_errno($conexion_bd), 0);
$row_bloqueoPdo = mysqli_fetch_array($result, MYSQLI_ASSOC);

//Si existe una fila, la ruta esta bloqueada por lo que no se puede trabajar
$disabledUniversal = '';
$disabledClassUniversal = '';

//Si la ruta esta bloqueada, deshabilitamos los botones
if($result != null){
    if($row_bloqueoPdo['bloqueo'] == 1){
        $disabledUniversal = 'disabled';
        $disabledClassUniversal = 'disabled';
    }
}
//Creamos una variable para evaluar 
//Si no esta bloqueda tambien vamos a verificar si ya se cerro la ruta
$query = "SELECT * FROM ruta_sincro WHERE id_ruta = ".$_POST['idRuta']." AND fecha = '".$fechaHoy."'";
if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
if(mysqli_num_rows($result) > 0){
    $row_sincro = mysqli_fetch_array($result, MYSQLI_ASSOC);
    if($row_sincro['id_estado'] == 2){
        $disabledUniversal = 'disabled';
        $disabledClassUniversal = 'disabled';
    }
}

$incidencias = array();
//Descargamos el catalogo de inciencias
$query = "SELECT * FROM callcenter_incidencias";
if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
while($row = mysqli_fetch_array($result, MYSQLI_ASSOC)){
    $incidencias[$row['id']] = $row['nombre'];
}

//Vamos a obtener los clientes de la ruta que recibimos
//CONCAT(SUBSTR(cliente.tel1, 1, 3), '-', SUBSTR(cliente.tel1, 4, 3), '-', SUBSTR(cliente.tel1, 7)) AS tel1_formateado
$queryClientes = "SELECT bod_oficina.id_bodega as idBodega, ruta.id_oficina as idOficina,
ruta.id as idRuta, cliente.id as idCliente, concat(sys_negocio.nombre , ' - ', cliente.comercial) as establecimiento,
ruta.nombre as ruta, cliente.tel1, cliente.tel2, cliente.tel3, cliente.telpreferente
FROM cliente
INNER JOIN sys_negocio ON cliente.id_negocio = sys_negocio.id
INNER JOIN rutas_cliente on rutas_cliente.id_cte = cliente.id && rutas_cliente.id_status = 4
INNER JOIN ruta ON rutas_cliente.id_ruta = ruta.id
INNER JOIN bod_oficina ON ruta.id_oficina = bod_oficina.id_oficina
WHERE rutas_cliente.id_visita = $diaHoy AND cliente.id_status = 4 AND rutas_cliente.id_ruta = ".$_POST['idRuta'];
if(!$result = mysqli_query($conexion_bd,$queryClientes))errores(mysqli_errno($conexion_bd), 0);
//Recorremos los clientes para enviarlos al datatable
while($row = mysqli_fetch_array($result, MYSQLI_ASSOC)){

    //Antes de todo, verificamos si el cliente tiene un telefono valido para poder comunicarnos con el
    $tienetelefono = false;
    $telefonos= array();
    if($row['tel1'] != '') array_push($telefonos, $row['tel1']);
    if($row['tel2'] != '') array_push($telefonos, $row['tel2']);
    if($row['tel3'] != '') array_push($telefonos, $row['tel3']);

    // Vamos a verificar cada telefono para saber si es valido
    if(count($telefonos) > 0){
        foreach($telefonos as $telefono){
            // Si el telefono tiene 10 caracteres, es un telefono valido
            if(strlen($telefono) == 10){
                // Vamos a verificar si el telefono es concistente y no lleno de numeros repetidos
                if(!in_array($telefono, $fakePhones)){
                    $tienetelefono = true;
                    break;
                }
            }
        }
    }else{
        $tienetelefono = false;
    }

    // Si el cliente no tiene telefono valido saltamos al siguiente cliente
    if(!$tienetelefono) continue;

    $telpreferente = '';
    $colorRow = '';
    $telefonosConIncidencia = array();

    //Vamos a obtener las incidencias del cliente del dia de hoy, sobre todo las que involucran el telefono de contacto
    $query = "SELECT * FROM callcenter_incidenciaspedido WHERE id_incidencia IN (3,6) AND id_cliente = ".$row['idCliente']." AND DATE(fechaIncidencia) = CURDATE()";
    // echo $query."<br>";
    if(!$result_incidencia = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);

    //Si no hay telefono preferente, vamos a buscar el telefono preferente para asignarlo
    if($row['telpreferente'] == ''){
        //Si hay incidencia vamos a vericar numero con numero para excluir los que ya tienen incidencia
        if(mysqli_num_rows($result_incidencia) > 0){
            $colorRow = 'table-warning';
            
            while($row_incidencia = mysqli_fetch_array($result_incidencia)){
                //Vamos a buscar que telefono asignar
                $telefonosConIncidencia[] = $row_incidencia['telefono'];
            }

            //Ya conociendo los telefonos con incidencia, vamos a buscar el primer telefono valido que no tenga incidencia
            $asignoTelefono = false;
            foreach($telefonos as $telefono){
                if(!in_array($telefono, $telefonosConIncidencia)){
                    $row['telpreferente'] = $telefono;
                    $asignoTelefono = true;
                    break;
                }
            }

            //Actualizamos el telefono preferente en la base de datos
            if($asignoTelefono){
                $query = "UPDATE cliente SET telpreferente = '".$row['telpreferente']."' WHERE id = ".$row['idCliente'];
                // echo $query."<br>";
                if(!$resultUpdate = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
                // $colorRow = 'table-warning';
            }else{
                //Si no hay telefono preferente valido saltamos al siguiente cliente
                continue;
            }

        }else{
            //Si no hay incidencia, asignamos el primer telefono valido como telefono preferente
            $row['telpreferente'] = $telefonos[0];
            //Actualizamos el telefono preferente en la base de datos
            $query = "UPDATE cliente SET telpreferente = '".$row['telpreferente']."' WHERE id = ".$row['idCliente'];
            // echo $query."<br>";
            if(!$resultUpdate = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);

        }
    }else{
        $preferenteConIncidencia = false;
        // Si existe un telefono preferente, vamos a verificar si tiene incidencia y si la tiene la vamos a cambiar
        if(mysqli_num_rows($result_incidencia) > 0){
            $colorRow = 'table-warning';

            while($row_incidencia = mysqli_fetch_array($result_incidencia)){
                $telefonosConIncidencia[] = $row_incidencia['telefono'];
                if($row_incidencia['telefono'] == $row['telpreferente']){
                    $preferenteConIncidencia = true;                
                }
            }

            //El telefono preferente tiene incidencia, vamos a buscar el primer telefono valido que no tenga incidencia
            if($preferenteConIncidencia){

                $asignoTelefono = false;
                foreach($telefonos as $telefono){
                    if(!in_array($telefono, $telefonosConIncidencia)){
                        $row['telpreferente'] = $telefono;
                        $asignoTelefono = true;
                        break;
                    }
                }


                //Actualizamos el telefono preferente en la base de datos
                if($asignoTelefono){
                    $query = "UPDATE cliente SET telpreferente = '".$row['telpreferente']."' WHERE id = ".$row['idCliente'];
                    // echo $query."<br>";
                    if(!$resultUpdate = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
                    $colorRow = 'table-warning';
                }else{
                    //Si no hay telefono preferente valido saltamos al siguiente cliente
                    continue;
                }
            }
        }

    }

    //Creamos una variable para evaluar la procedencia de los pedidos
    $procedencia = 0;
    $tieneIncidencia = false;
    $existePedido = false;
    //Verificamos si el cliente tiene una incidencia o su pedido esta pendiente
    $query = "SELECT * FROM callcenter_incidenciaspedido WHERE id_cliente = ".$row['idCliente']." AND id_ruta = ".$_POST['idRuta']." AND DATE(fechaIncidencia) = CURDATE() ORDER BY id DESC LIMIT 1";
    if(!$result_incidencia = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
    
    if(mysqli_num_rows($result_incidencia) > 0){
        $row_incidencia = mysqli_fetch_array($result_incidencia);
        $row['estado'] = $incidencias[$row_incidencia['id_incidencia']];
        //Si la incidencia es 3 o 6 concatenmos el telefono
        if($row_incidencia['id_incidencia'] == 3 || $row_incidencia['id_incidencia'] == 6){
            $row['estado'] .= ' <small>Previo: '.$row_incidencia['telefono'].'</small>';
        }
        $tieneIncidencia = true;
    }else{
        $row['estado'] = '';
        $row_incidencia['id_incidencia'] = 1;
    }
    //Varificamos si el cliente ya tiene un pedido en la ruta
    $existePedido = false;
    $query = "SELECT pedido.id, CONCAT(usr.nombres, ' ', usr.apat) AS emisor, pedido.id_status  FROM pedido 
    INNER JOIN callcenter_pedidos ON pedido.id = callcenter_pedidos.id_pedido
    INNER JOIN usr on callcenter_pedidos.id_usuario = usr.id
    WHERE pedido.id_cte = ".$row['idCliente']." AND pedido.id_ruta = ".$_POST['idRuta']." AND DATE(pedido.fecha_modificacion) = '".date('Y-m-d')."'";
    if(!$result_pedido = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
    
    if(mysqli_num_rows($result_pedido) > 0){
        $row_pedido = mysqli_fetch_array($result_pedido);
        $row_pedido['id'] = str_replace(' ','',$row_pedido['id']);
        $existePedido = true;
        $totalPedido = totalPeidido($conexion_bd,$row_pedido['id']);
        $row['monto'] = number_format($totalPedido , 2);
        $existePedido = true;
        $row['estado'] = $row_pedido['id_status'] == 4 ? 'Pedido confirmado <br> <small>Emisor: '.$row_pedido['emisor'].'</small>' : $row['estado'].'<br> Emisor: <small>'.$row_pedido['emisor'].'</small>';
    }else{
        $row['monto'] = "0.00";
        $totalPedido = 0;
    }
    /**
     * CONTRUIMOS BOTONES POR SEPARADO
     * Si el cliente ya tiene una incidencia con status 3, 5 o 6 el boton de incidencia se deshabilita
     * Si el cliente ya tiene un pedido en la ruta, el boton de pedido se muestra de color azul con la leyenda "Editar Pedido"
     * En otro caso el boton de incidencia se habilita
     */
    $botonIncidencia = '';
    //$existePedido OR [3, 5, 6]
    if ( in_array($row_incidencia['id_incidencia'], [0])) {       
         $botonIncidencia = '<button type="button" disabled class="btn btn-warning btn-sm mt-1 btnIncidencia" disabled><span><i style=" color: #f6fcfb;" data-feather="alert-triangle"></i></span><span><b>Incidencia</b></span></button>';
    }else{
        $botonIncidencia = '<button type="button" '.$disabledUniversal.' establecimiento="'.$row['establecimiento'].'" telpreferente="'.$row['telpreferente'].'" nombreRuta="'.$row['ruta'].'" oficina="'.$row['idOficina'].'" ruta="'.$row['idRuta'].'" id="btnIncidencia-'.$row['idCliente'].'" cliente="'.$row['idCliente'].'" class="btn btn-warning btn-sm mt-1 btnIncidencia '.$disabledClassUniversal.'"><span><i style=" color: #f6fcfb;" data-feather="alert-triangle"></i></span><span><b>Incidencia</b></span></button>';
    }

    $classBoton = $estadoBoton = $botonPedido = '';
    //3, 5, 6
    //$classBoton = $estadoBoton = (in_array($row_incidencia['id_incidencia'], [0]) OR $row_pedido['id_status'] == 4) ? 'disabled' : '';
    
    if($existePedido){
        if($row_pedido['id_status'] == 4){
            $procedencia = 3;
            $botonPedido =  '<form class="formNvoPedido" ruta="'.$row['idRuta'].'" id="formNvoPed-'.$row['idCliente'].'" method="post" action="nuevoPedido.php">
            <input hidden="true" readonly value="'.$row['idBodega'].'" name="idBodega" type="text">
            <input hidden="true" readonly value="'.$row['idRuta'].'" name="idRuta" type="text">
            <input hidden="true" readonly value="'.$row['idOficina'].'" name="idOficina" type="text">
            <input hidden="true" readonly value="'.$row['ruta'].'" name="nombreRuta" type="text">
            <button '.$estadoBoton.' '.$disabledUniversal.' type="submit" id="'.$row['idCliente'].'" name="idCliente" ruta="'.$row['idRuta'].'" value="'.$row['idCliente'].'" class="btn btn-info btn-sm mt-1 '.$classBoton.' '.$disabledClassUniversal.' btnLevantarPedido"><span><i style=" color: #f6fcfb;" data-feather="user-check"></i></span><span><b>Pedido Confirmado</b></span></button>
        </form>';
        }else{
            $procedencia = 2;
            $botonPedido = '<form class="formNvoPedido" ruta="'.$row['idRuta'].'" id="formNvoPed-'.$row['idCliente'].'" method="post" action="nuevoPedido.php">
                <input hidden="true" readonly value="'.$row['idBodega'].'" name="idBodega" type="text">
                <input hidden="true" readonly value="'.$row['idRuta'].'" name="idRuta" type="text">
                <input hidden="true" readonly value="'.$row['idOficina'].'" name="idOficina" type="text">
                <input hidden="true" readonly value="'.$row['ruta'].'" name="nombreRuta" type="text">
                <button '.$estadoBoton.' '.$disabledUniversal.' type="submit" id="'.$row['idCliente'].'" name="idCliente" ruta="'.$row['idRuta'].'" value="'.$row['idCliente'].'" class="btn btn-primary btn-sm mt-1 '.$classBoton.' '.$disabledClassUniversal.' btnLevantarPedido"><span><i style=" color: #f6fcfb;" data-feather="edit"></i></span><span><b>Editar Pedido</b></span></button>
            </form>';
        }
    }else{
        $procedencia = 0;
        $botonPedido = '<form class="formNvoPedido" ruta="'.$row['idRuta'].'" id="formNvoPed-'.$row['idCliente'].'" method="post" action="nuevoPedido.php">
            <input hidden="true" readonly value="'.$row['idBodega'].'" name="idBodega" type="text">
            <input hidden="true" readonly value="'.$row['idRuta'].'" name="idRuta" type="text">
            <input hidden="true" readonly value="'.$row['idOficina'].'" name="idOficina" type="text">
            <input hidden="true" readonly value="'.$row['ruta'].'" name="nombreRuta" type="text">
            <button '.$estadoBoton.' '.$disabledUniversal.' type="submit" id="'.$row['idCliente'].'" name="idCliente" ruta="'.$row['idRuta'].'" value="'.$row['idCliente'].'" class="btn btn-success btn-sm mt-1 '.$classBoton.' '.$disabledClassUniversal.' btnLevantarPedido"<span><i style=" color: #f6fcfb;" data-feather="file-text"></i></span><span><b>Levantar Pedido</b></span></button>
        </form>';
    }

    //Si tiene incidencia pero no tiene pedido la procedencia es 1
    if($tieneIncidencia && !$existePedido){
        $procedencia = 1;
    }
    //Si tiene incidencia la escribimos en el estado siempre y cuando la procedencia sea menor a 3
    if($tieneIncidencia && $procedencia < 3 and $row_incidencia['notaIncidencia'] != ''){
        $row['estado'] .= '<br> <small>Nota: '.$row_incidencia['notaIncidencia'].'</small>';
    }



    //Si es un cliente con el que nos podemos comunicar, creamos un boton para enviar via whatsapp un mensaje diciendo que somos de la empresa y que pronto nos pondremos en contacto con el
    $botonWhatsappAviso = '<br>';

    //Preguntamos si ya hemos  avisado previamente al cliente a traves del telefono asignado del usuario de la sesion
    $queryAviso = "SELECT * FROM callcenter_telefonoasignado 
        INNER JOIN callcenter_mensajeaviso ON callcenter_mensajeaviso.id_telefono = callcenter_telefonoasignado.id_telefono AND callcenter_mensajeaviso.id_cliente = ".$row['idCliente']." 
        WHERE callcenter_mensajeaviso.telefonoCliente = '".$row['telpreferente'] ."' AND  callcenter_mensajeaviso.id_telefono IN (SELECT id_telefono FROM callcenter_telefonoasignado WHERE id_usuario = ". $_SESSION['id_usr'] ." AND DATE(fechaAsignacion) = CURDATE())";
    //Algunas veces el usuario puede no tener un telefono asignado, por lo que no se puede enviar un mensaje de aviso asi que vamos a usar un try catch
    try{
        if(!$resultAviso = mysqli_query($conexion_bd,$queryAviso))errores(mysqli_errno($conexion_bd), 0);
    }catch(Exception $e){
        $resultAviso = null;
    }
    // Si obtenemos un registro ententonces ya hemos enviado un mensaje de aviso por lo que no mostramos el boton
    if(mysqli_num_rows($resultAviso) < 1){
        // Para cada telefono valido, creamos un boton para enviar un mensaje de aviso
        $botonWhatsappAviso .= '<p id="'.$row['idCliente'].'" class="d-none telefonoFormatCliente">'.formatearTelefono($row['telpreferente']).'</p><span style="cursor: pointer;" title="'.$row['telpreferente'].'" establecimiento="'.$row['establecimiento'].'" telefono="'.$row['telpreferente'].'" id="'.$row['idCliente'].'" class="badge badge-info mt-1 ml-3 btnWhatsappAviso"><span><i style="color: #f6fcfb; font-size: 10px;" data-feather="users"></i></span><span><b>Saludo</b></span></span>';
    }
    
    // Si el pedido ya fue confirmado, creamos un boton para enviar via whatsapp el pedido al numero del cliente en funcion a los telefonos que tenga registrados
    $botonWhatsapp = '';
    if($procedencia == 3){
        $botonWhatsapp = '<span style="cursor: pointer;" establecimiento="'.$row['establecimiento'].'" title="'.$row['telpreferente'].'" telefono="'.$row['telpreferente'].'" pedido="'.str_replace(' ','',$row_pedido['id']).'" id="'.$row['idCliente'].'" class="badge badge-success mt-1 ml-3 btnWhatsapp"><span><i style=" color: #f6fcfb;" data-feather="send"></i></span><span><b>Ticket</b></span></span>';
    }
    
    // Si el pedido ya fue confirmado, sumamos el monto
    $monto += $procedencia == 3 ? $totalPedido : 0;
    // Agregamos el monto aleatroio
    $aux['idCliente'] = $row['idCliente'];
    $aux['establecimiento'] = ucwords(strtolower($row['establecimiento']));
    $aux['ruta'] = $row['ruta'];
    $aux['telefonos'] = (mysqli_num_rows($resultAviso) < 1) ? $botonWhatsappAviso:formatearTelefono($row['telpreferente']).$botonWhatsapp;
    $aux['monto'] = "$ ".$row['monto'];
    $aux['estado'] = $row['estado'];
    $aux['acciones'] = '<div class="row"> <div class="col-auto">'.$botonIncidencia.'</div> <div class="col-auto">'.$botonPedido.'</div> </div>';
    $aux['procedencia'] = $procedencia;
    $aux['rowColor'] = $colorRow;
    
    $estadopedido = $procedencia == 2 ? 'confirmados' : 'pendientes';
    $aux['busqueda'] = limpiaCadena($row['idCliente']." ".$row['establecimiento']."   ".$row['estado']." ".$row['ruta']." ".$row['tel1']." ".$row['tel2']." ".$row['tel3']." ".$estadopedido);
    // Obtenida la información, la agregamos al array de respuesta
    array_push($response, $aux);
    //Vaciamos el array auxiliar para la siguiente iteración
    $aux = array();

} // Fin del while de clientes

// Ordenamos el array por la procedencia y el nombre del cliente
usort($response, function($a, $b) {
    if ($a['procedencia'] == $b['procedencia']) {
        return strcmp($a['establecimiento'], $b['establecimiento']);
    }
    return $a['procedencia'] - $b['procedencia'];
});

//Consultamos el telefono de atencion utilizado en su turno por el usuario
$queryTelefonoAtencion = "SELECT numero FROM callcenter_telefonos 
INNER JOIN callcenter_telefonoasignado ON callcenter_telefonos.id = callcenter_telefonoasignado.id_telefono
WHERE callcenter_telefonoasignado.id_usuario = $_SESSION[id_usr] AND DATE(callcenter_telefonoasignado.fechaAsignacion) = CURDATE()";
//Ejecutamos el query
if(!$result = mysqli_query($conexion_bd,$queryTelefonoAtencion))errores(mysqli_errno($conexion_bd), 0);
//Si obtenemos un resultado, lo guardamos en la variable
if(mysqli_num_rows($result) > 0){
    $row_telefonoAtencion = mysqli_fetch_array($result, MYSQLI_ASSOC);
}else{
    $row_telefonoAtencion = array('numero' => 'Telefono no disponible');
}

$respuesta = array(
    "clientes" => $response,
    "rutaCerrada" => $disabledUniversal,
    "totalRuta" => number_format($monto, 2),
    "telefonoAtencion" => formatearTelefono($row_telefonoAtencion['numero']),
    "bloqueo" => $row_bloqueoPdo['bloqueo'],
    "queryBloqueo" => $querybloqueoPdo,
    "telefonosConIncidencia" => $telefonosConIncidencia
);

$_SESSION['workRute_CC'] = $_POST['idRuta'];

echo json_encode($respuesta);
?>