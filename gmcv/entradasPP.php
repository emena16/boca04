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
require 'models/CompraFacturaProd.php';
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
</style>



<!-- <div class="page-header layout-top-spacing title-header">
    <div class="pge-title" style="margin-left: 3.5%;">
        <h3 class="ml-4">Entrada por Proveedores</h3>
    </div>
</div> -->


<div style="padding-left: 80px; padding-right: 10px;" >
    <div class="container-fluid">
        <div class="page-title" style="float: none;">
            <h3>Entrada por proveedores</h3>
        </div>
        <!-- Aqui vamos a pintar todo lo que se requiera en la vista -->
        <div class="statbox widget box box-shadow widget-content-area p-3  mt-3">

            <div class="row mt-3">
                <div class="col-md-4 col-sm-6">
                    <label for="lblProveedor">Proveedor:</label>
                    <select class="form-control" id="proveedor" name="proveedor">
                        <option selected disabled value="0">Selecciona un proveedor</option>
                        <?php
                        $compra = new Compra();
                        $proveedores = $compra->getProveedoresConOrdenes();
                        foreach ($proveedores as $proveedor) {
                            echo '<option value="'.$proveedor['id'].'">'.$proveedor['nombre'].'</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4 col-sm-6">
                    <label for="lblBodega">Bodega:</label>
                    <select class="form-control" id="bodega" name="bodega">
                        <option selected disabled value="0">Selecciona un proveedor</option>
                    </select>
                </div>
            </div>
            

            <!-- <div class="row">
                <div class="col-md-4">
                    <a href="validaEntradas.php">
                        <button class="mt-lg-4 btn btn-info btn-lg"><i style=" color: #f6fcfb;" data-feather="search"></i> Consulta Avanzada </button>
                    </a>
                </div>
            </div> --> 


            <!-- Div para mostrar el mensaje de messageAlert -->
            <div class="row mt-lg-4">
                <div class="col-md-12 col-sm-12">
                    <div class="row justify-content-center mt-2">
                        <div id="divMessage" class="col-md-8"></div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12"><div class="page-header layout-top-spacing title-header mt-lg-4">
                    <div class="pge-title"> <br>
                        <h5 id="tituloTabla"></h5>
                    </div>
                </div></div>
            </div>
            <div class="row">
                <div id="divTablaEntradas" class="col-md-12 col-lg-12">
                    <!-- Aqui vamos a pintar el datatable con las ordenes de compra -->
                </div>
            </div>

        </div> <!-- fin widget-content-area -->
    </div> <!-- fin container-fluid -->
</div> <!-- fin padding-left  este div mantiene la vista centrada, lejos del menu lateral -->




<!-- Creamos un modal para ingresar las facturas de la orden de compra seleccionada -->
<div class="modal fade" id="modalIngresarFactura" tabindex="-1" role="dialog" aria-labelledby="modalIngresarFactura" aria-hidden="true">
    <div class="modal-dialog modal-xl" >
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tituloModal"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <!-- Aqui vamos a pintar la vista de ingreso de facturas -->
                <div class="row">
                    <div class="col-md-4">
                        <label for="lblUUID">UUID:</label>
                        <input type="text" class="form-control" id="uuidFactura" name="uuid">
                    </div>
                    <div class="col-md-4">
                        <label for="lblFechaFactura">Fecha Factura:</label>
                        <input type="date" class="form-control" id="fechaFactura" name="fechaFactura" max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="lblFechaLlegada">Fecha Llegada:</label>
                        <input type="date" class="form-control" id="fechaLlegada" name="fechaLlegada" max="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                <div class="row mt-lg-4">
                    <div class="col-md-12">
                        <h5>Captura de productos en la factura</h5>
                        <table id="tablaProductosFactura" class="table table-striped table-bordered tablaPequena" style="width:100%">
                            <thead>
                                <tr>
                                    <th><small>Producto</small></th>
                                    <th><small>Código de <br>proveedor</small></th>
                                    <th><small>EAN</small></th>
                                    <th><small>Unidades<br>Facturadas</small></th>
                                    <!-- <th><small>Costo SubTotal Bruto</small></th> -->
                                    <th><small>Unidades<br>Rechazadas</small></th>
                                    <th><small>Caducidad</small> <span id="btnCopiarCaducidad" class="shadow-none badge badge-primary"><i class="feather-16" data-feather="copy"></i></span>
                                </tr>
                            </thead>
                            <tbody>

                            </tbody>
                        </table>
                    </div>
                </div>
                
            </div> <!--  fin del modal-body -->
            <div class="modal-footer">
                <!-- <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button> -->
                <!-- icono: refresh-cw  Actualizar factura-->

                <button type="button" id="btnEnviarFactura" class="btn btn-primary">
                    <i data-feather="plus-circle"></i> Agregar Factura
                </button>

                <button type="button" id="btnActualizarFactura" class="btn btn-primary">
                    <i data-feather="refresh-cw"></i> Actualizar Factura
                </button>
            </div>
        </div>
    </div>
</div>
<!--- FIN MODAL INGRESAR FACTURA -->



<!-- Modal de ingeso de factura a almacen -->
<div class="modal fade" id="modalIngresarAlmacen" tabindex="-1" role="dialog" aria-labelledby="modalIngresarAlmacen" aria-hidden="true">
    <div class="modal-dialog modal-xl" >
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tituloModalAlmacen"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <!-- Aqui vamos a pintar un resumen de la factura que se va a ingresar a almacen, todo sera de lectura y solo se confirmara el ingreso -->
                <div class="row">
                    <div class="col-md-4">
                        <label for="lblUUIDAlmacen">UUID:</label>
                        <label class="form-control" id="uuidFacturaAlmacen" name="uuidAlmacen" readonly></label>
                    </div>
                    <div class="col-md-4">
                        <label for="lblFechaFacturaAlmacen">Fecha Factura:</label>
                        <label class="form-control" id="fechaFacturaAlmacen" name="fechaFacturaAlmacen" readonly></label>
                    </div>
                    <div class="col-md-4">
                        <label for="lblFechaLlegadaAlmacen">Fecha Llegada:</label>
                        <label class="form-control" id="fechaLlegadaAlmacen" name="fechaLlegadaAlmacen" readonly></label>
                    </div>
                </div>
                <div class="row mt-lg-4">
                    <div class="col-md-12">
                        <h5>Productos de la factura</h5>
                        <table id="tablaProductosFacturaAlmacen" class="table table-striped table-bordered tablaPequena" style="width:100%">
                            <thead>
                                <tr>
                                    <th><small>Producto</small></th>
                                    <th><small>Código de <br>proveedor</small></th>
                                    <th><small>EAN</small></th>
                                    <th><small>Unidades<br>Facturadas</small>
                                    <th><small>Unidades<br>Aceptadas</small>
                                    <th><small>Unidades<br>Rechazadas</small>
                                    <th><small>Caducidad</small>
                                </tr>
                            </thead>
                            <tbody>

                            </tbody>
                        </table>
                    </div>
                </div>                 
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!--- FIN MODAL INGRESAR FACTURA A ALMACEN -->

<!-- Modal para agregar un producto a la orden de compra -->
<div class="modal fade" id="modalAgregarProducto" tabindex="-1" role="dialog" aria-labelledby="modalAgregarProducto" aria-hidden="true">
    <div class="modal-dialog modal-lg" >
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tituloModalAgregarProducto">Agregar producto a orden de compra</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <!-- Aqui vamos a pintar la vista de ingreso de productos -->
                <div class="row">
                    <div class="col-md-8">
                        <label for="lblProducto">Producto:</label>
                        <select class="form-control" id="selectAddProductoToOrden" name="producto">
                            <option selected disabled value="0">Selecciona un producto</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="lblUnidades">Unidades:</label>
                        <input type="number" value="1" class="form-control" id="unidadesSolicitadas" name="unidadesSolicitadas">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="btnAgregarProductoOrden" class="btn btn-success">
                    <i data-feather="plus-circle"></i> Agregar Producto a la orden de compra
                </button>
            </div>
        </div>
    </div>
</div>
<!--- FIN MODAL AGREGAR PRODUCTO -->

<!-- Modal de confirmacion de cierre de compra, en el vamos a escribir la respuesta del metodo cerrarOrdenCompra -->
<div class="modal fade" id="modalCerrarCompra" tabindex="-1" role="dialog" aria-labelledby="modalCerrarCompra" aria-hidden="true">
    <div class="modal-dialog modal-lg" >
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tituloModalCerrarCompra">Cerrar Orden de Compra</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div id="modalBody-modalConfirmaCerrarCompra" class="modal-body"> </div>
            <div class="modal-footer">
                <button class="btn btn-success" id="btnProcessConfirmClose" type="button" disabled style="display: none;">
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...
                </button>
                <button type="button" id="btnCerrarCompra" class="btn btn-success">
                    <i data-feather="check-circle"></i> Cerrar Orden de Compra
                </button>
                <button type="button" class="btn btn-danger" data-dismiss="modal"> Cancelar </button>
            </div>
        </div>
    </div>
</div>
<!--- FIN MODAL CERRAR COMPRA -->








<?php
$rutaArchivo = file_exists($ruta."sys/hf/pie_v3.php") ? $ruta."sys/hf/pie_v3.php" : "../../../sys/hf/pie_v3.php";
include $rutaArchivo;
?>

<script src="js/functions.js"></script>
<script>
    $(document).ready(function() {
        // Inicializamos el datatable
        $('#tablaEntradas').DataTable({
            dom: 'frti',
            language: {
                "url": "js/spanish.js"
            },
            order: [],
            stripeClasses: [],
            paging: false // Deshabilitamos la paginación
        });
        //Creamos un evento para cuendo el usuario agregue unidades facturadas o rechazadas habilitaremos o deshabilitaremos el input caducidad de esa fila
        $(document).on('change', '.uFacturadas', function() {
            var id = $(this).attr('id').replace('unidadesFacturadas', '');
            var unidadesFacturadas = $('#unidadesFacturadas'+id).val();
            var unidadesRechazadas = $('#unidadesRechazadas'+id).val();
            if(unidadesFacturadas > 0 || unidadesRechazadas > 0) {
                $('#caducidad'+id).prop('disabled', false);
            } else {
                $('#caducidad'+id).prop('disabled', true);
                //Limpiamos el input de caducidad
                $('#caducidad'+id).val('');
            }
        });

        //Creamos un eventos para cuando se pulsa el boton btnAgregarProducto para agregar un producto a la orden de compra, mostrar modal
        $(document).on('click', '#btnAgregarProducto', function() {
            //Llamamos al metodo getProductosParaAgregarOrden para obtener los productos que se pueden agregar a la orden de compra
            $.ajax({
                url: 'services/mainService.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'getProductosParaAgregarOrden',
                    controller: "Compra",
                    args: { 
                        'id_compra': $('#idCompra').val()
                    }
                },
                success: function(response) {
                    // console.log(response);
                    //Parseamos el JSON recibido
                    var productos = JSON.parse(response);
                    //Limpiamos el select de productos
                    $('#selectAddProductoToOrden').html('');
                    //Limpiamos el input de unidades solicitadas
                    $('#unidadesSolicitadas').val('1');
                    //Recorremos los productos y los agregamos al select
                    $.each(productos, function(index, producto) {
                        $('#selectAddProductoToOrden').append('<option costo="'+producto.costo_unitario+'"  value="'+producto.id_prod+'">'+producto.comercial+'</option>');
                    });
                    //Mostramos el modal
                    $('#modalAgregarProducto').modal('show');
                },
                error: function(error) {
                    console.log(error);
                }
            });
        });

        //Creamos un evento para cuando se precione el boton btnAgregarProductoOrden obtenemos el producto para agregar a la orden de compra, vamos a leer el costo de la opcion seleccionada y llamamos al meteodo addProductoToOrden de compraController
        $(document).on('click', '#btnAgregarProductoOrden', function() {
            var id_prod = $('#selectAddProductoToOrden').val();
            var unidadesSolicitadas = $('#unidadesSolicitadas').val();
            var costo_unitario = $('#selectAddProductoToOrden option:selected').attr('costo');
            //Llamamos al servicio addProductoToOrden para agregar el producto a la orden de compra
            $.ajax({
                url: 'services/mainService.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'addProductoToOrden',
                    controller: "Compra",
                    args: { 
                        'id_compra': $('#idCompra').val(),
                        'id_prod': id_prod,
                        'cantidad': unidadesSolicitadas,
                        'costo_unitario': costo_unitario
                    }
                },
                success: function(response) {
                    // console.log(response);
                    //Parseamos el JSON recibido
                    var response = JSON.parse(response);
                    if(response.status == 1) {
                        //Si el producto se agrego correctamente, mostramos un mensaje de alerta
                        alert('El producto se ha agregado correctamente');
                        //Cerramos el modal
                        $('#modalAgregarProducto').modal('hide');
                        //Recargamos la vista de productos restantes
                        pintarTablaRestantes($('#idCompra').val());
                    }else{
                        //Si el producto no se agrego correctamente, mostramos un mensaje de alerta recibido del servicio
                        alert(response.message);
                    }
                },
                error: function(error) {
                    console.log(error.responseText);
                }
            });
        });
    


        $(document).on('change', '.uRechazadas', function() {
            var id = $(this).attr('id').replace('unidadesRechazadas', '');
            var unidadesFacturadas = $('#unidadesFacturadas'+id).val();
            var unidadesRechazadas = $('#unidadesRechazadas'+id).val();
            if(unidadesFacturadas > 0 || unidadesRechazadas > 0) {
                $('#caducidad'+id).prop('disabled', false);
            } else {
                $('#caducidad'+id).prop('disabled', true);
                //Limpiamos el input de caducidad
                $('#caducidad'+id).val('');
            }
        });

        //Creamos un eventos para cuando el usuario agregue la factura
        $(document).on('click', '#btnEnviarFactura', function() {           
            var id_compra = $('#idCompra').val();
            var uuid = $('#uuidFactura').val();
            var fecha_factura = $('#fechaFactura').val();
            var fecha_llegada = $('#fechaLlegada').val();            
            var productos = [];
            var factura = {
                'id_compra': id_compra,
                'uuid': uuid,
                'fecha_factura': fecha_factura,
                'fecha_llegada': fecha_llegada
            };
            // Recorremos todos los inputs de caducidad de la tabla y los guardamos en un array
            $('#tablaProductosFactura input[type="date"]').each(function() {
                // Solo guardamos la fila si la caducidad está habilitada
                if(!$(this).prop('disabled')) {
                    var id_prod = $(this).attr('id').replace('caducidad', '');
                    var caducidad = $(this).val();
                    var unidadesFacturadas = $('#unidadesFacturadas'+id_prod).val();
                    var unidadesRechazadas = $('#unidadesRechazadas'+id_prod).val();
                    var costo_unitario = $('#costo_unitario'+id_prod).val();
                    var id_prod_compra = $('#id_prod_compra'+id_prod).val();
                    productos.push({
                        'id_prod': id_prod,
                        'unidadesFacturadas': unidadesFacturadas,
                        'unidadesRechazadas': unidadesRechazadas,
                        'caducidad': caducidad,
                        'costo_unitario': costo_unitario,
                        'id_prod_compra': id_prod_compra
                    });
                }
            });
            //Llamamos al servicio addFacturaToOrden para agregar la factura
            $.ajax({
                url: 'services/mainService.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'addFacturaToOrden',
                    controller: "CompraFactura",
                    args: { 
                        'factura': factura,
                        'productos': productos
                    }                        
                },
                success: function(response) {
                    // console.log('Respuesta del servicio de agregar factura');
                    // console.log(response);
                    //Parseamos el JSON para obtener el valor de exists
                    var response = JSON.parse(response);
                    if(response.status == 1) {
                        //Si la factura se agrego correctamente, mostramos un mensaje de alerta
                        // alert('La factura se ha agregado correctamente');
                        messageAlert('La factura se ha agregado correctamente', 'success', false);
                        //Cerramos el modal
                        $('#modalIngresarFactura').modal('hide');
                        //Recargamos la vista de productos restantes
                        pintarTablaRestantes(id_compra);
                        //Recargamos la vista de facturas
                        pintarTablaFacturas(id_compra);
                    }else{
                        //Si la factura no se agrego correctamente, mostramos un mensaje de alerta recibiendolo del servicio
                        alert(response.message);
                    }
                },
                error: function(error) {
                    alert('Hubo un error al agregar la factura');
                }
            });
           
        });

        //Creamos un eventos para cuando el usuario actualice la factura, mostramos el resumen en el modal de ingreso a almacen
        $(document).on('click', '#btnActualizarFactura', function() {           
            var id_compra = $('#idCompra').val();
            var uuid = $('#uuidFactura').val();
            var fecha_factura = $('#fechaFactura').val();
            var fecha_llegada = $('#fechaLlegada').val();
            var idFactura = $('#uuidFactura').attr('fact');

            //Creamos una variable llamada "hoy" que sera la fecha de hoy para comparar con la fecha de llegada
            var hoy = <?= json_encode(date('Y-m-d')); ?>;
            //Verificamos que la factura tenga un uuid y que no existe en la db
            if(uuid == '') {
                alert('El UUID de la factura no puede estar vacío');
                return false;
            }
            //Verificamos que la factura tenga una fecha de factura
            if(fecha_factura == '') {
                alert('La fecha de la factura no puede estar vacía');
                return false;
            }
            //Verificamos que la factura tenga una fecha de llegada
            if(fecha_llegada == '') {
                alert('La fecha de llegada no puede estar vacía');
                return false;
            }

            //La fecha de llegada no puede ser menor a la fecha de factura
            // if(fecha_llegada < fecha_factura) {
            //     alert('La fecha de llegada no puede ser menor a la fecha de factura');
            //     return false;
            // }

            //La fecha de llegada no puede ser mayor a la fecha de hoy
            if(fecha_llegada > hoy) {
                alert('La fecha de llegada no puede ser mayor a la fecha de hoy');
                return false;
            }

            var uuidOriginal = $(this).attr('uuid');
            //Si el UUID de la factura es diferente al original, verificamos que no exista en la db
            if(uuid != uuidOriginal) {
                //Llamamos al servicio para verificar si el UUID de la factura ya existe
                $.ajax({
                    url: 'services/mainService.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'checkUuid',
                        controller: "CompraFactura",
                        args: { 
                            'uuid': uuid
                        }
                    },
                    success: function(response) {
                        console.log(response);
                        //Parseamos el JSON recibido
                        var response = JSON.parse(response);
                        if(response.exists == 1) {
                            //Si el UUID de la factura ya existe, mostramos un mensaje de alerta
                            alert('El UUID de la factura ya existe');
                            return false;
                        }
                    },
                    error: function(error) {
                        console.log(error);
                    }
                });
            }

            var productos = [];
            var factura = {
                'id_compra': id_compra,
                'uuid': uuid,
                'id': idFactura,
                'fecha_factura': fecha_factura,
                'fecha_llegada': fecha_llegada
            };
            // Recorremos todos los inputs de caducidad de la tabla y los guardamos en un array
            $('#tablaProductosFactura input[type="date"]').each(function() {
                // Solo guardamos la fila si la caducidad está habilitada
                if(!$(this).prop('disabled')) {
                    var id_prod = $(this).attr('id').replace('caducidad', '');
                    var caducidad = $(this).val();
                    //La fecha de caducidad no puede ser menos a hoy
                    if(caducidad < hoy) {
                        alert('Por favor, verifica la fecha de caducidad de los productos');
                        return false;
                    }
                    console.log("Caducidad: " + caducidad + " Hoy: " + hoy);
                    var unidadesFacturadas = $('#unidadesFacturadas'+id_prod).val();
                    var unidadesRechazadas = $('#unidadesRechazadas'+id_prod).val();
                    var costo_unitario = $('#costo_unitario'+id_prod).val();
                    var id_prod_compra = $('#id_prod_compra'+id_prod).val();
                    productos.push({
                        'id_prod': id_prod,
                        'unidadesFacturadas': unidadesFacturadas,
                        'unidadesRechazadas': unidadesRechazadas,
                        'caducidad': caducidad,
                        'costo_unitario': costo_unitario,
                        'id_prod_compra': id_prod_compra
                    });
                }
            });
            //Llamamos al servicio addFacturaToOrden para agregar la factura
            $.ajax({
                url: 'services/mainService.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'actualizaFacturaEntrada',
                    controller: "CompraFactura",
                    args: { 
                        'factura': factura,
                        'productos': productos
                    }
                },
                success: function(response) {
                    console.log('Respuesta del servicio de actualizar factura');
                    // console.log(response);
                    //Parseamos el JSON para obtener el valor de exists
                    var response = JSON.parse(response);
                    if(response.status == 1) {
                        //Si la factura se agrego correctamente, mostramos un mensaje de alerta
                        // alert('La factura se ha actualizado correctamente');
                        messageAlert('La factura se ha actualizado correctamente', 'success', false);
                        //Cerramos el modal
                        $('#modalIngresarFactura').modal('hide');
                        //Recargamos la vista de productos restantes
                        pintarTablaRestantes(id_compra);
                        //Recargamos la vista de facturas
                        pintarTablaFacturas(id_compra);
                    }else{
                        //Si la factura no se agrego correctamente, mostramos un mensaje de alerta recibido del servicio
                        alert(response.message);
                        
                    }
                },
                error: function(error) {
                    console.log(error);
                }
            });
           
        });

        //Creamos un evento para cuando se pulse el boton de copiar caducidad se abra el modal
        $(document).on('click', '#btnCopiarCaducidad', function() {
            //Buscamos el un input que tenga valor diferente de vacio y lo copiamos en todos los inputs de caducidad
            var caducidad = '';
            $('#tablaProductosFactura input[type="date"]:not([disabled])').each(function() {
                if($(this).val() != '') {
                    caducidad = $(this).val();
                    return false;
                }
            });
            //Si encontramos una caducidad, la copiamos en todos los inputs de caducidad
            if(caducidad != '') {
                $('#tablaProductosFactura input[type="date"]:not([disabled])').each(function() {
                    $(this).val(caducidad);
                });
            } else {
                //Si no encontramos ninguna caducidad, mostramos un mensaje de alerta
                alert('No hay ninguna caducidad para copiar');
            }
        });


        //Creamos un evento para mostrar el resumen de la factura para autorizar el ingreso a almacen
        $(document).on('click', '.btnIngresarFactAlmacen', function() {
            var id_factura = $(this).attr('id');
            var id_compra = $('#idCompra').val();
            //Limpiamos los campos del modal
            $('#uuidFacturaAlmacen').text('');
            $('#fechaFacturaAlmacen').text('');
            $('#fechaLlegadaAlmacen').text('');            
            
            //Limpiamos la tabla de productos de la factura
            $('#tablaProductosFacturaAlmacen tbody').html('');
            //Mostramos el modal obligando al usuario a enviar el mensaje y confirmar que se ha hecho
            $('#modalIngresarAlmacen').modal({
                backdrop: 'static',
                keyboard: false, 
                show: true 
            });
            //Llamamos al servicio para obtener los productos de la orden de compra seleccionada
            $.ajax({
                url: 'services/mainService.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'getProductosByFactura',
                    controller: "CompraFacturaProd",
                    args: { 
                        'id_factura': id_factura,
                        'id_prod_compra': id_compra
                    }
                },
                success: function(response) {
                    // console.log(response);
                    // Procesamos el JSON recibido
                    var respuesta = JSON.parse(response);
                    var data = respuesta.productos;
                    var factura = respuesta.factura;
                    $.each(data, function(index, producto) {
                        //Verificamos que sea un producto valido por lo que si no tiene fecha de caducidad no lo mostramos y continuamos con el siguiente producto
                        if(producto.caducidad == '') {
                            return true;
                        }
                        //Quitamos el signo de pesos y los espacios en blanco del costo unitario
                        producto.costo_unitario = producto.costo_unitario.replace(/[$\s]/g, '');
                        var fila = '<tr id="'+producto.id_prod+'" >';
                        fila += '<td>' + producto.comercial + '</td>';
                        fila += '<td>'+producto.cod_prov+'</td>';
                        fila += '<td>'+producto.ean+'</td>';
                        // fila += '<td>'+producto.costo_subtotal_bruto+'</td>';
                        fila += '<td class="dt-right">' + (producto.total_cantidad_aceptada + producto.total_cantidad_rechazada) + '</td>';
                        fila += '<td class="dt-right">' + producto.total_cantidad_aceptada + '</td>';
                        fila += '<td class="dt-right">' + producto.total_cantidad_rechazada + '</td>';
                        fila += '<td class="dt-right">' + producto.caducidad + '</td>';
                        fila += '</tr>';
                        $('#tablaProductosFacturaAlmacen tbody').append(fila);
                    });
                    //Actualizamos el datatable en caso de no estar inicializado
                    if (!$.fn.DataTable.isDataTable('#tablaProductosFacturaAlmacen')) {
                        $('#tablaProductosFacturaAlmacen').DataTable( {
                            caption: 'Productos de la factura',
                            dom: 'frti',
                            searching: false,
                            columnDefs: [
                                {
                                    "targets": [0],
                                    "visible": true,
                                    "width": "300",
                                    "orderable": false
                                },
                                {
                                    "targets": [5],
                                    "visible": true,
                                    "width": "100",
                                    "orderable": false
                                },
                                {
                                    "targets": [1,2,3,4,6],
                                    "orderable": false
                                }
                            ],
                        });
                    }   
                    //Pegamos en las labels los datos de la factura
                    $('#uuidFacturaAlmacen').text(factura.uuid);
                    $('#fechaFacturaAlmacen').text(factura.fecha);
                    $('#fechaLlegadaAlmacen').text(factura.fecha_llegada);
                    //Agreamos el atributo fact a #uuidFacturaAlmacen para poder comparar el UUID de la factura
                    $('#uuidFacturaAlmacen').attr('fact', factura.id);

                    //Actualizamos el titulo del modal
                    $('#tituloModalAlmacen').html('Confirma ingresar mercancía de la factura: <b>'+factura.uuid+'</b>'); 

                    //Agregamos un boton para confirmar el ingreso a almacen
                    $('#modalIngresarAlmacen .modal-footer').html(
                        '<button class="btn btn-warning" id="btnProcessIngresoFact" type="button" disabled style="display: none;"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...</button>'+
                        '<button type="button" id="btnConfirmaIngresarAlmacen" factura="'+factura.id+'" class="btn btn-success"><i data-feather="check"></i> Confirmar Ingreso de mercancía a Almacén</button>' +
                        '<button type="button" id="btnCancelIngresoFact" class="btn btn-danger ml-3" data-dismiss="modal">Cancelar</button>'
                    );


                    feather.replace();
                },
                error: function(error) {
                    alert('Hubo un error al obtener los productos de la factura');
                    console.log(error.responseText);
                }
            });
        });

        //Creamos un evento para cuando pulsen el boton btnConfirmaIngresarAlmacen para autoriar el ingreso a almacen
        $(document).on('click', '#btnConfirmaIngresarAlmacen', function() {
            var id_factura = $(this).attr('factura');
            var id_compra = $('#idCompra').val();

            //Llamamos al servicio para autorizar el ingreso a almacen
            $.ajax({
                url: 'services/mainService.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'ingresarFacturaAlmacen',
                    controller: "CompraFactura",
                    args: {
                        'id_factura': id_factura,
                        'id_compra': id_compra
                    }
                },
                beforeSend: function() {
                    //Ocultamos el boton btnConfirmaIngresarAlmacen y mostramos el boton de procesando: <button class="btn btn-warning" type="button" disabled><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...</button>
                    $('#btnConfirmaIngresarAlmacen').hide();
                    $('#btnCancelIngresoFact').hide();
                    $('#btnProcessIngresoFact').show();
                },
                success: function(response) {
                    console.log('Respuesta del servicio de ingresar a almacen');
                    //Parseamos el JSON para obtener el valor de exists
                    var response = JSON.parse(response);
                    // console.log(response);
                    if(response.status == 1) {
                        //Si la factura se agrego correctamente, mostramos un mensaje de alerta
                        alert('La mercancía se ha ingresado correctamente a almacén');
                        //Cerramos el modal
                        $('#modalIngresarAlmacen').modal('hide');
                        //Recargamos la vista de productos restantes
                        pintarTablaRestantes(id_compra);
                        //Recargamos la vista de facturas
                        pintarTablaFacturas(id_compra);
                    }else{
                        //Si la factura no se agrego correctamente, mostramos un mensaje de alerta recibiendolo del servicio
                        alert(response.message);
                        //Intercalamos los botones de procesando y confirmar
                        $('#btnConfirmaIngresarAlmacen').show();
                        $('#btnCancelIngresoFact').show();
                        $('#btnProcessIngresoFact').hide();

                    }
                },
                error: function(error) {
                    alert('Hubo un error al ingresar la mercancía a almacén');
                    //Escribimos en consola el error
                    console.log(error.responseText);
                    //Intercalamos los botones de procesando y confirmar
                    $('#btnConfirmaIngresarAlmacen').show();
                    $('#btnCancelIngresoFact').show();
                    $('#btnProcessIngresoFact').hide();
                }
            });
        });



        //Creamos un evento para mostrar el modal de editar factura
        $(document).on('click', '.btnEditarFactura', function() {
            var id_factura = $(this).attr('id');
            var id_compra = $('#idCompra').val();
            //Limpiamos los campos del modal
            $('#uuidFactura').text('');
            $('#fechaFactura').val('');
            $('#fechaLlegada').val('');
            //Limpiamos el campo fact de #uuidFactura
            $('#uuidFactura').attr('fact', '');
            //Ocultamos y mostramos correspondientes botones 
            $('#btnActualizarFactura').show();
            $('#btnEnviarFactura').hide();
            
            //Limpiamos la tabla de productos de la factura
            $('#tablaProductosFactura tbody').html('');
            //Mostramos el modal obligando al usuario a enviar el mensaje y confirmar que se ha hecho
            $('#modalIngresarFactura').modal({
                backdrop: 'static',
                keyboard: false, 
                show: true 
            });
            //Llamamos al servicio para obtener los productos de la orden de compra seleccionada
            $.ajax({
                url: 'services/mainService.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'getProductosByFactura',
                    controller: "CompraFacturaProd",
                    args: { 
                        'id_factura': id_factura,
                        'id_prod_compra': id_compra
                    }
                },
                success: function(response) {
                    console.log('Respuesta del servicio de obtener productos de la factura');
                    // console.log(response);
                    // Procesamos el JSON recibido
                    var respuesta = JSON.parse(response);
                    var data = respuesta.productos;
                    var factura = respuesta.factura;
                    $.each(data, function(index, producto) {
                        //Quitamos el signo de pesos y los espacios en blanco del costo unitario
                        producto.costo_unitario = producto.costo_unitario.replace(/[$\s]/g, '');
                        var fila = '<tr id="'+producto.id_prod+'" >';
                        fila += '<td class="dt-right">'+producto.comercial+'<input type="hidden" id="costo_unitario'+producto.id_prod+'" value="'+producto.costo_unitario+'"><input type="hidden" id="id_prod_compra'+producto.id_prod+'" value="'+producto.id_prod_compra+'"></td>';
                        fila += '<td class="dt-right">'+producto.cod_prov+'</td>';
                        fila += '<td class="dt-right">'+producto.ean+'</td>';
                        // fila += '<td>'+producto.costo_subtotal_bruto+'</td>';
                        fila += '<td class="dt-right"><input type="number" class="dt-right uFacturadas" id="unidadesFacturadas'+producto.id_prod+'" name="unidadesFacturadas'+producto.id_prod+'" value="'+ (producto.total_cantidad_aceptada+producto.total_cantidad_rechazada) +'" style="width: 80px;"></td>';
                        fila += '<td class="dt-right"><input type="number" class="dt-right uRechazadas" id="unidadesRechazadas'+producto.id_prod+'" name="unidadesRechazadas'+producto.id_prod+'" value="'+ producto.total_cantidad_rechazada +'" style="width: 80px;"></td>';
                        // Si caducidad no esta vacia la escribimos
                        if(producto.caducidad != '') {
                            fila += '<td class="dt-right"><input type="date" class="dt-right" id="caducidad'+producto.id_prod+'" name="caducidad'+producto.id_prod+'" value="'+producto.caducidad+'" min="<?=date('Y-m-d');?>"></td>';
                        } else {
                            fila += '<td class="dt-right"><input disabled type="date" class="dt-right" id="caducidad'+producto.id_prod+'" name="caducidad'+producto.id_prod+'"></td>';
                        }
                        fila += '</tr>';
                        $('#tablaProductosFactura tbody').append(fila);
                    });
                    //Actualizamos el datatable en caso de no estar inicializado
                    if (!$.fn.DataTable.isDataTable('#tablaProductosFactura')) {
                        $('#tablaProductosFactura').DataTable( {
                            dom: 'frti',
                            searching: false,
                            columnDefs: [
                                {
                                    "targets": [0],
                                    "visible": true,
                                    "width": "300",
                                    "orderable": false
                                },
                                {
                                    "targets": [5],
                                    "visible": true,
                                    "width": "100",
                                    "orderable": false
                                },
                                {
                                    "targets": [1,2,3,4],
                                    "orderable": false
                                }
                            ],
                        });
                    }   
                    //Pegamos en los inputs los datos de la factura
                    $('#uuidFactura').val(factura.uuid);
                    //Agregamos el atributo fact a #uuidFactura para poder comparar el UUID de la factura
                    $('#uuidFactura').attr('fact', factura.id);
                    //Desactivamos el input de UUID
                    // $('#uuidFactura').attr('disabled', true);
                    $('#fechaFactura').val(factura.fecha);
                    $('#fechaLlegada').val(factura.fecha_llegada);
                    
                    //Actualizamos el titulo del modal
                    $('#tituloModal').text('Editar Factura: '+factura.uuid);
                    //Agregamos el atributo uuid al boton de actualizar factura
                    $('#btnActualizarFactura').attr('uuid', factura.uuid);

                    feather.replace();
                },
                error: function(error) {
                    alert('Hubo un error al obtener los productos de la factura');
                    console.log(error.responseText);
                }
            });
        });

        //Creamos un evento que verifique el UUID de la factura no exista en la base de datos
        $('#uuidFactura').change(function() {
            var uuid = $(this).val();

            //Verifacamos si el btnActualizarFactura esta activo para no hacer la validacion
            if($('#btnActualizarFactura').is(':visible')) {
                console.log('El boton de actualizar factura esta visible');
                //Obtenemos el UUID original de la factura
                var uuidOriginal = $('#btnActualizarFactura').attr('uuid');
                //Si el UUID de la factura es diferente al original, verificamos que no exista en la db
                if(uuid == uuidOriginal) {
                    $('#uuidFactura').removeClass('is-invalid');
                    $('#uuidFactura').next().remove();
                    console.log('El UUID es igual al original');
                    //En caso de que el UUID no exista, habilitamos el boton de agregar factura
                    $('#modalIngresarFactura button').attr('disabled', false);
                    
                    return false;
                }
            }

            $.ajax({
                url: 'services/mainService.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'checkUuid',
                    controller: "CompraFactura",
                    args: { 
                        'uuid': uuid
                    }                        
                },
                success: function(response) {
                    //Parseamos el JSON para obtener el valor de exists
                    var response = JSON.parse(response);
                    if(response.exists == 1) {
                        //Limpiamos por si acaso ya habia un mensaje previo de error
                        $('#uuidFactura').removeClass('is-invalid');
                        $('#uuidFactura').next().remove();

                        $('#uuidFactura').addClass('is-invalid');
                        //Agregamos un mensaje de error con un input invalid-feedback
                        $('#uuidFactura').after('<div class="invalid-feedback">El UUID de la factura ya existe en la base de datos</div>');
                        //Bloquemos el boton de agregar factura para evitar que se envie el formulario
                        $('#modalIngresarFactura button').attr('disabled', true);
                    }else{
                        $('#uuidFactura').removeClass('is-invalid');
                        $('#uuidFactura').next().remove();
                        //En caso de que el UUID no exista, habilitamos el boton de agregar factura
                        $('#modalIngresarFactura button').attr('disabled', false);
                    }
                },
                error: function(error) {
                    console.log(error);
                }
            });
        });

        //Creamos un evento change para el select de proveedor para obtener las bodegas con compras pendientes
        $('#proveedor').change(function() {
            var id_prov = $(this).val();
            if(id_prov != 0) {
                $.ajax({
                    url: 'services/mainService.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'getBodegasConComprasPendientes',
                        controller: "Compra",
                        args: { 
                            'id_prov': id_prov,
                            'modoValidacion': 0
                        }                        
                    },
                    success: function(response) {
                        // console.log(response);
                        $('#bodega').html('<option selected disabled value="0">Selecciona una bodega</option>');
                        response.forEach(bodega => {
                            $('#bodega').append('<option value="'+bodega.id+'">'+bodega.nombre+'</option>');
                        });
                    },
                    error: function(error) {
                        console.log(error);
                    }

                });
            }
        });

        //Creamos un evento change para el select de bodega para obtener las ordenes de compra
        $('#bodega').change(function() {
            var id_prov = $('#proveedor').val();
            var id_bodega = $(this).val();
            if(id_bodega != 0) {
                //Llamamos a la funcion para pintar las ordenes de compra de la bodega seleccionada
                pintaOrdenesCompra(id_prov, id_bodega);
            }
        });

        //Creamos un evento click para el boton de regresar llamamos a la funcion para pintar las ordenes de compra
        $(document).on('click', '#btnRegresar', function() {
            var id_prov = $('#proveedor').val();
            var id_bodega = $('#bodega').val();
            pintaOrdenesCompra(id_prov, id_bodega);
        });


        //Creamos un evento para cuando se pulse el boton de agregar factura aparezca el modal para ingresar la factura
        $(document).on('click', '#btnAgregarFactura', function() {
            var id_compra = $('#idCompra').val();
            //Limpiamos los campos del modal
            $('#uuidFactura').val('');
            $('#fechaFactura').val('');
            $('#fechaLlegada').val('');
            //Actualizamos el titulo del modal
            $('#tituloModal').text('Nueva Factura');
            //Ocultamos y mostramos correspondientes botones 
            $('#btnEnviarFactura').show();
            $('#btnActualizarFactura').hide();
            //Habilitamos el campo de UUID 
            $('#uuidFactura').attr('disabled', false);
            //Limpiamos la tabla de productos de la factura
            $('#tablaProductosFactura tbody').html('');
            //Mostramos el modal obligando al usuario a enviar el mensaje y confirmar que se ha hecho
            $('#modalIngresarFactura').modal({
                backdrop: 'static',
                keyboard: false, 
                show: true 
            });
            //Llamamos al servicio para obtener los productos de la orden de compra seleccionada
            $.ajax({
                url: 'services/mainService.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'getProductosRestantesOrdenCompra',
                    controller: "Compra",
                    args: { 
                        'id_compra': id_compra
                    }                        
                },
                beforeSend: function() {
                    $('#tablaProductosFactura tbody').html('<tr><td colspan="7">Cargando productos...</td></tr>');
                },
                success: function(response) {
                    // console.log(response);
                    // Parseamos la cadena JSON para convertirla en un array de objetos
                    var data = JSON.parse(response);
                    //Destruimos el datatable si ya esta inicializado
                    if ($.fn.DataTable.isDataTable('#tablaProductosFactura')) {
                        $('#tablaProductosFactura').DataTable().destroy();
                    }
                    //Limpiamos la tabla antes de llenarla
                    $('#tablaProductosFactura tbody').html('');
                    
                    //Llenamos la tabla con los productos de la orden de compra
                    if(data.length > 0) {
                        var hoy = new Date();
                        $.each(data, function(index, producto) {
                            //Si la cantidad restante de producto.cant_restante es < 0, saltamos a la siguiente iteración
                            if(producto.cant_restante <= 0) {
                                return true;
                            }
                            //Quitamos el signo de pesos y los espacios en blanco del costo unitario
                            producto.costo_unitario = producto.costo_unitario.replace(/[$\s]/g, '');
                            var fila = '<tr id="'+producto.id_prod+'" >';
                            fila += '<td>'+producto.comercial+'<input type="hidden" id="costo_unitario'+producto.id_prod+'" value="'+producto.costo_unitario+'"><input type="hidden" id="id_prod_compra'+producto.id_prod+'" value="'+producto.id_prod_compra+'"></td>';
                            fila += '<td class="dt-right">'+producto.cod_prov+'</td>';
                            fila += '<td class="dt-right">'+producto.ean+'</td>';
                            // fila += '<td>'+producto.costo_subtotal_bruto+'</td>';
                            fila += '<td class="dt-right"><input type="number" class="dt-right uFacturadas" id="unidadesFacturadas'+producto.id_prod+'" name="unidadesFacturadas'+producto.id_prod+'" value="'+producto.cant_restante+'" style="width: 80px;"></td>';
                            fila += '<td class="dt-right"><input type="number" class="dt-right uRechazadas" id="unidadesRechazadas'+producto.id_prod+'" name="unidadesRechazadas'+producto.id_prod+'" value="0" style="width: 80px;"></td>';
                            fila += '<td class="dt-right"><input type="date" class="dt-right" id="caducidad'+producto.id_prod+'" name="caducidad'+producto.id_prod+'" min="<?=date('Y-m-d');?>"></td>';
                            fila += '</tr>';
                            $('#tablaProductosFactura tbody').append(fila);
                        });
                    } else {
                        $('#tablaProductosFactura tbody').html('<tr><td colspan="7">No hay productos en esta orden de compra</td></tr>');
                    }

                    //Inicializamos el datatable en caso de no estar inicializado
                    if (!$.fn.DataTable.isDataTable('#tablaProductosFactura')) {
                        $('#tablaProductosFactura').DataTable( {
                            dom: 'frti',
                            searching: false,
                            columnDefs: [{
                                    "targets": [0],
                                    "visible": true,
                                    "width": "300",
                                    "orderable": false
                                },{
                                    "targets": [3,4],
                                    "visible": true,
                                    "width": "90",
                                    "orderable": false
                                },{
                                    "targets": [5],
                                    "visible": true,
                                    "width": "100",
                                    "orderable": false
                                }
                            ],
                            language: {
                                "url": "js/spanish.js"
                            },
                            order: [],
                            //Ajustamos las cabeceras de la tabla a la izquierda
                            stripeClasses: [], // Deshabilitamos las rayas de la tabla
                            paging: false // Deshabilitamos la paginación
                        });
                    }
                    
                },
                error: function(error) {
                    console.log(error);
                }
            });
        });

        //Creamos un evento para cuando se pulse el boton btnGuardarEntrada para cerrar la entrada en compraController usando el metodo cerrarOrdenCompra
        $(document).on('click', '#btnGuardarEntrada', function() {
            var id_compra = $('#idCompra').val();
            var notaEntrada = $('#notaEntrada').val();
            //Llamamos al servicio para cerrar la orden de compra
            $.ajax({
                url: 'services/mainService.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'cerrarOrdenCompra',
                    controller: "Compra",
                    args: { 
                        'id_compra': id_compra,
                        'notaEntrada': notaEntrada
                    }
                },
                success: function(response) {
                    console.log('Respuesta del servicio de cerrar orden de compra');
                    // console.log(response);
                    //Parseamos el JSON para obtener el valor de exists
                    var response = JSON.parse(response);
                    if(response.status == 1) {
                        //Si recibimos true se puede cerrar por lo que solicitamo confirmacion del usuario, mostramos el modal de confirmacion
                        //Escribimos el modal de confirmacion
                        $('#modalCerrarCompra .modal-body').html('<p><div id="alertaCierreOrden" class="alert alert-warning" role="alert"><strong><h5>Confirmación de cierre de orden</h5></strong> <p>¿Estás seguro de cerrar la orden de compra? Una vez cerrada no se podrá modificar.<p></div></p>');
                        //Si hay productos restantes, mostramos los productos que seran marcados como no recibidos
                        if(response.productosPendientes) {
                            $('#modalCerrarCompra .modal-body').append('<br><h5>Los siguientes productos serán enviados a "Productos no recibidos": </h5>');

                            var tabla = '<table class="table table-bordered tablaPequena" style="width:100%">';
                            tabla += '<thead>';
                            tabla += '<tr>';
                            tabla += '<th>EAN</th>';
                            tabla += '<th>Producto</th>';
                            tabla += '<th>Cantidad<br>Solicitada</th>';
                            tabla += '<th>Cantidad<br>Ingresada</th>';
                            tabla += '<th>Cantidad Pendiete<br>por ingresar</th>';
                            tabla += '</tr>';
                            tabla += '</thead>';
                            tabla += '<tbody>';
                            $.each(response.productos, function(index, producto) {
                                tabla += '<tr>';
                                tabla += '<td>'+producto.ean+'</td>';
                                tabla += '<td>'+producto.comercial+'</td>';
                                tabla += '<td class="dt-right" >'+producto.cant_solicitada+'</td>';
                                tabla += '<td class="dt-right" >'+producto.cant_ingresada+'</td>';
                                tabla += '<td class="dt-right" >'+producto.cant_restante+'</td>';
                                tabla += '</tr>';
                            });
                            tabla += '</tbody>';
                            tabla += '</table>';
                            $('#modalCerrarCompra .modal-body').append(tabla);
                        }
                        //Activamos el boton btnCerrarCompra
                        $('#btnGuardarEntrada').attr('disabled', false);
                        //Mostramos el boton btnCerrarCompra en caso de que este oculto
                        $('#btnCerrarCompra').show();
                        //Qitamos el style display none del boton btnCerrarCompra
                        $('#btnCerrarCompra').css('display', '');
                        //Ocultamos el boton btnProcessConfirmClose
                        $('#btnProcessConfirmClose').hide();

                        $('#modalCerrarCompra').modal({
                            backdrop: 'static',
                            keyboard: false, 
                            show: true 
                        });
                        
                    }else{
                        //Escribimos en el modal un alert explicando que no se puede cerrar la orden de compra y mostramos el mensaje del servicio
                        $('#modalCerrarCompra .modal-body').html('<div class="alert alert-danger" role="alert">'+response.message+'</div>');
                        $('#modalCerrarCompra').modal({
                            keyboard: false, 
                            show: true 
                        });
                        //Desactivamos el boton btnCerrarCompra
                        $('#btnGuardarEntrada').attr('disabled', true);
                    }
                },
                error: function(error) {
                    alert('Hubo un error al cerrar la orden de compra');
                }
            });
        });

        //Creamos un evento que al pulsar el boton btnCerrarCompra confirme el cierre de la orden de compra
        $(document).on('click', '#btnCerrarCompra', function() {
            var id_compra = $('#idCompra').val();
            var notaEntrada = $('#notaEntrada').val();

            //Intercalamos los botones de procesando y confirmar
            $('#btnCerrarCompra').show();
            $('#btnCancelClose').show();
            $('#btnProcessConfirmClose').hide();

            //Llamamos al servicio para cerrar la orden de compra
            $.ajax({
                url: 'services/mainService.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'confirmaCerrarOrdenCompra',
                    controller: "Compra",
                    args: { 
                        'id_compra': id_compra,
                        'notaEntrada': notaEntrada
                    }
                },
                success: function(response) {
                    
                    console.log('Respuesta del servicio de cerrar orden de compra');
                    // console.log(response);
                    //Parseamos el JSON para obtener el valor de exists
                    var response = JSON.parse(response);
                    if(response.status == 1) {
                        //Si recibimos el true la orden se he confirmado por lo que mostramos un mensaje de alerta de bootstrap
                        $('#alertaCierreOrden').removeClass('alert-warning').addClass('alert-success').html('<strong><h5>Hecho</h5></strong><p> La orden de compra se ha cerrado correctamente.<p><div id="alertCounterTime"></div>');
                        //Ocultamos el boton de confirmar cierre de orden
                        $('#btnCerrarCompra').hide();
                        //Mostramos el boton de btnProcessConfirmClose
                        $('#btnProcessConfirmClose').show();
                        //Oculta el boton de cancelar
                        $('#btnCancelClose').hide();
                        //Escribimos un contador de 5 seg y redirigimos a la pagina de ordenes de compra
                        var count = 5;
                        var interval = setInterval(function() {
                            count--;
                            //Escribimos el contador en el div alertCounterTime
                            $('#alertCounterTime').html('<p>Redirigiendo a ordenes de compra pendientes en '+count+' segundos</p>');
                            if(count == 0) {
                                clearInterval(interval);
                                //Cerramos el modal
                                $('#modalCerrarCompra').modal('hide');
                                //Pintamos la vista de ordenes de compra
                                pintaOrdenesCompra($('#proveedor').val(), $('#bodega').val());
                            }
                        }, 1000);
                    }else{
                        //Si recibimos false la orden no se ha cerrado por lo que mostramos un mensaje de alerta de bootstrap
                        $('#alertaCierreOrden').removeClass('alert-warning').addClass('alert-danger').html('<strong><h5>Error</h5></strong><p> '+response.message+'<p>');
                    }
                },
                error: function(error) {
                    alert('Hubo un error al cerrar la orden de compra, favor de revisar la consola.');
                    console.log(error.responseText);
                }
            });
        });
    




        

        //Creamos un evento que al hacer click en el boton de ingresar, pintemos la vista para ingresar las facturas
        $(document).on('click', '.btnIngresar', function() {
            var id_compra = $(this).attr('id');
            //Limpiamos el contenido del div divTablaEntradas para pintar la vista de ingreso de facturas en la orden de compra seleccionada
            $('#divTablaEntradas').html('');
            //Actualizamos el titulo de la tabla y agregamos un boton para regresar
            
            $('#tituloTabla').html('<div class="d-flex justify-content-between mb-3">Ingreso de Facturas para la Orden de Compra '+id_compra+
                '<div class="d-flex justify-content-end">'+
                '<button class="ml-3 btn btn-secondary" id="btnRegresar"><i style=" color: #f6fcfb;" data-feather="arrow-left"></i> Regresar Ordenes de compra</button>'+
                '<button class="ml-3 btn btn-primary" id="btnAgregarProducto"><i style=" color: #f6fcfb;" data-feather="package"></i> Agregar Producto a Orden</button>'+
                '<button class="ml-3 btn btn-primary" id="btnAgregarFactura"><i style=" color: #f6fcfb;" data-feather="file-plus"></i> Añadir Factura</button>'+
                '<input type="hidden" id="idCompra" value="'+id_compra+'">' +
                '</div></div>'
            );
            //Dentro del div divTablaEntradas vamos a crear un div para ahi pintar la vista de productos restantes  
            $('#divTablaEntradas').append('<div class="mt-4" id="divTablaRestantes"></div>');
            //Agregamos otro div para pintar la vista de facturas
            $('#divTablaEntradas').append('<div class="mt-4" id="divTablaFacturas"></div>');
            //Agregar otro div para agregar un textarea para agregar la nota de la entrada
            $('#divTablaEntradas').append('<br><br><div class="mt-4"><label for="lblNotaEntrada">Nota de la Entrada:</label><textarea class="form-control" id="notaEntrada" name="notaEntrada" placeholder="Aquí puedes escribir alguna anotación relacionada con la entrada"></textarea></div>');
            //Agregar un boton para guardar la entrada
            $('#divTablaEntradas').append('<div class="mt-4"><button class="btn btn-success btn-lg" id="btnGuardarEntrada" disabled><i style=" color: #f6fcfb;" data-feather="save"></i> Cerrar orden de compra</button></div>');
            //Llamamos a la funcion para pintar la vista de productos restantes
            pintarTablaRestantes(id_compra);

            //Llamamos a la funcion para pintar la vista de facturas
            pintarTablaFacturas(id_compra);

            //Actualizamos los iconos de feather
            feather.replace();
        });
        //Actualizamos los iconos de feather
        feather.replace();

    });





    function pintarTablaRestantes(id_compra){
        //Vamos a pintar una tabla que muestra los productos de una orden de compra y la vamos a llenar con los productos de la orden de compra seleccionada y actualizar las cantidades de productos restantes
        var tabla = '<table id="tablaProductos" class="table table-bordered tablaPequena" style="width:100%">';
            tabla += '<thead>';
            tabla += '<tr>';
            tabla += '<th>EAN</th>';
            tabla += '<th>Producto</th>';
            tabla += '<th>Cantidad Solicitada</th>';
            tabla += '<th>Cantidad Facturada</th>';
            tabla += '<th>Cantidad Rechazada</th>';
            tabla += '<th>Cantidad Restante</th>';
            tabla += '</tr>';
            tabla += '</thead>';
            tabla += '<tbody>';
            console.log('LLamanos al servicio para obtener los productos de la orden de compra seleccionada');
            //Vamos a hacer una llamada al servicio para obtener los productos de la orden de compra seleccionada
            $.ajax({
                url: 'services/mainService.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'getProductosRestantesOrdenCompra',
                    controller: "Compra",
                    args: { 
                        'id_compra': id_compra
                    }                        
                },
                success: function(response) {
                    // console.log(response);
                    // Parseamos la cadena JSON para convertirla en un array de objetos
                    var data = JSON.parse(response);
                    //Si tiene datos la respuesta, entonces pintamos una tabla nueva con los datos en div divTablaRestantes
                    if(response.length > 0) {
                        $.each(data, function(index, producto) {
                            tabla += '<tr style="background-color: '+producto.color+';">';
                            tabla += '<td class="dt-right" >'+producto.ean+'</td>';
                            tabla += '<td class="">'+producto.comercial+'</td>';
                            tabla += '<td class="dt-right">'+producto.cant_solicitada+'</td>';
                            tabla += '<td class="dt-right">'+producto.cant_ingresada+'</td>';
                            tabla += '<td class="dt-right">'+producto.cant_rechazada+'</td>';
                            tabla += '<td class="dt-right">'+producto.cant_restante+'</td>';
                            tabla += '</tr>';
                        });
                        tabla += '</tbody>';
                        tabla += '<tfoot>';
                        tabla += '<tr>';
                        tabla += '<th></th>';
                        tabla += '<th></th>';
                        tabla += '<th></th>';
                        tabla += '<th></th>';
                        tabla += '<th></th>';
                        tabla += '</tr>';
                        tabla += '</tfoot>';
                        tabla += '</table>';

                        $('#divTablaRestantes').html(tabla);

                        // Inicializamos el datatable
                        $('#tablaProductos').DataTable( {
                            dom: 'frti',
                            language: {
                                "url": "js/spanish.js"
                            },
                            order: [],
                            stripeClasses: [], // Deshabilitamos las rayas de la tabla
                            paging: false // Deshabilitamos la paginación
                        });
                    } else {
                        $('#divTablaRestantes').html('<div class="alert alert-warning">No hay productos en esta orden de compra</div>');
                    }
                },
                error: function(error) {
                    alert('Hubo un error al obtener los productos de la orden de compra');
                }
            });
    }

    //Funcion para pintar la tabla de facturas
    function pintarTablaFacturas(id_compra){
    //Vamos a pintar una tabla que muestra las facturas de una orden de compra y la vamos a llenar con las facturas de la orden de compra seleccionada
    var tabla = '<hr><br><br><h5>Facturas relacionadas a la Orden de Compra</h5><table id="tablaFacturas" class="table table-bordered" style="width:100%">';
        tabla += '<thead>';
        tabla += '<tr>';
        tabla += '<th>UUID</th>';
        tabla += '<th>Fecha Factura</th>';
        tabla += '<th>Fecha Llegada</th>';
        tabla += '<th>Unidades<br>Facturadas</th>';
        tabla += '<th>Unidades<br>Aceptadas</th>';
        tabla += '<th>Unidades<br>Rechazadas</th>';
        tabla += '<th>Acciones</th>';
        tabla += '</tr>';
        tabla += '</thead>';
        tabla += '<tbody>';
        //Vamos a hacer una llamada al servicio para obtener las facturas de la orden de compra seleccionada
        $.ajax({
            url: 'services/mainService.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'getFacturasByCompraId',
                controller: "CompraFactura",
                args: { 
                    'id_compra': id_compra
                }                        
            },
            success: function(response) {
                // console.log(response);
                // Parseamos la cadena JSON para convertirla en un array de objetos
                var data = JSON.parse(response);
                //Agregamos una banderita que determine si hay facturas pendientes de ingresar a almacen
                var hayFacturasPendientes = true;

                //Si tiene datos la respuesta, entonces pintamos una tabla nueva con los datos en div divTablaFacturas
                if(response.length > 0) {
                    $.each(data, function(index, factura) {
                        hayFacturasPendientes = factura.ingreso_almacen == 0 ? true : false;
                        tabla += '<tr>';
                        tabla += '<td>'+factura.uuid+'</td>';
                        tabla += '<td>'+factura.fecha+'</td>';
                        tabla += '<td>'+factura.fecha_llegada+'</td>';
                        tabla += '<td class="dt-right">'+parseFloat(factura.tFacturado).toFixed(2)+'</td>';
                        tabla += '<td class="dt-right">'+parseFloat(factura.tAceptado).toFixed(2)+'</td>';
                        tabla += '<td class="dt-right">'+parseFloat(factura.tRechazado).toFixed(2)+'</td>';
                        tabla += '<td>'+
                                (factura.ingreso_almacen == 0 ?
                                    '<button class="btn btn-info btn-sm ml-2 mt-2 btnEditarFactura" uuid="'+factura.uuid+'" id="'+factura.id+'"><i style=" color: #f6fcfb;" data-feather="edit"></i> Editar</button>'+
                                    '<button class="btn btn-success btn-sm ml-2 mt-2 btnIngresarFactAlmacen" id="'+factura.id+'"><i style=" color: #f6fcfb;" data-feather="edit"></i> Ingresar a almacén</button>' :
                                    '<button class="btn btn-info btn-sm ml-lg-4 mt-2" disabled><i style=" color: #f6fcfb;" data-feather="check-square"></i>&nbsp; Ingresada</button>')
                                '</td>';
                        tabla += '</tr>';
                    });
                    tabla += '</tbody>';
                    tabla += '</table>';

                    $('#divTablaFacturas').html(tabla);

                    // Inicializamos el datatable
                    $('#tablaFacturas').DataTable( {
                        dom: 'frti',
                        language: {
                            "url": "js/spanish.js"
                        },
                        //Ajustamos alguas columnas
                        columnDefs: [
                            {
                                "targets": [6],
                                "visible": true,
                                "width": "280",
                                "orderable": false
                            },
                            {
                                "targets": [1,2,3,4,5],
                                "orderable": false
                            }
                        ],
                        order: [],
                        stripeClasses: [],
                        paging: false 
                    });

                    //Si no hay facturas pendientes de ingresar a almacen, deshabilitamos el boton de btnGuardarEntrada
                    if(hayFacturasPendientes) {
                        $('#btnGuardarEntrada').attr('disabled', true);
                    }else{
                        $('#btnGuardarEntrada').attr('disabled', false);
                    }

                    //Agregamos un titulo a la tabla utlizando la parte izquierda del cuadro de busqueda
                    // $('#tablaFacturas_filter').prepend('<h4 style="text-align: left;">Facturas de la Orden de Compra</h4>');
                    // // $('#tablaFacturas_filter label').contents().filter(function() {
                    // //     return this.nodeType == 3;
                    // // }).replaceWith('Buscar en calientes:');

                    //Actualizamos los iconos de feather
                    feather.replace();

                    
                } else {
                    $('#divTablaFacturas').html('<div class="alert alert-warning">No hay facturas en esta orden de compra</div>');
                }
            },
            error: function(error) {
                console.log(error);
            }
        });
    }


    //Creamos una funcion para pintar las ordenes de compra
    function pintaOrdenesCompra(id_prov, id_bodega){
        $.ajax({
            url: 'services/mainService.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'getComprasPendientes',
                controller: "Compra",
                args: { 
                    'id_prov': id_prov,
                    'id_bodega': id_bodega
                }                        
            },
            success: function(response) {
                // console.log(response);
                // Parseamos la cadena JSON para convertirla en un array de objetos
                var data = JSON.parse(response);
                //Si tiene datos la respuesta, entonces pintamos una tabla nueva con los datos en div divTablaEntradas
                if(data.ordenesCompra.length > 0) {
                    var tabla = '<table id="tablaEntradas" class="table table-striped table-bordered table-hover" style="width:100%">';
                    tabla += '<thead>';
                    tabla += '<tr>';
                    tabla += '<th>Orden de Compra</th>';
                    tabla += '<th>Proveedor</th>';
                    tabla += '<th>Bodega</th>';
                    tabla += '<th>Cantidad Esperada</th>';
                    tabla += '<th>Monto Neto de la compra solicitada</th>';
                    tabla += '<th>Fecha de Solicitud</th>';
                    tabla += '<th>Facturas</th>';
                    tabla += '<th>Dar Entrada</th>';
                    tabla += '</tr>';
                    tabla += '</thead>';
                    tabla += '<tbody>';
                    $.each(data.ordenesCompra, function(index, compra) {
                        tabla += '<tr>';
                        tabla += '<td class="dt-right">'+compra.id+'</td>';
                        tabla += '<td>'+compra.nombre_proveedor+'</td>';
                        tabla += '<td>'+compra.nombre_bodega+'</td>';
                        tabla += '<td class="dt-right">'+compra.total_esperado+'</td>';
                        tabla += '<td class="dt-right">'+compra.total+'</td>';
                        tabla += '<td class="dt-right">'+compra.alta+'</td>';
                        tabla += '<td>'+compra.facturas+'</td>';
                        tabla += '<td>'+compra.btn+'</td>';
                        tabla += '</tr>';
                    });
                    tabla += '</tbody>';
                    tabla += '<tfoot>';
                    tabla += '<tr>';
                    tabla += '<th>Orden de Compra</th>';
                    tabla += '<th>Proveedor</th>';
                    tabla += '<th>Bodega</th>';
                    tabla += '<th>Cantidad Esperada</th>';
                    tabla += '<th>Monto Neto de la compra solicitada</th>';
                    tabla += '<th>Fecha de Solicitud</th>';
                    tabla += '<th>Facturas</th>';
                    tabla += '<th>Estado</th>';
                    tabla += '</tr>';
                    tabla += '</tfoot>';
                    tabla += '</table>';

                    $('#divTablaEntradas').html(tabla);

                    // Inicializamos el datatable
                    $('#tablaEntradas').DataTable({
                        caption: 'Ordenes de Compra',
                        dom: 'frti',
                        language: {
                            "url": "js/spanish.js"
                        },
                        order: [],
                        stripeClasses: [],
                        paging: false // Deshabilitamos la paginación
                    });
                    //Actualizamos los iconos de feather
                    feather.replace();
                    //Actualizamos el titulo de la tabla
                    $('#tituloTabla').html('');

                } else {
                    //Limipiamos el titulo de la tabla
                    $('#tituloTabla').html('');
                    $('#divTablaEntradas').html('<div class="alert alert-warning">No hay ordenes de compra pendientes para este proveedor en esta bodega</div>');
                }
            },
            error: function(error) {
                alert('Hubo un error al obtener las ordenes de compra');
            }
        });
    } // Fin de la funcion pintaOrdenesCompra   



</script>