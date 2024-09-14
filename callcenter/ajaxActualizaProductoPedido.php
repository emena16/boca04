<?php
$ruta = "../../../";
include $ruta."sys/precarga_mysqli.php";
//include $ruta."sys/fcn/fcnSelects.php";

$permitidos = array(1, 8, 9, 10, 13);
$acceso = validaAccesoAutorizado($permitidos);

if($acceso == 0)
	redirect("", "");

function limpiaCadena($cadena) {
    // Reemplazamos los acentos
    $sinAcentos = str_replace(
        ['á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú'],
        ['a', 'e', 'i', 'o', 'u', 'A', 'E', 'I', 'O', 'U'],
        $cadena
    );

    // Eliminamos las comas y los guiones
    $sinComasNiGuiones = str_replace(['-', ','], '', $sinAcentos);

    return $sinComasNiGuiones;
}


//Recibimos los productos que se van a actualizar
$idPedido = str_replace(' ','',$_POST['idPedido']);
$response = array(
    'mensaje' => '',
    'status' => 0
);
//Recorremos los valores que vamos a actualizar en el pedido
for($i=0; $i<sizeof($_POST['valores']); $i++){
    //Si recibimos un valor negativo, de momento lo saltamos
    if($_POST['valores'][$i] < 0)continue;

    //Actualizamos la cantidad del producto en el pedido
    $query = "UPDATE pdo_prod SET cantidad = ".$_POST['valores'][$i]." WHERE id_pdo = '$idPedido' AND id_prod = ".$_POST['ids'][$i];
    if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
}





//Obtenemos los productos del pedido
$query ="SELECT
    pdo_prod.id_prod,
    pdo_prod.cantidad,
    pdo_prod.precio,
    pdo_prod.precio_inicial,
    prod_medida.id as idProdMedida,
    producto.comercial as producto,
    sys_medida.nombre as medida
    FROM
    pdo_prod
    JOIN prod_medida ON pdo_prod.id_prod = prod_medida.id
    JOIN producto ON prod_medida.id_prod = producto.id
    JOIN sys_medida ON prod_medida.id_medida = sys_medida.id
    WHERE pdo_prod.id_pdo = '".$_POST['idPedido']."' AND pdo_prod.id_status = 4";

if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
$productos = array();
// Si aun hay productos en el pedido los mostramos
if(mysqli_num_rows($result) > 0){
    //Recorremos los productos para enviarlos al datatable
    while($row = mysqli_fetch_array($result)){
        $aux['idProdMedida'] = $row['idProdMedida'];
        $input = '<input type="number" id="'.$row['idProdMedida'].'" class="inputCantidadPedido" style="width: 60px;" min="1" value="'.$row['cantidad'].'">'.$row['medida'];
        $aux['cantidad'] = $input;
        // $aux['cantidad'] = $row['cantidad'];
        $aux['producto'] = $row['producto'];
        $aux['precio'] = "$ ".number_format($row['precio'], 2);
        $aux['subtotal'] = "$ ".number_format($row['precio'] * $row['cantidad'], 2);
        $aux['disponible'] = $row['medida'];
        $aux['acciones'] = '<div class="row">
        <div class="col-auto">
            <button type="button" id="'.$row['idProdMedida'].'" class="btn btn-danger mt-1 btnEliminarProducto"><span><i style=" color: #f6fcfb;" data-feather="trash"></i></span><span>Eliminar</span></button>
        </div>';
        //Creamos un campo que vamos a usar como contenido indexado para busqueda y ordenamiento
        $aux['busqueda'] = limpiaCadena($row['producto']." ".$row['precio']." ".$row['cantidad']);
        // Obtenida la información, la agregamos al array de respuesta
        array_push($productos, $aux);
        $total += $row['precio'] * $row['cantidad'];
        $aux = array();
    }
}
//Agregamos el total al array de respuesta
$response['total'] = "$ ".number_format($total, 2);
//Agregamos los productos al array de respuesta
$response['productos'] = $productos;

echo json_encode($response);




?>