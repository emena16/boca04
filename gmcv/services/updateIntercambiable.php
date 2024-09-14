<?php
$ruta = "../../../../";
include $ruta."sys/precarga.php";

$permitidos = array(1, 8, 9, 10, 13);
$acceso = validaAccesoAutorizado($permitidos);

if($acceso == 0)
	redirect("", "");


echo json_encode($_POST);

die();