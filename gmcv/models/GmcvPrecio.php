<?php
// Incluimos el header
include_once 'header.php';
//Si no esta declarada la inclucion de GmcvDescuentoProducto la incluimos
if (!class_exists('GmcvDescuentoProducto')) {
    include_once 'GmcvDescuentoProducto.php';
}
//Si no esta declarada la inclucion de ProdOficina la incluimos
if (!class_exists('ProdOficina')) {
    include_once 'ProdOficina.php';
}
class GmcvPrecio extends db{
    private $table_name = "gmcv_precio";

    public $id_prov;
    public $id_bodega;
    public $id_prod;
    public $ini;
    public $id_status;
    public $precio;

    public function create(){
        $query = "INSERT INTO " . $this->table_name . " (
            id_prov,
            id_bodega,
            id_prod,
            ini,
            id_status,
            precio
        ) VALUES (
            " .$this->id_prov . ",
            " .$this->id_bodega . ",
            " .$this->id_prod . ",
           '" .$this->ini . "',
            " .$this->id_status . ",
            " .$this->precio . "
        ) ON DUPLICATE KEY UPDATE
        id_status = VALUES(id_status),
        precio = VALUES(precio)";

        $result = db::query($query);

        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    //Creamos un metodo para eliminar precios por id_prov, bodegas e ini
    public function deletePrecioByProvBodegasFecha($id_prov, $bodegas, $ini){

        $query = "DELETE FROM " . $this->table_name . 
        " WHERE id_prov = " .$id_prov . " 
        AND id_bodega IN (" . db::real_escape_string($bodegas) . ")
        AND ini = '" .$ini . "'";

        $result = db::query($query);
        
        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    public static function getHistorialPrecios($input) {

		$proveedor = (int)$input['proveedor_id'];
		$bodega =  (int)$input['bodega_id'];
		$producto = (int)$input['producto_id'];

		$items = [];
		// Obtenemos el listado de productos y sus precios
		try {
			$query = "SELECT precio, ini FROM gmcv_precio WHERE id_prov = $proveedor AND id_bodega = $bodega AND id_prod = $producto AND id_status = 4";
			if($result = db::query($query))
			{	while($rw = db::fetch_assoc($result)) 
					$items[] = $rw;
			}
		}catch (\Exception $e) {
		    return ['error' => $e->getMessage(), 'query' => $query];
		}

		return [
			'items' => $items
		];
	}

    public function getPreciosMasRecientesByBodOfiProd($id_prov, $bodegas, $id_prod, $fecha){
        $query = "SELECT * FROM gmcv_precio WHERE id_prov = $id_prov AND id_bodega IN ($bodegas) AND id_prod = $id_prod AND ini <= DATE('$fecha') ORDER BY ini DESC LIMIT 1";
        $result = db::query($query);
        $precio = array();
        while ($row = db::fetch_assoc($result)) {
            $precio = $row;
        }
        return $precio;

    }
    
    public function getPresentacionesProducto($proveedores, $bodegas, $oficinas, $producto){
        //Esta  funcion retornara las presentaciones de los productos que se encuentran en las oficinas seleccionadas
        $query = "SELECT p.id, pm.id AS id_pm, p.comercial AS nombre,b.id AS id_bodega, po.id_oficina, GROUP_CONCAT(b.id) AS bodegas,
        p.litros, si.iva, si.ieps, si.iepsxl,pm.cant_unid_min, sm.nombre AS pm_unidad,pmc.cantidad AS piezasXCaja, sm2.nombre AS pmc_unidad,
        po.venta, po.venta_nva, po.fecha_cambio, po.pendiente
        FROM producto p
        JOIN prod_medida pm ON p.id = pm.id_prod AND pm.id_status = 4
        JOIN prod_medida_compra pmc ON pmc.id_prod = p.id AND pmc.id_status = 4
        JOIN prod_oficina po ON pm.id = po.id_prod AND po.id_status = 4 AND po.id_oficina IN ($oficinas)
        JOIN sys_medida sm ON pm.id_medida = sm.id
        JOIN sys_medida sm2 ON pmc.id_minimo = sm2.id
        JOIN bod_oficina bo ON po.id_oficina = bo.id_oficina
        JOIN bodega b ON bo.id_bodega = b.id AND b.id_status = 4 AND b.id IN ($bodegas)
        JOIN sys_impuestos si ON si.id = p.id_impuesto
        WHERE p.id_tipo_venta = 1 AND p.id_prov in($proveedores) AND p.id = $producto
        GROUP BY p.id, pm.id
        ORDER BY p.comercial, pm.id_prod, pm.cant_unid_min";
        //Guardamos el query en un archivo para debug
        file_put_contents('query_getPresentacionesProducto.sql', $query);

        //Ejecutamos el query
        $result = db::query($query);

        //Si tienemos una oficina entonces obtnemos los precios de los productos de otro modo retornamos el array tal cual
        $oficnasArray = explode(",", $oficinas);
        $presentaciones = array();
        if (count($oficnasArray) > 1) {
            while ($row = db::fetch_assoc($result)) {
                $presentaciones[] = $row;
            }
        }else{
            //Sabesmos que solo hay una oficina, por lo que por cada producto obtnemos los precios de las presentaciones
            //Instanciamos a prod_oficina para obtner los precios de las presentaciones de esa oficina
            $prodOficina = new ProdOficina();
            while ($row = db::fetch_assoc($result)) {
                //Obtenemos el producto de la oficina
                $prodOficina = ProdOficina::getByIdProdIdOficina($row['id_pm'], $oficinas);
                $row['venta'] = $prodOficina['venta'];
                $row['venta_nva'] = $prodOficina['venta_nva'];
                $row['fecha_cambio'] = $prodOficina['fecha_cambio'];
                $presentaciones[] = $row;
            }
        }
        return $presentaciones;

    }

    public function getPreciosOficina($oficinas, $bodegas, $proveedor, $fecha){
        //Separamos las bodegas en un array
        $bodegasArray = explode(",", $bodegas);

        //Si hay mas de una bodega entonces verificamos la coherencia de los descuentos entre las bodegas
        if (count($bodegasArray) > 1) {
            //Antes de todo vamos a revisar la coherencia entre bodegas, no puede haber diferentes descuentos entre bodegas seleccionadas para el mismo proveedor
            $query = "SELECT db.id_bodega, GROUP_CONCAT(DISTINCT d.id ORDER BY d.id SEPARATOR ', ') AS descuentos_por_bodega
            FROM gmcv_descuento_bodega db
            JOIN gmcv_descuento d ON db.id_descuento = d.id
            JOIN  gmcv_descuento_producto dp on dp.id_descuento = d.id
            WHERE db.id_bodega IN ($bodegas) AND d.id_prov = $proveedor AND dp.ini <= DATE('$fecha') and d.id_status = 4
            GROUP BY db.id_bodega";
            $result = db::query($query);
            $resultados = db::fetch_all($result);

            // Crear un array para almacenar los descuentos de cada bodega
            $descuentosBodegas = [];

            foreach ($resultados as $resultado) {
                $idBodega = $resultado['id_bodega'];
                $descuentos = explode(', ', $resultado['descuentos_por_bodega']);
                sort($descuentos); // Ordenar los descuentos para comparación

                // Convertir el array de descuentos en una cadena para compararlo fácilmente
                $descuentosBodegas[$idBodega] = implode(', ', $descuentos);
            }

            // Obtener los descuentos únicos
            $descuentosUnicos = array_values(array_unique($descuentosBodegas));

            // Si hay más de un tipo de descuento, significa que hay diferencias
            if (count($descuentosUnicos) > 1) {
                //Enviamos el mensaje de error por que hay descuentos diferentes entre bodegas y no podemos continuar
                return array(
                    "productos" => array(),
                    "descuentosAntesCP" => array(),
                    "descuentosDespCP" => array(),
                    "message" => "<strong>Error </strong> Los descuentos de las bodegas seleccionadas son diferentes, por lo que los precios de venta pueden variar.",
                    "statusMessage" => "danger"
                );
            }
        }



        //Antes de todo vamos a consultar si las oficinas seleccionadas estan pendientes de precios en prod_oficina, si es asi, entonces tenemos que avisar al usuario
        // $query = "SELECT of.id, of.nombre, po.pendiente 
        // FROM oficina of
        // JOIN prod_oficina po ON po.id_oficina = of.id
        // JOIN prod_medida pm on po.id_prod = pm.id
        // JOIN producto p on p.id = pm.id_prod
        // JOIN proveedor prov on prov.id = p.id_prov and prov.id in ($proveedor)
        // WHERE po.pendiente = 1 AND po.id_oficina IN ($oficinas) AND po.id_status = 4
        // GROUP BY po.id_oficina";

        $query = "SELECT of.id, CONCAT(b.nombre,' - ', of.nombre) as nombre, po.pendiente
        FROM oficina of
        JOIN prod_oficina po ON po.id_oficina = of.id
        JOIN prod_medida pm on po.id_prod = pm.id
        JOIN producto p on p.id = pm.id_prod
        JOIN proveedor prov on prov.id = p.id_prov and prov.id in ($proveedor)
        JOIN bod_oficina bo on bo.id_oficina = of.id
        JOIN bodega b on b.id = bo.id_bodega
        WHERE po.pendiente = 1 AND po.id_oficina IN ($oficinas) AND po.id_status = 4
        GROUP BY po.id_oficina
        ORDER BY nombre ASC";

        //Escribimos el query en un archivo para debugear
        // file_put_contents('query_getPreciosOficina_pendientes.sql', $query);

        $result = db::query($query);
        $unidadesPendientes = db::fetch_all($result);
        //Empatamos las oficinas pendientes con las oficinas seleccionadas
        $oficinasArray = explode(",", $oficinas);
        $oficinasPendientes = array();
        foreach ($unidadesPendientes as $unidadPendiente) {
            if (in_array($unidadPendiente['id'], $oficinasArray)) {
                $oficinasPendientes[] = $unidadPendiente['nombre'];
            }
        }
        $mensaje = "";
        //Si hay oficinas pendientes, entonces retornamos un mensaje de precaucion avisando que las siguienets oficinas estan pendientes de precios
        if (!empty($oficinasPendientes)) {
            //Creamos un mensaje para pegarlo en div alert de bootstrap
            $mensaje = "<strong>Precaución </strong> Ten en cuenta que los productos de las siguientes oficinas ya tienen precios programados para este proveedor: <ul>";
            foreach ($oficinasPendientes as $oficinaPendiente) {
                $mensaje .= "<li>" . $oficinaPendiente . "</li>";
            }
            $mensaje .= "</ul> Cuando guardes los cambios los precios programados se actualizarán.";
        }

		$query = "SELECT  p.id, pm.id AS id_pm, p.comercial AS nombre, b.id AS id_bodega, GROUP_CONCAT(DISTINCT po.id_oficina) AS oficinas, GROUP_CONCAT(DISTINCT b.id) AS bodegas, p.litros, si.iva, si.ieps, si.iepsxl,
        -- Ajustar precioListaCatalogo y agregar tienePL
        CASE WHEN pl.precio IS NOT NULL THEN pl.precio ELSE 0.01 END AS precioListaCatalogo, CASE WHEN pl.precio IS NOT NULL THEN 1 ELSE 0 END AS tienePL, pl.ini AS fechaInicioPL
        FROM  producto p
        JOIN  prod_medida pm ON p.id = pm.id_prod AND pm.id_status = 4
        JOIN  prod_oficina po ON pm.id = po.id_prod AND po.id_status = 4
        JOIN  bod_oficina bo ON po.id_oficina = bo.id_oficina
        JOIN  bodega b ON bo.id_bodega = b.id AND b.id_status = 4 AND b.id IN ($bodegas)
        JOIN  sys_impuestos si ON si.id = p.id_impuesto
        LEFT JOIN  gmcv_compra_factura_prod cfp ON p.id = cfp.id_prod_compra
        LEFT JOIN  gmcv_precio pl ON pl.id_prod = p.id AND pl.ini = (SELECT MAX(ini) FROM gmcv_precio WHERE id_prod = p.id AND ini <= DATE('$fecha')) AND pl.id_bodega = b.id
        WHERE  p.id_tipo_venta = 1 AND p.id_prov IN ($proveedor) AND bo.id_oficina IN ($oficinas)
        GROUP BY  p.id
        ORDER BY  p.comercial, p.id";
        $result = db::query($query);
        //Guardamos el query en un archivo para debug
        file_put_contents('query_getPreciosOficina.sql', $query);

		//Creamos modelos base para agregar a los productos
        $descuentoMuestra = array(
            "nombre" => "",
            "tasa" => 0,
            "id_descuento" => 0,
            "bodegas" => ""  
        );
        $descuentosAntesCP = array();
        $descuentosDespCP = array();
        $oficinasArray = explode(",", $oficinas);
        //Producto con mas descuentos antes de CP
        $productoMasDescuentosAntesCP = array();

        //Producto con mas descuentos despues de CP
        $productoMasDescuentosDespCP = array();
        //Recorremos los productos
        while ($row = db::fetch_assoc($result)){
            //Para cada bodega hay un descuento, generalmente es el mismo para todas las bodegas pero revisamos si hay descuentos diferentes
            //Obtnemos los descuentos del producto
            $descuentosAux = GmcvDescuentoProducto::getDescuentosProductoByBodegasFecha($row['id'], $proveedor, $bodegas, $fecha);
            //Sumamos los descuentos antes y despues de CP
            $sumaDescuentosAntesCP = 0;
            $sumaDescuentosDespCP = 0;
            //Recorremos los descuentos para separar con respecto a costo pactado posteriorCP 
            foreach ($descuentosAux as $descuento) {
                if ($descuento['posteriorCP'] == 0) {
                    //Antes verificamos si el id_descuento ya esta en el array de descuentos antes de CP
                    $existente = false;
                    foreach ($descuentosAntesCP as $descuentoAntesCP) {
                        if ($descuentoAntesCP['id_descuento'] == $descuento['id']) {
                            $existente = true;
                        }
                    }
                    if ($existente) {
                        //Es posible que hayamos recibido un array de bodegas por lo que tomamos el primer valor por convencion
                        $id_bodega = explode(",", $bodegas)[0];

                        //Si existe es posible que el descuento sea diferente para la bodega actual
                        $descCorrecto = GmcvDescuentoProducto::getDescuentosProductoByIdDescuento($row['id'], $proveedor, $id_bodega, $fecha, $descuento['id']);
                        //Verificamos si se encontro el descuento correcto
                        if ($descCorrecto) {
                            //Buscaremos el descuento correcto en el array de descuentos antes de CP y lo sustituimos
                            foreach ($descuentosAntesCP as $key => $descuentoAntesCP) {
                                if ($descuentoAntesCP['id_descuento'] == $descuento['id']) {
                                    //Sustituimos el descuento
                                    $descuentosAntesCP[$key]['nombre'] = $descCorrecto['nombre'];
                                    $descuentosAntesCP[$key]['tasa'] = $descCorrecto['descuento'];
                                    $descuentosAntesCP[$key]['bodegas'] = $descCorrecto['bodegasArray'];
                                    //Recalculamos la suma de los descuentos antes de CP
                                    $sumaDescuentosAntesCP = 0;
                                    foreach ($descuentosAntesCP as $descuentoAntesCP) {
                                        $sumaDescuentosAntesCP += $descuentoAntesCP['tasa'];
                                    }
                                    //Hemos encontrado el descuento correcto, por lo que salimos del foreach
                                    break;
                                } // fin if descuentoCorrecto
                            } // fin foreach descuentosAntesCP
                        }// fin if descCorrecto
                        continue;
                    } // fin if existente
                    $descuentoMuestra['nombre'] = $descuento['nombre'];
                    $descuentoMuestra['tasa'] = $descuento['descuento'];
                    $descuentoMuestra['id_descuento'] = $descuento['id'];
                    $descuentoMuestra['bodegas'] = $descuento['bodegasArray'];
                    $descuentosAntesCP[] = $descuentoMuestra;
                    $sumaDescuentosAntesCP += $descuento['descuento'];
                } else {
                    //Haemos lo mismo para los descuentos despues de CP
                    $existente = false;
                    foreach ($descuentosDespCP as $descuentoDespCP) {
                        if ($descuentoDespCP['id_descuento'] == $descuento['id']) {
                            $existente = true;
                        }
                    }
                    if ($existente) {
                        //Es posible que hayamos recibido un array de bodegas por lo que tomamos el primer valor por convencion
                        $id_bodega = explode(",", $bodegas)[0];

                        //Si existe es posible que el descuento sea diferente para la bodega actual
                        $descCorrecto = GmcvDescuentoProducto::getDescuentosProductoByIdDescuento($row['id'], $proveedor, $id_bodega, $fecha, $descuento['id']);
                        //Verificamos si se encontro el descuento correcto
                        if ($descCorrecto) {
                            //Buscaremos el descuento correcto en el array de descuentos antes de CP y lo sustituimos
                            foreach ($descuentosDespCP as $key => $descuentoDespCP) {
                                if ($descuentoDespCP['id_descuento'] == $descuento['id']) {
                                    //Sustituimos el descuento
                                    $descuentosDespCP[$key]['nombre'] = $descCorrecto['nombre'];
                                    $descuentosDespCP[$key]['tasa'] = $descCorrecto['descuento'];
                                    $descuentosDespCP[$key]['bodegas'] = $descCorrecto['bodegasArray'];
                                    //Recalculamos la suma de los descuentos antes de CP
                                    $sumaDescuentosDespCP = 0;
                                    foreach ($descuentosDespCP as $descuentoDespCP) {
                                        $sumaDescuentosDespCP += $descuentoDespCP['tasa'];
                                    }
                                    //Hemos encontrado el descuento correcto, por lo que salimos del foreach
                                    break;
                                } // fin if descuentoCorrecto
                            } // fin foreach descuentosAntesCP
                        }// fin if descCorrecto
                        continue;
                    } // fin if existente

                    $descuentoMuestra['nombre'] = $descuento['nombre'];
                    $descuentoMuestra['tasa'] = $descuento['descuento'];
                    $descuentoMuestra['id_descuento'] = $descuento['id'];
                    $descuentoMuestra['bodegas'] = $descuento['bodegasArray'];
                    $descuentosDespCP[] = $descuentoMuestra;
                    $sumaDescuentosDespCP += $descuento['descuento'];
                }
                //Reiniciamos el array descuentoMuestra para el siguiente descuento
                $descuentoMuestra = array(
                    "nombre" => "",
                    "tasa" => 0,
                    "id_descuento" => 0,
                    "bodegas" => ""
                );
            } // fin foreach descuentos

            //Agregamos los descuentos al array de productos 
            $row['descuentosAntesCP'] = $descuentosAntesCP;
            $row['descuentosDespCP'] = $descuentosDespCP;

            //Si este producto tiene mas descuentos que el anterior, se guarda
            if (count($descuentosAntesCP) > count($productoMasDescuentosAntesCP)) {
                $productoMasDescuentosAntesCP = $descuentosAntesCP;
            }

            //Si este producto tiene mas descuentos que el anterior, se guarda
            if (count($descuentosDespCP) > count($productoMasDescuentosDespCP)) {
                $productoMasDescuentosDespCP = $descuentosDespCP;
            }
            //Calculamos el costo Pactado aplicando la suma de los descuentos antes de CP
            $row['costoPactado'] = $row['precioListaCatalogo'] * (1 - ($sumaDescuentosAntesCP / 100));
            //Calculamos el costo ingreso aplicando la suma de los descuentos antes y despues de CP
            $row['costoIngresoBruto'] = $row['costoPactado'] * (1 - ($sumaDescuentosDespCP / 100));

            //Calculamos el costo ingreso con impuestos (IEPSxL, IEPS e IVA)
            $ieps = ($row['costoIngresoBruto'] * (1 + $row['ieps'] / 100)) - $row['costoIngresoBruto'];
            if (!empty($row['litros'])) {
                $iepsxl = $row['iepsxl'] * $row['litros'];
            } else {
                $iepsxl = 0;
            }

            $row['costoIngresoNeto'] = ($row['costoIngresoBruto'] + $ieps + $iepsxl) * (1 + $row['iva']);

            //Agregamos el producto al array de productos
        
            // Obntemos las presentaciones del producto y le sacamos una copia al row para agregarlo
            $presentaciones = $this->getPresentacionesProducto($proveedor, $bodegas, $oficinas, $row['id']);
            //almacenamos una copia en un archivo para debug
            file_put_contents('JSON_presentaciones.json', json_encode($presentaciones));
            //Recorremos las presentaciones para agregarlas al row
            foreach ($presentaciones as $presentacion) {
                $row_aux = $row;
                //Actualizamos el prod medida
                $row_aux['id_pm'] = $presentacion['id_pm'];
                //Agregamos los campos de la presentacion para la hoja de calculo de precios de venta
                $row_aux['unidadVta'] = $presentacion['cant_unid_min'];
                $row_aux['nombreUnidadVta'] = $presentacion['pm_unidad'];
                $row_aux['piezasXCaja'] = $presentacion['piezasXCaja'];
                $row_aux['unidad_min_vta'] = $presentacion['pmc_unidad'];

                //Si tenemos varias oficinas, entonces el costo de la unidad de venta es el costo de la presentacion
                if (count($oficinasArray) > 1) {
                    $row_aux['costoUnidadVta'] = $presentacion['venta'];
                }else{
                    //Si solo hay una oficina, entonces el costo de la unidad de venta es el costo de la presentacion
                    $row_aux['costoUnidadVta'] = $presentacion['venta'];
                }
                // IMPORTANTE:
                //ANTES SUPONIAMOS EL COSTO EN FUNCION A LA FECHA ACTUAL, AHORA LO TOMAMOS SEGUN SI ESTA PENDIENTE O NO POR APLICAR
                // //El costoUnidadVta lo tomamos segun la fecha de cambio, si es de de hoy o postfecha tomamos el nuevo precio de venta: venta_nva
                // if ($presentacion['fecha_cambio'] >= date('Y-m-d')) {
                //     $row_aux['costoUnidadVta'] = $presentacion['venta_nva'];
                // } else {
                //     $row_aux['costoUnidadVta'] = $presentacion['venta'];
                // }

                //Si $presentacion['pendiente'] es 1, entonces el costo de la unidad de venta es el atributo venta_nva en caso contrario es venta
                if ($presentacion['pendiente'] == 1) {
                    $row_aux['costoUnidadVta'] = $presentacion['venta_nva'];
                } else {
                    $row_aux['costoUnidadVta'] = $presentacion['venta'];
                }
                //Agregamo a row_aux nuevos atributos para distunguir que es una presentacion con pendiente
                $row_aux['pendiente'] = $presentacion['pendiente'];
                $row_aux['fecha_cambio'] = $presentacion['fecha_cambio'];
                $row_aux['venta'] = $presentacion['venta'];
                $row_aux['venta_nva'] = $presentacion['venta_nva'];
                $row_aux['bgcolor'] = $presentacion['pendiente'] == 1 ? 'blue' : '';

                $row_aux['venta_nva'] = $presentacion['venta_nva'];

                //Recalculamos el costo pactado y el costo ingreso con los datos de la presentacion
                $cp_individual = $row_aux['costoPactado'] / $row_aux['piezasXCaja'];
                $ci_individual = $row_aux['costoIngresoBruto'] / $row_aux['piezasXCaja'];
                $ci_net_individual = $row_aux['costoIngresoNeto'] / $row_aux['piezasXCaja'];
                $pl_individual = $row_aux['precioListaCatalogo'] / $row_aux['piezasXCaja'];
                //Si litros es vacio o null, se asigna 0
                if (empty($row_aux['litros'])) {
                    $row_aux['litros'] = 0;
                }

                //Calcuamos cuantos el ieps por litro
                $ieps_caja = $row_aux['iepsxl'] * $row_aux['litros'];
                $iepsxl_individual = $ieps_caja / $row_aux['piezasXCaja'];
                


                //Recalculamos el costo pactado y el costo ingreso con los datos de la presentacion
                $row_aux['costoPactado'] = $cp_individual * $row_aux['unidadVta'];
                $row_aux['costoIngresoBruto'] = $ci_individual * $row_aux['unidadVta'];
                $row_aux['costoIngresoNeto'] = $ci_net_individual * $row_aux['unidadVta'];                
                $row_aux['pl_individual'] = $pl_individual * $row_aux['unidadVta'];
                $row_aux['iepsxl_individual'] = $iepsxl_individual * $row_aux['unidadVta'];
                //Calculamos el margen actual con respecto al costo ingreso neto y $presentacion['venta']
                if ($presentacion['venta'] != 0) {
                    $row_aux['margenActual'] = (($presentacion['venta'] - $row_aux['costoIngresoNeto']) / $presentacion['venta']) * 100;
                } else {
                    $row_aux['margenActual'] = 0;
                }

                //Fecha Cambio
                $row_aux['fechaCambio'] = $presentacion['fecha_cambio'];

                //Calculamos el margen nuevo en caso de que haya un precio nuevo, esto en funcion a si hay un pendiete o no
                if ($presentacion['pendiente'] == 1) {
                    //Si hay un precio nuevo, entonces calculamos el margen con respecto al nuevo precio
                    if ($presentacion['venta_nva'] != 0) {
                        $row_aux['margenNuevo'] = (($presentacion['venta_nva'] - $row_aux['costoIngresoNeto']) / $presentacion['venta_nva']) * 100;
                    } else {
                        $row_aux['margenNuevo'] = 0;
                    }
                } else {
                    //Si no hay un precio nuevo, entonces el margen nuevo es el mismo que el actual
                    $row_aux['margenNuevo'] = $row_aux['margenActual'];
                }

                //Agregamos la presentacion al array de productos
                $row_aux['inventario'] = "Soy la presentacion: " . $presentacion['pm_unidad'];
                $row_aux['id_pm_raiz'] = $row['id_pm'];

                $data[] = $row_aux;

            }

            //Reiniciamos los arrays de descuentos
            $descuentosAntesCP = array();
            $descuentosDespCP = array();
            
        } //fIN WHILE PRODUCTOS

        // $proveedor = Proveedor::getById($proveedor);

        //Array de respone 
        $response = array(
            "productos" => $data,
            "descuentosAntesCP" => $productoMasDescuentosAntesCP,
            "descuentosDespCP" => $productoMasDescuentosDespCP,
            "message" => $mensaje,
            "statusMessage" => "warning"
        );

        return $response;

	}


    public function getProductosByProvBod($id_prov, $bodegas,$fechaInicio){
        $query = "SELECT p.id, pm.id AS id_pm, p.comercial AS nombre, b.id AS id_bodega, po.id_oficina, GROUP_CONCAT(DISTINCT b.id) AS bodegas,
                p.litros, si.iva, si.ieps, si.iepsxl,
                -- Ajustar precioListaCatalogo y agregar tienePL
                CASE WHEN pl.precio IS NOT NULL THEN pl.precio ELSE 0.01 END AS precioListaCatalogo,
                CASE WHEN pl.precio IS NOT NULL THEN 1 ELSE 0 END AS tienePL,
                pl.ini as fechaInicioPL
            FROM producto p
            JOIN prod_medida pm ON p.id = pm.id_prod AND pm.id_status = 4
            JOIN prod_oficina po ON pm.id = po.id_prod AND po.id_status = 4
            JOIN bod_oficina bo ON po.id_oficina = bo.id_oficina
            JOIN bodega b ON bo.id_bodega = b.id AND b.id_status = 4 AND b.id IN ($bodegas)
            JOIN sys_impuestos si ON si.id = p.id_impuesto
            LEFT JOIN gmcv_compra_factura_prod cfp ON p.id = cfp.id_prod_compra
            LEFT JOIN gmcv_precio pl ON pl.id_prod = p.id AND pl.ini = (
                SELECT MAX(ini) FROM gmcv_precio WHERE id_prod = p.id AND ini <= DATE('$fechaInicio')
                ) AND pl.id_bodega = b.id
            WHERE p.id_tipo_venta = 1 AND p.id_prov IN ($id_prov)
            GROUP BY p.id
            ORDER BY p.comercial, p.id";
        //Escribimos el query en un archivo para debug
        // file_put_contents('query_gmcvPrecio.txt', $query);
        $result = db::query($query);
        
        //Comenzamos a recorres los datos para agregar información adicional a cada producto

        //Creamos modelos base para agregar a los productos
        $descuentoMuestra = array(
            "nombre" => "",
            "tasa" => 0,
            "id_descuento" => 0,
            "bodegas" => ""  
        );
        $descuentosAntesCP = array();
        $descuentosDespCP = array();

        //Producto con mas descuentos antes de CP
        $productoMasDescuentosAntesCP = array();

        //Producto con mas descuentos despues de CP
        $productoMasDescuentosDespCP = array();

        //Recorremos los productos
        while ($row = db::fetch_assoc($result)){
            //Para cada bodega hay un descuento, generalmente es el mismo para todas las bodegas pero revisamos si hay descuentos diferentes
            //Obtnemos los descuentos del producto
            $descuentosAux = GmcvDescuentoProducto::getDescuentosProductoByBodegasFecha($row['id'], $id_prov, $bodegas, $fechaInicio);
            //Sumamos los descuentos antes y despues de CP
            $sumaDescuentosAntesCP = 0;
            $sumaDescuentosDespCP = 0;
            //Recorremos los descuentos para separar con respecto a costo pactado posteriorCP 
            foreach ($descuentosAux as $descuento) {
                if ($descuento['posteriorCP'] == 0) {
                    $descuentoMuestra['nombre'] = $descuento['nombre'];
                    $descuentoMuestra['tasa'] = $descuento['descuento'];
                    $descuentoMuestra['id_descuento'] = $descuento['id'];
                    $descuentoMuestra['bodegas'] = $descuento['bodegasArray'];
                    $descuentosAntesCP[] = $descuentoMuestra;
                    $sumaDescuentosAntesCP += $descuento['descuento'];
                } else {
                    $descuentoMuestra['nombre'] = $descuento['nombre'];
                    $descuentoMuestra['tasa'] = $descuento['descuento'];
                    $descuentoMuestra['id_descuento'] = $descuento['id'];
                    $descuentoMuestra['bodegas'] = $descuento['bodegasArray'];
                    $descuentosDespCP[] = $descuentoMuestra;
                    $sumaDescuentosDespCP += $descuento['descuento'];
                }
                //Reiniciamos el array descuentoMuestra para el siguiente descuento
                $descuentoMuestra = array(
                    "nombre" => "",
                    "tasa" => 0,
                    "id_descuento" => 0,
                    "bodegas" => ""
                );
            } // fin foreach descuentos

            //Agregamos los descuentos al array de productos 
            $row['descuentosAntesCP'] = $descuentosAntesCP;
            $row['descuentosDespCP'] = $descuentosDespCP;

            //Si este producto tiene mas descuentos que el anterior, se guarda
            if (count($descuentosAntesCP) > count($productoMasDescuentosAntesCP)) {
                $productoMasDescuentosAntesCP = $descuentosAntesCP;
            }

            //Si este producto tiene mas descuentos que el anterior, se guarda
            if (count($descuentosDespCP) > count($productoMasDescuentosDespCP)) {
                $productoMasDescuentosDespCP = $descuentosDespCP;
            }

            //Calculamos el costo Pactado aplicando la suma de los descuentos antes de CP
            $row['costoPactado'] = $row['precioListaCatalogo'] * (1 - ($sumaDescuentosAntesCP / 100));
            //Calculamos el costo ingreso aplicando la suma de los descuentos antes y despues de CP
            $row['costoIngresoBruto'] = $row['costoPactado'] * (1 - ($sumaDescuentosDespCP / 100));

            //Calculamos el costo ingreso con impuestos (IEPSxL, IEPS e IVA)
            $ieps = ($row['costoIngresoBruto'] * (1 + $row['ieps'] / 100)) - $row['costoIngresoBruto'];
            if (!empty($row['litros'])) {
                $iepsxl = $row['iepsxl'] * $row['litros'];
            } else {
                $iepsxl = 0;
            }

            $row['costoIngresoNeto'] = ($row['costoIngresoBruto'] + $ieps + $iepsxl) * (1 + $row['iva']);


            //Agregamos el producto al array de productos
            $data[] = $row;

            //Reiniciamos los arrays de descuentos
            $descuentosAntesCP = array();
            $descuentosDespCP = array();
            
        }

        //Obtnemos la info del proveedor
        $proveedor = Proveedor::getById($id_prov);

        //Array de respone 
        $response = array(
            "productos" => $data,
            "descuentosAntesCP" => $productoMasDescuentosAntesCP,
            "descuentosDespCP" => $productoMasDescuentosDespCP,
            "proveedor" => $proveedor
        );

        return $response;
    }


    //Creamos una funcion que retorne los el precio de lista de catalogo y los descuentos antes y despues de CP
    public static function getPrecioListaByIdProd($id_prod, $fecha_factura, $id_bodega){
        $query = "SELECT p.id AS id_prod, COALESCE(pl.precio, 0) AS precioListaCatalogo, dp.totalDescuento AS sumaDescuentosAntesCP, dp_posterior.totalDescuento AS sumaDescuentoDespCP,
        -- Calcular costoPactadoDB aplicando sumaDescuentosAntesCP a precioListaCatalogo
        ROUND(
            COALESCE(pl.precio, 0) * (1 - COALESCE(dp.totalDescuento, 0) / 100), 4
        ) AS costoPactadoDB,
        -- Calcular costoIngreso aplicando sumaDescuentoDespCP a costoPactadoDB
        ROUND(
            COALESCE(
                ROUND(COALESCE(pl.precio, 0) * (1 - COALESCE(dp.totalDescuento, 0) / 100), 4) * 
                (1 - COALESCE(dp_posterior.totalDescuento, 0) / 100), 
                0
            ), 4
        ) AS costoIngresoDB
        FROM producto p
        LEFT JOIN gmcv_precio AS pl ON pl.id_prod = p.id  AND pl.id_bodega = $id_bodega AND pl.id_status = 4
            AND pl.ini = (
                SELECT MAX(ini) 
                FROM gmcv_precio 
                WHERE id_prod = p.id 
                AND id_bodega = $id_bodega
                AND ini <= DATE('$fecha_factura')
                AND id_status = 4
            )
        LEFT JOIN (
            SELECT dp.id_prod, dp.id_bodega, SUM(dp.descuento) AS totalDescuento
                FROM gmcv_descuento_producto dp
                JOIN gmcv_descuento d ON d.id = dp.id_descuento
                WHERE dp.ini = (
                    SELECT MAX(dp2.ini)
                    FROM gmcv_descuento_producto dp2
                    WHERE dp2.id_prod = dp.id_prod 
                    AND dp2.id_bodega = dp.id_bodega
                    AND dp2.ini <= DATE('$fecha_factura')
                    ) AND dp.id_status = 4 AND d.posteriorCP = 0 AND d.id_status = 4
                GROUP BY dp.id_prod, dp.id_bodega
            ) AS dp 
            ON dp.id_prod = p.id
            AND dp.id_bodega = $id_bodega
        LEFT JOIN 
            (
                SELECT dp.id_prod, dp.id_bodega, SUM(dp.descuento) AS totalDescuento
                FROM gmcv_descuento_producto dp
                JOIN gmcv_descuento d ON d.id = dp.id_descuento
                WHERE dp.ini = (
                    SELECT MAX(dp2.ini)
                    FROM gmcv_descuento_producto dp2
                    WHERE dp2.id_prod = dp.id_prod 
                    AND dp2.id_bodega = dp.id_bodega
                    AND dp2.ini <= DATE('$fecha_factura')
                    ) AND dp.id_status = 4 AND d.posteriorCP = 1 AND d.id_status = 4
                GROUP BY 
                    dp.id_prod, dp.id_bodega
            ) AS dp_posterior ON dp_posterior.id_prod = p.id AND dp_posterior.id_bodega = $id_bodega
        WHERE p.id = $id_prod
        GROUP BY p.id, precioListaCatalogo, sumaDescuentosAntesCP, sumaDescuentoDespCP
        LIMIT 1";
        // Escribimos el query en un archivo para debug
        // file_put_contents('query_gmcvPrecio.txt', $query);
        //Ejecutamos el query
        $result = db::query($query);
        //Obtenemos el resultado
        $row = db::fetch_assoc($result);
        //Retornamos el resultado
        return $row;

    }// Fin getPrecioListaByIdProd

    public function getFechasCambiosPrecioLista($id_prov, $bodegas) {
        $query = "SELECT gp.ini AS fechaCambioPrecio
        FROM  gmcv_precio gp
        WHERE  gp.id_bodega IN ($bodegas)  
        AND gp.id_prov = $id_prov AND gp.ini BETWEEN DATE_SUB(CURDATE(), INTERVAL 1 YEAR) AND DATE_ADD(CURDATE(), INTERVAL 3 MONTH)  
        AND gp.id_status = 4
        GROUP BY  gp.id_bodega, gp.ini
        ORDER BY  gp.ini DESC";
        $result = db::query($query);
        return db::fetch_all($result);
    }



}