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
require_once 'models/Compra.php';
// require 'models/CompraFactura.php';
// require 'models/ProdCompra.php';
// require 'models/GmcvDescuento.php';
// require 'models/GmcvDescuentoBodega.php';
// require 'models/GmcvDescuentoProducto.php';

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

</style>
<!-- Estilos para el driver.js -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.css"/>


<div class="page-header layout-top-spacing title-header">
    <div class="pge-title" style="margin-left: 3.5%;">
        <h3>&nbsp; Precio Lista (GSV) y Descuentos</h3>
    </div>
</div>
<?php

$compra = new Compra();
$proveedores = $compra->getProveedores();
?>

<div class="card card-principal">
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <label for="miSelect">Selecciona un proveedor:</label>
                <select id="selectProveedor">
                    <option value="0">Seleccione un proveedor</option>
                    <?php
                foreach ($proveedores as $proveedor) {
                    echo "<option value='".$proveedor['id']."'>".$proveedor['corto']."</option>";
                }
                ?>
                </select>
            </div>

            <div class="col-md-6">
                <label for="miSelect">Selecciona bodega(s):</label>
                <select id="selectBodega"></select>
            </div>

            <div class="col-md-2">
                <label for="fecha">Fecha:</label>
                <input type="date" id="verFecha" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                <small id="emailHelp" class="form-text text-muted">Fecha inicial de la vigencia de precios de lista.</small>
            </div>

        </div>
        
        <div class="row">
            <div class="col-md-2">
                <button id="btnBuscarProductos" class="btn btn-primary btn-lg mt-lg-4"><i class="feather-16" data-feather="list"></i> Consultar / Listar</button>
            </div>
            <div class="col-md-10">
                <div class="row justify-content-center">
                    <div id="divMessage" class="col-md-8"></div>
                </div>
            </div>
        </div>

        <!-- <div class="row">
            <div class="col-md-12">
                <div class="page-header layout-top-spacing title-header mt-lg-4">
                    <div class="pge-title">
                        <h5>Productos</h5>
                    </div>
                </div>
            </div>
        </div> -->

        <br>
        <br>
        <div class="row align-items-end mb-lg-4">
            <div class="col-md-12 d-flex justify-content-end">
                <!-- <button id="mostrarFechas" class="btn btn-info ml-1"><i class="feather-16" data-feather="search"></i> Mostrar productos</button> -->
                <button id="btnDefinirGSV" disabled="true" class="btn btn-primary ml-1"><i class="feather-16" data-feather="edit"></i> Definir GSV</button>
                <button id="btnAddDescuento" disabled="true" class="btn btn-primary ml-4"><i class="feather-16" data-feather="plus"></i> Agregar/Reactivar descuento</button>
                <!-- <button id="btnExportarExcel" disabled="true" class="btn btn-primary ml-1"><i class="feather-16" data-feather="file-text"></i> Exportar a Excel</button> -->
            </div>
        </div>

        <!-- <div class="row mt-lg-4">
            <div class="col-md-7">
                <label for="prov">Proveedor:</label> -->
                <label hidden class="form-control form-control-sm" id="nombreProveedor"></label>
            <!-- </div> -->

            <!-- <div class="col-md-3">
                <label for="bod">Bodega:</label> -->
                <label hidden class="form-control form-control-sm" id="nombreBodega"></label>
            <!-- </div> -->

            <!-- <div class="col-md-2">
                <label for="fecha">Fecha:</label>
                <input type="date" id="verFecha" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
            </div> -->
        </div>





        <div class="row">
            <div class="col-md-12">
                <div class="page-header layout-top-spacing title-header mt-lg-4">
                    <div class="pge-title">
                        <h5 id="tituloTabla"></h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12" id="divTablaProductos">

            </div>
        </div>

        <!-- <div class="row">
            <div  class="col-md-12 col-lg-12">
            </div>
        </div> -->


    </div> <!-- fin card-body -->
</div> <!-- fin card-principal -->


<!-- Modal para modificar un descuento existente -->
<div class="modal fade" id="modalEditarDescuento" tabindex="-1" role="dialog" aria-labelledby="modalEditarDescuentoLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarDescuentoLabel">Modificar descuento</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <input type="hidden" id="idDescuento" value="" name="idDescuento">
            <div class="modal-body">
                <div class="row mt-3">
                    <div class="col-md-12">
                        <label for="nombreDescuento">Nombre del descuento:</label>
                        <input type="text" id="nombreDescuento" class="form-control form-control-sm">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-5">
                        <label for="estado">Estado:</label>
                        <select name="estado" id="estado" class="form-control form-control-sm">
                            <option value="1">Vigente</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                    <div class="col-md-7">
                        <label for="posteriorCP">Descuento posterior a Costo Pactado:</label>
                        <select name="posteriorCP" id="posteriorCP" class="form-control form-control-sm">
                            <option value="1">Sí</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <label for="bodegasDescuento">Bodegas:</label>
                        <select name="bodegasDescuento" id="bodegasDescuento" class="form-control form-control-sm" multiple></select>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <!-- <button id="btnGuardarDescuento" type="button" class="btn btn-primary">Guardar</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button> -->

                <div class="w-100 d-flex justify-content-between">
                    <button id="btnGuardarDescuento" type="button" class="btn btn-primary">Guardar</button>
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Cancelar</button>
                </div>
                
            </div>
        </div>
    </div>
</div>
            


<!-- Modal para agregar o reactivar un descuento -->
<div class="modal fade" id="modalAgregarDescuento" tabindex="-1" role="dialog" aria-labelledby="modalAgregarDescuentoLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <input type="hidden" id="idProveedor" value="" name="idProveedor">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAgregarDescuentoLabel">Agregar/Reactivar descuento</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12 text-center mb-lg-4">
                        <h5>Activar descuento</h5>
                    </div> 
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <label for="descuentosInactivos">Descuentos inactivos:</label>
                        <select name="descuentosInactivos" id="descuentosInactivos" class="form-control form-control-sm"></select>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-12 text-center mb-lg-4">
                        <h5>Agregar descuento</h5>
                    </div> 
                </div>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <label for="nombreDescuentoAdd">Nombre del descuento:</label>
                        <input type="text" id="nombreDescuentoAdd" class="form-control form-control-sm">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <label for="estado">Estado:</label>
                        <select name="estadoAdd" id="estadoAdd" class="form-control form-control-sm">
                            <option value="1">Vigente</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="posteriorCP">Posterior a CP:</label>
                        <select name="posteriorCPAdd" id="posteriorCPAdd" class="form-control form-control-sm">
                            <option value="1">Sí</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <label for="bodegasDescuentoAdd">Bodegas:</label>
                        <select name="bodegasDescuentoAdd" id="bodegasDescuentoAdd" class="form-control form-control-sm" multiple></select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <!-- <button id="btnGuardarDescuentoNuevo" type="button" class="btn btn-primary">Guardar</button>
                <button type="button" class="btn btn-danger" data-dismiss="modal">Cancelar</button> -->
                <div class="w-100 d-flex justify-content-between">
                    <button id="btnGuardarDescuentoNuevo" type="button" class="btn btn-primary">Guardar</button>
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>
</div>



<!-- Script para el select2 -->
<script src="../../../sys/bocampana_vista/plugins/select2/select2.min.js"></script>
<script src="../../../sys/bocampana_vista/plugins/select2/es.js"></script>
<!-- Script para el driver.js -->
<script src="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.js.iife.js"></script>


<!-- Script para el driver.js -->
<script src="./js/functions.js"></script>
<script>

// Definimos el driver.js para el tutorial
const driver = window.driver.js.driver;
const driverObj = driver();

//Document ready
$(document).ready(function() {
    $('#selectBodega').select2({
        language: 'es',
        multiple: true,
        closeOnSelect: false,
        search: true,
        placeholder: "Seleccione una bodega"
    });

    //Inicializamos el select de proveedores
    $('#selectProveedor').select2({
        language: 'es',
        multiple: false,
        closeOnSelect: true,
        search: true,
        placeholder: "Seleccione un proveedor" // Placeholder
    });

    $('#selectBodega').on('select2:select', function(e) {
        var data = e.params.data;
        if (data.id === 'select_all') {
            var allOptions = $('#selectBodega').find('option').not('[value="select_all"]');
            allOptions.prop('selected', true);
            //Quitamos la selección de la opción "Seleccionar todas"
            $('#selectBodega').find('option[value="select_all"]').prop('selected', false);
            $('#selectBodega').trigger('change');

            // Desactiva la opción "Seleccionar todas"
            $('#selectBodega').find('option[value="select_all"]').prop('disabled', true);
            $('#selectBodega').trigger('change.select2');
        }
    });

    $('#selectBodega').on('select2:unselect', function(e) {
        var data = e.params.data;
        if (data.id === 'select_all') {
            $('#selectBodega').find('option').prop('selected', false);
            $('#selectBodega').trigger('change');
        }

        // Reactiva la opción "Seleccionar todas" si alguna opción es deseleccionada
        if ($('#selectBodega').val().length === 0) {
            $('#selectBodega').find('option[value="select_all"]').prop('disabled', false);
            $('#selectBodega').trigger('change.select2');
        }
    });


    //Creamos un evento cuando se cambie el proveedor seleccionado
    $(document).on('change', '#selectProveedor', function() {
        //Desactivamos los botonoes de definir GSV y agregar descuento y exportar a excel
        $('#btnDefinirGSV').prop('disabled', true);
        $('#btnAddDescuento').prop('disabled', true);
        $('#btnExportarExcel').prop('disabled', true);

        //Obtnemos el id del proveedor seleccionado
        var idProveedor = $(this).val();

        //Llamamos al service para obtener las bodegas del proveedor
        $.ajax({
            url: 'services/mainService.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'getBodegasByIdProveedor',
                controller: "Compra",
                args: {
                    'idProveedor': idProveedor
                }
            },
            success: function(response) {
                console.log(response);
                // response = JSON.parse(response);
                //Quitamos las opciones actuales del select bodegas
                $('#selectBodega').empty();
                $('#selectBodega').append(
                    '<option value="select_all">Seleccionar todas</option>');
                //Recorremos el array de bodegas y las agregamos al select
                $.each(response, function(index, bodega) {
                    $('#selectBodega').append('<option value="' + bodega.id + '">' +
                        bodega.nombre + '</option>');
                });
            },
            error: function(error) {
                alert('Error al obtener las bodegas del proveedor');
            }
        });
    });


    //Creamos un evento cuando se haga click en el botón de buscar productos
    $(document).on('click', '#btnBuscarProductos', function() {
        //Obtenemos el id del proveedor seleccionado
        var idProveedor = $('#selectProveedor').val();
        //Obtenemos las bodegas seleccionadas
        var bodegas = $('#selectBodega').val();
        //Validamos que se haya seleccionado un proveedor
        if (idProveedor == 0) {
            alert('Seleccione un proveedor');
            return;
        }
        //Validamos que se haya seleccionado al menos una bodega
        if (bodegas == null || bodegas.length == 0) {
            alert('Seleccione al menos una bodega');
            return;
        }

        //Parseamos las bodegas a un string separado por comas
        bodegas = bodegas.join(',');

        console.log(
            'bodegas: ' + bodegas,
            'idProveedor: ' + idProveedor
        );
        //Pintramos la tabla con los productos del proveedor
        pintaTablaProductosGSV(idProveedor, bodegas);


    });

    //Si detectamos un cambio en el select de  selectProveedor, limpiamos la tabla de productos
    $(document).on('change', '#selectProveedor', function() {
        $('#divTablaProductos').empty();
        //Limpiamos los nombres de proveedor y bodega
        $('#nombreProveedor').text('');
        $('#nombreBodega').text('');
        //Limpiamos el select de bodegas
        $('#selectBodega').empty();

        //Borramos el sessionStorage
        sessionStorage.removeItem('productos');
        sessionStorage.removeItem('descuentosAntesCP');
        sessionStorage.removeItem('descuentosDespCP');
    });

    //Creamos un evento para cuando se pulse el boton btnDefinirGSV
    $(document).on('click', '#btnDefinirGSV', function() {
        //Vamos a pintar una tabla igual a la de productos, pero con los campos de GSV editables para eso recojeremos los datos del sessionStorage
        
        idProveedor = $('#selectProveedor').val();
        bodegas = $('#selectBodega').val();
        //Validamos que se haya seleccionado un proveedor
        if (idProveedor == 0) {
            alert('Seleccione un proveedor');
            return;
        }
        //Validamos que se haya seleccionado al menos una bodega
        if (bodegas == null || bodegas.length == 0) {
            alert('Seleccione al menos una bodega');
            return;
        }

        //Hacemos un join de las bodegas seleccionadas
        bodegas = bodegas.join(',');

        console.log("Estos son los datos: "+ 
        "Proveedor: " + idProveedor, 
        "Bodegas: "+ bodegas
        )
        //Bloquemos la fecha para que no se pueda cambiar
        $('#verFecha').prop('disabled', true);
        pintaTablaProductosGSVEditable(idProveedor, bodegas);

    });// Fin del evento click en btnDefinirGSV


    //Creamos un evento para cuando se modifique la clase del input: inputPrecioLista
    $(document).on('change', '.inputPrecioLista', function() {
        //Obtenemos el valor del input
        var valor = $(this).val();
        //Obtenemos el id del producto
        var id = $(this).attr('id').split('-')[1];
        //Obtenemos el valor real del producto
        var valorReal = $(this).attr('valorReal');
        //Validamos que el valor sea diferente al valor real
        if(valor != valorReal){
            //Cambiamos el color del input
            $(this).css('background-color', 'yellow');
        }else{
            $(this).css('background-color', 'white');
        }
    });
    
    //Creamos un evento para cuando se modifique la clase del input: inputDescuentoAntesCP
    $(document).on('change', '.inputDescuentoAntesCP', function() {
        //Obtenemos el valor del input
        var valor = $(this).val();
        //Obtenemos el id del producto
        var id = $(this).attr('id').split('-')[1];
        //Obtenemos el valor real del producto
        var valorReal = $(this).attr('valorReal');
        //Validamos que el valor sea diferente al valor real
        if(valor != valorReal){
            //Cambiamos el color del input
            $(this).css('background-color', 'yellow');
        }else{
            $(this).css('background-color', 'white');
        }
    });

    //Creamos un evento para cuando se modifique la clase del input: inputDescuentoDespCP
    $(document).on('change', '.inputDescuentoDespCP', function() {
        //Obtenemos el valor del input
        var valor = $(this).val();
        //Obtenemos el id del producto
        var id = $(this).attr('id').split('-')[1];
        //Obtenemos el valor real del producto
        var valorReal = $(this).attr('valorReal');
        //Validamos que el valor sea diferente al valor real
        if(valor != valorReal){
            //Cambiamos el color del input
            $(this).css('background-color', 'yellow');
        }else{
            $(this).css('background-color', 'white');
        }
    });


    //Creamos un evento para cuando se pulse el boton btnGuardarGSV, Este va a recoge0r los datos de la tabla y los va a enviar al service
    $(document).on('click', '#btnGuardarGSV', function() {
        //Antes de todo vamos a pedir confirmación al usuario
        if(!confirm('¿Está seguro de guardar los datos \n para la fecha: '+ $('#verFecha').val()+' ?')){
            return;
        }
        let descuentosAntesCP = sessionStorage.getItem('descuentosAntesCP');
        let descuentosDespCP = sessionStorage.getItem('descuentosDespCP');

        // Comanzamos a recoger los datos de la tabla de productos, vamos producto por producto
        var productoBase = {
            'id': 0,
            'precioLista': 0,
            'descuentos': []
        };
        let productos = [];

        //Recorremos la tabla de productos
        $('#tablaProductosFacturaAlmacen tbody tr').each(function(index, tr) {
            //Obtenemos el id del producto
            productoBase.id = $(tr).attr('id').split('-')[1];
            //Obtenemos el precio de lista
            productoBase.precioLista = $(tr).find('.inputPrecioLista').val();
            //Recorremos los descuentos antes de costo pactado
            $(tr).find('.inputDescuentoAntesCP').each(function(index, input) {
                var idDescuento = $(input).attr('idDescuento');
                var valor = $(input).val();
                productoBase.descuentos.push({
                    'id': idDescuento,
                    'tasa': valor
                });
            });

            //Recorremos los descuentos después de costo pactado
            $(tr).find('.inputDescuentoDespCP').each(function(index, input) {
                var idDescuento = $(input).attr('idDescuento');
                var valor = $(input).val();
                productoBase.descuentos.push({
                    'id': idDescuento,
                    'tasa': valor
                });
            });

            //Agregamos el producto al array de productos
            productos.push(productoBase);
            //Limpiamos el producto base
            productoBase = {
                'id': 0,
                'precioLista': 0,
                'descuentos': []
            };

        });
        //Informacion complementaria
        var bodegas = $('#selectBodega').val(),
        bodegas = bodegas.join(',');

        //Estos son los datos que vamos a enviar al service
        console.log(productos);
        console.log(bodegas);
        console.log($('#verFecha').val());
        //Ya tenemos el paquete de productos, ahora lo enviamos al service. Llamamos al service para guardar los datos
        $.ajax({
            url: 'services/mainService.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'guardarGSV',
                controller: "Compra",
                args: {
                    'productos': productos,
                    'id_prov': $('#selectProveedor').val(),
                    'bodegas': bodegas,
                    'fecha': $('#verFecha').val()
                }
            },
            success: function(response) {
                console.log(response);
                response = JSON.parse(response);
                // alert(response.message);
                messageAlert(response.message, 'success',false);
                //Recargamos la tabla de productos
                pintaTablaProductosGSV($('#selectProveedor').val(),bodegas);
            },
            error: function(error) {
                alert('Error al guardar los datos');
            }
        });
    }); // Fin de evento btnGuardar GSV


    //Creamos un evento para cuando sea modifique el input de precioLista
    $(document).on('change', '.inputPrecioLista', function(){
        //Obtenemos el id del producto
        var id = $(this).attr('id').split('-')[1];
        //Calculamos el costo pactado
        calculaCostoPactado(id);
        //Calculamos el costo de ingreso neto
        calculaCostoIngresoNeto(id);
    });

    //Creamos un evento para cuando se modifique un input de descuento antes de costo pactado
    $(document).on('change', '.inputDescuentoAntesCP', function(){
        //Obtenemos el id del producto
        var id = $(this).attr('id').split('-')[1];
        //Calculamos el costo pactado
        calculaCostoPactado(id);
        //Calculamos el costo de ingreso neto
        calculaCostoIngresoNeto(id);
    });

    //Creamos un evento para cuando se modifique un input de descuento después de costo pactado
    $(document).on('change', '.inputDescuentoDespCP', function(){
        //Obtenemos el id del producto
        var id = $(this).attr('id').split('-')[1];
        //Actualizamos el atributo valorReal del input
        $('#'+$(this).attr('id')).attr('valorReal', $(this).val());
        //Calculamos el costo pactado
        calculaCostoPactado(id);
        //Calculamos el costo de ingreso neto
        calculaCostoIngresoNeto(id);
    });

    //Creamos un evento para cuando se pulse el botón de agregar descuento a traves de la clase descuentoEditable
    $(document).on('click', '.descuentoEditable', function(){
        //Obtenemos el id del descuento
        var idDescuento = $(this).attr('descuento');
        //Obtenemos el id del producto
        console.log("ID del descuento: ", idDescuento);

        //Llamaos al service para obtener los datos del descuento
        $.ajax({
            url: 'services/mainService.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'getDescuentoParaEdicion',
                controller: "GmcvDescuento",
                args: {
                    'id_descuento': idDescuento,
                    'id_prov': $('#selectProveedor').val()
                }
            },
            success: function(response) {
                console.log(response);
                //Pintamos los datos del descuento en el modal
                let descuento = response.descuento;
                let bodegasDescuento = response.bodegasDescuento;
                let bodegasProveedor = response.bodegasProveedor;
                $('#idDescuento').val(descuento.id);
                $('#nombreDescuento').val(descuento.nombre);
                descuento.id_status == 4 ? $('#estado').val(1) : $('#estado').val(0);
                descuento.posteriorCP == 1 ? $('#posteriorCP').val(1) : $('#posteriorCP').val(0);
                //Limpiamos el select de bodegas
                $('#bodegasDescuento').empty();
                //Iteramos las bodegas del proveedor y si la bodega está en las bodegas del descuento la marcamos
                $.each(bodegasProveedor, function(index, bodega){
                    selected = bodegasDescuento.find(b => b.id_bodega == bodega.id) ? 'selected' : '';
                    $('#bodegasDescuento').append('<option value="'+bodega.id+'" '+selected+'>'+bodega.nombre+'</option>');
                });

                //Mostramos el modal para hacer pruebas
                $('#modalEditarDescuento').modal('show');
            },
            error: function(error) {
                alert('Error al obtener los datos del descuento');
            }
        });

    });


    //Creammos un evento para cuando se guardan los cambios del descuento a traves del boton btnGuardarDescuento
    $(document).on('click', '#btnGuardarDescuento', function(){
        //Obtenemos los datos del descuento
        var idDescuento = $('#idDescuento').val();
        var nombre = $('#nombreDescuento').val();
        var estado = $('#estado').val();
        var posteriorCP = $('#posteriorCP').val();
        var bodegas = $('#bodegasDescuento').val();
        var id_prov = $('#selectProveedor').val();
        //Validamos que se haya seleccionado al menos una bodega
        if(bodegas == null || bodegas.length == 0){
            alert('Seleccione al menos una bodega');
            return;
        }
        //Parseamos las bodegas a un string separado por comas
        bodegas = bodegas.join(',');
        
        //Llamamos al service para guardar el descuento
        $.ajax({    
            url: 'services/mainService.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'updateDescuento',
                controller: "GmcvDescuento",
                args: {
                    'id_descuento': idDescuento,
                    'nombre': nombre,
                    'id_status': estado,
                    'posteriorCP': posteriorCP,
                    'bodegas': bodegas,
                    'id_prov': id_prov
                }
            },
            success: function(response) {
                console.log(response);
                //Mostramos el mensaje en un alert
                // alert(response.message);
                messageAlert(response.message, 'success',false);
                //Cerramos el modal
                $('#modalEditarDescuento').modal('hide');
                //Volvemos a pintar la tabla de productos para que se reflejen los cambios
                var bodegasProveedor = $('#selectBodega').val();
                bodegasProveedor = bodegasProveedor.join(',');
                pintaTablaProductosGSV($('#selectProveedor').val(), bodegasProveedor);
            },
            error: function(error) {
                alert('Error al guardar el descuento');
            }
        });
    });


    //Creamos un evento para el boton: btnAddDescuento que va a llamar el modal
    $(document).on('click', '#btnAddDescuento', function(){
        $('#nombreDescuentoAdd').prop('disabled', false);
        $('#estadoAdd').prop('disabled', false);
        $('#posteriorCPAdd').prop('disabled', false);
        $('#bodegasDescuentoAdd').prop('disabled', false);
        //Cambiamos el boton a guardar
        $('#btnGuardarDescuentoNuevo').text('Guardar');
        //Llamamos al service para obtener los descuentos inactivos
        $.ajax({
            url: 'services/mainService.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'getDescuentosInactivosByProveedor',
                controller: "GmcvDescuento",
                args: {
                    'id_prov': $('#selectProveedor').val()
                }
            },
            success: function(response) {
                console.log(response);
                var descuentosInactivos = response.descuentosInactivos;
                var bodegasProveedor = response.bodegasProveedor;
                //Limpiamos el select de descuentos inactivos
                $('#descuentosInactivos').empty();
                //Limpiamos el formulario de agregar descuento
                $('#nombreDescuentoAdd').val('');
                $('#estadoAdd').val(1);
                $('#posteriorCPAdd').val(1);
                //Limpiamos el select de bodegas
                $('#bodegasDescuentoAdd').empty();


                //Iteramos los descuentos inactivos y los agregamos al select
                if(descuentosInactivos.length > 0){
                    $('#descuentosInactivos').append('<option selected disabled value="">Seleccione descuento inactivo</option>');
                    $.each(descuentosInactivos, function(index, descuento){
                        $('#descuentosInactivos').append('<option value="'+descuento.id+'">'+descuento.nombre+'</option>');
                    });
                } else {
                    $('#descuentosInactivos').append('<option value="">No hay descuentos inactivos</option>');
                }
                //Limpiamos el input de bodegas del modal de agregar descuento
                $('#bodegasDescuentoAdd').empty();
                //Iteramos las bodegas del proveedor y las agregamos al select 
                $.each(bodegasProveedor, function(index, bodega){
                    $('#bodegasDescuentoAdd').append('<option value="'+bodega.id+'">'+bodega.nombre+'</option>');
                });

                //Mostramos el modal
                $('#modalAgregarDescuento').modal('show');
            },
            error: function(error) {
                alert('Error al obtener los descuentos inactivos');
            }
        });
    });


    //Creamos un evento para cuando se seleccione un descuento inactivo
    $(document).on('change', '#descuentosInactivos', function(){
        //Si se selecciona un descuento inactivo desabilitamos el formulario de agregar descuento
        if($(this).val() != ''){
            $('#nombreDescuentoAdd').prop('disabled', true);
            $('#estadoAdd').prop('disabled', true);
            $('#posteriorCPAdd').prop('disabled', true);
            $('#bodegasDescuentoAdd').prop('disabled', true);
            //Cambiamos el boton de guardar a reactivar
            $('#btnGuardarDescuentoNuevo').text('Reactivar Descuento seleccionado');
        } else {
            $('#nombreDescuentoAdd').prop('disabled', false);
            $('#estadoAdd').prop('disabled', false);
            $('#posteriorCPAdd').prop('disabled', false);
            $('#bodegasDescuentoAdd').prop('disabled', false);
            //Cambiamos el boton a guardar
            $('#btnGuardarDescuentoNuevo').text('Guardar');            
        }
    });

    //Creamos un evento para cuando se pulse el boton de guardar descuento nuevo
    $(document).on('click', '#btnGuardarDescuentoNuevo', function(){
        //Para este evento hay 2 escenarios, si se selecciona un descuento inactivo se reactiva, si no se crea uno nuevo
        //Verificamos el valor del select de descuentos inacactivos para ver que escenario es, no cuenta si esta seleccionado "selecte descuento inactivo"
        console.log("Descuentos inactivos: "+$('#descuentosInactivos').val());
        if($('#descuentosInactivos').val()){     
            console.log("Vamos a reactivar un descuento");
            //Reactivamos el descuento
            var idDescuento = $('#descuentosInactivos').val();
            var id_prov = $('#selectProveedor').val();
            //Llamamos al service para reactivar el descuento
            $.ajax({
                url: 'services/mainService.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'reactivarDescuento',
                    controller: "GmcvDescuento",
                    args: {
                        'id_descuento': idDescuento,
                        'id_prov': id_prov
                    }
                },
                success: function(response) {
                    console.log(response);
                    //Mostramos el mensaje en un alert
                    // alert(response.message);
                    messageAlert(response.message, 'success',false);
                    //Cerramos el modal
                    $('#modalAgregarDescuento').modal('hide');
                    //Volvemos a pintar la tabla de productos para que se reflejen los cambios
                    var bodegasProveedor = $('#selectBodega').val();
                    bodegasProveedor = bodegasProveedor.join(',');
                    pintaTablaProductosGSV($('#selectProveedor').val(), bodegasProveedor);
                },
                error: function(error) {
                    alert('Error al reactivar el descuento');
                }
            });
        } else {
            console.log("Vamos a crear un nuevo descuento");
            //Creamos un nuevo descuento
            var nombre = $('#nombreDescuentoAdd').val();
            var estado = $('#estadoAdd').val();
            var posteriorCP = $('#posteriorCPAdd').val();
            var bodegas = $('#bodegasDescuentoAdd').val();
            bodegas = bodegas.join(',');
            var id_prov = $('#selectProveedor').val();
            //Validamos que se haya seleccionado al menos una bodega
            if(bodegas == null || bodegas.length == 0){
                alert('Seleccione al menos una bodega');
                return;
            }
            console.log("Datos del nuevo descuento: "+nombre, estado, posteriorCP, bodegas, id_prov);
            //Llamamos al service para guardar el descuento
            $.ajax({
                url: 'services/mainService.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'createDescuento',
                    controller: "GmcvDescuento",
                    args: {
                        'nombre': nombre,
                        'id_prov': id_prov,
                        'id_status': estado,
                        'posteriorCP': posteriorCP,
                        'bodegas': bodegas,
                        'fecha': $('#verFecha').val()
                    }
                },
                success: function(response) {
                    
                    if(response.status == 0){
                        alert(response.message);
                        // messageAlert(response.message, 'warning',false);
                        return;
                    }

                    //Mostramos el mensaje en un alert
                    // alert(response.message);
                    messageAlert(response.message, 'success',false);
                    //Cerramos el modal
                    $('#modalAgregarDescuento').modal('hide');
                    //Volvemos a pintar la tabla de productos para que se reflejen los cambios
                    var bodegasProveedor = $('#selectBodega').val();
                    bodegasProveedor = bodegasProveedor.join(',');
                    pintaTablaProductosGSV($('#selectProveedor').val(), bodegasProveedor);
                },
                error: function(error) {
                    alert('Error al guardar el descuento');
                    console.log(error);
                }
            });
        } // Fin de if else

    });

    //Creamos un evento para cuando se pulse el boton de cancelar gsv btnCancelarGSV
    $(document).on('click', '#btnCancelarGSV', function(){
        //Solicitamos una confirmación al usuario
        if(!confirm('¿Está seguro de cancelar los cambios?')){
            return;
        }
        // Recargamos la tabla de productos
        var bodegasProveedor = $('#selectBodega').val();
        bodegasProveedor = bodegasProveedor.join(',');
        pintaTablaProductosGSV($('#selectProveedor').val(), bodegasProveedor);
    });
        



    //Actualizamos los iconos
    feather.replace();
}); //Fin document ready


function calculaCostoPactado(id){
    //Obtenemos el precio de lista
    var precioLista = $('#precioLista-'+id).val();
    //Obtnemos los descuentos antes de costo pactado
    var descuentosAntesCP = JSON.parse(sessionStorage.getItem('descuentosAntesCP'));

    //Descuentos a nivel local para el producto
    var descuentosAntesCPProducto = 0

    $.each(descuentosAntesCP, function(index, descuento){
        var nombre = descuento.nombre.replace(/\s/g, "").toLowerCase();
        var tasa = $('#'+nombre+'-'+id).val();
        descuentosAntesCPProducto += parseFloat(tasa);    
    });
    //Aplicamos los descuentos antes de costo pactado
    var costoConDescuentosAntesCP = parseFloat(precioLista) * (1 - (descuentosAntesCPProducto / 100));

    //Pintmos el costo con descuentos antes de costo pactado
    $('#costoPactado-'+id).text(parseFloat(costoConDescuentosAntesCP).toFixed(2));
}

//Calcula el costo de ingreso con impuestos
function calculaCostoIngresoNeto(id){
    //Obtenemos el costo pactado
    var costoPactado = parseFloat($('#costoPactado-'+id).text());
    var descuentosDespCP = JSON.parse(sessionStorage.getItem('descuentosDespCP'));
    var descuentosDespCPProducto = 0;
    $.each(descuentosDespCP, function(index, descuento){
        var nombre = descuento.nombre.replace(/\s/g, "").toLowerCase();
        var tasa = $('#'+nombre+'-'+id).val();
        console.log("Descuento: "+nombre+" Tasa: "+tasa);
        descuentosDespCPProducto += parseFloat(tasa);
    });

    //Aplicamos a costo pactado los descuentos después de costo pactado
    var costoIngreso = parseFloat(costoPactado) * (1 - (descuentosDespCPProducto / 100));
    //El costo ingreso se calculo asi: 
    console.log("Costo ingreso: "+costoPactado+" * (1 - ("+descuentosDespCPProducto+" / 100)) = "+costoIngreso);

    //Obtnemos los impuestos del producto, son parte de los atributos del input precioLista
    var iva = $('#precioLista-'+id).attr('iva');
    var ieps = $('#precioLista-'+id).attr('ieps');
    var iepsxl = $('#precioLista-'+id).attr('iepsxl');

    //Calculamos el costo pactado con impuestos
    var costoConIEPS = parseFloat((costoIngreso) * (1 + parseFloat(ieps))) + parseFloat(iepsxl);
    var costoConIVA = costoConIEPS * (1 + parseFloat(iva));
    //Pintamos el costo con impuestos
    $('#neto-'+id).text(parseFloat(costoConIVA).toFixed(2));
    //Pintamos el costo bruto
    $('#bruto-'+id).text(parseFloat(costoIngreso).toFixed(2));
}





function pintaTablaProductosGSV(idProveedor, bodegas) {
    //Activamos los botones definir GSV y agregar descuento
    $('#btnDefinirGSV').prop('disabled', false);
    $('#btnAddDescuento').prop('disabled', false);

    //Al pintar un editable bloqueamos  el boton Defini GSV
    $('#btnDefinirGSV').prop('disabled', false);
    //Llamamos al service para obtener los productos del proveedor
    $.ajax({
        url: 'services/mainService.php',
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'getProductosByProvBod',
            controller: "Compra",
            args: {
                'idProveedor': idProveedor,
                'bodegas': bodegas,
                'fecha': $('#verFecha').val()
            }
        },
        success: function(response) {
            console.log(response);
            $('#tituloTabla').text('Productos');
            //Desbloqueamos la fecha
            $('#verFecha').prop('disabled', false);
            columnas = 5 + response.descuentosAntesCP.length + response.descuentosDespCP.length;
            //Pintramos la tabla con los productos del proveedor, comenzando por las cabeceras
            var tabla = '<table id="tablaProductosFacturaAlmacen" class="table table-striped table-bordered table-sm tablaPequena">';
            tabla += '<thead><tr> <th colspan="4"></th>';
            if(response.descuentosAntesCP.length > 0){
                tabla += '<th style="text-align: center;" colspan="'+response.descuentosAntesCP.length+'"> Antes Costo Pactado </th>';
                tabla += '<th colspan="1"></th>';
            }else{
                tabla += '<th colspan="1"></th>';
            }
            if(response.descuentosDespCP.length > 0){
                tabla += '<th style="text-align: center;" colspan="'+response.descuentosDespCP.length+'"> Después Costo Pactado </th>';
            }
            tabla += '<th style="text-align: center;" colspan="2"> Costo Ingreso </th></tr><thead>';
            tabla += '<thead>';
            tabla += '<tr>';
            tabla += '<th>Producto</th>';
            tabla += '<th style="text-align: center;">Precio Lista<br>GSV</th>';
            tabla += '<th style="text-align: center;">Fecha inicio</th>';
            tabla += '<th style="text-align: center;">Fecha fin</th>';
            //Iteramos los descuentos antes de costo pactado (CP)
            $.each(response.descuentosAntesCP, function(index, descuento) {
                tabla += '<th style="text-align: center;">'+descuento.nombre+'<button descuento="'+descuento.id_descuento+'" class="btn btn-info ml-1 btn-small descuentoEditable"><i class="feather-8" data-feather="edit"></i></button></th>';
            });
            //Continuamos con costo pactado
            tabla += '<th style="text-align: center;">Costo<br>Pactado</th>';
            //Iteramos los descuentos antes de costo pactado (CP)
            $.each(response.descuentosDespCP, function(index, descuento) {
                tabla += '<th style="text-align: center;">'+descuento.nombre+'<button descuento="'+descuento.id_descuento+'" class="btn btn-info ml-1 btn-small descuentoEditable"><i class="feather-8" data-feather="edit"></i></button></th>';
            });
            tabla += '<th style="text-align: center;">Bruto</th>';
            tabla += '<th style="text-align: center;">Neto</th>';
            tabla += '</tr></thead>';
            tabla += '<tbody>';
            //Comenzamos a pintar los productos 
            $.each(response.productos, function(index, producto){
                tabla += '<tr>';
                //Contenido de las columnas
                tabla += '<td>'+producto.nombre+'</td>';
                tabla += '<td class="dt-right" id="precioListaText-'+producto.id+'" valorReal="'+producto.precioListaCatalogo+'" >'+parseFloat(producto.precioListaCatalogo).toFixed(2)+'</td>';
                tabla += '<td id="fechaInicioPlText-'+ producto.id +'" >' + (producto.fechaInicioPL ? producto.fechaInicioPL : '-- / -- / --') + '</td>';
                tabla += '<td>'+(producto.fechaFinPL ? producto.fechaFinPL : '-- / -- / --')+'</td>';
                //Iteramos los descuentos antes de costo pactado (CP)
                $.each(response.descuentosAntesCP, function(index, descuento) {
                    var tasa = 0;
                    var id = 0
                    var nombre = descuento.nombre.replace(/\s/g, "").toLowerCase();
                    //Recorremos los descuentos de del producto para ver si tiene el descuento
                    $.each(producto.descuentosAntesCP, function(index, descuentoProducto) {
                        if(descuentoProducto.id_descuento == descuento.id_descuento){
                            tasa = descuentoProducto.tasa; 
                            id = descuentoProducto.id_descuento;
                        }
                    });
                    tabla += '<td id="'+nombre+'Text-'+descuento.id_descuento+'-'+id+' " class="dt-right">'+tasa+' %</td>';
                });
                //Continuamos con costo pactado
                tabla += '<td class="dt-right" id="costoPactadoText-'+producto.id+'" valorReal="'+producto.costoPactado+'">'+parseFloat(producto.costoPactado).toFixed(2)+'</td>';
                //Iteramos los descuentos después de costo pactado (CP)
                $.each(response.descuentosDespCP, function(index, descuento) {
                    var tasa = 0;
                    var id = 0
                    var nombre = descuento.nombre.replace(/\s/g, "").toLowerCase();
                    //Recorremos los descuentos de del producto para ver si tiene el descuento
                    $.each(producto.descuentosDespCP, function(index, descuentoProducto) {
                        if(descuentoProducto.id_descuento == descuento.id_descuento){
                            tasa = descuentoProducto.tasa; 
                            id = descuentoProducto.id_descuento;
                        }
                    });
                    tabla += '<td id="'+nombre+'Text-'+descuento.id_descuento+'-'+id+' " class="dt-right">'+tasa+' %</td>';
                });
                tabla += '<td class="dt-right" id="brutoText-'+producto.id+'" valorReal="'+producto.costoIngresoBruto+'">'+parseFloat(producto.costoIngresoBruto).toFixed(2)+'</td>';
                tabla += '<td class="dt-right" id="netoText-'+producto.id+'" valorReal="'+producto.costoIngresoNeto+'">'+parseFloat(producto.costoIngresoNeto).toFixed(2)+'</td>';

                //Fin de la fila
                tabla += '</tr>';
            });
            //Fin del cuerpo de la tabla
            tabla += '</tbody>';
            //Terminamos la tabla
            tabla += '</table>';
            //Pinta la tabla en el div correspondiente
            $('#divTablaProductos').html(tabla);

            //Pintamos los fatos del proveedor y bodega seleccionados
            $('#nombreProveedor').text(response.proveedor[0].largo);
            // Leemos las bodegas seleccionadas
            var bodegas = $('#selectBodega').val();
            console.log("Bodegas del select: ", bodegas);
            //Si hay muchaas bodegas seleccionadas, mostramos "Varias bodegas"
            if(bodegas.length > 1){
                $('#nombreBodega').text('Varias bodegas seleccionadas');
                //Bloquemos el boton de exportar a excel
                $('#btnExportarExcel').prop('disabled', true);
            }else{
                //Si solo hay una bodega seleccionada, mostramos el nombre de la bodega
                $('#nombreBodega').text($('#selectBodega option:selected').text());                
                //Desbloqueamos el boton de exportar a excel
                $('#btnExportarExcel').prop('disabled', false);
            }
            //Actualizamos los iconos
            feather.replace();
            //Alamcenamos los datos del respose en un objeto de sesión sessionStorage
            sessionStorage.setItem('productos', JSON.stringify(response.productos));
            sessionStorage.setItem('descuentosAntesCP', JSON.stringify(response.descuentosAntesCP));
            sessionStorage.setItem('descuentosDespCP', JSON.stringify(response.descuentosDespCP));

            // const driver = window.driver.js.driver;
            // const driverObj = driver();
            
            // //Le dicmos al usuario que se actualizó la tabla usando el driver.js
            // driverObj.highlight({
            //     element: document.querySelector('#divTablaProductos'),
            //     popover: {
            //         title: 'Tabla actualizada',
            //         description: 'La tabla de productos se ha actualizado correctamente',
            //         position: 'top'
            //     }
            // });

            console.log(response);

        },
        error: function(error) {
            alert('Error al obtener los productos del proveedor');
        }
    });
}// Fin de la función pintaTablaProductosGSV

function pintaTablaProductosGSVEditable(idProveedor, bodegas) {
    //Activamos los botones definir GSV y agregar descuento
    $('#btnAddDescuento').prop('disabled', false);
    //Al pintar un editable bloqueamos  el boton Defini GSV
    $('#btnDefinirGSV').prop('disabled', true);
    //Llamamos al service para obtener los productos del proveedor
    $.ajax({
        url: 'services/mainService.php',
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'getProductosByProvBod',
            controller: "Compra",
            args: {
                'idProveedor': idProveedor,
                'bodegas': bodegas,
                'fecha': $('#verFecha').val()
            }
        },
        success: function(response) {
            // console.log(response);
            //Cambiamos el titulo de la tabla a modo de edicion de precios de lista #tituloTabla
            $('#tituloTabla').text('Productos - Definir GSV');
            columnas = 5 + response.descuentosDespCP.length;
            //Pintramos la tabla con los productos del proveedor, comenzando por las cabeceras
            var tabla = '<table id="tablaProductosFacturaAlmacen" class="table table-striped table-bordered table-sm tablaPequena">';
            tabla += '<thead><tr> <th colspan="4"></th>';
            
            if (response.descuentosAntesCP.length > 0) {
                tabla += '<th style="text-align: center;" colspan="'+response.descuentosAntesCP.length+'"> Antes Costo Pactado </th>';
            }
            
            tabla += '<th colspan="1"></th>';
            
            if (response.descuentosDespCP.length > 0) {
                tabla += '<th style="text-align: center;" colspan="'+response.descuentosDespCP.length+'"> Despues Costo Pactado </th>';
            }
            
            tabla += '<th style="text-align: center;" colspan="2"> Costo Ingreso </th></tr><thead>';
            tabla += '<thead>';
            tabla += '<tr>';
            tabla += '<th>Producto</th>';
            tabla += '<th style="text-align: center;">Precio Lista<br>GSV</th>';
            tabla += '<th style="text-align: center;">Fecha inicio</th>';
            tabla += '<th style="text-align: center;">Fecha fin</th>';
            //Iteramos los descuentos antes de costo pactado (CP)
            $.each(response.descuentosAntesCP, function(index, descuento) {
                tabla += '<th style="text-align: center;">'+descuento.nombre+'</th>';
            });
            //Continuamos con costo pactado
            tabla += '<th style="text-align: center;">Costo<br>Pactado</th>';
            //Iteramos los descuentos antes de costo pactado (CP)
            $.each(response.descuentosDespCP, function(index, descuento) {
                tabla += '<th style="text-align: center;">'+descuento.nombre+'</th>';
            });
            tabla += '<th style="text-align: center;">Bruto</th>';
            tabla += '<th style="text-align: center;">Neto</th>';
            tabla += '</tr></thead>';
            tabla += '<tbody>';
            //Comenzamos a pintar los productos 
            $.each(response.productos, function(index, producto){
                tabla += '<tr id="producto-'+producto.id+'" >';
                //Contenido de las columnas
                tabla += '<td>'+producto.nombre+'</td>';
                tabla += '<td class="dt-right"> <input iva="'+producto.iva+'" ieps="'+producto.ieps+'" iepsxl="'+producto.iepsxl+'" class="inputPrecioLista" id="precioLista-'+producto.id+'" valorReal="'+producto.precioListaCatalogo+'" type="number" step="0.01" value="'+parseFloat(producto.precioListaCatalogo).toFixed(2)+'" style="width: 105px; text-align: right;"></td>';
                tabla += '<td id="fechaInicioPl-'+ producto.id +'" >' + (producto.fechaInicioPL ? producto.fechaInicioPL : '-- / -- / --') + '</td>';
                tabla += '<td>'+(producto.fechaFinPL ? producto.fechaFinPL : '-- / -- / --')+'</td>';
                //Iteramos los descuentos antes de costo pactado (CP)
                $.each(response.descuentosAntesCP, function(index, descuento) {
                    var tasa = 0;
                    var nombre = descuento.nombre.replace(/\s/g, "").toLowerCase();
                    //Recorremos los descuentos de del producto para ver si tiene el descuento
                    $.each(producto.descuentosAntesCP, function(index, descuentoProducto) {
                        if(descuentoProducto.id_descuento == descuento.id_descuento){
                            tasa = descuentoProducto.tasa; 
                        }
                    });
                    tabla += '<td class="dt-right"> <input class="inputDescuentoAntesCP" idDescuento="'+descuento.id_descuento+'" id="'+nombre+'-'+producto.id+'" valorReal="'+tasa+'" type="number" step="0.01" value="'+parseFloat(tasa).toFixed(2)+'" style="width: 105px; text-align: right;"></td>';
                });
                //Costo pactado
                tabla += '<td class="dt-right" id="costoPactado-'+producto.id+'" valorReal="'+producto.costoPactado+'">'+parseFloat(producto.costoPactado).toFixed(2)+'</td>';
                //Iteramos los descuentos después de costo pactado (CP)
                $.each(response.descuentosDespCP, function(index, descuento) {
                    var tasa = 0;
                    var nombre = descuento.nombre.replace(/\s/g, "").toLowerCase();
                    //Recorremos los descuentos de del producto para ver si tiene el descuento
                    $.each(producto.descuentosDespCP, function(index, descuentoProducto) {
                        if(descuentoProducto.id_descuento == descuento.id_descuento){
                            tasa = descuentoProducto.tasa; 
                        }
                    });
                    tabla += '<td class="dt-right"> <input class="inputDescuentoDespCP" idDescuento="'+descuento.id_descuento+'" id="'+nombre+'-'+producto.id+'" valorReal="'+tasa+'" type="number" step="0.01" value="'+parseFloat(tasa).toFixed(2)+'" style="width: 105px; text-align: right;"></td>';
                });
                tabla += '<td class="dt-right" id="bruto-'+producto.id+'" valorReal="'+producto.costoIngresoBruto+'">'+parseFloat(producto.costoIngresoBruto).toFixed(2)+'</td>';
                tabla += '<td class="dt-right" id="neto-'+producto.id+'" valorReal="'+producto.costoIngresoNeto+'">'+parseFloat(producto.costoIngresoNeto).toFixed(2)+'</td>';

                //Fin de la fila
                tabla += '</tr>';
            });
            //Fin del cuerpo de la tabla
            tabla += '</tbody>';
            //Terminamos la tabla
            tabla += '</table>';

            //Agregamos un div para agregar un par de botones para guardar y cancelar
            tabla += '<div style="text-align: center;">';
            tabla += '<button id="btnGuardarGSV" class="btn btn-success btn-lg mt-lg-4 mr-2 "><i class="feather-16" data-feather="save"></i> Guardar cambios para esta fecha</button>';
            tabla += '<button id="btnCancelarGSV" class="btn btn-danger btn-lg mt-lg-4 ml-2 "><i class="feather-16" data-feather="x"></i> Cancelar</button>';
            tabla += '</div>';

            //Pinta la tabla en el div correspondiente
            $('#divTablaProductos').html(tabla);

            //Pintamos los fatos del proveedor y bodega seleccionados
            $('#nombreProveedor').text(response.proveedor[0].largo);
            // Leemos las bodegas seleccionadas
            var bodegas = $('#selectBodega').val();
            console.log("Bodegas del select: ", bodegas);
            //Si hay muchaas bodegas seleccionadas, mostramos "Varias bodegas"
            if(bodegas.length > 1){
                $('#nombreBodega').text('Varias bodegas seleccionadas');
            }else{
                //Si solo hay una bodega seleccionada, mostramos el nombre de la bodega
                $('#nombreBodega').text($('#selectBodega option:selected').text());                
            }
            //Actualizamos los iconos
            feather.replace();
            //Alamcenamos los datos del respose en un objeto de sesión sessionStorage
            sessionStorage.setItem('productos', JSON.stringify(response.productos));
            sessionStorage.setItem('descuentosAntesCP', JSON.stringify(response.descuentosAntesCP));
            sessionStorage.setItem('descuentosDespCP', JSON.stringify(response.descuentosDespCP));
        },
        error: function(error) {
            alert('Error al obtener los productos del proveedor');
            messageAlert('Error al obtener los productos del proveedor', 'danger',false);
        }
    });
}
</script>
<?php
$rutaArchivo = file_exists($ruta."sys/hf/pie_v3.php") ? $ruta."sys/hf/pie_v3.php" : "../../../sys/hf/pie_v3.php";
include $rutaArchivo;
?>