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
        <h3>&nbsp; Validación de Entradas </h3>
    </div>
</div>


<div class="card card-principal">
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 col-sm-6">
                <label for="lblProveedor">Proveedor:</label>
                <select class="form-control" id="proveedor" name="proveedor">
                    <option selected disabled value="0">Selecciona un proveedor</option>
                    <?php
                    $compra = new Compra();
                    $proveedores = $compra->getProveedoresConOrdenesPorValidar();
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
                <a href="validaCostos.php"><button class="mt-lg-4 btn btn-info btn-lg"><i style=" color: #f6fcfb;" data-feather="search"></i> Consulta Avanzada </button></a>
            </div>
        </div> -->

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


<!-- MODAL DE CONFIRMACION DE CERRIE DE VALIDACION DE ORDEN DE COMPRA -->
<div class="modal fade" id="modalConfirmCloseOrdenCompra" tabindex="-1" role="dialog" aria-labelledby="modalConfirmCloseTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalConfirmCloseTitle"></h5>
            </div>
            <div class="modal-body">
                <div class="alert alert-success" role="alert">
                    <h4 class="alert-heading">Confirmación</h4>
                    <p>¿Deseas cerrar la validación de la orden de compra?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="btnConfirmCloseOrdenCompra">Confirmar cierre de validación</button>
                <button type="button" class="btn btn-warning" id="btnProcessConfirmClose" style="display: none;"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>



<!-- Modal de info de costo -->
<div class="modal fade" id="modalInfoCostoProducto" tabindex="-1" role="dialog" aria-labelledby="modalConfirmCloseTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg " role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalInfoCostoProductoTitle"></h5>
            </div>
            <div class="modal-body">
                <table class="table table-striped table-bordered tablaPequena">
                    <thead>
                        <tr>
                            <th><small>Precio lista</small></th>
                            <th><small>Costo Pactado</small></th>
                            <th><small>Costo Ingreso</small></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td id="precioListaProducto" ></td>
                            <td id="costoPactadoProducto" ></td>
                            <td id="costoIngresoProducto" ></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <!-- <button type="button" class="btn btn-success" id="btnConfirmCloseOrdenCompra">Confirmar cierre de validación</button>
                <button type="button" class="btn btn-warning" id="btnProcessConfirmClose" style="display: none;"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button> -->
            </div>
        </div>
    </div>
</div>


<script>
    $(document).ready(function() {

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
                            'modoValidacion': 1
                        }                        
                    },
                    success: function(response) {
                        // // console.log(response);
                        $('#bodega').html('<option selected disabled value="0">Selecciona una bodega</option>');
                        response.forEach(bodega => {
                            $('#bodega').append('<option value="'+bodega.id+'">'+bodega.nombre+'</option>');
                        });
                    },
                    error: function(error) {
                        // console.log(error);
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

        //Creamos un evento click para el boton de regresar llamamos a la función para pintar las ordenes de compra
        $(document).on('click', '#btnRegresar', function() {
            var id_prov = $('#proveedor').val();
            var id_bodega = $('#bodega').val();
            pintaOrdenesCompra(id_prov, id_bodega);
        });


        $(document).on('click', '.btnValidar', function() {
            var id_compra = $(this).attr('id');
            //Limpiamos el contenido del div divTablaEntradas para pintar la vista de ingreso de facturas en la orden de compra seleccionada
            $('#divTablaEntradas').html('');
            //Actualizamos el titulo de la tabla y agregamos un boton para regresar
            
            $('#tituloTabla').html('<div class="d-flex justify-content-between mb-3">Validación de facturas para la Orden de Compra: '+id_compra+
                '<div class="d-flex justify-content-end">'+
                '<button class="ml-3 btn btn-secondary" id="btnRegresar"><i style=" color: #f6fcfb;" data-feather="arrow-left"></i> Regresar Ordenes de compra</button>'+
                // '<button class="ml-3 btn btn-primary" id="btnAgregarProducto"><i style=" color: #f6fcfb;" data-feather="package"></i> Agregar Producto a Orden</button>'+
                // '<button class="ml-3 btn btn-success" id="btnAgregarFactura"><i style=" color: #f6fcfb;" data-feather="file-plus"></i> Añadir Factura</button>'+
                '<input type="hidden" id="idCompra" value="'+id_compra+'">' +
                '<input type="hidden" id="idFactura" value="">'+
                '</div></div>'
            );
            //Dentro del div divTablaEntradas vamos a crear un div para ahi pintar la vista de productos restantes  
            $('#divTablaEntradas').append(
                '<div class="mt-4" id="divHojaValidacionFactura" style="display: none;">' +
                    '<div class="row">' +
                        '<div class="col-md-3">' + 
                            '<label for="lblUUIDAlmacen">UUID:</label>' +
                            '<label class="form-control form-control-sm campoFactura" id="uuidFacturaAlmacen" name="uuidAlmacen" readonly></label>' +
                        '</div>' +
                        '<div class="col-md-3">' +
                            '<label for="lblFechaFacturaAlmacen">Fecha Factura:</label>' +
                            '<input type="date" class="form-control form-control-sm campoFactura" id="fechaFacturaAlmacen" name="fechaFacturaAlmacen">' +
                        '</div>' +
                        '<div class="col-md-3">' +
                            '<label for="lblFechaLlegadaAlmacen">Fecha Llegada:</label>' +
                            '<input type="date" class="form-control form-control-sm campoFactura" id="fechaLlegadaAlmacen" name="fechaLlegadaAlmacen">' +
                        '</div>' +
                        '<div class="col-md-3">' +
                            '<label for="lblFechaLlegadaAlmacen">Fecha Compromiso:</label>' +
                            '<input type="date" class="form-control form-control-sm campoFactura" id="fechaCompromisoAlmacen" name="fechaCompromisoAlmacen">' +
                        '</div>' +
                    '</div>' +
                    
                    '<div class="row">' +
                        '<!-- Contador de dias de alerta -->' +
                        '<div class="col-md-3">' +
                            '<label for="lblDiasAlertaAlmacen">Días de Alerta:</label>' +
                            '<input type="number" class="form-control form-control-sm" id="diasAlertaAlmacen" name="diasAlertaAlmacen">' +
                        '</div>' +
                    '</div>' +
                                    
                    '<div class="row mt-lg-4">' +
                        '<div class="col-md-12">' +
                            '<h5>Productos de la factura</h5>' +
                            '<table id="tablaProductosFacturaAlmacen" class="table table-striped table-bordered tablaPequena">' +
                                '<thead>' +
                                    '<tr>' +
                                        '<th><small>Producto</small></th>' +
                                        '<th><small>Código de <br>proveedor</small></th>' +
                                        '<th><small>Unidades<br>Facturadas</small></th>' + 
                                        '<th><small>Costo<br>Unitario<br>Bruto</small></th>' +
                                        '<th><small>Costo<br>Subtotal<br>Bruto</small></th>' + 
                                        '<th><small>Unidades<br>Rechazadas</small></th>' + 
                                        '<th><small>Descuento<br>&nbsp; ($)</small></th>' + 
                                        '<th><small>Costo<br>Después de<br>Rechazo</small></th>' +
                                        '<th><small>Caducidad</small></th>' + 
                                    '</tr>' +
                                '</thead>' +
                                '<tbody>' +
                                '</tbody>' +
                            '</table>' +
                    '</div> <!-- final col-md-12 -->'+
                '</div> <!-- final row --></div>'+
            '<div class="mt-4" id="divTotalesFactura"></div>');
            //Agregamos otro div para pintar la vista de facturas
            $('#divTablaEntradas').append('<div class="mt-4" id="divTablaFacturas"></div>');
            //Agregar otro div para agregar un textarea para agregar la nota de la entrada
            // $('#divTablaEntradas').append('<br><br><div class="mt-4"><label for="lblNotaEntrada">Nota de la Entrada:</label><textarea class="form-control" id="notaEntrada" name="notaEntrada" placeholder="Aquí puedes escribir alguna anotación relacionada con la entrada"></textarea></div>');
            //Agregar un boton para guardar la entrada
            $('#divTablaEntradas').append('<div class="mt-lg-4"><button class="btn btn-success btn-lg" id="btnFinalizarValidacion" disabled><i style=" color: #f6fcfb;" data-feather="save"></i> Guardar e ingresar a almacén</button></div>');
            //Llamamos a la funcion para pintar la vista de productos restantes
            // pintarTablaRestantes(id_compra);

            //Llamamos a la funcion para pintar la vista de facturas
            pintarTablaFacturas(id_compra);

            //Actualizamos los iconos de feather
            feather.replace();
        });


        //Creamos un evento que al pulsar un boton de la clase infoCosto muestre un modal con la información del costo del producto
        $(document).on('click', '.infoCosto', function() {
            // console.log('click en el boton con el idProducto: '+$(this).attr('id'));
            var fechaFactura = $('#fechaFacturaAlmacen').val();

            // console.log('Request para obtener el costo del producto con id: '+$(this).attr('id')+' y fecha de factura: '+fechaFactura + ' y id de compra: '+$('#idCompra').val());

            //Llamamos al servicio para obtener la información del costo del producto a traves del id del producto y gmcv_producto_compra
            $.ajax({
                url: 'services/mainService.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'getCostosProducto',
                    controller: "GmcvPrecio",
                    args: { 
                        'id_prod': $(this).attr('id'),
                        'fechaFactura': fechaFactura,
                        'id_compra': $('#idCompra').val()
                    }
                },
                success: function(response) {
                    // console.log(response);

                    // //Actualizamos los inputs del modal con la información del costo del producto
                    // $('#precioListaProducto').text(response.precioListaCatalogo);
                    // $('#costoPactadoProducto').text(response.costoPactadoDB);
                    // $('#costoIngresoProducto').text(response.costoIngresoDB);
                    //Hacemos un pequeño retraso para que se actualicen los inputs antes de mostrar el modal
                    // $('#modalInfoCostoProducto').mod l('show');
                    alert(
                        'Precio lista: $'+ parseFloat(response.precioListaCatalogo).toFixed(2)+
                        '\nCosto Pactado: $'+ parseFloat(response.costoPactadoDB).toFixed(2)+
                        '\nCosto Ingreso: $'+ parseFloat(response.costoIngresoDB).toFixed(2));
                },

                error: function(error) {
                    // console.log(error);
                    //Mostramos un mensaje de error
                    alert('Ocurrió un error al obtener la información del costo del producto');
                }
            });
        });

        $(document).on('click', '#btnConfirmCloseOrdenCompra', function() {
            console.log('Vamos a cerrar la validación de la orden de compra');
            var id_compra = $(this).attr('idCompra');
            //Estamos listos para cerrar la compra y la validación, vamos a llamar al metodo para cerrar la validación: confirmaCerrarValidacionOrdenCompra
            $.ajax({
                url: 'services/mainService.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'confirmaCerrarValidacionOrdenCompra',
                    controller: "Compra",
                    args: { 
                        'id_compra': id_compra
                    }
                },
                beforeSend: function() {
                    $('#btnConfirmCloseOrdenCompra').hide();
                    $('#btnProcessConfirmClose').show();
                },
                success: function(response) {
                    // console.log(response);
                    response = JSON.parse(response);
                    if(response.status == 1) {
                        //Mostramos un mensaje de exito
                        alert(response.message);
                        //Cerramos el modal de confirmación de cierre de validación
                        $('#modalConfirmCloseOrdenCompra').modal('hide');
                        //Recargamos la tabla de ordenes de compra
                        pintaOrdenesCompra($('#proveedor').val(), $('#bodega').val());
                        //Reestablecemos los botones de btnConfirmCloseOrdenCompra
                        $('#btnConfirmCloseOrdenCompra').show();
                        $('#btnProcessConfirmClose').hide();
                    } else {
                        //Mostramos un mensaje de error
                        alert(response.message);
                        // console.log(response);
                    }
                },
                error: function(error) {
                    // console.log(error);
                    //Mostramos un mensaje de error
                    alert('Ocurrió un error al cerrar la validación de la orden de compra');
                }
            });
        });


        $(document).on('click', '#btnFinalizarValidacion', function() {
            var id_compra = $('#idCompra').val();
            console.log('La compra que vamoa a cerrar es: '+id_compra);
            //Agregamos el id de compra al boton de btnConfirmCloseOrdenCompra
            $('#btnConfirmCloseOrdenCompra').attr('idCompra', id_compra);
            //Agregamos un titulos al modal de confirmación de cierre de validación
            $('#modalConfirmCloseTitle').html('Confirmación de cierre de validación de la Orden de Compra: <b>'+id_compra+'</b>');
            //Mostramos el modal de confirmación de cierre de validación
            $('#modalConfirmCloseOrdenCompra').modal('show');
            
            
        });

        //Creamos un evento para cuendo se valida una factura a partir de la clase btnValidarFact
        $(document).on('click', '.btnValidarFact', function() {
            var id_factura = $(this).attr('id');
            var id_compra = $('#idCompra').val();
            //Actualizamos el input oculto con el id de la factura seleccionada
            $('#idFactura').val(id_factura);
            //Llamamos a la funcion para validar la factura
            $.ajax({
                url: 'services/mainService.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'getProductosByFacturaValidacion',
                    controller: "CompraFacturaProd",
                    args: { 
                        'id_factura': id_factura,
                        'id_prod_compra': id_compra
                    }
                },
                beforeSend: function() {
                    $('#tablaProductosFacturaAlmacen tbody').html('');
                    //Mostramos el div que contiene la tabla de productos
                    $('#divHojaValidacionFactura').show();
                    
                    
                },
                success: function(response) {
                    // console.log(response);
                    // $('#modalValidarCostosTitle').html('Validación de Costos de la Factura: '+id_factura);

                    //En caso de que la datatable es inicializada, la destruimos
                    if ($.fn.DataTable.isDataTable('#tablaProductosFacturaAlmacen')) {
                        $('#tablaProductosFacturaAlmacen').DataTable().destroy();
                    }
                    //Limpiamos el div de facturas para dejar mas limpia la vista
                    $('#divTablaFacturas').html('');
                    //Limpiamos todos los inputs de la factura a traves de la clase campoFactura
                    $('.campoFactura').val('');
                    //Limpiamos el contenido de tablaProductosFacturaAlmacen
                    $('#tablaProductosFacturaAlmacen tbody').html('');
                    //Agregamos los productos de la factura a la tabla
                    var respuesta = JSON.parse(response);
                    var data = respuesta.productos;
                    var factura = respuesta.factura;

                    let subtotalBruto = 0;
                    let descuentoTotal = 0;
                    let subtotalNeto = 0;
                    let totalIVA = 0;
                    let totalIEPS = 0;  
                    let totalTotal = 0;

                    $.each(data, function(index, producto) {
                        //Creamos una variable para aplicar ieps e iva
                        let iepsXL = producto.totalIEPSxL * parseFloat(producto.cantidad_aceptada);
                        let ieps = (parseFloat(producto.costoPactado) * (1+parseFloat(producto.ieps)) - parseFloat(producto.costoPactado)) + iepsXL;
                        let iva = (parseFloat(producto.costoPactado) + ieps) * (1+parseFloat(producto.iva)) - (parseFloat(producto.costoPactado)+ieps);
                        let descuento = parseFloat(producto.costoPactado) * parseFloat(producto.cantidad_rechazada);

                        subtotalBruto += parseFloat(producto.costoPactado) * parseFloat(producto.cantidad_facturada);
                        descuentoTotal += descuento;
                        subtotalNeto += parseFloat(producto.costoPactado) * parseFloat(producto.cantidad_aceptada);
                        totalIEPS += ieps * parseFloat(producto.cantidad_aceptada);

                        totalIVA += iva * parseFloat(producto.cantidad_aceptada);


                        $('#tablaProductosFacturaAlmacen tbody').append('<tr idProducto="'+ producto.id_prod +'" id="fila-'+producto.id_prod+'">'+
                            '<td>'+producto.comercial+'</td>'+
                            '<td>'+producto.cod_prod_prov+'</td>'+
                            '<input type="hidden" class="" id="descDespCP-' + producto.id_prod + '" idProdCompra="'+ producto.id_prod_compra +'" cIngreso="'+ producto.costoIngreso +'" value="' + producto.sumaDescuentoDespCP + '" />'+
                            '<input type="hidden" class="" id="id_cfp-' + producto.id_prod + '" idProdCompra="'+ producto.id_prod_compra +'" value="' + producto.id_cfp + '" />'+
                            '<input type="hidden" class="iepsFila" iepsxl="'+ producto.totalIEPSxL +'" porcentaje="' + producto.ieps + '" id="ieps-' + producto.id_prod + '" value="' + ieps+ '" />'+
                            '<input type="hidden" class="ivaFila" porcentaje="' + producto.iva + '" id="iva-' + producto.id_prod + '" value="' + iva  + '" />'+
                            '<input type="hidden" style="width: 90px;" id="cantidadAceptada-' + producto.id_prod + '" value="' + parseFloat(producto.cantidad_aceptada).toFixed(2) + '" pattern="^[0-9]*\\.?[0-9]{0,2}$" />'+
                            '<td class="dt-right"><input class="cantidadFacturada" type="number" style="width: 90px; text-align: right;" id="cantidadFacturada-' + producto.id_prod + '" value="' + parseFloat(producto.cantidad_facturada).toFixed(2) + '" pattern="^[0-9]*\\.?[0-9]{0,2}$" /></td>'+
                            '<td class="dt-right"> <span class="shadow-none badge badge-info infoCosto" nombreProducto="'+producto.comercial+'" id="'+ producto.id_prod +'" ><i style=" color: #f6fcfb;  width: 12px; height: 12px;" data-feather="info"></i></span> <input class="costoUnitarioBruto" valorReal="' + parseFloat(producto.costoPactado) + '" type="number" style="width: 105px; text-align: right;" id="costoUnitarioBruto-' + producto.id_prod + '" value="' + parseFloat(producto.costoPactado).toFixed(2) + '" pattern="^[0-9]*\\.?[0-9]{0,2}$" /></td>'+
                            '<td class="dt-right"><input class="costoSubTotalBruto" valorReal="' + (parseFloat(producto.costoPactado) * parseFloat(producto.cantidad_facturada)).toFixed(2) + '" type="number" style="width: 115px; text-align: right;" id="costoSubTotalBruto-' + producto.id_prod + '" value="' + (parseFloat(producto.costoPactado) * parseFloat(producto.cantidad_facturada)).toFixed(2) + '" pattern="^[0-9]*\\.?[0-9]{0,2}$" /></td>'+
                            '<td class="dt-right"><input class="rechazado" type="number" style="width: 90px; text-align: right;" id="rechazado-' + producto.id_prod + '" value="' + parseFloat(producto.cantidad_rechazada).toFixed(2) + '" pattern="^[0-9]*\\.?[0-9]{0,2}$" /></td>'+
                            '<td class="dt-right"><input class="descuento disabled-input" valorReal="'+parseFloat(descuento)+'" type="number" style="width: 105px; text-align: right;" id="descuento-' + producto.id_prod + '" value="' + parseFloat(descuento).toFixed(2) + '" pattern="^[0-9]*\\.?[0-9]{0,2}$" /></td>'+
                            '<td class="dt-right"><input class="costoDespuesRechazo disabled-input" valorReal="'+ (parseFloat(producto.costoPactado) * parseFloat(producto.cantidad_aceptada)) +'" type="number" style="width: 115px; text-align: right;" id="costoDespuesRechazo-' + producto.id_prod + '" value="' + (parseFloat(producto.costoPactado) * parseFloat(producto.cantidad_aceptada)).toFixed(2) + '" pattern="^[0-9]*\\.?[0-9]{0,2}$" /></td>'+
                            '<td><input type="date" style="width: 110px;" id="caducidad-' + producto.id_prod + '" value="'+producto.caducidad+'" /></td>'+
                            '</tr>'
                        );                        
                    });
                    //Llenamos los campos de la factura
                    $('#uuidFacturaAlmacen').text(factura.uuid);
                    $('#fechaFacturaAlmacen').val(factura.fecha);
                    $('#fechaLlegadaAlmacen').val(factura.fecha_llegada);
                    factura.fecha_compromiso ? $('#fechaCompromisoAlmacen').val(factura.fecha_compromiso) : $('#fechaCompromisoAlmacen').val('');
                    factura.dias_alerta ? $('#diasAlertaAlmacen').val(factura.dias_alerta) : $('#diasAlertaAlmacen').val('');
                    
                    //Agregamos un boton para guardar la validacion de la factura y otro para cancelar y regresar a facturas
                    // $('#divTablaFacturas').html('<button class="btn btn-primary btn-sm ml-2 mt-2" id="btnGuardarValidacionFactura"><i style=" color: #f6fcfb;" data-feather="save"></i> Guardar Validación</button>'+
                    //     '<button class="btn btn-secondary btn-sm ml-2 mt-2" id="btnCancelarValidacionFactura"><i style=" color: #f6fcfb;" data-feather="x"></i> Cancelar</button>'
                    // );

                    //Dentro del div divTotalesFactura vamos a pintar los totales de la factura
                    $('#divTotalesFactura').html('<div class="row align-content-end"><div class="col-md-3 col-sm-12 col-lg-8">'+
                        '<button class="btn btn-success" id="btnProcessConfirmClose" type="button" disabled style="display: none;"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...</button>'+
                        '<button class="btn btn-primary btn-sm ml-2 mt-2 mb-lg-4" id="btnGuardarValidacionFactura"><i style=" color: #f6fcfb;" data-feather="save"></i> Guardar Validación</button>'+
                        '<button class="btn btn-secondary btn-sm ml-2 mt-2 mb-lg-4" id="btnCancelarValidacionFactura"><i style=" color: #f6fcfb;" data-feather="x"></i> Cancelar</button></div>'+
                        '<div class="col-md-3 col-sm-12 col-lg-3">'+
                            '<table class="table table-striped table-bordered tablaPequena">'+
                                
                                '<tbody>' +
                                    '<tr>' +
                                        '<th colspan="2">Totales Factura</th>' +
                                        '<th colspan="2">Totales después de rechazos</th>' +
                                    '</tr>' +
                                    '<tr>' +
                                        '<td class="dt-right">Subtotal Bruto</td>' +
                                        '<td class=""><label class="dt-right" valorReal="" style="width: 150px; display: block;" id="subtotalBrutoFactura2"></label></td>' +

                                        '<td class="dt-right">Subtotal Bruto</td>' +
                                        '<td class=""><label class="dt-right" valorReal="" style="width: 150px; display: block;" id="subtotalBrutoFactura"></label></td>' +
                                        
                                    '</tr>' +

                                    '<tr>' +
                                        '<td class="dt-right">Descuento Total</td>' +
                                        '<td class=""><label class="dt-right" valorReal="" style="width: 150px; display: block;" id="descuentoTotalFactura2"></label></td>' +

                                        '<td class="dt-right">Descuento Total</td>' +
                                        '<td class=""><label class="dt-right" valorReal="" style="width: 150px; display: block;" id="descuentoTotalFactura"></label></td>' +
                                    '</tr>' +

                                    '<tr>' +
                                        '<td class="dt-right">Subtotal Neto</td>' +
                                        '<td class=""><label class="dt-right" valorReal="" style="width: 150px; display: block;" id="subtotalNetoFactura2"></label></td>' +

                                        '<td class="dt-right">Subtotal Neto</td>' +
                                        '<td class=""><label class="dt-right" valorReal="" style="width: 150px; display: block;" id="subtotalNetoFactura"></label></td>' +
                                    '</tr>' +

                                    '<tr>' +
                                        '<td class="dt-right">IVA</td>' +
                                        '<td class=""><label class="dt-right" valorReal="" style="width: 150px; display: block;" id="ivaFactura2"></label></td>' +

                                        '<td class="dt-right">IVA</td>' +
                                        '<td class=""><label class="dt-right" valorReal="" style="width: 150px; display: block;" id="ivaFactura"></label></td>' +
                                    '</tr>' +

                                    '<tr>' +
                                        '<td class="dt-right">IEPS</td>' +
                                        '<td class=""><label class="dt-right" valorReal="" style="width: 150px; display: block;" id="iepsFactura2"></label></td>' +
                                        
                                        '<td class="dt-right">IEPS</td>' +
                                        '<td class=""><label class="dt-right" valorReal="" style="width: 150px; display: block;" id="iepsFactura"></label></td>' +
                                    '</tr>' +
                                    
                                    '<tr>' +
                                        '<td class="dt-right">Total</td>' +
                                        '<td class=""><label class="dt-right" valorReal="" style="width: 150px; display: block;" id="totalFactura2"></label></td>' +

                                        '<td class="dt-right">Total</td>' +
                                        '<td class=""><label class="dt-right" valorReal="" style="width: 150px; display: block;" id="totalFactura"></label></td>' +
                                    '</tr>'+
                                '</tbody>'+
                            '</table>'+
                        '</div>'+
                    '</div>');

                    // Formateamos los numeros a moneda
                    const formatter = new Intl.NumberFormat('es-MX', {
                        style: 'currency',
                        currency: 'MXN'
                    });
                    
                    $('#subtotalBrutoFactura').text(formatter.format(subtotalBruto));
                    $('#descuentoTotalFactura').text(formatter.format(descuentoTotal));
                    $('#subtotalNetoFactura').text(formatter.format(subtotalNeto));
                    $('#ivaFactura').text(formatter.format(totalIVA));
                    $('#iepsFactura').text(formatter.format(totalIEPS));
                    $('#totalFactura').text(formatter.format(subtotalNeto + totalIVA + totalIEPS));
                    //Actualizamos los valores reales de los labels
                    $('#subtotalBrutoFactura').attr('valorReal', subtotalBruto);
                    $('#descuentoTotalFactura').attr('valorReal', descuentoTotal);
                    $('#subtotalNetoFactura').attr('valorReal', subtotalNeto);
                    $('#ivaFactura').attr('valorReal', totalIVA);
                    $('#iepsFactura').attr('valorReal', totalIEPS);
                    $('#totalFactura').attr('valorReal', subtotalNeto + totalIVA + totalIEPS);
                    


                    //Inicializamos el datatable
                    $('#tablaProductosFacturaAlmacen').DataTable({
                        dom: 'frti',
                        language: {
                            "url": "js/spanish.js"
                        },
                        columnDefs: [{
                                "targets": [3],
                                "visible": true,
                                "width": "130",
                                "orderable": false
                            },
                            {
                                "targets": [2,5],
                                "visible": true,
                                "width": "110",
                                "orderable": false
                            },{
                                "targets": [4,6],
                                "visible": true,
                                "width": "135",
                                "orderable": false
                            },
                            {
                                "targets": [7],
                                "visible": true,
                                "width": "115",
                                "orderable": false
                            },
                            {
                                "targets": [8],
                                "visible": true,
                                "width": "130",
                                "orderable": false
                            }
                        ],
                        order: [],
                        stripeClasses: [],
                        paging: false, // Deshabilitamos la paginación
                        searching: false,
                        info: false
                    });
                    //Ocultamos el boton de btnFinalizarValidacion
                    $('#btnFinalizarValidacion').hide();
                    //Actualiza los totales de la factura que vienen impresos en ella
                    calculaTotalesFacturaImpresos();
                    //Actualizamos los iconos de feather
                    feather.replace();
                },
                error: function(error) {
                    // console.log(error);
                    //Escribimos un mensaje de error en la tabla de productos
                    $('#tablaProductosFacturaAlmacen tbody').html('<tr><td colspan="9">Error al obtener los productos de la factura</td></tr>');
                }
            });
        });

        //Creamos un eventos para cuando se altera un input de la clase costoUnitarioBruto
        $(document).on('change', '.costoUnitarioBruto', function() {
            var idAttr = $(this).attr('id');
            id= idAttr.split('-')[1];
            verificaInputValue('costoUnitarioBruto-'+id);
            $(this).attr('valorReal', $(this).val());
            //calculaPrecioLista(id);
            calculaCostoSubtotalBruto(id);
            calculaCostoDespuesRechazo(id);
            calculaDescuento(id);
            calculaIEPS(id);
            calculaIVA(id);            
            calculaTotalesFactura();
            calculaTotalesFacturaImpresos();
        });


        //Creamos un eventos para cuando se altera un input de la clase cantidadFacturada
        $(document).on('change', '.cantidadFacturada', function() {
            var idAttr = $(this).attr('id');
            id= idAttr.split('-')[1];
            verificaInputValue('cantidadFacturada-'+id);
            calculaUnidadesAceptadas(id);
            calculaCostoSubtotalBruto(id);
            calculaCostoDespuesRechazo(id);
            calculaDescuento(id);
            calculaIEPS(id);
            calculaIVA(id);
            calculaTotalesFactura();
            calculaTotalesFacturaImpresos();
        });

        //Creamos un evento para cuando se altera el costo subtotal bruto
        $(document).on('change', '.costoSubTotalBruto', function() {
            var idAttr = $(this).attr('id');
            id= idAttr.split('-')[1];
            verificaInputValue('costoSubTotalBruto-'+id);
            $(this).attr('valorReal', $(this).val());
            calculaCostoUnitarioBruto(id);
            //calculaPrecioLista(id);
            calculaCostoSubtotalBruto(id);
            calculaCostoDespuesRechazo(id);
            calculaDescuento(id);
            calculaIEPS(id);
            calculaIVA(id);
            calculaTotalesFactura();
            calculaTotalesFacturaImpresos();
        });

        //Creamos un evento para cuando se altera el input de unidades rechazadas
        $(document).on('change', '.rechazado', function() {
            var idAttr = $(this).attr('id');
            id= idAttr.split('-')[1];
            verificaInputValue('rechazado-'+id);
            calculaUnidadesAceptadas(id);
            calculaCostoDespuesRechazo(id);
            calculaDescuento(id);
            calculaIEPS(id);
            calculaIVA(id);
            calculaTotalesFactura();
            calculaTotalesFacturaImpresos();   
        });

        //Creamos un evento que al precionar el boton btnCancelarValidacionFactura regresemos a las facturas de la orden de compra
        $(document).on('click', '#btnCancelarValidacionFactura', function() {
            //Limpiamos el contenido del div divTablaEntradas para pintar la vista de ingreso de facturas en la orden de compra seleccionada
            $('#divTablaFacturas').html('');
            ///Limpiamos el contenido del div divHojaValidacionFactura
            $('#divHojaValidacionFactura tbody').html('');
            //Eliminamos la tabla de totales de la factura
            $('#divTotalesFactura').html('');

            //Limipamos la tabla de productos y oculatamos el divHojaValidacionFactura
            $('#tablaProductosFacturaAlmacen tbody').html('');
            $('#divHojaValidacionFactura').hide();

            var id_compra = $('#idCompra').val();
            pintarTablaFacturas(id_compra);
        }); 


        //Creamos un evento para guardar la validacion de la factura
        $(document).on('click', '#btnGuardarValidacionFactura', function() {
            //No puede haber fechas vacias
            if( $('#fechaLlegadaAlmacen').val() == '' ) {
                alert('La fecha de llegada no puede estar vacía.');
                $('#fechaLlegadaAlmacen').focus();
                return;
            }
            if( $('#fechaFacturaAlmacen').val() == '' ) {
                alert('La fecha de la factura no puede estar vacía.');
                $('#fechaFacturaAlmacen').focus();
                return;
            }
            //La fecha compromiso tampoco puede estar vacia
            if($('#fechaCompromisoAlmacen').val() == '') {
                alert('La fecha compromiso no puede estar vacía.');
                $('#fechaCompromisoAlmacen').focus();
                return;
            }

            // Convertir las cadenas de fecha a objetos Date para verificar que haya coherencia
            var fechaLlegadaDate = new Date($('#fechaLlegadaAlmacen').val());
            var fechaFacturaAlmacenDate = new Date($('#fechaFacturaAlmacen').val());
            var fechaCompromisoDate = new Date($('#fechaCompromisoAlmacen').val());

            //El input de dias de alerta tampoco puede estar vacio y debe ser un valor entero
            if($('#diasAlertaAlmacen').val() == '' || isNaN($('#diasAlertaAlmacen').val())) {
                alert('El campo de días de alerta no puede estar vacío y debe ser un valor numérico entero.');
                $('#diasAlertaAlmacen').focus();
                return;
            }
            
            // Comparar las fechas
            if(fechaLlegadaDate < fechaFacturaAlmacenDate) {
                // La fecha de llegada es menor que la fecha de almacenamiento
                alert('La fecha de llegada no puede ser menor a la fecha de la factura.');
                // Aquí puedes tomar otras acciones, como devolver el foco al campo de fecha de llegada
                $('#fechaLlegadaAlmacen').focus();
                return;
            }


            //Verificamos que la fecha compromiso no sea menor a la fecha de almacenamiento
            if(fechaCompromisoDate < fechaFacturaAlmacenDate) {
                // La fecha de compromiso es menor que la fecha de almacenamiento
                alert('La fecha de compromiso no puede ser menor a la fecha de la factura.');
                // Aquí puedes tomar otras acciones, como devolver el foco al campo de fecha de compromiso
                $('#fechaCompromisoAlmacen').focus();
                return;
            }      

            // Crear un objeto con la información de la factura
            var factura = {
                id_factura: $('#idFactura').val(),
                id_compra: $('#idCompra').val(),
                uuid: $('#uuidFacturaAlmacen').text(),
                fecha: $('#fechaFacturaAlmacen').val(),
                fecha_llegada: $('#fechaLlegadaAlmacen').val(),
                fecha_compromiso: $('#fechaCompromisoAlmacen').val(),
                dias_alerta: $('#diasAlertaAlmacen').val(),
                //Totales de la factura
                subtotal_bruto: $('#subtotalBrutoFactura2').attr('valorReal'),
                descuento_total: $('#descuentoTotalFactura2').attr('valorReal'),
                subtotal_neto: $('#subtotalNetoFactura2').attr('valorReal'),
                iva: $('#ivaFactura2').attr('valorReal'),
                ieps: $('#iepsFactura2').attr('valorReal'),
                total: $('#totalFactura2').attr('valorReal')
            };
            //Obtnemos los productos de la factura y creamos un paquete de productos
            var productos = [];
            $('#tablaProductosFacturaAlmacen tbody tr').each(function() {
                var id_prod = $(this).attr('idProducto');
                var descuentoVal = $('#descuento-'+id_prod).val();
                //descuento: parseFloat($('#descuento-'+id_prod).val()).toFixed(4),
                productos.push({
                    id_prod: $(this).attr('idProducto'),
                    cantidad_facturada: $('#cantidadFacturada-'+id_prod).val(),
                    cantidad_rechazada: $('#rechazado-'+id_prod).val(),
                    cantidad_aceptada: $('#cantidadAceptada-'+id_prod).val(),
                    costo_unitario_bruto: $('#costoUnitarioBruto-'+id_prod).attr('valorReal'),
                    costo_subtotal_bruto: $('#costoSubTotalBruto-'+id_prod).attr('valorReal'),
                    descuento: isNaN(parseFloat(descuentoVal)) ? 0 : parseFloat(descuentoVal).toFixed(4),
                    costo_despues_rechazo: $('#costoDespuesRechazo-'+id_prod).attr('valorReal'),
                    caducidad: $('#caducidad-'+id_prod).val(),
                    id_cfp: $('#id_cfp-'+id_prod).val(),
                    id_prod_compra: $('#id_cfp-'+id_prod).attr('idProdCompra')
                });

            }); //Fin del each que recorre los productos de la factura
            
            // return; 
            //Hacemos el request al servicio para guardar la validacion de la factura
            $.ajax({
                url: 'services/mainService.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'actualizarFacturaAlmacen',
                    controller: "CompraFactura",
                    args: { 
                        'id_factura': $('#idFactura').val(),
                        'id_compra': $('#idCompra').val(),
                        'factura': factura,
                        'productos': productos
                    }
                },
                beforeSend: function() {
                    //Deshabilitamos el boton de guardar validacion
                    $('#btnGuardarValidacionFactura').attr('disabled', true);
                    //Deshabilitamos el boton de cancelar validacion
                    $('#btnCancelarValidacionFactura').attr('disabled', true);
                    //Ocultamos el boton de btnGuardarValidacionFactura
                    $('#btnGuardarValidacionFactura').hide();
                    //Mostramos el spinner en el boton de guardar validacion
                    $('#btnProcessConfirmClose').show();
                    
                },
                success: function(response) {
                    // console.log(response);
                    response = JSON.parse(response);
                    //Si recibimos un false en el status del response, mostramos un mensaje de error
                    if(response.status == false) {
                        alert(response.message);
                        //Habilitamos el boton de guardar validacion
                        $('#btnGuardarValidacionFactura').attr('disabled', false);
                        //Habilitamos el boton de cancelar validacion
                        $('#btnCancelarValidacionFactura').attr('disabled', false);
                        //Ocultamos el spinner en el boton de guardar validacion
                        $('#btnProcessConfirmClose').hide();
                        //Mostramos el boton de btnGuardarValidacionFactura
                        $('#btnGuardarValidacionFactura').show();
                        return;
                    }
                    //Si la respuesta es correcta, mostramos un mensaje de exito
                    alert(response.message);

                    //Limpiamos el contenido del div divTablaEntradas para pintar la vista de ingreso de facturas en la orden de compra seleccionada
                    $('#divTablaFacturas').html('');
                    ///Limpiamos el contenido del div divHojaValidacionFactura
                    $('#divHojaValidacionFactura tbody').html('');
                    //Eliminamos la tabla de totales de la factura
                    $('#divTotalesFactura').html('');
                    //Limipamos la tabla de productos y oculatamos el divHojaValidacionFactura
                    $('#tablaProductosFacturaAlmacen tbody').html('');
                    $('#divHojaValidacionFactura').hide();
                    //Pintamos la tabla de facturas
                    pintarTablaFacturas($('#idCompra').val());
                },
                error: function(error) {
                    // console.log(error);
                    //Si la respuesta es erronea, mostramos un mensaje de error
                    alert('Error al guardar la validación de la factura.');
                    //Habilitamos el boton de guardar validacion
                    $('#btnGuardarValidacionFactura').attr('disabled', false);
                    //Habilitamos el boton de cancelar validacion
                    $('#btnCancelarValidacionFactura').attr('disabled', false);
                    //Ocultamos el spinner en el boton de guardar validacion
                    $('#btnProcessConfirmClose').hide();
                    //Mostramos el boton de btnGuardarValidacionFactura
                    $('#btnGuardarValidacionFactura').show();
                }
            });






        });// Fin del evento para guardar la validacion de la factura


        feather.replace();
    }); // Fin document ready

    function pintarTablaFacturas(id_compra){
    //Vamos a pintar una tabla que muestra las facturas de una orden de compra y la vamos a llenar con las facturas de la orden de compra seleccionada
    var tabla = '<table id="tablaFacturas" class="table table-bordered" style="width:100%">';
        tabla += '<thead>';
        tabla += '<tr>';
        tabla += '<th>UUID</th>';
        tabla += '<th>Fecha Factura</th>';
        tabla += '<th>Fecha Llegada</th>';
        tabla += '<th>Unidades<br>Facturadas</th>';
        tabla += '<th>Unidades<br>Aceptadas</th>';
        tabla += '<th>Unidades<br>Rechazadas</th>';
        tabla += '<th>Validar</th>';
        tabla += '</tr>';
        tabla += '</thead>';
        tabla += '<tbody>';
        // console.log('LLamanos al servicio para obtener las facturas de la orden de compra seleccionada');
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
                // // console.log(response);
                // Parseamos la cadena JSON para convertirla en un array de objetos
                var data = JSON.parse(response);
                //Agregamos una banderita que determine si hay facturas pendientes de ingresar a almacen
                var hayFacturasPendientes = false;

                //Si tiene datos la respuesta, entonces pintamos una tabla nueva con los datos en div divTablaFacturas
                if(response.length > 0) {
                    hayFacturasPendientes = false;
                    $.each(data, function(index, factura) {
                        if(factura.validada == 0){
                            hayFacturasPendientes = true;
                        }
                        tabla += '<tr>';
                        tabla += '<td>'+factura.uuid+'</td>';
                        tabla += '<td>'+factura.fecha+'</td>';
                        tabla += '<td>'+factura.fecha_llegada+'</td>';
                        tabla += '<td class="dt-right">'+parseFloat(factura.tFacturado).toFixed(2)+'</td>';
                        tabla += '<td class="dt-right">'+parseFloat(factura.tAceptado).toFixed(2)+'</td>';
                        tabla += '<td class="dt-right">'+parseFloat(factura.tRechazado).toFixed(2)+'</td>';
                        tabla += '<td>'+
                                //'<button class="btn btn-info btn-sm ml-2 mt-2 btnEditarFactura" id="'+factura.id+'"><i style=" color: #f6fcfb;" data-feather="edit"></i> Editar</button>'+
                        (factura.validada == 0 ?
                            '<button class="btn btn-primary btn-sm ml-2 mt-2 btnValidarFact" id="'+factura.id+'"><i style=" color: #f6fcfb;" data-feather="edit"></i> Validar Factura</button>' :
                            '<button class="btn btn-success btn-sm ml-lg-4 mt-2" disabled><i style=" color: #f6fcfb;" data-feather="check-square"></i>&nbsp; Validada</button>')
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

                    //Si no hay facturas pendientes de ingresar a almacen, deshabilitamos el boton de btnFinalizarValidacion
                    console.log('Hay facturas pendientes: '+hayFacturasPendientes);
                    if(hayFacturasPendientes) {
                        $('#btnFinalizarValidacion').attr('disabled', true);
                    }else{
                        $('#btnFinalizarValidacion').attr('disabled', false);
                    }

                    //Agregamos un titulo a la tabla utlizando la parte izquierda del cuadro de busqueda
                    // $('#tablaFacturas_filter').prepend('<h4 style="text-align: left;">Facturas de la Orden de Compra</h4>');
                    // // $('#tablaFacturas_filter label').contents().filter(function() {
                    // //     return this.nodeType == 3;
                    // // }).replaceWith('Buscar en calientes:');

                    //Actualizamos los iconos de feather
                    feather.replace();
                    //En caso de estar oculto el boton de btnFinalizarValidacion, lo mostramos
                    $('#btnFinalizarValidacion').show();

                    
                } else {
                    $('#divTablaFacturas').html('<div class="alert alert-warning">No hay facturas en esta orden de compra</div>');
                }
            },
            error: function(error) {
                // console.log(error);
            }
        });
    }

    function verificaInputValue(id){
        var valor = $('#'+id).val();
        if (valor === '' || isNaN(parseFloat(valor))) {
            // console.log('El valor no es un número, se asigna 0.00');
            $('#'+id).val('0.00');
        }
    }

    //Calcula unidades facturadas
    function calculaUnidadesFacturadas(id){
        //Obtenemos el valor de la cantidad aceptada
        var cantidadAceptada = parseFloat($('#cantidadAceptada-'+id).val());
        //Obtenemos el valor de la cantidad rechazada
        var rechazado = parseFloat($('#rechazado-'+id).val());
        //Calculamos las unidades facturadas
        var facturado = cantidadAceptada + rechazado;
        //Actualizamos el input de unidades facturadas
        $('#cantidadFacturada-'+id).val(facturado.toFixed(2));
        //Al alterar las cantidades facturadas, actualizamos el costo subtotal bruto llamando a sus respectivas funciones
        calculaCostoUnitarioBruto(id);
        calculaCostoSubtotalBruto(id);
        calculaCostoDespuesRechazo(id);
        calculaDescuento(id);
    }

    function calculaCostoUnitarioBruto(id){
        //Obtenemos el valor de la cantidad facturada
        var cantidadFacturada = parseFloat($('#cantidadFacturada-'+id).val());
        //Obtenemos el valor del costo subtotal bruto
        var costoSubtotalBruto = parseFloat($('#costoSubTotalBruto-'+id).val());
        //Calculamos el costo unitario bruto
        var costoUnitario = costoSubtotalBruto / cantidadFacturada;
        //Actualizamos el atributo valorReal del input de costo unitario bruto con todos sus decimales
        $('#costoUnitarioBruto-'+id).attr('valorReal', costoUnitario);
        //Actualizamos el input de costo unitario bruto
        $('#costoUnitarioBruto-'+id).val(costoUnitario.toFixed(2));
        //Al alterar el costo unitario bruto, actualizamos el costo subtotal bruto llamando a sus respectivas funciones
        calculaCostoSubtotalBruto(id);
        calculaCostoDespuesRechazo(id);
        calculaDescuento(id);
    }

    function calculaCostoSubtotalBruto(id){
        //Obtenemos el valor de la cantidad facturada
        var cantidadFacturada = parseFloat($('#cantidadFacturada-'+id).val());
        //Obtenemos el valor del costo unitario bruto
        var costoUnitario = parseFloat($('#costoUnitarioBruto-'+id).val());
        //Calculamos el costo subtotal bruto
        var costoSubtotalBruto = cantidadFacturada * costoUnitario;
        //Actualizamos el atributo valorReal del input de costo subtotal bruto con todos sus decimales
        $('#costoSubTotalBruto-'+id).attr('valorReal', costoSubtotalBruto);
        //Actualizamos el input de costo subtotal bruto
        $('#costoSubTotalBruto-'+id).val(costoSubtotalBruto.toFixed(2));
        //Al alterar el costo subtotal bruto, actualizamos el costo despues de rechazo llamando a sus respectivas funciones
        calculaCostoDespuesRechazo(id);
        calculaDescuento(id);
    }

    function calculaUnidadesRechazadas(id){
        //Obtenemos el valor de la cantidad facturada
        var cantidadFacturada = parseFloat($('#cantidadFacturada-'+id).val());
        //Obtenemos el valor de la cantidad aceptada
        var cantidadAceptada = parseFloat($('#cantidadAceptada-'+id).val());
        //Calculamos las unidades rechazadas
        var rechazado = cantidadFacturada - cantidadAceptada;
        //Actualizamos el input de unidades rechazadas
        $('#rechazado-'+id).val(rechazado.toFixed(2));
        //Al alterar las unidades rechazadas, actualizamos el costo despues de rechazo llamando a sus respectivas funciones
        calculaCostoDespuesRechazo(id);
        calculaDescuento(id);
    }

    function calculaCostoDespuesRechazo(id){
        //Obtenemos el valor de la cantidad facturada
        var cantidadFacturada = parseFloat($('#cantidadFacturada-'+id).val());
        //Obtenemos el valor de las unidades rechazadas
        var rechazado = parseFloat($('#rechazado-'+id).val());
        //Obtenemos el valor del costo unitario bruto
        var costoUnitario = parseFloat($('#costoUnitarioBruto-'+id).attr('valorReal'));
        //Calculamos el costo despues de rechazo
        var costoDespuesRechazo = costoUnitario * (cantidadFacturada - rechazado);
        //Actualizamos el atributo valorReal del input de costo despues de rechazo con todos sus decimales
        $('#costoDespuesRechazo-'+id).attr('valorReal', costoDespuesRechazo);
        //Actualizamos el input de costo despues de rechazo
        $('#costoDespuesRechazo-'+id).val(costoDespuesRechazo.toFixed(2));
        //Al alterar el costo despues de rechazo, actualizamos el descuento llamando a sus respectivas funciones
        calculaDescuento(id);
    }

    function calculaDescuento(id){
        //Obtnemos el costo subtotal bruto
        var costoSubtotalBruto = parseFloat($('#costoSubTotalBruto-'+id).attr('valorReal'));
        //Obtenemos el costo despues de rechazo
        var costoDespuesRechazo = parseFloat($('#costoDespuesRechazo-'+id).attr('valorReal'));
        //Calculamos el descuento
        var descuento = costoSubtotalBruto - costoDespuesRechazo;
        //Actualizamos el atributo valorReal del input de descuento con todos sus decimales
        $('#descuento-'+id).attr('valorReal', descuento);
        //Actualizamos el input de descuento
        $('#descuento-'+id).val(descuento.toFixed(2));
    }

    //Calcula unidades aceptadas
    function calculaUnidadesAceptadas(id){
        //Obtenemos el valor de la cantidad facturada
        var cantidadFacturada = parseFloat($('#cantidadFacturada-'+id).val());
        //Obtenemos el valor de la cantidad rechazada
        var rechazado = parseFloat($('#rechazado-'+id).val());
        //Calculamos las unidades aceptadas
        var aceptado = cantidadFacturada - rechazado;
        //Actualizamos el input de unidades aceptadas
        $('#cantidadAceptada-'+id).val(aceptado.toFixed(2));
    }

    // function calculaPrecioLista(id){
    //     //Obtenemos el valor del costo unitario bruto
    //     var costoUnitario = parseFloat($('#costoUnitarioBruto-'+id).val());
    //     //Obtnemos el valor de IVA a traves del atributo porcentaje del input
    //     var iva = parseFloat($('#iva-'+id).attr('porcentaje'));
    //     //Obtnemos el valor de ieps a traves del atributo porcentaje del input
    //     var ieps = parseFloat($('#ieps-'+id).attr('porcentaje'));
    //     //Calculamos el precio de lista a traves de una formula : costoUnitario / (1+Tasa de IEPS)⋅(1+Tasa de IVA)
    //     var precioLista = costoUnitario / ((1+ieps) * (1+iva));
    //     //Actualizamos el input de precio de lista
    //     $('#costoUnitarioBruto-'+id).val(precioLista.toFixed(2));
    // }

    function calculaIVA(id){
        //Obtenemos el valor del precio de lista
        var precioLista = parseFloat($('#costoUnitarioBruto-'+id).val());
        //Sumamos el precio de lista el IEPS de esa fila
        var ieps = parseFloat($('#ieps-'+id).val());    
        //Obtnemos el valor de IVA a traves del atributo porcentaje del input
        var ivaPorcentaje = parseFloat($('#iva-'+id).attr('porcentaje'));
        //Calculamos el iva

        var iva = ((precioLista + ieps) * (1+ivaPorcentaje)) - (precioLista + ieps);
        
        //Actualizamos el input de iva
        $('#iva-'+id).val(iva);
    }

    function calculaIEPS(id){
        var precioLista = parseFloat($('#costoUnitarioBruto-'+id).attr('valorReal'));
        //Calculamos el nuevo ieps a traves conciderando los nuevos cambios
        var iepsXLitro = parseFloat($('#ieps-'+id).attr('iepsxl')) * parseFloat($('#cantidadAceptada-'+id).val());
        var iepsPorcentaje = parseFloat($('#ieps-'+id).attr('porcentaje'));
        var ieps = (precioLista * (1+iepsPorcentaje) - precioLista) + iepsXLitro;

        $('#ieps-'+id).val(ieps);
    }

    function calculaTotalesFactura(){
        //Sumamos cada clase
        var subtotalBruto = 0;
        var descuentoTotal = 0;
        var subtotalNeto = 0;
        var totalIVA = 0;
        var totalIEPS = 0;
        var totalTotal = 0;

        $('.costoSubTotalBruto').each(function(){
            subtotalBruto += parseFloat($(this).attr('valorReal'));
        });

        $('.descuento').each(function(){
            descuentoTotal += parseFloat($(this).attr('valorReal'));
        });

        $('.costoDespuesRechazo').each(function(){
            subtotalNeto += parseFloat($(this).attr('valorReal'));
        });

        //Caculamos IEPS recorriendo cada input de esta clase
        $('.iepsFila').each(function(){
            id = $(this).attr('id').split('-')[1];
            totalIEPS += parseFloat($(this).val())*parseFloat($('#cantidadAceptada-'+id).val());
            // console.log('IEPS de: '+id+" "+totalIEPS);
        });
        

        $('.ivaFila').each(function(){
            id = $(this).attr('id').split('-')[1];
            totalIVA += parseFloat($(this).val())*parseFloat($('#cantidadAceptada-'+id).val());
            // console.log('IVA de: '+id+" "+totalIVA);
        });

        totalTotal = subtotalNeto + totalIVA + totalIEPS;
        //Actualizamos los valores de los totales
        const formatter = new Intl.NumberFormat('es-MX', {
            style: 'currency',
            currency: 'MXN',
        });
        
        $('#subtotalBrutoFactura').text(formatter.format(subtotalBruto));
        $('#descuentoTotalFactura').text(formatter.format(descuentoTotal));
        $('#subtotalNetoFactura').text(formatter.format(subtotalNeto));
        $('#ivaFactura').text(formatter.format(totalIVA));
        $('#iepsFactura').text(formatter.format(totalIEPS));
        $('#totalFactura').text(formatter.format(totalTotal));
        //Actualizamos los valores reales de los labels
        $('#subtotalBrutoFactura').attr('valorReal', subtotalBruto);
        $('#descuentoTotalFactura').attr('valorReal', descuentoTotal);
        $('#subtotalNetoFactura').attr('valorReal', subtotalNeto);
        $('#ivaFactura').attr('valorReal', totalIVA);
        $('#iepsFactura').attr('valorReal', totalIEPS);
        $('#totalFactura').attr('valorReal', totalTotal);
    }


    function calculaTotalesFacturaImpresos(){
        //console.log('Calculando totales de la factura impresa');
        //Sumamos cada clase
        var subtotalBruto = 0;
        var descuentoTotal = 0;
        var subtotalNeto = 0;
        var totalIVA = 0;
        var totalIEPS = 0;
        var totalTotal = 0;

        $('.costoSubTotalBruto').each(function(){
            subtotalBruto += parseFloat($(this).attr('valorReal'));
        });

        // $('.descuento').each(function(){
        //     descuentoTotal += parseFloat($(this).attr('valorReal'));
        // });

        // $('.costoDespuesRechazo').each(function(){
        //     subtotalNeto += parseFloat($(this).attr('valorReal'));
        // });

        //Caculamos IEPS recorriendo cada input de esta clase
        $('.iepsFila').each(function(){
            id = $(this).attr('id').split('-')[1];
            totalIEPS += parseFloat($(this).val())*parseFloat($('#cantidadFacturada-'+id).val());
            // console.log('IEPS de: '+id+" "+totalIEPS);
        });
        

        $('.ivaFila').each(function(){
            id = $(this).attr('id').split('-')[1];
            totalIVA += parseFloat($(this).val())*parseFloat($('#cantidadFacturada-'+id).val());
            // console.log('IVA de: '+id+" "+totalIVA);
        });

        totalTotal = subtotalBruto + totalIVA + totalIEPS;
        //Actualizamos los valores de los totales
        const formatter = new Intl.NumberFormat('es-MX', {
            style: 'currency',
            currency: 'MXN',
        });
        
        $('#subtotalBrutoFactura2').text(formatter.format(subtotalBruto));
        $('#subtotalBrutoFactura2').attr('valorReal', subtotalBruto);
        $('#descuentoTotalFactura2').text(formatter.format(0));
        $('#descuentoTotalFactura2').attr('valorReal', 0);
        $('#subtotalNetoFactura2').text(formatter.format(subtotalBruto));
        $('#subtotalNetoFactura2').attr('valorReal', subtotalBruto);
        $('#ivaFactura2').text(formatter.format(totalIVA));
        $('#ivaFactura2').attr('valorReal', totalIVA);
        $('#iepsFactura2').text(formatter.format(totalIEPS));
        $('#iepsFactura2').attr('valorReal', totalIEPS);
        $('#totalFactura2').text(formatter.format(totalTotal));
        $('#totalFactura2').attr('valorReal', totalTotal);
    }


    //Creamos una funcion para pintar las ordenes de compra
    function pintaOrdenesCompra(id_prov, id_bodega){
        $.ajax({
            url: 'services/mainService.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'getComprasPendientesPorValidar',
                controller: "Compra",
                args: { 
                    'id_prov': id_prov,
                    'id_bodega': id_bodega
                }                        
            },
            success: function(response) {
                // // console.log(response);
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
                    tabla += '<th>Estado</th>';
                    tabla += '</tr>';
                    tabla += '</thead>';
                    tabla += '<tbody>';
                    
                    $.each(data.ordenesCompra, function(index, compra) {
                        tabla += '<tr>';
                        tabla += '<td>'+compra.id+'</td>';
                        tabla += '<td>'+compra.nombre_proveedor+'</td>';
                        tabla += '<td>'+compra.nombre_bodega+'</td>';
                        tabla += '<td>'+compra.total_esperado+'</td>';
                        tabla += '<td>'+compra.total+'</td>';
                        tabla += '<td>'+compra.alta+'</td>';
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
                    $('#tablaEntradas').DataTable( {
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
                    $('#tituloTabla').html('Ordenes de Compra pendientes por validar para <u>'+data.razonSocial+'</u>');

                } else {
                    //Actualizamos el titulo de la tabla en funcion al texto del select de proveedores
                    $('#tituloTabla').html('Ordenes de Compra pendientes por validar para <u>'+$('#proveedor option:selected').text()+'</u>');
                    $('#divTablaEntradas').html('<div class="alert alert-warning">No hay ordenes de compra pendientes para este proveedor en esta bodega</div>');
                }
            },
            error: function(error) {
                // console.log(error);
                alert('Error al cargar las ordenes de compra pendientes por validar.');
            }
        });
    } // Fin de la funcion pintaOrdenesCompra
</script>