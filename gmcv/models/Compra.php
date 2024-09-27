<?php
// Incluimos el header
include_once 'header.php';
include_once 'GmcvDescuentoProducto.php';
include_once 'Proveedor.php';

/**
 * En esta clase vamos a manejar todo lo relacionado a compras
 * A continuacion se resume los metodos definidos en esta clase para un mejor entendimiento de su funcionamiento
 * 1.- create() - Crea una nueva compra
 * 2.- update() - Actualiza una compra
 * 3.- getByIdObject($id) - Obtiene una compra por su id
 * 4.- getById($id) - Obtiene una compra por su id
 * 5.- getFacturas() - Obtiene las facturas de una compra
 * 6.- getProductos() - Obtiene los productos de una compra
 * 7.- getProveedores() - Obtiene los proveedores
 * 8.- getBodegas() - Obtiene las bodegas
 * 9.- getTotalCompra($id_compra) - Obtiene el total de una compra
 * 10.- getTotalProductosEsperados($id_compra) - Obtiene el total de productos esperados en una compra
 * 11.- getBodegasConComprasPendientes($id_prov, $modoValidacion) - Obtiene las bodegas con compras pendientes
 * 12.- getComprasPendientes($id_prov, $id_bodega) - Obtiene las compras pendientes
 * 13.- getProductosParaAgregarOrden($id_compra) - Obtiene los productos para agregar a una orden de compra
 * 14.- getProductosBodega($bodega,$proveedor) - Obtiene los productos de una bodega
 * 15.- getProductosRestantesOrdenCompra($id_compra) - Obtiene los productos restantes de una orden de compra
 * 16.- getComprasPendientesPorValidar($id_prov, $id_bodega) - Obtiene las compras pendientes por validar
 * 17.- getProveedoresConOrdenesPorValidar() - Obtiene los proveedores con ordenes por validar
 * 18.- getProveedoresConOrdenesPorValidarCostos() - Obtiene los proveedores con ordenes por validar costos
 * 
 */

class Compra extends db {
    private $table_name = "compra";

    public $id;
    public $id_prov;
    public $alta;
    public $llegada;
    public $id_status;
    public $id_usr;
    public $id_bodega;
    public $id_tipo_venta;
    public $id_usr_alm;
    public $tipo;
    public $nota_orden;
    public $nota_entrada;
    public $fecha_modificacion;

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " (
            id_prov,
            alta,
            llegada,
            id_status,
            id_usr,
            id_bodega,
            id_tipo_venta,
            id_usr_alm,
            tipo,
            nota_orden,
            nota_entrada
        ) VALUES (
            '" . db::real_escape_string($this->id_prov) . "',
            '" . db::real_escape_string($this->alta) . "',
            '" . db::real_escape_string($this->llegada) . "',
            '" . db::real_escape_string($this->id_status) . "',
            '" . db::real_escape_string($this->id_usr) . "',
            '" . db::real_escape_string($this->id_bodega) . "',
            '" . db::real_escape_string($this->id_tipo_venta) . "',
            '" . db::real_escape_string($this->id_usr_alm) . "',
            '" . db::real_escape_string($this->tipo) . "',
            '" . db::real_escape_string($this->nota_orden) . "',
            '" . db::real_escape_string($this->nota_entrada) . "'
        )";
        $result = db::query($query);

        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    public function update(){
        $query = "UPDATE " . $this->table_name . " SET
            id_prov = '" . db::real_escape_string($this->id_prov) . "',
            alta = '" . db::real_escape_string($this->alta) . "',
            id_status = '" . db::real_escape_string($this->id_status) . "',
            id_usr = '" . db::real_escape_string($this->id_usr) . "',
            id_bodega = '" . db::real_escape_string($this->id_bodega) . "',
            id_tipo_venta = '" . db::real_escape_string($this->id_tipo_venta) . "',
            tipo = " .$this->tipo . ",
            nota_orden = '" . db::real_escape_string($this->nota_orden) . "',
            nota_entrada = '" . db::real_escape_string($this->nota_entrada) . "'
            WHERE id = " .$this->id;
        $result = db::query($query);

        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    public static function getByIdObject($id){
        $query = "SELECT * FROM compra WHERE id = '" . db::real_escape_string($id) . "'";
        $result = db::query($query);

        if ($row = db::fetch_assoc($result)) {
            $compra = new Compra();
            $compra->id = $row['id'];
            $compra->id_prov = $row['id_prov'];
            $compra->alta = $row['alta'];
            $compra->llegada = $row['llegada'];
            $compra->id_status = $row['id_status'];
            $compra->id_usr = $row['id_usr'];
            $compra->id_bodega = $row['id_bodega'];
            $compra->id_tipo_venta = $row['id_tipo_venta'];
            $compra->id_usr_alm = $row['id_usr_alm'];
            $compra->tipo = $row['tipo'];
            $compra->nota_orden = $row['nota_orden'];
            $compra->nota_entrada = $row['nota_entrada'];
            return $compra;
        }

        return null;
    }

    public static function getById($id) {
        $query = "SELECT * FROM compra WHERE id = '" . db::real_escape_string($id) . "'";
        $result = db::query($query);

        return db::fetch_assoc($result);
    }

    public function getFacturas() {
        return CompraFactura::getFacturasByCompraId($this->id);
    }

    public function getProductos() {
        return ProdCompra::getProductosByCompraId($this->id);
    }

    public function getProveedores() {
        $query = "SELECT id,corto,largo FROM proveedor WHERE id > 1 and id_status = 4 ORDER BY corto";
        $result = db::query($query);
        $data = array();
        while ($row = db::fetch_assoc($result)) {
            $data[] = $row;
        }
        return $data;
    }

    public function getBodegas() {
        $query = "SELECT nombre, id FROM bodega WHERE id_status = 4 ORDER BY nombre";
        $result = db::query($query);
        $data = array();
        while ($row = db::fetch_assoc($result)) {
            $data[] = $row;
        }
        return $data;
    }

    public function getBodegasByIdProveedor($proveedor) {

		$query = "SELECT bo.id_bodega as id, b.nombre
			from producto p
			join prod_medida pm on p.id = pm.id_prod && pm.id_status = 4
			join prod_oficina po on pm.id = po.id_prod && po.id_status = 4
			join bod_oficina bo on po.id_oficina = bo.id_oficina
			join bodega b on bo.id_bodega = b.id && b.id_status = 4
			where p.id_tipo_venta = 1 ".
			 ($proveedor?" && p.id_prov in ($proveedor) ":"").
			"group by bo.id_bodega order by b.nombre";
        $result = db::query($query);
        $data = array();
        while ($row = db::fetch_assoc($result)) {
            $data[] = $row;
        }
        return $data;
    }
    

    public function getProveedoresConOrdenes() {
        $query = "SELECT p.id, p.corto as nombre, p.largo as nombre_largo
				FROM compra c, proveedor p 
				WHERE p.id = c.id_prov AND p.id_status = 4 AND c.id_tipo_venta = 1 AND c.tipo IN (1,4) AND c.id_status = 5
				-- AND p.id = 6 -- La Morena == Temporal
				GROUP BY p.id
				ORDER BY p.corto";
        $result = db::query($query);
        $data = array();
        while ($row = db::fetch_assoc($result)) {
            $data[] = $row;
        }
        return $data;
    }

    //Obtenemos el total de una compra  (suma de los productos)
    public function getTotalCompra($id_compra) {
        // $query = "SELECT SUM(costo_unitario * cant_solicitada) as total
        //         FROM prod_compra
        //         WHERE id_compra = $id_compra";
        /*
        Este query calcula el costo bruto de la compra sin considerar impuestos ni descuentos
        SELECT 
            SUM(
                COALESCE(
                    (SELECT gp.precio 
                    FROM gmcv_precio gp 
                    WHERE gp.id_prov = c.id_prov AND gp.id_bodega = c.id_bodega AND gp.id_prod = pc.id_prod AND gp.ini <= c.alta AND gp.id_status = 4
                    ORDER BY gp.ini DESC 
                    LIMIT 1),
                    pc.costo_unitario -- Si no hay precio en gmcv_precio, usar el costo_unitario de prod_compra
                ) * pc.cant_solicitada
            ) AS total
        FROM prod_compra pc
        JOIN compra c ON pc.id_compra = c.id
        WHERE pc.id_compra = 239554;
        
        */
        $query = "SELECT 
            pc.id_prod,
            c.id_prov,
            COALESCE(
                (SELECT gp.precio 
                FROM gmcv_precio gp 
                WHERE gp.id_prod = pc.id_prod AND gp.id_bodega = c.id_bodega AND gp.id_prov = c.id_prov AND gp.ini <= c.alta AND gp.id_status = 4
                ORDER BY gp.ini DESC 
                LIMIT 1),
                pc.costo_unitario -- Si no hay precio en gmcv_precio, usar costo_unitario de prod_compra
            ) AS precioLista,

            -- Obtener descuentos antes de CP para el bloque de la fecha de alta
            IFNULL((SELECT SUM(gdp.descuento)
                    FROM gmcv_descuento_producto gdp
                    JOIN gmcv_descuento gd ON gd.id = gdp.id_descuento
                    WHERE gdp.id_prod = pc.id_prod AND gdp.id_prov = c.id_prov AND gdp.id_bodega = c.id_bodega
                    AND gdp.ini = (
                        SELECT MAX(gdp2.ini)
                        FROM gmcv_descuento_producto gdp2
                        WHERE gdp2.id_prod = pc.id_prod
                            AND gdp2.id_bodega = c.id_bodega
                            AND gdp2.ini <= c.alta
                            AND gdp2.id_status = 4
                    ) AND gd.posteriorCP = 0 -- Solo descuentos antes de CP
                    AND gdp.id_status = 4 -- Descuento activo
            ), 0) AS sumaDescuentosAntesCP,

            -- Obtener descuentos después de CP
            IFNULL((SELECT SUM(gdp.descuento)
                    FROM gmcv_descuento_producto gdp
                    JOIN gmcv_descuento gd ON gd.id = gdp.id_descuento
                    WHERE gdp.id_prod = pc.id_prod
                    AND gdp.id_prov = c.id_prov
                    AND gdp.id_bodega = c.id_bodega
                    AND gdp.ini = (
                        SELECT MAX(gdp2.ini)
                        FROM gmcv_descuento_producto gdp2
                        WHERE gdp2.id_prod = pc.id_prod
                            AND gdp2.id_bodega = c.id_bodega
                            AND gdp2.ini <= c.alta
                            AND gdp2.id_status = 4
                    )
                    AND gd.posteriorCP = 1 -- Solo descuentos después de CP
                    AND gdp.id_status = 4 -- Descuento activo
            ), 0) AS sumaDescuentosDespuesCP,

            -- Calcular CostoPactado aplicando el porcentaje de descuento
            COALESCE(
                (SELECT gp.precio 
                FROM gmcv_precio gp 
                WHERE gp.id_prod = pc.id_prod AND gp.id_bodega = c.id_bodega AND gp.id_prov = c.id_prov AND gp.ini <= c.alta AND gp.id_status = 4
                ORDER BY gp.ini DESC 
                LIMIT 1),
                pc.costo_unitario
            ) * (1 - (IFNULL((SELECT SUM(gdp.descuento)
                    FROM gmcv_descuento_producto gdp
                    JOIN gmcv_descuento gd ON gd.id = gdp.id_descuento
                    WHERE gdp.id_prod = pc.id_prod
                        AND gdp.id_prov = c.id_prov
                        AND gdp.id_bodega = c.id_bodega
                        AND gdp.ini = (
                            SELECT MAX(gdp2.ini)
                            FROM gmcv_descuento_producto gdp2
                            WHERE gdp2.id_prod = pc.id_prod AND gdp2.id_bodega = c.id_bodega AND gdp2.ini <= c.alta AND gdp2.id_status = 4
                        ) AND gd.posteriorCP = 0
                    AND gdp.id_status = 4), 0) / 100)) AS CostoPactado,
            si.iva AS tasaIVA,
            si.ieps AS tasaIEPS,
            si.iepsxl as iepsXL,
            pc.cant_solicitada
        FROM prod_compra pc
        JOIN compra c ON pc.id_compra = c.id
        LEFT JOIN producto p ON p.id = pc.id_prod
        LEFT JOIN sys_impuestos si ON si.id = p.id_impuesto
        WHERE pc.id_compra = $id_compra";
        $result = db::query($query);

        //Tenemos parcialmente la información de la compra, ahora vamos a obtener el total de la compra
        //Recorremos los productos de la compra y sumamos el total de la compra
        $total = 0;
        while ($row = db::fetch_assoc($result)) {
            //Calculamos las tasas de IEPS e IVA
            $totalPrecioLista = $row['precioLista'] * $row['cant_solicitada'];
            $totalPrecioPactado = $row['CostoPactado'] * $row['cant_solicitada'];

            $iepsxl = $row['iepsXL'] * $row['cant_solicitada'];
            //Aplicamos la tasa de IESP a totalPrecioPactado
            $totalIEPS = $row['tasaIEPS'] * $totalPrecioPactado;
            //Aplicamos la tasa de IVA a totalPrecioPactado
            $totalIVA = $row['tasaIVA'] * ($totalPrecioPactado + $totalIEPS + $iepsxl);
            //Sumamos el total de la compra
            $total += $totalPrecioPactado + $totalIEPS + $totalIVA + $iepsxl;
        }
        
        // $row = db::fetch_assoc($result);
        // return $row['total'];
        return $total;
    }


    //Obtnemos el total de productos esperados en una compra
    public function getTotalProductosEsperados($id_compra) {
        $query = "SELECT SUM(cant_solicitada) as total
                FROM prod_compra
                WHERE id_compra = $id_compra";
        $result = db::query($query);
        $row = db::fetch_assoc($result);
        return $row['total'];
    }
    
    //Bodegas con compras pendientes a partir de un proveedor
    public function getBodegasConComprasPendientes($id_prov, $modoValidacion) {
        $id_status = $modoValidacion ? 23 : 5; // modoValidacion jala los status 23, modoIngreso los status 5

        $query = "SELECT b.id, b.nombre
                FROM compra c, bodega b
                WHERE b.id = c.id_bodega AND b.id_status = 4 AND c.id_prov = $id_prov AND c.id_tipo_venta = 1 AND c.tipo IN (1,4) AND c.id_status = $id_status
                GROUP BY b.id
                ORDER BY b.nombre";
        $result = db::query($query);
        $data = array();
        while ($row = db::fetch_assoc($result)) {
            $data[] = $row;
        }
        return $data;
    }

    //Obtenemos las compras pendientes de un proveedor según la bodega
    public function getComprasPendientes($id_prov, $id_bodega) {
        //Obtenemos las compras pendientes de un proveedor según la bodega
        $query = "SELECT c.id, c.alta, c.llegada, c.id_status, c.id_usr, c.id_bodega, c.id_tipo_venta, c.id_usr_alm, c.tipo, c.nota_orden, c.nota_entrada,
        COALESCE((SELECT GROUP_CONCAT(f.id) FROM gmcv_compra_factura f WHERE f.id_compra = c.id), 'Pendiente por ingresar') as facturasid,
        COALESCE((SELECT GROUP_CONCAT(f.uuid) FROM gmcv_compra_factura f WHERE f.id_compra = c.id), 'Pendiente por ingresar') as facturas,
        p.corto as nombre_proveedor, p.largo as razonsocial,
        b.nombre as nombre_bodega
        FROM compra c
        JOIN proveedor p ON c.id_prov = p.id
        JOIN bodega b ON c.id_bodega = b.id
        WHERE c.id_prov = $id_prov AND c.id_bodega = $id_bodega AND c.id_tipo_venta = 1 AND c.tipo IN (1,4) AND c.id_status = 5
        ORDER BY c.alta DESC";
        $result = db::query($query);
        $data = array();
        while ($row = db::fetch_assoc($result)) {
            
            $row['total'] = round($this->getTotalCompra($row['id']), 2);
            $row['total_esperado'] = round($this->getTotalProductosEsperados($row['id']), 2);
            //Comvetimos el total en moneda
            $row['total'] = "$ ".number_format($row['total'], 2, '.', ',');
            //Convertimos la fecha de alta en formato de fecha
            $row['alta'] = date('Y-m-d', strtotime($row['alta']));

            //Agregamos un boton para ver/agregar facturas
            $row['btn'] = '<button type="button" id="'.$row['id'].'" class="btn btn-success btnIngresar" ><i style=" color: #f6fcfb;" data-feather="log-in"></i>  Ingresar</button>';

            //Agregamos toda la información a un arreglo para mandarla como respuesta
            $data[] = $row;
        }

        $response = array(
            'ordenesCompra' => $data,
            'razonSocial' => $data[0]['razonsocial'],
        );

        return $response;
    }

    //Obtenemos los productos que podemos agregar a la orden de compra sin repetir
    public function getProductosParaAgregarOrden($id_compra) {
        $query = "SELECT p.id AS id_prod, p.comercial, p.codigo AS EAN, p.cod_prod_prov, p.catalogo AS categoria_sat, sys_medida.nombre AS medida, p.litros, si.iva, si.ieps, si.iepsxl,
                CASE
                    WHEN gp.precio IS NOT NULL THEN
                        gp.precio * (1 + (si.iva / 100) + (si.ieps / 100)) * (1 - (COALESCE(MAX(gd.descuento), 0) / 100))
                    ELSE
                        pc.costo_unitario
                END AS costo_final_con_descuento
            FROM producto p
            JOIN prod_medida pm ON (p.id = pm.id_prod AND pm.id_status = 4)
            JOIN prod_oficina po ON (pm.id = po.id_prod AND po.id_status = 4)
            JOIN bod_oficina bo ON (po.id_oficina = bo.id_oficina)
            JOIN bodega b ON (bo.id_bodega = b.id AND b.id_status = 4 AND b.id = (SELECT c.id_bodega FROM compra c WHERE c.id = $id_compra))
            JOIN sys_impuestos si ON (si.id = p.id_impuesto)
            JOIN prod_medida_compra ON (p.id = prod_medida_compra.id_prod)
            JOIN sys_medida ON (prod_medida_compra.id_medida = sys_medida.id)
            LEFT JOIN (
                SELECT 
                    id_prod,
                    MAX(ini) AS max_ini
                FROM gmcv_precio
                WHERE ini <= CURDATE()  -- Considera solo precios vigentes
                GROUP BY id_prod
            ) AS latest_price ON p.id = latest_price.id_prod
            LEFT JOIN gmcv_precio gp ON latest_price.id_prod = gp.id_prod AND latest_price.max_ini = gp.ini
            LEFT JOIN (
                SELECT 
                    id_prod,
                    descuento
                FROM gmcv_descuento_producto
                WHERE id_status = 4
                AND ini <= CURDATE()  -- Considera solo descuentos vigentes
                GROUP BY id_prod
            ) AS gd ON p.id = gd.id_prod
            LEFT JOIN prod_compra pc ON p.id = pc.id_prod AND pc.id_compra = $id_compra
            WHERE p.id_tipo_venta = 1 
            AND p.id_prov = (SELECT c.id_prov FROM compra c WHERE c.id = $id_compra)
            AND p.id NOT IN (SELECT pc_ex.id_prod FROM prod_compra pc_ex WHERE pc_ex.id_compra = $id_compra)
            GROUP BY p.id
            ORDER BY p.comercial, p.id";
        $result = db::query($query);
        $data = array();
        while ($row = db::fetch_assoc($result)) {
            //Agregamos el costo unitario a 2 decimales
            $row['costo_unitario'] = number_format($row['costo_final_con_descuento'], 2, '.', ',');
            $data[] = $row;
        }
        return $data;
    }


    //Funcion para obtener los productos que vende esa bodega y se pueden agregar a una compra
    public function getProductosBodega($bodega,$proveedor) {
        $query = "SELECT 
					p.id as id_prod, p.comercial, p.codigo AS EAN, p.cod_prod_prov, p.catalogo AS categoria_sat,
					sys_medida.nombre AS medida,
					p.litros, si.iva, si.ieps, si.iepsxl
				FROM producto p
				JOIN prod_medida pm ON (p.id = pm.id_prod && pm.id_status = 4)
				JOIN prod_oficina po ON (pm.id = po.id_prod && po.id_status = 4)
				JOIN bod_oficina bo ON (po.id_oficina = bo.id_oficina)
				JOIN bodega b ON (bo.id_bodega = b.id && b.id_status = 4 && b.id = $bodega)
				JOIN sys_impuestos si ON (si.id = p.id_impuesto)
				JOIN prod_medida_compra ON (p.id = prod_medida_compra.id_prod)
				JOIN sys_medida ON (prod_medida_compra.id_medida = sys_medida.id)
				WHERE p.id_tipo_venta = 1 AND p.id_prov = $proveedor
				GROUP BY p.id
				ORDER BY p.comercial, p.id";
        $result = db::query($query);
        $data = array();
        while ($row = db::fetch_assoc($result)) {
            $data[] = $row;
        }
        return $data;
    }

    // Obtnemos los productos de una compra
    public function getProductosRestantesOrdenCompra($id_compra) {
        $query = "SELECT pc.id_prod, p.comercial, p.codigo as ean, p.cod_prod_prov as cod_prov, 
        COALESCE(SUM(cfp.cantidad_aceptada), 0) AS total_cantidad_aceptada, 
        COALESCE(SUM(cfp.cantidad_rechazada), 0) AS total_cantidad_rechazada, 
        pc.cant_solicitada, pc.costo_unitario, pc.prod_agregado, pc.id_compra, 
        pc.id AS id_prod_compra, pc.cant_solicitada - COALESCE(SUM(cfp.cantidad_facturada), 0) AS cant_restante, 
        COALESCE(SUM(cfp.cantidad_aceptada),0) + COALESCE(SUM(cfp.cantidad_rechazada),0) as cant_ingresada,
        COALESCE(SUM(cfp.cantidad_rechazada),0) as cant_rechazada
        FROM prod_compra pc
        LEFT JOIN producto p ON p.id = pc.id_prod
        LEFT JOIN gmcv_compra_factura_prod cfp ON cfp.id_prod_compra = pc.id
        WHERE pc.id_compra = $id_compra
        GROUP BY 
            pc.id_prod, 
            p.comercial, 
            pc.cantidad, 
            pc.cant_solicitada, 
            pc.costo_unitario, 
            pc.prod_agregado, 
            pc.id_compra, 
            pc.id
        ORDER BY p.comercial";
        
        $result = db::query($query);
        $data = array();
        while ($row = db::fetch_assoc($result)) {
            //Formateamos algunos campos
            $row['costo_unitario'] = number_format($row['costo_unitario'], 2, '.', ',');
            $row['cant_restante'] = number_format($row['cant_restante'], 2, '.', ',');
            $row['cant_solicitada'] = number_format($row['cant_solicitada'], 2, '.', ',');
            $row['cant_ingresada'] = number_format($row['cant_ingresada'], 2, '.', ',');
            //Agregamos un campo color para pintar la fila de la tabla
            $row['color'] = '';
            //Definimos un color para los productos que ya han sido ingresados en su totalidad o parcialmente, ignorando los productos que no han sido ingresados
            if ($row['cant_restante'] == 0) {
                //Pintamos de verde los productos que ya han sido ingresados en su totalidad
                $row['color'] = '#d4edda';
            }
            //Si hay un ingreso parcial pintamos de amarillo
            if ($row['cant_restante'] < $row['cant_solicitada'] && $row['cant_restante'] > 0) {
                $row['color'] = '#fff3cd';
            }

            $data[] = $row;
        }
        return $data;
    }


    //Obtenemos las compras pendientes por Validar de un proveedor según la bodega
    public function getComprasPendientesPorValidar($id_prov, $id_bodega) {
        
        $query = "SELECT c.id, c.alta, c.llegada, c.id_status, c.id_usr, c.id_bodega, c.id_tipo_venta, c.id_usr_alm, c.tipo, c.nota_orden, c.nota_entrada,
        COALESCE((SELECT GROUP_CONCAT(' ',f.uuid) FROM gmcv_compra_factura f WHERE f.id_compra = c.id), 'Pendiente por ingresar') as facturas,
        p.corto as nombre_proveedor, p.largo as razonsocial,
        b.nombre as nombre_bodega
        FROM compra c
        JOIN proveedor p ON c.id_prov = p.id
        JOIN bodega b ON c.id_bodega = b.id
        WHERE c.id_prov = $id_prov AND c.id_bodega = $id_bodega AND c.id_tipo_venta = 1 AND c.tipo IN (1,4) AND c.id_status = 23
        ORDER BY c.alta DESC";
        $result = db::query($query);
        $data = array();
        while ($row = db::fetch_assoc($result)) {
            
            $row['total'] = round($this->getTotalCompra($row['id']), 2);
            $row['total_esperado'] = round($this->getTotalProductosEsperados($row['id']), 2);
            //Comvetimos el total en moneda
            $row['total'] = "$ ".number_format($row['total'], 2, '.', ',');
            //Convertimos la fecha de alta en formato de fecha
            $row['alta'] = date('Y-m-d', strtotime($row['alta']));

            //Agregamos un boton para ver/agregar facturas
            $row['btn'] = '<button type="button" id="'.$row['id'].'" class="btn btn-success btnValidar" ><i style=" color: #f6fcfb;" data-feather="check-square"></i>  Validar Orden</button>';

            //Agregamos toda la información a un arreglo para mandarla como respuesta
            $data[] = $row;
        }

        $response = array(
            'ordenesCompra' => $data,
            'razonSocial' => $data[0]['razonsocial'],
        );

        return $response;
    }


    public function getProveedoresConOrdenesPorValidar() {
        $query = "SELECT p.id, p.corto AS nombre, p.largo AS nombre_largo
            FROM proveedor p
            JOIN compra c ON p.id = c.id_prov
            JOIN gmcv_compra_factura cf ON cf.id_compra = c.id
            WHERE p.id_status = 4
            AND c.id_tipo_venta = 1
            AND c.tipo IN (1, 4)
            AND c.id_status = 23
            -- AND p.id = 6 -- La Morena ==
            GROUP BY p.id
            ORDER BY p.corto";
        $result = db::query($query);
        $data = array();
        while ($row = db::fetch_assoc($result)) {
            $data[] = $row;
        }
        return $data;
    }
    

    //Get proveedores con ordenes de compra pendientes por validar costos
    public static function getProveedoresConOrdenesPorValidarCostos() {
        $query = "SELECT p.id, p.corto AS nombre, p.largo AS nombre_largo
            FROM proveedor p
            JOIN compra c ON p.id = c.id_prov
            JOIN gmcv_compra_factura cf ON cf.id_compra = c.id
            WHERE p.id_status = 4
            AND c.id_tipo_venta = 1
            AND c.tipo IN (1, 4)
            AND c.id_status = 1
            -- AND p.id = 6 -- La Morena ==
            GROUP BY p.id
            ORDER BY p.corto";
        $result = db::query($query);
        $data = array();
        while ($row = db::fetch_assoc($result)) {
            $data[] = $row;
        }
        return $data;
    }


    ////////////////////// PRECIO DE LISTA //////////////////////
    //Obtenemos los productos que se pueden comprar de un proveedor a traves de 1 o varias bodegas
    public function getProductosByProvBod($id_prov, $bodegas,$fechaInicio){
        $bodegasArray = explode(',', $bodegas);
        $bodegas = implode(',', $bodegasArray);
        $fecha = date('Y-m-d', strtotime($fechaInicio));
        $proveedor = $id_prov;
        //Antes de todo vamos a verificar que las bodegas tengan consistencia en terminos de descuentos iguales
        //Si hay mas de una bodega entonces verificamos la coherencia de los descuentos entre las bodegas
        if (count($bodegasArray) > 1) {
            //Antes de todo vamos a revisar la coherencia entre bodegas, no puede haber diferentes descuentos entre bodegas seleccionadas para el mismo proveedor
            $query = "SELECT db.id_bodega, GROUP_CONCAT(DISTINCT d.id ORDER BY d.id SEPARATOR ', ') AS descuentos_por_bodega
            FROM gmcv_descuento_bodega db
            JOIN gmcv_descuento d ON db.id_descuento = d.id
            JOIN  gmcv_descuento_producto dp on dp.id_descuento = d.id
            WHERE db.id_bodega IN ($bodegas) AND d.id_prov = $proveedor AND dp.ini <= DATE('$fecha') and d.id_status = 4
            GROUP BY db.id_bodega";
            //Escribimos el query en un archivo para debugear
            file_put_contents('query_descuentos_consistentes.sql', $query);
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
                //Obtnemos la info del proveedor
                $proveedor = Proveedor::getById($id_prov);
                //Enviamos el mensaje de error por que hay descuentos diferentes entre bodegas y no podemos continuar
                return array(
                    "productos" => array(),
                    "descuentosAntesCP" => array(),
                    "descuentosDespCP" => array(),
                    "message" => "<strong>Error </strong> Los descuentos de las bodegas seleccionadas son diferentes, por lo que los precios de lista pueden variar.",
                    "statusMessage" => "danger",
                    "proveedor" => $proveedor
                );

            }
        }

        /*
         * ESTE QUERY NO SUMA LOS DESCUENTOS ANTES Y DESPUES DE CP
           SELECT p.id, pm.id AS id_pm, p.comercial AS nombre, b.id AS id_bodega, po.id_oficina, GROUP_CONCAT(DISTINCT b.id) AS bodegas,
            p.litros, si.iva, si.ieps, si.iepsxl,
            COALESCE(pl.precio, cfp.costo_unitario_bruto) AS precioListaCatalogo,
            dp.totalDescuento AS sumaDescuentosAntesCP, dp_posterior.totalDescuento AS sumaDescuentoDespCP
        FROM producto p
        JOIN prod_medida pm ON p.id = pm.id_prod AND pm.id_status = 4
        JOIN prod_oficina po ON pm.id = po.id_prod AND po.id_status = 4
        JOIN bod_oficina bo ON po.id_oficina = bo.id_oficina
        JOIN bodega b ON bo.id_bodega = b.id AND b.id_status = 4 AND b.id IN ($bodegas)
        JOIN sys_impuestos si ON si.id = p.id_impuesto
        LEFT JOIN gmcv_compra_factura_prod cfp ON p.id = cfp.id_prod_compra
        LEFT JOIN gmcv_precio pl ON pl.id_prod = p.id AND pl.ini = (
            SELECT MAX(ini) FROM gmcv_precio WHERE id_prod = p.id AND ini <= CURDATE()
            ) AND pl.id_bodega = b.id
        LEFT JOIN (
            SELECT dp.id_prod, dp.id_bodega, SUM(dp.descuento) AS totalDescuento
            FROM gmcv_descuento_producto dp
            JOIN gmcv_descuento d ON d.id = dp.id_descuento
            WHERE dp.ini <= CURDATE() AND dp.id_status = 4 AND d.posteriorCP = 0 AND d.id_status = 4
            GROUP BY dp.id_prod, dp.id_bodega
        ) AS dp ON dp.id_prod = p.id AND dp.id_bodega = b.id
        LEFT JOIN (
            SELECT dp.id_prod, dp.id_bodega, SUM(dp.descuento) AS totalDescuento
            FROM gmcv_descuento_producto dp
            JOIN gmcv_descuento d ON d.id = dp.id_descuento
            WHERE dp.ini <= CURDATE() AND dp.id_status = 4 AND d.posteriorCP = 1 AND d.id_status = 4
            GROUP BY dp.id_prod, dp.id_bodega
        ) AS dp_posterior ON dp_posterior.id_prod = p.id AND dp_posterior.id_bodega = b.id
        WHERE p.id_tipo_venta = 1 AND p.id_prov IN ($id_prov)
        GROUP BY p.id
        ORDER BY p.comercial, p.id ;
        */
        $query = "SELECT p.id, pm.id AS id_pm, p.comercial AS nombre, b.id AS id_bodega, po.id_oficina, GROUP_CONCAT(DISTINCT b.id) AS bodegas,
                p.litros, si.iva, si.ieps, si.iepsxl,
                -- Ajustar precioListaCatalogo y agregar tienePL
                CASE WHEN pl.precio IS NOT NULL THEN pl.precio ELSE 0.01 END AS precioListaCatalogo,
                CASE WHEN pl.precio IS NOT NULL THEN 1 ELSE 0 END AS tienePL,
                pl.ini as fechaInicioPL,
                -- Obtener la fecha final del precio de lista (siguiente bloque)
                (
                    SELECT MIN(ini) 
                    FROM gmcv_precio 
                    WHERE id_prod = p.id 
                    AND ini > pl.ini 
                    AND id_bodega = b.id
                ) AS fechaFinPL
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
        // file_put_contents('query_getProductosByProvBod.txt', $query);
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
                    //Antes verificamos si el id_descuento ya existe en el array de descuentosAntesCP
                    $existe = false;
                    foreach ($descuentosAntesCP as $desc) {
                        if ($desc['id_descuento'] == $descuento['id']) {
                            $existe = true;
                            break;
                        }
                    }
                    if ($existe) {
                        //Es probabable que tambien hallamos recibido un array de bodegas, por lo que como referencia tomamos el primer elemento
                        $id_bodega = explode(",", $descuento['bodegasArray'])[0];

                        //Si existe es probable que sea un descuento de otra bodega, consultamos el correcto a la db y sustituimos por el correcto
                        $descCorrecto = GmcvDescuentoProducto::getDescuentosProductoByIdDescuento($row['id'], $id_prov, $id_bodega, $fechaInicio, $descuento['id']);
                        //Verificamos si la funcion encontró un descuento correcto
                        if ($descCorrecto) {
                            //Buscamos el descuento en array de descuentosAntesCP y lo sustituimos
                            foreach ($descuentosAntesCP as $key => $desc) {
                                if ($desc['id_descuento'] == $descuento['id']) {
                                    //Sustituimos el descuento incorrecto por el correcto
                                    $descuentosAntesCP[$key]['nombre'] = $descCorrecto['nombre'];
                                    $descuentosAntesCP[$key]['tasa'] = $descCorrecto['descuento'];
                                    $descuentosAntesCP[$key]['id_descuento'] = $descCorrecto['id'];
                                    $descuentosAntesCP[$key]['bodegas'] = $descCorrecto['bodegasArray'];
                                    //Reseteamos la suma de descuentos antes de CP para recalcular
                                    $sumaDescuentosAntesCP = 0;
                                    foreach ($descuentosAntesCP as $desc) {
                                        $sumaDescuentosAntesCP += $desc['tasa'];
                                    }

                                    break;
                                } // Fin if descCorrecto
                            } // Fin foreach descuentosAntesCP
                        } // Fin if descCorrecto
                        continue;
                    }
                    $descuentoMuestra['nombre'] = $descuento['nombre'];
                    $descuentoMuestra['tasa'] = $descuento['descuento'];
                    $descuentoMuestra['id_descuento'] = $descuento['id'];
                    $descuentoMuestra['bodegas'] = $descuento['bodegasArray'];
                    $descuentosAntesCP[] = $descuentoMuestra;
                    $sumaDescuentosAntesCP += $descuento['descuento'];
                } else {
                    //Es probabable que tambien hallamos recibido un array de bodegas, por lo que como referencia tomamos el primer elemento
                    $id_bodega = explode(",", $descuento['bodegasArray'])[0];
                    //Verificamos si el id_descuento ya existe en el array de descuentosDespCP
                    $existe = false;
                    foreach ($descuentosDespCP as $desc) {
                        if ($desc['id_descuento'] == $descuento['id']) {
                            $existe = true;
                            break;
                        }
                    }
                    if ($existe) {
                        //Si existe es probable que sea un descuento de otra bodega, consultamos el correcto a la db y sustituimos por el correcto
                        //public static function getDescuentosProductoByIdDescuento($id_prod, $id_prov, $id_bodega, $fecha, $id_descuento)
                        $descCorrecto = GmcvDescuentoProducto::getDescuentosProductoByIdDescuento($row['id'], $id_prov, $id_bodega, $fechaInicio, $descuento['id']);
                        //Verificamos si la funcion encontró un descuento correcto
                        if ($descCorrecto) {
                            //Buscamos el descuento en array de descuentosDespCP y lo sustituimos
                            foreach ($descuentosDespCP as $key => $desc) {
                                if ($desc['id_descuento'] == $descuento['id']) {
                                    $descuentosDespCP[$key]['nombre'] = $descCorrecto['nombre'];
                                    $descuentosDespCP[$key]['tasa'] = $descCorrecto['descuento'];
                                    $descuentosDespCP[$key]['id_descuento'] = $descCorrecto['id'];
                                    $descuentosDespCP[$key]['bodegas'] = $descCorrecto['bodegasArray'];
                                    //Reseteamos la suma de descuentos antes de CP para recalcular
                                    $sumaDescuentosDespCP = 0;
                                    foreach ($descuentosDespCP as $desc) {
                                        $sumaDescuentosDespCP += $desc['tasa'];
                                    }
                                    break;
                                }
                            }
                        }
                        continue;
                    }
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
            //Recorremos los arreglos y verificamos que no haya algo null o vacio
            foreach ($descuentosAntesCP as $key => $desc) {
                if (empty($desc['nombre'])) {
                    unset($descuentosAntesCP[$key]);
                }
            }
            foreach ($descuentosDespCP as $key => $desc) {
                if (empty($desc['nombre'])) {
                    unset($descuentosDespCP[$key]);
                }
            }


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
            //Si fechaFinPL existe restamos 1 dia
            if ($row['fechaFinPL']) {
                $row['fechaFinPL'] = date('Y-m-d', strtotime($row['fechaFinPL'] . ' -1 day'));
            }

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
}
?>