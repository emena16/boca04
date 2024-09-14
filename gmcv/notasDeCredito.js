//Modelo, nuestra unica fuente de verdad
var model = {
  notas: { items: [], val: null, loading: false, show: false },
  item: { },
  proveedor: { items: [], val: null, loading: false },
  form: {
    ini: toIsoString(new Date()).substr(0,10),
    fin: toIsoString(new Date()).substr(0,10),
    monto: null,
    id: null
  },
  notaDeCreditoDefault: {id: 0, id_proveedor: 0, uuid: '', facturas: '', montos: '', total_usado: 0, tipo: 1, esUUIDDisponible: true },
  UUIDTimer: null,
  readOnly: false,
  
  indexShow: false, // Pantallas
  editarNotaCreditoDialogShow: false
};

$(function () {
  
  $.views.helpers({
    editarNotaCreditoDialogShow: (index, ev, eventArgs) => editarNotaCreditoDialogShow(index) 
  })

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

function updateListadoNotas() {
  if(model.form.ini && model.form.fin && model.proveedor.val) {
    loadOptions(model.notas, '/api/boca/Pago/getPagos', {
      ini: model.form.ini, 
      fin: model.form.fin,
      proveedor_id: model.proveedor.val,
      tipo: 1
    }, { placeholder: false })
    $.observable(model.notas).setProperty('show', true)
  }
}

function limpiarFiltro () {
  $.observable(model.form).setProperty('ini', null)
  $.observable(model.form).setProperty('fin', null)
  $.observable(model.proveedor).setProperty('val', null)
  $.observable(model.notas).setProperty('show', false)
}


function editarNotaCreditoDialogShow(index = null) {

  if(index === null) {
    $.observable(model).setProperty("item", Object.assign({}, model.notaDeCreditoDefault))
    $.observable(model.item).setProperty("fecha", model.form.ini)
    $.observable(model.item).setProperty("id_proveedor", model.proveedor.val)
  } else {
    $.observable(model).setProperty("item", Object.assign({}, model.notas.items[index]))
    $.observable(model.item).setProperty("esUUIDDisponible", true)
  }
  $.observe(model.item, "uuid", (ev, eventArgs) => pagoUUIDDisponible())

	$.observable(model).setProperty("editarNotaCreditoDialogShow", true);
	let editarNotaCreditoDialog = $('#editarNotaCreditoDialog').dialog({ // Mas info en: https://jqueryui.com/dialog/#modal-form
      title: index === null?'Agregando Nota de Credito':'Editando Nota: ' + model.item.uuid,
      autoOpen: true,
      // height: 400,
      width: 450,
      modal: true,
      buttons: { 
        "Guardar": () => {
          guardarCambios()
          .then(() => editarNotaCreditoDialog.dialog( "close" ))
        },
        "Cerrar": () => { editarNotaCreditoDialog.dialog( "close" ) }
      },
      close: () => { 
        $.observable(model).setProperty('editarNotaCreditoDialogShow', false)
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

// Esperamos a que deje de tipear para hacer la busqueda
function pagoUUIDDisponible() {
  clearTimeout(model.UUIDTimer) // Si acaba de ocurrir el evento, reiniciamos el contador
  
  model.UUIDTimer = setTimeout(() => {
    // Consultamos en DB
    $.post('/api/boca/Pago/UUIDDisponible', {prov_id: model.proveedor.val, uuid: model.item.uuid, pago_id: model.item.id },
      (resp) => {
        maneja_errores(resp.error)
        $.observable(model.item).setProperty('esUUIDDisponible', resp.result)
      }, 'json').fail(() => {
        console.log('Error comunicandonos con el servidor')
      });
  }, 500) //Esperamos un tiempo despues de typear para no saturar al servidor
}


function exportarAExcel() {
    var csv = 'Proveedor, Fecha, Monto, Id Nota de Credito, Factura\n'

  model.notas.items.forEach(item =>{
    csv += (item.proveedor?item.proveedor:'') + ','
      + (item.fecha?item.fecha:'') + ','
      + (item.monto?item.monto:'') + ','
      + (item.uuid?item.uuid:'') + ','
      + (item.facturas?item.facturas:'') + '\n'
  })

  download('NotasDeCredito-' + model.notas.items[0].proveedor + '-' + model.form.ini + '.csv', csv)
}

function guardarCambios() {
  var d = new $.Deferred   //Promesa

  if(!esNumeroFlotanteValido(model.item.monto)) {
    alert('El monto tiene un valor invalido, revise antes de guardarlo')
    d.reject()

  } else if (!model.item.uuid  || model.item.uuid.length > 36) {
    alert('Introduzca un UUID valido.')
    d.reject()

  } else if (!model.item.esUUIDDisponible) {
    alert('Este UUID ya se encuentra registrado.')
    d.reject()

  } else {
    $.post('/api/boca/Pago/store', model.item, (resp) => {
      maneja_errores(resp.error)
      updateListadoNotas()
      d.resolve()
    }, 'json').fail(function() {
      alert("Error al guardar los cambios en el servidor.");
      d.reject()
    });
  }
  return d
}