<?
$ruta = "../../../";
$rutaArchivo = file_exists($ruta."sys/precarga_mysqli.php") ? $ruta."sys/precarga_mysqli.php" : "../../../sys/precarga_mysqli.php";
include $rutaArchivo;

$permitidos = array(1,9,11,12,13); // SA y GC (correcto)
$acceso = validaAccesoAutorizado($permitidos);

if($acceso == 0)
    redirect("", "");

include $ruta."sys/hf/header_v3.php";
include $ruta."sys/hf/banner_v3.php";
include $ruta."mtto/mods/menuGral_v4.php";

// Incluimos los modelos necesarios para el costeo de la compra de productos
// require 'models/Compra.php';
// require 'models/CompraFactura.php';
// require 'models/ProdCompra.php';
// require 'models/GmcvDescuento.php';
// require 'models/GmcvDescuentoBodega.php';
// require 'models/GmcvDescuentoProducto.php';
?>
<!-- Importamos los estilos necesarios para la vista -->
<link rel="stylesheet" href="css/estilos.css">

<style>
.dt-right {
    text-align: right;
}

table.dataTable tbody th,
table.dataTable tbody td {
    padding: 3px 3px;
}

/*th, td { white-space: nowrap; }
    div.dataTables_wrapper {
        width: 900px;
        margin: 0 auto;
    }*/

/*tr { height: 50px; }*/
.tablaPequena {
    font-size: small;
}

table.tablaPequena tbody th,
table.tablaPequena tbody td {
    padding: 3px 3px;
}

.feather-16{
    width: 16px;
    height: 16px;
}
#tablaProductosFacturaAlmacen + #tablaAlineadaDerecha {
    float: right;
    margin-left: 10px;
}

/* Cambiamos el color de texto de un input que esta desabilitado */
.disabled-input {
    color: black;
    /*El contorno del input se ve igual que si estuviera habilitado*/
    border: 1px solid #ccc;
    /*Ajustamo el fondo del input para que se vea como si estuviera habilitado*/
    background-color: #f8f9fa;
    /*Ajustamos el texto para que se vea como si estuviera habilitado*/
    opacity: 1;
}
</style>


<div class="page-header layout-top-spacing title-header">
    <div class="pge-title" style="margin-left: 3.5%;">
        <h3>&nbsp; BLANK</h3>
    </div>
</div>


<div style="padding-left: 80px; padding-right: 10px;" >
    <div class="container-fluid">
        <div class="page-title" style="float: none;">
            <h3>Tittle</h3>
        </div>
        <!-- Aqui vamos a pintar todo lo que se requiera en la vista -->
        <div class="statbox widget box box-shadow widget-content-area p-3  mt-3">
            
        </div>
    </div>
</div>

<div class="card card-principal">
    <div class="card-body">

        

        <div class="row">
            <div class="col-md-4">
                <a href="validaCostos.php"><button class="mt-lg-4 btn btn-info btn-lg"><i style=" color: #f6fcfb;" data-feather="search"></i> Consulta Avanzada </button></a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12"><div class="page-header layout-top-spacing title-header mt-lg-4">
                <div class="pge-title"> <br><br><br>
                    <h5 id="tituloTabla"></h5>
                </div>
            </div></div>
        </div>
        <div class="row">
            
            <div id="divTablaEntradas" class="col-md-12 col-lg-12">
                <!-- Aqui vamos a pintar el datatable con las ordenes de compra -->
            </div>
        </div>


    </div> <!-- fin card-body -->
</div> <!-- fin card-principal -->


<?php


$rutaArchivo = file_exists($ruta."sys/hf/pie_v3.php") ? $ruta."sys/hf/pie_v3.php" : "../../../sys/hf/pie_v3.php";
include $rutaArchivo;


?>