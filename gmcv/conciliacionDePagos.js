//Modelo, nuestra unica fuente de verdad
var model = {
  pago: { },
  pagoDefault: {id: 0, id_proveedor: 0, uuid: '', facturas: '', montos: '', total_usado: 0, tipo: 0, esUUIDDisponible: true },
  notaDeCredito: { },
  notaDeCreditoDefault: {id: 0, id_proveedor: 0, uuid: '', facturas: '', montos: '', total_usado: 0, tipo: 1, esUUIDDisponible: true },
  asignar: { },
  asignarDefault: {id_compra_factura: 0, monto: null, pendiente: 0},
  proveedor: { items: [], val: null, loading: false, getNombre: getNombreByID },
  pagos: { items: [], val: null, loading: false },
  notas: { items: [], val: null, loading: false },
  factura: { items: [], val: null, loading: false, show: false },
  facturasAlerta: { items: [], val: null, loading: false, diasFiltro: "14"},
  pendiente: {},
  form: {
    ini: null,
    fin: null,
    status: { items: [
      {id: 0, nombre: ' -- Todos --' },
      {id: 1, nombre: 'Aceptado' },
      {id: 4, nombre: 'Vigente' },
      {id: 23, nombre: 'Por Validar' }
      ], val: 0, loading: false },
    id: null,
    filtrarProveedores: true
  },
  UUIDTimer: null,
  readOnly: false,

  indexShow: false, // Pantallas
  agregarPagoDialogShow: false,
  asignarPagoDialogShow: false,
  editarNotaCreditoDialogShow: false
};

$(function () {

  // Observamos cambios en el modelo
  $.observe(model.proveedor, "val", (ev, eventArgs) => updatePagosYNotas())
  $.observe(model.form, "filtrarProveedores", (ev, eventArgs) => {
    if(model.form.filtrarProveedores){
      loadOptions(model.proveedor, '/api/boca/Proveedor/getProveedorConConciliacionesPendientes')
    } else {
      loadOptions(model.proveedor, '/api/boca/Proveedor/getItems')
    }
  })
	
  //Funciones extra:
  $.views.helpers({
    asignarPagoDialogShow: (id, ev, eventArgs) => asignarPagoDialogShow(id),
    editarNotaCreditoDialogShow: (index, ev, eventArgs) => editarNotaCreditoDialogShow(index),
    borrarRelacionPagoFactura: (index, ev, eventArgs) => borrarRelacionPagoFactura(index),
    semaforo: (dias) => {
      if(dias > 0)         return 'red'
      else if (dias > -14) return 'yellow'
      else                 return ''
    },
    filtraFacturasAlerta: (item, index, items) => Number(model.facturasAlerta.diasFiltro)?(item.dias_vecimiento <= model.facturasAlerta.diasFiltro):true,
  })
  $.views.helpers.filtraFacturasAlerta.depends = ["facturasAlerta.diasFiltro"]

  // Leemos los parametros en Get
  const params = new URLSearchParams(window.location.search);
  $.observable(model).setProperty('readOnly', params.has('readOnly'))

  if(params.has('id_prov') && params.has('ini') && params.has('fin')) {
    setTimeout(() => { //Esperamos a que se cargue la UI, antes de cargar el formulario
      $.observable(model.proveedor).setProperty('val', params.get('id_prov'))
      $.observable(model.form).setProperty('ini', params.get('ini'))
      $.observable(model.form).setProperty('fin', params.get('fin'))
      updateListadoFactura()
    }, 500)
  }


	indexShow() // Cargamos la pantalla de indice

	var contenidoTmpl = $.templates("#contenidoTmpl"),
	  pageOptions = {};
	contenidoTmpl.link("#contenido", model, { page: pageOptions });
});

function indexShow() {
	
  if(model.form.filtrarProveedores){
    loadOptions(model.proveedor, '/api/boca/Proveedor/getProveedorConConciliacionesPendientes')
  } else {
    loadOptions(model.proveedor, '/api/boca/Proveedor/getItems')
  }
  
  setTimeout(() => { // Luego de un segundo revisamos si hay facturas alertadas.
    loadOptions(model.facturasAlerta, '/api/boca/Factura/getFacturasAlerta', {}, { placeholder: false })
  }, 1000)
	
  $.observable(model).setProperty("indexShow", true);
}

function updatePagosYNotas() {
  if(model.proveedor.val) {
    loadOptions(model.pagos, '/api/boca/Pago/getPagosConSaldo', {id_proveedor: model.proveedor.val})
    loadOptions(model.notas, '/api/boca/Pago/getPagosConSaldo', {id_proveedor: model.proveedor.val, tipo: 1})
  } else {
    $.observable(model.pagos).setProperty("items", [])
    $.observable(model.notas).setProperty("items", [])
  }
}

function updateListadoFactura() {
  if(model.form.ini && model.form.fin && model.proveedor.val) {
    $.observable(model.factura).setProperty('show', false)
    loadOptions(model.factura, '/api/boca/Factura/getFacturasConciliar', {
      proveedor_id: model.proveedor.val,
      ini: model.form.ini, 
      fin: model.form.fin,
      status: model.form.status.val,
      pago: model.form.id
    }, { placeholder: false })
    .then(()=>{
      // Calculamos pendiente.
      let id_compra_factura = 0
      let abonado = 0
      model.factura.items.forEach((factura) => {
        if(id_compra_factura != factura.id_compra_factura) {
          abonado = 0
          id_compra_factura = factura.id_compra_factura
        }
        abonado = abonado + Number(factura.monto_abono)
        $.observable(factura).setProperty('pendiente', Number(factura.autorizado) - abonado)

        //Semaforo, agrupamos 
        $.observable(model.pendiente).setProperty(id_compra_factura.toString(), factura.pendiente)
      })
        $.observable(model.factura).setProperty('show', true)
    })
  }
}

function limpiarFiltro () {
  $.observable(model.proveedor).setProperty('val', null)
  $.observable(model.form).setProperty('ini', null)
  $.observable(model.form).setProperty('fin', null)
  $.observable(model.status).setProperty('val', 0)
  $.observable(model.form).setProperty('id', null)
}

function exportarAExcel() {
    var csv = 'Fecha, ID de Factura, Bodega, Costo Real, Ajuste, Autorizado, Fecha Compromiso, Fecha Pagado, Id Pago, Monto Pago, Pendiente\n'

  model.factura.items.forEach(item =>{
    csv += (item.llegada?item.llegada:'') + ','
      + (item.factura?item.factura:'') + ','
      + (item.bodega_nombre?item.bodega_nombre:'') + ','
      + (item.costoReal?item.costoReal:'') + ','
      + (item.ajuste?item.ajuste:'') + ','
      + (item.autorizado?item.autorizado:'') + ','
      + (item.fecha_compromiso?item.fecha_compromiso:'') + ','
      + (item.fecha_abono?item.fecha_abono:'') + ','
      + (item.uuid?item.uuid:'') + ','
      + (item.monto_abono?item.monto_abono:'') + ','
      + (item.pendiente?item.pendiente:'') + '\n'
  })

  download('ConciliacionDePagos-' + model.proveedor.getNombre() + '-' + model.form.ini + '.csv', csv)
}


function agregarPagoDialogShow(index = null) {

  if(index === null){
    if(model.proveedor.val){
      $.observable(model).setProperty("pago", Object.assign({}, model.pagoDefault))
      $.observable(model.pago).setProperty("fecha", model.form.ini)
      $.observable(model.pago).setProperty("id_proveedor", model.proveedor.val)
    }else{
      alert('Escoja un proveedor primero.')
      return false
    }
  }
  else {
    $.observable(model).setProperty("pago", Object.assign({}, model.pagos.items[index]))
    $.observable(model.pago).setProperty("esUUIDDisponible", true)
  }
  $.observe(model.pago, "uuid", (ev, eventArgs) => pagoUUIDDisponible(model.pago))


  $.observable(model).setProperty("agregarPagoDialogShow", true);
  let agregarPagoDialog = $('#agregarPagoDialog').dialog({ // Mas info en: https://jqueryui.com/dialog/#modal-form
      title: index === null?'Agregando Pago':'Editando Pago: ' + model.item.uuid,
      autoOpen: true,
      // height: 400,
      width: 450,
      modal: true,
      buttons: { 
        "Guardar": function() {
          $(this).addClass('btn btn-success');
          guardarPago()
          .then(() => agregarPagoDialog.dialog( "close" ));
        },
        "Cerrar": () => { agregarPagoDialog.dialog( "close" ) }
      },
      close: () => { 
        $.observable(model).setProperty('agregarPagoDialogShow', false)
        $.observable(model).setProperty("pago", Object.assign({}, model.pagoDefault))
      },
      create: function() {
        $(this).closest(".ui-dialog")
               .find(".ui-button:contains('Cerrar')") 
               .addClass("btn btn-danger"); 
        $(this).closest(".ui-dialog")
                .find(".ui-button:contains('Guardar')") 
                .addClass("btn btn-primary"); 
      }
    });
}

function guardarPago() {
  var d = new $.Deferred   //Promesa

  if(!esNumeroFlotanteValido(model.pago.monto)) {
    alert('El monto tiene un valor invalido, revise antes de guardarlo')
    d.reject()

  } else if (!model.pago.uuid  || model.pago.uuid.length > 36) {
    alert('Introduzca un UUID valido.')
    d.reject()
  
  } else if (!model.pago.esUUIDDisponible) {
    alert('Este UUID ya se encuentra registrado.')
    d.reject()

  } else {
    $.post('/api/boca/Pago/store', model.pago, (resp) => {
      maneja_errores(resp.error)
      updateListadoFactura()
      updatePagosYNotas()
      d.resolve()
    }, 'json').fail(function() {
      alert("Error al guardar los cambios en el servidor.")
      d.reject()
    });
  }
  return d
}



// ################################################################ Notas de Credito

function editarNotaCreditoDialogShow() {

  if(model.proveedor.val){
    $.observable(model).setProperty("notaDeCredito", Object.assign({}, model.notaDeCreditoDefault))
    $.observable(model.notaDeCredito).setProperty("fecha", model.form.ini)
    $.observable(model.notaDeCredito).setProperty("id_proveedor", model.proveedor.val)
  }else{
    alert('Escoja un proveedor primero.')
    return false
  }

  $.observe(model.notaDeCredito, "uuid", (ev, eventArgs) => pagoUUIDDisponible(model.notaDeCredito))
  
  $.observable(model).setProperty("editarNotaCreditoDialogShow", true);
  let editarNotaCreditoDialog = $('#editarNotaCreditoDialog').dialog({ // Mas info en: https://jqueryui.com/dialog/#modal-form
      title: 'Agregando Nota de Credito',
      autoOpen: true,
      // height: 400,
      width: 450,
      modal: true,
      buttons: { 
        "Guardar": () => {
          guardarNotaDeCredito()
          .then(() => editarNotaCreditoDialog.dialog( "close" ))
        },
        "Cerrar": () => { editarNotaCreditoDialog.dialog( "close" ) }
      },
      close: () => { 
        $.observable(model).setProperty('editarNotaCreditoDialogShow', false)
        $.observable(model).setProperty("notaDeCredito", Object.assign({}, model.notaDeCreditoDefault))
      },
      create: function() {
        $(this).closest(".ui-dialog")
               .find(".ui-button:contains('Cerrar')") 
               .addClass("btn btn-danger"); 
        $(this).closest(".ui-dialog")
                .find(".ui-button:contains('Guardar')") 
                .addClass("btn btn-primary"); 
      }
    });
}

function guardarNotaDeCredito() {
  var d = new $.Deferred   //Promesa

  if(!esNumeroFlotanteValido(model.notaDeCredito.monto)) {
    alert('El monto tiene un valor invalido, revise antes de guardarlo')
    d.reject()

  } else if (!model.notaDeCredito.uuid  || model.notaDeCredito.uuid.length > 36) {
    alert('Introduzca un UUID valido.')
    d.reject()

  } else if (!model.notaDeCredito.esUUIDDisponible) {
    alert('Este UUID ya se encuentra registrado.')
    d.reject()

  } else {
    $.post('/api/boca/Pago/store', model.notaDeCredito, (resp) => {
      maneja_errores(resp.error)
      updateListadoFactura()
      updatePagosYNotas()
      d.resolve()
    }, 'json').fail(function() {
      alert("Error al guardar los cambios en el servidor.")
      d.reject()
    });
  }
  return d
}



// ################################################################ Asignar Pago/Nota de Credito a Factura

function asignarPagoDialogShow(index) {

  if(!model.notas.items.length && !model.pagos.items.length) { //No ni un solo pago, ni una sola nota disponible.
    alert('No hay Pagos ni Notas de Credito Disponibles. Agregue uno para poderlo asignar a la factura.')
    return
  }


  $.observable(model).setProperty("asignar", Object.assign({}, model.asignarDefault))
  $.observable(model.asignar).setProperty("id_compra_factura", model.factura.items[index].id_compra_factura)
  $.observable(model.asignar).setProperty("pendiente", model.factura.items[index].pendiente)
  $.observable(model.pagos).setProperty("val", null)
  $.observable(model.notas).setProperty("val", null)

  
  $.observable(model).setProperty("asignarPagoDialogShow", true);
  let asignarPagoDialog = $('#asignarPagoDialog').dialog({ // Mas info en: https://jqueryui.com/dialog/#modal-form
      title: 'Asignando Pago a Factura',
      autoOpen: true,
      // height: 400,
      width: 600,
      modal: true,
      buttons: { 
        "Guardar": () => {
          if(Number(model.asignar.monto) == 0) {
            alert('El monto no puede ser cero.')
            return
          } else if (Number(model.asignar.monto) > Number(model.asignar.pendiente)){
            alert ('El monto supera al pendiente de la factura: ' + Number(model.asignar.monto) + ' vs ' + Number(model.asignar.pendiente))
          } else {
            if(model.pagos.val){
              if(Number(model.pagos.items[model.pagos.val].restante) < Number(model.asignar.monto))
                  alert ('El monto supera a lo disponible en este pago')
                else {
                  guardarRelacionPagoFactura()  // Guardamos
                    .then(() => asignarPagoDialog.dialog( "close" ))
                }
              
            } else if(model.notas.val){
                if(Number(model.notas.items[model.notas.val].restante) < Number(model.asignar.monto))
                  alert ('El monto supera a lo disponible en esta nota de credito')
                else {
                  guardarRelacionPagoFactura()  // Guardamos
                    .then(() => asignarPagoDialog.dialog( "close" ))
                }
            } else {
              alert ('Tiene que escoger un Pago o una Nota de Credito')
            }
          } 
        },
        "Cerrar": () => { asignarPagoDialog.dialog( "close" ) }
      },
      close: () => { 
        $.observable(model).setProperty('asignarPagoDialogShow', false)
        $.observable(model).setProperty("item", Object.assign({}, model.notaDeCreditoDefault))
      },
      create: function() {
        $(this).closest(".ui-dialog")
               .find(".ui-button:contains('Cerrar')") 
               .addClass("btn btn-danger"); 
        $(this).closest(".ui-dialog")
                .find(".ui-button:contains('Guardar')") 
                .addClass("btn btn-primary"); 
      }
    });
}

function guardarRelacionPagoFactura() {
  var d = new $.Deferred   //Promesa
  const data = {
    id_compra_factura: model.asignar.id_compra_factura,
    monto: model.asignar.monto
  }
  if(model.pagos.val) {
      data['id_pago'] = model.pagos.items[model.pagos.val].id
  }else if (model.notas.val) {
      data['id_pago'] =  model.notas.items[model.notas.val].id
  } else {
    // Algo muy extraño acaba de ocurrir
    d.reject()
  }

  console.log(data)
  $.post('/api/boca/Pago/storeMontoAsignadoAFactura', data, (resp) => {
    maneja_errores(resp.error)
    updateListadoFactura()
    updatePagosYNotas()
    d.resolve()
  }, 'json').fail(() => {
    alert("Error al guardar los cambios en el servidor.")
  })
  return d
}

function borrarRelacionPagoFactura(index) {
  var d = new $.Deferred   //Promesa
  if(!confirm('Está seguro que desea borrar este pago?')) {
    d.resolve()
  } else {
    $.post('/api/boca/Pago/removeMontoAsignadoAFactura', { id_abono: model.factura.items[index].id_abono }, (resp) => {
      maneja_errores(resp.error)
      updateListadoFactura()
      updatePagosYNotas()
      d.resolve()
    }, 'json').fail(() => {
      alert("Error al guardar los cambios en el servidor.")
    })
  }
  return d
}

// Esperamos a que deje de tipear para hacer la busqueda
function pagoUUIDDisponible(pago) {
  clearTimeout(model.UUIDTimer) // Si acaba de ocurrir el evento, reiniciamos el contador
  
  model.UUIDTimer = setTimeout(() => {
    // Consultamos en DB
    $.post('/api/boca/Pago/UUIDDisponible', {prov_id: model.proveedor.val, uuid: pago.uuid, pago_id: pago.id },
      (resp) => {
        maneja_errores(resp.error)
        $.observable(pago).setProperty('esUUIDDisponible', resp.result)
      }, 'json').fail(() => {
        console.log('Error comunicandonos con el servidor')
      });
  }, 500) //Esperamos un tiempo despues de typear para no saturar al servidor
}