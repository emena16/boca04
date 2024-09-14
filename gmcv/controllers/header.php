<?php
// Incluimos los modelos necesarios para el costeo de la compra de productos
foreach ($modelos as $modelo) {
    if(file_exists("../models/".$modelo.".php"))
        require_once '../models/'.$modelo.'.php';
    else
        require_once './models/'.$modelo.'.php';
}
?>