//Modelo, nuestra unica fuente de verdad
var model = {
  proveedor: { items: [], val: null, loading: false, getNombre: getNombreByID },
  bodega: { items: [{id: null, nombre: 'Elija un proveedor'}], val: null, loading: false, getNombre: getNombreByID },
  oficina: { items: [{id: null, nombre: 'Elija una bodega'}], val: null, loading: false, getNombre: getNombreByID },
  descuento: { items: [], val: null, loading: false }, // Solo activos
  descuentoXProducto: { items: [], val: null, loading: false, show: false },
  producto: { items: [], val: null, loading: false },
  producto_tabla: { items: [], val: null, loading: false },
  precios: { items: [], val: null, loading: false },
  preciosVenta: { items: [], val: null, loading: false },
  hoy: toIsoString(new Date()).substr(0,10),
  fechaPlaneacion: toIsoString(new Date()).substr(0,10), // Fecha de Navegacion, requerida para obtener el precio actual
  modoConsulta: true,
  nuevoMargen: null,
  readOnly: false,

  indexShow: false, // Pantallas
  nuevoMargenDialogShow: false,
};

$(function () {
  // Observamos cambios en el modelo
  $.observe(model.proveedor, "val", (ev, eventArgs) => {
    $.observable(model.oficina).setProperty('val', null)
    $.observable(model.oficina.items).refresh([])

    loadOptions(model.bodega, '/api/boca/Bodega/getItems', {
      id_prov: model.proveedor.val
    })
    updateListadoProducto()
  })
  $.observe(model.bodega, "val", (ev, eventArgs) => {
    if(model.bodega.val) {
      loadOptions(model.oficina, '/api/boca/Oficina/getItems', {
        id_prov: model.proveedor.val,
        id_bodega: model.bodega.val
      })
      updateListadoProducto()
    }
  })
  $.observe(model.oficina, "val", (ev, eventArgs) => updateListadoProducto())
  $.observe(model, "fechaPlaneacion", (ev, eventArgs) => updateListadoProducto())
  
  $.views.helpers({
    antesCP: (item, index, items) => (item.posteriorCP == 0), // Filtros
    despuesCP: (item, index, items) => (item.posteriorCP == 1),
    setMargenNuevo: (ev, eventArgs) => { setMargenNuevo() }
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

// ################################################################  Las funciones calculadas al vuelo en la tabla
function enabled () { // Enabled no cambia de valor
  return this.precio_lista.precio && this.precio_lista.mismoValor                   // Viene precio Lista y tienen el mismo valor en todas
            && this.precio_venta.venta && this.precio_venta.mismoValor              // Viene precio venta y tienen el mismo valor en todas
            && this.venta_nva_mismoValor && this.fecha_cambio_mismoValor            // Si venta_nva y fecha_cambio tienen el mismo valor en todas
            && this.desc.reduce((val, curItem) => val == curItem.mismoValor, true)
}

function costoPactado () {
  return this.enabled()?
    (1 - this                                                                      // Costo pactado
      .desc.filter(desc => (desc.sel == true && desc.posteriorCP == 0)) // Solo seleccionados y antes de CP
      .reduce((suma, curItem) => suma + Number(curItem.descuento), 0) / 100) * Number(this.precio_lista.precio)
    :null
}
costoPactado.depends = ["desc.**"]

function costoIngresoBruto () {
  return this.enabled()?
    (1 - this
      .desc.filter(desc => (desc.sel == true && desc.posteriorCP == 1)) // Solo seleccionados y despues de CP
      .reduce((suma, curItem) => suma + Number(curItem.descuento), 0) / 100) * this.costoPactado()
    :null
}
costoIngresoBruto.depends = ["desc.**"]

function costoXUnidadVentaBruto () {
  return this.enabled() ? Number(this.costoIngresoBruto()) / Number(this.piezasXCaja) * Number(this.cant_unid_min):null
}
costoXUnidadVentaBruto.depends = ["costoIngresoBruto()"]

function costoIngresoNeto () {
  return this.enabled()? (Number(this.costoIngresoBruto()) * (1 + this.ieps) + this.litros * this.iepsxl) * (1 + this.iva) :null
                                   //(gsv.precio * (1 + si.ieps) +  p.litros * si.iepsxl) * (1 + si.iva) -- version sql de rive acomodado
}
costoIngresoNeto.depends = ["costoIngresoBruto()"]

function costoXUnidadVenta () {
  return this.enabled() ? Number(this.costoIngresoNeto()) / Number(this.piezasXCaja) * Number(this.cant_unid_min):null
}
costoXUnidadVenta.depends = ["costoIngresoNeto()"]

function margen () {
  if(this.enabled()) {
    if(this.touched)
      return this._margen
    else if(this.pendiente && this.venta_nva)
      return (((Number(this.venta_nva_bruta) - Number(this.costoXUnidadVentaBruto()))/Number(this.venta_nva_bruta))*100).toFixed(4)
    else
      return (this.precio_venta.venta?(((Number(this.precio_venta.venta_bruta) - Number(this.costoXUnidadVentaBruto()))/Number(this.precio_venta.venta_bruta))*100).toFixed(4): 0)
  } else 
    return null
}
margen.set = function(val) {
  $.observable(this).setProperty('_margen', Number(val))
  $.observable(this).setProperty('touched', true)
  $.observable(this).setProperty('pendiente', false)
  $.observable(this).setProperty('fecha', model.fechaPlaneacion) // Borramos la fecha anterior y ponemos la actual
  if(model.modoConsulta)
    $.observable(model).setProperty('modoConsulta', false)  // Colocamos el formulario en modo edicion
}
margen.depends = ['costoXUnidadVentaBruto()']

function margenActual () {
  if(this.enabled()) 
    return (this.precio_venta.venta?(((Number(this.precio_venta.venta_bruta) - Number(this.costoXUnidadVentaBruto()))/Number(this.precio_venta.venta_bruta))*100).toFixed(4): 0)
  else 
    return null
}
margenActual.depends = ['costoXUnidadVentaBruto()']

function precioNuevo () {
  if(this.pendiente && this.venta_nva)
    return this.venta_nva
  else
    return this.enabled() ? ((this.costoXUnidadVentaBruto() ? ((1 + Number(this.margen())/100) * Number(this.costoXUnidadVentaBruto())).toFixed(2) : 0) * (1 + this.ieps) + this.litros * this.iepsxl) * (1 + this.iva):null
}
precioNuevo.depends = ['costoXUnidadVentaBruto()', 'margen()']

function incremento () {
  return this.enabled() ? (this.precioNuevo()?(((this.precioNuevo() - this.precio_venta.venta)/this.precio_venta.venta) *100).toFixed(2) : 0) : null
}
incremento.depends = ['precioNuevo()']

function updateListadoProducto() {
  if(model.proveedor.val && model.bodega.val && model.oficina.val) {

    $.observable(model.producto_tabla).setProperty('loading', "Cargando...")
    $.observable(model.producto_tabla).setProperty('show', false)
    $.observable(model.producto).setProperty('loading', true)
    $.observable(model.descuentoXProducto).setProperty('loading', true) // Los colocamos en loading a mano.
    const data = {
      'proveedor_id':model.proveedor.val,
      'bodega_id': model.bodega.val,    //Arreglo de bodegas seleccionadas
      'oficinas_id': model.oficina.val, //Arreglo de oficinas seleccionadas
      'fecha': model.fechaPlaneacion    //Fecha planeacion
    }
      // Descargamos los descuentos
    const promise0 = loadOptions(model.descuento, '/api/boca/Descuento/getDescuentosDisponibles', data, { placeholder: false })
      .then(() => { // Esperamos a que se carguen los datos para filtrarlos
        $.observable(model.descuento.items).refresh(model.descuento.items.filter(item => item.id_status == 4))
      })
      // Descargamos los productos
    const promise1 = loadOptions(model.producto, '/api/boca/Producto/getProductosPlanificador', data, { placeholder: false })
      // Descargamos los precios
    const promise2 = loadOptions(model.precios, '/api/boca/PrecioLista/getPrecios', data, { placeholder: false })
      // Descargamos los precios por Oficina
    const promise3 = loadOptions(model.preciosVenta, '/api/boca/PrecioLista/getPreciosOficina', data, { placeholder: false })
    
    Promise.all([promise0, promise1, promise2, promise3]).then(()=> {
      $.observable(model.producto_tabla).setProperty('loading', "Calculando...")

      // Requerimos el listado de Descuentos en pantalla para aplicar el filtro
      data['descuento_id'] = model.descuento.items.map(item => item.id)
 
      loadOptions(model.descuentoXProducto, '/api/boca/Descuento/getDescuentosXProducto', data, { placeholder: false })
      .then(() => {
        model.producto.items.forEach(item => {
          // ###################################################################################Asignamos Precios y Fechas
          let precio_lista = null
          let descuentos = null
          item.bodegas.forEach((bodega, index) => { // Obtenemos el dato de cualquiera de las primeras bodegas
            if(!precio_lista) {
              precio_lista = model.precios.items.find(precio => (precio.tipo == 'actual' && precio.id_prod == item.id && precio.id_bodega == bodega))
              if(precio_lista)
                precio_lista['mismoValor'] = (index== 0)? true : false; //
            } else {                                                                    //Ya se tiene un valor, es el mismo?
              if(precio_lista.mismoValor) { //Solo buscamos hasta encontrar uno falso
                precio = model.precios.items.find(precio => (precio.tipo == 'actual' && precio.id_prod == item.id && precio.id_bodega == bodega))
                if(precio){
                  if(precio.precio != precio_lista.precio) //Encontramos uno y no es el mismo valor
                    precio_lista.mismoValor = false
                } else {
                  precio_lista.mismoValor = false // No encontramos
                }
              }
            }
          })

          // Si ninguna bodega lo tuvo, mismoValor = true
          if(!precio_lista)
            precio_lista = {id:0, id_bodega: 0, id_prod: item.id, tipo: 'actual', precio: null, mismoValor: true }

          $.observable(item).setProperty('precio_lista', precio_lista)

          // ################################################################################### Asignamos descuentos
          let descArray = []
          model.descuento.items.forEach(desc => { // Ordenados por el orden original de los descuentos
            let descuento = null
            item.bodegas.forEach((bodega, index) => { // Obtenemos el dato de cualquiera de las primeras bodegas
              if(!descuento){
                descuento = model.descuentoXProducto.items.find(descXItem => (descXItem.id_prod == item.id && descXItem.id_descuento == desc.id && descXItem.id_bodega == bodega))
                if(descuento){
                  descuento['mismoValor'] = (index== 0)? true : false;
                  descuento['sel'] = true
                  descuento['posteriorCP'] = desc.posteriorCP // Lo usaremos para filtrarlo
                }
              } else {
                if(descuento.mismoValor) { //Solo buscamos hasta encontrar uno falso
                  descTemp = model.descuentoXProducto.items.find(descXItem => (descXItem.id_prod == item.id && descXItem.id_descuento == desc.id && descXItem.id_bodega == bodega))
                  if(descTemp){
                    if(descTemp.descuento != descuento.descuento)
                      descuento.mismoValor = false
                  }else {
                    descuento.mismoValor = false
                  }
                }
              }
            })
            // Si ninguna bodega tuvo un valor para este descuento, mismoValor = true
            if(!descuento) {
              descuento = {id:0, id_descuento: desc.id, id_prod: item.id, descuento: null, id_bodega: 0, ini:'', posteriorCP: desc.posteriorCP, mismoValor: true, sel: true}
            }
            descArray.push(descuento)
          })
          $.observable(item).setProperty('desc', descArray)

          //La busqueda de valores por oficina es aparte de la bodega
          let precio_venta = null
          let venta_nva = null
          let venta_nva_bruta = null
          let pendiente = 0
          let venta_nva_mismoValor = true
          let fecha_cambio = null
          let fecha_cambio_mismoValor = true
          const precios_venta = model.preciosVenta.items.filter(precio => (precio.id_pm == item.id_pm))
          if(precios_venta.length > 0) { // Encontramos al menos uno
            precio_venta = precios_venta[0] // Tomamos el primero encontrado.
            //item['id_pm'] = precio_venta.id_pm // Tomamos el prod_medida para guardarlo en prod_oficina de manera correcta
            
            precio_venta['mismoValor'] = 
              (precios_venta.length >= model.oficina.val.length) // Todas las oficinas seleccionadas tienen valor
              && precios_venta.every(precio => precio.venta == precio_venta.venta) // Y todas tienen el mismo valor.
            
            // Tienen valores por actualizar?
            const pendientes = precios_venta.filter(precio => (precio.pendiente == 1 && precio.venta_nva != null))
            if(pendientes.length > 0){
              pendiente = true
              venta_nva = pendientes[0].venta_nva // Tomamos el primer valor y lo mostramos
              venta_nva_bruta = pendientes[0].venta_nva_bruta
              fecha_cambio = pendientes[0].fecha_cambio
              
              venta_nva_mismoValor = 
                (pendientes.length >= model.oficina.val.length) // Todas las oficinas seleccionadas tienen valor
                && pendientes.every(precio => precio.venta_nva == venta_nva) // Y todas tienen el mismo valor.
              
              fecha_cambio_mismoValor = 
                (pendientes.length >= model.oficina.val.length) // Todas las oficinas seleccionadas tienen valor
                && pendientes.every(precio => precio.fecha_cambio == fecha_cambio) // Y todas tienen el mismo valor.
            }else{
              pendiente = false
              venta_nva = null
              venta_nva_bruta = null
              venta_nva_mismoValor = true
              fecha_cambio = null
              fecha_cambio_mismoValor = true
            }
          } else {
            precio_venta = {id_prod: item.id, id_pm: 0, id_oficina: 0,  venta: null, venta_bruta: null, id_bodega: 0, mismoValor: true }
            pendiente = false
            venta_nva = null
            venta_nva_bruta = null
            venta_nva_mismoValor = true
            fecha_cambio = null
            fecha_cambio_mismoValor = true
          }

          $.observable(item).setProperty('precio_venta', precio_venta)
          $.observable(item).setProperty('pendiente', pendiente)
          $.observable(item).setProperty('venta_nva', venta_nva)
          $.observable(item).setProperty('venta_nva_bruta', venta_nva_bruta)
          $.observable(item).setProperty('venta_nva_mismoValor', venta_nva_mismoValor)
          $.observable(item).setProperty('fecha_cambio', fecha_cambio)
          $.observable(item).setProperty('fecha_cambio_mismoValor', fecha_cambio_mismoValor)
          $.observable(item).setProperty('fecha', fecha_cambio?fecha_cambio:null)

          // Los valores calculados al vuelo
          item['_margen'] = null
          item['enabled'] = enabled
          item['costoPactado'] = costoPactado
          item['costoIngresoBruto'] = costoIngresoBruto
          item['costoIngresoNeto'] = costoIngresoNeto
          item['costoXUnidadVenta'] = costoXUnidadVenta
          item['costoXUnidadVentaBruto'] = costoXUnidadVentaBruto
          item['margen'] = margen
          item['margenActual'] = margenActual
          item['precioNuevo'] = precioNuevo
          item['incremento'] = incremento
        })
        $.observable(model.producto_tabla).setProperty('items', model.producto.items)
        $.observable(model.producto).setProperty("val", null); //// Eliminamos el producto actual.
        $.observable(model.producto_tabla).setProperty('loading', false)
        $.observable(model.producto_tabla).setProperty('show', true)
        $( ".descuento" ).checkboxradio({ icon: false })
      })
    })
  }
}

// Entramos en modo Edicion
function salirModoEdicion() {
  if(confirm('Desea descartar cualquier cambio realizado?')){
    $.observable(model).setProperty("modoConsulta", true);
    updateListadoProducto()
  }
}

function exportarAExcel() {
	  var csv = 'Nombre del Producto, Unidad Minima de Venta (UMV), Unidad de Venta, UMV Incluidas, Precio Lista (GSV),'
    model.descuento.items.filter(desc => desc.posteriorCP == 0).forEach(desc => csv += ' ' + desc.nombre + '(%),') 
    csv += ' Costo Pactado,' 
    model.descuento.items.filter(desc => desc.posteriorCP == 1).forEach(desc => csv += ' ' + desc.nombre + '(%),') 
    csv += 'Costo Ingreso Bruto, Costo Ingreso Neto, Costo X Unidad de Venta, Margen Actual (%), Precio Actual, Margen Nuevo (%), Precio Nuevo, Dif (%), Fecha\n'

  model.producto_tabla.items.forEach(item =>{
    csv += '"' + item.nombre + '",'
      + (item.pmc_unidad?'"' + item.pmc_unidad + '"':'') + ','
      + (item.pm_unidad?'"' + item.pm_unidad + '"':'') + ','
      + (item.cant_unid_min?item.cant_unid_min:'') + ','
      + (item.precio_lista.precio?item.precio_lista.precio:'') + ','
    item.desc.filter(desc => desc.posteriorCP == 0).forEach(desc => csv += (desc.descuento ? desc.descuento:'') + ',')
    csv += (item.costoPactado()?item.costoPactado():'') + ','
    item.desc.filter(desc => desc.posteriorCP == 1).forEach(desc => csv += (desc.descuento ? desc.descuento:'') + ',')
    csv += (item.costoIngresoBruto()?item.costoIngresoBruto():'') + ','
      + (item.costoIngresoNeto()?item.costoIngresoNeto():'') + ','
      + (item.costoXUnidadVenta()?item.costoXUnidadVenta():'') + ','
      + (item.margenActual()?item.margenActual():'') + ','
      + (item.precio_venta.venta?item.precio_venta.venta:'') + ','
      + ((item.enabled() && item.margen())?item.margen() + '%':'') + ','
      + (item.precioNuevo()?item.precioNuevo():'') + ','
      + (item.incremento()?item.incremento() + '%':'') + ','
      + (item.fecha?item.fecha:'') + '\n'
  })

  download('PlanificadorPrecios-' + model.proveedor.getNombre() + '-' + model.bodega.getNombre() + '-' + model.oficina.getNombre() + '-' + model.fechaPlaneacion + '.csv', csv)
}

function guardarCambios() {
	if(model.proveedor.val && model.bodega.val && model.oficina.val) {
  
    let items = []
    let errores = null
    $.observable(model.producto_tabla).setProperty('loading', true)
    // Revisamos que todos los afectados tengan fecha
    if(model.producto_tabla.items.filter(item => item.touched && !item.fecha).length) {
      alert('Existen valores sin fecha. No se guardarán cambios. Corrija y vuelva a intentarlo.')
      $.observable(model.producto_tabla).setProperty('loading', false)
      return
    }

    // Revisamos que los afectados tengan un valor valido
    if(model.producto_tabla.items.filter(item => item.touched && (!item.precioNuevo() || item.precioNuevo() < .0001)).length){
      alert('Existen valores sin un Precio Nuevo valido. No se guardarán cambios. Corrija y vuelva a intentarlo.')
      $.observable(model.producto_tabla).setProperty('loading', false)
      return
    }

    model.producto_tabla.items.filter(item => item.touched).forEach(item => {
      items.push({ 
        id_prod: item.id_pm,
        fecha: item.fecha,
        precio: item.precioNuevo()
      })
    })

    const data = {
      id_oficinas: model.oficina.val, // El cambio se hará en TODAS las oficinas seleccionadas
      items: items
    }
    console.log(data)

    $.post('/api/boca/PrecioLista/storePreciosOficina', data, (resp) => {
      maneja_errores(resp.error);
      updateListadoProducto()
      $.observable(model).setProperty("modoConsulta", true);
    }, 'json').fail(function() {
      alert("Error al guardar los cambios en el servidor. Intentelo mas tarde.");
       $.observable(model.producto_tabla).setProperty('loading', false)
    });
  }
}

function setMargenNuevo () {
  $.observable(model).setProperty("nuevoMargenDialogShow", true)
  
  let nuevoMargenDialog = $('#nuevoMargenDialog').dialog({
    title: 'Asignar nuevo Margen', autoOpen: true, width: 400, modal: true,
    buttons: {
      "Asignar": () => { 
        if(isNaN(Number.parseFloat(model.nuevoMargen)) || Math.abs(model.nuevoMargen) > 1000000){
          alert('Introduzca un numero valido.')
        } else {
          model.producto_tabla.items.filter(prod => prod.enabled()).forEach(prod => {
            $.observable(prod).setProperty("margen", model.nuevoMargen)
          })
          nuevoMargenDialog.dialog( "close" )
        }
      },
      "Cancelar": () => { nuevoMargenDialog.dialog( "close" ) },
    },
    create: function() {
      $(this).closest(".ui-dialog")
             .find(".ui-button:contains('Cancelar')") 
             .addClass("btn btn-danger"); 
      $(this).closest(".ui-dialog")
              .find(".ui-button:contains('Asignar')") 
              .addClass("btn btn-primary"); 
    },
    close: () => {$.observable(model).setProperty('nuevoMargenDialogShow', false) }
  });
}