//Modelo, nuestra unica fuente de verdad
var model = {
  bodega: { items: [{id: null, nombre: 'Elija un proveedor'}], val: null, loading: false, getNombre: getNombreByID },
  proveedor: { items: [], val: null, loading: false, getNombre: getNombreByID },
  descuento: { items: [], val: null, loading: false },
  descuentoInactivo: { items: [], val: null, loading: false },
  historialDescuentos: { items: [], val: null, loading: false },
  descuentoSeleccionado: {id: 0, id_status: 4, nombre: '', posteriorCP: false, bodegasSel: [] },
  descuentoDefault: {id: 0, id_status: 4, nombre: '', posteriorCP: false, bodegasSel: [] },
  descuentoXProducto: { items: [], val: null, loading: false },
  descuentoBodegas: { items: [], val: null, loading: false }, // Las bodegas a las que está relacionado el descuento.
  producto: { items: [], val: null, loading: false },
  producto_tabla: { items: [], val: null, loading: false },
  precios: { items: [], val: null, loading: false },
  historialPrecios: { items: [], val: null, loading: false },
  modoConsulta: true,
  form: {  
    fecha: toIsoString(new Date()).substr(0,10),
    mostrarFecha: true
  },
  readOnly: false,
  
  indexShow: false, // Pantallas
  editarDescuentoDialogShow: false,
  historialPreciosProdDialogShow: false
};



$(function () {
  // Observamos cambios en el modelo
  $.observe(model.proveedor, "val", (ev, eventArgs) => {
    loadOptions(model.bodega, '/api/boca/Bodega/getItems', {id_prov: model.proveedor.val})
    updateListadoProducto()
  })
  $.observe(model.bodega, "val", (ev, eventArgs) => updateListadoProducto())
  $.observe(model.form, "fecha", (ev, eventArgs) => updateListadoProducto())
  $.observe(model.descuentoInactivo, "val", (ev, eventArgs) => {
    console.log('Se actualizó val descuentoInactivo')
    $.observable(model).setProperty("descuentoSeleccionado", Object.assign({}, model.descuentoInactivo.items[model.descuentoInactivo.val]))
    $.observable(model.descuentoSeleccionado).setProperty("nuevoOreactivar", true)

    //Cargamos las bodegas a las que está relacionado el descuento:
    loadOptions(model.descuentoBodegas, '/api/boca/Descuento/getDescuentoBodegas', {'descuento_id': model.descuentoSeleccionado.id }, { placeholder: false })
    .then(() => {
      $.observable(model.descuentoSeleccionado).setProperty("bodegasSel", model.descuentoBodegas.items)
    })
  })


  // Funciones extras
  $.views.helpers({
    editarDescuentoDialogShow: (id, ev, eventArgs) => editarDescuentoDialogShow(id),
    historialPreciosProdDialogShow: (index, ev, eventArgs) => historialPreciosProdDialogShow(index),
    antesCP: (item, index, items) => (item.posteriorCP == 0), // Filtros
    despuesCP: (item, index, items) => (item.posteriorCP == 1)
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

// ################################################################  Las funciones calculadas al vuelo en la tabla
function enabled () {
  return this.precio_lista.precio
}
enabled.depends = ["precio_lista.precio"]

function costoPactado () {
  return this.enabled()?
    (1 - this.desc.filter(desc => (desc.posteriorCP == 0)) // Solo seleccionados y antes de CP
      .reduce((suma, curItem) => suma + Number(curItem.descuento), 0) / 100) * Number(this.precio_lista.precio)
    :null
}
costoPactado.depends = ["enabled()", "precio_lista.precio", "desc.**"]

function bruto () {
  return this.enabled()?
    (1 - this.desc.filter(desc => (desc.posteriorCP == 1)) // Solo seleccionados y despues de CP
      .reduce((suma, curItem) => suma + Number(curItem.descuento), 0) / 100) * this.costoPactado()
    :null
}
bruto.depends = ["enabled()", "costoPactado()", "desc.**"]

function neto () {
  return this.enabled()? (Number(this.bruto()) * (1 + this.ieps) + this.litros * this.iepsxl) * (1 + this.iva) :null
                                   //(gsv.precio * (1 + si.ieps) +  p.litros * si.iepsxl) * (1 + si.iva) -- version sql de rive acomodado
}
neto.depends = ["bruto()"]



// ###############################################################################Actualizamos el listado de productos (ver3)
function updateListadoProducto() {
  if(model.proveedor.val && model.bodega.val && model.form.fecha) {

    $.observable(model.producto_tabla).setProperty('loading', "Cargando...")
    $.observable(model.producto).setProperty('loading', true)
    $.observable(model.descuentoXProducto).setProperty('loading', true) // Los colocamos en loading a mano.
    $("#mostrarFecha").checkboxradio({ icon: false }) // Lo hacemos botón
    const data = {
      'proveedor_id': model.proveedor.val,
      'bodega_id': model.bodega.val, //Arreglo de bodegas seleccionadas
      'fecha': model.form.fecha // Fecha actual
    }

    // Descargamos los descuentos
    const promise0 = loadOptions(model.descuento, '/api/boca/Descuento/getDescuentosDisponibles', data, { placeholder: false })
      .then(() => { // Esperamos a que se carguen los datos para filtrarlos
        $.observable(model.descuentoInactivo.items).refresh(model.descuento.items.filter(item => item.id_status != 4));
        $.observable(model.descuento.items).refresh(model.descuento.items.filter(item => item.id_status == 4));
      })
    // Descargamos los productos
    const promise1 = loadOptions(model.producto, '/api/boca/Producto/getProductosCompra', data, { placeholder: false })
    // Descargamos los precios
    const promise2 = loadOptions(model.precios, '/api/boca/PrecioLista/getPrecios', data, { placeholder: false })
    
    Promise.all([promise0, promise1, promise2]).then(()=> {
      $.observable(model.producto_tabla).setProperty('loading', "Calculando...")

      // Requerimos el listado de Descuentos en pantalla para aplicar el filtro
      data['descuento_id'] = model.descuento.items.map(item => item.id)

      loadOptions(model.descuentoXProducto, '/api/boca/Descuento/getDescuentosXProducto', data, { placeholder: false })
      .then(() => {
        model.producto.items.forEach(item => {

          // ###################################################################################Asignamos Precios y Fechas
          let precio_lista = null
          let precio_futuro = null
          let descuentos = null
          item.bodegas.forEach((bodega, index) => { // Obtenemos el dato de cualquiera de las primeras bodegas
            if(!precio_lista) {
              precio_lista = model.precios.items.find(precio => (precio.tipo == 'actual' && precio.id_prod == item.id && precio.id_bodega == bodega))
              if(precio_lista)
                precio_lista['mismoValor'] = (index== 0)? true : false;
            } else {                                                                    //Ya se tiene un valor, es el mismo?
              if(precio_lista.mismoValor) { //Solo buscamos hasta encontrar uno falso
                precio = model.precios.items.find(precio => (precio.tipo == 'actual' && precio.id_prod == item.id && precio.id_bodega == bodega))
                if(precio) {
                  if(precio.precio != precio_lista.precio)
                    precio_lista.mismoValor = false
                } else
                  precio_lista.mismoValor = false
              }
            }
            if(!precio_futuro)
              precio_futuro = model.precios.items.find(precio => (precio.tipo == 'futuro' && precio.id_prod == item.id && precio.id_bodega == bodega))
          })

          // Si ninguna bodega lo tuvo, mismoValor = true
          if(!precio_lista)
            precio_lista = {id_bodega: 0, id_prod: item.id, tipo: 'actual', precio: null, touched: false, mismoValor: true}
          else
            precio_lista.precio = precio_lista.precio.toFixed(2)
          if(!precio_futuro)
            precio_futuro = {id_bodega: 0, id_prod: item.id, tipo: 'futuro', precio: null, touched: false, mismoValor: true}

          // Observamos si hay cambios en el precio_lista
          $.observe(precio_lista, "precio", (ev, eventArgs) => {
            $.observable(ev.target).setProperty("touched", true)
            if(isNaN(Number.parseFloat(ev.target.precio)) ||   ev.target.precio > 1000000 || ev.target.precio < 0)
              alert('Revise que el precio sea correcto y esté dentro de los limites validos.')
          })

          $.observable(item).setProperty('precio_lista', precio_lista)
          $.observable(item).setProperty('precio_futuro', precio_futuro)

          // ################################################################################### Asignamos descuentos
          let descArray = []
          model.descuento.items.forEach(desc => { // Ordenados por el orden original de los descuentos
            let descuento = null
            item.bodegas.forEach((bodega, index) => { // Obtenemos el dato de cualquiera de las primeras bodegas
              if(!descuento) {
                descuento = model.descuentoXProducto.items.find(descXItem => (descXItem.id_prod == item.id && descXItem.id_descuento == desc.id && descXItem.id_bodega == bodega))
                if(descuento) {
                  descuento['mismoValor'] = (index== 0)? true : false;
                  descuento['posteriorCP'] = desc.posteriorCP // Lo usaremos para filtrarlo
                }
              } else {
                if(descuento.mismoValor) { //Solo buscamos hasta encontrar uno falso
                  descTemp = model.descuentoXProducto.items.find(descXItem => (descXItem.id_prod == item.id && descXItem.id_descuento == desc.id && descXItem.id_bodega == bodega))
                  if(descTemp){
                    if(descTemp.descuento != descuento.descuento)
                      descuento.mismoValor = false
                  } else
                    descuento.mismoValor = false
                }
              }
            })
            // Si ninguna bodega tuvo un valor para este descuento, mismoValor = true
            if(!descuento)
              descuento = {id_descuento: desc.id, id_prod: item.id, descuento: null, id_bodega: 0, ini:'', posteriorCP: desc.posteriorCP, touched: false, mismoValor: true}

            //Observamos si cambia al valor del descuento
            $.observe(descuento, "descuento", (ev, eventArgs) => {
              $.observable(ev.target).setProperty("touched", true)
              if(isNaN(Number.parseFloat(ev.target.descuento)) || ev.target.descuento > 1000 || ev.target.descuento < 0)
                alert('Revise que el valor del descuento sea correcto y enté dentro de los limites validos')
            })
            descArray.push(descuento)
          })
          $.observable(item).setProperty('desc', descArray)          

          // Los valores calculados al vuelo
          item['enabled'] = enabled
          item['costoPactado'] = costoPactado
          item['bruto'] = bruto
          item['neto'] = neto
        })

        //$.observe(model.producto.items,"precio_lista.precio", (ev, eventArgs) => console.log(ev, eventArgs))
        $.observable(model.producto_tabla).setProperty('items', model.producto.items)
        $.observable(model.producto).setProperty("val", null); //// Eliminamos el producto actual.
        $.observable(model.producto_tabla).setProperty('loading', false)
      })
    })
  }
}

// Entramos en modo Edicion
function entrarModoEdicion() {
  if(model.modoConsulta) { //Entramos en modo Edición
    $.observable(model).setProperty("modoConsulta", false);
  } else { // Regresamos a modo Consulta
    if(confirm('Desea descartar cualquier cambio realizado?')){
      $.observable(model).setProperty("modoConsulta", true);
      updateListadoProducto()
    }
  }
}

// ################################################################ DESCUENTOS

function editarDescuentoDialogShow(id = null) {
  if(!model.modoConsulta) {
    if(!confirm('Si continua se podrían perder los cambios realizados. ¿Desea continuar?'))
      return
  }

  // Creamos una copia nueva del descuento seleccionado o default,
  // Al guardar, enviamos la data a DB y recargamos descuentos.
  if(id === null){
    $.observable(model).setProperty("descuentoSeleccionado", Object.assign({}, model.descuentoDefault))
    $.observable(model.descuentoSeleccionado).setProperty("nuevoOreactivar", true)
    $.observable(model.descuentoSeleccionado).setProperty("bodegasSel", model.bodega.val) // Al ser nuevo, preseleccionamos las bodegas actuales.
  } else {
    let item = model.descuento.items.find(desc => desc.id == id)
    if(item) {
      $.observable(model).setProperty("descuentoSeleccionado", Object.assign({}, item))

      //Cargamos las bodegas a las que está relacionado el descuento:
      loadOptions(model.descuentoBodegas, '/api/boca/Descuento/getDescuentoBodegas', {'descuento_id': model.descuentoSeleccionado.id }, { placeholder: false })
      .then(() => {
        $.observable(model.descuentoSeleccionado).setProperty("bodegasSel", model.descuentoBodegas.items)
      })
    } else {
      alert('Imposible editar este descuento. Recargue pantalla.')
      return
    }
  }
  
  $.observable(model).setProperty("editarDescuentoDialogShow", true);
	let editarDescuentoDialog = $('#editarDescuentoDialog').dialog({ // Mas info en: https://jqueryui.com/dialog/#modal-form
      title: id === null?'Agregando/Reactivando Descuento':'Editando descuento: ' + model.descuentoSeleccionado.nombre,
      autoOpen: true,
      // height: 400,
      width: 500,
      modal: true,
      buttons: { 
        "Guardar": () => {
          if(model.descuentoSeleccionado.nombre.length) {
            guardarCambiosDescuentos()
            .then(() => editarDescuentoDialog.dialog( "close" ))
          } else {
            alert('El nombre del descuento está vacío.')
          }
        },
        
        "Cerrar": () => { editarDescuentoDialog.dialog( "close" ) }
      },
      close: () => { 
        $.observable(model).setProperty('editarDescuentoDialogShow', false) 
        $.observable(model.descuento).setProperty('val', null)
        $.observable(model.descuentoInactivo).setProperty('val', null)
      }
    });
}

// Guarda los cambios realizados a los descuentos.
function guardarCambiosDescuentos() {
  var d = new $.Deferred   //Promesa
  $.observable(model.descuentoSeleccionado).setProperty("GUIStatus","Guardando...")

  if(model.descuentoSeleccionado.bodegasSel.length < 1) { // Necesita tener al menos una bodega seleccionada, de lo contrario nunca se va a volver a  mostrar.
    alert('Necesita tener al menos una bodega seleccionada. Puede dejarlo inactivo si no quiere que se muestre.')
    $.observable(model.descuentoSeleccionado).setProperty("GUIStatus", null)
    d.reject()
  } else {
    if(!model.descuentoSeleccionado.id)
      $.observable(model.descuentoSeleccionado).setProperty('id_prov', model.proveedor.val)

    $.post('/api/boca/Descuento/store', model.descuentoSeleccionado, function (resp) {
      maneja_errores(resp.error)
      $.observable(model.descuentoSeleccionado).setProperty("GUIStatus","Listo!")
      updateListadoProducto()
      d.resolve()
    }, 'json').fail(function() {
      alert("Error al guardar los cambios en el servidor.");
      $.observable(model.descuentoSeleccionado).setProperty("GUIStatus","Error!")
      d.reject()
    })
  }
  return d
}

// Muestra el dialogo de Historial de Precios
function historialPreciosProdDialogShow (index) {
  // Definimos el producto seleccionado
  $.observable(model.producto).setProperty("val", model.producto.items[index])

  loadOptions(model.historialPrecios, '/api/boca/PrecioLista/getHistorialPrecios', {
      'proveedor_id': model.proveedor.val,
      'bodega_id': model.bodega.val[0], // Solo enviamos el primero
      'producto_id': model.producto.val.id
    },{ placeholder: false })

	$.observable(model).setProperty("historialPreciosProdDialogShow", true);
	let historialPreciosProdDialog = $('#historialPreciosProdDialog').dialog({ // Mas info en: https://jqueryui.com/dialog/#modal-form
      title: 'Historial de precios de: ' + model.producto.val.nombre,
      autoOpen: true,
      // height: 400,
      width: 450,
      modal: true,
      buttons: {"Cerrar": () => { historialPreciosProdDialog.dialog( "close" ) } },
      close: () => {$.observable(model).setProperty('historialPreciosProdDialogShow', false) }
    });
}

function productosDescargarExcel() {
  var csv = 'Nombre del Producto, Precio Lista (GSV), Fecha Inicio, Fecha Fin,'
    model.descuento.items.filter(desc => desc.posteriorCP == 0).forEach(desc => csv += ' ' + desc.nombre + '(%),') 
    csv += ' Costo Pactado,' 
    model.descuento.items.filter(desc => desc.posteriorCP == 1).forEach(desc => csv += ' ' + desc.nombre + '(%),') 
    csv += 'Bruto, Neto\n'

  model.producto_tabla.items.forEach(item =>{
    csv += '"' + item.nombre + '",'
      + (item.precio_lista.precio?item.precio_lista.precio:'') + ','
      + (item.precio_lista.ini?item.precio_lista.ini:'') + ','
      + (item.precio_futuro.ini?item.precio_futuro.ini:'') + ','
    item.desc.filter(desc => desc.posteriorCP == 0).forEach(desc => csv += (desc.descuento ? desc.descuento:'') + ',')
    csv += (item.costoPactado()?item.costoPactado():'') + ','
    item.desc.filter(desc => desc.posteriorCP == 1).forEach(desc => csv += (desc.descuento ? desc.descuento:'') + ',')
    csv += (item.bruto()?item.bruto():'') + ',' 
      + (item.neto()?item.neto():'') + '\n'
  })

  download('PrecioListaDesc-' + model.proveedor.getNombre() + '-' + model.bodega.getNombre() + '-' + model.form.fecha + '.csv', csv)
}

function guardarCambios() {

  if(model.proveedor.val && model.bodega.val && model.form.fecha) {
  
    let items = []
    let descs = []
    $.observable(model.producto_tabla).setProperty('loading', true)
    if(!model.producto_tabla.items.some(item => {
      if(item.precio_lista.touched) {  // Agregamos los items modificados
        if(!esNumeroFlotanteValido(item.precio_lista.precio)) {
          alert('Revise que todos los precios sean validos.')
          return true
        }
        items.push({ 
          id_prod: item.id,
          bodegas: item.bodegas.join(), //Se actualiza en todas las bodegas del producto (no necesariamente las seleccionadas)
          precio: item.precio_lista.precio
        })
      }

      if(item.desc.some(desc => {
        if(desc.touched) { // Agregamos los descuentos modificados
          if(!esNumeroFlotanteValido(desc.descuento)) {
            alert('Revise que todos los descuentos sean validos')
            return true
          }
          descs.push({
            id_prod: item.id,
            bodegas: item.bodegas.join(), //Se actualiza en todas las bodegas del producto
            id_descuento: desc.id_descuento,
            descuento: desc.descuento
          })
        }
      })) 
        return true // Encontramos un error, no lo enviamos
    })){
      const data = {
        id_prov: model.proveedor.val,
        bodegas: model.bodega.val.join(),
        fecha: model.form.fecha,
        items: items,
        descs: descs
      }
      $.post('/api/boca/PrecioLista/store', data, (resp) => {
        maneja_errores(resp.error);
        updateListadoProducto()
        $.observable(model).setProperty("modoConsulta", true);
      }, 'json').fail(function() {
        alert("Error al guardar los cambios en el servidor. Intentelo mas tarde.");
         $.observable(model.producto_tabla).setProperty('loading', false)
      });
    }else{
      $.observable(model.producto_tabla).setProperty('loading', false)
    }
  }
}
