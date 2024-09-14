<?php
$ruta = "../../../";
include $ruta."sys/precarga_mysqli.php";
//include $ruta."sys/fcn/fcnSelects.php";

$permitidos = array(1, 8, 9, 10, 13);
$acceso = validaAccesoAutorizado($permitidos);

if($acceso == 0)
	redirect("", "");

//Generamos el formulario para los datos fiscales
$response = array();

$query = "SELECT rfc,rs,cp,email,id_regimen, id_usocfdi FROM cte_fact WHERE id_cte = ".$_POST['idCliente'];
if($result = mysqli_query($conexion_bd,$query)){
    $response = mysqli_fetch_array($result);
}else{
    $response['mesnaje'] = "No se encontraron datos fiscales";

}

$response['idCliente'] = $_POST['idCliente'];
$response['query']=$query;
echo json_encode($response);





?>