<?
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

/* Explico como diseñe el proceso de agregar productos al pedido
1.- Recibimos los valores que se van a agregar al pedido en formato de array, orginalmente 
recibia los valores en formato de array, ahora se decicio 1 a 1 por lo que este proceso se
aun es util, recibe un array de 1 elemento. 

**De momento idPromo no es un array de valores, pero se dejo la estructura para que
si en un futuro se decida agregar mas de una promocion al pedido, se pueda hacer sin problemas
simplemente cambiar el valor de idPromo a un array de valores de promociones***

*/


$_POST['idPedido'] = str_replace(' ','',$_POST['idPedido']);

$response = array(
    'mensaje' => '',
    'status' => 0
);

$querysExiste = array();
$querysUpdate = array();
$aux = array();
$productos = array();

$mensaje = array();
//Recorremos los valores que recibimos para irlos agregando al pedido
for($i=0; $i<sizeof($_POST['valores']); $i++){

    //Antes de agregar el producto al pedido, verificamos que no exista ya en el pedido, si existe, solo actualizamos la cantidad
    $queryExiste = "SELECT * FROM pdo_prod WHERE id_pdo = '".$_POST['idPedido']."' AND id_prod = ".$_POST['prodmedida'][$i]." AND id_status = 4";
    if(!$resultExiste = mysqli_query($conexion_bd,$queryExiste))errores(mysqli_errno($conexion_bd), 0);
    //Si el producto ya existe en el pedido, solo actualizamos la cantidad
    if(mysqli_num_rows($resultExiste) > 0){
        $query = "UPDATE pdo_prod SET cantidad = cantidad + ".$_POST['valores'][$i]." WHERE id_pdo = '".$_POST['idPedido']."' AND id_prod = ".$_POST['prodmedida'][$i]." AND id_status = 4";
        array_push($querysUpdate, $query);
        if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
        //Avisamos que el producto ya existia en el pedido y solo se actualizo la cantidad
        // array_push($mensaje, "Se actualizo la cantidad del producto <strong>".$_POST['productos'][$i][2]."</strong> en el pedido");
        array_push($mensaje, "Se actualizo la cantidad del producto en el pedido");
        $response['status'] = 1;
        continue;
    }
    //Si el producto no existe en el pedido, lo agregamos
    //Buscamos el precio del producto
    $queryPrecio = "SELECT prod_oficina.venta FROM prod_oficina WHERE id_prod = ".$_POST['prodmedida'][$i]." AND id_oficina = ".$_POST['idOficina'];
    if(!$resultPrecio = mysqli_query($conexion_bd,$queryPrecio))errores(mysqli_errno($conexion_bd), 0);
    $rowPrecio = mysqli_fetch_array($resultPrecio);

    //Agregamos el producto al pedido
    $precioProducto = str_replace(array('$',' '), '', $rowPrecio['venta']);
    array_push($productos,"('".$_POST['idPedido']."', ".$_POST['prodmedida'][$i].", ".$_POST['valores'][$i].", 4, ".$precioProducto.", ".$precioProducto.", '".date('Y-m-d')."',".$_POST['idPromo'].")");
}

//Si se agregaron productos al pedido, los agregamos a la base de datos
if(sizeof($productos) > 0){
    $query = "INSERT INTO pdo_prod (id_pdo, id_prod, cantidad, id_status, precio, precio_inicial, fecha_modificacion, id_promo) VALUES ";
    $query .= implode(',', $productos);
    if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
}

//Verificamos si hubo algun mensaje para enviarlo
if(sizeof($mensaje) > 0){
    $response['mensaje'] = implode('<br>', $mensaje);
}else{
    $response['mensaje'] = "Se agregaron los productos al pedido";
}

$productos = array();
$total = 0;
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
//Recorremos los productos para enviarlos al datatable
while($row = mysqli_fetch_array($result)){
    $aux['idProdMedida'] = $row['idProdMedida'];
    $input = '<input type="number" id="'.$row['idProdMedida'].'" class="inputCantidadPedido" style="width: 60px;" min="1" value="'.$row['cantidad'].'">'.$row['medida'];
    $aux['cantidad'] = $input;
    //$aux['cantidad'] = $row['cantidad'];
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

//Agregamos los productos al array de respuesta
$response['productos'] = $productos;
$response['total'] = "$ ".number_format($total, 2);

echo json_encode($response);
?>