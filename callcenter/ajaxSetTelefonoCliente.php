<?
$ruta = "../../../";
include $ruta."sys/precarga_mysqli.php";
//include $ruta."sys/fcn/fcnSelects.php";

$permitidos = array(1, 8, 9, 10, 13);
$acceso = validaAccesoAutorizado($permitidos);

if($acceso == 0)
	redirect("", "");

//Como ya conocemos el pedido del cliente, no es necesario verificar si ya tiene un pedido en la ruta, actualizamos el telefono del cliente directamente
$queryCallcenterPedidos = "UPDATE callcenter_pedidos SET telefonoContacto = '".$_POST['telefono']."' WHERE id_pedido = '".$_POST['idPedido']."'";
if(!$result = mysqli_query($conexion_bd,$queryCallcenterPedidos))errores(mysqli_errno($conexion_bd), 0);
//creamos el response
$response = array(
    'status' => 'ok',
    'verificado' => '1'
);
echo json_encode($response);

?>