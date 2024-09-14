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

//Actualizamos el estado del telefono que nos llega por POST
if(isset($_POST['idTelefono'])){
    $query = "UPDATE callcenter_telefonos 
SET id_status = CASE 
    WHEN id_status = 3 THEN 4 
    WHEN id_status = 4 THEN 3 
    ELSE id_status 
END 
WHERE id = ".$_POST['idTelefono'];
    if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
}


$response = array();

$query = "SELECT * FROM callcenter_telefonos";
if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
$aux = array();
while($row = mysqli_fetch_array($result)){
    //Utilizamos un array auxiliar para guardar los datos de cada fila
    $aux['id'] = $row['id'];
    $aux['numero'] = formatearTelefono($row['numero']);
    $aux['id_status'] = $row['id_status'];
    $aux['boton'] = $row['id_status'] == 4 ? "<td><button class='btn btn-success btn-sm btnCambiaEstadoTelefono' id='".$row['id']."'><span><i style=' color: #f6fcfb;' data-feather='check'></i></span>&emsp;Activo</button></td>" : "<td><button class='btn btn-danger btn-sm btnCambiaEstadoTelefono' id='".$row['id']."'><span><i style=' color: #f6fcfb;' data-feather='slash'></i></span>&emsp;Inactivo</button></td>";
    //Guardamos el array auxiliar en el array principal
    array_push($response, $aux);
    //Limipiamos el array auxiliar
    $aux = array();
}

echo json_encode($response);


?>