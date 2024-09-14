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
include $ruta."mtto/mods/menuGral_looknfeel_mysqli.php";
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




    <div class="card card-principal" style="">
        <!-- Aquí va el contenido de tu tarjeta -->
        
        <div class="row ml-4">
            <div class="page-title">
                <h3>Asignación de rutas a usuarios de Call Center</h3>
            </div>
        </div>

        <div class="row justify-content-center "> 
            <div class="col-md-auto">
                <?php  
                    if(isset($_SESSION['mensaje'])){
                        echo $_SESSION['mensaje'];
                        unset($_SESSION['mensaje']);
                    }
                ?>
            </div>
        </div>
        <?php
            // Obtenemos las bodegas activas 
            $query = "SELECT id, nombre FROM bodega WHERE id_status = 4 ORDER BY nombre ASC";
            if(!$resultBodegas = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
        ?>
        
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-success" id="alerta" role="alert" style="display: none;">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                        </button>
                        
                        <h4 id="tituloAlerta" class="alert-heading"></h4>
                        <p id="mensajeAlerta"></p>
                        
                    </div>
                </div>
            </div>
            <!-- Vamos a crear un SELECT2 de muestra para verificar que la librería funciona -->
            <div class="row mt-lg-4">
                <div class="col-md-3">
                    <label for="miSelect">Selecciona usuario(s):</label>
                    <select id="selectUsuarios" multiple="multiple">
                        <?php
                            // while($row = mysqli_fetch_array($resultUsuarios)){
                            //     echo "<option value='".$row['id']."'>".$row['nombres']." ".$row['apat']." ".$row['amat']."</option>";
                            // }
                        ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="miSelect">Selecciona una bodega:</label>
                    <select id="selectBodega" multiple="multiple">
                        <option selected disabled value="">Seleccione una bodega</option>
                        <?php
                            while($row = mysqli_fetch_array($resultBodegas)){
                                echo "<option value='".$row['id']."'>".$row['nombre']."</option>";
                            }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="miSelect">Selecciona una U. Operativa:</label>
                    <select id="selectUOperativa" multiple="multiple">
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="miSelect">Selecciona una ruta:</label>
                    <select id="selectRuta" multiple="multiple">
                    </select>
                </div>
            </div> <!-- Aquí termina el row de los selectores -->
            <div class="row mt-2 mb-4">
                <div class="col-md-4">
                    <label for="fechaInicial">Seleccione la fecha inicial:</label> 
                    <input type="date" class="form-control inputFecha" value="<?= date('Y-m-d'); ?>" style=" background-color: #f1f2f3 !important;" id="fechaInicial">
                </div>
                <div class="col-md-4">
                    <label for="fechaFinal">Seleccione la fecha final:</label>
                    <input type="date" class="form-control inputFecha" style=" background-color: #f1f2f3 !important;" id="fechaFinal">
                </div>
            </div>
            <div class="container mt-3">
                <div class="row mt-4 justify-content-md-center">
                    <div class="col-md-auto">
                        <button class="btn btn-primary btn-lg" id="btnAsignar">
                            <i style=" color: #f6fcfb;" data-feather="user-plus"></i> Asignar rutas a usuarios
                        </button>
                    </div>
                </div>
            </div> <!-- Aquí termina el container del botón -->
            <?php
            $hoy = date('Y-m-d');
            $query = "SELECT
            DATE_FORMAT(callcenter_usuariosruta.fechaAlta,'%d/%m/%y') AS fechaAlta, usr.id AS id_usuario, ruta.id AS id_ruta,
            usr.nombres,usr.apat, usr.amat, ruta.nombre, bodega.nombre AS nombreBodega, oficina.nombre AS nombreUOperativa,
            ruta.nombre AS nombreRuta, DATE_FORMAT(callcenter_usuariosruta.fechaInicial,'%Y/%m/%d') AS fechaInicial,
            DATE_FORMAT(callcenter_usuariosruta.fechaFinal,'%Y/%m/%d') AS fechaFinal
            FROM
                callcenter_usuariosruta
            INNER JOIN usr ON usr.id = callcenter_usuariosruta.id_usuario
            INNER JOIN ruta ON ruta.id = callcenter_usuariosruta.id_ruta
            INNER JOIN oficina ON oficina.id = ruta.id_oficina
            INNER JOIN bod_oficina ON bod_oficina.id_oficina = oficina.id
            INNER JOIN bodega ON bodega.id = bod_oficina.id_bodega
            WHERE
            callcenter_usuariosruta.estadoAsignacion = 1 AND
            callcenter_usuariosruta.fechaFinal BETWEEN CURDATE() AND (SELECT MAX(fechaFinal) FROM callcenter_usuariosruta WHERE fechaFinal >= CURDATE())
            ORDER BY
                nombreBodega ASC,
                nombreUOperativa ASC,
                nombreRuta ASC";
            if(!$resultRutasAsignadas = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
            ?>
            <hr>
            <div>
                <h5 class="mt-4 mb-2">Rutas asignadas vigentes</h5>
            </div>
            <div class="row mt-4">
                <div class="col-md-12">
                    <table id="rutasAsignadasTable" class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Vigencia</th>
                                <th>Usuario</th>
                                <th>bodega</th>
                                <th>U. Operativa</th>
                                <th>Ruta</th>
                                <th>Eliminar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Aquí se van a mostrar los datos de las rutas asignadas a los usuarios -->
                            <?php
                            while($row = mysqli_fetch_array($resultRutasAsignadas)){
                                echo "<tr>";
                                    echo "<td>".$row['fechaInicial']." - ".$row['fechaFinal']."</td>";
                                    echo "<td>".$row['nombres']." ".$row['apat']." ".$row['amat']."</td>";
                                    echo "<td>".$row['nombreBodega']."</td>";
                                    echo "<td>".$row['nombreUOperativa']."</td>";
                                    echo "<td>".$row['nombreRuta']."</td>";
                                    echo "<td><button id='". $row['id_ruta'] ."' usr='". $row['id_usuario'] ."' class='btn btn-danger btn-sm btnEliminarRuta'>Eliminar</button></td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Vigencia</th>
                                <th>Usuario</th>
                                <th>bodega</th>
                                <th>U. Operativa</th>
                                <th>Ruta</th>
                                <th>Eliminar</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

        </div> <!-- Aquí termina card-body -->
    </div> <!-- Aquí termina card-principal -->
<!-- </div> div del container comentado -->

<?
include $ruta."sys/hf/pie_v3.php";
?>
<!-- Script para el select2 -->
<script src="../../../sys/bocampana_vista/plugins/select2/select2.min.js"></script>
<script src="../../../sys/bocampana_vista/plugins/select2/es.js"></script>

<script>
$(document).ready(function() {

    //Creamos un evento que oculte la classe alert despues de 10 segundos en caso de encontrarlo
    setTimeout(function() {
        $(".alert").fadeOut(3500);
    },10000);

    //Select de usuarios
    $('#selectUsuarios').select2({
        language: 'es',
        multiple: true,
        closeOnSelect: false,
        search: true,
        placeholder: 'Selecciona usuario(s)',
        ajax: {
            url: 'ajaxGetUsuarios.php',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term // Term es el valor ingresado en el campo de búsqueda
                };
            },
            processResults: function(data) {
                console.log(JSON.stringify(data));
                return {
                    results: data
                };
            },
            cache: true
        },
        minimumInputLength: 1 // Número mínimo de caracteres para comenzar la búsqueda
    });




    //Select de bodegas
    $('#selectBodega').select2({
      language: 'es',
      multiple: false,
      closeOnSelect: true,
      search: true,
      placeholder: "Seleccione una bodega" // Placeholder
    });
    //U. Operativa
    $('#selectUOperativa').select2({
        language: 'es',
      multiple: false,
      closeOnSelect: true,
      search: true,
      placeholder: "Seleccione una U. Operativa" // Placeholder
    });
    //Rutas
    $('#selectRuta').select2({
        language: 'es',
      multiple: true,
      closeOnSelect: false,
      search: true,
      placeholder: "Seleccione una ruta" // Placeholder
    });

    // Obtener los valores seleccionados
    $('#selectBodega').on('change', function() {
        var valoresSeleccionados = $(this).val();
        //console.log(valoresSeleccionados);
        // Si se selecciona mas de una bodega se entiende que se asignaran todas las rutas de las bodegas seleccionadas a los usuarios seleccionados y mostraremos un mensaje explicando esto
        // en caso de que solo se seleccione una bodega se despliegan las U. Operativas de esa bodega 
        // if(valoresSeleccionados.length > 1){
        //     // Mostramos mensaje
        //     $('#alerta').show();
        //     $('#tituloAlerta').text('Atención');
        //     $('#mensajeAlerta').text('Se asignarán todas las rutas de las bodegas seleccionadas a los usuarios seleccionados');
        //     //Desabilitamos el selector de U. Operativa
        //     $('#selectUOperativa').prop('disabled', true);
        // }else{
        //     //Retiramos el mensaje en caso de que se haya mostrado
        //     $('#alerta').hide();
        //     //Habilitamos el selector de U. Operativa
        //     $('#selectUOperativa').prop('disabled', false);

        // }

        //Limipiamos el select de U. Operativa y Rutas
        $('#selectUOperativa').empty();
        $('#selectRuta').empty();
    });
    // Si hay seleccionada una sola bodega mostramos las U. Operativas de esa bodega
    $('#selectBodega').on('select2:select', function (e) {
        var data = e.params.data;
        //console.log(data);
        // Mostramos los selectores de U. Operativa y Rutas
        $('#selectUOperativa').show();
        $('#selectRuta').show();
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
                // Agregamos las opciones al select
                $('#selectUOperativa').append('<option value="" selected disabled>Seleccione</option>');
                for(var i = 0; i < uOperativas.length; i++){
                    $('#selectUOperativa').append('<option value="'+uOperativas[i].id+'">'+uOperativas[i].nombre+'</option>');
                }
            }
        });
    });

    // Si hay seleccionada una sola U. Operativa mostramos las rutas de esa U. Operativa en el caso de haber seleccionado mas de una U. Operativa se entiende que se asignaran todas las rutas de las U. Operativas seleccionadas a los usuarios seleccionados y mostraremos un mensaje explicando esto
    $('#selectUOperativa').on('change', function() {
        var valoresSeleccionados = $(this).val();
        //console.log(valoresSeleccionados);
        // Si se selecciona mas de una U. Operativa se entiende que se asignaran todas las rutas de las U. Operativas seleccionadas a los usuarios seleccionados y mostraremos un mensaje explicando esto
        // en caso de que solo se seleccione una U. Operativa se despliegan las rutas de esa U. Operativa 
        // if(valoresSeleccionados.length > 1){
        //     // Mostramos mensaje
        //     $('#alerta').show();
        //     $('#tituloAlerta').text('Atención');
        //     $('#mensajeAlerta').text('Se asignarán todas las rutas de las U. Operativas seleccionadas a los usuarios seleccionados');
        //     //Desabilitamos el selector de Rutas
        //     $('#selectRuta').prop('disabled', true);
        // }else{
        //     //Retiramos el mensaje en caso de que se haya mostrado
        //     $('#alerta').hide();
        //     //Habilitamos el selector de Rutas
        //     $('#selectRuta').prop('disabled', false);
        // }
        //Limpiamos el select de Rutas
        //$('#selectRuta').empty();
        
    });

    // Si hay seleccionada una sola U. Operativa mostramos las rutas de esa U. Operativa
    $('#selectUOperativa').on('select2:select', function (e) {
        var data = e.params.data;
        //console.log(data);
        // Mostramos el selector de Rutas
        $('#selectRuta').show();
        // Obtenemos las rutas de la U. Operativa seleccionada
        $.ajax({
            type: "POST",
            url: "ajaxGetUnidadesOperativasRutas.php",
            data: {idUOperativa: data.id, accion: 'obtenerRutas'},
            success: function(response){
                //console.log(response);
                // Parseamos el JSON
                var rutas = JSON.parse(response);
                // Limpiamos el select
                $('#selectRuta').empty();
                // Agregamos las opciones al select
                for(var i = 0; i < rutas.length; i++){
                    $('#selectRuta').append('<option value="'+rutas[i].id+'">'+rutas[i].nombre+'</option>');
                }
                //Desabilitamos el selector de Rutas
                $('#selectRuta').prop('disabled', false);
            }
        });
    });

    // Botón de asignar rutas, aquí se hace la asignación de rutas a los usuarios seleccionados
    $('#btnAsignar').on('click', function(){
        // Obtenemos los valores seleccionados
        var usuariosSeleccionados = $('#selectUsuarios').val();
        var bodegasSeleccionadas = $('#selectBodega').val();
        var uOperativasSeleccionadas = $('#selectUOperativa').val();
        var rutasSeleccionadas = $('#selectRuta').val();

        // Si no se selecciona ningun usuario mostramos un mensaje de error
        if(usuariosSeleccionados.length == 0){
            $('#alerta').addClass('alert-danger').removeClass('alert-success');
            $('#alerta').show();
            $('#tituloAlerta').text('Error');
            $('#mensajeAlerta').text('Debes seleccionar al menos un usuario');
            return;
        }
        // // Si no se selecciona ninguna bodega mostramos un mensaje de error
        // if(bodegasSeleccionadas.length == 0){
        //     $('#alerta').addClass('alert-danger').removeClass('alert-success');
        //     $('#alerta').show();
        //     $('#tituloAlerta').text('Error');
        //     $('#mensajeAlerta').text('Debes seleccionar al menos una bodega');
        //     return;
        // }
        
        //Verificamos que al menos se haya seleccionado una ruta
        if(rutasSeleccionadas.length == 0){
            $('#alerta').addClass('alert-danger').removeClass('alert-success');
            $('#alerta').show();
            $('#tituloAlerta').text('Error');
            $('#mensajeAlerta').text('Debes seleccionar al menos una ruta');
            return;
        }

        //Vamos a verificar que se haya seleccionado una fecha inicial y una fecha final y que sean coherentes
        var fechaInicial = $('#fechaInicial').val();
        var fechaFinal = $('#fechaFinal').val();

        if(fechaInicial == "" || fechaFinal == ""){
            $('#alerta').addClass('alert-danger').removeClass('alert-success');
            $('#alerta').show();
            $('#tituloAlerta').text('Error');
            $('#mensajeAlerta').text('Debes asignar una fecha inicial y una fecha final correctamente');
            return;
        }

        if(fechaInicial > fechaFinal){
            $('#alerta').addClass('alert-danger').removeClass('alert-success');
            $('#alerta').show();
            $('#tituloAlerta').text('Error');
            $('#mensajeAlerta').text('La fecha inicial no puede ser mayor a la fecha final');
            return;
        }
        // Enviamos los datos al servidor
        $.ajax({
            type: "POST",
            url: "ajaxAsignarRutas.php",
            data: {
                usuarios: usuariosSeleccionados,
                bodegas: bodegasSeleccionadas,
                uOperativas: uOperativasSeleccionadas,
                rutas: rutasSeleccionadas,
                fechaInicial: fechaInicial,
                fechaFinal: fechaFinal
            },
            success: function(response){
                //console.log(response);
                // Parseamos el JSON
                var respuesta = JSON.parse(response);
                var asignaciones = respuesta.asignaciones;
                var tablaRutas = $('#rutasAsignadasTable').DataTable();

                //console.log("Asignaciones: "+JSON.stringify(asignaciones));

                //Eliminamos los datos de la tabla
                tablaRutas.clear().draw();
                // Agregamos los datos al datatable
                for(var i = 0; i < asignaciones.length; i++){
                    tablaRutas.row.add([
                        asignaciones[i].fechaInicial+" - "+asignaciones[i].fechaFinal,
                        asignaciones[i].nombres,
                        asignaciones[i].nombreBodega,
                        asignaciones[i].nombreUOperativa,
                        asignaciones[i].nombreRuta,
                        '<button id="'+ asignaciones[i].id_ruta +'" usr="'+ asignaciones[i].id_usuario +'" class="btn btn-danger btn-sm btnEliminarRuta">Eliminar</button>'
                    ]).draw();
                }
                
                // Mostramos mensaje
                $('#alerta').addClass('alert-success').removeClass('alert-danger');
                $('#alerta').show();
                $('#tituloAlerta').text('Éxito');
                $('#mensajeAlerta').text('Rutas asignadas correctamente');

                //Limpiamos los selectores
                $('#selectBodega').val(null).trigger('change');
                $('#selectUOperativa').val(null).trigger('change');
                $('#selectRuta').val(null).trigger('change');
                // Deseleccionamos los usuarios
                $('#selectUsuarios').val(null).trigger('change');
                //Desabilitamos el selector de U. Operativa
                $('#selectUOperativa').prop('disabled', true);
                //Desabilitamos el selector de Rutas
                $('#selectRuta').prop('disabled', true);
                //Limpiamos las fechas
                //Fecha inicial se queda con la fecha actual
                $('#fechaInicial').val('<?= date('Y-m-d'); ?>');
                $('#fechaFinal').val('');
            }
        });
    });

    // Creamos un evento para eliminar una ruta asignada
    $('#rutasAsignadasTable').on('click', '.btnEliminarRuta', function(){
        var idRuta = $(this).attr('id');
        var idUsuario = $(this).attr('usr');
        //console.log("ID Ruta: "+idRuta);
        //console.log("ID Usuario: "+idUsuario);
        // Enviamos los datos al servidor
        $.ajax({
            type: "POST",
            url: "ajaxEliminarRutaAsignada.php",
            data: {
                idRuta: idRuta,
                idUsuario: idUsuario
            },
            success: function(response){
                //console.log(response);
                // Parseamos el JSON
                var respuesta = JSON.parse(response);
                var tablaRutas = $('#rutasAsignadasTable').DataTable();
                //Eliminamos los datos de la tabla
                tablaRutas.clear().draw();
                // Agregamos los datos al datatable
                for(var i = 0; i < respuesta.length; i++){
                    tablaRutas.row.add([
                        respuesta[i].fechaInicial+" - "+respuesta[i].fechaFinal,
                        respuesta[i].nombres,
                        respuesta[i].nombreBodega,
                        respuesta[i].nombreUOperativa,
                        respuesta[i].nombreRuta,
                        '<button id="'+ respuesta[i].id_ruta +'" usr="'+ respuesta[i].id_usuario +'" class="btn btn-danger btn-sm btnEliminarRuta">Eliminar</button>'
                    ]).draw();
                }
                
                // Mostramos mensaje
                $('#alerta').addClass('alert-success').removeClass('alert-danger');
                $('#alerta').show();
                $('#tituloAlerta').text('Éxito');
                $('#mensajeAlerta').text('Ruta eliminada correctamente');
            }
        });
    });
    // Inicializamos el datatable
    $('#rutasAsignadasTable').DataTable( {
        dom: 'frti',
        language: {
            "url": "js/spanish.js"
        },
        order: [],
        stripeClasses: [],
        paging: false // Deshabilitamos la paginación
	});
  });
    
</script>