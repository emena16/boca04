<?
$ruta = "../../../";
include $ruta."sys/precarga_mysqli.php";
//include $ruta."sys/fcn/fcnSelects.php";

$permitidos = array(1, 8, 9, 10, 13);
$acceso = validaAccesoAutorizado($permitidos);

if($acceso == 0)
	redirect("", "");
$response = array(
    "status" => 0,
    "msg" => "Error al consultar la información",
    "productos" => array()

);

//Vamos a consultar el contenido de un paquete promocional
$query = "SELECT promo.id AS promo_id, sub.id AS sub_id,pcc.id_contenedor,
paq.comercial as pqt , pcc.id_contenido, pcc.cant_prod_med, sub.comercial as pza
from promocion promo 
join prod_medida paqpm on promo.id_prod = paqpm.id
join producto paq on paqpm.id_prod = paq.id
join prod_comp_contenido pcc on promo.id_prod = pcc.id_contenedor
join prod_medida subpm on pcc.id_contenido =subpm.id
join producto sub on subpm.id_prod = sub.id
where paq.id = {$_POST['idProducto']} and promo.id = {$_POST['idPromo']}
ORDER BY `promo_id` ASC";

//Agregamos los productos a la respuesta
if(!$resultIndividuales = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);

while($row = mysqli_fetch_assoc($resultIndividuales)){
    $response['productos'][] = $row;
}
//enviamos la respuesta
$response['status'] = 1;
$response['msg'] = "Información consultada correctamente";
$response['query'] = $query;
echo json_encode($response);



?>