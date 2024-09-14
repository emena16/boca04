<?
$ruta = "../../../";
include $ruta."sys/precarga_mysqli.php";
//include $ruta."sys/fcn/fcnSelects.php";

$permitidos = array(1, 8, 9, 10, 13);
$acceso = validaAccesoAutorizado($permitidos);

if($acceso == 0)
	redirect("", "");


$response = array(
    'telefonos' => array(),
    'verificado' => '0',
    'disponibles' => 0,
    'query' => '',
    'queryCallcenterPedidos' => ''
);

function formatearTelefono($numero) {
    if (strlen($numero) != 10) {
        return $numero;
    }
    return substr($numero, 0, 3) . '-' . substr($numero, 3, 3) . '-' . substr($numero, 6);
}

//Consultamos las asignaciones del usuario logueado de hoy
$queryAsignaciones = "SELECT * FROM callcenter_telefonoasignado 
INNER JOIN callcenter_telefonos ON callcenter_telefonoasignado.id_telefono = callcenter_telefonos.id
WHERE id_usuario = $_SESSION[id_usr] AND DATE(fechaAsignacion) = CURDATE()";
if(!$result = mysqli_query($conexion_bd,$queryAsignaciones))errores(mysqli_errno($conexion_bd), 0);

//Si aun no tiene asignado un telefono, obtenemos los telefonos disponibles el dia de hoy haciendo interseccion entre los telefonos disponibles y los telefonos asignados
if(mysqli_num_rows($result) < 1){
    $query = "SELECT t.* FROM callcenter_telefonos t LEFT JOIN callcenter_telefonoasignado ta ON t.id = ta.id_telefono AND DATE(ta.fechaAsignacion) = CURDATE() WHERE ta.id_telefono IS NULL AND t.id_status = 4";
    if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
    //Si hay telefonos disponibles, los insertamos en el array de telefonos
    if(mysqli_num_rows($result) > 0){
        while($row = mysqli_fetch_array($result)){
            array_push($response['telefonos'], array('id' => $row['id'], 'telefono' => formatearTelefono($row['numero'])));
        }
        $response['disponibles'] = mysqli_num_rows($result);
    }else{
        //Si no hay telefonos disponibles, mandamos el false de verificado
        $response['disponibles'] = 0;
    }
    // Enviamos la respuesta al cliente
    $response['verificado'] = '0';
    $response['query'] = $query;
    $response['queryAsignaciones'] = $queryAsignaciones;
    // $response['query'] = $query;
    echo json_encode($response);
    die();
}else{
    // Si ya tiene un telefono asignado, obtenemos el telefono asignado y mandmos el true de verificado
    $row = mysqli_fetch_array($result);
    array_push($response['telefonos'], array('id' => $row['id'], 'telefono' => formatearTelefono($row['numero'])));
    $response['verificado'] = '1';
    $response['disponibles'] = 1;
    $response['queryAsignaciones'] = $queryAsignaciones;
    // Enviamos la respuesta al cliente
    echo json_encode($response);
    die();


}
?>