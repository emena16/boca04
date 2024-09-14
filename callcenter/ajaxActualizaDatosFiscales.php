<?php
$ruta = "../../../";
include $ruta."sys/precarga_mysqli.php";
//include $ruta."sys/fcn/fcnSelects.php";

$permitidos = array(1, 8, 9, 10, 13);
$acceso = validaAccesoAutorizado($permitidos);

if($acceso == 0)
	redirect("", "");


$response = array(
    "status" => "0"
);


// $jsonTest = '{"idCliente":"558363","idRuta":"744","idPedido":"2036-1709566439","idOficina":"66","rfc":"MEDE9402054H7","razonSocial":"jose eduardo mena delgado","cp":"74125","id_regimen":"26","id_usocfdi":"2"}';
// $_POST = json_decode($jsonTest, true);


//Verificamos si tenemos almacenados los datos fiscales del cliente
$query = "SELECT rfc,rs,cp,email,id_regimen, id_usocfdi FROM cte_fact 
INNER JOIN sys_sat_reg_fiscal ON sys_sat_reg_fiscal.id = cte_fact.id_regimen
INNER JOIN sys_uso_cfdi ON sys_uso_cfdi.id = cte_fact.id_usocfdi
WHERE cte_fact.id_cte = ".$_POST['idCliente'];

//Si tenemos datos fiscales almacenados solo actualizamos la fila en la base de datos en otro caso insertamos una nueva fila
if(mysqli_num_rows($result = mysqli_query($conexion_bd,$query) ) > 0){
    $query = "UPDATE cte_fact SET rfc = '".$_POST['rfc']."', rs = '".$_POST['razonSocial']."', cp = '".$_POST['cp']."', email = '".$_POST['email']."', id_regimen = ".$_POST['id_regimen'].", id_usocfdi = ".$_POST['id_usocfdi']." WHERE id_cte = ".$_POST['idCliente'];
    if(!$result = mysqli_query($conexion_bd,$query)){
        $response['status'] = "1";
        $response['mensaje'] = mysqli_errno($conexion_bd);
    }
    
}else{
    $fechaHoy = date("Y-m-d");
    $query = "INSERT INTO cte_fact (id_cte, rfc, rs, cp, email, id_regimen, id_usocfdi,alta,id_status) VALUES (".$_POST['idCliente'].", '".$_POST['rfc']."', '".$_POST['razonSocial']."', '".$_POST['cp']."', '".$_POST['email']."', ".$_POST['id_regimen'].", ".$_POST['id_usocfdi'].", '".$fechaHoy."',4)";
    if(!$result = mysqli_query($conexion_bd,$query)){
        $response['status'] = "1";
        $response['mensaje'] = mysqli_errno($conexion_bd);
    }
}

$response['idCliente'] = $_POST['idCliente'];
$response['query']=$query;
echo json_encode($response);





?>