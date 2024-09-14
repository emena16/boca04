<?php
$ruta = "../../../";
include $ruta."sys/precarga_mysqli.php";

$permitidos = array(1, 8, 9, 10, 13);
$acceso = validaAccesoAutorizado($permitidos);

if($acceso == 0)
	redirect("", "");

if(isset($_POST['idRuta'])){

    $fechaHoy = date('Y-m-d');

    //Verificamos si el dia de hoy ya se ha cerrado la ruta en caso contrario la cerramos
    $query = "SELECT * FROM ruta_sincro WHERE id_ruta = ".$_POST['idRuta']." AND fecha = '".$fechaHoy."'";
    if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
    if(mysqli_num_rows($result) == 0){
        $query = "INSERT INTO ruta_sincro (id_ruta, fecha, id_estado) VALUES (".$_POST['idRuta'].", '".$fechaHoy."', 2)";
        if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
    }else{
        //Hacemos el updtae a la fila con status 2
        $query = "UPDATE ruta_sincro SET id_estado = 2 WHERE id_ruta = ".$_POST['idRuta']." AND fecha = '".$fechaHoy."'";
        if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
    }

    $_SESSION['mensaje'] = '
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Hecho!</strong> La ruta se ha cerrado correctamente.
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>';

}else{
    $_SESSION['mensaje'] = '
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error!</strong> Hubo un error al cerrar la ruta, por favor intente de nuevo.
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>';
}
//Almacenamos en la session la ruta que seleccionamos
$_SESSION['workRute_CC'] = $_POST['idRuta'];
//Redireccionamos a la pagina de mis rutas
header("Location: misRutas.php");
die();

?>