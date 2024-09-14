<?
$ruta = "../../../";
include $ruta."sys/precarga_mysqli.php";
//include $ruta."sys/fcn/fcnSelects.php";

$permitidos = array(1, 8, 9, 10, 13);
$acceso = validaAccesoAutorizado($permitidos);

if($acceso == 0)
	redirect("", "");

$usuarios = array();

if($_GET['q'] == "    "){
    //Lista de usuarios
    $query = "SELECT DISTINCT usr.*, CONCAT(usr.nombres, ' ', usr.apat, ' ', usr.amat) AS nombreCompleto from usr
    INNER JOIN rh_relacion_usr ON usr.id = rh_relacion_usr.id_usr
    INNER JOIN rh_personal ON rh_personal.id = rh_relacion_usr.id_personal
    INNER JOIN rh_puesto ON rh_puesto.id = rh_personal.id_puesto
    WHERE rh_personal.id_status = 4 AND rh_puesto.id in (20,21,2)";
    if(!$resultUsuarios = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);

    while($rowUsuarios = mysqli_fetch_array($resultUsuarios)){
        // Agregamos cada usuario al array en forma de array
        array_push($usuarios, array('id' => $rowUsuarios['id'], 'text' => $rowUsuarios['nombreCompleto']));
    }
}else{
    //Lista de usuarios con filtro
    $query = "SELECT DISTINCT usr.*, CONCAT(usr.nombres, ' ', usr.apat, ' ', usr.amat) AS nombreCompleto 
    FROM usr
    INNER JOIN rh_relacion_usr ON usr.id = rh_relacion_usr.id_usr
    INNER JOIN rh_personal ON rh_personal.id = rh_relacion_usr.id_personal
    INNER JOIN rh_puesto ON rh_puesto.id = rh_personal.id_puesto
    WHERE rh_personal.id_status = 4 
    AND rh_puesto.id IN (20,21,2) 
    AND CONCAT(usr.nombres, ' ', usr.apat, ' ', usr.amat) LIKE '%".$_GET['q']."%'";
    if(!$resultUsuarios = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
    
    while($rowUsuarios = mysqli_fetch_array($resultUsuarios)){
        array_push($usuarios, array('id' => $rowUsuarios['id'], 'text' => $rowUsuarios['nombreCompleto']));
    }

}
// // Búsqueda
// $search = $_GET['q']; // Obtener el término de búsqueda
// $results = array_filter($usuarios, function ($usuario) use ($search) {
//     return stripos($usuario['text'], $search) !== false;
// });
// echo json_encode(array_values($results));
echo json_encode($usuarios);



?>