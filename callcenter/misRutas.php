<?
$ruta = "../../../";
include $ruta."sys/precarga_mysqli.php";
include $ruta."sys/fcn/fcnSelects.php";

$permitidos = array(1,9,11,12,13); // SA y GC (correcto)
$acceso = validaAccesoAutorizado($permitidos);

if($acceso == 0)
    redirect("", "");

include $ruta."sys/hf/header_v3.php";
include $ruta."sys/hf/banner_v3.php";
include $ruta."mtto/mods/menuGral_looknfeel_mysqli.php";
?>
<link rel="stylesheet" type="text/css" href="css/estilos.css">
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
</style>




    <div class="card card-principal" style="">
        <!-- Aquí va el contenido de tu tarjeta -->
        
        <div class="row ml-2">
            <div class="page-title">
                <h3>Crear pedido</h3>
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
                <!-- Dejamos un alert predefinido para mostrar mensajes de error o de éxito -->
                <div class="alert alert-dismissible fade show d-none" role="alert" id="alerta">
                    <strong id="mensajeAlerta"></strong>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true" id="cerrarAlerta">&times;</span>
                    </button>
                </div>
            </div>
        </div>

        <?php
            //Obtebemos la fecha de hoy para buscarlas rutas que tiene asignadas el usuario en función a la fecha
            $fechaHoy = date('Y-m-d');
            // Obtenemos las rutas relacionadas con el usuario en función a la fecha de hoy
            $query = "SELECT ruta.id, ruta.nombre, ruta.id_status, callcenter_usuariosruta.id as idAsignacionRuta FROM ruta 
                INNER JOIN callcenter_usuariosruta ON ruta.id = callcenter_usuariosruta.id_ruta 
                WHERE callcenter_usuariosruta.id_usuario =".$_SESSION['id_usr']." AND '".$fechaHoy."' between callcenter_usuariosruta.fechaInicial and callcenter_usuariosruta.fechaFinal and callcenter_usuariosruta.estadoAsignacion = 1"; 
            // echo $query."<br>";
            if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);

        ?>
        
        <div class="card-body" >
            <hr>
            <div class="row">
                <div class="page-title">
                    <h3 class="text-left">Rutas asignadas:</h3>
                </div>
            </div>
            <div class="row">
                <?php
                if(mysqli_num_rows($result) > 0){
                    while($row = mysqli_fetch_array($result)){?> 
                        <div class="col-auto mb-3">
                            <button type="button" idAsignacion="<?= $row['idAsignacionRuta'] ?>" id="<?= $row['id'] ?>" class="btn btn-sm btn-outline-info btnRuta"><?= $row['nombre'] ?></button>
                        </div>
                    <?php
                    }
                }else{
                    echo "No hay rutas asignadas";
                }
                ?>

                
                
            </div>
            <div class="row">
                <div class="col-xl-12 col-lg-12 col-sm-12  layout-spacing">
                    <div class="widget-content widget-content-area br-6">
                        <table id="misRutasTable" class="table table-hover non-hover" style="width:100%">
                            <thead>
                                <tr>
                                    <th>No. Cliente</th>
                                    <th>Cliente</th>
                                    <th>Ruta</th>
                                    <th>Teléfono</th>
                                    <th>Monto</th>
                                    <th>Incidencia/Estado</th>
                                    <th class=" dt-no-sorting">Acciones</th>
                                    <th>Búsqueda</th>
                                </tr>
                            </thead>
                            <tbody>
                            
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>No. Cliente</th>
                                    <th>Cliente</th>
                                    <th>Ruta</th>
                                    <th>Teléfono</th>
                                    <th>Monto</th>
                                    <th>Incidencia/Estado</th>
                                    <th class=" dt-no-sorting">Acciones</th>
                                    <th>Búsqueda</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div> 
                </div> <!-- Aquí termina col -->
            </div> <!-- Aquí termina row -->
            <!-- Agregamos un div para mostrar el total de lo que se ha vendido en la ruta -->

            <div class="row">
                <div class="col-3">
                    <label for="telefonoUtilizado">Telefono utilizado para atender:</label>
                    <label class="form-control" id="labelTelefonoAsignado"></label>
                </div> <!-- Aquí termina col -->
                <div class="col-4">
                    <label for="totalRuta">Total vendido en la ruta:</label>
                    <label class="form-control" id="totalRuta">$ 0.00</label>
                </div> <!-- Aquí termina col -->
            </div> <!-- Aquí termina row -->
            
            <hr>
            <!-- Agregamos un div invisible para agregar un boton de cierre de ruta -->
            <div class="row">
                <div class="col-12">
                    <div class="d-none" id="divCerrarRuta">
                        <form id="formCierreRuta" method="post" action="setCierreRuta.php">
                            <input type="hidden" value="" readonly name="idRuta" id="idRutaCerrar">
                            <label for="btnCerrarRuta">Si ya ha terminado de atender a los clientes de esta ruta, presione el botón para cerrar el dia.</label>
                            <button type="button" class="btn btn-danger btn-lg btn-block" id="btnCerrarRuta">Cerrar ruta</button>
                        </form>
                    </div>
                </div> <!-- Aquí termina col -->
            </div> <!-- Aquí termina row -->

        </div> <!-- Aquí termina card-body -->
    </div> <!-- Aquí termina card-principal -->
<!-- </div> div del container comentado -->


<!-- Modal de incidencia -->
<div class="modal fade" id="modalIncidencia" tabindex="-1" role="dialog" aria-labelledby="modalWhatsappLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="formSetIncidencia" method="post" action="setIncidenciaPedido.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalWhatsappLabel">Incidencia para el cliente: <span id="nombreCliente"></span></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <input type="hidden" name="idCliente" id="idCliente">
                <input type="hidden" name="idRuta" id="idRuta">
                <input type="hidden" name="idOficina" id="idOficina">
                <input type="hidden" name="telPreferente" id="telPreferente">
                
                <div class="modal-body">
                    <div class="form-group">
                        <div class="row">
                            <div class="col">
                                <label for="incidencia">Incidencia</label>
                                <select class="form-control" required name="idIncidencia" id="idIncidencia">
                                    <option selected disabled value="">Selecciona una incidencia</option>
                                    <?php
                                    //Obtenemos las incidencias para mostrarlas en el select
                                    $query = "SELECT * FROM callcenter_incidencias WHERE id > 2";
                                    if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
                                        while($row = mysqli_fetch_array($result)){
                                            echo "<option value='".$row['id']."'>".$row['nombre']."</option>";
                                        }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <!-- <label for="comentarios">Nota:</label> -->
                                <textarea class="form-control mt-2 d-none" name="notaIncidencia" id="notaIncidencia" rows="3" placeholder="Si necesitas agregar una nota puedes hacerlo aquí"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-success">Enviar incidencia</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Modal de WhatsApp -->
<div class="modal fade" id="modalWhatsapp" tabindex="-1" role="dialog" aria-labelledby="modalWhatsappLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalWhatsappLabel">Enviar ticket web via WhatsApp al cliente: <span id="nombreEstablecimiento"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            
            <div class="modal-body">
                <div class="row mb-4">
                    <div class="col-12">
                        <label for="mensaje">URL de pedido:</label>
                        <label class="form-control" id="urlPedido"></label>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 text-center">
                        <label for="mensaje">Enviar via WhatsApp:</label>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 text-center">
                        <a target="_blank" href="" class="btn btn-success" id="linkWhatsapp">Enviar mensaje</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de WhatsApp de Aviso -->
<div class="modal fade" id="modalWhatsappAviso" tabindex="-1" role="dialog" aria-labelledby="modalWhatsappLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalWhatsappLabel">Contactar WhatsApp al cliente: <b><span id="nombreEstablecimientoAviso"></span></b></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            
            <div class="modal-body">
                <div class="row mb-4">
                    <div class="col-12">
                        <label for="mensaje">Antes de contactar al cliente para levantar pedido, envía un saludo a través de un mensaje WhatsApp:</label>
                        <label class="form-control" id="urlAviso"></label>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-12 text-center">
                        <label for="mensaje">Utiliza este botón para confirmar que haz enviado un mensaje de saludo de contacto al cliente:</label>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 text-center">
                        <button type="button" value="" idCliente="" idTelefono="" id="linkWhatsappAviso" class="btn btn-success btnConfirmarAviso"><span><i style="color: #f6fcfb; font-size: 10px;" data-feather="alert-octagon"></i>&emsp;Confirmar envió de saludo</span></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Seleccion de telefono -->
<div class="modal fade" id="modalTelefonoAsignado" tabindex="-1" role="dialog" aria-labelledby="modalTelefonoAsignadosLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTelefonoAsignadosLabel">Seleccione el numero de teléfono asignado para atender a los clientes de esta ruta</h5>
            </div>
            <div class="modal-body">
                <div class="row mb-4">
                    <div class="col-12">
                        <label for="mensaje">Seleccione el numero de teléfono tiene asignado:</label>
                        <select class="form-control" name="telefonosAtencion" id="telefonosAtencion">
                            <option value="" selected disabled>Selecciona un teléfono</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 text-center">
                        <button type="button" value="" name="asignacion" class="btn btn-success mt-3" id="btnValidarTelefonoAtencion">Continuar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Fin del modal de seleccion de telefono -->


<?php
include $ruta."sys/hf/pie_v3.php";
?>

<script>

    //Creamos una funcion para mostrar mensaje es la alerta
    function muestraMensaje(mensaje, tipo){
        //console.log("Mensaje: "+mensaje);
        //console.log("Tipo: "+tipo);
        //Mostramos el mensaje en la alerta
        $("#mensajeAlerta").html(mensaje);
        //Mostramos la alerta
        $("#alerta").removeClass("d-none");
        //Agregamos la clase de acuerdo al tipo de mensaje
        if(tipo == 0){
            $("#alerta").addClass("alert-danger");
        }else{
            $("#alerta").addClass("alert-success");
        }

        // Ocultamos la alerta después de 8 segundos
        setTimeout(function() {
            $("#alerta").addClass("d-none");
        }, 8000);
    }

    <?php
    if(isset($_SESSION['workRute_CC'])){ ?>
    //Creamos una funcion para que se ejecute cuando el session exista
    function loadClientes(){
        // Aquí puedes colocar el código que deseas ejecutar cuando la página haya terminado de cargar
        //console.log('La página ha terminado de cargar');

        var idRuta = <?= $_SESSION['workRute_CC'] ?>;
        var tabla = $("#misRutasTable").DataTable();
        //console.log(idRuta);
        //Eliminamos los datos de la tabla
        tabla.clear().draw();
        //Eliminanos la clase btn-primary de todos los botones
        $(".btnRuta").removeClass("btn-primary").addClass("btn-outline-info");
        //Eliminanos la clase active de todos los botones
        $(".btnRuta").removeClass("active");

        //Agregamos la clase btn-primary al botón que se le dio click
        $('#'+idRuta).addClass("btn-primary");
        //Agregamos la clase active al botón que se le dio click
        $('#'+idRuta).addClass("active");


        $.ajax({
            url: "ajaxClientesRuta.php",
            type: "POST",
            data: {idRuta: idRuta},
            dataType: "json",
            beforeSend: function(){
                $("#alertRutaCerrada").remove();
                // Mostramos el mensaje de que se está cargando la información de los clientes
                $("#misRutasTable tbody").html("<tr><td colspan='7' class='text-center'><img src='../../../sysimg/icoCarga.gif' class='img-fluid'> Cargando información de los clientes...</td></tr>");
            },

            success: function(response){
                var data = response.clientes;
                //Escribimos en consola el JSON que recibimos
                // console.log("Total de la ruta: "+JSON.stringify(response.totalRuta));
                // Si la respuesta es vacía, mostramos un mensaje de que no hay clientes
                if(data.length < 1){
                    $("#misRutasTable tbody").html("<tr><td colspan='7' class='text-center'>No hay clientes en esta ruta</td></tr>");
                    //Termina la función
                    return;
                }
                //Recorremos el JSON para agregar los datos a la tabla
                //Recorremos el objeto data para agregar los datos a la tabla
                $.each(data, function(i, item){
                    var rowNode = tabla.row.add([
                        item.idCliente,
                        item.establecimiento,
                        item.ruta,
                        item.telefonos,
                        item.monto,
                        item.estado,
                        item.acciones,
                        item.busqueda
                    ]).draw().node();
                    //Agregamos la clase al rowNode
                    $(rowNode).addClass(item.rowColor);
                });
                //Actualizamos los feather icons
                setTimeout(function() {
                    feather.replace();
                }, 100); // Ajusta el tiempo según sea necesario
                
                //Obtenemos el total de la ruta
                $("#totalRuta").html("$ "+response.totalRuta);

                // Si data.rutaCerrada es distinto de '' significa que la ruta está cerrada por lo que avisamos al usuario
                if(response.rutaCerrada != ''){
                    //Agregamnos al div con la clase misRutasTable_filter el mensaje de que la ruta está cerrada con un apend
                    $("#misRutasTable_filter").append("<div id='alertRutaCerrada' class='alert alert-warning mt-2 text-center' role='alert'>Esta ruta está <b>cerrada</b>, no se puede realizar más pedidos.</div>");
                    //Ocultamos el div para cerrar la ruta
                    $("#divCerrarRuta").addClass("d-none");
                }else{
                    //Mostramos el div para cerrar la ruta
                    $("#idRutaCerrar").val(idRuta);
                    $("#divCerrarRuta").removeClass("d-none");
                    //Eliminamos la alerta de que la ruta está cerrada en caso de que exista
                    $("#alertRutaCerrada").remove();
                }
                //Agremos el telefono asignado al label
                $("#labelTelefonoAsignado").html(response.telefonoAtencion);
                
            },
            error: function(){
                $("#misRutas table tbody").html("<tr><td colspan='7' class='text-center'>No hay clientes disponibles para esta ruta, probablemente que se encuentre bloqueada.</td></tr>");
                //Ocultamos el div para cerrar la ruta
                $("#divCerrarRuta").addClass("d-none");
            }
        });
    }
    <?php
    } //Fin de la condición isset($_SESSION['workRute_CC'])
    ?>


    //Creamos una función para verificar si el usuario tiene un telefono asignado para atención a los clientes
    function verificaTelefonoAsignado(idAsignacion){
        //console.log("id de asignacion recibido: "+idAsignacion );
        //Creamos un ajax para verificar si el usuario tiene un telefono asignado y si no llamar al modal con los telefonos para que seleccione uno
        $.ajax({
            url: "ajaxGetTelefonosAtencion.php",
            type: "POST",
            data: {
                idAsignacion: idAsignacion
            },
            dataType: "json",
            success: function(response){
                //console.log("Respuesta de telefonos: "+JSON.stringify(response));
                //Si ya hay un telefono asignado, no hacemos nada y terminamos la función
                if(response.verificado == '1'){
                    //Mostramos el telefono asignado en el label
                    $("#labelTelefonoAsignado").html(response.telefonos[0].telefono);
                    return;
                }

                // Si la respuesta es vacía, mostramos un mensaje de que no hay clientes
                if(response.telefonos.length < 1){
                    $("#telefonosAtencion").html("<option value='' selected disabled>No hay teléfonos disponibles</option>");
                    return;
                }
                //Limipiamos el select de telefonos
                $("#telefonosAtencion").html("<option value='' selected disabled>Selecciona un teléfono</option>");
                //Recorremos el JSON para agregar los datos al select
                $.each(response.telefonos, function(i, item){
                    $("#telefonosAtencion").append("<option value='"+item.id+"'>"+item.telefono+"</option>");
                });
                //Ajustamos el valor del btnValidarTelefonoAtencion para que tenga el id de la asignación
                $("#btnValidarTelefonoAtencion").val(idAsignacion);
                //Mostramos el modal para seleccionar el telefono
                $('#modalTelefonoAsignado').modal({
                    backdrop: 'static',
                    keyboard: false, 
                    show: true 
                });
            },
            error: function(){
                alert("No se pudo obtener los teléfonos disponibles para atención al cliente");
                // $("#telefonosAtencion").html("<option value='' selected disabled>No hay teléfonos disponibles</option>");
            }
        });
    }

    $(document).ready(function(){
        //workRute_CC
        <?php
        echo isset($_SESSION['workRute_CC']) ? "loadClientes();" : "";
        ?>        
        $(".btnRuta").click(function(){
            var idRuta = $(this).attr("id");
            var tabla = $("#misRutasTable").DataTable();
            var idAsignacion = $(this).attr("idasignacion");
            //console.log("id de asignacion: "+idAsignacion);
            //console.log(idRuta);
            //Eliminamos los datos de la tabla
            tabla.clear().draw();
            //Eliminanos la clase btn-primary de todos los botones
            $(".btnRuta").removeClass("btn-primary").addClass("btn-outline-info");
            //Eliminanos la clase active de todos los botones
            $(".btnRuta").removeClass("active");

            //Agregamos la clase btn-primary al botón que se le dio click
            $(this).addClass("btn-primary");
            //Agregamos la clase active al botón que se le dio click
            $(this).addClass("active");


            $.ajax({
                url: "ajaxClientesRuta.php",
                type: "POST",
                data: {idRuta: idRuta},
                dataType: "json",
                beforeSend: function(){
                    //Limpiamos el total de la ruta
                    $("#totalRuta").html("$ 0.00");
                    $("#alertRutaCerrada").remove();
                    // Mostramos el mensaje de que se está cargando la información de los clientes
                    $("#misRutasTable tbody").html("<tr><td colspan='7' class='text-center'><img src='../../../sysimg/icoCarga.gif' class='img-fluid'> Cargando información de los clientes...</td></tr>");
                },

                success: function(response){
                    var data = response.clientes;
                    if(data.length < 1){
                        $("#alertRutaCerrada").remove();
                        $("#misRutasTable tbody").html("<tr><td colspan='7' class='text-center'>No hay clientes en esta ruta</td></tr>");
                        //Termina la función
                    }
                    //Recorremos el objeto data para agregar los datos a la tabla
                    $.each(data, function(i, item){
                        var rowNode = tabla.row.add([
                            item.idCliente,
                            item.establecimiento,
                            item.ruta,
                            item.telefonos,
                            item.monto,
                            item.estado,
                            item.acciones,
                            item.busqueda
                        ]).draw().node();

                        $(rowNode).addClass(item.rowColor);
                    });

                    //Obtenemos el total de la ruta
                    $("#totalRuta").html("$ "+response.totalRuta);
                    
                    //Actualizamos los feather icons
                    setTimeout(function() {
                        feather.replace();
                    }, 100); // Ajusta el tiempo según sea necesario

                    // Si data.rutaCerrada es distinto de '' significa que la ruta está cerrada por lo que avisamos al usuario
                    if(response.rutaCerrada != ''){
                        //Agregamnos al div con la clase misRutasTable_filter el mensaje de que la ruta está cerrada con un apend
                        $("#misRutasTable_filter").append("<div id='alertRutaCerrada' class='alert alert-warning mt-2 text-center' role='alert'>Esta ruta está <b>cerrada</b>, no se puede realizar más pedidos.</div>");
                        //Ocultamos el div para cerrar la ruta
                        $("#divCerrarRuta").addClass("d-none");
                    }else{
                        //Mostramos el div para cerrar la ruta
                        $("#idRutaCerrar").val(idRuta);
                        $("#divCerrarRuta").removeClass("d-none");
                        //Eliminamos la alerta de que la ruta está cerrada en caso de que exista
                        $("#alertRutaCerrada").remove();

                        //Verifica que tenga un telefono asignado
                        verificaTelefonoAsignado(idAsignacion);
                    }
                    
                },
                error: function(){
                    $("#misRutasTable tbody").html("<tr><td colspan='7' class='text-center'>No hay clientes disponibles para esta ruta, probablemente que se encuentre bloqueada.</td></tr>");
                    $("#divCerrarRuta").addClass("d-none");
                    //Limpiamos el total de la ruta
                    $("#totalRuta").html("$ 0.00");
                }
            });
        });
    }); //Fin de document ready

    //Creamos un evento que se activa cuando el selector #idIncidencia sufra un cambio
    $(document).on("change", "#idIncidencia", function(){
        //Obtenemos el valor del select
        var idIncidencia = $(this).val();
        //Si el valor de la incidencias es 3 o 6, mostramos el textarea
        if(idIncidencia == 4 || idIncidencia == 6){
            // Verifiamo si el textarea ya tiene la clase d-none
            if($("#notaIncidencia").hasClass("d-none")){
                //Eliminamos la clase d-none
                $("#notaIncidencia").removeClass("d-none");
            }
        }else{
            // Verifiamos si el textarea ya tiene la clase d-none
            if(!$("#notaIncidencia").hasClass("d-none")){
                //Agregamos la clase d-none
                $("#notaIncidencia").addClass("d-none");
            }            
        }
    });

    //Creamos un evento para almacene el telefono de atención al cliente al pulsar el bonton btnValidarTelefonoAtencion del modal modalTelefonoAsignado
    $(document).on("click", "#btnValidarTelefonoAtencion", function(){
        var idAsignacion = $(this).val();
        var idTelefono = $("#telefonosAtencion").val();
        //console.log("idAsignacion: "+idAsignacion);
        //console.log("idTelefono: "+idTelefono);

        //Veriricamos que el id del telefono no sea vacío
        if(idTelefono == ''){
            alert("Debes seleccionar un teléfono para continuar.");
            return;
        }

        //Creamos un ajax para almacenar el telefono de atención al cliente
        $.ajax({
            url: "ajaxSetTelefonoAtencion.php",
            type: "POST",
            data: {
                idAsignacion: idAsignacion,
                idTelefono: idTelefono
            },
            dataType: "json",
            success: function(response){
                //console.log("Respuesta de almacenamiento de telefono: "+JSON.stringify(response));
                if (response.status != 'ok'){
                    //Mostramos un mensaje de alerta
                    alert("No se pudo asignar un telefono, intente de nuevo más tarde.");
                }              
                
                //Cerramos el modal
                $('#modalTelefonoAsignado').modal('hide');
                //Actualizamos el label con el telefono asignado
                $("#labelTelefonoAsignado").html(response.telefono);
            },
            error: function(){
                alert("No se pudo almacenar el teléfono de atención al cliente, intente de nuevo más tarde.");
                //Cerramos el modal
                $('#modalTelefonoAsignado').modal('hide');
            }
        });
    });

    //Creamos una función para que al dar click en el botón de cerrar ruta, se abra el modal de cierre de ruta
    $(document).on("click", "#btnCerrarRuta", function(){
        //Mostramos un mensaje de alerta de confirmación para cerrar la ruta
        if(confirm("Si ya ha terminado de trabajar con esta ruta, presione Aceptar para cerrarla. Una vez cerrada, no podrá trabajar con ella nuevamente. ¿Desea cerrar la ruta?")){
            //Enviamos el formulario
            $("#formCierreRuta").submit();
        }
    });

    //Creamos un evento para que al dar click en el boton de whatsapp, se abra una nueva ventana con el link de whatsapp
    $(document).on("click", ".btnWhatsapp", function(){
        //console.log("Click en whatsapp");
        var telefono = $(this).attr("telefono");
        var establecimiento = $(this).attr("establecimiento");
        $.ajax({
            url: "ajaxEnviaWhatsaap.php",
            type: "POST",
            data: {
                idCliente: $(this).attr("id"),
                idPedido: $(this).attr("pedido")
            },
            dataType: "json",
            beforeSend: function(){
                //Agregamos el nombre del establecimiento al modal
                $("#nombreEstablecimiento").html(establecimiento);
                //Mostramos el modal
                $("#modalWhatsapp").modal("show");
            },
            success: function(response){
                // Si la respuesta es vacía, mostramos un mensaje de que no hay clientes
                if(response.url == ''){
                    $("#urlPedido").html("No se pudo obtener el link del pedido, intente de nuevo más tarde.");
                    $("#linkWhatsapp").addClass("d-none");
                    return;
                }
                // Mostramos el link de whatsapp
                $('#linkWhatsapp').removeClass("d-none");
                // Creamos un mensaje con el link del pedido
                var mensaje = "¡Hola!\n\nSomos Distribuidora *El Toro*.\n\nHemos enviado un enlace a este número de WhatsApp para que puedas revisar tu pedido.\n\nEnlace al pedido: " + response.url;
                //Agregamos el link al objeto del modal y al botón de enviar mensaje
                $("#urlPedido").html('<a target="_blank" href="'+response.url+'">'+ response.url +'</a>)');
                //Constriuimos el link de whatsapp con el mensaje y el teléfono
                var urlbuild = new URL("https://api.whatsapp.com/send?phone=52"+telefono+"&text="+mensaje);

                $("#linkWhatsapp").attr("href", urlbuild.toString());
            },
            error: function(){
                $("#urlPedido").html("No se pudo obtener el link del pedido, intente de nuevo más tarde.");
                $("#linkWhatsapp").addClass("d-none");
            }
        });
    });

    //Creamos un evento para la clase btnWhatsappAviso para que al dar click en el boton de whatsapp, se abra una nueva ventana con el link de whatsapp
    $(document).on("click", ".btnWhatsappAviso", function(){
        //console.log("Click en whatsapp");
        var telefono = $(this).attr("telefono");
        var establecimiento = $(this).attr("establecimiento");

        // Mostramos el link de whatsapp
        $('#linkWhatsappAviso').removeClass("d-none");
        // Creamos un mensaje con el link del pedido
        var mensaje = "*"+establecimiento+"* Te escribimos de *Distribuidora El Toro*.\n\n En breve te marcaremos de este número para saber si deseas que te surtamos mercancía.\n\nTe sugerimos que nos guardes en tus contactos si sueles no contestar llamadas de números desconocidos.\n\n¡Gracias!";
        //Agregamos el link al objeto del modal y al botón de enviar mensaje
        $("#urlAviso").html('<a target="_blank" href="https://api.whatsapp.com/send?phone=52'+telefono+'&text='+mensaje+'">https://api.whatsapp.com/send?phone=52'+telefono+'</a>');
        //Constriuimos el link de whatsapp con el mensaje y el teléfono
        var urlbuild = new URL("https://api.whatsapp.com/send?phone=52"+telefono+"&text="+mensaje);

        //Damos valores a los atributos del botón para poder confirmar que se ha enviado el mensaje
        $("#linkWhatsappAviso").attr("idCliente", $(this).attr("id"));
        $("#linkWhatsappAviso").attr("idTelefono", telefono);
        //Agregamos el nombre del establecimiento al modal
        $("#nombreEstablecimientoAviso").html(establecimiento);
        //Mostramos el modal obligando al usuario a enviar el mensaje y confirmar que se ha hecho
        $('#modalWhatsappAviso').modal({
            backdrop: 'static',
            keyboard: false, 
            show: true 
        });
    });

    //Creamos un evento para el botón de confirmar envío de aviso
    $(document).on("click", ".btnConfirmarAviso", function(){
        // console.log("Click en confirmar aviso");
        var idCliente = $(this).attr("idCliente");
        var idTelefono = $(this).attr("idTelefono");
        var telefono = $(this).attr("telefono");
        //Creamos un ajax para almacenar el envío de aviso
        $.ajax({
            url: "ajaxSetAvisoCliente.php",
            type: "POST",
            data: {
                idCliente: idCliente,
                idTelefono: idTelefono,
                telefono: telefono
            },
            dataType: "json",
            success: function(response){
                // console.log("Respuesta de almacenamiento de aviso: "+JSON.stringify(response));
                muestraMensaje(response.msg, response.status);
                //Cerramos el modal
                $('#modalWhatsappAviso').modal('hide');
                //Tambien ocultamos el boton de la clase btnWhatsappAviso para que no se pueda enviar otro mensaje lo podemos encontrar por que tiene la clase btnWhatsappAviso y tiene un id unico
                $(".btnWhatsappAviso[id='"+idCliente+"']").addClass("d-none");
                //Agremos en texto el telefono que se utilizó para enviar el mensaje
                $(".telefonoFormatCliente[id='"+idCliente+"']").removeClass("d-none");

                
            },
            error: function(){
                alert("No se pudo enviar el aviso al cliente, intente de nuevo más tarde.");
                //Cerramos el modal
                $('#modalWhatsappAviso').modal('hide');
            }
        });
    });


    //Creamos una función para que al dar click en el botón de incidencia, se abra el modal de incidencia
    $(document).on("click", ".btnIncidencia", function(){
        var idCliente = $(this).attr("cliente");
        var nombreRuta = $(this).attr("nombreRuta");
        var idOficina = $(this).attr("oficina");
        var idRuta = $(this).attr("ruta");
        var nombreCliente = $(this).attr("establecimiento");
        var telpreferente = $(this).attr("telpreferente");
        //Asignamos los valores a los campos del modal
        $("#idCliente").val(idCliente);
        $("#idRuta").val(idRuta);
        $("#idOficina").val(idOficina);
        $("#telPreferente").val(telpreferente);
        $("#nombreCliente").html('<b>'+nombreCliente+'</b>');
        //Posicionamos el select en la primera opción
        $("#idIncidencia").prop("selectedIndex", 0);
        //Limipamos el textarea
        $("#notaIncidencia").val("");
        //Verificamos si el textarea tiene la clase d-none
        if(!$("#notaIncidencia").hasClass("d-none")){
            //Agregamos la clase d-none
            $("#notaIncidencia").addClass("d-none");
        }
        //Abrimos el modal
        $("#modalIncidencia").modal("show");
    });

</script>

<script>
    $('#misRutasTable').DataTable( {
        dom: 'frti',
        "order": [],
        language: {
            "url": "js/spanish.js"
        },
        stripeClasses: [],
        columnDefs: [
            {
                "targets": [ 7 ],
                "visible": false,
                "searchable": true
                // "width": 30
            },
            {
                "targets": [ 0 ],
                "visible": false
                // "width": 30
            },
            {
                "targets": [ 6 ],
                "visible": true,
                "width": 275
            },
            {
                "targets": [ 4 ],
                "visible": true,
                "width": 65
            }
        ],
        paging: false // Deshabilita la paginación
	});
</script>