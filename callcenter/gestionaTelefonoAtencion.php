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

function formatearTelefono($numero) {
    if (strlen($numero) != 10) {
        return $numero;
    }
    return substr($numero, 0, 3) . '-' . substr($numero, 3, 3) . '-' . substr($numero, 6);
}

?>
<link rel="stylesheet" type="text/css" href="css/estilos.css">
<!-- estilos para el select2 -->
<!-- <link rel="stylesheet" type="text/css" href="../../../sys/bocampana_vista/plugins/select2/select2.min.css">
<link rel="stylesheet" type="text/css" href="../../../sys/bocampana_vista/plugins/select2/es.js"> -->

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
 
</style>
    <div class="card-principal" style="">
        <!-- Aquí va el contenido de tu tarjeta -->
        
        <div class="row ml-4 mb-4">
            <div class="page-title">
                <h3>Administrador de teléfonos de atención a clientes de Call Center</h3>
            </div>
        </div>

        <div class="row justify-content-center "> 
            <div class="col-md-auto">
                <div class="alert alert-primary alertas" role="alert" style="display: none;"></div>
            </div>
        </div>
        
        <div class="card-body">
            <div class="row mt-3">
                <div class="col-sm-12 col-4-md col-lg-4">
                    <div class="page-title">
                        <h5 class="mt-3 mb-4">Estado de los teléfonos </h5>
                    </div>    
                    <table id="tablaTelefonos" class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Teléfono</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $query = "SELECT * FROM callcenter_telefonos";
                                if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
                                while($row = mysqli_fetch_array($result)){
                                    echo "<tr>";
                                        echo "<td>". formatearTelefono($row['numero'])."</td>";
                                        echo $row['id_status'] == 4 ? "<td><button class='btn btn-sm btn-success btnCambiaEstadoTelefono' id='".$row['id']."'><span><i style=' color: #f6fcfb;' data-feather='check'></i></span>&emsp;Activo</button></td>" : "<td><button class='btn btn-sm btn-danger btnCambiaEstadoTelefono' id='".$row['id']."'><span><i style=' color: #f6fcfb;' data-feather='slash'></i></span>&emsp;Inactivo</button></td>";
                                    echo "</tr>";
                                }
                            ?>
                        </tbody>
                    </table>
                </div>
                <div class="col-sm-12 col-md-8 col-lg-8">
                    <div class="page-title">
                        <h5 class="mt-3 mb-4">Teléfonos asignados en la fecha: <?= date('Y-m-d') ?></h5>
                    </div>
                    <!-- Aqui vamos a poner una tabla para gestionar los telefonos asignados a los usuarios en el dia de hoy -->
                    <table id="tablaGestionTelefonos" class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Teléfono</th>
                                <th>Eliminar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $query = "SELECT *, CONCAT(usr.nombres, ' ', usr.apat, ' ', usr.amat) AS nombre_completo, usr.id as idUsuario, callcenter_telefonoasignado.id as idAsignacionTelefono
                                FROM callcenter_telefonoasignado
                                INNER JOIN usr ON usr.id = callcenter_telefonoasignado.id_usuario
                                INNER JOIN callcenter_telefonos ON callcenter_telefonos.id = callcenter_telefonoasignado.id_telefono
                                WHERE DATE(fechaAsignacion) = CURDATE()";
                                if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
                                while($row = mysqli_fetch_array($result)){
                                    echo "<tr>";
                                    // echo "<td>".$row['idUsuario']."</td>";
                                    echo "<td>".$row['nombre_completo']."</td>";
                                    echo "<td>". formatearTelefono($row['numero'])."</atd>";
                                    echo "<td><button class='btn btn-danger btnEliminaAsignacionTelefono' id='".$row['idAsignacionTelefono']."'><span><i style=' color: #f6fcfb;' data-feather='trash'></i></span>&emsp;Eliminar Asignacion</button></td>";
                                    echo "</tr>";
                                }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div> <!-- Aqui termina row -->
            <hr>
            <div class="">
                <div class="row mt-4 mb-4">
                    <div class="page-title">
                        <h3>Alta nuevo número de atención</h3>
                    </div>
                </div>

                <div class="row">
                    <div class="col-auto">
                        <label for="nuevoTelefono">Ingresa nuevo numero de teléfono para atención a clientes </label>
                        <input type="text" class="form-control" id="nuevoTelefono" name="nuevoTelefono" placeholder="Ingresa un numero de teléfono de atención">
                    </div>
                </div>
                <div class="row mt-4 mb-4">
                    <div class="col-auto">
                        <button class="btn btn-primary" id="btnGuardarTelefono"><span><i style=" color: #f6fcfb;" data-feather="save"></i></span>&emsp;Guardar</button>
                    </div>
                </div>
            </div> <!-- Aqui termina row de agregar telefono -->

        </div> <!-- Aquí termina card-body -->
    </div> <!-- Aquí termina card-principal -->
<!-- </div> div del container comentado -->

<?
include $ruta."sys/hf/pie_v3.php";
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Script para el select2 -->
<!-- <script src="../../../sys/bocampana_vista/plugins/select2/select2.min.js"></script>
<script src="../../../sys/bocampana_vista/plugins/select2/es.js"></script> -->

<script>
$(document).ready(function() {
    $("#nuevoTelefono").inputmask("999-999-9999");
    feather.replace();
    
    $('#tablaTelefonos').DataTable({
        dom: 'frti',
        language: {
            "url": "js/spanish.js"
        },
        order: [[0, 'asc']],
        stripeClasses: [],
        paging: false // Deshabilitamos la paginación
    });

    $('#tablaGestionTelefonos').DataTable({
        dom: 'frti',
        language: {
            "url": "js/spanish.js"
        },
        order: [],
        stripeClasses: [],
        paging: false // Deshabilitamos la paginación
    });

});

//Cramos un evento para eliminar la asignacion de un telefono
$(document).on('click', '.btnEliminaAsignacionTelefono', function(){
    var id = $(this).attr('id');
    console.log("Vamos a eliminar la asignacion del telefono con id: "+id);
    $.ajax({
        url: 'ajaxEliminaAsignacionTelefono.php',
        type: 'POST',
        dataType: 'json',
        data: {idAsignacionTelefono: id},
        success: function(response){
            //Mostramos el mensaje de respuesta
            muestraMensaje(response.mensaje, response.error);

            // Vacia la tabla para agregar los nuevos datos
            $('#tablaGestionTelefonos').DataTable().clear().draw();
            // Agrega los nuevos datos recorriendo el array
            $.each(response.asignaciones, function(i, item){
                $('#tablaGestionTelefonos').DataTable().row.add([
                    item.nombre_completo,
                    item.numero,
                    item.boton
                ]).draw();
            });
            // espearamo 100 milisegundos para que se actualice la tabla y le de tiempo a dibujar los botones
            setTimeout(function(){
                feather.replace();
            }, 100);
        },
        error: function(response){
            alert("Ocurrió un error al eliminar la asignación del teléfono");
        }
    });
});


function chartTest(){
    const ctx = document.getElementById('myChart');
    new Chart(ctx, {
        type: 'bar',
        data: {
        labels: ['Red', 'Blue', 'Yellow', 'Green', 'Purple', 'Orange'],
        datasets: [{
            label: '# of Votes',
            data: [12, 19, 3, 5, 2, 3],
            borderWidth: 1
        }]
        },
        options: {
        scales: {
            y: {
            beginAtZero: true
            }
        }
        }
    });
}

// Cramos un evento para cambiar el estado del telefono
$(document).on('click', '.btnCambiaEstadoTelefono', function(){
    var id = $(this).attr('id');
    $.ajax({
        url: 'ajaxCambiaEstadoTelefonoAtn.php',
        type: 'POST',
        data: {idTelefono: id},
        success: function(response){
            // Vacia la tabla para agregar los nuevos datos
            $('#tablaTelefonos').DataTable().clear().draw();
            // Agrega los nuevos datos recorriendo el array
            $.each(JSON.parse(response), function(i, item){
                $('#tablaTelefonos').DataTable().row.add([
                    item.numero,
                    item.boton
                ]).draw();
            });
            // espearamo 100 milisegundos para que se actualice la tabla y le de tiempo a dibujar los botones
            setTimeout(function(){
                feather.replace();
            }, 100);
        },
        error: function(response){
            alert("Ocurrió un error al cambiar el estado del teléfono");
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


//Creamos un evento para guardar el nuevo telefono
$(document).on('click', '#btnGuardarTelefono', function(){
    var telefono = $('#nuevoTelefono').val();

    if(telefono == ""){
        alert("Debes ingresar un numero de telefono");
        return;
    }

    //Limpiamos el telefono quitando los guiones
    telefono = telefono.replace(/-/g, "");
    //Verificamos que el telefono tenga 10 digitos y todos sean numeros
    if(telefono.length != 10 || isNaN(telefono)){
        alert("Por favor ingresa un numero de telefono valido");
        return;
    }
    $.ajax({
        url: 'ajaxGuardaTelefonoAtn.php',
        type: 'POST',
        dataType: 'json',
        data: {nuevoTelefono: telefono},
        success: function(response){
            //Recibimos la respuesta en formato JSON
            console.log(response);
            //Mostramos el mensaje de respuesta
            muestraMensaje(response.mensaje, response.status);
            var tabla = $('#tablaTelefonos').DataTable();
            // Vacia la tabla para agregar los nuevos datos
            tabla.clear().draw();
            // Agrega los nuevos datos recorriendo el array de telefonos
            $.each(response.telefonos, function(i, item){
                tabla.row.add([
                    item.numero,
                    item.boton
                ]).draw();
            });
            // espearamo 100 milisegundos para que se actualice la tabla y le de tiempo a dibujar los botones
            setTimeout(function(){
                feather.replace();
            }, 100);

            //Si el telefono se guardo correctamente limpiamos el campo
            if(response.status == 1){
                $('#nuevoTelefono').val("");
            }
        },
        error: function(response){
            alert("Ocurrió un error al guardar el teléfono ");
        }

    });
});


</script>