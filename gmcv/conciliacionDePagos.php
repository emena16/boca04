<?
$ruta = "../../../";
include $ruta."sys/precarga.php"; // Nos conecta a Mysql
/** Validacion de Acceso

$permitidos = array(1,2,5,3,8,9,10,13);
$acceso = validaAccesoAutorizado($permitidos);

if($acceso == 0)
  redirect("", "");
*/

$jsfile = 'conciliacionDePagos.js'; // Comportamiento
ob_start(); // Capturamos la salida

?>
<style>
  body { 
    color: #3b3f5c;
  }

  /* texto que esté dentro un label va de otro color */
  label { color: #3b3f5c; }
  
</style>


<h2>Conciliacion de Pagos</h2>

<!-- Colocamos las facturas con alerta primero -->
{^{if facturasAlerta^items^length}}
<table style="width: 80%;">
  <thead>
    <tr><td class="aviso" colspan="8">Facturas Vencidas con Saldo Pendiente:</td></tr>
    <tr class="titulo">
      <td>Filtrar por Dias:</td>
      <td><label><input type="radio" data-link="{:facturasAlerta^diasFiltro:}" value="1">1 dia</label></td>
      <td><label><input type="radio" data-link="{:facturasAlerta^diasFiltro:}" value="2">2 dias</label></td>
      <td><label><input type="radio" data-link="{:facturasAlerta^diasFiltro:}" value="3">3 dias</label></td>
      <td><label><input type="radio" data-link="{:facturasAlerta^diasFiltro:}" value="7">1 semana</label></td>
      <td><label><input type="radio" data-link="{:facturasAlerta^diasFiltro:}" value="14">2 semanas</label></td>
      <td><label><input type="radio" data-link="{:facturasAlerta^diasFiltro:}" value="21">3 semanas</label></td>
      <td><label><input type="radio" data-link="{:facturasAlerta^diasFiltro:}" value="0">Sin Filtro</label></td>
    </tr>
  </thead>
</table>
  <table style="width: 80%; margin-bottom: 30px;">
  <thead>
    <tr class="titulo"><th>Proveedor</th><th>Fecha Factura</th><th>Fecha Compromiso</th><th>ID Factura</th><th>Monto Restante</th></tr>
  </thead>
  <tbody>
  {^{for facturasAlerta^items filter=~filtraFacturasAlerta ~readOnly=readOnly}}
    <tr>
      <td>{{:prov_nombre}}</td>
      <td style="text-align: center;">{{:fecha}}</td>
      <td style="text-align: center;">{{:fecha_compromiso}}</td>
      <td><a data-link="href{:'../gmcv/conciliacionDePagos.php?id_prov=' + prov_id + '&ini=' + fecha + '&fin=' + fecha + (~readOnly?'&readOnly':'')}">{{:uuid}}</a></td>
      <td style="text-align: right;">{{mxn:monto_restante}}</td>
    </tr>
  {{/for}}  
  </tbody>
  </table>
{{/if}}


<table style="width: 65%;">
  <tbody>
    <tr><td class="titulo">Seleccione Proveedor</td><td>
      <select data-link='disabled{:proveedor^loading || !proveedor^items.length }{:proveedor^val:}' title="Elija un Proveedor" style="width:100%;">
        {^{for proveedor^items}}<option data-link="value{:id} {:nombre}"></option>{{else}}<option>Sin datos...</option>{{/for}}
      </select>
      </td>
      <td align="left" colspan="2"><label><input type="checkbox" data-link="{:form^filtrarProveedores:}">Filtrar Proveedores</label></td>
    </tr>
    <tr>
      <td class="titulo">Fecha Inicial</td><td><input data-link="{datepicker form^ini}"/></td>
      <td class="titulo">Fecha Final</td><td><input data-link="{datepicker form^fin}"/></td>
    </tr>
    <tr>
      <td class="titulo">Estado de Factura</td><td>
        <select data-link='disabled{:form^status^loading || !form^status^items.length }{:form^status^val:}' title="Elija un status" style="width:100%;">
        {^{for form^status^items}}<option data-link="value{:id} {:nombre}"></option>{{else}}<option>Sin datos...</option>{{/for}}
        </select>
      </td>
      <td class="titulo">Id de Pago</td><td><input data-link="{:form^id:}"/></td>
    </tr>
  </tbody>
  <tfoot>
    <tr><td colspan="4" align="right">
      <input class="btn btn-success mt-4 mb-4" type="button" value="Agregar Nota de Credito" onclick="editarNotaCreditoDialogShow()" data-link='disabled{:!proveedor.val || readOnly}'>
      <input class="btn btn-primary mt-4 mb-4" type="button" value="Agregar Pago" onclick="agregarPagoDialogShow()" data-link='disabled{:!proveedor.val || readOnly}' />
      <input class="btn btn-info mt-4 mb-4" type="button" value="Limpiar Filtro" onclick="limpiarFiltro()">
      <input class="btn btn-info mt-4 mb-4" type="button" value="Buscar" onclick="updateListadoFactura()">
    </td></tr>
  </tfoot>
</table>



{^{if factura^show}}
  <h3>Listado de Facturas</h3>

  <div style="text-align: right; width: 90%;">
    <input class="btn btn-primary" type="button" value="Exportar a Excel" onclick="exportarAExcel()" data-link='disabled{:!proveedor.val || !factura.items.length}'>
  </div>

  <table style="width: 90%;">
    <thead>
      <tr>
        <td colspan=3></td>
        <td class="titulo" align="center" colspan="3">Monto</td>
        <td class="titulo" align="center" colspan="2">Fecha</td>
      </tr>
      <tr class="titulo">
        <th>Fecha</th>
        <th>ID de Factura</th>
        <th>Bodega</th>
        <th>Costo Real</th>
        <th>Ajuste</th>
        <th>Autorizado</th>
        <th>Compromiso</th>
        <th>Pagado</th>
        <th>Id Pago</th>
        <th>Monto Pago</th>  
        <th>Pendiente</th>
      </tr>
    </thead>
    <tbody>
      {^{for factura^items ~pendiente=pendiente ~readOnly=readOnly}}
      <tr>
        <td style="text-align: center;">{{:fecha}}</td>
        <td style="text-align: right;">{{:uuid}}</td>
        <td>{{:bodega_nombre}}</td>
        <td style="text-align: right;">{{mxn:costoReal}}</td>
        <td style="text-align: right;">{{mxn:ajuste}}</td>
        <td style="text-align: right;">{{mxn:autorizado}}</td>
        <td style="text-align: center;" data-link="class{:(~pendiente^[id_compra_factura] > 0.01)?~semaforo(dias_diferencia):'' }">{{:fecha_compromiso}}</td>
        <td style="text-align: center;">{{:fecha_abono}}</td>
        <td>{{:abono_uuid}}</td>
        <td style="text-align: right;">{{mxn:monto_abono}}</td>
        <td style="text-align: right;">{^{mxn:pendiente}}</td>
        <td>
          {^{if monto_abono}}
          <button title="Eliminar este pago"  data-link="disabled{:~readOnly} {on 'click' ~borrarRelacionPagoFactura #getIndex()}"><span class="ui-icon ui-icon-trash"></span></button>
          {{/if}}
          <button class="btn btn-info btn-sm" title="Asignar Pago/Nota de Credito a Factura"  data-link="disabled{:~pendiente^[id_compra_factura] <= 0.01 || ~readOnly} {on 'click' ~asignarPagoDialogShow #index}"><span class="ui-icon ui-icon-pencil"></span></button>
        </td>
      </tr>
      {{else}}
          <tr><td colspan="11" class="aviso">No se encontraron Facturas en este periodo.</td></tr>
      {{/for}}      
    </tbody>
  </table>
{{/if}}

<!-- Caja de Dialogo Agregar Pago -->
<div id="agregarPagoDialog" data-link="visible{:agregarPagoDialogShow}">
  <table style="width: 90%;">
    <tbody>
      <tr><td class="titulo">Fecha de Pago</td><td><input data-link="{datepicker pago^fecha}"/></td></tr>
      <tr><td class="titulo">Monto</td><td><input data-link="{:pago^monto:}" type="number"  style="text-align: right;" min="0"  max="100000000" /></td></tr>
      <tr><td class="titulo">ID Pago</td><td><input data-link="css-background-color{: (!pago^esUUIDDisponible || pago^uuid.length > 36)? 'Orange':''}{:pago^uuid:}" style="width: 260px;"/></td></tr>
    </tbody>
  </table>
</div>

<!-- Caja de Dialogo Agregar Editar Nota de Credito-->
<div id="editarNotaCreditoDialog" data-link="visible{:editarNotaCreditoDialogShow}">
  <table style="width: 90%;">
    <tbody>
      <tr><td class="titulo">Fecha</td><td><input data-link="{datepicker notaDeCredito^fecha}"/></td></tr>
      <tr><td class="titulo">Monto</td><td><input data-link="{:notaDeCredito^monto:}" type="number"  style="text-align: right;"  min="0"  max="100000000" /></td></tr>
      <tr><td class="titulo">ID Nota de Credito</td><td><input data-link="css-background-color{: (!notaDeCredito^esUUIDDisponible || notaDeCredito^uuid.length > 36)? 'Orange':''} {:notaDeCredito^uuid:}" style="width: 260px;"/></td></tr>
    </tbody>
  </table>
</div>

<!-- Caja de Dialogo Para relacionar Monto de un Pago/Nota de Credito con factura -->
<div id="asignarPagoDialog" data-link="visible{:asignarPagoDialogShow}">
  <table style="width: 90%;">
    <tbody>
      <tr><td class="titulo">Pendiente</td><td style="text-align: right;">{^{mxn4:asignar^pendiente}}</td></tr>
      <tr><td class="titulo">Seleccione Pago</td><td><select data-link='disabled{:pagos^loading || !pagos^items.length || notas^val}{:pagos^val:}' title="Elija un pago" style="width:100%;">
        {^{for pagos^items}}<option data-link="value{:#index} {:uuid + ' ' + ~mxn(monto)+ ' ' + ~mxn(restante) }"></option>{{else}}<option>Sin datos...</option>{{/for}}
        </select></td></tr>
      <tr><td class="titulo">Seleccione N de C</td><td><select data-link='disabled{:notas^loading || !notas^items.length  || pagos^val}{:notas^val:}' title="Elija un pago" style="width:100%;">
        {^{for notas^items}}<option data-link="value{:#index} {:uuid + ' ' + ~mxn(monto)+ ' ' + ~mxn(restante) }"></option>{{else}}<option>Sin datos...</option>{{/for}}
        </select></td></tr>
      <tr><td class="titulo">Monto de Pago</td><td><input type="number" data-link="{:asignar^monto:}"/></td></tr>
    </tbody>
  </table>
</div>
<?

//Cargamos el layout
$contenido = ob_get_contents();
ob_end_clean();
include $ruta."sys/hf/jsviews.layout.php";
