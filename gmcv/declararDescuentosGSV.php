<?
$ruta = "../../../";
include $ruta."sys/precarga.php"; // Nos conecta a Mysql
/** Validacion de Acceso

$permitidos = array(1,2,5,3,8,9,10,13);
$acceso = validaAccesoAutorizado($permitidos);

if($acceso == 0)
  redirect("", "");
*/

// http://localhost/mtto/admin/gmcv/declararDescuentosGSV.php

$jsfile = 'declararDescuentosGSV.js'; // Comportamiento
ob_start(); // Capturamos la salida

?>
<style>
  body { 
    color: #3b3f5c;
  }

  /* texto que esté dentro un label va de otro color */
  label { color: #3b3f5c; }
  
  .ui-icon{
    color: white;
  }
</style>
<h2>Precio Lista (GSV) y Descuentos</h2>

<table class="table-bordered" style="width: 65%;">
  <tbody>
    <tr><td class="titulo">Seleccione Proveedor</td><td>
      <select data-link='disabled{:proveedor^loading || !proveedor^items.length || !modoConsulta || producto_tabla^loading}} {:proveedor^val:}' title="Elija al menos un proveedor" style="width:100%;">
        {^{for proveedor^items}}<option data-link="value{:id} {:nombre}"></option>{{else}}<option>Sin datos...</option>{{/for}}
      </select>
    </td></tr>
    <tr><td class="titulo">Seleccione Bodega</td><td>
      <select data-link='disabled{:bodega^loading || !bodega^items.length  || !modoConsulta || producto_tabla^loading}} {:bodega^val:}' size="4" title="Elija una o mas Bodegas" style="width:100%;" multiple>
        {^{for bodega^items}}<option data-link="value{:id} {:nombre}"></option>{{else}}<option>Sin datos...</option>{{/for}}
      </select>
    </td></tr>
  </tbody>
</table>

<div data-link="visible{:producto.items}">
  <h3 class="mt-4 mb-3">Listado de Productos</h3>
  <div style="text-align: right; width: 90%;"  data-link="visible{:proveedor^val && bodega^val}">
    <label><input type="checkbox" id="mostrarFecha" data-link="form.mostrarFecha"/>Mostrar Fechas</label>
    <input class="mb-3 btn btn-info" type="button" data-link="disabled{:readOnly} value{: modoConsulta ? 'Definir GSV y Descuentos' : 'Descartar Cambios'}" onclick="entrarModoEdicion()">
    <input class="mb-3 btn btn-primary" type="button" value="Agregar/Reactivar Descuento" onclick="editarDescuentoDialogShow(null)" data-link="disabled{:readOnly}">
    <input class="mb-3 btn btn-info" type="button" value="Exportar a Excel" data-link="disabled{:!modoConsulta || (bodega^val && bodega^val.length > 1) || producto_tabla^loading}" onclick="productosDescargarExcel()">
  </div>

  <table class="table-bordered" style="width: 98%;">
    <tbody>
      <tr>
        <td class="titulo">Proveedor:</td><td data-link="css-color{: proveedor^val ? '' : 'orange'}">
          {^{: proveedor^val?proveedor^getNombre():'Seleccione al menos un proveedor'}}
        </td>
        <td class="titulo">Bodega:</td><td data-link="css-color{: (!bodega^val || (bodega^val && bodega^val.length > 1)) ? 'orange' : ''}">
          {^{:bodega^val?bodega^getNombre():'Seleccione al menos una bodega'}}</td>
        <td class="titulo" style="text-align: right;">Ver Precios y Descuentos en la Fecha:</td>
        <td><input data-link="disabled{:!modoConsulta} {datepicker form^fecha}"/></td>
      </tr>
    </tbody>
  </table>

  <div style="text-align: center; font-weight: bold;"  data-link="visible{:producto_tabla^loading}">{^{:producto_tabla.loading?producto_tabla.loading:''}}</div>

  <table class="table-bordered table-striped" data-link="visible{:proveedor^val && bodega^val && !producto_tabla.loading}">
    <thead>
      <tr>
        <th></th>
        <th class="titulo2" data-link="colspan{:(form^mostrarFecha ? 3 : 1)}">Precio Lista</th>
        <th data-link="colspan{:descuento^items.length + 1}"></th>
        <th class="titulo2" colspan="2" data-link="visible{: bodega^val && bodega^val.length == 1}">Costo Ingreso</th>
      </tr>
      <tr class="titulo">
        <td>Nombre del Producto</td>
        <td class="titulo2">Precio Lista (GSV)</td>
        <td class="titulo2" data-link="visible{:form^mostrarFecha}">Fecha Inicio</td>
        <td class="titulo2" data-link="visible{:form^mostrarFecha}">Fecha Fin</td>
        {^{for descuento^items filter=~antesCP ~readOnly=readOnly}}
          <td>{{:nombre || '' }} <button class="btn-info btn-sm" title="Editar"  data-link="disabled{:~readOnly} {on 'click' ~editarDescuentoDialogShow id}"><span class="ui-icon ui-icon-pencil"></span></button></td>
        {{/for}}
        <td class="titulo2">Costo Pactado</td>
        {^{for descuento^items filter=~despuesCP ~readOnly=readOnly}}
          <td>{{:nombre || '' }} <button class="btn-info btn-sm" title="Editar"  data-link="disabled{:~readOnly} {on 'click' ~editarDescuentoDialogShow id}"><span class="ui-icon ui-icon-pencil"></span></button></td>
        {{/for}}
        <td class="titulo2" data-link="visible{: bodega^val && bodega^val.length == 1}">Bruto</td>
        <td class="titulo2" data-link="visible{: bodega^val && bodega^val.length == 1}">Neto</td>
      </tr>
    </thead>
    <tbody>
      {^{for producto_tabla.items ~mostrarFecha=form^mostrarFecha ~modoConsulta=modoConsulta ~singleBodegaSelect=(bodega^val && bodega^val.length == 1)}}
      <tr>
        <td data-link="title{:id}"><button title="Editar" class="btn-info btn-sm" data-link="visible{:~singleBodegaSelect} {on 'click' ~historialPreciosProdDialogShow #index}"><span class="ui-icon ui-icon-newwin"></span></button>{{:nombre}}</td>
        <td style="text-align: right;" data-link="css-background-color{: (~modoConsulta && !precio_lista^mismoValor) ? 'DodgerBlue' : ''}">
          {^{if ~modoConsulta}}
            {{mxn:precio_lista^precio}}
          {{else}}
              <input style="text-align: right; width: 70px;" type="number" min="0" max="1000000" data-link="css-background-color{:precio_lista^touched ? 'Yellow' : (precio_lista^mismoValor ? '' : 'DodgerBlue')} {:precio_lista^precio:}">
            {{/if}}
        </td>
        <td style="text-align: center;" data-link="visible{:~mostrarFecha}">{{:precio_lista^ini}}</td>
        <td style="text-align: center;" data-link="visible{:~mostrarFecha}">{{:precio_futuro^ini}}</td>
        {^{for desc ~itemIndex=#index ~enabled=enabled filter=~antesCP}}
          <td style="text-align: right;" data-link="css-background-color{: (~modoConsulta && !mismoValor) ? 'DodgerBlue' : ''}">
            {^{if ~modoConsulta}}
              {{:descuento?descuento+'%':''}}
            {{else}}
                <input style="text-align: right; width: 70px;" type="number" min="0" data-link="css-background-color{:touched ? 'Yellow' : (mismoValor ? '' : 'DodgerBlue')} {:descuento:}" size="4">%
            {{/if}}
          </td>
        {{/for}}
        <td style="text-align: right;">{^{mxn:costoPactado()}}</td>
        {^{for desc ~itemIndex=#index ~enabled=enabled filter=~despuesCP}}
          <td style="text-align: right;" data-link="css-background-color{: (~modoConsulta && !mismoValor) ? 'DodgerBlue' : ''}">
            {^{if ~modoConsulta}}
              {{:descuento?descuento+'%':''}}
            {{else}}
              <input style="text-align: right; width: 70px;" type="number" min="0" data-link="css-background-color{:touched ? 'Yellow' : (mismoValor ? '' : 'DodgerBlue')} {:descuento:}" size="4">%
            {{/if}}
          </td>
        {{/for}}
        <td style="text-align: right;" data-link="visible{: ~singleBodegaSelect}">{^{mxn:bruto()}}</td>
        <td style="text-align: right;" data-link="visible{: ~singleBodegaSelect}">{^{mxn:neto()}}</td>
      </tr>
      {{else}}
          <tr><td data-link="colspan{:(form^mostrarFecha ? 8 : 6)}" align="center" style="background-color: orange;">No se encontraron Productos</td></tr>
      {{/for}}      
    </tbody>
  </table>
  
  <p>&nbsp;</p>

  <div style="text-align: center;" data-link="visible{:proveedor^val && bodega^val && !modoConsulta}">
    <input type="button" value="Guardar Cambios para esta Fecha" onclick="guardarCambios()">
  </div>
</div>

<!-- Caja de Dialogo Agregar Editar Descuento -->
<div id="editarDescuentoDialog" data-link="visible{:editarDescuentoDialogShow}">
  <table style="width: 90%;">
    <tbody>
      <tr data-link="visible{:descuentoSeleccionado^nuevoOreactivar}">
        <td class="titulo">Descuentos Inactivos</td><td>
        <select data-link='disabled{:descuentoInactivo^loading || !descuentoInactivo^items.length }{:descuentoInactivo^val:}' title="Elija un Descuento a Reactivar" style="width:100%;">
          {^{for descuentoInactivo^items }}<option data-link="value{:#index} {:nombre}"></option>{{else}}<option>Sin datos...</option>{{/for}}
        </select>
      </td></tr>
      <tr><td class="titulo">Nombre</td><td><input type="text" data-link="{:descuentoSeleccionado^nombre:}" maxlength="30"></td></tr>
      <tr><td class="titulo">Estado</td><td><select data-link="{:descuentoSeleccionado^id_status:}">
          <option value="3">Inactivo</option>
          <option value="4">Vigente</option>
        </select>
      </td></tr>
      <tr><td class="titulo">Bodegas:</td><td>
      <select data-link='disabled{:bodega^loading || !bodega^items.length }} {:descuentoSeleccionado^bodegasSel:}' size="4" title="Elija una o mas Bodegas" style="width:100%;" multiple>
        {^{for bodega^items}}<option data-link="value{:id} {:nombre}"></option>{{else}}<option>Sin datos...</option>{{/for}}
      </select>
    </td></tr>
      <tr><td class="titulo" colspan="2"><label><input type="checkbox" data-link="{:descuentoSeleccionado^posteriorCP:}"> Posterior a Costo Pactado</label></td></tr>
    </tbody>
  </table>
</div>


<!-- Caja de Dialogo Historial de Precios por producto -->
<div id="historialPreciosProdDialog" data-link="visible{:historialPreciosProdDialogShow}">
  <table style="width: 90%;">
    <tbody>
      <tr><td class="titulo">Nombre del Producto</td><td>{^{:(producto^val ? producto^val^nombre:'Sin nombre')}}</td></tr>
      <tr><td class="titulo">Proveedor</td><td>{^{:proveedor^getNombre()}}</td></tr>
      <tr><td class="titulo">Bodega</td><td>{^{:bodega^getNombre()}}</td></tr>
    </tbody>
  </table>
  <div>&nbsp;</div>
  
  <table style="width: 90%;">
    <thead>
      <tr class="titulo"><td>Fecha</td><td>Precio</td></tr>
    </thead>
    <tbody>
      {^{for historialPrecios.items }}
      <tr>
        <td style="text-align: center;">{{:ini}}</td>
        <td style="text-align: right;">{{mxn:precio}}</td>
<!--         <td></td>
        <td><button title="Borrar"><span class="ui-icon ui-icon-trash"></span></button></td> -->
      </tr>
      {{else}}
          <tr><td colspan = "3" align="center" style="background-color: orange;">No se encontraron Precios anteriores</td></tr>
      {{/for}}  
    </tbody>
  </table>
</div>
<?php

//Cargamos el layout
$contenido = ob_get_contents();
ob_end_clean();
include $ruta."sys/hf/jsviews.layout.php";
?>
<script>
  
</script>