<?
$ruta = "../../../";
include $ruta."sys/precarga_mysqli.php";
//include $ruta."sys/fcn/fcnSelects.php";

$permitidos = array(1, 8, 9, 10, 13);
$acceso = validaAccesoAutorizado($permitidos);

if($acceso == 0)
	redirect("", "");


// Vamos a insertar al usuario marcandolo como ya avisado
$response = array(
    "status" => 0,
    "msg" => "Error al intentar marcar al usuario como avisado"
);


//Consultamos el telefono que tinene el usuario asignado en su turno
$queryTelefono = "SELECT id_telefono FROM callcenter_telefonoasignado WHERE id_usuario = ".$_SESSION['id_usr']." AND DATE(fechaAsignacion) = CURDATE()";
//Intentamos ejecutar la consulta con un try catch
try {
    $result = mysqli_query($conexion_bd,$queryTelefono);
} catch (Exception $e) {
    $response['msg'] = "Error al intentar obtener el telefono asignado al usuario";
    echo json_encode($response);
    exit();
}

//Si no se obtuvo ningun resultado, entonces no hay telefono asignado
if(mysqli_num_rows($result) == 0){
    $response['msg'] = "No se encontro ningun telefono asignado al usuario";
    echo json_encode($response);
    exit();
}else{
    $telefono = mysqli_fetch_assoc($result);
    $telefono = $telefono['id_telefono'];
}

// Vamos a insertar al usuario marcandolo como ya avisado
$query = "INSERT INTO callcenter_mensajeaviso (id_cliente, id_telefono, fechaEnvio, telefonoCliente) VALUES (".$_POST['idCliente'].", ".$telefono.", NOW(), '".$_POST['idTelefono']."')";
//Ejecutamos la consulta
if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);

$response = array(
    "status" => 1,
    "msg" => "El usuario ha confirmado el envió del saludo al cliente"
);

echo json_encode($response);
die();



?>