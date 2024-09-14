<?
$ruta = "../../../";
include $ruta."sys/precarga.php"; // Nos conecta a Mysql
/** Validacion de Acceso

$permitidos = array(1,2,5,3,8,9,10,13);
$acceso = validaAccesoAutorizado($permitidos);

if($acceso == 0)
  redirect("", "");
*/

$jsfile = 'validacionDeCostos.js'; // Comportamiento
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
<h2>Validacion de Costos</h2>
<h3>Listado de Diferencias de Costos Pactados</h3>

<table style="width: 500px;">
  <tbody>
    <tr><td class="titulo">Fecha Inicial</td><td><input data-link="{datepicker form^ini}" style="width: 90px;"/></td></tr>
    <tr><td class="titulo">Fecha Final</td><td><input data-link="{datepicker form^fin}" style="width: 90px;"/></td></tr>
    <tr><td class="titulo">Proveedor</td><td><select data-link='disabled{:proveedor^loading || !proveedor^items.length} {:proveedor^val:}' title="Elija uno o mas Proveedores">
        {^{for proveedor^items}}<option data-link="value{:id} {:nombre}"></option>{{else}}<option>Sin datos...</option>{{/for}}
      </select>
    </td></tr>
    <tr><td class="titulo">Descuento Ajuste</td><td><select data-link='{:form^ajuste^val:}' title="Especifique contra que se realiza el descuento ajuste.">
        {^{for form^ajuste^items}}<option data-link="value{:#index} {:nombre}"></option>{{/for}}
      </select>
    </td></tr>
  </tbody>
  <tfoot>
    <tr><td colspan="2" align="right">
      <!-- <input type="button" value="Limpiar Filtro" onclick="limpiarFiltro()"> -->
      <input class="btn btn-primary mt-2" type="button" value="Buscar" onclick="updateListadoFactura()">
    </td></tr>
  </tfoot>
</table>

<div style="text-align: center; font-weight: bold;"  data-link="visible{:factura^msg}">{^{:factura.msg?factura.msg:''}}</div>

<div data-link="visible{:factura^show}">
  <h3 class="mb-2">Costos de Factura</h3>

  {^{for factura^items sort='bodega_nombre' ~readOnly=readOnly}}
<div style="margin-bottom: 30px; width: 90%; background-color: #f0f2f4;"> <!-- data-link="visible{:totalCostoFacturaNeto != totalCostoPactadoNeto}" -->
  <table style="width: 100%;">
    <thead>
      <tr>
        <td class="titulo">Factura</td><td>{{:uuid}}</td>
        <td class="titulo">Emision</td><td>{{:fecha}}</td>
        <td class="titulo">Llegada</td><td>{{:fecha_llegada}}</td>
        <td class="titulo">Bodega</td><td>{{:bodega_nombre}}</td>
        <td class="titulo">Estado</td><td>{{:status}}</td>
      {^{if ajuste != 0}}<td class="titulo2">Descuento Ajuste Neto</td><td>{^{mxn:ajuste}}</td>{{/if}}
      <td><button class="btn btn-primary" data-link="{on 'click' ~exportarFacturaAExcel }">Exportar a Excel</button></td>
      </tr>
    </thead>
  </table>

  <table class="table-bordered table-striped" style="width: 100%;">
    <thead>
      <tr class="titulo">
        <td>Nombre del Producto</td>
        <td class="titulo2">Precio<br/>Lista</td>
        {^{for descuento^items filter=~antesCP}}
          <td>{{:nombre || '' }}</td>
        {{/for}}
        <td class="titulo2">Costo<br/>Pactado</td>
        <td class="titulo2">Costo<br/>Factura</td>
        <td>Diff %</td>
        {^{for descuento^items filter=~despuesCP}}
          <td>{{:nombre || '' }}</td>
        {{/for}}
        <td class="titulo2">Costo<br/>Ingreso</td>
        <td>U. de<br/>Compras</td>
        <td>U.<br/>Rech.</td>
        <td>Subtotal<br/>Factura</td>
        <td>Descuento<br/>Ajuste</td>
        <td>Subtotal<br/>Ingreso</td>
      </tr>
    </thead>
    <tbody>
      {^{for items ~facturaIndex=#index}}
      <tr data-link="css-background-color{:!valido?'Orange':(touched ? 'Yellow' : '')} css-color{:!enabled? 'Gray': ''}">
        <td data-link="title{:id_prod}">{{:nombre}}</td>
        <td style="text-align: right;">{^{mxn:precio_gsv}}</td>
        {^{for desc ~itemIndex=#index ~facturaIndex=~facturaIndex ~enabled=enabled filter=~antesCP}}
          <td style="text-align: right;">
            {^{if descuento > 0}}
              <label>
                {{:descuento}}%
                <input type="checkbox" class="descuento" data-link="disabled{:!~enabled} {:sel:}"/>
              </label>
            {{/if}}
          </td>
        {{/for}}
        <td style="text-align: right;">{^{mxn:costoPactado}}</td>
        <td style="text-align: right;">{^{mxn:costoReal}}</td>
        <td style="text-align: right;">{^{:diff}}</td>
        {^{for desc ~itemIndex=#index ~enabled=enabled filter=~despuesCP}}
          <td style="text-align: right;">
            {^{if descuento > 0}}
              <label>
                {{:descuento}}%
                <input type="checkbox" class="descuento" data-link="disabled{:!~enabled} {:sel:}"/>
              </label>
            {{/if}}
          </td>
        {{/for}}
        <td style="text-align: right;">{^{mxn:costoIngreso}}</td>
        <td style="text-align: right;">{^{:cantidad - cantidad_rechazada}}</td>
        <td style="text-align: right;">{^{int:cantidad_rechazada}}</td>
        <td style="text-align: right;">{^{mxn:costoFacturaBruto}}</td>
        <td style="text-align: right;">
          <input style="text-align: right; width: 100px;" type="number" step="1" data-link="disabled{:!enabled || ~readOnly} {:ajusteBruto:}" size="4">
          <button class="btn-sm btn-info" data-link="disabled{:!enabled || readOnly} {on 'click' ~resetAjuste }" title="Recalcular ajuste bruto"><span class="ui-icon ui-icon-refresh"></span></button>
        </td>
        <td style="text-align: right;">{^{mxn:costoIngresoBruto}}</td>
      </tr>
      {{else}}
          <tr><td colspan="10" class="aviso">No se encontraron Productos</td></tr>
      {{/for}}
      <tr style="text-align: right;">
        <td data-link="colspan{:descuento^items.length + 8}" class="titulo">Descuento Global</td>
        <td class="titulo2">
          <button class="btn-sm btn-info" data-link="disabled{:readOnly} {on 'click' ~agregarDescuento }" title="Agregar Descuento"><span class="ui-icon ui-icon-plusthick"></span>Agregar</button>
        </td>
        <td class="titulo2">
          {^{for descuentoGlobal filter=~eliminados}}
          <table width="160" align="right">
            <tbody>
            <tr><td>
              <input style="text-align: right; width: 100px;" type="number" step="1" data-link="disabled{:~readOnly} {:descuento:}" size="4">
              <button class="btn-sm btn-warning" data-link="disabled{:readOnly} {on 'click' ~eliminarDescuento }" title="Eliminar Descuento"><span class="ui-icon  ui-icon-trash"></span></button>
            </td></tr>
            <tr><td>
              <textarea data-link="disabled{:~readOnly} {:nota:}" rows="2" style="width: 150px;"></textarea>
            </td></tr>
          </tbody>
          </table><br/>
          {{/for}}
        </td>
        <td class="titulo2"></td>
      </tr>
      <tr style="text-align: right;">
        <td data-link="colspan{:descuento^items.length + 8}" class="titulo">Total Bruto</td>
        <td class="titulo2">{^{mxn:totalCostoFacturaBruto}}</td>
        <td class="titulo2">{^{mxn:totalAjusteBruto}}</td>
        <td class="titulo2">{^{mxn:totalCostoIngresoBruto}}</td>
      </tr>
      <tr style="text-align: right;">
        <td data-link="colspan{:descuento^items.length + 8}" class="titulo">Impuestos</td>
        <td class="titulo2">{^{mxn:totalCostoFacturaImpuestos}}</td>
        <td class="titulo2">{^{mxn:totalAjusteImpuestos}}</td>
        <td class="titulo2">{^{mxn:(totalCostoIngresoNeto - totalCostoIngresoBruto)}}</td>
      </tr>
      <tr style="text-align: right;">
        <td data-link="colspan{:descuento^items.length + 8}" class="titulo">Total Neto</td>
        <td class="titulo2">{^{mxn:totalCostoFacturaNeto}}</td>
        <td class="titulo2">{^{mxn:totalAjusteNeto}}</td>
        <td class="titulo2">{^{mxn:totalCostoIngresoNeto}}</td>
      </tr>
    </tbody>
  </table>
 </div>
  {{else}}
    <div class="aviso">No se encontraron Facturas en el periodo</div>
  {{/for}}

  <!-- ##############################################################################Tabla Resumen -->
  {^{if factura^items.length > 0}}
  <table class="table-bordered table-striped">
    <thead>
      <tr><th colspan="11"><h3>Resumen</h3></th></tr>
      <tr><td colspan="5"></td><th class="titulo2" colspan="4">Descuento Ajuste</th></tr>
      <tr class="titulo">
        <td>Bodega</td>
        <td>Emisión</td>
        <td>Llegada</td>
        <td>Factura</td>
        <td>Costo<br/>Factura</td>
        <td class="titulo2">Anterior<br>Neto</td>
        <td class="titulo2">Bruto</td>
        <td class="titulo2">Impuestos</td>
        <td class="titulo2">Neto</td>
        <td>Costo<br>Autorizado</td>
        <td></td></tr>
    </thead>
    <tbody>
      {^{for factura^items sort='bodega_nombre' ~readOnly=readOnly}}
      <tr>
        <td>{^{:bodega_nombre}}</td>
        <td>{^{:fecha}}</td>
        <td>{^{:fecha_llegada}}</td>
        <td style="text-align: right;">{^{:uuid}}</td>
        <td style="text-align: right;">{^{mxn:totalCostoFacturaNeto}}</td>
        <td style="text-align: right;">{^{mxn:ajuste}}</td>
        <td style="text-align: right;">{^{mxn:totalAjusteBruto}}</td>
        <td style="text-align: right;">{^{mxn:(totalAjusteNeto - totalAjusteBruto)}}</td>
        <td style="text-align: right;">{^{mxn:totalAjusteNeto}}</td>
        <td style="text-align: right;">{^{mxn:totalCostoFacturaNeto - totalAjusteNeto}}</td>
        <td><input type="checkbox" data-link="disabled{:!totalAjusteNeto || !valida || ~readOnly} {:sel:}"/></td>
      </tr>
      {{/for}}
    </tbody>
  </table>
  <center><button class="btn btn-success mt-4 mb-4" onclick="guardarCambios()" data-link="disabled{:readOnly}">Asignar Descuentos Ajuste</button></center>
  {{/if}}

</div>
<?

//Cargamos el layout
$contenido = ob_get_contents();
ob_end_clean();
include $ruta."sys/hf/jsviews.layout.php";

?>
<script>
//Creamos un evento que cambie de color a la clase ui-icon a color blanco cada que el documento sufra un cambio
$(document).on('change', function(){
  $('.ui-icon').css('color', 'white');
});

</script>