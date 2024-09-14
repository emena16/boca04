//Modelo, nuestra unica fuente de verdad
var model = {
  factura: { items: [], val: null, loading: null, show: false, msg: '', descuentoGlobal: [] },
  proveedor: { items: [], val: null, loading: false, getNombre: getNombreByID },
  form: {
    ini: null,
    fin: null,
    ajuste: {
      items: [
        {
          nombre: 'contra Costo Pactado',
          tipoReporte: '1'
        },
        {
          nombre: 'contra Costo Ingreso',
          tipoReporte: '2'
        },
        {
          nombre: 'contra Precio Lista',
          tipoReporte: '3'
        },
      ], val: "0", loading: false
    },
  },
  descuentoGlobalDefault: { id: 0, descuento: 0, nota: null, remove: false },
  modoConsulta: true,
  readOnly: false,

  indexShow: false, // Pantallas
};

$(function () {
  $.views.helpers({
    antesCP: (item, index, items) => (item.posteriorCP == 0), // Filtros
    despuesCP: (item, index, items) => (item.posteriorCP == 1),
    exportarFacturaAExcel: (ev, eventArgs) => exportarFacturaAExcel(eventArgs.linkCtx.data),
    resetAjuste: (ev, eventArgs) => {
      const item = eventArgs.linkCtx.data
      $.observable(item).setProperty('ajusteBruto', null)
      updateValoresProducto(item)
      updateValoresFactura(item.parent)
    },
    agregarDescuento: (ev, eventArgs) => {
      const descuentoGlobalNuevo = Object.assign({}, model.descuentoGlobalDefault)
      descuentoGlobalNuevo["parent"] = eventArgs.linkCtx.data
      $.observe(descuentoGlobalNuevo, "descuento", (ev, eventArgs) => { // Si hacen cambio en el descuento, actualizamos la factura
        updateValoresFactura(ev.currentTarget.parent)
      })
      $.observable(eventArgs.linkCtx.data.descuentoGlobal).insert(descuentoGlobalNuevo)
    },
    eliminarDescuento: (ev, eventArgs) => {
      $.observable(eventArgs.linkCtx.data).setProperty('remove', true)
      updateValoresFactura(eventArgs.linkCtx.data.parent)
      $.observable(eventArgs.linkCtx.data.parent.descuentoGlobal).refresh( // El filtro no se refresca, mejor lo forzamos.
        eventArgs.linkCtx.data.parent.descuentoGlobal.filter(desc => !desc.remove)
      )
    }
  });

  // Leemos los parametros en Get
  const params = new URLSearchParams(window.location.search);
  $.observable(model).setProperty('readOnly', params.has('readOnly'))

  indexShow() // Cargamos la pantalla de indice

  var contenidoTmpl = $.templates("#contenidoTmpl"),
    pageOptions = {};
  contenidoTmpl.link("#contenido", model, { page: pageOptions });
});

function indexShow() {

  loadOptions(model.proveedor, '/api/boca/Proveedor/getItems')

  $.observable(model).setProperty("indexShow", true);
}

function updateListadoFactura() {
  if (model.form.ini && model.form.fin && model.proveedor.val) {
    $.observable(model).setProperty('modoConsulta', false)
    $.observable(model.factura).setProperty('show', false)
    $.observable(model.factura).setProperty('msg', 'Cargando...')

    loadOptions(model.factura, '/api/boca/Factura/getFacturas', { ini: model.form.ini, fin: model.form.fin, proveedor_id: model.proveedor.val }, { placeholder: false })
      .then(() => {
        $.observable(model.factura).setProperty('msg', 'Calculando...')
        model.factura.items.forEach(factura => {
          factura.items.forEach(prod => {
            $.observable(factura).setProperty('enabled', true)
            prod['parent'] = factura

            // Asignamos precio gsv
            precio_gsv = factura.preciosXProducto.items.find(item => item.id_prod == prod.id_prod && item.tipo == 'actual')
            $.observable(prod).setProperty('precio_gsv', precio_gsv ? Number(precio_gsv.precio) : 0)

            //Asignamos descuentos
            let descArray = []
            factura.descuento.items.forEach(item => {
              let desc = factura.descuentosXProducto.items.find(desc => desc.id_descuento == item.id && desc.id_prod == prod.id_prod)
              if (desc) {
                $.observable(desc).setProperty('descuento', Number(desc.descuento).toFixed(2))
                $.observable(desc).setProperty('sel', true)
                $.observable(desc).setProperty('posteriorCP', item.posteriorCP)
                $.observable(desc).setProperty('parent', prod)

                $.observe(desc, "sel", (ev, eventArgs) => { // Si hacen cambio en el porcentaje de descuentos, actualizamos el item y la factura
                  $.observable(ev.currentTarget.parent).setProperty('ajusteBruto', null)
                  updateValoresProducto(ev.currentTarget.parent)
                  updateValoresFactura(ev.currentTarget.parent.parent)
                })
              } else {
                desc = { descuento: 0, sel: true, posteriorCP: item.posteriorCP }
              }
              descArray.push(desc)
            })
            $.observable(prod).setProperty('desc', descArray)
            $.observable(prod).setProperty('enabled', prod.precio_gsv && prod.costoReal)
            if (factura.enabled && !prod.enabled)
              $.observable(factura).setProperty('enabled', false)
            prod.cantidad = Number(prod.cantidad)
            $.observable(prod).setProperty('ajusteBruto', Number.parseFloat(prod.ajusteBruto) ? Number.parseFloat(prod.ajusteBruto).toFixed(2) : null)
            updateValoresProducto(prod)


            // Escuchamos los cambios en ajusteBruto
            $.observe(prod, "ajusteBruto", (ev, eventArgs) => { // Si hacen cambio en el ajuste, actualizamos la factura
              $.observable(ev.currentTarget).setProperty('touched', true)
              updateValoresProducto(ev.currentTarget)
              updateValoresFactura(ev.currentTarget.parent)
            })
          })
          factura.descuentoGlobal.forEach(desc => {
            $.observable(desc).setProperty('descuento', Number(desc.descuento).toFixed(2))
            $.observable(desc).setProperty('parent', factura)
          })
          updateValoresFactura(factura)
        })
        $.observable(model.factura).setProperty('msg', false)
        $.observable(model.factura).setProperty('show', true)
        $(".descuento").checkboxradio({ icon: false });
      })
  }
}

function toNeto(monto, item) {
  return (Number(monto) * (1 + Number(item.ieps)) + Number(item.litros) * Number(item.iepsxl)) * (1 + Number(item.iva))
}

function updateValoresProducto(item) {
  $.observable(item).setProperty('valido', true) // Default todos son validos.
  if (item.enabled) {

    const descuentoOff = (1 - item.desc.filter(desc => (desc.sel == true && desc.posteriorCP == 1)).reduce((suma, curItem) => suma + Number(curItem.descuento), 0) / 100)
    const descuento = (1 - item.desc.filter(desc => (desc.sel == true && desc.posteriorCP == 0)).reduce((suma, curItem) => suma + Number(curItem.descuento), 0) / 100)

    // Costo Pactado
    $.observable(item).setProperty('costoPactado', Number(item.precio_gsv) * descuento)
    $.observable(item).setProperty('costoIngresoPactado', item.costoPactado * descuentoOff)
    $.observable(item).setProperty('costoPactadoBruto', item.costoPactado * Number(item.cantidad))
    $.observable(item).setProperty('costoPactadoNeto', toNeto(item.costoPactadoBruto, item))

    // Costo Factura
    $.observable(item).setProperty('costoFacturaBruto', Number(item.costoReal) * Number(item.cantidad))
    $.observable(item).setProperty('costoIngresoFactura', Number(item.costoReal) * descuentoOff)
    $.observable(item).setProperty('costoFacturaNeto', toNeto(item.costoFacturaBruto, item))

    // Precio Lista
    $.observable(item).setProperty('precioListaBruto', Number(item.precio_gsv) * Number(item.cantidad))

    $.observable(item).setProperty('diff', ((Number(item.costoReal) - Number(item.costoPactado)) / Number(item.precio_gsv) * 100).toFixed(4))


    // costoIngreso Elegido de entre el menor
    $.observable(item).setProperty('costoIngreso', Math.min(item.costoIngresoFactura, item.costoIngresoPactado))
    $.observable(item).setProperty('costoIngresoBruto', item.costoIngreso * Number(item.cantidad))
    $.observable(item).setProperty('costoIngresoNeto', toNeto(item.costoIngresoBruto, item))

    //Calculamos el ajuste por productos rechazados
    const ajusteProductosRechazadosBruto = Number(item.costoReal) * Number(item.cantidad_rechazada)

    // Obtenemos el Ajuste Calculado en base a lo elegido en el formulario
    const costoCandidato = [item.costoPactadoBruto, item.costoIngresoBruto, item.costoPactadoBruto][model.form.ajuste.val]
    const costoElegido = [item.costoPactadoBruto, item.costoIngresoBruto, item.precioListaBruto][model.form.ajuste.val]

    const ajusteCalculadoRealBruto = ((item.costoFacturaBruto + ajusteProductosRechazadosBruto - costoCandidato) >= 1) ?
      Number((item.costoFacturaBruto + ajusteProductosRechazadosBruto - costoElegido).toFixed(2)) : 0

    // Ya calculados los costos y ajustes, vemos si hay que aplicar AJUSTES INGRESADOS A MANO
    if (item.ajusteBruto == null) {
      $.observable(item).setProperty("ajusteBruto", ajusteCalculadoRealBruto)
    }
    $.observable(item).setProperty('ajusteCalculadoNeto', Number(toNeto(Number(item.ajusteBruto), item).toFixed(2)))

    if (Number(item.ajusteBruto) != ajusteCalculadoRealBruto) { // El valor difiere del calculado, lo indicamos.
      $.observable(item).setProperty('touched', true)

      $.observable(item).setProperty('valido', flotanteValido(item.ajusteBruto) && Number(item.ajusteBruto) >= ajusteCalculadoRealBruto) // solo se permiten valores con ganancia

      // Se solicita que el cambio sea visual en costoIngreso
      $.observable(item).setProperty('costoIngresoBruto', item.costoIngresoBruto + (item.valido ? Number(item.ajusteBruto) : 0))
      $.observable(item).setProperty('costoIngresoNeto', toNeto(item.costoIngresoBruto, item))
    } else {
      $.observable(item).setProperty('touched', false)
    }
  }
}

function flotanteValido(texto) {

  if (!/^\-?(\d+(\.\d*)?|\.\d+)$/.test(texto)) { return false; } // Comprobar que el texto coincida con el formato de número flotante (incluyendo .1)

  const numero = parseFloat(texto); // Convertir el texto a un número
  if (isNaN(numero) || Math.abs(numero) > 1000000000) { // Comprobar que sea un número válido y que no exceda el límite de 1,000,000,000
    return false;
  }

  return true;
}


function updateValoresFactura(item) {
  if (item.query) {
    console.log(item.query);
  }else{
    console.log("No existe query"); 
  }
  $.observable(item).setProperty('totalCostoPactadoBruto', item.items.reduce((suma, curItem) => suma + Number(curItem.costoPactadoBruto ? curItem.costoPactadoBruto : 0), 0))
  $.observable(item).setProperty('totalCostoPactadoNeto', item.items.reduce((suma, curItem) => suma + Number(curItem.costoPactadoNeto ? curItem.costoPactadoNeto : 0), 0))

  $.observable(item).setProperty('totalCostoFacturaBruto', item.items.reduce((suma, curItem) => suma + Number(curItem.costoFacturaBruto ? curItem.costoFacturaBruto : 0), 0))
  $.observable(item).setProperty('totalCostoFacturaNeto', item.items.reduce((suma, curItem) => suma + Number(curItem.costoFacturaNeto ? curItem.costoFacturaNeto : 0), 0))
  $.observable(item).setProperty('totalCostoFacturaImpuestos', item.totalCostoFacturaNeto - item.totalCostoFacturaBruto)

  $.observable(item).setProperty('totalCostoIngresoBruto', item.items.reduce((suma, curItem) => suma + Number(curItem.costoIngresoBruto ? curItem.costoIngresoBruto : 0), 0))
  $.observable(item).setProperty('totalCostoIngresoNeto', item.items.reduce((suma, curItem) => suma + Number(curItem.costoIngresoNeto ? curItem.costoIngresoNeto : 0), 0))

  $.observable(item).setProperty('totalAjusteBruto', item.items.reduce((suma, curItem) => suma + Number(curItem.valido ? curItem.ajusteBruto : 0), 0))
  $.observable(item).setProperty('totalAjusteNeto', item.items.reduce((suma, curItem) => suma + Number(curItem.valido ? curItem.ajusteCalculadoNeto : 0), 0))
  $.observable(item).setProperty('totalAjusteImpuestos', item.totalAjusteNeto - item.totalAjusteBruto)

  // Ajustamos descuentoGlobal
  $.observable(item).setProperty('totalAjusteBruto', item.totalAjusteBruto +
    item.descuentoGlobal.reduce((suma, curItem) => suma + (esNumeroFlotanteValido(curItem.descuento) && !curItem.remove ? Number(curItem.descuento) : 0), 0))
  $.observable(item).setProperty('totalAjusteNeto', item.totalAjusteBruto + item.totalAjusteImpuestos)

  // Todos los items son validos?
  $.observable(item).setProperty('valida',
    item.items.every(prod => prod.valido)
    && item.descuentoGlobal.every(desc => esNumeroFlotanteValido(desc.descuento))
    && item.totalCostoFacturaNeto > 0)
}

function limpiarFiltro() {
  $.observable(model.form).setProperty('ini', null)
  $.observable(model.form).setProperty('fin', null)
  $.observable(model.proveedor).setProperty('val', null)
  $.observable(model).setProperty('modoConsulta', true)
}

function guardarCambios() {
  $.observable(model.factura).setProperty('loading', 'Guardando...')
  $.observable(model.factura).setProperty('show', false)
  items = model.factura.items.filter(factura => factura.sel && factura.valida).map(factura => {
    return {
      factura_id: factura.factura_id,
      ajuste: factura.totalAjusteNeto,
      descuentoGlobal: factura.descuentoGlobal.filter(desc => !desc.remove).map(desc => { return { id: desc.id, descuento: desc.descuento, nota: desc.nota } }),
      itemsID: factura.items.map(item => item.idpf).join(),
      ajusteBruto: factura.items.map(item => item.ajusteBruto).join()
    }
  })
  console.log(items)

  if (items.length) {
    $.post('/api/boca/Factura/storeAjustes', { factura: items }, (resp) => {
      maneja_errores(resp.error);
      updateListadoFactura()
      if (resp.result)
        alert('Cambios guardados.')
    }, 'json').fail(function () {
      alert("Error al guardar los cambios en el servidor. Intentelo mas tarde.");
      $.observable(model.factura).setProperty('loading', null)
      $.observable(model.factura).setProperty('show', true)
    });
  } else {
    $.observable(model.factura).setProperty('loading', null)
    $.observable(model.factura).setProperty('show', true)
    alert("Es necesario que elija al menos una factura para guardar.");
  }
}
/*
 * Esta funcion se encarga de exportar la factura a un archivo de excel
 * @param {Object} factura
 */
function exportarFacturaAExcel(factura) {

  // variables que manejan los impuestos
  let iva = 0;
  let ieps = 0;
  let iepsxl = 0;
  let totalLitros = 0;
  let totalLitrosIEPS = 0;
  let totalLitrosIEPSXL = 0;
  let ajusteTotal = 0;


  switch (model.form.ajuste.val) {
    // Caso: Contra Precio Lista
    case '2':
      var csv = 'Nombre del Producto, Precio Lista,'
      factura.descuento.items.filter(desc => desc.posteriorCP == 0).forEach(desc => csv += ' ' + desc.nombre + '(%),')
      csv += ' Precio Pactado, Costo Unitario Factura,Diff($), Diff(%),'
      factura.descuento.items.filter(desc => desc.posteriorCP == 1).forEach(desc => csv += ' ' + desc.nombre + '(%),')
      csv += 'Costo Ingreso Pactado, Costo Ingreso Factura, Cant. Facturada, Cant. Rechazadas, Subtotal Factura, Ajuste Precio Lista, Ajuste Rechazo, Ajuste Total\n'
      factura.items.forEach(item => {
        // En caso de que no exista el precio gsv, se toma el costoReal como precio gsv
        !item.precio_gsv ? item.precio_gsv = item.costoReal : item.precio_gsv;
        !item.costoPactado ? item.costoPactado = item.precio_gsv : item.costoPactado;
        !item.costoFacturaBruto ? item.costoFacturaBruto = item.costoReal * item.cantidad : item.costoFacturaBruto;

        csv += '"' + item.nombre + '",'
          + (item.precio_gsv ? '"' + item.precio_gsv + '"' : '') + ','
        // Sumamos los descuentos antes del costoPactado
        let sumaDescuentosAntes = 0;
        item.desc.filter(desc => desc.posteriorCP == 0).forEach(desc => {
          csv += (desc.descuento / 100 ? desc.descuento / 100 : '0') + ',';
          sumaDescuentosAntes += desc.descuento ? Number(desc.descuento) : 0;
        })
        csv += (item.costoPactado ? item.costoPactado : '') + ','
          + (item.costoReal ? item.costoReal : '') + ','
          + (item.costoReal && item.costoPactado ? item.costoReal - item.costoPactado : '0') + ',' //Diferencia en pesos MXN
          + (item.diff ? item.diff : '0') + ',' // Diferencia en porcentaje
        // Sumamos los descuentos posteriores al costoPactado
        let sumaDescuentosPost = 0;
        item.desc.filter(desc => desc.posteriorCP == 1).forEach(desc => {
          csv += (desc.descuento / 100 ? desc.descuento / 100 : '0') + ',';
          sumaDescuentosPost += desc.descuento ? Number(desc.descuento) : 0;
        })
        //Calculamos el costoIngresoPactado
        let costoIngresoPactado = item.costoPactado * (1 - sumaDescuentosPost / 100)
        let costoIngresoFactura = item.costoReal * (1 - sumaDescuentosPost / 100)
        csv += (costoIngresoPactado ? costoIngresoPactado : '') + ','
        //Calculamos el ajuste de precio lista
        let ajustePrecioLista = 0;
        if (item.costoReal && item.costoPactado && item.precio_gsv && (item.costoReal > item.precio_gsv)) {
          ajustePrecioLista = (item.costoReal - item.precio_gsv) * (item.cantidad - item.cantidad_rechazada);
        }
        // Calculamos ajuste de rechazados
        let ajusteRechazados = item.costoReal * item.cantidad_rechazada;
        //Calculamos el ajuste total
        let ajusteTotal = ajustePrecioLista + ajusteRechazados;
        csv += (costoIngresoFactura ? costoIngresoFactura.toFixed(4) : '') + ',' // Costo ingreso nuevo
          + (item.cantidad ? item.cantidad : '') + ','
          + (item.cantidad_rechazada ? item.cantidad_rechazada : '') + ','
          // Subtotal Factura
          + (item.costoFacturaBruto ? item.costoFacturaBruto.toFixed(4) : '') + ','
          // Ajuste Precio Lista
          + (ajustePrecioLista.toFixed(4)) + ','
          // Ajuste Rechazados
          + (ajusteRechazados ? ajusteRechazados.toFixed(4) : '0') + ','
          // Ajuste **Sumamos los ajustes de precio lista y rechazados
          + (ajusteTotal ? ajusteTotal.toFixed(4) : '0') + '\n'

        // Calculamos el iva de todos los productos recibidos
        ajusteTotal += ajusteTotal;
        iva += (item.precio_gsv * item.iva) * (item.cantidad - item.cantidad_rechazada);
        ieps += (item.precio_gsv * item.ieps) * (item.cantidad - item.cantidad_rechazada);
        iepsxl += (item.precio_gsv * item.iepsxl) * (item.cantidad - item.cantidad_rechazada);
        totalLitros += item.litros * (item.cantidad - item.cantidad_rechazada);
        totalLitrosIEPS += item.litros * item.iepsxl * (item.cantidad - item.cantidad_rechazada);
      })
      // Descuentos Globales
      var espaciado = ',,,,,,,,,,,,,,,,'

      //Dejamos un reglon en blanco
      csv += '\n';
      espacioDescuentos = espaciado;
      csv += ( espacioDescuentos + ',Desgloce de Totales despues de descuentos') + '\n';
      csv += ( espacioDescuentos + ',Ajuste Total:,' + ajusteTotal.toFixed(3)) + '\n';
      csv += ( espacioDescuentos + ',IVA :,' + iva.toFixed(3)) + '\n';
      csv += ( espacioDescuentos + ',IEPS:,' + ieps.toFixed(3)) + '\n';
      csv += ( espacioDescuentos + ',IEPSXL:,' + iepsxl.toFixed(3)) + '\n';
      csv += ( espacioDescuentos + ',Total Litros:,' + totalLitros.toFixed(3)) + '\n';
      csv += ( espacioDescuentos + ',Total IEPSxLts:,' + totalLitrosIEPS.toFixed(3)) + '\n';

      factura.descuento.items.forEach(desc => espaciado += ',')
      factura.descuentoGlobal.forEach(desc => csv += espaciado + ' ' + desc.descuento + ',"' + sanitizeString(desc.nota) + '"\n')
      // Descargamos el archivo
      download('ValidacionDeCostos-VsPrecioLista-' + model.proveedor.getNombre() + '- ' + factura.fecha + ' -' + factura.uuid + '.csv', csv)
      break;

    //Caso: Contra Costo Ingreso
    case '1':
      var csv = 'Nombre del Producto, Precio Lista,'
      let nombresDescuentosAntes = '';
      let nombresDescuentosDespues = '';
      let numColmDescAntes = '';
      let numColmDescDespues = '';
      let ajustesDescuentosAntes = [];
      let ajustesDescuentosDespues = [];

      factura.descuento.items.filter(desc => desc.posteriorCP == 0).forEach(desc => {
        csv += ' ' + desc.nombre + '(%),';
        nombresDescuentosAntes += "Ajuste " + desc.nombre + ',';
        numColmDescAntes += ',';
      })
      csv += ' Precio Pactado, Costo Unitario Factura, Diff %,'
      //factura.descuento.items.filter(desc => desc.posteriorCP == 1).forEach(desc => csv += ' ' + desc.nombre + '(%),') 
      factura.descuento.items.filter(desc => desc.posteriorCP == 1).forEach(desc => {
        csv += ' ' + desc.nombre + '(%),';
        nombresDescuentosDespues += "Ajuste " + desc.nombre + ',';
        numColmDescDespues += ',';
      })
      csv += 'Costo Ingreso Pactado, Costo Ingreso Factura, Cantidad Facturada, Cantidad Rechazada, Subtotal Factura, Ajuste Precio Lista,' + nombresDescuentosAntes + nombresDescuentosDespues + ' Ajuste Rechazo, Ajuste Pactado Extra, Ajuste Ingreso Extra, Ajuste Total\n'

      factura.items.forEach(item => {

        // En caso de que no exista el precio gsv, se toma el costoReal como precio gsv
        !item.precio_gsv ? item.precio_gsv = item.costoReal : item.precio_gsv;
        !item.costoPactado ? item.costoPactado = item.precio_gsv : item.costoPactado;
        !item.costoFacturaBruto ? item.costoFacturaBruto = item.costoReal * item.cantidad : item.costoFacturaBruto;

        csv += '"' + item.nombre + '",'
          + (item.precio_gsv ? '"' + item.precio_gsv + '"' : '') + ','
        let sumaDescuentosAntes = 0;
        item.desc.filter(desc => desc.posteriorCP == 0).forEach(desc => {
          csv += (desc.descuento / 100 ? desc.descuento / 100 : '0') + ',';
          sumaDescuentosAntes += desc.descuento ? Number(desc.descuento) : 0;
        })
        csv += (item.costoPactado ? item.costoPactado : '') + ','
          + (item.costoReal ? item.costoReal : '') + ','
          + (item.diff ? item.diff : '0') + ','
        //Calculamos los descuentos posteriores al costoPactado
        let sumaDescuentosPost = 0;
        item.desc.filter(desc => desc.posteriorCP == 1).forEach(desc => {
          csv += (desc.descuento / 100 ? desc.descuento / 100 : '0') + ',';
          sumaDescuentosPost += desc.descuento ? Number(desc.descuento) : 0;
        })
        // Calculamos el ajuste de precio lista
        let ajustePrecioLista = 0;
        if (item.costoReal && item.precio_gsv && (item.costoReal > item.precio_gsv)) {
          ajustePrecioLista = (item.costoReal - item.precio_gsv) * (item.cantidad - item.cantidad_rechazada);
        }
        //Alamcenamos la suma de los ajustes de descuentos antes del costoPactado
        let sumaAjustesDescuentosAntes = 0;
        let sumaAjustesDescuentosDespues = 0;
        //Calculamos el ajuste para cada descuento antes del costoPactado
        item.desc.filter(desc => desc.posteriorCP == 0).forEach(desc => {
          ajuste = desc.descuento / 100 * item.precio_gsv * (item.cantidad - item.cantidad_rechazada);
          ajustesDescuentosAntes.push(ajuste);
          sumaAjustesDescuentosAntes += ajuste;
        })
        //Calculamos el ajuste para cada descuento despues del costoPactado
        item.desc.filter(desc => desc.posteriorCP == 1).forEach(desc => {
          ajuste = desc.descuento / 100 * item.costoPactado * (item.cantidad - item.cantidad_rechazada)
          ajustesDescuentosDespues.push(ajuste);
          sumaAjustesDescuentosDespues += ajuste;
        })
        // Calculamos ajuste pactado extra
        let ajustePactadoExtra = 0;
        if ((sumaAjustesDescuentosAntes + ajustePrecioLista) == 0 && item.costoReal > item.costoPactado) {
          ajustePactadoExtra = (item.costoReal - item.costoPactado) * (item.cantidad - item.cantidad_rechazada);
        }
        let ajusteIngresoExtra = 0;
        let costoIngresoPactado = item.costoPactado * (1 - sumaDescuentosPost / 100)
        if (sumaAjustesDescuentosAntes > 0 && ajustesDescuentosDespues > 0 && item.costoReal > costoIngresoPactado) {
          ajusteIngresoExtra = (item.costoReal - item.costoIngreso) * (item.cantidad - item.cantidad_rechazada);
        }        
        ajusteRechazo = item.costoReal * item.cantidad_rechazada;
        csv += (costoIngresoPactado ? costoIngresoPactado.toFixed(4) : '') + ','
          //Calculamos el costoIngresoFactura
          + ((item.costoReal * (1 - sumaDescuentosPost / 100)).toFixed(4)) + ','
          + (item.cantidad ? item.cantidad : '') + ','
          + (item.cantidad_rechazada ? item.cantidad_rechazada : '') + ','
          + (item.costoFacturaBruto ? item.costoFacturaBruto.toFixed(4) : '') + ','
          + (ajustePrecioLista.toFixed(4)) + ','
          //Calculamos los ajustes conforme a los descuentos
          + ajustesDescuentosAntes.join(',') + ','
          + ajustesDescuentosDespues.join(',') + ','
          //Calculamos el ajuste de rechazados
          + (ajusteRechazo.toFixed(4)) + ','
          + (ajustePactadoExtra) + ','
          //Calculamos el ajuste de ingreso extra
          + (ajusteIngresoExtra) + ','
          //Calculamos el ajuste total
          + ((ajustePrecioLista + sumaAjustesDescuentosAntes + sumaAjustesDescuentosDespues + ajusteRechazo + ajustePactadoExtra + ajusteIngresoExtra).toFixed(4)) + '\n'
        // Vaciamos los arreglos de ajustes para el siguiente producto
        ajustesDescuentosAntes = [];
        ajustesDescuentosDespues = [];

        // Calculamos el iva de todos los productos recibidos
        ajusteTotal += ajustePrecioLista + sumaAjustesDescuentosAntes + sumaAjustesDescuentosDespues + ajusteRechazo + ajustePactadoExtra + ajusteIngresoExtra
        iva += (item.precio_gsv * item.iva) * (item.cantidad - item.cantidad_rechazada);
        ieps += (item.precio_gsv * item.ieps) * (item.cantidad - item.cantidad_rechazada);
        iepsxl += (item.precio_gsv * item.iepsxl) * (item.cantidad - item.cantidad_rechazada);
        totalLitros += item.litros * (item.cantidad - item.cantidad_rechazada);
        totalLitrosIEPS += item.litros * item.iepsxl * (item.cantidad - item.cantidad_rechazada);
      })

      // Descuentos Globales
      var espaciado = ',,,,,,,,,,,,,,,,,' + numColmDescAntes + numColmDescDespues

      //Dejamos un reglon en blanco
      csv += '\n';
      espacioDescuentos = espaciado;
      csv += ( espacioDescuentos + ',Desgloce de Totales despues de descuentos') + '\n';
      csv += ( espacioDescuentos + ',Ajuste Total:,' + ajusteTotal.toFixed(3)) + '\n';
      csv += ( espacioDescuentos + ',IVA :,' + iva.toFixed(3)) + '\n';
      csv += ( espacioDescuentos + ',IEPS:,' + ieps.toFixed(3)) + '\n';
      csv += ( espacioDescuentos + ',IEPSXL:,' + iepsxl.toFixed(3)) + '\n';
      csv += ( espacioDescuentos + ',Total Litros:,' + totalLitros.toFixed(3)) + '\n';
      csv += ( espacioDescuentos + ',Total IEPSxLts:,' + totalLitrosIEPS.toFixed(3)) + '\n';

      factura.descuento.items.forEach(desc => espaciado += ',')
      factura.descuentoGlobal.forEach(desc => csv += espaciado + ' ' + desc.descuento + ',"' + sanitizeString(desc.nota) + '"\n')
      // Descargamos el archivo
      download('ValidacionDeCostos-VsCostoIngreso-' + model.proveedor.getNombre() + '- ' + factura.fecha + ' -' + factura.uuid + '.csv', csv)
      break;

    case '0': // Caso: Contra Costo Pactado
      var csv = 'Nombre del Producto, Precio Lista,'
      let nombDescuentosAntes = '';
      let numColDescAntes = '';
      let numColDescDespues = '';
      let ajustesDectosAntes = [];
      factura.descuento.items.filter(desc => desc.posteriorCP == 0).forEach(desc => {
        csv += ' ' + desc.nombre + '(%),';
        nombDescuentosAntes += "Ajuste " + desc.nombre + ',';
        numColDescAntes += ',';
      })
      csv += 'Precio Pactado, Costo Unitario Factura, Diff,'
      factura.descuento.items.filter(desc => desc.posteriorCP == 1).forEach(desc => {
        csv += ' ' + desc.nombre + '(%),';
        numColDescDespues += ',';
      })
      csv += 'Costo Ingreso Pactado, Costo Ingreso Factura, Cantidad Facturada, Cantidad Rechazada, Subtotal Factura, Ajuste Precio Lista, ' + nombDescuentosAntes + 'Ajuste Rechazo, Ajuste Pactado Extra, Ajuste Total\n'

      factura.items.forEach(item => {

        let sumaDesPost = 0;
        let sumaDesAntes = 0;
        let sumaAjustesDttosAntes = 0;
        let ajusteDto = 0;
        // En caso de que no exista el precio gsv, se toma el costoReal como precio gsv
        !item.precio_gsv ? item.precio_gsv = item.costoReal : item.precio_gsv;
        // En caso de que no exista el costoPactado, se toma el precio gsv como costoPactado
        !item.costoPactado ? item.costoPactado = item.precio_gsv : item.costoPactado;
        // En caso de que no exista el costoFacturaBruto, se toma el costoReal * cantidad como costoFacturaBruto
        !item.costoFacturaBruto ? item.costoFacturaBruto = item.costoReal * item.cantidad : item.costoFacturaBruto;
        
        
        //Vaciamos el arreglo de ajustes para el siguiente producto
        ajustesDectosAntes = [];
        csv += '"' + item.nombre + '",'
          + (item.precio_gsv ? '"' + item.precio_gsv + '"' : '') + ','

        // Imprimimos los descuentos antes del costoPactado y los sumamos
        item.desc.filter(desc => desc.posteriorCP == 0).forEach(desc => {
          //Dividimos el descuento entre 100 para dejar indicado en terminos de porcentaje
          csv += (desc.descuento / 100 ? desc.descuento / 100 : '0') + ',';
          // csv += (desc.descuento ? desc.descuento : '') + ',';
          sumaDesAntes += desc.descuento ? Number(desc.descuento) : 0;
          // Si la diferencia entre el costoReal y el costoPactado es mayor a 0, calculamos el ajuste
          ajusteDto = 0;
          if (item.costoReal > item.costoPactado) {
            ajusteDto = desc.descuento / 100 * item.precio_gsv * (item.cantidad - item.cantidad_rechazada);
          }
          ajustesDectosAntes.push(ajusteDto);
          sumaAjustesDttosAntes += ajusteDto;
        })
        //Calculamos la diferencia %
        let diff = (item.costoReal - item.costoPactado) / item.precio_gsv * 100;
        csv += (item.costoPactado ? item.costoPactado : '') + ','
          + (item.costoReal ? item.costoReal : '') + ','
          + (!diff ? '0' : diff.toFixed(3)) + ','
        // Imprimimos los descuentos despues del costoPactado y los sumamos
        item.desc.filter(desc => desc.posteriorCP == 1).forEach(desc => {
          csv += (desc.descuento / 100 ? desc.descuento / 100 : '0') + ',';
          // csv += (desc.descuento ? desc.descuento : '') + ',';
          sumaDesPost += desc.descuento ? Number(desc.descuento) : 0;
        })
        //Calculamos costoingresoFactura
        let costoIngFactura = (1 - sumaDesPost / 100) * item.costoReal
        let costoIngPactado = (1 - sumaDesPost / 100) * item.costoPactado;
        //Calculamos el ajuste de precio de lista
        let ajustePrecLista = 0;
        if (item.costoReal && item.precio_gsv && (item.costoReal > item.precio_gsv)) {
          ajustePrecLista = (item.costoReal - item.precio_gsv) * (item.cantidad - item.cantidad_rechazada);
        }
        let ajusteRechazo = item.costoReal * item.cantidad_rechazada;
        // Calculamos ajuste pactado extra
        let ajustePactadoExt = 0;
        if ((ajustePrecLista + sumaAjustesDttosAntes) == 0 && item.costoReal > item.costoPactado) {
          ajustePactadoExt = (item.costoReal - item.costoPactado) * (item.cantidad - item.cantidad_rechazada);
        }
        csv += (costoIngPactado) + ','
          //Costo Ingreso Factura
          + (costoIngFactura.toFixed(4)) + ','
          + (item.cantidad ? item.cantidad : '') + ','
          + (item.cantidad_rechazada ? item.cantidad_rechazada : '') + ','
          + (item.costoFacturaBruto ? item.costoFacturaBruto.toFixed(4) : '') + ','
          + (ajustePrecLista.toFixed(4)) + ','
          + ajustesDectosAntes.join(',') + ','
          //Calculamos el ajuste de rechazados
          + (ajusteRechazo) + ','
          + (ajustePactadoExt) + ','
          //Calculamos el ajuste total
          + (ajustePrecLista + sumaAjustesDttosAntes + ajusteRechazo + ajustePactadoExt) + '\n'
        // Vacia los arreglos de ajustes para el siguiente producto
        ajustesDectosAntes = [];
        //Calculamos impuestos de la factura

        // Calculamos el iva de todos los productos recibidos
        ajusteTotal += ajustePrecLista + sumaAjustesDttosAntes + ajusteRechazo + ajustePactadoExt;
        iva += (item.precio_gsv * item.iva) * (item.cantidad - item.cantidad_rechazada);
        ieps += (item.precio_gsv * item.ieps) * (item.cantidad - item.cantidad_rechazada);
        iepsxl += (item.precio_gsv * item.iepsxl) * (item.cantidad - item.cantidad_rechazada);
        totalLitros += item.litros * (item.cantidad - item.cantidad_rechazada);
        totalLitrosIEPS += item.litros * item.iepsxl * (item.cantidad - item.cantidad_rechazada);
      })
      // ## Descuentos Globales ##
      var espaciado = ',,,,,,,,,,,,,,' + numColDescAntes+ numColDescDespues;
      
      // Vamos a imprimir los descuentos globales  y desgloce de tasas de impuestos
      // espacioDescuentos = espaciado.slice(0, -2);

      //Dejamos un reglon en blanco
      csv += '\n';
      espacioDescuentos = espaciado;
      csv += ( espacioDescuentos + ',Desgloce de Totales despues de descuentos') + '\n';
      csv += ( espacioDescuentos + ',Ajuste Total:,' + ajusteTotal.toFixed(3)) + '\n';
      csv += ( espacioDescuentos + ',IVA :,' + iva.toFixed(3)) + '\n';
      csv += ( espacioDescuentos + ',IEPS:,' + ieps.toFixed(3)) + '\n';
      csv += ( espacioDescuentos + ',IEPSXL:,' + iepsxl.toFixed(3)) + '\n';
      csv += ( espacioDescuentos + ',Total Litros:,' + totalLitros.toFixed(3)) + '\n';
      csv += ( espacioDescuentos + ',Total IEPSxLts:,' + totalLitrosIEPS.toFixed(3)) + '\n';


      factura.descuento.items.forEach(desc => espaciado += ',')
      factura.descuentoGlobal.forEach(desc => csv += espaciado + ' ' + desc.descuento + ',"' + sanitizeString(desc.nota) + '"\n')
      // Descargamos el archivo
      download('ValidacionDeCostos-VsCostoPactado-' + model.proveedor.getNombre() + '- ' + factura.fecha + ' -' + factura.uuid + '.csv', csv)
      break;

    default:
      var csv = 'Nombre del Producto, Precio Lista,'
      factura.descuento.items.filter(desc => desc.posteriorCP == 0).forEach(desc => csv += ' ' + desc.nombre + '(%),')
      csv += ' Precio Pactado, Costo Unitario Factura, Diff %,'
      factura.descuento.items.filter(desc => desc.posteriorCP == 1).forEach(desc => csv += ' ' + desc.nombre + '(%),')
      csv += 'Costo Ingreso, U. de Compras, U. Rechazadas, Subtotal Factura, Subtotal Ingreso, Ajuste\n'
      // Imprimimos los productos
      factura.items.forEach(item => {
        csv += '"' + item.nombre + '",'
          + (item.precio_gsv ? '"' + item.precio_gsv + '"' : '') + ','
        item.desc.filter(desc => desc.posteriorCP == 0).forEach(desc => csv += (desc.descuento ? desc.descuento : '') + ',')
        csv += (item.costoPactado ? item.costoPactado : '') + ','
          + (item.costoReal ? item.costoReal : '') + ','
          + (item.diff ? item.diff : '') + ','
        item.desc.filter(desc => desc.posteriorCP == 1).forEach(desc => csv += (desc.descuento ? desc.descuento : '') + ',')
        csv += (item.costoIngreso ? item.costoIngreso : '') + ','
          + (item.cantidad ? item.cantidad : '') + ','
          + (item.cantidad_rechazada ? item.cantidad_rechazada : '') + ','
          + (item.costoFacturaBruto ? item.costoFacturaBruto : '') + ','
          + (item.costoIngresoBruto ? item.costoIngresoBruto : '') + ','
          + (item.ajusteBruto ? item.ajusteBruto : '') + '\n'
      })
      // Descuentos Globales
      var espaciado = ',,,,,,,,,,'
      factura.descuento.items.forEach(desc => espaciado += ',')
      factura.descuentoGlobal.forEach(desc => csv += espaciado + ' ' + desc.descuento + ',"' + sanitizeString(desc.nota) + '"\n')
      // Descargamos el archivo
      download('ValidacionDeCostos-DEFAULT' + model.proveedor.getNombre() + '- ' + factura.fecha + ' -' + factura.uuid + '.csv', csv)
      break;

  }

}
