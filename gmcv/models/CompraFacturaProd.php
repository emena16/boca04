<?php
// Incluimos el header 
include_once 'header.php';

include_once 'GmcvDescuentoProducto.php';
include_once 'GmcvPrecio.php';

class CompraFacturaProd extends db {
    private $table_name = "gmcv_compra_factura_prod";

    public $id;
    public $id_compra_factura;
    public $id_prod_compra;
    public $caducidad;
    public $cantidad_aceptada;
    public $cantidad_rechazada;
    public $cantidad_facturada;
    public $descuento;
    public $descuento_porcentaje;
    public $ajuste_bruto;
    public $costo_unitario_bruto;

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " (
            id_compra_factura,
            id_prod_compra,
            caducidad,
            cantidad_aceptada,
            cantidad_rechazada,
            cantidad_facturada,
            descuento,
            descuento_porcentaje,
            ajuste_bruto,
            costo_unitario_bruto
        ) VALUES (
            '" . db::real_escape_string($this->id_compra_factura) . "',
            '" . db::real_escape_string($this->id_prod_compra) . "',
            '" . db::real_escape_string($this->caducidad) . "',
            '" . db::real_escape_string($this->cantidad_aceptada) . "',
            '" . db::real_escape_string($this->cantidad_rechazada) . "',
            '" . db::real_escape_string($this->cantidad_facturada) . "',
            '" . db::real_escape_string($this->descuento) . "',
            '" . db::real_escape_string($this->descuento_porcentaje) . "',
            '" . db::real_escape_string($this->ajuste_bruto) . "',
            '" . db::real_escape_string($this->costo_unitario_bruto) . "'
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
            id_compra_factura = '" . db::real_escape_string($this->id_compra_factura) . "',
            id_prod_compra = '" . db::real_escape_string($this->id_prod_compra) . "',
            caducidad = '" . db::real_escape_string($this->caducidad) . "',
            cantidad_aceptada = '" . db::real_escape_string($this->cantidad_aceptada) . "',
            cantidad_rechazada = '" . db::real_escape_string($this->cantidad_rechazada) . "',
            cantidad_facturada = '" . db::real_escape_string($this->cantidad_facturada) . "',
            descuento = '" . db::real_escape_string($this->descuento) . "',
            descuento_porcentaje = '" . db::real_escape_string($this->descuento_porcentaje) . "',
            ajuste_bruto = '" . db::real_escape_string($this->ajuste_bruto) . "',
            costo_unitario_bruto = '" . db::real_escape_string($this->costo_unitario_bruto) . "'
            WHERE id = '" . db::real_escape_string($this->id) . "'";
            //Escribinmos la consulta en un archivo de texto para debug
            // file_put_contents('update_compra_factura_prod.txt', $query);
        $result = db::query($query);
        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    //Metodo para obtner por id y devolver un objeto CompraFacturaProd
    public static function getByIdObject($id) {
        $query = "SELECT * FROM gmcv_compra_factura_prod WHERE id = '" . db::real_escape_string($id) . "'";
        $result = db::query($query);
        //Creamos el objeto que vamos a devolver
        $compraFacturaProd = new CompraFacturaProd();
        //Obtenemos el resultado de la consulta
        $row = db::fetch_assoc($result);
        //Asignamos los valores al objeto
        $compraFacturaProd->id = $row['id'];
        $compraFacturaProd->id_compra_factura = $row['id_compra_factura'];
        $compraFacturaProd->id_prod_compra = $row['id_prod_compra'];
        $compraFacturaProd->caducidad = $row['caducidad'];
        $compraFacturaProd->cantidad_aceptada = $row['cantidad_aceptada'];
        $compraFacturaProd->cantidad_rechazada = $row['cantidad_rechazada'];
        $compraFacturaProd->cantidad_facturada = $row['cantidad_facturada'];
        $compraFacturaProd->descuento = $row['descuento'];
        $compraFacturaProd->descuento_porcentaje = $row['descuento_porcentaje'];
        $compraFacturaProd->ajuste_bruto = $row['ajuste_bruto'];
        $compraFacturaProd->costo_unitario_bruto = $row['costo_unitario_bruto'];
        //Devolvemos el objeto
        return $compraFacturaProd;
    }


    //Creamos el metodo para obtenr por id de compra
    public function getFacturasByCompraId($id_compra) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id_compra_factura = " . $id_compra;
        echo $query;
        $result = db::query($query);
        $data = array();
        while ($row = db::fetch_assoc($result)) {
            $data[] = $row;
        }
        return $data;
    }

    //Creamos el metodo getbyid
    public static function getById($id) {
        $query = "SELECT * FROM gmcv_compra_factura_prod WHERE id = '" . db::real_escape_string($id) . "'";
        $result = db::query($query);
        return db::fetch_assoc($result);
    }

    //Metodo para obtner los productos de una factura
    public function getProductsByFacturaId($id_factura) {
        $query = "SELECT cfp.caducidad, pc.id_prod, p.comercial, pc.cantidad, COALESCE(SUM(cfp.cantidad_aceptada), 0) AS total_cantidad_aceptada, COALESCE(SUM(cfp.cantidad_rechazada), 0) AS total_cantidad_rechazada, pc.cant_solicitada, pc.costo_unitario, pc.prod_agregado, pc.id_compra, pc.id AS id_prod_compra, pc.cant_solicitada - COALESCE(SUM(cfp.cantidad_aceptada) + SUM(cfp.cantidad_rechazada), 0) AS cantidad_restante
        FROM gmcv_compra_factura_prod cfp
        LEFT JOIN prod_compra pc ON cfp.id_prod_compra = pc.id
        LEFT JOIN producto p ON p.id = pc.id_prod
        WHERE cfp.id_compra_factura = $id_factura
        GROUP BY 
            pc.id_prod, 
            p.comercial, 
            pc.cantidad, 
            pc.cant_solicitada, 
            pc.costo_unitario, 
            pc.prod_agregado, 
            pc.id_compra, 
            pc.id";
        $result = db::query($query);
        $data = array();
        while ($row = db::fetch_assoc($result)) {
            $data[] = $row;
        }
        return $data;
    }

    //Agregamos productos a una factura nueva
    public function addProductsToFactura($id_factura, $productos) {
        $query = "INSERT INTO " . $this->table_name . " (
            id_compra_factura,
            id_prod_compra,
            caducidad,
            cantidad_aceptada,
            cantidad_rechazada,
            cantidad_facturada,
            descuento,
            descuento_porcentaje,
            ajuste_bruto,
            costo_unitario_bruto
        ) VALUES ";
        $values = array();
        foreach ($productos as $producto) {
            $values[] = "(
                '" . db::real_escape_string($id_factura) . "',
                '" . db::real_escape_string($producto['id_prod_compra']) . "',
                '" . db::real_escape_string($producto['caducidad']) . "',
                '" . db::real_escape_string($producto['cantidad_aceptada']) . "',
                '" . db::real_escape_string($producto['cantidad_rechazada']) . "',
                '" . db::real_escape_string($producto['cantidad_facturada']) . "',
                '" . db::real_escape_string($producto['descuento']) . "',
                '" . db::real_escape_string($producto['descuento_porcentaje']) . "',
                '" . db::real_escape_string($producto['ajuste_bruto']) . "',
                '" . db::real_escape_string($producto['costo_unitario_bruto']) . "'
            )";
        }
        $query .= implode(", ", $values);
        $result = db::query($query);
        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    //Creamos un metodo para vaciar los productos de una factura
    public function deleteProductsByFacturaId($id_factura) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_compra_factura = " . $id_factura;
        $result = db::query($query);
        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    //Metodo para obtner los productos de una factura
    public function getProductsByFacturaIdValidate($id_factura) {
        // 15-08-2024 esta query calculaba mal los descuentos

    //     $query = "SELECT cfp.id as id_cfp,
    //        p.comercial, p.cod_prod_prov, p.id AS id_prod, cfp.*, imp.id AS idImpuesto, imp.iva, imp.ieps, imp.iepsxl, c.id_prov as id_prov, c.id_bodega as id_bodega,
    //        cfp.costo_unitario_bruto as costoFactura,
           
    //        COALESCE(pl.precio, cfp.costo_unitario_bruto) AS precioListaCatalogo,
    //        ROUND(
    //            COALESCE(pl.precio, cfp.costo_unitario_bruto) * (1 - COALESCE(dp.totalDescuento, 0) / 100),4) AS costoPactado, 
    //        dp.totalDescuento AS sumaDescuentosAntesCP, dp_posterior.totalDescuento AS sumaDescuentoDespCP,
    //        ROUND(
    //            COALESCE(
    //                 -- Si el precio de lista es nulo, se toma el costo unitario bruto
    //                ROUND(COALESCE(pl.precio, cfp.costo_unitario_bruto) * (1 - COALESCE(dp.totalDescuento, 0) / 100), 4) * (1 - COALESCE(dp_posterior.totalDescuento, 0) / 100), cfp.costo_unitario_bruto
    //            ), 4
    //        ) AS costoIngreso,
    //        CASE 
    //          WHEN imp.iepsxl > 0 THEN imp.iepsxl * p.litros 
    //       ELSE 0 
    //      END AS totalIEPSxL
    //    FROM  gmcv_compra_factura_prod cfp
    //    LEFT JOIN  prod_compra pc ON cfp.id_prod_compra = pc.id
    //    LEFT JOIN  compra c ON c.id = pc.id_compra
    //    LEFT JOIN  producto p ON p.id = pc.id_prod
    //    JOIN  sys_impuestos imp ON imp.id = p.id_impuesto AND imp.id_status = 4
    //    LEFT JOIN  gmcv_precio AS pl ON pl.id_prod = p.id  AND pl.ini = (SELECT MAX(ini) FROM gmcv_precio WHERE id_prod = p.id AND ini <= CURDATE()) AND pl.id_bodega = c.id_bodega
    //    -- Se obtiene la suma de descuentos anteriores a CP del producto en la bodega de la compra
    //    LEFT JOIN ( SELECT id_prod, id_bodega, SUM(dp.descuento) AS totalDescuento
    //        FROM gmcv_descuento_producto dp
    //        JOIN gmcv_descuento d ON d.id = dp.id_descuento
    //        WHERE dp.ini <= CURDATE() AND dp.id_status = 4 AND d.posteriorCP = 0 AND d.id_status = 4
    //        GROUP BY dp.id_prod, dp.id_bodega
    //    ) AS dp ON dp.id_prod = p.id AND dp.id_bodega = c.id_bodega
    //    -- Se obtiene la suma de descuentos posteriores a CP del producto en la bodega de la compra
    //    LEFT JOIN ( SELECT id_prod, id_bodega, SUM(dp.descuento) AS totalDescuento
    //        FROM gmcv_descuento_producto dp
    //        JOIN gmcv_descuento d ON d.id = dp.id_descuento
    //        WHERE dp.ini <= CURDATE() AND dp.id_status = 4 AND d.posteriorCP = 1 AND d.id_status = 4
    //        GROUP BY dp.id_prod, dp.id_bodega
    //    ) AS dp_posterior ON dp_posterior.id_prod = p.id AND dp_posterior.id_bodega = c.id_bodega
    //    WHERE cfp.id_compra_factura = $id_factura";
        $query = "SELECT cfp.id as id_cfp,
            p.comercial, p.cod_prod_prov, p.id AS id_prod, cfp.*, imp.id AS idImpuesto, imp.iva, imp.ieps, imp.iepsxl, c.id_prov as id_prov, c.id_bodega as id_bodega,
            cfp.costo_unitario_bruto as costoFactura,
            COALESCE(pl.precio, cfp.costo_unitario_bruto) AS precioListaCatalogo,
            ROUND(COALESCE(pl.precio, cfp.costo_unitario_bruto) * (1 - COALESCE(dp.totalDescuento, 0) / 100), 4) AS costoPactadoCalculadoPast, dp.totalDescuento AS sumaDescuentosAntesCP, dp_posterior.totalDescuento AS sumaDescuentoDespCP,
            ROUND(
                COALESCE(
                    ROUND(COALESCE(pl.precio, cfp.costo_unitario_bruto) * (1 - COALESCE(dp.totalDescuento, 0) / 100), 4) * (1 - COALESCE(dp_posterior.totalDescuento, 0) / 100), cfp.costo_unitario_bruto
                ), 4
            ) AS costoIngreso, cfp.costo_unitario_bruto as costoPactado,
            CASE     WHEN imp.iepsxl > 0 THEN imp.iepsxl * p.litros     ELSE 0 END AS totalIEPSxL
        FROM gmcv_compra_factura_prod cfp
        JOIN gmcv_compra_factura cf on cf.id = cfp.id_compra_factura
        LEFT JOIN prod_compra pc ON cfp.id_prod_compra = pc.id
        LEFT JOIN compra c ON c.id = pc.id_compra
        LEFT JOIN producto p ON p.id = pc.id_prod
        JOIN sys_impuestos imp ON imp.id = p.id_impuesto AND imp.id_status = 4
        LEFT JOIN gmcv_precio AS pl ON pl.id_prod = p.id AND pl.ini = (
                SELECT MAX(ini) FROM gmcv_precio WHERE id_prod = p.id AND ini <= DATE(cf.fecha_llegada)
            ) AND pl.id_bodega = c.id_bodega
        LEFT JOIN (
                SELECT dp.id_prod, dp.id_bodega, SUM(dp.descuento) AS totalDescuento
                FROM gmcv_descuento_producto dp
                JOIN gmcv_descuento d ON d.id = dp.id_descuento
                CROSS JOIN (SELECT cf.fecha_llegada FROM gmcv_compra_factura cf WHERE cf.id = $id_factura) AS fecha WHERE dp.ini = (
                        SELECT MAX(dp2.ini)
                        FROM gmcv_descuento_producto dp2
                        WHERE dp2.id_prod = dp.id_prod AND dp2.id_bodega = dp.id_bodega
                        AND dp2.ini <= DATE(fecha.fecha_llegada)
                    )
                    AND dp.id_status = 4 AND d.posteriorCP = 0 AND d.id_status = 4
                GROUP BY dp.id_prod, dp.id_bodega
            ) AS dp ON dp.id_prod = p.id AND dp.id_bodega = c.id_bodega
        LEFT JOIN (
                SELECT dp.id_prod, dp.id_bodega, SUM(dp.descuento) AS totalDescuento
                FROM gmcv_descuento_producto dp
                JOIN gmcv_descuento d ON d.id = dp.id_descuento
                CROSS JOIN (SELECT cf.fecha_llegada FROM gmcv_compra_factura cf WHERE cf.id = $id_factura) AS fecha WHERE dp.ini = (
                        SELECT MAX(dp2.ini)
                        FROM gmcv_descuento_producto dp2
                        WHERE dp2.id_prod = dp.id_prod     AND dp2.id_bodega = dp.id_bodega
                        AND dp2.ini <= DATE(fecha.fecha_llegada)
                    )
                    AND dp.id_status = 4 AND d.posteriorCP = 1 AND d.id_status = 4
                GROUP BY dp.id_prod, dp.id_bodega
            ) AS dp_posterior ON dp_posterior.id_prod = p.id AND dp_posterior.id_bodega = c.id_bodega
        WHERE cfp.id_compra_factura = $id_factura";
        $result = db::query($query);

        while ($row = db::fetch_assoc($result)) {
            //Convertimos en valor absoluto el campo costo_ingreso
            $row['costoIngreso'] = strval(abs($row['costoIngreso']));
            $data[] = $row;
        }


        return $data;
    }


    //Metodo para obtner los productos de una factura
    public function getProductsByFacturaIdValidateCost($id_factura,$descuentoAjuste) {
        /**
        * Descuento ajuste:
        * 1.- vs Costo Pactado
        * 2.- vs Costo Ingreso
        * 3.- vs Precio de lista
        */
//     $query = "SELECT cfp.id as id_cfp, 
//     cf.fecha as fechaFactura,
//     p.comercial, p.cod_prod_prov, p.id AS id_prod, cfp.*, imp.id AS idImpuesto, imp.iva, imp.ieps, imp.iepsxl, 
//     c.id_prov as id_prov, c.id_bodega as id_bodega,
//     cfp.costo_unitario_bruto as costoFactura,
//     COALESCE(pl.precio, cfp.costo_unitario_bruto) AS precioListaCatalogo,
//     ROUND(COALESCE(pl.precio, cfp.costo_unitario_bruto) * (1 - COALESCE(dp.totalDescuento, 0) / 100), 4) AS costoPactado, 
//     dp.totalDescuento AS sumaDescuentosAntesCP, 
//     dp.nombresDescuentos AS nombresDescuentosAntesCP, 
//     dp_posterior.totalDescuento AS sumaDescuentoDespCP,
//     dp_posterior.nombresDescuentos AS nombresDescuentosDespCP, 
//     ROUND(
//         COALESCE(
//             ROUND(COALESCE(pl.precio, cfp.costo_unitario_bruto) * (1 - COALESCE(dp.totalDescuento, 0) / 100), 4) * (1 - COALESCE(dp_posterior.totalDescuento, 0) / 100), cfp.costo_unitario_bruto
//         ), 4
//     ) AS costoIngreso,
//     CASE WHEN imp.iepsxl > 0 THEN imp.iepsxl * p.litros ELSE 0 END AS totalIEPSxL
// FROM gmcv_compra_factura_prod cfp
// JOIN gmcv_compra_factura cf on cf.id = cfp.id_compra_factura
// LEFT JOIN prod_compra pc ON cfp.id_prod_compra = pc.id
// LEFT JOIN compra c ON c.id = pc.id_compra
// LEFT JOIN producto p ON p.id = pc.id_prod
// JOIN sys_impuestos imp ON imp.id = p.id_impuesto AND imp.id_status = 4
// LEFT JOIN gmcv_precio AS pl ON pl.id_prod = p.id AND pl.ini = (
//         SELECT MAX(ini) FROM gmcv_precio WHERE id_prod = p.id AND ini <= DATE(cf.fecha)
//     ) AND pl.id_bodega = c.id_bodega
// LEFT JOIN (
//         SELECT dp.id_prod, dp.id_bodega, 
//             SUM(dp.descuento) AS totalDescuento, 
//             GROUP_CONCAT(d.nombre ORDER BY d.nombre ASC SEPARATOR ', ') AS nombresDescuentos
//         FROM gmcv_descuento_producto dp
//         JOIN gmcv_descuento d ON d.id = dp.id_descuento
//         CROSS JOIN (SELECT cf.fecha FROM gmcv_compra_factura cf WHERE cf.id = 285) AS fecha     
//         WHERE dp.ini = (
//                 SELECT MAX(dp2.ini)
//                 FROM gmcv_descuento_producto dp2
//                 WHERE dp2.id_prod = dp.id_prod AND dp2.id_bodega = dp.id_bodega
//                 AND dp2.ini <= DATE(fecha.fecha)
//             )
//             AND dp.id_status = 4 AND d.posteriorCP = 0 AND d.id_status = 4
//         GROUP BY dp.id_prod, dp.id_bodega
//     ) AS dp ON dp.id_prod = p.id AND dp.id_bodega = c.id_bodega
// LEFT JOIN (
//         SELECT dp.id_prod, dp.id_bodega, 
//             SUM(dp.descuento) AS totalDescuento, 
//             GROUP_CONCAT(d.nombre ORDER BY d.nombre ASC SEPARATOR ', ') AS nombresDescuentos
//         FROM gmcv_descuento_producto dp
//         JOIN gmcv_descuento d ON d.id = dp.id_descuento
//         CROSS JOIN (SELECT cf.fecha FROM gmcv_compra_factura cf WHERE cf.id = 285) AS fecha     
//         WHERE dp.ini = (
//                 SELECT MAX(dp2.ini)
//                 FROM gmcv_descuento_producto dp2
//                 WHERE dp2.id_prod = dp.id_prod AND dp2.id_bodega = dp.id_bodega
//                 AND dp2.ini <= DATE(fecha.fecha)
//             )
//             AND dp.id_status = 4 AND d.posteriorCP = 1 AND d.id_status = 4
//         GROUP BY dp.id_prod, dp.id_bodega
//     ) AS dp_posterior ON dp_posterior.id_prod = p.id AND dp_posterior.id_bodega = c.id_bodega
// WHERE cfp.id_compra_factura = 285";
    $query = "SELECT cfp.id as id_cfp, cf.fecha as fechaFactura,
            p.comercial, p.cod_prod_prov, p.id AS id_prod, cfp.*, imp.id AS idImpuesto, imp.iva, imp.ieps, imp.iepsxl, c.id_prov as id_prov, c.id_bodega as id_bodega,
            cfp.costo_unitario_bruto as costoFactura,
            COALESCE(pl.precio, cfp.costo_unitario_bruto) AS precioListaCatalogo,
            ROUND(COALESCE(pl.precio, cfp.costo_unitario_bruto) * (1 - COALESCE(dp.totalDescuento, 0) / 100), 4) AS costoPactado, dp.totalDescuento AS sumaDescuentosAntesCP, dp_posterior.totalDescuento AS sumaDescuentoDespCP,
            ROUND(
                COALESCE(
                    ROUND(COALESCE(pl.precio, cfp.costo_unitario_bruto) * (1 - COALESCE(dp.totalDescuento, 0) / 100), 4) * (1 - COALESCE(dp_posterior.totalDescuento, 0) / 100), cfp.costo_unitario_bruto
                ), 4
            ) AS costoIngreso,
            CASE     WHEN imp.iepsxl > 0 THEN imp.iepsxl * p.litros     ELSE 0 END AS totalIEPSxL
        FROM gmcv_compra_factura_prod cfp
        JOIN gmcv_compra_factura cf on cf.id = cfp.id_compra_factura
        LEFT JOIN prod_compra pc ON cfp.id_prod_compra = pc.id
        LEFT JOIN compra c ON c.id = pc.id_compra
        LEFT JOIN producto p ON p.id = pc.id_prod
        JOIN sys_impuestos imp ON imp.id = p.id_impuesto AND imp.id_status = 4
        LEFT JOIN gmcv_precio AS pl ON pl.id_prod = p.id AND pl.ini = (
                SELECT MAX(ini) FROM gmcv_precio WHERE id_prod = p.id AND ini <= DATE(cf.fecha)
            ) AND pl.id_bodega = c.id_bodega
        LEFT JOIN (
                SELECT dp.id_prod, dp.id_bodega, SUM(dp.descuento) AS totalDescuento
                FROM gmcv_descuento_producto dp
                JOIN gmcv_descuento d ON d.id = dp.id_descuento
                CROSS JOIN (SELECT cf.fecha FROM gmcv_compra_factura cf WHERE cf.id = $id_factura) AS fecha     WHERE dp.ini = (
                        SELECT MAX(dp2.ini)
                        FROM gmcv_descuento_producto dp2
                        WHERE dp2.id_prod = dp.id_prod     AND dp2.id_bodega = dp.id_bodega
                        AND dp2.ini <= DATE(fecha.fecha)
                    )
                    AND dp.id_status = 4 AND d.posteriorCP = 0 AND d.id_status = 4
                GROUP BY dp.id_prod, dp.id_bodega
            ) AS dp ON dp.id_prod = p.id AND dp.id_bodega = c.id_bodega
        LEFT JOIN (
                SELECT dp.id_prod, dp.id_bodega, SUM(dp.descuento) AS totalDescuento
                FROM gmcv_descuento_producto dp
                JOIN gmcv_descuento d ON d.id = dp.id_descuento
                CROSS JOIN (SELECT cf.fecha FROM gmcv_compra_factura cf WHERE cf.id = $id_factura) AS fecha     WHERE dp.ini = (
                        SELECT MAX(dp2.ini)
                        FROM gmcv_descuento_producto dp2
                        WHERE dp2.id_prod = dp.id_prod     AND dp2.id_bodega = dp.id_bodega
                        AND dp2.ini <= DATE(fecha.fecha)
                    )
                    AND dp.id_status = 4 AND d.posteriorCP = 1 AND d.id_status = 4
                GROUP BY dp.id_prod, dp.id_bodega
            ) AS dp_posterior ON dp_posterior.id_prod = p.id AND dp_posterior.id_bodega = c.id_bodega
        WHERE cfp.id_compra_factura = $id_factura";

        $result = db::query($query);
        $descuentoMuestra = array(
            "nombre" => "",
            "tasa" => 0,
            "id_descuento" => 0
        );
        $descuentosAntesCP = array();
        $descuentosDespCP = array();

        //Producto con mas descuentos antes de CP
        $productoMasDescuentosAntesCP = array();

        //Producto con mas descuentos despues de CP
        $productoMasDescuentosDespCP = array();

        while ($row = db::fetch_assoc($result)) {
            //Convertimos en valor absoluto el campo costo_ingreso
            $row['costoIngreso'] = strval(abs($row['costoIngreso']));
            //Obtnemos los descuentos del producto
            $descuentosAux = GmcvDescuentoProducto::getDescuentosProducto($row['id_prod'], $row['id_prov'], $row['id_bodega'], $row['fechaFactura']);

            $sumaDescuentosAntesCP = 0;
            $sumaDescuentosDespCP = 0;


            //Recorremos los descuentos para separar con respecto a costo pactado posteriorCP 
            foreach ($descuentosAux as $descuento) {
                if ($descuento['posteriorCP'] == 0) {
                    $descuentoMuestra['nombre'] = $descuento['nombre'];
                    $descuentoMuestra['tasa'] = $descuento['descuento'];
                    $descuentoMuestra['id_descuento'] = $descuento['id'];
                    $descuentosAntesCP[] = $descuentoMuestra;
                    //Sumamos los descuentos antes de CP
                    $sumaDescuentosAntesCP += $descuento['descuento'];
                } else {
                    $descuentoMuestra['nombre'] = $descuento['nombre'];
                    $descuentoMuestra['tasa'] = $descuento['descuento'];
                    $descuentoMuestra['id_descuento'] = $descuento['id'];
                    $descuentosDespCP[] = $descuentoMuestra;
                    //Sumamos los descuentos despues de CP
                    $sumaDescuentosDespCP += $descuento['descuento'];
                }
                //Reiniciamos el array descuentoMuestra para el siguiente descuento
                $descuentoMuestra = array(
                    "nombre" => "",
                    "tasa" => 0,
                    "id_descuento" => 0
                );
            }

            //Agregamos los descuentos al array de productos 
            $row['descuentosAntesCP'] = $descuentosAntesCP;
            $row['descuentosDespCP'] = $descuentosDespCP;

            //Calculamos la diferencia porcentual en funcion a la peticion del usuario $descuentoAjuste
            switch ($descuentoAjuste) {
                //Costo pactado
                case "1":
                    //Reclaculamos el costo pactado
                    $row['costoPactado'] = $row['precioListaCatalogo'] * (1 - ($sumaDescuentosAntesCP / 100));
                    //Calculamos el valor original
                    $valorOriginal = $row['costoPactado']/(1-($sumaDescuentosAntesCP/100));

                    //Calculamos el ajuste por rechazo
                    $row['descuentoRechazo'] = $row['costoFactura'] * $row['cantidad_rechazada'];

                    //Calculamos la diferencia porcentual
                    $row['porcentajeResultante'] = ((($row['costoFactura'] - $valorOriginal)/$valorOriginal) * 100);
                    //Ya conocemos el porcentaje resultante con respecto al costo pactado, calculamos la diferencia con los descuentos antes de CP
                    $row['diffPorcentaje'] = abs($row['porcentajeResultante']) - $sumaDescuentosAntesCP;
                    $row['diffMoneda'] = $row['costoPactado'] - $row['costoFactura'];
                    //Recalculamos el costo de ingreso conciderando el ajuste monetario al costo de factura si la diferencia porcentual es mayor a 0
                    if (floatval($row['diffPorcentaje']) > 0) {
                        //Si la diferencia porcentual es negativa, el costo de ingreso es el costo de factura. A Esto le aplicamos los desuentos despues de CP
                        $row['costoIngreso'] = $row['costoFactura'] * (1 - ($sumaDescuentosDespCP / 100));
                    }else{
                        $row['costoIngreso'] = $row['costoPactado'] * (1 - ($sumaDescuentosDespCP / 100));
                    }

                    //Calculamos el ajuste por descuento en moneda teniendo en cuenta que la diferencia porcentual es menor a 0 por que el costo de factura es menor al costo pactado
                    $row['ajusteDescuento'] = $row['diffMoneda'] * $row['cantidad_aceptada'];

                    // Invierte el signo del ajuste por descuento
                    $row['ajusteDescuento'] = -$row['ajusteDescuento'];

                    //Calculamos el sub total ingreso
                    $row['subTotalFact'] = $row['costoFactura'] * ($row['cantidad_aceptada'] + $row['cantidad_rechazada']);
                    $row['subTotalIngreso'] = $row['subTotalFact'] - $row['ajusteDescuento'] - $row['descuentoRechazo'];

                    $siglaDescuentoAjuste = 'CP';
                    $nombreDescuentoAjuste = 'Costo Pactado';
                    
                    break;
                //Costo ingreso
                case "2":
                    //Reclaculamos el costo pactado
                    $row['costoPactado'] = $row['precioListaCatalogo'] * (1 - ($sumaDescuentosAntesCP / 100));

                    //Calculamos el valor original
                    $valorOriginal = $row['costoPactado']/(1-($sumaDescuentosAntesCP/100));
                    
                    //Calculamos el ajuste por rechazo
                    $row['descuentoRechazo'] = $row['costoFactura'] * $row['cantidad_rechazada'];

                    //Calculamos la diferencia porcentual
                    $row['porcentajeResultante'] = ((($row['costoFactura'] - $valorOriginal)/$valorOriginal) * 100);
                    //Ya conocemos el porcentaje resultante con respecto al costo pactado, calculamos la diferencia con los descuentos antes de CP
                    $row['diffPorcentaje'] = abs($row['porcentajeResultante']) - $sumaDescuentosAntesCP;
                    $row['diffMoneda'] = $row['costoPactado'] - $row['costoFactura'];
                    //Recalculamos el costo de ingreso conciderando el ajuste monetario al costo de factura si la diferencia porcentual es mayor a 0
                    if (floatval($row['diffPorcentaje']) > 0) {
                        //Si la diferencia porcentual es negativa, el costo de ingreso es el costo de factura. A Esto le aplicamos los desuentos despues de CP
                        $row['costoIngreso'] = $row['costoFactura'] * (1 - ($sumaDescuentosDespCP / 100));
                    }else{
                        $row['costoIngreso'] = $row['costoPactado'] * (1 - ($sumaDescuentosDespCP / 100));
                    }

                    //El costo ingreso es mas facil por que es el costo de factura multiplicado por la cantidad aceptada
                    $row['subTotalSistema'] = $row['costoPactado'] * $row['cantidad_aceptada'];

                    $row['subTotalFactura'] = $row['costoIngreso'] * $row['cantidad_aceptada'];
                    //Calculamos la diferencia monetaria
                    $row['diffMoneda'] = $row['subTotalFactura'] - $row['subTotalSistema'];
                    //Calculamos el ajuste por rechazo
                    $row['descuentoRechazo'] = $row['costoFactura'] * $row['cantidad_rechazada'];

                    //Calculamos el ajuste por descuento en moneda teniendo en cuenta que la diferencia porcentual es menor a 0 por que el costo de factura es menor al costo pactado
                    $row['ajusteDescuento'] = $row['subTotalSistema'] - $row['subTotalFactura'];

                    //Calculamos el sub total ingreso
                    $row['subTotalFact'] = $row['costoFactura'] * ($row['cantidad_aceptada'] + $row['cantidad_rechazada']);
                    $row['subTotalIngreso'] = $row['subTotalFact'] - $row['ajusteDescuento'] - $row['descuentoRechazo'];

                    $siglaDescuentoAjuste = 'CI';
                    $nombreDescuentoAjuste = 'Costo Ingreso';

                    break;
                //Precio de lista
                case "3":
                    //Reclaculamos el costo pactado
                    $row['costoPactado'] = $row['precioListaCatalogo'] * (1 - ($sumaDescuentosAntesCP / 100));
                    //Calculamos el valor vel precio de lista de acuerdo con lo facturado
                    $valorOriginal = $row['costoFactura']/(1-($sumaDescuentosAntesCP/100));
                    //Calculamos el ajuste por rechazo
                    $row['descuentoRechazo'] = $row['costoFactura'] * $row['cantidad_rechazada'];
                    //Calculamos la diferencia porcentual entrre el valor original y row'precioListaCatalogo'
                    $row['diffPorcentaje'] = ((($valorOriginal - $row['precioListaCatalogo']) / $valorOriginal) * 100);
                    //Calculamos la diferencia monetaria
                    $row['diffMoneda'] = $valorOriginal - $row['precioListaCatalogo'];
                    //Hacemos los calculos para calcular la diferencia en precio de lista

                    //Si tienemos una diferencia negativa quiere decir que el precio es menor al costo de factura
                    $row['diffMoneda'] < 0 ? $row['ajusteDescuento'] = $row['diffMoneda'] * $row['cantidad_aceptada'] : $row['ajusteDescuento'] = 0;

                    //Calculamos el ajuste por descuento en moneda teniendo en cuenta que la diferencia porcentual es menor a 0 por que el costo de factura es menor al costo pactado
                    $row['ajusteDescuento'] = $row['diffMoneda'] * $row['cantidad_aceptada'];

                    $row['valorOriginal'] = $valorOriginal;

                    //Calculamos el sub total ingreso
                    $row['subTotalFact'] = $row['costoFactura'] * ($row['cantidad_aceptada'] + $row['cantidad_rechazada']);
                    $row['subTotalIngreso'] = $row['subTotalFact'] - $row['ajusteDescuento'] - $row['descuentoRechazo'];

                    $siglaDescuentoAjuste = 'PL';
                    $nombreDescuentoAjuste = 'Precio Lista';


                    break;

                default:
                    $row['diffPorcentaje'] = 0.01;
                    break;
            }

            //Si este producto tiene mas descuentos que el anterior, se guarda
            if (count($descuentosAntesCP) > count($productoMasDescuentosAntesCP)) {
                $productoMasDescuentosAntesCP = $descuentosAntesCP;
            }

            //Si este producto tiene mas descuentos que el anterior, se guarda
            if (count($descuentosDespCP) > count($productoMasDescuentosDespCP)) {
                $productoMasDescuentosDespCP = $descuentosDespCP;
            }

            $data[] = $row;

            //Reiniciamos los arrays de descuentos
            $descuentosAntesCP = array();
            $descuentosDespCP = array();
        }

        //Array de respone 
        $response = array(
            "productos" => $data,
            "descuentosAntesCP" => $productoMasDescuentosAntesCP,
            "descuentosDespCP" => $productoMasDescuentosDespCP,
            'nombreDescuentoAjuste' => $nombreDescuentoAjuste,
            'siglaDescuentoAjuste' => $siglaDescuentoAjuste
        );

        return $response;
    }

    
}


?>