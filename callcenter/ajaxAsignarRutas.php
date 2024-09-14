<?
$ruta = "../../../";
include $ruta."sys/precarga_mysqli.php";
//include $ruta."sys/fcn/fcnSelects.php";

$permitidos = array(1, 8, 9, 10, 13);
$acceso = validaAccesoAutorizado($permitidos);

if($acceso == 0)
	redirect("", "");


// Obtenemos las rutas que ya tiene asignadas el usuario
function getRutasUsuario($conexion_bd,$idUsuario){
    // Obtenemos las rutas que ya tiene asignadas el usuario
    $query = "SELECT id_ruta FROM callcenter_usuariosruta WHERE callcenter_usuariosruta.estadoAsignacion = 1 AND id_usuario = ".$idUsuario." and callcenter_usuariosruta.fechaFinal BETWEEN CURDATE() AND (SELECT MAX(fechaFinal) FROM callcenter_usuariosruta WHERE fechaFinal >= CURDATE())";
    if(!$resultRutasUsuario = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
    $rutasUsuario = array();
    while($row = mysqli_fetch_assoc($resultRutasUsuario)){
        array_push($rutasUsuario, $row['id_ruta']);
    }
    return $rutasUsuario;
}

function getRutasUOperativa($conexion_bd,$idUOperativa){
    // Obtenemos las rutas de la unidad operativa
    $query = "SELECT id FROM ruta WHERE id_status = 4 and id_oficina = $idUOperativa";
    if(!$resultRutasUOperativa = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
    $rutasUOperativa = array();
    while($row = mysqli_fetch_assoc($resultRutasUOperativa)){
        array_push($rutasUOperativa, $row['id']);
    }
    return $rutasUOperativa;
}

function getUnidesdesOpBodega($conexion_bd,$idBodega){
    // Obtenemos las unidades operativas de la bodega
    $query = "SELECT id_oficina FROM bod_oficina WHERE id_status = 4 and id_bodega = $idBodega";
    if(!$resultUOperativas = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
    $uOperativas = array();
    while($row = mysqli_fetch_assoc($resultUOperativas)){
        array_push($uOperativas, $row['id_oficina']);
    }
    return $uOperativas;
}

//Obtenemos los datos
$idUsuarios = $_POST['usuarios'];
$idBodegas = $_POST['bodegas'];
$idUOperativas = $_POST['uOperativas'];
$idRutas = $_POST['rutas'];
$fechaInicial = $_POST['fechaInicial'];
$fechaFinal = $_POST['fechaFinal'];

// Recorremos los usuarios para asignarles las rutas
foreach($idUsuarios as $idUsuario){
    // Obtenemos las rutas que ya tiene asignadas el usuario en funcion a un rango de fechas inicial y final
    $rutasUsuario = getRutasUsuario($conexion_bd,$idUsuario);
    // Recorremos las rutas para asignarlas al usuario si no las tiene asignadas
    foreach($idRutas as $idRuta){
        if(!in_array($idRuta, $rutasUsuario)){
            $query = "INSERT INTO callcenter_usuariosruta (id_usuario, id_ruta, fechaInicial, fechaFinal) VALUES ($idUsuario, $idRuta, '".$fechaInicial."', '".$fechaFinal."')";
            
        }else{
            //Si ya tiene la ruta asignada, solo actualizamos la fecha final de la asignación
            $query = "UPDATE callcenter_usuariosruta SET fechaFinal = '".$fechaFinal."' WHERE id_usuario = $idUsuario AND id_ruta = $idRuta";
        }
        // Ejecutamos la consulta de acuerdo a la condición
        if(!$resultInsert = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
    }

}

$response = array(
    'status' => 0,
    'mesnaje' => 'No se hizo ninguna asignación'
);


$hoy = date('Y-m-d');
$asignaciones = array();
// Consultamos la tabla de rutas asignadas para devolver la lista de rutas asignadas de cada usuario
$query = "SELECT
DATE_FORMAT(callcenter_usuariosruta.fechaAlta,'%d/%m/%y') AS fechaAlta, usr.id AS id_usuario, ruta.id AS id_ruta,
usr.nombres,usr.apat, usr.amat, ruta.nombre, bodega.nombre AS nombreBodega, oficina.nombre AS nombreUOperativa,
ruta.nombre AS nombreRuta, DATE_FORMAT(callcenter_usuariosruta.fechaInicial,'%Y/%m/%d') AS fechaInicial,
DATE_FORMAT(callcenter_usuariosruta.fechaFinal,'%Y/%m/%d') AS fechaFinal
FROM
    callcenter_usuariosruta
INNER JOIN usr ON usr.id = callcenter_usuariosruta.id_usuario
INNER JOIN ruta ON ruta.id = callcenter_usuariosruta.id_ruta
INNER JOIN oficina ON oficina.id = ruta.id_oficina
INNER JOIN bod_oficina ON bod_oficina.id_oficina = oficina.id
INNER JOIN bodega ON bodega.id = bod_oficina.id_bodega
WHERE
callcenter_usuariosruta.estadoAsignacion = 1 AND
callcenter_usuariosruta.fechaFinal BETWEEN CURDATE() AND (SELECT MAX(fechaFinal) FROM callcenter_usuariosruta WHERE fechaFinal >= CURDATE())
ORDER BY
    nombreBodega ASC,
    nombreUOperativa ASC,
    nombreRuta ASC";
if(!$resultRutasAsignadas = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
while($row = mysqli_fetch_assoc($resultRutasAsignadas)){
    array_push($asignaciones, $row);
}

$response['asignaciones'] = $asignaciones;

echo json_encode($response);