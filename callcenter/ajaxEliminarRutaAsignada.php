<?php
$ruta = "../../../";
include $ruta."sys/precarga_mysqli.php";
//include $ruta."sys/fcn/fcnSelects.php";

$permitidos = array(1, 8, 9, 10, 13);
$acceso = validaAccesoAutorizado($permitidos);

if($acceso == 0)
	redirect("", "");

//Vamos a eliminar la ruta asignada al usuario
$idUsuario = $_POST['idUsuario'];
$idRuta = $_POST['idRuta'];

//$query = "DELETE FROM callcenter_usuariosruta WHERE id_usuario = $idUsuario AND id_ruta = $idRuta";
//Actualizamos el campo a 0 para que no se muestre en la lista de rutas asignadas
$query = "UPDATE callcenter_usuariosruta SET estadoAsignacion = 0 WHERE id_usuario = $idUsuario AND id_ruta = $idRuta";
if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);

$hoy = date('Y-m-d');
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
$rutasAsignadas = array();
$response = array();
while($row = mysqli_fetch_assoc($resultRutasAsignadas)){
    array_push($response, $row);
}
echo json_encode($response);

?>