<?php
$ruta = "../../../";
include $ruta."sys/precarga_mysqli.php";

$permitidos = array(1, 8, 9, 10, 13);
$acceso = validaAccesoAutorizado($permitidos);

if($acceso == 0)
	redirect("", "");


$idPedido = $_POST['idPedido'] == NULL ? 'NULL' : "'".str_replace(' ','',$_POST['idPedido'])."'";
//Verificamos si el pedido tiene al menos un producto
$query = "SELECT * FROM pdo_prod WHERE id_pdo =".$idPedido;
if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
if(mysqli_num_rows($result)<1){
    $_SESSION['mensaje'] = '
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error!</strong> No se puedo generar el pedido, no tiene productos.
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>';
    //Redireccionamos a la pagina de mis rutas
    header("Location: misRutas.php");
    die();
}
//Vamos a pasar el estado del pedido a 4 (Vigente, listo para despachar)
$query = "UPDATE pedido SET id_status = 4 WHERE id = ".$idPedido;
if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
//Actualizamo la fecha en la que se termino el pedido en la tabla de callcenter_pedidos
$query = "UPDATE callcenter_pedidos SET fechaTermino = '".date('Y-m-d H:i:s')."' WHERE id_pedido = ".$idPedido;
if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);

$_SESSION['mensaje'] = '
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>Hecho!</strong> Se ha generado el pedido del cliente: <strong>'.$_POST['cliente'].'</strong> de la ruta: <strong>'.$_POST['ruta'].'</strong>.
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>';
//Redireccionamos a la pagina de mis rutas
header("Location: misRutas.php");
die();