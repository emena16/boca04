<?
$ruta = "../../../";
include $ruta."sys/precarga_mysqli.php";
include $ruta."sys/fcn/fcnSelects.php";

$permitidos = array(1,9,11,12,13); // SA y GC (correcto)
$acceso = validaAccesoAutorizado($permitidos);

if($acceso == 0)
    redirect("", "");

include $ruta."sys/hf/header_v3.php";
include $ruta."sys/hf/banner_v3.php";
//include $ruta."mtto/mods/menuGral_looknfeel_mysqli.php";

if(validaAccesoAutorizado(array(9,12)))
	$condOficinas = " join bod_oficina on bod_oficina.id_bodega = bodega.id && bod_oficina.id_status = 4
					  join usr_oficina on usr_oficina.id_oficina = bod_oficina.id_oficina && usr_oficina.id_usr = {$_SESSION["id_usr"]} && usr_oficina.id_status = 4";

?>
<style type="text/css">
		.header{
			padding: 7px 0px!important;
		}
</style>
<table width="100%">
<tr>
<td class = 'columnaMenu' valign="top"><? if (!file_exists($ruta."mtto/mods/menuGral_looknfeel_mysqli.php")) {include $ruta."mtto/mods/menuGral_mysqli.php";}?></td>
<td valign="top" style="padding-right: 15px;">
<div class="page-header ">
    <div class="page-title">
        <h3>Nómina por vendedor</h3>
    </div>
</div>
<div class="statbox widget box box-shadow" style="background-color: #fff;">	
	<form name="reporte" id="reporte" method="post" action="<?=$ruta."mtto/admin/rep/generaNominaVendedor.php"?>">
		<table width="100%" border="0" cellpadding="1" cellspacing="1">
		  <tr>
		    <td align="center" colspan="8">&nbsp;&nbsp;&nbsp;</td>
		  </tr>
		  <tr>
		    <td align="right" valign="middle" width="150">Bodega</td>
		    <td align="left" valign="middle" width="150"><span id="muestraBodega">
		      <?   // Se deben mostrar todas las bodegas, de ahí el cambio en las condiciones
		$rutanueva=$ruta."/mtto/admin/nomina-v2/ajaxMuestraOficina.php";
		           unaTablaDosCampos(" distinct bodega.id, bodega.nombre", "bodega $condOficinas", "bodega.id_status = 4 order by bodega.nombre asc", "bodega",  "",
		             "Todas", "onchange=\"carga1Var(this.id, '$rutanueva', 'muestraOficina')\" ", true)
		      ?></span></td>
		       <td align="right" valign="middle"  width="200">Unid. Op. </td>
		       <td align="left" valign="middle"  width="200"><span id="muestraOficina">
		       
		       <?
		       
		       if(validaAccesoAutorizado(array(9,12)))
		       {
		       	$condOficinas = " join usr_oficina on usr_oficina.id_oficina = bod_oficina.id_oficina && usr_oficina.id_usr = {$_SESSION["id_usr"]} && usr_oficina.id_status = 4 ";
		       		unaTablaDosCampos("oficina.id,oficina.nombre",
		       			"oficina join bod_oficina on bod_oficina.id_oficina = oficina.id 
		       			&& bod_oficina.id_status = 4 $condOficinas ", "oficina.id_status = 4 order by oficina.nombre", "oficina", "", "Selecciona","");
		       }
		       $rutanueva=$ruta."/mtto/admin/nomina-v3/ajaxMuestraSemanas.php";
		       ?>
		       
		      </span></td>
		      <td colspan="1" align="right">Año inicio</td>
		      	<td colspan="1" align="left"><input type="hidden" name="fecha_in" id="fecha_in" value="fecha_inicio"><? $margen = 2; $actual = date("Y"); $nombre = "fecha_inicio"?>
		      	 <select id="año_inicio" name="año_inicio" onchange="carga2Var(this.id,'fecha_in','<?=$rutanueva?>', 'fecha_i'); ">
				    <?	$margen = 2; $actual = intval(date("Y"));  ?>
				    <?  for($x = $actual- $margen; $x <= $actual; $x++) {	?>
				    	<option value="<?=$x?>" <? if($x==$actual) print " selected"; ?>>
				    		<?=$x?>
				    	</option>
				    <?	} ?>
				 </select> </td>
				 <td align="right">Semana inicio</td>
				 <td align="left"><span id="fecha_i" name="fecha_i"><? $nombre = "fecha_inicio"; include("$rutanueva"); ?></span></td>
		  </tr>
		  
		  <tr>
		    <td align="center" colspan="8">&nbsp;&nbsp;&nbsp;</td>
		  </tr>
		 
		 <tr>
		    <td align="center" colspan="8"><a class="btn btn-primary" href="#" onclick="cargaFormId('reporte',0,'ajaxListNominaVendedorv3.php', 'contenido')">Mostrar nómina</a></td>
		  </tr>
		</table>
	</form>
</div>
 
 <table width="100%" border="0" cellpadding="1" cellspacing="1">
  	<tr>
    <td align="left" colspan="8"><div id="contenido" style="overflow: auto;
	width: 100%"></div></td>
  </tr>
 </table>
  
</td>
</tr>

</table>
<?
	if (file_exists($ruta."sys/hf/pie_v3.php")) {
    include $ruta."sys/hf/pie_v3.php";
  }else{
    include $ruta."sys/hf/pie.php";
  }
?>