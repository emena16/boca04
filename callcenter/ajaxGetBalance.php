<?
$ruta = "../../../";
include $ruta."sys/precarga_mysqli.php";
//include $ruta."sys/fcn/fcnSelects.php";

$permitidos = array(1, 8, 9, 10, 13);
$acceso = validaAccesoAutorizado($permitidos);

if($acceso == 0)
	redirect("", "");


//Creamos una funcion que calcule el total de productos en el pedido
function totalPeidido($idPedido){
    //Obtenemos los productos del pedido
    $query ="
    SELECT
        SUM(pdo_prod.precio * pdo_prod.cantidad) as total, SUM(pdo_prod.cantidad) as numProductos
    FROM
        pdo_prod
    JOIN prod_medida ON pdo_prod.id_prod = prod_medida.id
    JOIN producto ON prod_medida.id_prod = producto.id
    JOIN sys_medida ON prod_medida.id_medida = sys_medida.id
    WHERE pdo_prod.id_pdo = '".$idPedido."' AND pdo_prod.id_status = 4";
    if(!$result = mysqli_query($conexion_bd,$query)) errores(mysqli_errno($conexion_bd), 0);
    $row = mysqli_fetch_array($result);
    $respuesta = array(
        'total' => $row['total'],
        'numProductos' => $row['numProductos']
    );
    return $respuesta;
}



//Vamos a obtener estadisticas del modulo de callcenter
$fecha = date("Y-m-d");

//Obtenemos los pedidos que se han en un periodo de tiempo
$query = "SELECT * FROM callcenter_pedidos
INNER JOIN pedido on pedido.id = callcenter_pedidos.id_pedido
INNER JOIN usr ON usr.id = callcenter_pedidos.id_usuario
WHERE callcenter_pedidos.fechaAltaPedido BETWEEN '2024-02-01' AND '2024-03-31'";
if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);

$pedidos = array();
$aux = array();
//Vamos a recorrer cada pedido para obtener el total de compra de cada uno y el numero de productos
while($row = mysqli_fetch_assoc($result)){
    $idPedido = str_replace(" ", "", $row['id_pedido']); //Eliminamos los espacios en blanco
    $aux = totalPeidido($idPedido);
    $row['totalCompra'] = $aux['total'];
    $row['numProductos'] = $aux['numProductos'];
    array_push($pedidos, $row);
}


?>