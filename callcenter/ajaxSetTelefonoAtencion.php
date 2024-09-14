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


//Insertamos el telefono en la tabla de telefonos asignados 
// $query = "INSERT INTO callcenter_telefonoasignado (id_telefono, id_asignacionruta) VALUES (".$_POST['idTelefono'].", ".$_POST['idAsignacion'].")";
// if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);

//Insertamos el telefono en la tabla de telefonos asignados
$query = "INSERT INTO callcenter_telefonoasignado (id_telefono, id_usuario) VALUES (".$_POST['idTelefono'].", $_SESSION[id_usr])";
if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);

//Obtnemos el telefono asignado para mostrarlo en el campo de texto
$queryCallcenterTelefonos = "SELECT numero FROM callcenter_telefonos WHERE id = ".$_POST['idTelefono'];
if(!$resultCallcenterTelefonos = mysqli_query($conexion_bd,$queryCallcenterTelefonos))errores(mysqli_errno($conexion_bd), 0);
$rowCallcenterTelefonos = mysqli_fetch_assoc($resultCallcenterTelefonos);

//creamos el response
$response = array(
    'status' => 'ok',
    'verificado' => '1',
    'telefono' => formatearTelefono($rowCallcenterTelefonos['numero'])
);
echo json_encode($response);

?>