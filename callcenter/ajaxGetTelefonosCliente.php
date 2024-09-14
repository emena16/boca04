<?
$ruta = "../../../";
include $ruta."sys/precarga_mysqli.php";
//include $ruta."sys/fcn/fcnSelects.php";

$permitidos = array(1, 8, 9, 10, 13);
$acceso = validaAccesoAutorizado($permitidos);

if($acceso == 0)
	redirect("", "");


//Antes verificamos si el cliente ya tiene un pedido en la ruta y para ver si tiene un telefono registrado en el pedido
$queryCallcenterPedidos = "SELECT
    telefonoContacto
    FROM
    callcenter_pedidos
    WHERE
    id_cliente = ".$_POST['idCliente']." 
    AND id_ruta = ".$_POST['idRuta']." 
    AND DATE(fechaAltaPedido) = '".date('Y-m-d')."'
    AND telefonoContacto IS NOT NULL";
///Intentamos obtener el telefono del cliente y no enviamos el query para debug
if(!$result = mysqli_query($conexion_bd,$queryCallcenterPedidos))errores(mysqli_errno($conexion_bd), 0);
if(mysqli_num_rows($result) > 0){
    $row = mysqli_fetch_array($result);
    if($row['telefonoContacto'] != ''){
        $response = array(
            'telefonos' => array($row['telefonoContacto']),
            'verificado' => '1',
            'query' => $queryCallcenterPedidos
        );
        echo json_encode($response);
        die();
    }
}

//Obtenemos los telefonos del cliente 
$query = "SELECT tel1,tel2,tel3 FROM cliente WHERE id = ".$_POST['idCliente'];
if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
$row = mysqli_fetch_array($result);
$telefonos = array();
// Insertamos los telefonos en un array
if($row['tel1'] != '')array_push($telefonos, $row['tel1']);
if($row['tel2'] != '')array_push($telefonos, $row['tel2']);
if($row['tel3'] != '')array_push($telefonos, $row['tel3']);

$response = array(
    'telefonos' => $telefonos,
    'verificado' => '0',
    'query' => $query,
    'queryCallcenterPedidos' => $queryCallcenterPedidos
);

echo json_encode($response);

?>