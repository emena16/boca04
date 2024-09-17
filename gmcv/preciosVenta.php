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

#tablaProductosPVta+#tablaAlineadaDerecha {
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


<div class="page-header layout-top-spacing title-header">
    <div class="pge-title" style="margin-left: 3.5%;">
        <h3 id="tituloPagina">&nbsp; Planificador de precios de venta</h3>
    </div>
</div>
<?php

$compra = new Compra();
$proveedores = $compra->getProveedores();
?>

<div class="card card-principal">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
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

            <div class="col-md-4">
                <label for="miSelect">Selecciona bodega(s):</label>
                <select id="selectBodega" multiple></select>
            </div>

            <div class="col-md-5">
                <label for="miSelect">Selecciona Unidad(es) Operaiva(s):</label>
                <select id="selectUOperativas" multiple></select>
            </div>
        </div>
                
        <div class="row">
            <div class="col-md-4">
                <label for="miSelect">Fecha de Planeación:</label>
                <input type="date" id="verFecha" class="form-control form-control-sm" value="<?= date("Y-m-d",strtotime(date("Y-m-d")."+ 1 days")) ?>" min="<?= date("Y-m-d",strtotime(date("Y-m-d")."+ 1 days")) ?>">
            </div>
            <div class="col-md-2">
                <button id="btnBuscarProductos" class="btn btn-primary btn-lg mt-lg-4"><i class="feather-16" data-feather="list"></i> Consultar/Listar</button>
            </div>
        </div>
        <div class="row">
            <div class="col-md-10">
                <div class="row justify-content-center">
                    <div id="divMessage" class="col-md-8 mt-lg-4"></div>
                </div>
            </div>
        </div>
        <hr>


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
                <!-- <button id="btnDefinirGSV" disabled="true" class="btn btn-primary ml-1"><i class="feather-16" data-feather="edit"></i> Definir GSV</button> -->
                <!-- <button id="btnAddDescuento" disabled="true" class="btn btn-success ml-1"><i class="feather-16" data-feather="plus"></i> Agregar/Reactivar descuento</button> -->
                <!-- <button id="btnExportarExcel" class="btn btn-primary ml-1"><i class="feather-16" data-feather="file-text"></i> Exportar a Excel</button> -->
            </div>
        </div>

        <div class="row mt-lg-4">
            <!-- <div class="col-md-7">
                <label for="prov">Proveedor:</label>
                <label class="form-control form-control-sm" id="nombreProveedor"></label>
            </div>

            <div class="col-md-5">
                <label for="bod">Bodega:</label>
                <label class="form-control form-control-sm" id="nombreBodega"></label>
            </div> -->

            <!-- <div class="col-md-2">
                <label for="fecha">Fecha:</label>
                <input type="date" id="verFecha" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
            </div> -->
        </div>





        <div class="row">
            <div class="col-md-12">
                <div class="page-header layout-top-spacing title-header mt-lg-4">
                    <div class="pge-title">
                        <h5 id="tituloTabla">Productos</h5>
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
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarDescuentoLabel">Modificar descuento</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <input type="hidden" id="idDescuento" value="" name="idDescuento">
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <label for="nombreDescuento">Nombre del descuento:</label>
                        <input type="text" id="nombreDescuento" class="form-control form-control-sm">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-8">
                        <label for="estado">Estado:</label>
                        <select name="estado" id="estado" class="form-control form-control-sm">
                            <option value="1">Vigente</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="posteriorCP">Posterior a CP:</label>
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
            <button id="btnGuardarDescuento" type="button" class="btn btn-primary">Guardar</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                
            </div>
        </div>
    </div>
</div>
            
<!-- modal para copiar un descuentos y aplicarlo en toda la tabla -->
<div class="modal fade" id="modalCopiarDescuento" tabindex="-1" role="dialog" aria-labelledby="modalCopiarDescuentoLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCopiarDescuentoLabel">Copiar descuento a toda la tabla</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            
            <div class="modal-body statbox widget box box-shadow widget-content widget-content-area">
                <div class="row form-row">
                    <div class="col-md-12 mb-4">
                        <label for="validationCustomUsername">Aplicar margen a toda la hoja:</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="inputMargenGeneral" min="0" max="99.99" placeholder="Margen porcentual" aria-describedby="inputGroupPrepend" required>
                            <div class="input-group-prepend">
                                <span class="input-group-text" style="background-color: #f1f2f3;" id="inputGroupPrepend">%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button id="btnCopiarDescuentoGeneral" type="button" class="btn btn-primary">Aplicar a toda la hoja</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>


<!-- modal para mostrar un mensaje de procesando la información utilizando spinner -->
<div class="modal fade" id="modalProcesando" tabindex="-1" role="dialog" aria-labelledby="modalProcesandoLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="background-color: transparent; border: none;">
            <div class="modal-body">
                <div class="d-flex justify-content-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">
                            Procesando información...
                            <p>Por favor espere...</p>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Script para el select2 -->
<script src="../../../sys/bocampana_vista/plugins/select2/select2.min.js"></script>
<script src="../../../sys/bocampana_vista/plugins/select2/es.js"></script>

<script src="./js/functions.js"></script>



<script>
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

    //Uniciliamos el select de unidades operativas
    $('#selectUOperativas').select2({
        language: 'es',
        multiple: true,
        closeOnSelect: false,
        search: true,
        placeholder: "Seleccione una unidad operativa"
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
                // console.log(response);
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




    //Escuchamos el evento de cambio en el select de bodegas para obtener las unidades operativas
    $(document).on('change', '#selectBodega', function() {
        //Obtenemos las bodegas seleccionadas
        var bodegas = $(this).val();
        //Obtenemos el id del proveedor
        var idProveedor = $('#selectProveedor').val();

        //Si se selecciona la opción de "Seleccionar todas", no hacemos nada
        if (bodegas.includes('select_all')) {
            return;
        }

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

        //Llamamos al service para obtener las unidades operativas
        $.ajax({
            url: 'services/mainService.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'getOficinasByidProvIdBod',
                controller: "Proveedor",
                args: {
                    'proveedores': idProveedor,
                    'bodegas': bodegas
                }
            },
            success: function(response) {
                // // console.log(response);
                //Limpiamos las opciones del select de unidades operativas
                $('#selectUOperativas').empty();
                //Recorremos el array de unidades operativas y las agregamos al select
                $.each(response, function(index, uOperativa) {
                    $('#selectUOperativas').append('<option value="' + uOperativa.id + '">' +
                        uOperativa.nombre + '</option>');
                });
            },
            error: function(error) {
                alert('Error al obtener las unidades operativas');
            }
        });

        delateAlertMessage();
    });


    //Creamos un evento cuando se haga click en el botón de buscar productos
    $(document).on('click', '#btnBuscarProductos', function() {
        //Obtenemos el id del proveedor
        var idProveedor = $('#selectProveedor').val();
        //Obtenemos las bodegas seleccionadas
        var bodegas = $('#selectBodega').val();
        //Obtenemos las unidades operativas seleccionadas
        var uOperativas = $('#selectUOperativas').val();
        //Obtenemos la fecha
        var fecha = $('#verFecha').val();
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
        //Validamos que se haya seleccionado al menos una unidad operativa
        if (uOperativas == null || uOperativas.length == 0) {
            alert('Seleccione al menos una unidad operativa');
            return;
        }

        //Parseamos las bodegas y las unidades operativas a un string separado por comas
        bodegas = bodegas.join(',');
        uOperativas = uOperativas.join(',');
        
        //Llamamos a la funcion pintaTablaPVta para pintar la tabla de productos
        pintaHojaTrabajoPVta(idProveedor, bodegas, uOperativas, fecha);
    });


    //Creamos un evento para cuando se pulse el boton de copiar descuento a toda la tabla id: margenCopy
    $(document).on('click', '#margenCopy', function() {
        //Limpiamos el input de margen antes de mostrar el modal
        $('#inputMargenGeneral').val('');
        //Mostramos el modal para capturar el descuento
        $('#modalCopiarDescuento').modal('show');
    });


    //Creamos un evento para cuando se pulse el boton de copiar descuento a toda la tabla id: btnCopiarDescuentoGeneral
    $(document).on('click', '#btnCopiarDescuentoGeneral', function() {
        //Obtenemos el valor del input de margen
        var margen = $('#inputMargenGeneral').val();
        //Validamos que el margen sea mayor a 0
        if (margen <= 0) {
            alert('El margen debe ser mayor a 0');
            return;
        }        
        //Obtenemos los productos de la tabla
        var productos = JSON.parse(sessionStorage.getItem('productos'));
        //Iteramos los productos y les asignamos el margen
        $.each(productos, function(index, producto) {
            //Seteamos el input de margen
            $('#margenNuevo-' + producto.id_pm).attr('value', margen);
            $('#margenNuevo-' + producto.id_pm).val(margen);
            //Actualizamos el valor real del input
            $('#margenNuevo-' + producto.id_pm).attr('valorReal', margen);
            //Disparamos el evento change para que se calcule el precio de venta
            $('#margenNuevo-' + producto.id_pm).trigger('change');
        });

        //Cerramos el modal
        $('#modalCopiarDescuento').modal('hide');
    });

    //Creamos un evento para cuando se pulse el bonto de guardar: btnGuardarCambios
    $(document).on('click', '#btnGuardarCambios', function() {
        //Antes de todo solicitamos confirmacion al usuario sobre la fecha en la que se ran aplicados los cambios de precio
        //Convertimos la fecha en dia de la semana y mes y año
        var fecha = $('#verFecha').val();
        const fechaSeleccionada = new Date(fecha);
        const fechaFormateada = formatearFecha(fechaSeleccionada);        

        //No se puede seleccionar una fecha menor a mañana por lo que avisamos al usuario
        if (fecha < '<?= date("Y-m-d",strtotime(date("Y-m-d")."+ 1 days")) ?>') {
            alert('La fecha seleccionada no puede ser menor a mañana');
            return;
        }
        
        if (!confirm('¿Está seguro de que desea aplicar los cambios \n de precio en la fecha: ' + fechaFormateada + '?')) {
            return;
        }

        //Obtenemos los productos de la tabla
        var productos = JSON.parse(sessionStorage.getItem('productos'));
        //Antes damos una iteración para validar que todos los productos tengan un precio de venta o no sean invalidor o nulos o infinitos
        var productosInvalidos = [];
        //Despintamos los inputs de precio
        $('.precioNuevoTxt').css('background-color', '');
        $.each(productos, function(index, producto) {
            //Obtenemos el precio de venta
            var precioVenta = parseFloat($('#precioNuevo-' + producto.id_pm).text());
            //Validamos que el precio de venta sea mayor a 0 o no es un número
            if (precioVenta <= 0 || isNaN(precioVenta) || !isFinite(precioVenta)) {
                productosInvalidos.push(producto.id_pm);
            }   
        });

        //Validamos que no haya productos con precio de venta igual a 0
        if (productosInvalidos.length > 0) {
            //Recorre los productos invalidos y los pinta de color rojo
            $.each(productosInvalidos, function(index, idProducto) {
                $('#precioNuevo-' + idProducto).css('background-color', 'red');
            });
            alert('Hay productos que no tienen un precio de venta válido');
            return;

        }


        //Mostramos el modal de procesando información, este no debe cerrarse hasta que se reciba la respuesta
        $('#modalProcesando').modal({
            show: true,
            backdrop: 'static',
            keyboard: false
        });

        //Creamos un arreglo para guardar los productos con los cambios
        var paquete = [];
        var modelo = {
            id_prod: 0,
            oficinas: [],
            id_status: 4,
            fecha: fecha,
            venta: 0.00,
            margen: 0.00
        };

        //Iteramos los productos y guardamos los cambios
        $.each(productos, function(index, producto) {
            //Creamos una copia del modelo para no modificar el original
            var modeloCopia = JSON.parse(JSON.stringify(modelo));
            //Asignamos los valores al modelo
            modeloCopia.id_prod = producto.id_pm;
            //Convertimos el string de producto.oficinas a un array
            modeloCopia.oficinas = producto.oficinas.split(',');
            //Asignamos el precio de venta
            modeloCopia.venta = parseFloat($('#precioNuevo-' + producto.id_pm).text());
            //Asignamos el margen
            modeloCopia.margen = parseFloat($('#margenNuevo-' + producto.id_pm).val());
            //Agregamos el modelo al paquete
            paquete.push(modeloCopia);
            //Destruimos el modelo copia
            delete modeloCopia;           
        });

        // console.log(paquete);

        //Evivamos el paquete al servicio para guardar los cambios
        $.ajax({
            url: 'services/mainService.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'setPrecioVentaOficina',
                controller: "GmcvPrecio",
                args: {
                    'paquete': paquete,
                    'fecha': fecha

                },
            },
            beforeSend: function() {
                               
                    
            },
            success: function(response) {
                console.log(response);
                messageAlert('Los cambios se guardaron correctamente', 'success', false);

                setTimeout(function() {
                    $('#modalProcesando').modal('hide');
                }, 300); // Un retraso de 100ms
                //Desactivamos el botón de guardar cambios
                //Despinta los inputs de precio y margen
                $('.inputMargenNuevo').css('background-color', 'white');
                //inputMargen
                $('.inputPrecioNuevo').css('background-color', 'white');                
                $('#btnGuardarCambios').prop('disabled', true);


                //Movemos la pantalla hacie el boton de buscar productos
                $('html, body').animate({
                    scrollTop: $("#tituloPagina").offset().top
                }, 350);

                //Por si acaso volvemos a verificar que el modal se haya quitado por que sucedio que no se quitó
                setTimeout(function() {
                    $('#modalProcesando').modal('hide');
                }, 100); // Un retraso de 100ms
            },
            error: function(error) {
                alert('Error al guardar los cambios');
                //Cerramos el modal de procesando información
                setTimeout(function() {
                    $('#modalProcesando').modal('hide');
                }, 300); // Un retraso de 100ms
                
            }
    });
    
});




    // Función para formatear la fecha
    function formatearFecha(fecha) {
        const opciones = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        return fecha.toLocaleDateString('es-ES', opciones);
    }    

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

        //LLamamos a la funcion que limpia el div de mensajes en caso de que exista
        delateAlertMessage();
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
        //Bloquemos la fecha para que no se pueda cambiar
        $('#verFecha').prop('disabled', true);
        pintaTablaProductosPVtaEditable(idProveedor, bodegas);

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
        // console.log("ID del descuento: ", idDescuento);

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
                // console.log(response);
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
                // console.log(response);
                //Mostramos el mensaje en un alert
                alert(response.message);
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


    //Creamos un evento para cuando se pulse el boton de guardar descuento nuevo
    $(document).on('click', '#btnGuardarDescuentoNuevo', function(){
        //Para este evento hay 2 escenarios, si se selecciona un descuento inactivo se reactiva, si no se crea uno nuevo
        //Verificamos el valor del select de descuentos inacactivos para ver que escenario es
        if($('#descuentosInactivos').val() != ''){
            // console.log("Vamos a reactivar un descuento");
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
                    // console.log(response);
                    //Mostramos el mensaje en un alert
                    alert(response.message);
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
                        'bodegas': bodegas
                    }
                },
                success: function(response) {
                    
                    if(response.status == 0){
                        alert(response.message);
                        return;
                    }

                    //Mostramos el mensaje en un alert
                    alert(response.message);
                    //Cerramos el modal
                    $('#modalAgregarDescuento').modal('hide');
                    //Volvemos a pintar la tabla de productos para que se reflejen los cambios
                    var bodegasProveedor = $('#selectBodega').val();
                    bodegasProveedor = bodegasProveedor.join(',');
                    pintaTablaProductosGSV($('#selectProveedor').val(), bodegasProveedor);
                },
                error: function(error) {
                    alert('Error al guardar el descuento');
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
        //Recargamos la tabla de productos
        var bodegasProveedor = $('#selectBodega').val();
        bodegasProveedor = bodegasProveedor.join(',');
        pintaTablaProductosGSV($('#selectProveedor').val(), bodegasProveedor);
    });

    //Creamos un evento para activarlo al pulsar un boton descuento de la clase btnDescuentoAntes
    $(document).on('click', '.btnDescuentoAntes', function() {
        //Obtenemos el id del descuento y el id del producto
        var idDescuento = $(this).attr('id').split('-')[1];
        var idProducto = $(this).attr('id').split('-')[2];
        //Valores tomados de los atributos del boton 
        // console.log("ID del descuento: ", idDescuento);
        // console.log("ID del producto: ", idProducto);


        //Obtenemos el valor actual del descuento
        var interruptor = parseInt($(this).attr('interruptor'));
        //Este boton será un interruptor por lo que en out-line se considera que si es 0 se activa y si es 1 se desactiva, cambiamos la clase y gurdamos el valor del interruptor
        if ($(this).hasClass('btn-primary')) {
            $(this).removeClass('btn-primary').addClass('btn-outline-primary');
            $(this).attr('interruptor', '0');
        } else {
            $(this).removeClass('btn-outline-primary').addClass('btn-primary');
            $(this).attr('interruptor', '1');
        }
        //Busca mas botones con interruptor 1 y obten sus valores para recalcular los descuentos 
        var descuentosSeleccionados = []; // Descuentos que queremos conservar
        var descuentos = []; // Arreglo de descuentos en porcentaje
        var porcentajesInactivos = 0;
        $('.btnFilaDescuentoAntes-' + idProducto).each(function(index, boton) {
            descuentos.push(parseFloat($(boton).attr('value')));
            if ($(boton).attr('interruptor') == 1) {
                descuentosSeleccionados.push(parseFloat($(boton).attr('value')));
            } else {
                porcentajesInactivos += parseFloat($(boton).attr('value'));
            }
        });
        //Obtenemos el costo facturado del producto para quitarle el descuento
        let precioFinal = calculaPrecioConDescuentos(idProducto, 'Antes');
        let resultado = revertirDescuentosYAplicarSeleccionados(precioFinal, descuentos,descuentosSeleccionados);
        // Actualizamos el valor de la celda de costo pactado
        $('#costoPactado-' + idProducto).text(parseFloat(resultado.precioConDescuentosSeleccionados).toFixed(2));
        $('#costoPactado-' + idProducto).attr('valorReal', parseFloat(resultado.precioConDescuentosSeleccionados));
        
        calculaCostoIngresoNeto(idProducto);
    }); // Fin del evento click en btnDescuentoAntes

    //Creamos un evento al pulsar el boton con la clase btnDescuentoDesp
    $(document).on('click', '.btnDescuentoDesp', function() {
        //Obtenemos el id del descuento y el id del producto
        var idDescuento = $(this).attr('id').split('-')[1];
        var idProducto = $(this).attr('id').split('-')[2];
        //Obtenemos el valor actual del descuento
        var interruptor = parseInt($(this).attr('interruptor'));
        //Este boton será un interruptor por lo que en out-line se considera que si es 0 se activa y si es 1 se desactiva, cambiamos la clase y gurdamos el valor del interruptor
        if ($(this).hasClass('btn-primary')) {
            $(this).removeClass('btn-primary').addClass('btn-outline-primary');
            $(this).attr('interruptor', '0');
        } else {
            $(this).removeClass('btn-outline-primary').addClass('btn-primary');
            $(this).attr('interruptor', '1');
        }
        //Busca mas botones con interruptor 1 y obten sus valores para recalcular los descuentos 
        var descuentosSeleccionados = []; // Descuentos que queremos conservar
        var descuentos = []; // Arreglo de descuentos en porcentaje
        var porcentajesInactivos = 0;
        var sumaDescuentosDesp = 0;
        $('.btnFilaDescuentoDesp-' + idProducto).each(function(index, boton) {
            descuentos.push(parseFloat($(boton).attr('value')));
            if ($(boton).attr('interruptor') == 1) {
                descuentosSeleccionados.push(parseFloat($(boton).attr('value')));
            } else {
                porcentajesInactivos += parseFloat($(boton).attr('value'));
            }
            sumaDescuentosDesp += parseFloat($(boton).attr('value'));
        });
        //Obtenemos el costo ingreso del producto
        // let precioFinal = calculaPrecioConDescuentos(idProducto, 'Antes');
        let precioFinal = parseFloat($('#costoPactado-' + idProducto).attr('valorReal'));
        //Aplicamos los todos los descuentos despues de CP
        precioFinal = precioFinal * (1 - sumaDescuentosDesp / 100);
        let resultado = revertirDescuentosYAplicarSeleccionados(precioFinal, descuentos,descuentosSeleccionados);
        // console.log("Valor original antes de tozdos los descuentos: $" + resultado.precioOriginal);
        // console.log("Precio después de aplicar solo los descuentos seleccionados: $" + resultado.precioConDescuentosSeleccionados);
        
        var costoIngresoBruto = parseFloat(resultado.precioConDescuentosSeleccionados);

        //Actualizamos el valor de la celda de costo pactado
        $('#costoIngresoBruto-' + idProducto).text(parseFloat(costoIngresoBruto).toFixed(2));
        $('#costoIngresoBruto-' + idProducto).attr('valorReal', costoIngresoBruto);
        
        //Recalculamos el costo de ingreso neto utilizando los atributos de impuestos de precioLista
        var iepsxl = parseFloat($('#precioLista-'+idProducto).attr('iepsxl'));
        var eips = parseFloat($('#precioLista-'+idProducto).attr('eips'));
        var iva = parseFloat($('#precioLista-'+idProducto).attr('iva'));
        
        //Aplicamos la tasa de ieps a costoIngresoBruto
        var costoIngresoNeto = (costoIngresoBruto * (1 + iepsxl / 100))+iepsxl;
        //Aplicamos la tasa de iva
        costoIngresoNeto = costoIngresoNeto * (1 + iva / 100);
        $('#costoIngresoNeto-' + idProducto).text(parseFloat(costoIngresoBruto).toFixed(2));
        $('#costoIngresoNeto-' + idProducto).attr('valorReal', costoIngresoBruto);

        calculaCostoIngresoNeto(idProducto);

    }); // Fin de evento click en btnDescuentoDesp


    //Creamos un evento que escuche el cambio en el input de margen nuevo de la clase inputMargen
    $(document).on('change', '.inputMargen', function() {
        //Obtenemos el id del producto
        var id = $(this).attr('id').split('-')[1];
        //Obtenemos el valor del input
        var valor = parseFloat($(this).val());

        // //Seteamos el valor del input
        // $(this).attr('value', valor);
        // //Seteamos el valor real del input
        // $(this).attr('valorReal', valor);
        // //Seteamos el valor de del input utilizando puramente javascript
        // document.getElementById('margenNuevo-' + id).value = valor;

        //Obtenemos el valor real del input
        var valorReal = $(this).attr('valorReal');
        var valorAnterior = $(this).attr('valorAnt');
        // console.log("Valores tomados Valor:"+valor+" Valor Real: "+valorReal+" Valor Anterior: "+valorAnterior+" ID: "+id);

        //Pintamos la fila de amaarillo si el valor es diferente al valor real a traves del identificador de la fila con el id="celdaMargen-id"
        if (valor != valorAnterior) {
            // console.log("El valor es diferente al valor anterior");
            //pintamos el tr de amarillo para indicar que el valor ha cambiado a traves del id: celdaMargen-id
            $('#celdaMargen-' + id).css('background-color', 'yellow');
            //Actualizamos el valor anterior del input
            $(this).attr('valorAnt', valor);
            
        } else {
            $('#celdaMargen-' + id).css('background-color', 'white');
        }
        var unidadesOperativasSeleccionadas = $('#selectUOperativas').val() || [];
        // Obtenemos el margen actual del producto para compararlo con el nuevo margen y obtener la diferencia porcentual
        if (unidadesOperativasSeleccionadas.length === 1) {
            var margenActual = parseFloat($('#margenActual-' + id).text());
            $('#diffPorcentaje-' + id).text(parseFloat(margenActual - valor).toFixed(2));
            // Actualizamos el atributo valorReal de diferencial de porcentaje
            $('#diffPorcentaje-' + id).attr('valorReal', parseFloat(margenActual - valor));
        }

        //Calculamos el precio con el margen nuevo
        calculaPrecioNuevoConMargen(id);


    });

    //Creamos un evento para cuando se pulse el boton de guardar la hoja de trabajo en un archivo de excel
    $(document).on('click', '#btnExportarExcel', function() {
        //Obtenemos los productos de la tabla
        var productos = JSON.parse(sessionStorage.getItem('productos'));
        //Validamos que haya productos en la tabla
        if (productos == null || productos.length == 0) {
            alert('No hay productos en la tabla');
            return;
        }
        //Creamos un arreglo para guardar los productos
        var productosExcel = [];
        //Iteramos los productos y guardamos los datos en el arreglo
        $.each(productos, function(index, producto) {
            var productoExcel = {
                'Clave': producto.clave,
                'Descripción': producto.descripcion,
                'Precio Lista': producto.precioLista,
                'Costo Pactado': producto.costoPactado,
                'Costo Ingreso Bruto': producto.costoIngresoBruto,
                'Costo Ingreso Neto': producto.costoIngresoNeto,
                'Margen': producto.margen,
                'IEPS XL': producto.iepsxl,
                'EIPS': producto.eips,
                'IVA': producto.iva
            };
            productosExcel.push(productoExcel);
        });
        //Creamos un objeto para guardar los datos
        var datos = {
            'fecha': $('#verFecha').val(),
            'proveedor': $('#nombreProveedor').text(),
            'bodega': $('#nombreBodega').text(),
            'productos': productosExcel
        };
        //Llamamos a la función para guardar el archivo de excel
        guardarExcel(datos);
    });
    //Actualizamos los iconos
    feather.replace();
}); //Fin document ready


function guardarExcel(datos) {
    //Creamos un objeto de FormData
    var formData = new FormData();
    //Agregamos los datos al formData
    formData.append('datos', JSON.stringify(datos));

    //Llamamos al servicio para guardar el archivo de excel
    $.ajax({
        url: 'services/mainService.php',
        type: 'POST',
        dataType: 'json',
        data: formData,
        contentType: false,
        processData: false,
        success: function(response) {
            // console.log(response);
            //Validamos la respuesta
            if (response.status == 1) {
                //Descargamos el archivo
                window.location.href = response.file;
            } else {
                alert(response.message);
            }
        },
        error: function(error) {
            alert('Error al guardar el archivo de excel');
        }
    });
}


//Creamos una funcion que calcule aplique todos los descuentos y devuelva el precio final, a traves de su clase (antes o desp de CP) y el id del producto
function calculaPrecioConDescuentos(idProducto, clase) {
    let precioFinal = parseFloat($('#precioLista-' + idProducto).attr('valorReal'));
    //Obtenemos los descuentos seleccionados
    let descuentosAaplicar = 0;
    $('.btnFilaDescuento' + clase + '-' + idProducto).each(function(index, boton) {
        descuentosAaplicar += parseFloat($(boton).attr('value'));
    });
    //Calculamos el precio con los descuentos que vamos a aplicar
    let precioConDescuentos = precioFinal * (1 - descuentosAaplicar / 100);
    // console.log("La funcion: calculaPrecioConDescuentos, regresa: " + precioConDescuentos);
    return precioConDescuentos;
}


function revertirDescuentosYAplicarSeleccionados(precioFinal, descuentos, descuentosSeleccionados) {
    // Verificar si el arreglo de descuentos está vacío
    if (descuentos.length === 0) {
        return {
            precioOriginal: precioFinal.toFixed(2),
            precioConDescuentosSeleccionados: precioFinal.toFixed(2)
        };
    }

    // Calcular el valor original antes de todos los descuentos
    let precioOriginal = precioFinal;

    //Sumamos los descuentos 
    let descuentosSumados = 0;
    for (let i = 0; i < descuentos.length; i++) {
        descuentosSumados += descuentos[i];
    }
    //Aplicamos el descuento a precioOriginal
    precioOriginal = precioOriginal / (1 - descuentosSumados / 100);

    let precioConDescuentosSeleccionados = precioOriginal;

    if (descuentosSeleccionados.length > 0) {

        let descuentosSeleccionadosSumados = 0;
        for (let i = 0; i < descuentosSeleccionados.length; i++) {
            descuentosSeleccionadosSumados += descuentosSeleccionados[i];
        }
        precioConDescuentosSeleccionados = precioConDescuentosSeleccionados * (1 - descuentosSeleccionadosSumados /
        100);

    }

    return {
        precioOriginal: precioOriginal.toFixed(2),
        precioConDescuentosSeleccionados: precioConDescuentosSeleccionados.toFixed(2)
    };
}






function calculaCostoPactado(id){
    //Obtenemos el precio de lista
    var precioLista = parseFloat($('#precioLista-'+id).val());
    //Obtnemos los descuentos antes de costo pactado
    var descuentosAntesCP = JSON.parse(sessionStorage.getItem('descuentosAntesCP'));
    // console.log("Descuentos de la seccion de calculaCostoPactado: "+JSON.stringify(descuentosAntesCP));

    //Descuentos a nivel local para el producto
    var descuentosAntesCPProducto = 0;

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
    // console.log("Calculando costo de ingreso neto");
    // console.log("");
    //Obtenemos el costo pactado
    var costoPactado = parseFloat($('#costoPactado-'+id).text());
    var descuentosDespCP = JSON.parse(sessionStorage.getItem('descuentosDespCP'));
    var descuentosDespCPProducto = 0;
    $.each(descuentosDespCP, function(index, descuento){
        var nombre = descuento.nombre.replace(/\s/g, "").toLowerCase();
        var botonDescuento = 'descuento-'+descuento.id_descuento+'-'+id;
        var tasa = $('#'+botonDescuento).val();
        //Si el interruptor está en 1, sumamos el descuento
        if($('#'+botonDescuento).attr('interruptor') == 1){
            descuentosDespCPProducto += parseFloat(tasa);
        }
    });

    //Aplicamos a costo pactado los descuentos después de costo pactado
    var costoIngreso = parseFloat(costoPactado) * (1 - (descuentosDespCPProducto / 100));
    //El costo ingreso se calculo asi: 
    // console.log("Costo ingreso: "+costoPactado+" * (1 - ("+descuentosDespCPProducto+" / 100)) = "+costoIngreso);

    //Obtnemos los impuestos del producto, son parte de los atributos del input precioLista
    var iva = $('#precioLista-'+id).attr('iva');
    var ieps = $('#precioLista-'+id).attr('ieps');
    var iepsxl = $('#precioLista-'+id).attr('iepsxl');

    //Calculamos el costo pactado con impuestos
    var costoConIEPS = parseFloat((costoIngreso) * (1 + parseFloat(ieps))) + parseFloat(iepsxl);
    var costoConIVA = costoConIEPS * (1 + parseFloat(iva));
    //Pintamos el costo con impuestos
    $('#costoIngresoBruto-'+id).text(parseFloat(costoIngreso).toFixed(2));
    $('#costoIngresoBruto-'+id).attr('valorReal', costoIngreso);
    //Pintamos el costo bruto
    $('#costoIngresoNeto-'+id).text(parseFloat(costoConIVA).toFixed(2));
    $('#costoIngresoNeto-'+id).attr('valorReal', costoConIVA);

    //Recalculamos el precio de venta por unidad de venta
    calculaPrecioNuevoConMargen(id);
}

//Funcion para calcular el precio nuevo con margen a traves de una funcion que recibe el id del producto
function calculaPrecioNuevoConMargen(id){
    //Aplicamos a costo ingreso neto el margen nuevo
    var costoIngresoNeto = parseFloat($('#costoIngresoNeto-'+id).attr('valorReal'));
    var margenNuevo = parseFloat($('#margenNuevo-'+id).val());
    // console.log("Function CalculaPrecionNuevoMargen: Costo de ingreso neto: "+costoIngresoNeto);
    //Hay que leer el costo de ingreso neto
    // Convertir el margen porcentual a decimal
    let margenDecimal = margenNuevo / 100;
    // Calcular el precio de venta
    let precioNuevo = costoIngresoNeto / (1 - margenDecimal);


    //Pintamos el precio nuevo
    $('#precioNuevo-'+id).text(parseFloat(precioNuevo).toFixed(2));
    //Seteamos el valor real del input
    $('#precioNuevo-'+id).attr('valorReal', precioNuevo);

    //Si el precio es menor a 0.01 o es infinito o NaN, entonces pintamos la celda de rojo
    if(precioNuevo < 0.01 || isNaN(precioNuevo) || !isFinite(precioNuevo)){
        $('#precioNuevo-'+id).css('background-color', 'red');
    } else {
        $('#precioNuevo-'+id).css('background-color', '');
    }
    
    // console.log("Calculando precio nuevo con margen: "+margenNuevo+" del id: "+id);
    // console.log("Nuevo precio: "+precioNuevo);

    //Si hay precio nuevo se entiende que hay un cambio por lo que activamos el boton de guardar cambios
    $('#btnGuardarCambios').prop('disabled', false);

}   // Fin de la funcion calculaPrecioNuevoConMargen



//Funcion para pintar la tabla de productos con los datos de la hoja de trabajo
function  pintaHojaTrabajoPVta(idProveedor, bodegas, uOperativas, fecha) {

    var tamUOperativas = uOperativas.split(',').length;
    $.ajax({
        url: 'services/mainService.php',
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'getPreciosOficina',
            controller: "GmcvPrecio",
            args: {
                'proveedores': idProveedor,
                'bodegas': bodegas,
                'uOperativas': uOperativas,
                'fecha': fecha
            }
        },
        success: function(response) {
            // console.log("Respuesta de la hoja de trabajo");
            // console.log("La variable es de tipo: " + typeof response);
            console.log(response);

            // Parseamos la cadena JSON para convertirla en un array de objetos
            // var response = JSON.parse(response);
            var data = response.productos;
            var columnasAntesCP = response.descuentosAntesCP;
            var columnasDespuesCP = response.descuentosDespCP;
            //Creamos variables para los totales
            let subtotalBruto = 0;
            let descuentoTotal = 0;
            let subtotalNeto = 0;
            let totalIVA = 0;
            let totalIEPS = 0;
            let totalTotal = 0;
            let totalRechazoIngreso = 0;

            //Si tiene datos la respuesta, entonces pintamos una tabla nueva con los datos en div divTablaProductos
            if (response.productos) {
                var tabla =
                    '<div id="infoHojaPVta"></div><table id="tablaProductosPVta" class="table table-striped table-bordered  tablaPequena" style="width:100%">';
                tabla += '<thead>';
                tabla += '<tr>';
                tabla += '<th><small>Producto</small></th>';
                tabla += '<th><small>Unidad<br>Mínima<br>de Venta</small></th>';
                tabla += '<th><small>Unidad<br>de<br>Venta</small></th>';
                tabla += '<th><small>UMV<br>Incluidas</small></th>';
                tabla += '<th><small>Precio<br>Lista</small></th>';

                //Recorrer el array de descuentos antes de CP
                $.each(columnasAntesCP, function(index, columna) {
                    tabla += '<th><small>' + columna.nombre.replace(/ /g, '<br>') + '</small></th>';
                });

                tabla += '<th><small>Costo<br>Pactado</small></th>';
                
                // Recorrer el array de descuentos despues de CP
                $.each(columnasDespuesCP, function(index, columna) {
                    tabla += '<th><small>' + columna.nombre.replace(/ /g, '<br>') + '</small></th>';
                });
                
                tabla += '<th><small>Costo<br>Ingreso<br>Bruto</small></th>';
                tabla += '<th><small>Costo<br>Ingreso<br>Neto</small></th>';
                //No tiene sentido mostrar el costo por unidad de venta si se seleccionaron varias bodegas o uOperativas
                if (tamUOperativas <= 1) {
                    // console.log("Solo hay una uOperativa");
                    tabla += '<th><small>Costo X<br>Unidad<br>de Venta</small></th>';
                    tabla += '<th><small>Margen<br>Actual</small></th>';
                }
                tabla += '<th><small>Margen<br>Nuevo<br>&nbsp; (<b>%</b>)<button class="btn btn-sm btn-primary ml-lg-2 btn-small" id="margenCopy"><i class="feather-12" data-feather="copy"></i></button></small></th>';
                tabla += '<th><small>Precio<br>Nuevo</small></th>';
                //Tampoco tiene sentido mostrar la diferencia porcentual si se seleccionaron varias bodegas o uOperativas
                if (tamUOperativas <= 1) {
                    tabla += '<th><small>Diff<br>&nbsp; %</small></th>';
                    tabla += '<th><small>Fecha<br><small></th>';
                }
                tabla += '</tr>';
                tabla += '</thead>';
                tabla += '<tbody>';
                //Recorremos el array de productos
                $.each(data, function(index, producto) {
                    tabla += '<tr>';
                    tabla += '<td id="nombreProducto-'+producto.id_pm+'">' + producto.nombre + '</td>';
                    tabla += '<td>' + producto.unidad_min_vta + '</td>';
                    tabla += '<td>' + producto.nombreUnidadVta + '</td>';
                    tabla += '<td class="dt-right">' + producto.unidadVta + '</td>';
                    tabla += '<td class="dt-right" iva="'+producto.iva+'" ieps="'+producto.ieps+'" iepsxl="'+producto.iepsxl_individual+'" id="precioLista-' + producto.id_pm +'" valorReal="' + (producto.pl_individual > 0.01 ? parseFloat(producto.pl_individual).toFixed(2) : 0.01) + '">' + (producto.pl_individual > 0.01 ? parseFloat(producto.pl_individual).toFixed(2) : 0.01) + '</td>';
                    //Array de descuentos antes de CP, recorremos las columnas y buscamos el descuento que corresponde a la columna
                    $.each(columnasAntesCP, function(index, columna) {
                        let descuento = 0;
                            $.each(producto.descuentosAntesCP, function(index, desc) {if (desc.id_descuento == columna.id_descuento) {    descuento = desc.tasa;}
                        });
                        tabla +='<td style="text-align: center;"><button class="btn btn-sm btn-primary btnDescuentoAntes btnFilaDescuentoAntes-' +    producto.id_pm + '" interruptor="1" value="' + descuento +    '" id="descuento-' + columna.id_descuento + '-' + producto.id_pm + '" style="padding: 3px 5px;">' + parseFloat(descuento).toFixed(2) + ' %</button></td>';
                    });
                    //Costo pactado
                    producto.costoPactado = producto.costoPactado > 0.01 ? parseFloat(producto.costoPactado).toFixed(2) : 0.01;
                    tabla += '<td class="dt-right" id="costoPactado-' + producto.id_pm +'" valorReal="' + producto.costoPactado + '">' + producto.costoPactado + '</td>';
                    //Array de descuentos despues de CP, recorremos las columnas y buscamos el descuento que corresponde a la columna
                    $.each(columnasDespuesCP, function(index, columna) {
                        let descuento = 0;
                        $.each(producto.descuentosDespCP, function(index, desc) {if (desc.id_descuento == columna.id_descuento) {    descuento = desc.tasa;}
                        });
                        tabla +='<td style="text-align: center;" ><button class="btn btn-sm btn-primary btnDescuentoDesp btnFilaDescuentoDesp-' +    producto.id_pm + '" interruptor="1" value="' + descuento +    '" id="descuento-' + columna.id_descuento + '-' + producto.id_pm + '" style="padding: 3px 5px;">' + parseFloat(descuento).toFixed(2) + ' %</button></td>';
                    });
                    //Coston ingreso bruto y neto
                    producto.costoIngresoBruto = producto.costoIngresoBruto > 0.01 ? parseFloat(producto.costoIngresoBruto).toFixed(2) : 0.01;
                    producto.costoIngresoNeto = producto.costoIngresoNeto > 0.01 ? parseFloat(producto.costoIngresoNeto).toFixed(2) : 0.01;
                    tabla += '<td class="dt-right" id="costoIngresoBruto-' + producto.id_pm +'" valorReal="' + producto.costoIngresoBruto + '">' + producto.costoIngresoBruto + '</td>';
                    tabla += '<td class="dt-right" id="costoIngresoNeto-' + producto.id_pm +'" valorReal="' + producto.costoIngresoNeto + '">' + producto.costoIngresoNeto + '</td>';

                    //Si tenemos seleccionadas varias bodegas o uOperativas, entonces no mostramos el costo por unidad de venta por que los datos pueden variar con respescto a la unidad de venta
                    if (tamUOperativas <= 1) {
                        //Costo por unidad de venta
                        tabla += '<td class="dt-right" id="costoUnidadVta-' + producto.id_pm +'" valorReal="' + producto.costoUnidadVta + '">' + parseFloat(producto.costoUnidadVta).toFixed(2) + '</td>';
                        //Margen actual
                        tabla += '<td class="dt-right" id="margenActual-' + producto.id_pm +'" valorReal="' + producto.margenActual + '">' + parseFloat(producto.margenActual).toFixed(2) + '</td>';    
                    }
                    //Margen nuevo
                    tabla += '<td id="celdaMargen-'+producto.id_pm+'" class="dt-right"><input class="inputMargen" valorAnt="'+parseFloat(producto.margenNuevo).toFixed(2)+'" id="margenNuevo-' + producto.id_pm + '" valorReal="' + producto.margenNuevo + '" type="number" step="0.01" value="' + parseFloat(producto.margenNuevo).toFixed(2) + '" style="width: 80px; text-align: right;"></td>';
                    //Precio nuevo
                    producto.costoUnidadVta = producto.costoUnidadVta > 0.01 ? parseFloat(producto.costoUnidadVta).toFixed(2) : 0.01;
                    if (tamUOperativas > 1) {
                        tabla += '<td class="dt-right precioNuevoTxt" id="precioNuevo-' + producto.id_pm +'" valorReal="' + producto.costoUnidadVta + '">' + producto.costoUnidadVta + '</td>';
                    } else {
                        tabla += '<td class="dt-right precioNuevoTxt" id="precioNuevo-' + producto.id_pm +'" valorReal="' + producto.costoUnidadVta + '">' + producto.costoUnidadVta + '</td>';
                    }
                    //Diferencia porcentual no tiene sentido mostrarla si se seleccionaron varias bodegas o uOperativas
                    if (tamUOperativas <= 1) {
                        //Diferencia porcentual
                        var diffPorcentaje = parseFloat(producto.margenActual) - parseFloat(producto.margenNuevo);
                        // console.log("Diferencia porcentual: "+diffPorcentaje);
                        tabla += '<td class="dt-right" id="diffPorcentaje-' + producto.id_pm +'" valorReal="' +  diffPorcentaje + '">' + parseFloat(diffPorcentaje).toFixed(2) + '</td>';
                        //Fecha si en null escribimos --/--/----
                        var fecha = producto.fecha ? producto.fecha : '--/--/--  ';
                        tabla += '<td class="dt-right">' + fecha + '</td>';
                    }
                    
                    tabla += '</tr>';
                });
                tabla += '</tbody>';
                tabla += '</table> <div class="mt-4 row align-items-center" id="divTotalesFactura">';
                //Agregamos un div para un boton de guardar cambios
                tabla += '<div class="text-center"><button class="btn btn-success btn-lg ml-3" disabled id="btnGuardarCambios"><i class="feather-16" data-feather="save"></i> Guardar cambios</button></div>';

                $('#divTablaProductos').html(tabla);
                //Alamcenamos los datos del respose en un objeto de sesión sessionStorage
                sessionStorage.setItem('productos', JSON.stringify(response.productos));
                sessionStorage.setItem('descuentosAntesCP', JSON.stringify(response.descuentosAntesCP));
                sessionStorage.setItem('descuentosDespCP', JSON.stringify(response.descuentosDespCP));
                //almacenamoas una banderita para que se active el boton de guardar
                sessionStorage.setItem('banderaGuardar', 0);

                //Si solo hay una uOperativa, entonces se puede exportar a excel
                // if(tamUOperativas > 1){
                //     $('#btnExportarExcel').prop('disabled', true);
                // } else {
                //     $('#btnExportarExcel').prop('disabled', false);
                // }
                //Actualizamos los iconos de feather
                //Aqui verificamos si response.mensaje tiene algo
                if(response.message){
                    messageAlert(response.message, response.statusMessage,true);
                }


                feather.replace();

            } else {
                $('#divTablaProductos').html('<div class="alert alert-warning">No hay productos para mostrar</div>');
            }
        },
        error: function(error) {
            // console.log(error);
            alert('Error al cargar la hoja de trabajo');
        }
    }); //Fin ajax pintaHojaTrabajoCosteo

} //Fin de la función pintaHojaTrabajoCosteo






</script>

<?php
$rutaArchivo = file_exists($ruta."sys/hf/pie_v3.php") ? $ruta."sys/hf/pie_v3.php" : "../../../sys/hf/pie_v3.php";
include $rutaArchivo;
?>