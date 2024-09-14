<?
$ruta = "../../../";
include $ruta."sys/precarga.php"; // Nos conecta a Mysql
/** Validacion de Acceso

$permitidos = array(1,2,5,3,8,9,10,13);
$acceso = validaAccesoAutorizado($permitidos);

if($acceso == 0)
  redirect("", "");
*/



$jsfile = 'notasDeCredito.js'; // Comportamiento
ob_start(); // Capturamos la salida
?>
<style>
  body { 
    color: #3b3f5c;
  }

  /* texto que esté dentro un label va de otro color */
  label { color: #3b3f5c; }
  
</style>
<h2>Notas de Credito</h2>

<table style="width: 500px;">
  <tbody>
    <tr><td class="titulo form-control-sm">Fecha Inicial</td><td><input data-link="{datepicker form^ini}" style="width: 90px;"/></td></tr>
    <tr><td class="titulo form-control-sm">Fecha Final</td><td><input data-link="{datepicker form^fin}" style="width: 90px;"/></td></tr>
    <tr><td class="titulo form-control-sm">Proveedor</td><td><select data-link='disabled{:proveedor^loading || !proveedor^items.length }{:proveedor^val:}' title="Elija uno o mas Proveedores">
        {^{for proveedor^items}}<option data-link="value{:id} {:nombre}"></option>{{else}}<option>Sin datos...</option>{{/for}}
      </select>
    </td></tr>
  </tbody>
  <tfoot>
    <tr><td colspan="2" align="right">
      <input class="mt-3 mb-4 btn btn-success" type="button" value="Agregar Nota de Credito" onclick="editarNotaCreditoDialogShow()" data-link='disabled{:!proveedor.val || readOnly}'>
      <input class="mt-3 mb-4 btn btn-info" type="button" value="Limpiar Filtro" onclick="limpiarFiltro()">
      <input class="mt-3 mb-4 btn btn-primary" type="button" value="Buscar" onclick="updateListadoNotas()">
    </td></tr>
  </tfoot>
</table>


<div data-link="visible{:notas^show}">
  <h3>Listado de Notas de Credito</h3>
  
  <div style="text-align: right; width: 90%;">
    <input class="mt-3 btn btn-primary mb-4" type="button" value="Exportar a Excel" onclick="exportarAExcel()" data-link='disabled{:!proveedor.val || notas^items.length < 1}'>
  </div>
  <table style="width: 90%;">
    <thead>
      <tr class="titulo">
        <th>Proveedor</th>
        <th>Fecha</th>
        <th>Monto</th>
        <th>Id Nota de Credito</th>
        <th>Factura</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      {^{for notas^items ~readOnly=readOnly}}
      <tr>
        <td>{{:proveedor}}</td>
        <td style="text-align: center;">{{:fecha}}</td>
        <td style="text-align: right;">{{mxn:monto}}</td>
        <td style="text-align: right;">{{:uuid}}</td>
        <td style="text-align: right;">{{:facturas}}</td>
        <td><button class="mt-3 btn btn-primary" title="Editar" data-link="disabled{:~readOnly} {on 'click' ~editarNotaCreditoDialogShow #index}"><span class="ui-icon ui-icon-pencil"></span></button></td>
      </tr>
      {{else}}
          <tr><td colspan="8" align="center" style="background-color: orange;">No se encontraron Notas de Credito</td></tr>
      {{/for}}      
    </tbody>
  </table>
</div>


<!-- Caja de Dialogo Agregar Editar Nota de Credito-->
<div id="editarNotaCreditoDialog" data-link="visible{:editarNotaCreditoDialogShow}">
  <table style="width: 90%;">
    <tbody>
      <tr><td class="titulo">Proveedor</td><td><select data-link='disabled{:proveedor^loading || !proveedor^items.length }{:item^id_proveedor:}' title="Elija uno Proveedor">
        {^{for proveedor^items}}<option data-link="value{:id} {:nombre}"></option>{{else}}<option selected="selected">Sin datos...</option>{{/for}}
      </select>
      </td></tr>
      <tr><td class="titulo">Fecha</td><td><input data-link="{datepicker item^fecha}"/></td></tr>
      <tr><td class="titulo">Monto</td><td><input data-link="{:item^monto:}" type="number"  style="text-align: right;" min="0"  max="100000000" /></td></tr>
      <tr><td class="titulo">ID Nota de Credito</td><td><input data-link="css-background-color{: (!item^esUUIDDisponible || item^uuid.length > 36)? 'Orange':''} {:item^uuid:}" style="width: 260px;"/></td></tr>
    </tbody>
  </table>
</div>
<?

//Cargamos el layout
$contenido = ob_get_contents();
ob_end_clean();
include $ruta."sys/hf/jsviews.layout.php";
