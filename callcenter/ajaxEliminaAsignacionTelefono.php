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
$response = array(
    'asignaciones' => array(),
    'error' => 1,
    'mensaje' => 'Error al eliminar la asignacion de telefono'
);

//Eliminar asignacion de telefono a traves del post que se recibe 
if(isset($_POST['idAsignacionTelefono']) && $_POST['idAsignacionTelefono'] != ""){
    //Eliminamos la asignacion de telefono
    $query = "DELETE FROM callcenter_telefonoasignado WHERE callcenter_telefonoasignado.id = ".$_POST['idAsignacionTelefono'];
    if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);

    //Si todo ok, regresamos la lista actualizada de asignaciones
    $query = "SELECT *, CONCAT(usr.nombres, ' ', usr.apat, ' ', usr.amat) AS nombre_completo, usr.id as idUsuario, callcenter_telefonoasignado.id as idAsignacionTelefono FROM callcenter_telefonoasignado INNER JOIN usr ON usr.id = callcenter_telefonoasignado.id_usuario INNER JOIN callcenter_telefonos ON callcenter_telefonos.id = callcenter_telefonoasignado.id_telefono WHERE DATE(fechaAsignacion) = CURDATE()";
    $result = mysqli_query($conexion_bd,$query);
    $aux = array();
    // Si hay asignaciones las regresamos
    if(mysqli_num_rows($result) > 0){
        while($row = mysqli_fetch_array($result)){
            $row['numero'] = formatearTelefono($row['numero']);
            $row['boton'] = "<button class='btn btn-danger btnEliminaAsignacionTelefono' id='".$row['idAsignacionTelefono']."'><span><i style=' color: #f6fcfb;' data-feather='trash'></i></span>&emsp;Eliminar Asignacion</button>";
            $aux[] = $row;
            $response['asignaciones'] = $aux;
            $aux = array();
        }
    }
    $response['error'] = 0;
    $response['mensaje'] = "Asignacion de teléfono eliminada correctamente";
    echo json_encode($response);
    die();

}else{
    $response['error'] = 1;
    echo json_encode($response);
    die();
}

?>