<?
$ruta = "../../../";
include $ruta."sys/precarga.php"; // Nos conecta a Mysql
/** Validacion de Acceso

$permitidos = array(1,2,5,3,8,9,10,13);
$acceso = validaAccesoAutorizado($permitidos);

if($acceso == 0)
  redirect("", "");
*/

// http://localhost/mtto/admin/gmcv/planificadorPrecios.php

$jsfile = 'planificadorPrecios.js'; // Comportamiento
ob_start(); // Capturamos la salida
?>
<style>
  body { 
    color: #3b3f5c;
  }

  /* texto que esté dentro un label va de otro color */
  label { color: #3b3f5c; }
  
</style>
<h2>Planificador de Precios</h2>

<table style="width: 40%;">
  <tbody>
    <tr><td class="titulo">Seleccione Proveedor</td><td>
      <select data-link='disabled{:proveedor^loading || !proveedor^items.length || !modoConsulta || producto_tabla^loading}}{:proveedor^val:}' title="Elija Proveedor" style="width:100%;">
        {^{for proveedor^items}}<option data-link="value{:id} {:nombre}"></option>{{else}}<option>Sin datos...</option>{{/for}}
      </select>
    </td></tr>
    <tr><td class="titulo">Seleccione Bodega</td><td>
      <select data-link='disabled{:bodega^loading || !bodega^items.length || !proveedor^val || !modoConsulta || producto_tabla^loading}}{:bodega^val:}' size="4" title="Elija una o mas Bodegas" style="width:100%;" multiple>
        {^{for bodega^items}}<option data-link="value{:id} {:nombre}"></option>{{else}}<option>Sin datos...</option>{{/for}}
      </select>
    </td></tr>
    <tr><td class="titulo">Seleccione Unidad Operativa</td><td>
      <select data-link='disabled{:oficina^loading || !oficina^items.length || !bodega^val || !modoConsulta || producto_tabla^loading}{:oficina^val:}' size="4" title="Elija una o mas Unidades Operativas" style="width:100%;" multiple>
        {^{for oficina^items}}<option data-link="value{:id} {:nombre}"></option>{{else}}<option>Sin datos...</option>{{/for}}
      </select>
    </td></tr>
    <tr><td class="titulo">Fecha de Planeación</td><td><input style="width: 100%;" data-link="disabled{:!modoConsulta || producto_tabla^loading} {datepicker fechaPlaneacion ^_minDate=hoy}"/></td></tr>
  </tbody>
</table>

<div data-link="visible{:proveedor^val && bodega^val && oficina^val}">
  <h3 class="mt-4">Costos De Compra</h3>

<div style="text-align: right; width: 90%;">
    <input class="mt-3 mb-4 btn btn-warning" type="button" value="Descartar Cambios" data-link="disabled{:modoConsulta}" onclick="salirModoEdicion()">
    <input class="mt-3 mb-4 btn btn-primary" type="button" value="Exportar a Excel" onclick="exportarAExcel()" data-link="disabled{:!modoConsulta || producto_tabla^loading || (bodega^val && bodega^val.length > 1) || (oficina^val && oficina^val.length > 1)}" >
</div>


  <table class="table-bordered table-striped" data-link="visible{:producto_tabla^show}">
    <thead>
      <tr>
        <td data-link="colspan{:descuento^items.length + 6}"></td>
        <td class="titulo2" colspan="2" align="center">Costo<br/>Ingreso</td>
        <td></td>
        <td class="titulo" colspan="6" align="center">{^{:oficina^getNombre()}}</td>
      </tr>
      <tr class="titulo">
        <td>Nombre del Producto</td>
        <td>Unidad<br/>Minima<br/>de Venta<br/>(UMV)</td>
        <td>Unidad<br/>de Venta</td>
        <td>UMV<br/>Incluidas</td>
        <td class="titulo2">Precio<br/>Lista</td>
        {^{for descuento^items filter=~antesCP}}
          <td>{{:nombre || '' }}</td>
        {{/for}}
        <td class="titulo2">Costo<br/>Pactado</td>
        {^{for descuento^items filter=~despuesCP}}
          <td>{{:nombre || '' }}</td>
        {{/for}}
        <td class="titulo2">Bruto</td>
        <td class="titulo2">Neto</td>
        <td>Costo X<br/>Unidad<br/>de Venta</td>
        <td>Margen<br/>Actual<br/>(%)</td>
        <td>Precio<br/>Actual</td>
        <td>Margen<br/>Nuevo<br/>(%)<button class="btn-info btn-sm" data-link="disabled{:readOnly} {on 'click' ~setMargenNuevo}"><span class="ui-icon ui-icon-copy"></span></button></td>
        <td>Precio<br/>Nuevo</td>
        <td>Dif<br/>(%)</td>
        <td>Fecha</td>
      </tr>
    </thead>
    <tbody>
      {^{for producto_tabla.items ~hoy=hoy ~readOnly=readOnly}}
      <tr data-link="css-background-color{:touched ? 'Yellow' : ''} css-color{:!enabled()? 'Gray': ''}">
        <td data-link="title{:id}">{{:nombre}}</td>
        <td>{{:pmc_unidad}}</td>
        <td>{{:pm_unidad}}</td>
        <td style="text-align: right;">{{:cant_unid_min}}</td>
        <td style="text-align: right;" data-link="css-background-color{:!precio_lista^mismoValor ? 'DodgerBlue' : ''}">{{mxn:precio_lista^precio}}</td>
        {^{for desc ~itemIndex=#index ~enabled=enabled() filter=~antesCP}}
          <td style="text-align: right;">
            {^{if descuento > 0}}
              <label>
              {{:descuento}}% <input type="checkbox"  class="descuento" data-link="disabled{:!~enabled} {:sel:}"/>
            </label>
            {{/if}}
          </td>
        {{/for}}
        <td style="text-align: right;">{^{mxn:costoPactado()}}</td>
        {^{for desc ~itemIndex=#index ~enabled=enabled() filter=~despuesCP}}
          <td style="text-align: right;">
            {^{if descuento > 0}}
              <label>
              {{:descuento}}% <input type="checkbox"  class="descuento" data-link="disabled{:!~enabled} {:sel:}"/>
            </label>
            {{/if}}
          </td>
        {{/for}}
        <td style="text-align: right;">{^{mxn:costoIngresoBruto()}}</td>
        <td style="text-align: right;">{^{mxn:costoIngresoNeto()}}</td>
        <td style="text-align: right;">{^{mxn:costoXUnidadVenta()}}</td>
        <td style="text-align: right;">{^{:margenActual()}}</td>
        <td style="text-align: right;" data-link="css-background-color{:!precio_venta^mismoValor ? 'DodgerBlue' : ''}">{{mxn:precio_venta.venta}}</td>
        <td>
          {^{if enabled()}}
          <input style="text-align: right; width: 60px;" type="number" step="0.1" data-link="disabled{:~readOnly} {:margen():}" size="4">
          {{/if}}
        </td>
        <td style="text-align: right;"  data-link="css-background-color{:!venta_nva_mismoValor ? 'DodgerBlue' : ''} css-color{:pendiente? 'Green': ''}">{^{mxn:precioNuevo()}}</td>
        <td style="text-align: right;">{^{:incremento()}}</td>
        <td style="text-align: center;" data-link="css-background-color{:!venta_nva_mismoValor ? 'DodgerBlue' : ''} css-color{:pendiente? 'Green': ''}">{^{:fecha}}</td>
      </tr>
      {{else}}
          <tr data-link="visible{:producto_tabla^loading == false}"><td colspan="19" align="center" style="background-color: orange;">No se encontraron Productos</td></tr>
      {{/for}}      
    </tbody>
  </table>

  <div style="text-align: center; font-weight: bold;"  data-link="visible{:producto_tabla^loading}">{^{:producto_tabla.loading?producto_tabla.loading:''}}</div>

  <div style="text-align: right; width: 90%;">
    <input class="mt-3 mb-4 btn btn-success" type="button" value="Guardar Cambios" onclick="guardarCambios()" data-link="disabled{:modoConsulta || readOnly}" >
  </div>
</div>

<!-- Caja de Dialogo Nuevo Margen -->
<div id="nuevoMargenDialog" data-link="visible{:nuevoMargenDialogShow}">
  <table style="width: 100%; border-spacing: 0 17px;">
    <tbody>
      <tr>
        <td class="titulo">Coloque el <b>Margen Nuevo</b> que será asignado a todos los elementos:</td>
        <td><input style="text-align: right; width: 60px;" type="number" min="0" max="1000000" size="4" data-link="{:nuevoMargen:}"></td>
    </tr>
    </tbody>
  </table>
</div>
<?

//Cargamos el layout
$contenido = ob_get_contents();
ob_end_clean();
include $ruta."sys/hf/jsviews.layout.php";
