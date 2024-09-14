//Modelo, nuestra unica fuente de verdad
var model = {
  factura: { items: [], val: null, loading: null, show: false, msg: '', descuentoGlobal: []},
  proveedor: { items: [], val: null, loading: false, getNombre: getNombreByID },
  form: {
    ini: null,
    fin: null,
    ajuste: { items: [
      {nombre: 'contra Costo Pactado'},
      {nombre: 'contra Costo Ingreso'},
      {nombre: 'contra Precio Lista'},
    ], val: "0", loading: false },
  },
  descuentoGlobalDefault: {id:0, descuento: 0, nota: null, remove: false },
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
  if(model.form.ini && model.form.fin && model.proveedor.val) {
    $.observable(model).setProperty('modoConsulta', false)
    $.observable(model.factura).setProperty('show', false)
    $.observable(model.factura).setProperty('msg', 'Cargando...')

    loadOptions(model.factura, '/api/boca/Factura/getFacturas', {ini: model.form.ini, fin: model.form.fin, proveedor_id: model.proveedor.val }, { placeholder: false })
    .then(() => {
      $.observable(model.factura).setProperty('msg', 'Calculando...')
      model.factura.items.forEach(factura =>{
        factura.items.forEach(prod => {
          $.observable(factura).setProperty('enabled', true)
          prod['parent'] = factura

          // Asignamos precio gsv
          precio_gsv = factura.preciosXProducto.items.find(item => item.id_prod == prod.id_prod && item.tipo == 'actual')
          $.observable(prod).setProperty('precio_gsv', precio_gsv?Number(precio_gsv.precio):0)

          //Asignamos descuentos
          let descArray = []
          factura.descuento.items.forEach(item => {
            let desc = factura.descuentosXProducto.items.find(desc => desc.id_descuento == item.id && desc.id_prod == prod.id_prod)
            if(desc){
              $.observable(desc).setProperty('descuento', Number(desc.descuento).toFixed(2))
              $.observable(desc).setProperty('sel', true)
              $.observable(desc).setProperty('posteriorCP', item.posteriorCP)
              $.observable(desc).setProperty('parent', prod)

              $.observe(desc, "sel", (ev, eventArgs) => { // Si hacen cambio en el porcentaje de descuentos, actualizamos el item y la factura
                $.observable(ev.currentTarget.parent).setProperty('ajusteBruto', null)
                updateValoresProducto(ev.currentTarget.parent)
                updateValoresFactura(ev.currentTarget.parent.parent)
              })
            }else{
              desc = { descuento: 0, sel: true, posteriorCP: item.posteriorCP }
            }
            descArray.push(desc)
          })
          $.observable(prod).setProperty('desc', descArray)
          $.observable(prod).setProperty('enabled', prod.precio_gsv && prod.costoReal)
          if(factura.enabled && !prod.enabled)
            $.observable(factura).setProperty('enabled', false)
          prod.cantidad = Number(prod.cantidad)
          $.observable(prod).setProperty('ajusteBruto', Number.parseFloat(prod.ajusteBruto)?Number.parseFloat(prod.ajusteBruto).toFixed(2):null)
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
      $( ".descuento" ).checkboxradio({ icon: false });
    })
  }
}

function toNeto(monto, item) {
  return (Number(monto) * (1 + Number(item.ieps)) + Number(item.litros) * Number(item.iepsxl)) * (1 + Number(item.iva))
}

function updateValoresProducto(item){
  $.observable(item).setProperty('valido', true) // Default todos son validos.
  if(item.enabled){

    const descuentoOff = (1 - item.desc.filter(desc => (desc.sel == true && desc.posteriorCP == 1)).reduce((suma, curItem) => suma + Number(curItem.descuento), 0) / 100)
    const descuento =    (1 - item.desc.filter(desc => (desc.sel == true && desc.posteriorCP == 0)).reduce((suma, curItem) => suma + Number(curItem.descuento), 0) / 100)

    // Costo Pactado
    $.observable(item).setProperty('costoPactado',  Number(item.precio_gsv) * descuento)
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

    const ajusteCalculadoRealBruto = ((item.costoFacturaBruto + ajusteProductosRechazadosBruto - costoCandidato) >= 1)?
                               Number((item.costoFacturaBruto + ajusteProductosRechazadosBruto - costoElegido).toFixed(2)):0

    // Ya calculados los costos y ajustes, vemos si hay que aplicar AJUSTES INGRESADOS A MANO
    if(item.ajusteBruto == null) {
      $.observable(item).setProperty("ajusteBruto", ajusteCalculadoRealBruto)
    }
    $.observable(item).setProperty('ajusteCalculadoNeto', Number(toNeto(Number(item.ajusteBruto), item).toFixed(2)))

    if(Number(item.ajusteBruto) != ajusteCalculadoRealBruto) { // El valor difiere del calculado, lo indicamos.
      $.observable(item).setProperty('touched', true)
    
      $.observable(item).setProperty('valido', flotanteValido(item.ajusteBruto) && Number(item.ajusteBruto) >= ajusteCalculadoRealBruto) // solo se permiten valores con ganancia

      // Se solicita que el cambio sea visual en costoIngreso
      $.observable(item).setProperty('costoIngresoBruto', item.costoIngresoBruto + (item.valido?Number(item.ajusteBruto):0))
      $.observable(item).setProperty('costoIngresoNeto', toNeto(item.costoIngresoBruto, item))
    } else {
      $.observable(item).setProperty('touched', false)
    }
  }
}

function flotanteValido (texto) {

  if (!/^\-?(\d+(\.\d*)?|\.\d+)$/.test(texto)) { return false; } // Comprobar que el texto coincida con el formato de número flotante (incluyendo .1)

  const numero = parseFloat(texto); // Convertir el texto a un número
  if (isNaN(numero) || Math.abs(numero) > 1000000000) { // Comprobar que sea un número válido y que no exceda el límite de 1,000,000,000
    return false;
  }

  return true;
}


function updateValoresFactura(item){
  $.observable(item).setProperty('totalCostoPactadoBruto', item.items.reduce((suma, curItem) => suma + Number(curItem.costoPactadoBruto?curItem.costoPactadoBruto:0), 0))
  $.observable(item).setProperty('totalCostoPactadoNeto', item.items.reduce((suma, curItem) => suma + Number(curItem.costoPactadoNeto?curItem.costoPactadoNeto:0), 0))
  
  $.observable(item).setProperty('totalCostoFacturaBruto', item.items.reduce((suma, curItem) => suma + Number(curItem.costoFacturaBruto?curItem.costoFacturaBruto:0), 0))
  $.observable(item).setProperty('totalCostoFacturaNeto', item.items.reduce((suma, curItem) => suma + Number(curItem.costoFacturaNeto?curItem.costoFacturaNeto:0), 0))
  $.observable(item).setProperty('totalCostoFacturaImpuestos', item.totalCostoFacturaNeto - item.totalCostoFacturaBruto)
  
  $.observable(item).setProperty('totalCostoIngresoBruto', item.items.reduce((suma, curItem) => suma + Number(curItem.costoIngresoBruto?curItem.costoIngresoBruto:0), 0))
  $.observable(item).setProperty('totalCostoIngresoNeto', item.items.reduce((suma, curItem) => suma + Number(curItem.costoIngresoNeto?curItem.costoIngresoNeto:0), 0))
  
  $.observable(item).setProperty('totalAjusteBruto', item.items.reduce((suma, curItem) => suma + Number(curItem.valido?curItem.ajusteBruto:0), 0))
  $.observable(item).setProperty('totalAjusteNeto', item.items.reduce((suma, curItem) => suma + Number(curItem.valido?curItem.ajusteCalculadoNeto:0), 0))
  $.observable(item).setProperty('totalAjusteImpuestos', item.totalAjusteNeto - item.totalAjusteBruto)

  // Ajustamos descuentoGlobal
  $.observable(item).setProperty('totalAjusteBruto', item.totalAjusteBruto + 
    item.descuentoGlobal.reduce((suma, curItem) => suma + (esNumeroFlotanteValido(curItem.descuento) && !curItem.remove?Number(curItem.descuento):0), 0))
  $.observable(item).setProperty('totalAjusteNeto', item.totalAjusteBruto + item.totalAjusteImpuestos)

  // Todos los items son validos?
  $.observable(item).setProperty('valida', 
    item.items.every(prod => prod.valido) 
    && item.descuentoGlobal.every(desc => esNumeroFlotanteValido(desc.descuento))
    && item.totalCostoFacturaNeto > 0)
}

function limpiarFiltro () {
  $.observable(model.form).setProperty('ini', null)
  $.observable(model.form).setProperty('fin', null)
  $.observable(model.proveedor).setProperty('val', null)
  $.observable(model).setProperty('modoConsulta', true)
}

function guardarCambios() {
  $.observable(model.factura).setProperty('loading', 'Guardando...')
  $.observable(model.factura).setProperty('show', false)
  items = model.factura.items.filter(factura => factura.sel && factura.valida).map(factura => { return {
    factura_id: factura.factura_id,
    ajuste: factura.totalAjusteNeto,
    descuentoGlobal: factura.descuentoGlobal.filter(desc => !desc.remove).map(desc => { return { id: desc.id, descuento: desc.descuento, nota: desc.nota}}),
    itemsID: factura.items.map(item => item.idpf).join(),
    ajusteBruto: factura.items.map(item => item.ajusteBruto).join()
  }})
  console.log(items)

  if(items.length) {
    $.post('/api/boca/Factura/storeAjustes', {factura: items}, (resp) => {
      maneja_errores(resp.error);
      updateListadoFactura()
      if(resp.result)
        alert('Cambios guardados.')
    }, 'json').fail(function() {
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

function exportarFacturaAExcel (factura) {
  var csv = 'Nombre del Producto, Precio Lista,'
  factura.descuento.items.filter(desc => desc.posteriorCP == 0).forEach(desc => csv += ' ' + desc.nombre + '(%),') 
  csv += ' Costo Pactado, Costo Factura, Diff %,' 
  factura.descuento.items.filter(desc => desc.posteriorCP == 1).forEach(desc => csv += ' ' + desc.nombre + '(%),') 
  csv += 'Costo Ingreso, U. de Compras, U. Rechazadas, Subtotal Factura, Subtotal Ingreso, Ajuste\n'
  
  factura.items.forEach(item => {
    csv += '"' + item.nombre + '",'
      + (item.precio_gsv?'"' + item.precio_gsv + '"':'') + ','
    item.desc.filter(desc => desc.posteriorCP == 0).forEach(desc => csv += (desc.descuento ? desc.descuento:'') + ',')
    csv += (item.costoPactado?item.costoPactado:'') + ','
     + (item.costoReal?item.costoReal:'') + ','
     + (item.diff?item.diff:'') + ','
    item.desc.filter(desc => desc.posteriorCP == 1).forEach(desc => csv += (desc.descuento ? desc.descuento:'') + ',')
    csv += (item.costoIngreso?item.costoIngreso:'') + ','
      + (item.cantidad?item.cantidad:'') + ','
      + (item.cantidad_rechazada?item.cantidad_rechazada:'') + ','
      + (item.costoFacturaBruto?item.costoFacturaBruto:'') + ','
      + (item.costoIngresoBruto?item.costoIngresoBruto:'') + ','
      + (item.ajusteBruto?item.ajusteBruto:'') + '\n'
  })

  // Descuentos Globales
  var espaciado = ',,,,,,,,,,'
  factura.descuento.items.forEach(desc => espaciado += ',')
  factura.descuentoGlobal.forEach(desc => csv += espaciado + ' '  + desc.descuento + ',"' + sanitizeString(desc.nota) + '"\n') 



  download('ValidacionDeCostos-' + model.proveedor.getNombre() + '- ' + factura.fecha + ' -' + factura.uuid + '.csv', csv)
}
