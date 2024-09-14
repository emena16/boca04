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

.feather-16 {
    width: 12px;
    height: 12px;
}

#tablaProductosFacturaAlmacen+#tablaAlineadaDerecha {
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

/* Hacemos un 10% mas pequeño el boton de la clase btn-xs */
.btn-xs {
    padding: 0.25rem 0.5rem;
    font-size: 0.7rem;
    line-height: 1.5;
    border-radius: 0.2rem;
}

.groupDescuentoGlobal {
    margin-bottom: 10px;
}

.inputDesctGlobal {
    width: 105px;
    margin-right: 10px;
}
.button-trash {
    display: inline-block;
}


td.text-15 {
    font-size: 15px;
}

td.text-18 {
    font-size: 18px;
}

td.text-20 {
    font-size: 20px;
}
</style>


<div class="page-header layout-top-spacing title-header">
    <div class="pge-title" style="margin-left: 3.5%;">
        <h3>&nbsp; Validación de costos</h3>
    </div>
</div>


<div class="card card-principal">
    <div class="card-body">
        <div class="row">
            <div class="col-sm-12 mb-4">
                <h5>Listado de Diferencias de Costos Pactados</h5>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <label for="fechaInicio">Fecha Inicial: </label>
                <input type="date" class="form-control" id="fechaInicio" name="fechaInicio"
                    value="<?= date('Y-m-d', strtotime('-1 month')) ?>">
            </div>

            <div class="col-md-3">
                <label for="fechaFin">Fecha Final: </label>
                <input type="date" class="form-control" id="fechaFin" name="fechaFin"
                    value="<?=date('Y-m-d')?>">
            </div>

            <div class="col-md-4">
                <label for="idProveedor">Proveedor: </label>
                <select class="form-control" id="idProveedor" name="idProveedor">
                    <option value="" selected disabled>Seleccione un proveedor</option>
                    <?php
                        $proveedores = Compra::getProveedoresConOrdenesPorValidarCostos();
                        foreach ($proveedores as $proveedor) {
                            echo "<option value='".$proveedor['id']."'>".$proveedor['nombre']."</option>";
                        }
                    ?>
                </select>
            </div>

            <div class="col-md-2">
                <label for="descAjuste">Descuento Ajuste: </label>
                <select class="form-control" id="descAjuste" name="descAjuste">
                    <option value="" selected disabled>Seleccione</option>
                    <option value="1">Contra Costo Pactado</option>
                    <option value="2">Contra Costo Ingreso</option>
                    <option value="3">Contra Precio de Lista</option>
                </select>
            </div>

            <div class="col-md-2">
                <button class="btn btn-lg btn-primary" id="btnBuscar" style="margin-top: 32px;">
                    <i class="feather-16" data-feather="search"></i>
                    Buscar
                </button>
            </div>
        </div><!-- Fin row de los criterios de busqueda -->

        <div class="row">
            <div class="col-md-12">
                <div class="page-header layout-top-spacing title-header mt-lg-4">
                    <div class="pge-title"> <br><br><br>
                        <h5 id="tituloTabla"></h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div id="divTablaEntradas" class="col-md-12 col-lg-12">
                <!-- Aqui vamos a pintar el datatable con las facturas ordenas por compra -->
            </div>
        </div>

    </div> <!-- fin card-body -->
</div> <!-- fin card-principal -->

<!-- Agregamos la libreria de sweetalert2 -->
<link rel="stylesheet" type="text/css" href="../../../sys/bocampana_vista/plugins/sweetalerts/sweetalert2.all.min.css">
<script src="../../../sys/bocampana_vista/plugins/sweetalerts/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function() {

    //Creamos un evento para el boton de buscar
    $(document).on('click', '#btnBuscar', function() {
        //Obtenemos los valores de los campos de búsqueda
        var fechaInicio = $('#fechaInicio').val();
        var fechaFin = $('#fechaFin').val();
        var idProveedor = $('#idProveedor').val();
        var descAjuste = $('#descAjuste').val();
        //Validamos que se haya seleccionado un proveedor
        if (idProveedor == null || idProveedor == '') {
            alert('Debe seleccionar un proveedor');
            return;
        }
        //Validamos que se haya seleccionado un descuento ajuste
        if (descAjuste == null || descAjuste == '') {
            alert('Debe seleccionar un descuento ajuste');
            return;
        }
        //Llamamos a la funcion que pinta las facturas
        pintaFacturas(fechaInicio, fechaFin, idProveedor, descAjuste);

    });

    //Creamos un evento al pulsar el boton de costear una factura
    $(document).on('click', '.btnCosteaFactura', function() {
        //Obtenemos el id de la factura 
        var idFactura = $(this).attr('id');
        var descuentoAjuste = $('#descAjuste').val();
        //Llamamos a la funcion que costea la factura
        pintaHojaTrabajoCosteo(idFactura, descuentoAjuste);
    });

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
        let precioFinal = calculaPrecioConDescuentos(idProducto, 'Antes');
        //Aplicamos los todos los descuentos despues de CP
        precioFinal = precioFinal * (1 - sumaDescuentosDesp / 100);
        let resultado = revertirDescuentosYAplicarSeleccionados(precioFinal, descuentos,descuentosSeleccionados);
        console.log("Valor original antes de todos los descuentos: $" + resultado.precioOriginal);
        console.log("Precio después de aplicar solo los descuentos seleccionados: $" + resultado.precioConDescuentosSeleccionados);

        //Actualizamos el valor de la celda de costo pactado
        $('#costoIngreso-' + idProducto).text(parseFloat(resultado.precioConDescuentosSeleccionados).toFixed(2));
        $('#costoIngreso-' + idProducto).attr('valorReal', parseFloat(resultado.precioConDescuentosSeleccionados));
    });

    //Creamos un evento para activarlo al pulsar un boton descuento de la clase btnDescuentoAntes
    $(document).on('click', '.btnDescuentoAntes', function() {
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
        var porcentajesActivos = 0;
        $('.btnFilaDescuentoAntes-' + idProducto).each(function(index, boton) {
            descuentos.push(parseFloat($(boton).attr('value')));
            if ($(boton).attr('interruptor') == 1) {
                descuentosSeleccionados.push(parseFloat($(boton).attr('value')));
                porcentajesActivos += parseFloat($(boton).attr('value'));
            } else {
                porcentajesInactivos += parseFloat($(boton).attr('value'));
            }
        });
        //Obtenemos el costo facturado del producto para quitarle el descuento
        let precioFinal = calculaPrecioConDescuentos(idProducto, 'Antes');
        let resultado = revertirDescuentosYAplicarSeleccionados(precioFinal, descuentos,descuentosSeleccionados);
        //Actualizamos el valor de la celda de costo pactado
        $('#costoPactado-' + idProducto).text(parseFloat(resultado.precioConDescuentosSeleccionados).toFixed(2));
        $('#costoPactado-' + idProducto).attr('valorReal', parseFloat(resultado.precioConDescuentosSeleccionados));
        //Restamos a diffPorcentaje el porcentaje de los descuentos inactivos
        let diffPorcentaje = parseFloat($('#diffPorcentaje-' + idProducto).attr('valorReal'));
        // console.log("Porcentaje antes de sufir cambios: " + diffPorcentaje);
        diffPorcentaje -=  porcentajesActivos - porcentajesInactivos;

        // console.log("Porcentaje despues de sufir cambios: " + diffPorcentaje);
        $('#diffPorcentaje-' + idProducto).text(diffPorcentaje.toFixed(2));
        $('#diffPorcentaje-' + idProducto).attr('valorReal', diffPorcentaje);
    });

    //Creamos un evento para cuando pulsan el boton de cancelar ajuste de factura btnCancelarValidacionFactura
    $(document).on('click', '#btnCancelarValidacionFactura, #btnRegresarFacturas', function() {
        //Volvemos a pintar la tabla de facturas
        $('#divTablaEntradas').html('');
        $('#divTotalesFactura').html('');
        //Volvemos a pintar la tabla de facturas
        pintaFacturas($('#fechaInicio').val(), $('#fechaFin').val(), $('#idProveedor').val(), $('#descAjuste').val());
    }); // fin evento btnCancelarValidacionFactura

    //Creamos un evento al pulsar el boton agregarDescuentoGlobal
    $(document).on('click', '#agregarDescuentoGlobal', function() {

        //Contamos cuantos descuentos globales hay
        var numDescuentos = $('.groupDescuentoGlobal').length;
        //obtnemos el indice del ultimo descuento global en caso de existir para asignarle un nuevo id
        var id = 0;
        if (numDescuentos > 0) {
            id = parseInt($('.groupDescuentoGlobal').last().attr('id').split('groupDesctGlobal')[1]) + 1;
        }
        //Agregamos un par de inputs dentro de un div.row para agregar un nuevo descuento global
        var div = '<div class="mt-2 groupDescuentoGlobal" id="groupDesctGlobal-' + id + '">';
        div += '<div class="row mb-1">';
        div += '<div class=""><input type="number" min="0" class="ml-4 inputDesctGlobal" id="descuentoGlobal-' + id + '" placeholder="Descuento Global" value="0.00"></div>'; //Cerramos el el div col-auto
        div += '<div class=""><button class="btn btn-xs btn-danger btnDelateGroup" id="btnDelateGroup-' + id + '" ><i class="feather-16" data-feather="trash"></i></button></div>'; //Cerramos el el div col-2
        div += '</div>';//Cierra el div row del descuento
        div += '<div class="row mb-3">';
        div += '<div class="col"><textarea row="2" class="commentDesctGlobal" id="comentarioDescuentoGlobal-' + id + '" placeholder="Comentario"></textarea></div>'; //Cerramos el el div col-auto
        div += '</div>';//Cierra el div row del comentario
        div += '</div>'; // Cierre del div row principal

        $('#descuentosGlobales').append(div);

        feather.replace();

    });


    //Creamos un evento al pulsar el boton de eliminar un grupo de descuento global
    $(document).on('click', '.btnDelateGroup', function() {
        //Obtenemos el id del grupo de descuento global
        var id = $(this).attr('id').split('-')[1];
        //Eliminamos el grupo de descuento global
        $('#groupDesctGlobal-' + id).remove();
        recalculaAjustes();

    });

    //Creamos un evento para cuando se hace un cambio en algun input de descuento global inputDesctGlobal
    $(document).on('change', '.inputDesctGlobal', function() {
        //Obtenemos el id del input
        var id = $(this).attr('id').split('-')[1];
        //Obtenemos el valor del input
        var valor = parseFloat($(this).val());  
        //Validamos que el valor sea un numero
        if (isNaN(valor)) {
            alert('El valor ingresado no es un número');
            $(this).val('0.00');
            return;
        }
        //Validamos que el valor sea mayor o igual a 0
        if (valor < 0) {
            alert('El valor ingresado no puede ser menor a 0');
            $(this).val('0.00');
            return;
        }

        recalculaAjustes();

    });


    //Creamos un evento para cuando se modufica el inputDescuentoAjuste de un producto
    $(document).on('change', '.inputDescuentoAjuste', function() {
        //Obtenemos el id del producto
        var idProducto = $(this).attr('id').split('-')[1];
        //Obtenemos el valor del input
        var valor = parseFloat($(this).val());
        //Validamos que el valor sea un numero
        if (isNaN(valor)) {
            alert('El valor ingresado no es un número');
            $(this).val('0.00');
            return;
        }
        //Validamos que el valor sea mayor o igual a 0
        if (valor < 0) {
            alert('El valor ingresado no puede ser menor a 0');
            $(this).val('0.00');
            return;
        }
        // IMPORTANTE: VERIFICAR ESTE DATO
        // //Obtenemos el valor real del input
        // var valorReal = parseFloat($(this).attr('valorReal'));
        // //Obtenemos la diferencia entre el valor real y el valor actual
        // var diff = valor - valorReal;
        // //Obtenemos el valor de la celda de costo ingreso
        // var costoIngreso = parseFloat($('#costoIngreso-' + idProducto).attr('valorReal'));
        // //Sumamos la diferencia al costo de ingreso
        // costoIngreso += diff;
        // //Actualizamos el valor de la celda de costo ingreso
        // $('#costoIngreso-' + idProducto).text(costoIngreso.toFixed(2));
        // $('#costoIngreso-' + idProducto).attr('valorReal', costoIngreso);
        //Recalculamos los ajustes

        //Actualizamos el total de descuentos de ajuste en funcion al tipo de ajuste
        var tipoAjuste = sessionStorage.getItem('tipoAjuste');
        var totalDescuento = 0;
        var subTotalIngreso = 0;
        var subTotalFact = 0;
        var costoPactado = parseFloat($('#costoPactado-' + idProducto).attr('valorReal'));
        var costoFactura = parseFloat($('#costoFactura-' + idProducto).attr('valorReal'));
        var costoIngreso = parseFloat($('#costoIngreso-' + idProducto).attr('valorReal'));
        var unidadesAceptadas = parseFloat($('#unidadesAceptadas-' + idProducto).attr('valorReal'));
        var unidadesRechazadas = parseFloat($('#unidadesRechazadas-' + idProducto).attr('valorReal'));
        var ajusteDescuentoInput = parseFloat($(this).val());
        var totalAceptado = 0;

        // switch (tipoAjuste) {
        //     case 'CP':
        //         /*
        //             //Calculamos el ajuste por descuento en moneda teniendo en cuenta que la diferencia porcentual es menor a 0 por que el costo de factura es menor al costo pactado
        //             $row['ajusteDescuento'] = $row['diffMoneda'] * $row['cantidad_aceptada'];
        //             // Invierte el signo del ajuste por descuento
        //             $row['ajusteDescuento'] = -$row['ajusteDescuento'];
        //             //Calculamos el sub total ingreso
        //             $row['subTotalFact'] = $row['costoFactura'] * ($row['cantidad_aceptada'] + $row['cantidad_rechazada']);
        //             $row['subTotalIngreso'] = $row['subTotalFact'] - $row['ajusteDescuento'] - $row['descuentoRechazo'];
        //         */
        //         // //Obtnemos la diferencia entre el costo facturado y el costo pactado
        //         // subTotalFact = costoFactura * (unidadesAceptadas + unidadesRechazadas);
        //         // subtotalIngreso = subTotalFact - ajusteDescuentoInput - totalRechazado;
        //         // $('#subTotalIngreso-' + idProducto).text(toMoney(ajusteDescuento.toFixed(2)));
        //         // $('#subTotalIngreso-' + idProducto).attr('valorReal', ajusteDescuento);

        //         break;
        //     case 'CI':
        //         //Contra Costo Ingreso
                
        //         break;
        //     case 'PL':
        //         //Contra Precio de Lista
        //         break;
        // }

        //Obtnemos la diferencia entre el costo facturado y el costo pactado
        subTotalFact = costoFactura * (unidadesAceptadas + unidadesRechazadas);
        subtotalIngreso = subTotalFact - ajusteDescuentoInput - (costoFactura * unidadesRechazadas);
        $('#subTotalIngreso-' + idProducto).text(toMoney(subtotalIngreso.toFixed(2)));
        $('#subTotalIngreso-' + idProducto).attr('valorReal', subtotalIngreso);

        suma = sumaInputDescuentosAjuste();
        //Actualizamos el descuentos correspondiente de acuerdo al atributo "ajuste" del input
        $('#totalAjusteDescuento').text(toMoney(suma.toFixed(2)));
        $('#totalAjusteDescuento').attr('valorReal', suma);
        //Actualizamos el subtotal ingreso de la fila del producto
        // var subtotalIngreso = (parseFloat($('#costoIngreso-' + idProducto).attr('valorReal')) * parseFloat($('#unidadesAceptadas-' + idProducto).attr('valorReal'))) - valor;
        // $('#subTotalIngreso-' + idProducto).attr('valorReal', subtotalIngreso);
        // $('#subTotalIngreso-' + idProducto).text(toMoney(subtotalIngreso.toFixed(2)));




        //Recalculamos los ajustes
        recalculaAjustes();

    });

    feather.replace();
}); // fin document.ready

//Creamos una funcion que recalcule los ajutestes
function recalculaAjustes(){
    //Al cambiar un descuento recalculamos el total de descuentos
    var suma = 0;
    //Recorremos la clase inputDesctGlobal para sumar los valores de los descuentos globales en caso de haber
    $('.inputDesctGlobal').each(function(index, input) {
        suma += parseFloat($(input).val());
    });

    //Sumamos los valores reales de los demas ajustes CP, CI, PL y ajusteRechazo a traves del atributo "valorReal"
    suma += parseFloat($('#totalAjusteDescuento').attr('valorReal'));
    suma += parseFloat($('#totalRechazosIngreso').attr('valorReal'));

    // console.log("Modificacion en un inpur, Sumamos de los descuentos: " + suma);
    //Escribimos el total en total descuento en #totalAjusteIngreso y su atributo valorReal
    $('#totalAjusteIngreso').text(toMoney(suma.toFixed(2)));
    $('#totalAjusteIngreso').attr('valorReal', suma);

    //Actualizamos el subtotal neto ingreso
    subtotalBrutoIngreso = parseFloat($('#subtotalBrutoIngreso').attr('valorReal'));
    $('#subtotalNetoIngreso').text(toMoney((subtotalBrutoIngreso - suma).toFixed(2)));
    $('#subtotalNetoIngreso').attr('valorReal', (subtotalBrutoIngreso - suma));
    //Actualizamos el total de la factura
    actualizaTotalIngreso();
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



//Creamos una funcion que va a pintar la hoja de trabajo con los productos de la factura para costear
function pintaHojaTrabajoCosteo(idFactura, descuentoAjuste) {
    $.ajax({
        url: 'services/mainService.php',
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'getProductosByFacturaValidacionCosto',
            controller: "CompraFacturaProd",
            args: {
                'id_factura': idFactura,
                'ajusteDescuento': descuentoAjuste
            }
        },
        success: function(response) {
            
            // console.log(response);
            // Parseamos la cadena JSON para convertirla en un array de objetos
            // var response = JSON.parse(response);
            var data = response.productos;
            var factura = response.factura;
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
            let facturaIVA = 0;
            let facturaIEPS = 0;
            let facturaSubtotal = 0;


            //Si tiene datos la respuesta, entonces pintamos una tabla nueva con los datos en div divTablaEntradas
            if (data.length > 0) {
                var tabla =
                    '<div id="infoFacturaActual"></div><table id="tablaProductosFacturaAlmacen" class="table table-striped table-bordered table-hover tablaPequena" style="width:100%">';
                tabla += '<thead>';
                tabla += '<tr>';
                tabla += '<th><small>Producto</small></th>';
                tabla += '<th><small>Precio de<br>Lista</small></th>';
                //Recorrer el array de descuentos antes de CP
                $.each(columnasAntesCP, function(index, columna) {
                    tabla += '<th><small>' + columna.nombre.replace(/ /g, '<br>') + '</small></th>';
                });

                tabla += '<th><small>Costo<br>Pactado</small></th>';
                tabla += '<th><small>Costo<br>Factura</small></th>';
                tabla += '<th><small>Diff. %</small></th>';
                // Recorrer el array de descuentos despues de CP
                $.each(columnasDespuesCP, function(index, columna) {
                    tabla += '<th><small>' + columna.nombre.replace(/ /g, '<br>') + '</small></th>';
                });
                tabla += '<th><small>Costo<br>Ingreso</small></th>';
                tabla += '<th><small>Unidades<br>Aceptadas</small></th>';
                tabla += '<th><small>Unidades<br>Rechazadas</small></th>';
                tabla += '<th><small>Subtotal<br>Factura</small></th>';
                tabla += '<th><small>Descuento<br>Ajuste vs.<br><b>' + response.nombreDescuentoAjuste +
                    '</b> </small></th>';
                tabla += '<th><small>Descuento<br>Rechazo</small></th>';
                tabla += '<th><small>Subtotal<br>Ingreso</small></th>';
                tabla += '</tr>';
                tabla += '</thead>';
                tabla += '<tbody>';
                //Recorremos el array de productos
                $.each(data, function(index, producto) {
                    //Creamos una variable para aplicar ieps e iva
                    let iepsXL = producto.totalIEPSxL * parseFloat(producto.cantidad_aceptada);
                    let ieps = (parseFloat(producto.costoPactado) * (1 + parseFloat(producto.ieps)) - parseFloat(producto.costoPactado)) + iepsXL;
                    let iva = (parseFloat(producto.costoPactado) + ieps) * (1 + parseFloat(producto.iva)) - (parseFloat(producto.costoPactado) + ieps);
                    let descuento = parseFloat(producto.costoPactado) * parseFloat(producto.cantidad_rechazada);

                    let iepsXLFactura = producto.totalIEPSxL * parseFloat(producto.cantidad_facturada);
                    let iepsFactura = (parseFloat(producto.costoFactura) * (1 + parseFloat(producto.ieps)) - parseFloat(producto.costoFactura)) + iepsXLFactura;
                    let ivaFactura = (parseFloat(producto.costoFactura) + iepsFactura) * (1 + parseFloat(producto.iva)) - (parseFloat(producto.costoFactura) + iepsFactura);
                    //Sumamos los totales factura
                    facturaIVA += ivaFactura * parseFloat(producto.cantidad_facturada);
                    facturaIEPS += iepsFactura * parseFloat(producto.cantidad_facturada);
                    facturaSubtotal += parseFloat(producto.costoFactura) * parseFloat(producto.cantidad_facturada);



                    subtotalBruto += parseFloat(producto.costoPactado) * parseFloat(producto.cantidad_facturada);
                    descuentoTotal += descuento;
                    subtotalNeto += parseFloat(producto.costoPactado) * parseFloat(producto.cantidad_aceptada);
                    totalIEPS += ieps * parseFloat(producto.cantidad_aceptada);
                    totalIVA += iva * parseFloat(producto.cantidad_aceptada);
                    totalRechazoIngreso += parseFloat(producto.descuentoRechazo);

                    tabla += '<tr>';
                    tabla += '<td>' + producto.comercial + '</td>';
                    tabla += '<td class="dt-right" id="precioLista-' + producto.id_prod +'" valorReal="' + producto.precioListaCatalogo + '">' + parseFloat(producto.precioListaCatalogo).toFixed(2) + '</td>';
                    //Array de descuentos antes de CP, recorremos las columnas y buscamos el descuento que corresponde a la columna
                    $.each(columnasAntesCP, function(index, columna) {
                        let descuento = 0;
                            $.each(producto.descuentosAntesCP, function(index, desc) {if (desc.id_descuento == columna.id_descuento) {    descuento = desc.tasa;}
                        });
                        tabla +='<td style="text-align: center;"><button class="btn btn-sm btn-primary btnDescuentoAntes btnFilaDescuentoAntes-' +    producto.id_prod + '" interruptor="1" value="' + descuento +    '" id="descuento-' + columna.id_descuento + '-' + producto.id_prod + '" style="padding: 3px 5px;">' + parseFloat(descuento).toFixed(2) + ' %</button></td>';
                    });
                    tabla += '<td class="dt-right" id="costoPactado-' + producto.id_prod +'" valorReal="' + producto.costoPactado + '">' + parseFloat(producto.costoPactado).toFixed(2) + '</td>';
                    tabla += '<td class="dt-right" id="costoFactura-' + producto.id_prod +'" valorReal="' + producto.costoFactura + '">' + parseFloat(producto.costoFactura).toFixed(2) + '</td>';
                    // tabla += '<td>'+producto.diffPorcentaje+'</td>'; //Nuevo campo en la DB
                    tabla += '<td class="dt-right" id="diffPorcentaje-' + producto.id_prod +'" valorReal="' + producto.diffPorcentaje + '">' + parseFloat(producto.diffPorcentaje).toFixed(2) + '</td>';
                    //Array de descuentos despues de CP, recorremos las columnas y buscamos el descuento que corresponde a la columna
                    $.each(columnasDespuesCP, function(index, columna) {
                        let descuento = 0;
                        $.each(producto.descuentosDespCP, function(index, desc) {if (desc.id_descuento == columna.id_descuento) {    descuento = desc.tasa;}
                        });
                        tabla +='<td style="text-align: center;" ><button class="btn btn-sm btn-primary btnDescuentoDesp btnFilaDescuentoDesp-' +    producto.id_prod + '" interruptor="1" value="' + descuento +    '" id="descuento-' + columna.id_descuento + '-' + producto.id_prod + '" style="padding: 3px 5px;">' + parseFloat(descuento).toFixed(2) + ' %</button></td>';
                    });
                    tabla += '<td class="dt-right" id="costoIngreso-' + producto.id_prod +'" valorReal="' + producto.costoIngreso + '">' + parseFloat(producto.costoIngreso).toFixed(2) + '</td>';
                    tabla += '<td class="dt-right" id="unidadesAceptadas-' + producto.id_prod +'" valorReal="' + producto.cantidad_aceptada + '">' + parseFloat(producto.cantidad_aceptada).toFixed(2) + '</td>';
                    tabla += '<td class="dt-right" id="unidadesRechazadas-' + producto.id_prod +'" valorReal="' + producto.cantidad_rechazada + '">' + parseFloat(producto.cantidad_rechazada).toFixed(2) + '</td>';
                    tabla += '<td class="dt-right" id="subTotalFact-' + producto.id_prod +'" valorReal="' + producto.subTotalFact + '">' + producto.subTotalFact.toFixed(2) + '</td>';
                    tabla += '<td class=""><input type="number" ajuste="'+response.siglaDescuentoAjuste+'" class="inputDescuentoAjuste" diffMoneda="' + producto.diffMoneda + '" id="descuentoAjuste-' + producto.id_prod + '" value="' +parseFloat(producto.ajusteDescuento).toFixed(2) +'" style="width: 105px; text-align: right;"></td>';
                    tabla += '<td class=""><input type="number" class="inputDescuentoRechazo" diffMoneda="' + producto.diffMoneda + '" id="descuentoRechazo-' + producto.id_prod + '" value="' +parseFloat(producto.descuentoRechazo).toFixed(2) +'" style="width: 105px; text-align: right;"></td>';
                    tabla += '<td class="dt-right" id="subTotalIngreso-' + producto.id_prod +'" valorReal="' + parseFloat(producto.subTotalIngreso) + '">' + toMoney(parseFloat(producto.subTotalIngreso).toFixed(2)) + '</td>';

                    tabla += '</tr>';
                });
                tabla += '</tbody>';
                tabla += '</table> <div class="mt-4" id="divTotalesFactura"></div>';

                $('#divTablaEntradas').html(tabla);
                
                //Antes de guardar la informacion de la factura en un sessionStorage, eliminamos la informacion del pasado
                sessionStorage.removeItem('factura');
                sessionStorage.removeItem('columnasAntesCP');
                sessionStorage.removeItem('columnasDespuesCP');
                sessionStorage.removeItem('productos');
                sessionStorage.removeItem('tipoAjuste');



                //Guardamos la informacion del response un sessionStorage
                sessionStorage.setItem('factura', JSON.stringify(factura));
                sessionStorage.setItem('columnasAntesCP', JSON.stringify(columnasAntesCP));
                sessionStorage.setItem('columnasDespuesCP', JSON.stringify(columnasDespuesCP));
                sessionStorage.setItem('productos', JSON.stringify(data));
                sessionStorage.setItem('tipoAjuste', response.siglaDescuentoAjuste);
                

                // // Inicializamos el datatable
                // $('#tablaProductosFacturaAlmacen').DataTable( {
                //     dom: 'frti',
                //     language: {
                //         "url": "js/spanish.js"
                //     },
                //     order: [],
                //     stripeClasses: [],
                //     //Quitamos las anotaciones de la tabla
                //     info: false,

                //     paging: false // Deshabilitamos la paginación
                // });
                factura.ajuste = sumaInputDescuentosAjuste();

                //Calculamos el ajuste de descuento y rechazo
                totalAjusteIngreso = parseFloat(factura.ajuste) + parseFloat(totalRechazoIngreso);
                //Leeemos que clase de ajuste estamos haciendo
                var tituloAjuste = response.siglaDescuentoAjuste == 'CP' ? 'Costo Pactado' : response.siglaDescuentoAjuste == 'CI' ? 'Costo Ingreso' : 'Precio Lista';

                $('#divTotalesFactura').html('<div class="row align-content-end"><div class="col-md-6 col-sm-12 col-lg-6">' +
                    '<button class="btn btn-success" id="btnProcessConfirmClose" type="button" disabled style="display: none;"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...</button>' +
                    '<button class="btn btn-primary ml-2 mt-2 mb-lg-4" id="btnGuardarValidacionFactura"><i style=" color: #f6fcfb;" data-feather="save"></i> Guardar Ajuste contra <b>' +
                    response.nombreDescuentoAjuste + '</b></button>' +
                    '<button class="btn btn-secondary ml-2 mt-2 mb-lg-4" id="btnCancelarValidacionFactura"><i style=" color: #f6fcfb;" data-feather="x"></i> Cancelar</button></div>' +
                    '<div class="col-md-6 col-sm-12 col-lg-6">' +
                    '<table class="table table-striped table-bordered tablaPequena">' +
                    '<tbody>' +
                    '<tr><th colspan="2">Totales Factura&nbsp;&nbsp;</th> <th colspan="2">Totales Factura Despues de Rechazos</th> <th colspan="2">&nbsp;&nbsp;Totales Ingreso</th></tr>' +
                    
                    '<tr>' +
                    '<td class="dt-right">Subtotal Bruto</td>' +
                    '<td class=""><label valorReal="" class="dt-right" style="width: 150px; display: block;" id="subtotalBrutoFactura1"></label></td>' +

                    '<td class="dt-right">Subtotal Bruto</td>' +
                    '<td class=""><label valorReal="" class="dt-right" style="width: 150px; display: block;" id="subtotalBrutoFactura"></label></td>' +

                    '<td class="dt-right">Subtotal Bruto</td>' +
                    '<td class=""><label valorReal="" class="dt-right" style="width: 150px; display: block;" id="subtotalBrutoIngreso"></label></td>' +
                    '</tr>' +

                    '<tr>' +
                    '<td class="dt-right">Descuento Total</td>' +
                    '<td class=""><label valorReal="" class="dt-right" style="width: 150px; display: block;" id="descuentoTotalFactura1"></label></td>' +

                    '<td class="dt-right">Descuento Total</td>' +
                    '<td class=""><label valorReal="" class="dt-right" style="width: 150px; display: block;" id="descuentoTotalFactura"></label></td>' +
                    
                    '<td class="dt-right">Total<br>Ajustes<br><br>Descuentos<br>Globales<br><button class="btn btn-xs btn-success" type="button" id="agregarDescuentoGlobal">Agregar</button></td>' + 
                    '<td>' +
                        '<table class="table table-striped table-bordered tablaPequena">' +
                            '<tr><td class="dt-right">'+tituloAjuste+'</td><td valorReal="'+factura.ajuste+'" id="totalAjusteDescuento">' + toMoney(factura.ajuste) + '</td></tr>' +
                            '<tr><td class="dt-right">Desct. Rechazos</td><td valorReal="' + totalRechazoIngreso + '" id="totalRechazosIngreso">' + toMoney(totalRechazoIngreso) + '</td></tr>' +
                            '<tr><td class="align-items-end" colspan="2"><div class="align-items-end" id="descuentosGlobales">  </div></td></tr>' +
                            '<tr><td class="dt-right"><h6>Total Descuento</h6></td><td><h6 valorReal="' + totalAjusteIngreso + '" id="totalAjusteIngreso">' + toMoney(totalAjusteIngreso) + '</h6></td></tr>' +
                        '</table>' +
                    '</td>' +
                    '</tr>' +

                    '<tr>' +
                    '<td class="dt-right">Subtotal Neto</td>' +
                    '<td class=""><label class="dt-right" valorReal="" style="width: 150px; display: block;" id="subtotalNetoFactura1"></label></td>' +

                    '<td class="dt-right">Subtotal Neto</td>' +
                    '<td class=""><label class="dt-right" valorReal="" style="width: 150px; display: block;" id="subtotalNetoFactura"></label></td>' +

                    '<td class="dt-right">Subtotal Neto</td>' +
                    '<td class=""><label class="dt-right" valorReal="" style="width: 150px; display: block;" id="subtotalNetoIngreso"></label></td>' +
                    '</tr>' +

                    '<tr>' +
                    '<td class="dt-right">IVA</td>' +
                    '<td class=""><label class="dt-right" valorReal="" style="width: 150px; display: block;" id="ivaFactura1"></label></td>' +

                    '<td class="dt-right">IVA</td>' +
                    '<td class=""><label class="dt-right" valorReal="" style="width: 150px; display: block;" id="ivaFactura"></label></td>' +

                    '<td class="dt-right">IVA</td>' +
                    '<td class=""><label class="dt-right" valorReal="" style="width: 150px; display: block;" id="ivaIngreso"></label></td>' +
                    '</tr>' +

                    '<tr>' +
                    '<td class="dt-right">IEPS</td>' +
                    '<td class=""><label class="dt-right" valorReal="" style="width: 150px; display: block;" id="iepsFactura1"></label></td>' +

                    '<td class="dt-right">IEPS</td>' +
                    '<td class=""><label class="dt-right" valorReal="" style="width: 150px; display: block;" id="iepsFactura"></label></td>' +

                    '<td class="dt-right">IEPS</td>' +
                    '<td class=""><label class="dt-right" valorReal="" style="width: 150px; display: block;" id="iepsIngreso"></label></td>' +
                    '</tr>' +

                    '<tr>' +
                    '<td class="dt-right">Total</td>' +
                    '<td class=""><label class="dt-right" valorReal="" style="width: 150px; display: block;" id="totalFactura1"></label></td>' +

                    '<td class="dt-right">Total</td>' +
                    '<td class=""><label class="dt-right" valorReal="" style="width: 150px; display: block;" id="totalFactura"></label></td>' +

                    '<td class="dt-right">Total a Pagar:</td>' +
                    '<td class=""><label class="dt-right" valorReal="" style="width: 150px; display: block;" id="totalIngreso"></label></td>' +
                    '</tr>' +

                    '</tbody>' +
                    '</table>' +
                    '</div>' +
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

                //Llenamos los de la factura por defecto
                $('#subtotalBrutoFactura1').text(formatter.format(facturaSubtotal));
                $('#descuentoTotalFactura1').text(formatter.format(0));
                $('#subtotalNetoFactura1').text(formatter.format(facturaSubtotal));
                $('#ivaFactura1').text(formatter.format(facturaIVA));
                $('#iepsFactura1').text(formatter.format(facturaIEPS));
                $('#totalFactura1').text(formatter.format(facturaSubtotal + facturaIVA + facturaIEPS));

                //De momento aplicamos lo mismo para los totales de ingreso ACTUALIZAR PARA MAS ADELANTE!!!!!!!!
                $('#subtotalBrutoIngreso').text(formatter.format(subtotalBruto));
                $('#subtotalBrutoIngreso').attr('valorReal', subtotalBruto);
                $('#descuentoTotalIngreso').text(formatter.format(descuentoTotal));
                $('#subtotalNetoIngreso').text(formatter.format(subtotalNeto));
                $('#subtotalNetoIngreso').attr('valorReal', subtotalNeto);
                $('#ivaIngreso').text(formatter.format(totalIVA));
                $('#ivaIngreso').attr('valorReal', totalIVA);
                $('#iepsIngreso').text(formatter.format(totalIEPS));
                $('#iepsIngreso').attr('valorReal', totalIEPS);
                $('#totalIngreso').text(formatter.format(subtotalNeto + totalIVA + totalIEPS));
                $('#totalIngreso').attr('valorReal', subtotalNeto + totalIVA + totalIEPS);


                //Actualizamos los valores reales de los labels
                $('#subtotalBrutoFactura').attr('valorReal', subtotalBruto);
                $('#descuentoTotalFactura').attr('valorReal', descuentoTotal);
                $('#subtotalNetoFactura').attr('valorReal', subtotalNeto);
                $('#ivaFactura').attr('valorReal', totalIVA);
                $('#iepsFactura').attr('valorReal', totalIEPS);
                $('#totalFactura').attr('valorReal', subtotalNeto + totalIVA + totalIEPS);

                //Agregamos en el div de titulo de la tabla un boton para regresar a la tabla de facturas
                $('#tituloTabla').html('<div class="d-flex justify-content-between align-items-center mb-4"><h5>Costeo de Productos de la Factura ' + idFactura + ' contra <b>' + response.nombreDescuentoAjuste +
                    '</b> </h5><button class="btn btn-sm btn-primary" id="btnRegresarFacturas"><i class="feather-16" data-feather="arrow-left"></i> Regresar</button></div>'
                    );

                //Agregamos informacion de la factura en el div infoFacturaActual 
                $('#infoFacturaActual').html('<div class="row mb-lg-4"><div class="col-md-3 col-sm-6"> <label for="uuidFact">Factura: </label> <label id="uuidFact"class="form-control">' + factura.uuid + '</label></div>' +
                    '<div class="col-md-2 col-sm-6"> <label for="fechaFact">Fecha Factura: </label> <label id="fechaFact"class="form-control">' + factura.fecha + '</label></div>' +
                    '<div class="col-md-2 col-sm-6"> <label for="fechaIngreso">Fecha Ingreso: </label> <label id="fechaIngreso"class="form-control">' + factura.fecha_llegada + '</label></div>' +
                    '<div class="col-md-3 col-sm-6"> <label for="bodegaFact">Bodega: </label> <label id="BodegaFact"class="form-control">' + factura.nombreBodega + '</label></div>' +
                    '<div class="col-md-2 col-sm-6"> <label for="estadoFact">Estado: </label> <label id="estadoFact"class="form-control">' + factura.nombreStatus + '</label></div>'
                );
                //Recalcular los ajustes de la factura
                recalculaAjustes();
                //Actualizamos los iconos de feather    
                feather.replace();
            } else {
                $('#divTablaEntradas').html('<div class="alert alert-warning">No hay productos para mostrar</div>');
            }
        },
        error: function(error) {
            console.log(error);
        }
    }); //Fin ajax pintaHojaTrabajoCosteo
}


//Creamoos un evento para cuando se presiona el boton de guardar validacion de factura btnGuardarValidacionFactura
$(document).on('click', '#btnGuardarValidacionFactura', function() {
    //Antes de empezar solicitamos confirmacion al usuario
    // Swal.fire({
    //     title: '¿Estás seguro de guardar la validación de la factura?',
    //     text: "Una vez guardada no podrá ser modificada",
    //     icon: 'warning',
    //     showCancelButton: true,
    //     confirmButtonColor: '#3085d6',
    //     cancelButtonColor: '#d33',
    //     confirmButtonText: 'Guardar',
    //     cancelButtonText: 'Cancelar'
    // }).then((result) => {
    //     if (!result.isConfirmed) {
    //         return;
    //     }
    // }); //Fin Swal.fire
    confirmarGuardado = confirm('¿Confirma validar los ajustes de la factura?');
    if (!confirmarGuardado) {
        return;
    }


    //Obtenemos los productos de la factura
    let productos = JSON.parse(sessionStorage.getItem('productos'));
    //Obtenemos la factura
    let factura = JSON.parse(sessionStorage.getItem('factura'));
    let tipoAjuste = sessionStorage.getItem('tipoAjuste');
    console.log(tipoAjuste);
    switch (tipoAjuste) {
        case 'CP':
            tipoAjuste = 'CP';
            break;
        case 'CI':
            tipoAjuste = 'CI';
            break;
        case 'PL':
            tipoAjuste = 'PL';
            break;
    }

    modeloDescuentoGlobal = {
        'id': 0,
        'comentario': '',
        'valor': 0
    };

    //Creamos un arreglo para enviar los datos
    let paqFactura = {
        'id_factura':factura.id,
        'tipoAjuste': tipoAjuste,
        'ajusteDescuento': 0,
        'ajusteRechazo': 0,
        'iva': 0,
        'ieps': 0,
        'subtotalBruto': 0,
        'descuentoTotal': 0,
        'descuentosGlobales': [],
        'descuentoGlobal': 0,
        'subtotalNeto': 0,
        'totalAPagar': 0
    };
    //Actualizamos algunos datos de la factura en funcion a lo que tenemos en las eqtiquetas del resumen
    paqFactura.ajusteDescuento = sumaInputDescuentosAjuste();
    paqFactura.ajusteRechazo = parseFloat($('#totalRechazosIngreso').attr('valorReal'));
    paqFactura.iva = parseFloat($('#ivaIngreso').attr('valorReal'));
    paqFactura.ieps = parseFloat($('#iepsIngreso').attr('valorReal'));
    paqFactura.subtotalBruto = parseFloat($('#subtotalBrutoIngreso').attr('valorReal'));
    paqFactura.descuentoTotal = parseFloat($('#totalAjusteIngreso').attr('valorReal'));
    paqFactura.subtotalNeto = parseFloat($('#subtotalNetoIngreso').attr('valorReal'));
    paqFactura.totalAPagar = parseFloat($('#totalIngreso').attr('valorReal'));
    //Recorremos los descuentos globales
    $('.inputDesctGlobal').each(function(index, input) {
        let descuentoGlobal = Object.create(modeloDescuentoGlobal);
        //Separamos el id y el comentario del input
        var id = $(this).attr('id').split('-')[1];
        descuentoGlobal.id = id;
        //Tomamos el comentario del textbox
        descuentoGlobal.comentario = $('#comentarioDescuentoGlobal-' + id).val();

        descuentoGlobal.valor = $(input).val();
        paqFactura.descuentosGlobales.push(descuentoGlobal);
        //Sumamos el descuento global
        paqFactura.descuentoGlobal += parseFloat($(input).val());
    });

    //Creamos un modelo de producto para enviar los datos
    let paqProducto = [];
    let baseProducto = {
        'id_cfp': 0,
        'id_prod': 0,
        'id': 0,
        'id_compra_factura':0,
        'id_prod_compra': 0,
        'costoIngreso': 0,
        'precioListaCatalogo': 0,
        'descuentoRechazo': 0,
        'ajusteDescuento': 0,
        'totalIngreso': 0,
        'descuento_porcentaje': 0
    };

    //Recorremos los productos para enviar los datos
    $.each(productos, function(index, producto) {
        let productoEnviar = Object.create(baseProducto);
        productoEnviar.id_cfp = producto.id_cfp;
        productoEnviar.id_prod = producto.id_prod;
        productoEnviar.id = producto.id;
        productoEnviar.id_compra_factura = producto.id_compra_factura;
        productoEnviar.id_prod_compra = producto.id_prod_compra;
        productoEnviar.costoIngreso = parseFloat($('#costoIngreso-' + producto.id_prod).text());
        productoEnviar.precioListaCatalogo = parseFloat($('#precioLista-' + producto.id_prod).text());
        productoEnviar.descuentoRechazo = parseFloat($('#descuentoRechazo-' + producto.id_prod).val());
        productoEnviar.ajusteDescuento = parseFloat($('#descuentoAjuste-' + producto.id_prod).val());
        productoEnviar.totalIngreso = parseFloat($('#subTotalIngreso-' + producto.id_prod).attr('valorReal'));
        productoEnviar.descuento_porcentaje = parseFloat(producto.diffPorcentaje);
        paqProducto.push(productoEnviar);
    });
    console.log("Datos a enviar:");
    console.log(
        paqFactura,
        paqProducto
    );
    // return;  

    //Estamos listos para enviar los datos
    $.ajax({
        url: 'services/mainService.php',
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'actualizarAjustesFactura',
            controller: "CompraFactura",
            args: {
                'factura': paqFactura,
                'productos': paqProducto
            }
        },
        success: function(response) {
            // console.log(response);
            response = JSON.parse(response);
            if (response.status) {
                //Mostramos un mensaje de exito
                Swal.fire({
                    icon: 'success',
                    title: 'Validación Guardada',
                    text: response.message,
                    showConfirmButton: false
                });
                //Simulamos un clic en btnRegresarFacturas
                $('#btnRegresarFacturas').click();
            } else {
                //Mostramos un mensaje de error
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message,
                    showConfirmButton: false
                });
            }
        },
        error: function(error) {
            console.log(error);
        }
    });

});

//Creamos un evento para cuando pulsan el boton de descargar excel de la factura a traves de la clase btnDownloadExcel
$(document).on('click', '.btnDownloadExcel', function() {
    //Obtenemos el id de la factura
    var idFactura = $(this).attr('id');
    //Obtenemos el valor del descuento ajuste
    var descuentoAjuste = $('#descAjuste').val();
    //Verificamos si ya se ha agregado el formulario
    if ($('#exportForm').length === 0) {
        //Creamos un formulario para enviar los datos
        var form = $('<form id="exportForm" action="services/createReport.php" method="post" target="_blank">' +
            '<input type="hidden" name="action" value="exportaExcelFactura">' +
            '<input type="hidden" name="controller" value="CompraFacturaProd">' +
            '<input type="hidden" name="id_factura" value="' + idFactura + '">' +
            '<input type="hidden" name="ajusteDescuento" value="' + descuentoAjuste + '">' +
            '</form>');
        $('body').append(form);
        form.submit();
    }else{
        //Si ya existe el formulario, entonces solo actualizamos los valores
        $('#exportForm input[name="id_factura"]').val(idFactura);
        $('#exportForm input[name="ajusteDescuento"]').val(descuentoAjuste);
        $('#exportForm').submit();
    
    }
});

//Funcion que recalcula los totales del total a pagar
function actualizaTotalIngreso() {
   //Obtnemos una copia de los productos de sessionStorage
    let productos = JSON.parse(sessionStorage.getItem('productos'));
    //creamos variables para calcular impuestos en funcion al reciduo de la diferencia del ajuste y el subtotal de la factura del producto
    let totalIEPS = 0;
    let totalIVA = 0;
    let subtotalNeto = 0;
    
    var subTotalIngreso = 0;
    var iepsXL = 0;
    var ieps = 0;
    var iva = 0;
    // console.log("El tamaño de productos es: " + productos.length);
    //Recorremos los productos para calcular los impuestos
    $.each(productos, function(index, producto) {
        //Leemos subTotalIngreso- + idProducto para obtener el subtotal de ingreso
        subTotalIngreso = parseFloat($('#subTotalIngreso-' + producto.id_prod).attr('valorReal'));
        // console.log("El subtotal de este producto id: " + producto.id_prod + " es: " + subTotalIngreso);

        //Los descuentos off no afectan los impuestos
        //Calculamos los impuestos en base a la variable subTotalIngreso
        // iepsXL = producto.totalIEPSxL * parseFloat(producto.cantidad_aceptada);
        // ieps = (subTotalIngreso * (1 + parseFloat(producto.ieps)) - subTotalIngreso) + iepsXL;
        // iva = (subTotalIngreso + ieps) * (1 + parseFloat(producto.iva)) - (parseFloat(subTotalIngreso) + ieps);

        //Calculamos los inpuestos
        // totalIEPS += ieps;
        // totalIVA += iva;
        //Calculamos el subtotal neto
        subtotalNeto += subTotalIngreso;
    });

    //Leemos descuentos globales 
    let sumaDescGlobales = 0;
    //Recorremos la clase inputDesctGlobal para sumar los valores de los descuentos globales en caso de haber
    $('.inputDesctGlobal').each(function(index, input) {
        sumaDescGlobales += parseFloat($(input).val());
    });
    subtotalNeto -= sumaDescGlobales;

    //Los descuentos off no afectan los impuestos
    //Actualizamos las etiquetas de los totales
    // $('#ivaIngreso').text(toMoney(totalIVA.toFixed(2)));
    // $('#ivaIngreso').attr('valorReal', totalIVA);
    // $('#iepsIngreso').text(toMoney(totalIEPS.toFixed(2)));
    // $('#iepsIngreso').attr('valorReal', totalIEPS);
    

    // **** Como los descuentos off no afectan los impuestos, entonces no se actualizan los impuestos por lo tanto tomamos los valores de las etiquetas ****
    totalIVA = parseFloat($('#ivaIngreso').attr('valorReal'));
    totalIEPS = parseFloat($('#iepsIngreso').attr('valorReal'));

    
    // $('#subtotalNetoIngreso').text(toMoney(subtotalNeto.toFixed(2)));
    // $('#subtotalNetoIngreso').attr('valorReal', subtotalNeto);
    $('#totalIngreso').text(toMoney((subtotalNeto + totalIVA + totalIEPS).toFixed(2)));
    $('#totalIngreso').attr('valorReal', (subtotalNeto + totalIVA + totalIEPS));

}




//Creamos una funcion que sume los valores de los inputs .inputDescuentoAjuste 
function sumaInputDescuentosAjuste() {
    let total = 0;
    $('.inputDescuentoAjuste').each(function(index, input) {
        total += parseFloat($(input).val());
    });
    return total;
}


//Creamos una funcion que sume los valores de los inputs .inputDescuentoAjuste 
function sumaInputRechazos(){
    let total = 0;
    $('.inputDescuentoRechazo').each(function(index, input) {
        total += parseFloat($(input).val());
    });
    return total;
}


//Creamos una funcion para pintar las facturas de acuerdo a las fechas, proveedor y descuento ajuste
function pintaFacturas(fechaInicio, fechaFin, idProveedor, idOrdenCompra) {
    $.ajax({
        url: 'services/mainService.php',
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'getResumenFacturasValidadas',
            controller: "CompraFactura",
            args: {
                'fechaInicio': fechaInicio,
                'fechaFin': fechaFin,
                'idProveedor': idProveedor
            }
        },
        success: function(response) {
            // console.log(response);
            // Parseamos la cadena JSON para convertirla en un array de objetos
            var data = JSON.parse(response);

            //Si tiene datos la respuesta, entonces pintamos una tabla nueva con los datos en div divTablaEntradas
            if (data.length > 0) {
                var tabla =
                    '<table id="tablaEntradas" class="table table-striped table-bordered table-hover" style="width:100%">';
                // //Agregamos una fila de cabecera a la tabla
                // tabla += '<tr>';
                // //Agregamos una celda que ocupe 5 columnas
                // tabla += '<td colspan="5"></td>';
                // //Agregamos una celda que ocupe 4 columnas
                // tabla += '<td colspan="4" style="background-color: #E2E2E2;">Descuento Ajuste</td>';
                // //Agregamos una celda que ocupe 2 columnas
                // tabla += '<td colspan="2"></td>';
                // tabla += '</tr>';

                tabla += '<thead>';
                tabla += '<tr>';
                tabla += '<th><small>Bodega</small></th>';
                tabla += '<th><small>Factura</small></th>';
                tabla += '<th><small>Fecha<br>Factura</small></th>';
                tabla += '<th><small>Fecha<br>Ingreso</small></th>';
                tabla += '<th><small>Costo de<br>Factura</small></th>';
                tabla += '<th><small>Anterior<br>Neto</small></th>';
                tabla += '<th><small>Bruto</small></th>';
                tabla += '<th><small>Impuestos</small></th>';
                tabla += '<th><small>Neto</small></th>';
                tabla += '<th><small>Costo<br>Autorizado</small></th>';
                tabla += '<th><small>Acciones</small></th>';
                tabla += '</tr>';
                tabla += '</thead>';
                tabla += '<tbody>';
                //Recorremos el array de facturas
                $.each(data, function(index, factura) {
                    tabla += '<tr>';
                    tabla += '<td>' + factura.bodega + '</td>';
                    tabla += '<td>' + factura.uuid + '</td>';
                    tabla += '<td>' + factura.fecha + '</td>';
                    tabla += '<td>' + factura.fecha_llegada + '</td>';
                    tabla += '<td class="dt-right">' + toMoney((parseFloat(factura.total)).toFixed(2)) +
                    '</td>';
                    tabla += '<td class="dt-right">' + toMoney((parseFloat(factura.total)).toFixed(2)) + '</td>';
                    tabla += '<td class="dt-right">' + toMoney((parseFloat(factura.subTotalBruto)).toFixed(2)) + '</td>';
                    tabla += '<td class="dt-right">' + toMoney((parseFloat(factura.iepsTraslado)+parseFloat(factura.ivaTraslado)).toFixed(2)) + '</td>';
                    //Neto
                    var neto = (parseFloat(factura.subTotalBruto) + parseFloat(factura.ivaTraslado) + parseFloat(factura.iepsTraslado)) - (parseFloat(factura.descRechazo) - parseFloat(factura.descGlobal));
                    // console.log("Neto se calculo asi: "+factura.subTotalBruto+" + "+factura.ivaTraslado+" + "+factura.iepsTraslado+" - "+factura.descRechazo+" - "+factura.descGlobal+" = "+neto);
                    tabla += '<td class="dt-right">' + toMoney(neto) + '</td>';
                    tabla += '<td class="dt-right">' + toMoney((parseFloat(factura.totalAPagar2)).toFixed(2)) + '</td>';
                    let buttonHtml = `
                        <button id="${factura.idFactura}" class="btn btn-xs ml-2 btn-primary btnCosteaFactura" ${factura.status_factura == 1 ? 'disabled' : ''}>
                            <i class="feather-16" data-feather="dollar-sign"></i> ${factura.status_factura == 1 ? 'Validada' : 'Validar'}
                        </button>
                    `;
                    tabla += '<td><button id="' + factura.idFactura +'" title="Exportar a excel: ' + $('#descAjuste option:selected').text() +'" class="btn btn-xs btn-info btnDownloadExcel"><i class="feather-16" data-feather="download"></i></button> '+buttonHtml+' </td>';
                    tabla += '</tr>';
                });

                tabla += '</tbody>';
                tabla += '<tfoot>';
                tabla += '<tr>';
                tabla += '<th><small>Bodega</small></th>';
                tabla += '<th><small>Factura</small></th>';
                tabla += '<th><small>Fecha<br>Factura</small></th>';
                tabla += '<th><small>Fecha<br>Ingreso</small></th>';
                tabla += '<th><small>Costo de<br>Factura</small></th>';
                tabla += '<th><small>Anterior<br>Neto</small></th>';
                tabla += '<th><small>Bruto</small></th>';
                tabla += '<th><small>Impuestos</small></th>';
                tabla += '<th><small>Neto</small></th>';
                tabla += '<th><small>Costo<br>Autorizado</small></th>';
                tabla += '<th><small>Acciones</small></th>';
                tabla += '</tr>';
                tabla += '</tfoot>';
                tabla += '</table>';

                $('#divTablaEntradas').html(tabla);

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
                //Actualizamos los iconos de feather
                // console.log(data);
                //Actualizamos el titulo de la tabla
                $('#tituloTabla').html('Ordenes de Compra para <b>' + data[0].razonSocial + '</b> ' + $('#descAjuste option:selected').text());
                feather.replace();


            } else {
                //Actualizamos el titulo de la tabla
                $('#tituloTabla').html('Ordenes de Compra para <b>' + $('#idProveedor option:selected').text() + '</b> ' + $('#descAjuste option:selected').text());
                $('#divTablaEntradas').html('<div class="alert alert-warning">No hay facturas validadas para este proveedor en este rango de fechas</div>');
                //Avisamos que no hay facturas para mostrar para este proveedor
                alert('No hay facturas para mostrar para este proveedor: ' + $('#idProveedor option:selected').text());
            }
        },
        error: function(error) {
            console.log(error);
        }
    });
} // Fin de la funcion pintaFacturas


//Creamos una funcion para pintar las ordenes de compra
function pintaOrdenes(id_prov, id_bodega) {
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
        success: function(data) {
            //Si tiene datos la respuesta, entonces pintamos una tabla nueva con los datos en div divTablaEntradas
            if (data.length > 0) {
                var tabla =
                    '<table id="tablaEntradas" class="table table-striped table-bordered table-hover" style="width:100%">';
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
                    tabla += '<td>' + compra.id + '</td>';
                    tabla += '<td>' + compra.nombre_proveedor + '</td>';
                    tabla += '<td>' + compra.nombre_bodega + '</td>';
                    tabla += '<td>' + compra.total_esperado + '</td>';
                    tabla += '<td>' + compra.total + '</td>';
                    tabla += '<td>' + compra.alta + '</td>';
                    tabla += '<td>' + compra.facturas + '</td>';
                    tabla += '<td>' + compra.btn + '</td>';
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
                    dom: 'frti',
                    language: {
                        "url": "js/spanish.js"
                    },
                    order: [],
                    stripeClasses: [],
                    paging: false // Deshabilitamos la paginación
                });
                console.log(data);
                //Actualizamos el titulo de la tabla
                $('#tituloTabla').html('Ordenes de Compra para <u>' + data[0].razonSocial + '</u>');
                //Actualizamos los iconos de feather
                feather.replace();


            } else {
                $('#divTablaEntradas').html('<div class="alert alert-warning">No hay ordenes de compra pendientes para este proveedor en esta bodega</div>');
            }
        },
        error: function(error) {
            console.log(error);
        }
    });
} // Fin de la funcion pintaOrdenesCompra 

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