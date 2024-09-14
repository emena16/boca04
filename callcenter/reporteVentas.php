<?
$ruta = "../../../";
include $ruta."sys/precarga_mysqli.php";
// include $ruta."sys/ajaxGetUnidadesOperativasRutas.php";

$permitidos = array(1,9,11,12,13); // SA y GC (correcto)
$acceso = validaAccesoAutorizado($permitidos);

if($acceso == 0)
    redirect("", "");

include $ruta."sys/hf/header_v3.php";
include $ruta."sys/hf/banner_v3.php";
include $ruta."mtto/mods/menuGral_mysqli.php";

function formatearTelefono($numero) {
    if (strlen($numero) != 10) {
        return $numero;
    }
    return substr($numero, 0, 3) . '-' . substr($numero, 3, 3) . '-' . substr($numero, 6);
}

?>
<link rel="stylesheet" type="text/css" href="css/estilos.css">
<!-- estilos para el select2 -->
<link rel="stylesheet" type="text/css" href="../../../sys/bocampana_vista/plugins/select2/select2.min.css">
<link rel="stylesheet" type="text/css" href="../../../sys/bocampana_vista/plugins/select2/es.js">

<style>
    .dataTables_paginate {
        display: none;
    }
  table.dataTable tbody th, table.dataTable tbody td {
    padding: 2px 2px;
    }
   /*th, td { white-space: nowrap; }
    div.dataTables_wrapper {
        width: 900px;
        margin: 0 auto;
    }*/
 
    /*tr { height: 50px; }*/
    /* Estilos personalizados para opciones de Select2 */
    .select2-results__option {
        font-size: 12px; /* Tamaño de la fuente */
        padding: 4px 8px; /* Espaciado interno de la opción */
    }
    /*Ajustamos el tamaño de choise para que no se vea tan grande*/
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        font-size: 12px;
        padding: 2px 4px;
    }
    /* .select2-container--default .select2-results__option {
        background-color: #f1f2f3;
    }
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #f1f2f3;
    } */
    .select2-selection__rendered {
        background-color: #f1f2f3 !important;
    }

</style>
    <div class="card-principal" style="">
        <!-- Aquí va el contenido de tu tarjeta -->
        
        <div class="row ml-4">
            <div class="page-title">
            <h5 class="mt-3 mb-4">Ventas de CallCenter </h5>
            </div>
        </div>

        
        <div class="card-body">
            <!-- <div class="row mt-3">
                <div class="col-sm-12">
                    <div class="page-title">
                        <h5 class="mt-3 mb-4">Ventas de CallCenter </h5>
                    </div>                    
                </div>
            </div> -->
            <div class="row justify-content-center">
                <div class="col-sm-6">
                    <div class="alertas alert text-center" style="display:none;"></div>
                </div>
            </div>
            <!-- Aqui ira el multi selector y los campos de busqueda -->
            <div class="row mb-3">
                <div class="col-sm-7">
                    <div class="row">
                        <div class="col-sm-6">
                            <?php
                                // Obtenemos las bodegas activas 
                                $query = "SELECT id, nombre FROM bodega WHERE id_status = 4 ORDER BY nombre ASC";
                                if(!$resultBodegas = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
                            ?>
                            <div class="form-group" id="divBodega">
                                <label for="bodega">Bodega</label>
                                <select class="form-control" id="selectBodega" name="selectBodega">
                                    <option selected disabled value="">Seleccione una bodega</option>
                                    <?php
                                        while($row = mysqli_fetch_array($resultBodegas)){
                                            echo "<option value='".$row['id']."'>".$row['nombre']."</option>";
                                        }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group" id="divUnidadOperativa">
                                <label for="unidadOperativa">Unidad Operativa</label>
                                <select id="selectUOperativa" multiple="multiple">
                                    <!-- <option value="">Selecciona una unidad operativa</option> -->
                                </select>
                            </div> 
                        </div>
                    </div> 
                </div>
                <div class="col-sm-5 align-content-end">
                    <!-- Aqui ira el resto de los campos de busqueda -->
                    <?php

                    $query = "SELECT DISTINCT usr.*, CONCAT(usr.nombres, ' ', usr.apat, ' ', usr.amat) AS nombreCompleto from usr
                    INNER JOIN rh_relacion_usr ON usr.id = rh_relacion_usr.id_usr
                    INNER JOIN rh_personal ON rh_personal.id = rh_relacion_usr.id_personal
                    INNER JOIN rh_puesto ON rh_puesto.id = rh_personal.id_puesto
                    WHERE rh_personal.id_status = 4 AND rh_puesto.id in (20,21,2)";
                    //Obtenemos los usuarios que son preventistas que han vendido a traves del modulo de callcenter
                    if(!$resultUsuarios = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);


                    ?>
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="form-group mb-3">
                                <label for="fechaInicio">Vendedor:</label>
                                <select class="form-control" id="selectVendedor" name="selectVendedor">
                                    <option selected disabled value="">Seleccione un vendedor</option>
                                    <?php
                                        while($row = mysqli_fetch_array($resultUsuarios)){
                                            echo "<option value='".$row['id']."'>".$row['nombreCompleto']."</option>";
                                        }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group mb-3">
                                <label for="fechaInicio">Fecha Inicio</label>
                                <input type="date" class="form-control form-control-sm" id="fechaInicio" name="fechaInicio" value="<?php echo date('Y-m-d', strtotime('-7 days')); ?>">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group mb-3">
                                <label for="fechaFin">Fecha Fin</label>
                                <input type="date" class="form-control form-control-sm" id="fechaFin" name="fechaFin" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="form-group" id="divAgrupador">
                                <label for="agrupador">Agrupador</label>
                                <select class="form-control" id="selectAgrupado" name="selectAgrupador">
                                    <option value="1">Grupo</option>
                                    <option value="2">Agrupador Secundario</option>
                                    <option value="3">Proveedor</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- en este div vamos a mostrar los botones de accion -->
            <div class="row">
                <div class="col-sm-12 text-center">
                    <div class="form-group" id="divBotones">
                        <button type="button" class="btn btn-primary ml-1" id="btnMostrar">Mostrar</button>
                        <button type="button" class="btn btn-secondary ml-1" id="btnLimpiar">Limpiar</button>
                        <button type="button" class="btn btn-success ml-1" id="btnExportar">Generar Reporte</button>
                    </div>
                </div>
            </div>

            <!-- Aqui ira los resultados de la busqueda en forma de tabla -->
            <div class="row mt-3">
                <div id="resultados" class="col-sm-12">
                    
                </div> <!-- fin de la columna -->
            </div> <!-- fin de la fila de la busqueda de resultado -->


        </div> <!-- Aquí termina card-body -->
    </div> <!-- Aquí termina card-principal -->
<!-- </div> div del container comentado -->

<?
include $ruta."sys/hf/pie_v3.php";
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Script para el select2 -->
<script src="../../../sys/bocampana_vista/plugins/select2/select2.min.js"></script>
<script src="../../../sys/bocampana_vista/plugins/select2/es.js"></script>

<script>
$(document).ready(function() {
    // Agregamos el select2 a los selects
    $('#selectBodega').select2({
      language: 'es',
      multiple: false,
      closeOnSelect: true,
      search: true,
      placeholder: "Seleccione una bodega" 
    });

    $('#selectUOperativa').select2({
      language: 'es',
      multiple: true,
      closeOnSelect: false,
      search: true,
      placeholder: "Seleccione una unidad operativa" 
    });

    $('#selectVendedor').select2({
      language: 'es',
      multiple: false,
      closeOnSelect: true,
      search: true,
      placeholder: "Seleccione un vendedor" // Placeholder
    });


    

});

//Creamos un evento para el boton limpiar
$('#btnLimpiar').on('click', function(){
    //Limpiamos los campos de busqueda
    $('#selectBodega').val('').trigger('change');
    $('#selectUOperativa').val('').trigger('change');
    $('#selectVendedor').val('').trigger('change');
    // $('#fechaInicio').val('');
    // $('#fechaFin').val('');
    $('#selectAgrupador').val('1');
    //Limpiamos la tabla de resultados
    // $('#tablaResultados').empty();
});





$('#selectBodega').on('select2:select', function (e) {
    var data = e.params.data;
    // Obtenemos las U. Operativas de la bodega seleccionada
    $.ajax({
        type: "POST",
        url: "ajaxGetUnidadesOperativasRutas.php",
        data: {idBodega: data.id, accion: 'obtenerUOperativas'},
        success: function(response){
            //console.log(response);
            // Parseamos el JSON
            var uOperativas = JSON.parse(response);
            // Limpiamos el select
            $('#selectUOperativa').empty();
            // Si esta desabilitado lo habilitamos
            $('#selectUOperativa').prop('disabled', false);

            for(var i = 0; i < uOperativas.length; i++){
                $('#selectUOperativa').append('<option value="'+uOperativas[i].id+'">'+uOperativas[i].nombre+'</option>');
            }
        }
    });
});


function muestraMensaje(mensaje,status){
    // Mostramos el mensaje de alerta a través de la clase alertas
    if(status == 1){
        $('.alertas').removeClass('alert-danger');
        $('.alertas').addClass('alert-success');
    }else{
        $('.alertas').removeClass('alert-success');
        $('.alertas').addClass('alert-danger');
    }
    $('.alertas').html(mensaje);
    $('.alertas').show();
    // Después de 5 segundos ocultamos el mensaje
    setTimeout(function(){
        $('.alertas').hide();
    }, 10000);
    
}


</script>