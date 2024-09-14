<?php
$ruta = "../../../";
include $ruta."sys/precarga_mysqli.php";

$permitidos = array(1, 8, 9, 10, 13);
$acceso = validaAccesoAutorizado($permitidos);

if($acceso == 0)
	redirect("", "");


//si no existe el pedido, lo buscamos
if(!isset($_POST['idPedido'])){
    //Buscamos si el cliente ya tiene un pedido en la ruta ligado al callcenter
    $query = "SELECT id_pedido FROM callcenter_pedidos WHERE id_cliente = ".$_POST['idCliente']." AND id_ruta = ".$_POST['idRuta']." AND DATE(fechaAltaPedido) = CURDATE()";
    if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
    $_POST['idPedido'] = mysqli_num_rows($result) > 0 ? mysqli_fetch_array($result)['id_pedido'] : NULL;
}
//Limipiamos el telefono
$_POST['telefono'] = str_replace(array(' ','-'),'',$_POST['telPreferente']);
$idPedido = $_POST['idPedido'] == NULL ? 'NULL' : "'".str_replace(' ','',$_POST['idPedido'])."'";

//Para esta version ahora un pedido puede tener muchas incidencias por lo que las insertamos directamente en la tabla de incidencias
$query = "INSERT INTO callcenter_incidenciaspedido (id_pedido, id_usuario, id_incidencia, id_cliente, id_ruta,notaIncidencia, telefono) VALUES (".$idPedido.", ".$_SESSION['id_usr'].", ".$_POST['idIncidencia'].", ".$_POST['idCliente'].", ".$_POST['idRuta'].", '".$_POST['notaIncidencia']."', '".$_POST['telefono']."')";
if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), mysqli_error($conexion_bd));

//Obtenemos los telefonos del cliente 
$query = "SELECT tel1,tel2,tel3,telpreferente FROM cliente WHERE id = ".$_POST['idCliente'];
if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), mysqli_error($conexion_bd));
$row_telefonosCliente = mysqli_fetch_array($result);


//Eliminamos los telefonos vacios 
$telefonosCliente = array();
if($row_telefonosCliente['tel1'] != '') array_push($telefonosCliente, $row_telefonosCliente['tel1']);
if($row_telefonosCliente['tel2'] != '') array_push($telefonosCliente, $row_telefonosCliente['tel2']);
if($row_telefonosCliente['tel3'] != '') array_push($telefonosCliente, $row_telefonosCliente['tel3']);

$mensajeAdicional = '';

//Recorremos los telefonos del cliente para ver cuantas incidencias tiene
foreach($telefonosCliente as $telefono){
    //Hacemos un query que me muestre los numeros de telefono del cliente y cuantas incidencias tiene cada uno
    $query = "SELECT COUNT(DISTINCT DATE(fechaIncidencia)) as incidencias 
    FROM callcenter_incidenciaspedido 
    WHERE id_cliente = ".$_POST['idCliente']." AND telefono = '".$telefono."' 
    AND id_incidencia IN (3, 6)";
    if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), mysqli_error($conexion_bd));
    $row = mysqli_fetch_array($result);
    //Si el telefono tiene mas de 3 incidencias
    if($row['incidencias'] > 3){
        //Vamos a verificar si el telefono ha sido eliminado antes
        $query = "SELECT fechaEliminacion FROM callcenter_telefonoeliminadocliente WHERE id_cliente = ".$_POST['idCliente']." AND telefonoCliente = '".$telefono."'";
        if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), mysqli_error($conexion_bd));
        //Si no ha sido eliminado antes
        if(mysqli_num_rows($result) < 1){
            //Lo eliminamos
            $query = "INSERT INTO callcenter_telefonoeliminadocliente (id_cliente, id_usuario,telefonoCliente) VALUES (".$_POST['idCliente'].", ".$_SESSION['id_usr'].", '".$telefono."')";
            if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), mysqli_error($conexion_bd));

            //Eliminamos el telefono del cliente, vamos a buscar cual es el telefono(tel1,tel2,tel3) que vamos a eliminar
            $query = "UPDATE cliente SET ";
            if($row_telefonosCliente['tel1'] == $telefono){
                $query .= "tel1 = ''";
            }else if($row_telefonosCliente['tel2'] == $telefono){
                $query .= "tel2 = ''";
            }else if($row_telefonosCliente['tel3'] == $telefono){
                $query .= "tel3 = ''";
            }
            $query .= " WHERE id = ".$_POST['idCliente'];
            if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), mysqli_error($conexion_bd));

            //Eliminamos el telefono preferente por defecto
            $query = "UPDATE cliente SET telpreferente = '' WHERE id = ".$_POST['idCliente'];
            if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), mysqli_error($conexion_bd));

            //Agregamos un mensaje adicional explicando que el telefono ha sido eliminado
            $mensajeAdicional .= '<p><hr><br>El telefono '.$telefono.' ha sido eliminado del cliente por tener mas de 3 incidencias';


        }else{
            //Si ya ha sido eliminado antes contamos cuantas veces ha levantado incidencias desde la ultima eliminacion
            $row = mysqli_fetch_array($result);
            $fechaEliminacion = $row['fechaEliminacion'];
            $query = "SELECT COUNT(DISTINCT DATE(fechaIncidencia)) as incidencias
            FROM callcenter_incidenciaspedido 
            WHERE id_cliente = ".$_POST['idCliente']." AND telefono= '".$telefono."'
            AND id_incidencia IN (3, 6) AND fechaIncidencia > '".$fechaEliminacion."'";
            if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), mysqli_error($conexion_bd));
            $row_incidenciasActuales = mysqli_fetch_array($result);
            //Si ha levantado mas de 3 incidencias desde la ultima eliminacion
            if($row_incidenciasActuales['incidencias'] > 3){
                //Lo eliminamos
                $query = "INSERT INTO callcenter_telefonoeliminadocliente (id_cliente, id_usuario,telefonoCliente) VALUES (".$_POST['idCliente'].", ".$_SESSION['id_usr'].", '".$telefono."')";
                if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), mysqli_error($conexion_bd));

                //Eliminamos el telefono del cliente, vamos a buscar cual es el telefono(tel1,tel2,tel3) que vamos a eliminar
                $query = "UPDATE cliente SET ";
                if($row_telefonosCliente['tel1'] == $telefono){
                    $query .= "tel1 = ''";
                }else if($row_telefonosCliente['tel2'] == $telefono){
                    $query .= "tel2 = ''";
                }else if($row_telefonosCliente['tel3'] == $telefono){
                    $query .= "tel3 = ''";
                }
                $query .= " WHERE id = ".$_POST['idCliente'];
                if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), mysqli_error($conexion_bd));

                //Eliminamos el telefono preferente por defecto
                $query = "UPDATE cliente SET telpreferente = '' WHERE id = ".$_POST['idCliente'];
                if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), mysqli_error($conexion_bd));

                //Agregamos un mensaje adicional explicando que el telefono ha sido eliminado
                $mensajeAdicional .= '<p><hr><br>El telefono '.$telefono.' ha sido eliminado del cliente por tener mas de 3 incidencias';
            }
        }
    }
}
$_SESSION['mensaje'] = '
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <strong>Hecho!</strong> Se ha levantado una incidencia al cliente.
        <strong>'.$mensajeAdicional.'</strong>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>';

//Almacenamos en la session la ruta que seleccionamos
$_SESSION['workRute_CC'] = $_POST['idRuta'];
//Redireccionamos a la pagina de mis rutas
header("Location: misRutas.php");
die();