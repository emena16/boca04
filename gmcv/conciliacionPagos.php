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
require 'models/Compra.php';
require 'models/CompraFactura.php';
?>
<!-- Importamos los estilos necesarios para la vista -->
<link rel="stylesheet" href="css/estilos.css">
<!-- estilos para el select2 -->
<link rel="stylesheet" type="text/css" href="../../../sys/bocampana_vista/plugins/select2/select2.min.css">
<link rel="stylesheet" type="text/css" href="../../../sys/bocampana_vista/plugins/select2/es.js">

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

.feather-16 {
    width: 16px;
    height: 16px;
}

#tablaProductosFacturaAlmacen+#tablaAlineadaDerecha {
    float: right;
    margin-left: 10px;
}

/* Estilos personalizados para opciones de Select2 */
.select2-results__option {
    font-size: 12px;
    /* Tamaño de la fuente */
    padding: 4px 8px;
    /* Espaciado interno de la opción */
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
/* .select2-selection__rendered {
        background-color: #f1f2f3 !important;
    } */
.btn-small {
    font-size: 0.2em; /* Reduce el tamaño de la fuente al 50% */
    padding: 0.2em 1em; /* Ajusta el relleno para mantener la proporción */
    /* width: 40px;
    height: 30px; */
}

.feather-8 {
    width: 8px;
    height: 10px;
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
    <div class="pge-title" style="display: flex; justify-content: space-between; align-items: center; margin-left: 3.5%;">
        <h3>&nbsp; Conciliación de pagos</h3>
        <button type="button" class="btn btn-info btn-circle" data-toggle="modal" data-target="#modalAyuda" style="border-radius: 50%; width: 40px; height: 40px; display: flex; justify-content: center; align-items: center; margin-right: 20px;" title="Ayuda para la vista">?</button>
    </div>
</div>


<div class="card card-principal">
    <div class="card-body">
        <!-- <div class="row">
            <div class="col-md-4">
                <a href="validaCostos.php"><button class="mt-lg-4 btn btn-info btn-lg"><i style=" color: #f6fcfb;" data-feather="search"></i> Consulta Avanzada </button></a>
            </div>
        </div> -->
        <div class="row">
            <div class="col-md-4 ">
                <label for="miSelect">Selecciona un proveedor:</label>
                <select id="selectProveedor">
                    <option value="0">Seleccione un proveedor</option>
                    <?php
                        $proveedores = CompraFactura::getProveedoresConciliar();
                        foreach ($proveedores as $proveedor) {
                            echo "<option value='".$proveedor['id']."'>".$proveedor['corto']."</option>";
                        }
                    ?>
                </select>
            </div>
            <!-- FECHAS -->
            <div class="col-md-2">
                <label for="fechaInicio">Fecha inicio:</label>
                <input type="date" id="fechaInicio" class="form-control" value="<?=date('Y-m-d' ,strtotime('-1 month'))?>">
            </div>
            <div class="col-md-2">
                <label for="fechaFin">Fecha fin:</label>
                <input type="date" id="fechaFin" class="form-control" value="<?=date('Y-m-d')?>">
            </div>
            <!-- Estado de la factura: Todas, Aceptada, Vigente, Por Validar -->
            <div class="col-md-2">
                <label for="estadoFactura">Estado de la factura:</label>
                <select id="estadoFactura" class="form-control">
                    <option value="0">Todas</option>
                    <option value="1">Aceptada</option>
                    <option value="4">Vigente</option>
                    <option value="23">Por Validar</option>
                </select>
            </div>
            <div class="col-md-2">
                <button id="btnBuscarFacturas" class="mt-lg-4 btn btn-primary btn-lg"><i style=" color: #f6fcfb;" data-feather="search"></i> Buscar Facturas </button>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="row justify-content-center">
                    <div id="divMessage" class="col-md-6"></div>
                </div>
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
            <div id="divTablaFacturas" class="col-md-12 col-lg-12">
                <!-- Aqui vamos a pintar el datatable con las facturas pendientes -->
            </div>
        </div>
    </div> <!-- fin card-body -->
</div> <!-- fin card-principal -->



<!--- Agregamos una tarjeta principal nueva para gestionar las notas de credito del proveedor -->
<div class="page-header layout-top-spacing title-header">
    <div class="pge-title" style="margin-left: 3.5%;">
        <h3>&nbsp; Notas de crédito </h3>
    </div>
</div>
<!-- Card principal para gestionar las notas de credito -->
<div class="card card-principal">
    <div class="card-body">
        <!-- Agregamo un row para agregar un boton de agregar nota de credito  alineaando el boton a la derecha -->
        <div class="row">
            <div class="col-md-12">
                <button documento="1" disabled class="btn btn-success btn-sm float-right mb-4 btnAgregarDocumentoPago"><i data-feather="plus"></i> Agregar Nota de Crédito</button>
                <button documento="1" disabled class="btn btn-primary btn-sm float-right mb-4 mr-lg-4 btnConsultarDocumentoPago"><i data-feather="search"></i> Consultar</button>

            </div>
        </div>
        <div class="row">            
            <div id="divTablaNotasCR" class="col-md-12 col-lg-12">
                <!-- Pintamos un datatable con los documentos de pago del proveedor --> 
                <table id="tablaNotasCR" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>Proveedor</th>
                            <th>Fecha</th>
                            <th>Monto</th>
                            <th>Saldo</th>
                            <th>Id Nota de Crédito</th>
                            <th>Facturas</th>
                            <th>Editar</th>
                    </thead>
                    <tbody>
                        <!-- Aqui vamos a pintar los documentos de pago del proveedor -->
                    </tbody>
                </table>
            </div>
        </div>
    </div> <!-- fin card-body -->
</div> <!-- fin card-principal -->


<!--- Agregamos una tarjeta principal nueva para gestionar las notas de credito del proveedor -->
<div class="page-header layout-top-spacing title-header">
    <div class="pge-title" style="margin-left: 3.5%;">
        <h3>&nbsp; Documentos de Pago</h3>
    </div>
</div>
<!-- Card principal para gestionar las notas de credito -->
<div class="card card-principal">
    <div class="card-body">
        <!-- Agregamo un row para agregar un boton de agregar nota de credito  alineaando el boton a la derecha -->
        <div class="row">
            <div class="col-md-12">
                <button documento="0" disabled class="btn btn-success btn-sm btnAgregarDocumentoPago float-right mb-4"><i data-feather="plus"></i> Agregar Documento Pago</button>
                <button documento="0" disabled class="btn btn-primary btn-sm float-right mb-4 mr-lg-4 btnConsultarDocumentoPago"><i data-feather="search"></i> Consultar</button>
            </div>
        </div>
        <div class="row">
            <div id="divTablaDocsPago" class="col-md-12 col-lg-12">
                <!-- Pintamos un datatable con los documentos de pago del proveedor --> 
                 <table id="tablaDocsPago" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>Proveedor</th>
                            <th>Fecha</th>
                            <th>Monto</th>
                            <th>Saldo</th>
                            <th>Id Pago</th>
                            <th>Facturas</th>
                            <th>Editar</th>
                    </thead>
                    <tbody>
                        <!-- Aqui vamos a pintar los documentos de pago del proveedor -->
                    </tbody>
                </table>

            </div>
        </div>
    </div> <!-- fin card-body -->
</div> <!-- fin card-principal -->

<!-- Creamos un modal para mostrar ayuda de que hacer en la vista -->
<div class="modal fade" id="modalAyuda" tabindex="-1" role="dialog" aria-labelledby="modalAyudaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <input type="hidden" id="idProveedor" value="" name="idProveedor">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAyudaLabel">Ayuda para la vista de conciliación de pagos</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <h5>Indicadores de colores para esta vista</h5>
                        <table class="table table-sm" border="1" style="border-collapse: collapse;">
                            <tr>
                                <th style="text-align: center;">Fechas</th>
                                <th style="text-align: center;">Saldos</th>
                            </tr>

                            <tr>
                                <th style="background-color: red; color: white; ">Saldo Vencido</th>
                                <th style="background-color: white; ">Sin abonos</th>
                            </tr>

                            <tr>
                                <th style="background-color: orange;">Saldo por vencer</th>
                                <th style="background-color: yellow;">Con abonos (Saldo pendiente)</th>
                            </tr>

                            <tr>
                                <th style="text-align: center;"> -- </th>
                                <th style="background-color: green; color: white;">Saldo pagado</th>

                        </table>
                    </div>
                    
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para agregar abonos a las facturas -->
<div class="modal fade" id="modalAbonosFactura" tabindex="-1" role="dialog" aria-labelledby="modalAbonosFacturaLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <!-- Titulo del modal -->
            <div class="modal-header">
                <h5 class="modal-title" id="modalAbonosFacturaLabel">Abonos a factura</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" style="font-size: 1.5em;">&times;</span>
                </button>
            </div>
            <!-- Cuerpo del modal -->
            <div class="modal-body mb-lg-3">
                <div class="row mb-lg-4">

                    <div class="col-md-3">
                        <label for="fechaAbono">Saldo Pendiente:</label>
                        <input type="text" id="saldoPendiente" class="form-control">
                        <input hidden type="date" id="fechaAbono" class="form-control">
                    </div>
                    <div class="col-md-5">
                        <label for="documentoPago">Seleccione documento:</label>
                        <select class="form-control" name="documentoPago" id="documentoPago"></select>
                        <small class="form-text text-muted">
                         [ Valor documento / Saldo disponible]
                        </small>
                    </div>
                    <div class="col-md-4">
                        <label for="montoAbono">Monto del abono:</label>
                        <input type="number" id="montoAbono" class="form-control" placeholder="0.00">
                    </div>

                </div> <!-- Cierre de la fila -->

            </div>
            <!-- Pie del modal -->
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="btnGuardarAbonoFactura"><i data-feather="save"></i> Guardar</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i data-feather="x"></i> Cerrar</button>
            </div>
        </div>
    </div>
</div>


<!-- Modal para agregar abonos a las facturas -->
<div class="modal fade" id="modalEliminarAbonosFactura" tabindex="-1" role="dialog" aria-labelledby="modalAbonosFacturaLabel" aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <!-- Titulo del modal -->
            <div class="modal-header">
                <h5 class="modal-title" id="modalAbonosFacturaLabel">Eliminar abonos a factura</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" style="font-size: 1.5em;">&times;</span>
                </button>
            </div>
            <!-- Cuerpo del modal -->
            <div class="modal-body mb-lg-3">
                <div class="row mb-lg-4">

                    <!-- <div class="col-md-3">
                        <label for="fechaAbono">Saldo Pendiente:</label>
                        <input type="text" id="saldoPendiente" class="form-control">
                        <input hidden type="date" id="fechaAbono" class="form-control">
                    </div> -->
                    <div class="col-md-12">
                        <label for="documentoPago">Seleccione documento:</label>
                        <select class="form-control" name="documentoPagoEliminar" id="documentoPagoEliminar"></select>
                        <!-- <small class="form-text text-muted">
                         [ Valor documento / Saldo Abonado]
                        </small> -->
                    </div>
                    <!-- <div class="col-md-4">
                        <label for="montoAbono">Monto del abono:</label>
                        <input type="number" id="montoAbono" class="form-control" placeholder="0.00">
                    </div> -->

                </div> <!-- Cierre de la fila -->

            </div>
            <!-- Pie del modal -->
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" id="btnDelateAbonoFactura"><i data-feather="trash"></i> Eliminar</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i data-feather="x"></i> Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para agregar documentos de pago -->
<div class="modal fade" id="modalAgregarDocumentoPago" tabindex="-1" role="dialog" aria-labelledby="modalAgregarDocumentoPagoLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <!-- Titulo del modal -->
            <div class="modal-header">
                <h5 class="modal-title" id="tituloAgregarDocumento"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" style="font-size: 1.5em;">&times;</span>
                </button>
            </div>
            <!-- Cuerpo del modal -->
            <div class="modal-body">
                <div class="row mb-lg-4">
                    <div class="col-md-3">
                        <label for="fechaDocumentoPago">Fecha Documento:</label>
                        <input type="date" id="fechaDocumentoPago" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label for="montoDocumentoPago">Monto:</label>
                        <input type="number" id="montoDocumentoPago" class="form-control" placeholder="0.00">
                    </div>
                    <div class="col-md-3">
                        <label for="uuidDocumentoPago">UUID:</label>
                        <input type="text" id="uuidDocumentoPago" class="form-control">
                    </div>
                </div> <!-- Cierre de la fila -->
            </div>
            <!-- Pie del modal -->
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="btnGuardarDocumentoPago"><i data-feather="save"></i> Guardar</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i data-feather="x"></i> Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para agregar documentos de pago -->
<div class="modal fade" id="modalEditarDocumentoPago" tabindex="-1" role="dialog" aria-labelledby="modalEditarDocumentoPagoLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <!-- Titulo del modal -->
            <div class="modal-header">
                <h5 class="modal-title" id="tituloEditarDocumento">Editar Documento de pago</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" style="font-size: 1.5em;">&times;</span>
                </button>
            </div>
            <!-- Cuerpo del modal -->
            <div class="modal-body">
                <div class="row mb-lg-4">
                    <div class="col-md-3">
                        <label for="fechaDocumentoPago">Fecha Documento:</label>
                        <input type="date" id="fechaDocumentoPagoEdit" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label for="montoDocumentoPago">Monto:</label>
                        <input type="number" id="montoDocumentoPagoEdit" class="form-control" placeholder="0.00">
                    </div>
                    <div class="col-md-3">
                        <label for="uuidDocumentoPago">UUID:</label>
                        <input type="text" id="uuidDocumentoPagoEdit" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label for="uuidDocumentoPago">Tipo Documento:</label>
                        <select class="form-control" name="tipoDocumentoPagoEdit" id="tipoDocumentoPagoEdit">
                            <option value="0">Documento de Pago</option>
                            <option value="1">Nota de Crédito</option>
                        </select>
                    </div>
                </div> <!-- Cierre de la fila -->
            </div>
            <!-- Pie del modal -->
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="btnGuardarDocumentoPagoEditar"><i data-feather="save"></i> Guardar</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i data-feather="x"></i> Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para consultar documentos de pago en un rango de fechas -->
<div class="modal fade" id="modalConsultarDocumentoPago" tabindex="-1" role="dialog" aria-labelledby="modalConsultarDocumentoPagoLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <!-- Titulo del modal -->
            <div class="modal-header">
                <h5 class="modal-title" id="tituloConsultarDocumentoPago">Consultar Documentos de Pago en un rango de fechas</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" style="font-size: 1.5em;">&times;</span>
                </button>
            </div>
            <!-- Cuerpo del modal -->
            <div class="modal-body">
                <div class="row mb-lg-4">
                    <div class="col-md-3">
                        <label for="fechaInicioDocumentoPago">Fecha Inicio:</label>
                        <input type="date" id="fechaInicioDocumentoPago" class="form-control" value="<?=date('Y-m-d' ,strtotime('-1 month'))?>">
                    </div>
                    <div class="col-md-3">
                        <label for="fechaFinDocumentoPago">Fecha Fin:</label>
                        <input type="date" id="fechaFinDocumentoPago" class="form-control" value="<?=date('Y-m-d')?>">
                    </div>
                </div> <!-- Cierre de la fila -->
            </div>
            <!-- Pie del modal -->
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="btnBuscarPagos"><i data-feather="search"></i> Consultar</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i data-feather="x"></i> Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Script para el select2 -->
<script src="../../../sys/bocampana_vista/plugins/select2/select2.min.js"></script>
<script src="../../../sys/bocampana_vista/plugins/select2/es.js"></script>

<!-- Scripts de funciones generales -->
<script src="js/functions.js"></script>

<script>
    //Variable global para almacenar el nombre del proveedor
    var nombreProveedor = '';

    $(document).ready(function() {
        //Inicializamos el select de proveedores
        $('#selectProveedor').select2({
            language: 'es',
            multiple: false,
            closeOnSelect: true,
            search: true,
            placeholder: "Seleccione un proveedor"
        });

        //Creamos un eventos que se dispare al hacer click en el boton de buscar facturas
        $('#btnBuscarFacturas').click(function(){
            //Verificamos que se haya seleccionado un proveedor
            var proveedor = $('#selectProveedor').val();
            if(proveedor == 0){
                alert("Seleccione un proveedor");
                return;
            }

            pintaFacturas();
            pintaNotasCredito();
            feather.replace();
        });

        //Inicializamos el datatable de notas de credito y documentos de pago
        $('#tablaNotasCR').DataTable( {
            dom: 'Bfrti',
            language: {
                "url": "js/spanish.js"
            },
            order: [],
            stripeClasses: [], 
            paging: false,
            buttons: [{
                extend: 'excel',
                text: 'Exportar a Excel', 
                className: 'btn btn-sm btn-info',
                filename: function() {
                    var fecha = new Date().toISOString().slice(0, 10);
                    return nombreProveedor + '_Reporte_NotasCR_' + fecha;
                }                
            }]
            
        });

        $('#tablaDocsPago').DataTable( {
            dom: 'Bfrti',
            language: {
                "url": "js/spanish.js"
            },
            order: [],
            stripeClasses: [], 
            paging: false,
            buttons: [{
                extend: 'excel',
                text: 'Exportar a Excel', // Texto del botón
                className: 'btn btn-sm btn-info',
                filename: function() {
                    // Puedes usar la fecha actual o cualquier otra lógica para el nombre del archivo
                    var fecha = new Date().toISOString().slice(0, 10); // Fecha en formato YYYY-MM-DD
                    return nombreProveedor + '_Reporte_DocsPago_' + fecha;
                }
            }] 
        });


        //Creamos un evento que se dispare al hacer click en el boton de agregar abonos a una factura
        $('#divTablaFacturas').on('click', '.btnAddAbono', function(){
            //Obtenemos el id de la factura
            var idFactura = $(this).attr('id');
            var proveedor = $('#selectProveedor').val();
            $.ajax({
                url: 'services/mainService.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'getSaldoFactura',
                    controller: "CompraFactura",
                    args: {
                        'id_factura': idFactura,
                        'id_proveedor': proveedor
                    }
                },
                success: function(response) {
                    // console.log(response);

                    let documentos = response.documentos;
                    let saldoFactura = response.saldoFactura;

                    $('#montoAbono').val('');
                    //Actualizamos el id de la factura en el boton de guardar abono
                    $('#btnGuardarAbonoFactura').attr('idFactura', idFactura);
                    $('#saldoPendiente').val(toMoney(saldoFactura.saldoPendiente));
                    $('#montoAbono').val(parseFloat(saldoFactura.saldoPendiente).toFixed(4));
                    //Actualizamo fecha abono a la fecha actual
                    $('#fechaAbono').val(new Date().toISOString().slice(0, 10));

                    //Recorremos los documentos de pago y los agregamos al select
                    $('#documentoPago').html('');
                    documentos.forEach(documento => {
                        $('#documentoPago').append('<option value="'+documento.id_pago+'">'+documento.pago_uuid+ ' - ['+toMoney(documento.monto_pago) +' / '+toMoney(documento.saldo_disponible)+']</option>');
                    });


                    //Abrimos el modal para agregar abonos a la factura
                    $('#modalAbonosFactura').modal('show');
                    
                },
                error: function(response){
                    // console.log(response);
                    alert("Error al obtener el saldo de la factura");
                }
            }); // Fin de la llamada ajax
        });

        //Creamos un evento que se dispare al hacer click en el boton de eliminar abonos a una factura
        $('#divTablaFacturas').on('click', '.btnRemoveAbono', function(){
            //Obtenemos el id de la factura
            var idFactura = $(this).attr('id');
            var proveedor = $('#selectProveedor').val();
            //Llamamos al controlador para obtener los documentos de pago del proveedor
            $.ajax({
                url: 'services/mainService.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'getSaldoFactura',
                    controller: "CompraFactura",
                    args: {
                        'id_factura': idFactura,
                        'id_proveedor': proveedor
                    }
                },
                success: function(response) {
                    //Agregamos el id de la factura al boton de eliminar abono
                    $('#btnDelateAbonoFactura').attr('idFactura', idFactura);
                    //Recorremos los documentos de pago y los agregamos al select
                    $('#documentoPagoEliminar').html('');
                    //los pagos estan en una cadena separada por comas
                    let uuidsAbonos = response.saldoFactura.uuidsAbonos.split(',');
                    let idsAbonos = response.saldoFactura.idsAbonos.split(',');
                    $('#documentoPagoEliminar').append('<option selected disabled value="">Seleccione pago a eliminar</option>');
                    uuidsAbonos.forEach((uuid, index) => {
                        $('#documentoPagoEliminar').append('<option value="'+idsAbonos[index]+'">'+uuid+'</option>');
                    });
                    //Abriremos el modal para eliminar abonos
                    $('#modalEliminarAbonosFactura').modal('show');
                },
                error: function(response){
                    alert("Error al obtener el saldo de la factura");
                }
            }); // Fin de la llamada ajax
        });
    

        // //Formateamos a moneda el monto del abono cada vez que se modifica por el usuario
        // $('#montoAbono').on('input', function() {
        //     // Remueve todo excepto los números y el punto decimal
        //     let valor = $(this).val().replace(/[^0-9.]/g, '');
        //     // Asegúrate de que solo haya un punto decimal en el valor
        //     let partes = valor.split('.');
        //     let parteEntera = partes[0];
        //     let parteDecimal = partes[1] ? partes[1].substring(0, 2) : '';
        //     // console.log("Parte entera: "+parteEntera + " Parte decimal: "+parteDecimal);

        //     // Aplica formato de comas a la parte entera
        //     parteEntera = parteEntera.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        //     // Combina las partes con el símbolo de moneda
        //     let valorFormateado = '$' + parteEntera + (parteDecimal ? '.' + parteDecimal : '');
        //     // Actualiza el valor del input con el formato de moneda
        //     $(this).val(valorFormateado);
        //     // console.log(valorFormateado);
        // });

        //Creamos un evento que se dispare al hacer click en el boton de eliminar abono a factura
        $('#btnDelateAbonoFactura').click(function(){
            var idFactura = $(this).attr('idFactura');
            var idDocumentoPago = $('#documentoPagoEliminar').val();
            if(!idDocumentoPago){
                alert("Seleccione un documento de pago");
                return;
            }
            
            $.ajax({
                url: 'services/mainService.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'eliminarAbonoFactura',
                    controller: "GmcvPago",
                    args: {
                        'id_factura': idFactura,
                        'id_documento_pago': idDocumentoPago
                    }
                },
                success: function(response) {
                    pintaFacturas();
                    pintaNotasCredito();
                    $('#modalEliminarAbonosFactura').modal('hide');
                    setTimeout(function(){
                        messageAlert(response.message, 'success',false);
                    }, 200);
                },
                error: function(response){
                    alert("Error al eliminar el abono");
                }
            }); // Fin de la llamada ajax
        });

        //Creamos une evento que se dispare al hacer click en el boton de guardar abono a factura btnGuardarAbonoFactura
        $('#btnGuardarAbonoFactura').click(function(){
            var idFactura = $(this).attr('idFactura');
            var idDocumentoPago = $('#documentoPago').val();
            var montoAbono = $('#montoAbono').val();
            var fechaAbono = $('#fechaAbono').val();
            if(!idDocumentoPago){
                alert("Seleccione un documento de pago");
                return;
            }
            if(montoAbono <= 0){
                alert("El monto del abono debe ser mayor a 0");
                return;
            }
            console.log("Argumentos Enviados: "+
                "id_factura: "+idFactura+
                " id_documento_pago: "+idDocumentoPago+
                " monto_abono: "+montoAbono+
                " fecha_abono: "+fechaAbono
            );
            
            $.ajax({
                url: 'services/mainService.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'guardarAbonoFactura',
                    controller: "GmcvPago",
                    args: {
                        'id_factura': idFactura,
                        'id_documento_pago': idDocumentoPago,
                        'monto_abono': montoAbono,
                        'fecha_abono': fechaAbono
                    }
                },
                success: function(response) {
                    // console.log(response);
                    if(response.status){
                        pintaFacturas();
                        pintaNotasCredito();
                        $('#modalAbonosFactura').modal('hide');
                        setTimeout(function(){
                            messageAlert(response.message, 'success',false);
                        }, 200);
                    }else{
                        alert(response.message);
                    }
                },
                error: function(response){
                    alert("Error al guardar el abono");
                }
            }); // Fin de la llamada ajax
        });

        //Creamos un evento pra agregar un documento de pago al proveedor a través de un modal
        $('.btnAgregarDocumentoPago').click(function(){
            //Limpiamos el modal antes de abrirlo
            $('#fechaDocumentoPago').val('');
            $('#montoDocumentoPago').val('');
            $('#uuidDocumentoPago').val('');
            $('#btnGuardarDocumentoPago').attr('idDocumentoPago', '');
            $('tituloAgregarDocumento').html('');
            var documento = $(this).attr('documento');
            //Actualizamos el titulo del modal
            if(documento == 0){
                $('#tituloAgregarDocumento').html('Agregar documento de pago');
            }else{
                $('#tituloAgregarDocumento').html('Agregar nota de crédito');
            }
            //Agregamos el tipo de documento al boton de guardar 
            $('#btnGuardarDocumentoPago').attr('documento', documento);
            //Abrimos el modal
            $('#modalAgregarDocumentoPago').modal('show');
        });

        //Creamos un evento que se dispare al hacer click en el boton de guardar documento de pago
        $('#btnGuardarDocumentoPago').click(function(){
            //Obtenemos los valores de los campos
            var fechaDocumentoPago = $('#fechaDocumentoPago').val();
            var montoDocumentoPago = $('#montoDocumentoPago').val();
            var uuidDocumentoPago = $('#uuidDocumentoPago').val();
            var documento = $(this).attr('documento');
            //Validamos que la fecha del documento no sea mayor a la fecha actual
            if(fechaDocumentoPago > new Date().toISOString().slice(0, 10)){
                alert("La fecha del documento no puede ser mayor a la fecha actual");
                return;
            }
            //Validamos que el monto del documento sea mayor a 0
            if(montoDocumentoPago <= 0){
                alert("El monto del documento debe ser mayor a 0");
                return;
            }
            //Validamos que el uuid del documento no este vacio
            if(uuidDocumentoPago == ''){
                alert("El UUID del documento no puede estar vacio");
                return;
            }
            //Llamamos al controlador para guardar el documento de pago
            $.ajax({
                url: 'services/mainService.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'nuevoDocumentoPago',
                    controller: "GmcvPago",
                    args: {
                        'fecha_documento': fechaDocumentoPago,
                        'monto_documento': montoDocumentoPago,
                        'uuid_documento': uuidDocumentoPago,
                        'documento': documento,
                        'id_proveedor': $('#selectProveedor').val()
                    }
                },
                success: function(response) {
                    if(response.status){
                        pintaFacturas();
                        pintaNotasCredito();
                        //Cerramos el modal
                        $('#modalAgregarDocumentoPago').modal('hide');
                        setTimeout(function(){
                            messageAlert(response.message, 'success',false);
                        }, 200);
                    }else{
                        messageAlert(response.message, 'warning',false);
                    }
                },
                error: function(response){
                    alert("Error al guardar el documento de pago");
                }
            }); // Fin de la llamada ajax
        });

        //Creamos une evengo para cuando selectProveedor cambie
        $('#selectProveedor').change(function(){
            $('#divTablaFacturas').html('');
            $('#tablaNotasCR').DataTable().clear().draw();
            $('#tablaDocsPago').DataTable().clear().draw();
            $('#divMessage').empty();
        });

        $(document).on('click', '.btnEditPago', function(){
            var idDocumentoPago = $(this).attr('idPago');
            $.ajax({
                url: 'services/mainService.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'getDocumentoPago',
                    controller: "GmcvPago",
                    args: {
                        'id_documento_pago': idDocumentoPago,
                    }
                },
                success: function(response) {
                    // console.log(response);
                    //Actualizamos los campos del modal de editar documento de pago
                    $('#fechaDocumentoPagoEdit').val(response.fecha);
                    $('#montoDocumentoPagoEdit').val(response.monto);
                    $('#uuidDocumentoPagoEdit').val(response.uuid);
                    $('#tipoDocumentoPagoEdit').val(response.tipo);
                    $('#tituloEditarDocumento').html('Editar documento de pago');
                    $('#btnGuardarDocumentoPagoEditar').attr('idDocumentoPago', idDocumentoPago);
                    $('#modalEditarDocumentoPago').modal('show');
                },
                error: function(response){
                    alert("Error al obtener el documento de pago");
                }
            }); // Fin de la llamada ajax
        });

        //Creamos un evento que se dispare al hacer click en el boton de guardar documento de pago editado
        $('#btnGuardarDocumentoPagoEditar').click(function(){
            //Obtenemos los valores de los campos
            var fechaDocumentoPago = $('#fechaDocumentoPagoEdit').val();
            var montoDocumentoPago = $('#montoDocumentoPagoEdit').val();
            var uuidDocumentoPago = $('#uuidDocumentoPagoEdit').val();
            var tipoDocumentoPago = $('#tipoDocumentoPagoEdit').val();
            var idDocumentoPago = $(this).attr('idDocumentoPago');

            //Validamos que la fecha del documento no sea mayor a la fecha actual
            if(fechaDocumentoPago > new Date().toISOString().slice(0, 10)){
                alert("La fecha del documento no puede ser mayor a la fecha actual");
                return;
            }
            //Validamos que el monto del documento sea mayor a 0
            if(montoDocumentoPago <= 0){
                alert("El monto del documento debe ser mayor a 0");
                return;
            }
            //Validamos que el uuid del documento no este vacio
            if(uuidDocumentoPago == ''){
                alert("El UUID del documento no puede estar vacio");
                return;
            }
            //Llamamos al controlador para guardar el documento de pago
            $.ajax({
                url: 'services/mainService.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'updateDocumentoPago',
                    controller: "GmcvPago",
                    args: {
                        'fecha_documento': fechaDocumentoPago,
                        'monto_documento': montoDocumentoPago,
                        'uuid_documento': uuidDocumentoPago,
                        'id_documento_pago': idDocumentoPago,
                        'tipo_documento': tipoDocumentoPago
                    }
                },
                success: function(response) {
                    if(response.status){
                        pintaFacturas();
                        pintaNotasCredito();
                        $('#modalEditarDocumentoPago').modal('hide');
                        setTimeout(function(){
                            messageAlert(response.message, 'success',false);
                        }, 200);
                    }else{
                        messageAlert(response.message, 'warning',false);
                    }
                },
                error: function(response){
                    alert("Error al actualizar el documento de pago");
                }
            }); // Fin de la llamada ajax
        });

        //Creamos un evento para cuando se pulse .btnConsultarDocumentoPago llame al modal de consultar documentos de pago
        $('.btnConsultarDocumentoPago').click(function(){
            //Agregamos un atributo al boton de buscar pagos para saber que tipo de documento es
            $('#btnBuscarPagos').attr('documento', $(this).attr('documento'));
            $('#modalConsultarDocumentoPago').modal('show');
        });

        $('#btnBuscarPagos').click(function(){
            var fechaInicio = $('#fechaInicioDocumentoPago').val();
            var fechaFin = $('#fechaFinDocumentoPago').val();
            if(fechaInicio == '' || fechaFin == ''){
                alert("Seleccione un rango de fechas");
                return;
            }
            //Verificamos que haya seleccionado un proveedor
            if($('#selectProveedor').val() == ''){
                alert("Seleccione un proveedor");
                return;
            }

            //Verificamos que las fechas sean consistentes
            if(fechaInicio > fechaFin){
                alert("La fecha de inicio no puede ser mayor a la fecha fin");
                return;
            }
            // console.log("Argumentos: "+fechaInicio+" "+fechaFin+" "+$(this).attr('documento')+" "+$('#selectProveedor').val() + "documento: "+$(this).attr('documento'));
            var documento = $(this).attr('documento');
            $.ajax({
                url: 'services/mainService.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'getDocumentosPagoRangoFechas',
                    controller: "GmcvPago",
                    args: {
                        'fecha_inicio': fechaInicio,
                        'fecha_fin': fechaFin,
                        'tipoDocumento': $(this).attr('documento'),
                        'id_proveedor': $('#selectProveedor').val()
                    }
                },
                success: function(response) {
                    // console.log(response);
                    var tabla = (documento == 0) ? 'tablaDocsPago' : 'tablaNotasCR';
                    var titlebtn = (documento == 0) ? 'Editar Documento de Pago' : 'Editar Nota de Crédito';
                    nombreProveedor = $('#selectProveedor option:selected').text();
                    var btnDocumentoDisabled = '';
                    $('#'+tabla).DataTable().clear().draw();
                    response.forEach(documento => {
                        btnDocumentoDisabled = (documento.facturas) ? '' : 'disabled';
                        let disabled = documento.facturas ? 'disabled' : '';
                        let btnEdit = '<button title="'+titlebtn+'" '+disabled+' class="btn btn-info btn-sm ml-2 btnEditPago" idPago="'+documento.id_pago+'" style="padding: 2px 5px; font-size: 12px;"><i data-feather="edit-2"></i></button>';
                        $('#'+tabla).DataTable().row.add([
                            documento.proveedor,
                            documento.fecha,
                            toMoney(documento.monto_pago),
                            toMoney(documento.saldo_disponible),
                            documento.pago_uuid,
                            documento.facturas,
                            btnEdit
                            
                        ]).draw();
                    });
                    //Actualizamos lo iconos de feather
                    feather.replace();
                    $('#modalConsultarDocumentoPago').modal('hide');
                },
                error: function(response){
                    alert("Error al obtener los documentos de pago");
                }
            }); // Fin de la llamada ajax
        });
        

    }); // Fin document ready

    function pintaFacturas(){
        //Esta funcion se encarga de pintar las facturas en la tabla leyendo los valores de los filtros e invocando al controlador
        var proveedor = $('#selectProveedor').val();
        var fechaInicio = $('#fechaInicio').val();
        var fechaFin = $('#fechaFin').val();
        var estadoFactura = $('#estadoFactura').val();
        $.ajax({
        url: 'services/mainService.php',
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'getFacturasConciliacion',
            controller: "CompraFactura",
            args: {
                'proveedor': proveedor,
                'fechaInicio': fechaInicio,
                'fechaFin': fechaFin,
                'estadoFactura': estadoFactura
            }
        },
        success: function(response) {

            $('#divTablaFacturas').html('');
            var tabla = '<table id="tablaFacturas" class="table table-striped table-bordered" style="width:100%">';
            tabla += '<thead><tr>';
            tabla += '<th><small>Proveedor</small></th>';
            tabla += '<th><small>Factura</small></th>';
            tabla += '<th><small>Bodega</small></th>';
            tabla += '<th><small>Costo Real</small></th>';
            tabla += '<th><small>Ajuste</small></th>';
            tabla += '<th><small>Autorizado</small></th>';
            tabla += '<th><small>Fecha<br>Compromiso</small></th>';
            tabla += '<th><small>Fecha<br>Pago</small></th>';
            tabla += '<th><small>Id<br>Pago</small></th>';
            tabla += '<th><small>Saldo<br>Abonado</small></th>';
            tabla += '<th><small>Saldo<br>Pendiente</small></th>';
            tabla += '<th><small>Acciones</small></th>';
            tabla += '</tr></thead>';
            tabla += '<tbody>';
            //Recorremos las facturas con un foreach
            response.forEach(factura => {
                tabla += '<tr>';
                tabla += '<td>'+factura.proveedor+'</td>';
                tabla += '<td>'+factura.uuid+'</td>';
                tabla += '<td>'+factura.bodega+'</td>';
                tabla += '<td class="dt-right">'+toMoney(factura.total)+'</td>';
                tabla += '<td class="dt-right">'+toMoney(factura.ajuste)+'</td>'; 
                tabla += '<td class="dt-right">'+toMoney(factura.totalAPagar)+'</td>';
                tabla += '<td>'+factura.fecha_alerta+'</td>';
                tabla += '<td bgcolor="'+factura.colorFCompromiso+'">'+factura.fecha_compromiso+'</td>';
                tabla += '<td>'+(factura.uuidsAbonos ? factura.uuidsAbonos : 'No hay Abonos')+'</td>';
                tabla += '<td class="dt-right">'+toMoney(factura.totalAbonado)+'</td>';
                tabla += '<td bgcolor="'+factura.colorSaldo+'" class="dt-right">'+toMoney(factura.saldoPendiente)+'</td>';
                
                let disabledBtn =  factura.saldoPendiente < 0.0001 ? 'disabled' : '';
                let disabledBtnDelate =  factura.uuidsAbonos ? '' : 'disabled';
                let botonAbono = '<button '+ disabledBtn +' title="Abonos a factura" class="btn btn-success btn-sm btnAddAbono" id="'+factura.idFactura+'" style="padding: 2px 5px; font-size: 12px;"><i data-feather="file-plus"></i></button>';
                tabla += '<td align="center">'+
                    botonAbono+ 
                    '<button '+disabledBtnDelate+' title="Eliminar Abonos" class="btn btn-danger btn-sm ml-2 btnRemoveAbono" id="'+factura.idFactura+'" style="padding: 2px 5px; font-size: 12px;"><i data-feather="trash-2"></i></button>';
                '</td>';
                tabla += '</tr>';
            });
            tabla += '</tbody>';
            tabla += '</table>';
            $('#divTablaFacturas').html(tabla);
            //Inicializamos el datatable
            $('#tablaFacturas').DataTable( {
                dom: 'Bfrti',
                language: {
                    "url": "js/spanish.js"
                },
                order: [],
                stripeClasses: [], // Deshabilitamos las rayas de la tabla
                paging: false,
                buttons: [{
                    extend: 'excel',
                    text: 'Exportar a Excel', 
                    className: 'btn btn-sm btn-info',
                    filename: function() {
                        // Puedes usar la fecha actual o cualquier otra lógica para el nombre del archivo
                        var fecha = new Date().toISOString().slice(0, 10); // Fecha en formato YYYY-MM-DD
                        return nombreProveedor + '_Reporte_' + fecha;
                    },
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
                    }
                }] 
            });
            //Actualizamos lo iconos de feather
            feather.replace();
        },
        error: function(response){
            alert("Error al obtener las facturas");
        }}); // Fin de la llamada ajax
    } // Fin de la funcion pintaFacturas


    //Creamos una funcion que pinte las notas de credito del proveedor seleccionado
    function pintaNotasCredito(){
        //Obtenemos el id del proveedor seleccionado
        var proveedor = $('#selectProveedor').val();
        //Si no se ha seleccionado un proveedor no hacemos nada
        if(!proveedor){
            alert("Seleccione un proveedor");
            return;
        }
        $.ajax({
        url: 'services/mainService.php',
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'getPagosByIdProv',
            controller: "GmcvPago",
            args: {
                'idProveedor': proveedor
            }
        },
        success: function(response) {
            //Limpiamos el contenido de los datatable de notas de credito y documentos de pago
            $('#tablaNotasCR').DataTable().clear().draw();
            $('#tablaDocsPago').DataTable().clear().draw();
            //Agregar las notas de credito
            
            //Actualizamos el nombre del proveedor en la variable global a traves de lo seleccionado en el select2
            nombreProveedor = $('#selectProveedor option:selected').text();

            //Notas de Credito
            if (response.notas.length > 0) {
                response.notas.forEach(nota => {

                let disabled = nota.facturas ? 'disabled' : '';
                let btnEdit = '<button title="Editar Nota de Crédito" '+disabled+' class="btn btn-info btn-sm ml-2 btnEditPago" idPago="'+nota.id_pago+'" style="padding: 2px 5px; font-size: 12px;"><i data-feather="edit-2"></i></button>';
                
                $('#tablaNotasCR').DataTable().row.add([
                    nota.proveedor,
                    nota.fecha,
                    toMoney(nota.monto_pago),
                    toMoney(nota.saldo_disponible),
                    nota.pago_uuid,
                    nota.facturas,
                    btnEdit
                ]).draw();

            });
            } else {
                $('#tablaNotasCR').DataTable().row.add([
                    'No hay notas de crédito disponibles para este proveedor',
                    '',
                    '',
                    '',
                    '',
                    '',
                    ''
                ]).draw();
            }

            
            //Agregar los documentos de pago
            if (response.pagos.length > 0) {
                response.pagos.forEach(pago => {
                    let disabled = pago.facturas ? 'disabled' : '';
                    let btnEdit = '<button title="Editar Documento de Pago" '+disabled+' class="btn btn-info btn-sm ml-2 btnEditPago" idPago="'+pago.id_pago+'" style="padding: 2px 5px; font-size: 12px;"><i data-feather="edit-2"></i></button>';
                    $('#tablaDocsPago').DataTable().row.add([
                        pago.proveedor,
                        pago.fecha,
                        toMoney(pago.monto_pago),
                        toMoney(pago.saldo_disponible),
                        pago.pago_uuid,
                        pago.facturas,
                        btnEdit
                        
                    ]).draw();
                });
            } else {
                $('#tablaDocsPago').DataTable().row.add([
                    'No hay pagos registrados para este proveedor',
                    '',
                    '',
                    '',
                    '',
                    '',
                    ''
                ]).draw();
            }
            
            //Quitamos el atributo disabled de los botones de agregar documento de pago btnAgregarDocumentoPago
            $('.btnAgregarDocumentoPago').removeAttr('disabled');
            $('.btnConsultarDocumentoPago').removeAttr('disabled');
            //Actualizamos lo iconos de feather
            feather.replace();
        },
        error: function(response){
            alert("Error al obtener los documentos de pago");
        }}); // Fin de la llamada ajax

    } // Fin de la funcion pintaNotasCredito

    //Creamos una funcion que reciba un flotante o string y lo convierta a moneda
    function toMoney(valor) {
        const formatter = new Intl.NumberFormat('es-MX', {
            style: 'currency',
            currency: 'MXN'
        });
        return formatter.format(valor);
    }

</script>

<?php


$rutaArchivo = file_exists($ruta."sys/hf/pie_v3.php") ? $ruta."sys/hf/pie_v3.php" : "../../../sys/hf/pie_v3.php";
include $rutaArchivo;


?>
