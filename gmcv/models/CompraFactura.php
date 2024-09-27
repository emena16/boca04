<?php
// Incluimos el header
include_once 'header.php';

class CompraFactura extends db {
    private $table_name = "gmcv_compra_factura";
    public $id;
    public $id_compra;
    public $id_status;
    public $uuid;
    public $fecha;
    public $tipo;
    public $ajuste;
    public $tipoAjuste;
    public $id_status_pago;
    public $validada;
    public $fecha_compromiso;
    public $fecha_llegada;
    public $fecha_alerta;
    public $ingreso_almacen;
    

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " (
            id_compra,
            id_status,
            uuid,
            fecha,
            tipo,
            ajuste,
            tipoAjuste,
            id_status_pago,
            validada,
            fecha_llegada,
            fecha_alerta,
            fecha_compromiso
        ) VALUES (
            '" . db::real_escape_string($this->id_compra) . "',
            '" . db::real_escape_string($this->id_status) . "',
            '" . db::real_escape_string($this->uuid) . "',
            '" . db::real_escape_string($this->fecha) . "',
            '" . db::real_escape_string($this->tipo) . "',
            " . number_format($this->ajuste, 4, '.', '') . ",
            '" . db::real_escape_string($this->tipoAjuste) . "',
            '" . db::real_escape_string($this->id_status_pago) . "',
            '" . db::real_escape_string($this->validada) . "',
            '" . db::real_escape_string($this->fecha_llegada) . "',
            '" . db::real_escape_string($this->fecha_alerta) . "',
            '" . db::real_escape_string($this->fecha_compromiso) . "'
        )";
        //Escribimos el query en un archivo de texto 
        file_put_contents('createFactura.txt', $query);
        $result = db::query($query);

        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    //Creamos una funcion para recibir un numero y descomponerlo a 4 decimales
    public function getNumber($number) {
        return number_format($number, 4, '.', '');
    }


    public function update(){
        $this->fecha_alerta = empty($this->fecha_alerta) ? date('Y-m-d') : $this->fecha_alerta;
        $this->fecha_compromiso = empty($this->fecha_compromiso) ? date('Y-m-d') : $this->fecha_compromiso;
        $query = "UPDATE " . $this->table_name . " SET 
            id_compra = '" . db::real_escape_string($this->id_compra) . "',
            id_status = '" . db::real_escape_string($this->id_status) . "',
            uuid = '" . db::real_escape_string($this->uuid) . "',
            fecha = '" . db::real_escape_string($this->fecha) . "',
            tipo = '" . db::real_escape_string($this->tipo) . "',
            ajuste = " . number_format($this->ajuste, 4, '.', '') . ",
            tipoAjuste = '" . db::real_escape_string($this->tipoAjuste) . "',
            id_status_pago = '" . db::real_escape_string($this->id_status_pago) . "',
            validada = '" . db::real_escape_string($this->validada) . "',
            fecha_llegada = '" . db::real_escape_string($this->fecha_llegada) . "',
            fecha_alerta = '" . db::real_escape_string($this->fecha_alerta) . "',
            fecha_compromiso = '" . db::real_escape_string($this->fecha_compromiso) . "',
            ingreso_almacen = '" . db::real_escape_string($this->ingreso_almacen) . "'
            WHERE id = '" . db::real_escape_string($this->id) . "'";
            //Escribimos el query en un archivo de texto
            file_put_contents('updateFactura.txt', $query);

        $result = db::query($query);

        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    public static function getFacturasByCompraId($id_compra) {
        
        $query = "SELECT cf.*, SUM(cfp.cantidad_aceptada) AS tAceptado, SUM(cfp.cantidad_rechazada) AS tRechazado, SUM(cfp.cantidad_facturada) AS tFacturado
                FROM gmcv_compra_factura cf
                JOIN gmcv_compra_factura_prod cfp ON cfp.id_compra_factura = cf.id
                WHERE cf.id_compra = '".db::real_escape_string($id_compra)."'
                GROUP BY cf.id;";
        $result = db::query($query);

        $facturas = array();
        while ($row = db::fetch_assoc($result)) {
            $facturas[] = $row;
        }
        return $facturas;
    }

    public function getDescuentos() {
        return GmcvDescuento::getDescuentosByFacturaId($this->id);
    }

    public static function checkUuid($uuid) {
        $query = "SELECT * FROM gmcv_compra_factura WHERE uuid = '" . db::real_escape_string($uuid) . "'";
        $result = db::query($query);
        
        $response = [
            'exists' => db::num_rows($result) > 0
        ];
        return $response;
    }

    //Obtenemos los datos de una factura por id
    public static function getById($id) {
        $query = "SELECT * FROM gmcv_compra_factura WHERE id = '" . db::real_escape_string($id) . "'";
        $result = db::query($query);

        if (db::num_rows($result) > 0) {
            return db::fetch_assoc($result);
        } else {
            return false;
        }
    }

    //Creamos un getByIdObject para obtener los datos de una factura por id y devolver un objeto
    public function getByIdObject($id) {
        
        $factura = $this->getById($id);
        //Instancia de la clase CompraFactura
        $compraFactura = new CompraFactura();
        foreach ($factura as $key => $value) {
            $compraFactura->$key = $value;
        }
        return $compraFactura;
    }

    //Creamos un metodo para actualizar los datos de una factura
    public function updateFacturaEntrada() {
        //Solo actualizamos los campos de fecha y fecha de llegada
        $query = "UPDATE " . $this->table_name . " SET 
            uuid = '" . db::real_escape_string($this->uuid) . "',
            fecha = '" . db::real_escape_string($this->fecha) . "',
            fecha_llegada = '" . db::real_escape_string($this->fecha_llegada) . "'
            WHERE id = '" . db::real_escape_string($this->id) . "'";
            
        
        $result = db::query($query);

        if ($result) {
            return true;
        } else {
            return false;
        }
    }



    ///////////// VALIDACIONES DE FACTURAS /////////////

    public function getResumenFacturasValidadas($fechaIni, $fachaFin, $id_prov) {
        // $query = "SELECT cf.id_compra,b.nombre AS bodega,cf.fecha,cf.fecha_llegada, cf.uuid,
        // SUM(cf.total - cf.total_iva - cf.total_ieps) AS costo_sin_impuestos,
        // SUM(cfp.descuento) AS descuento,
        // SUM(cf.total_iva) AS total_iva,
        // SUM(cf.total_ieps) AS total_ieps,
        // SUM(cf.total) AS total_a_pagar,
        // (SUM(cfp.descuento) / SUM(cf.total - cf.total_iva - cf.total_ieps)) * 100 AS porcentaje_descuento, 
        // cf.total, p.corto, p.largo as razonSocial, cf.id as idFactura, cf.id_status AS status_factura, 
        // cf.subTotalBruto, cf.subTotalNeto, cf.descRechazo, cf.ivaTraslado, cf.iepsTraslado, cf.totalAPagar, cf.descGlobal
        // FROM gmcv_compra_factura cf
        // JOIN compra c ON cf.id_compra = c.id
        // JOIN bodega b ON c.id_bodega = b.id
        // JOIN proveedor p ON c.id_prov = p.id
        // LEFT JOIN gmcv_compra_factura_prod cfp ON cf.id = cfp.id_compra_factura
        // WHERE c.id_prov = $id_prov AND cf.fecha BETWEEN DATE('$fechaIni') AND DATE('$fachaFin') AND cf.validada = 1
        // GROUP BY cf.id
        // ORDER BY cf.fecha_llegada";


        // Query: -- v3.0.0
        $query = "SELECT 
            -- Sumar costos sin impuestos
            SUM(cfp.costo_unitario_bruto * cfp.cantidad_facturada) AS costo_sin_impuestos,

            -- Calcular IEPS y sumar IEPSxL
            SUM(ROUND(cfp.costo_unitario_bruto * cfp.cantidad_facturada * imp.ieps, 4)) AS total_ieps,
            SUM(CASE 
                    WHEN imp.iepsxl > 0 THEN imp.iepsxl * p.litros * cfp.cantidad_facturada
                    ELSE 0 
                END) AS total_iepsxL,

            -- Calcular IVA después de aplicar IEPS
            SUM(ROUND((cfp.costo_unitario_bruto * cfp.cantidad_facturada + cfp.costo_unitario_bruto * cfp.cantidad_facturada * imp.ieps + CASE WHEN imp.iepsxl > 0 THEN imp.iepsxl * p.litros * cfp.cantidad_facturada ELSE 0 END) * imp.iva, 4)) AS total_iva,

            -- Total incluyendo impuestos
            SUM(
                cfp.costo_unitario_bruto * cfp.cantidad_facturada 
                + ROUND((cfp.costo_unitario_bruto * cfp.cantidad_facturada + cfp.costo_unitario_bruto * cfp.cantidad_facturada * imp.ieps + CASE WHEN imp.iepsxl > 0 THEN imp.iepsxl * p.litros * cfp.cantidad_facturada ELSE 0 END) * imp.iva, 4)
                + ROUND(cfp.costo_unitario_bruto * cfp.cantidad_facturada * imp.ieps, 4)
                + CASE 
                    WHEN imp.iepsxl > 0 THEN imp.iepsxl * p.litros * cfp.cantidad_facturada
                    ELSE 0 
                END
            ) AS total,

            -- Total después de descontar cantidades rechazadas
            SUM(cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada)) AS totaldescuentoBruto,
            SUM(ROUND((cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada) + cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada) * imp.ieps + CASE WHEN imp.iepsxl > 0 THEN imp.iepsxl * p.litros * (cfp.cantidad_facturada - cfp.cantidad_rechazada) ELSE 0 END) * imp.iva, 4)) AS ivaTraslado,
            SUM(ROUND(cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada) * imp.ieps, 4)) AS iepsTraslado,
            SUM(CASE 
                    WHEN imp.iepsxl > 0 THEN imp.iepsxl * p.litros * (cfp.cantidad_facturada - cfp.cantidad_rechazada)
                    ELSE 0 
                END) AS iepsTrasladoxL,
            SUM(
                cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada)
                + ROUND((cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada) + cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada) * imp.ieps + CASE WHEN imp.iepsxl > 0 THEN imp.iepsxl * p.litros * (cfp.cantidad_facturada - cfp.cantidad_rechazada) ELSE 0 END) * imp.iva, 4)
                + ROUND(cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada) * imp.ieps, 4)
                + CASE 
                    WHEN imp.iepsxl > 0 THEN imp.iepsxl * p.litros * (cfp.cantidad_facturada - cfp.cantidad_rechazada)
                    ELSE 0 
                END
            ) AS subTotalNeto,

            -- Monto total descontado
            SUM(
                cfp.costo_unitario_bruto * cfp.cantidad_rechazada 
                + ROUND((cfp.costo_unitario_bruto * cfp.cantidad_rechazada + cfp.costo_unitario_bruto * cfp.cantidad_rechazada * imp.ieps + CASE WHEN imp.iepsxl > 0 THEN imp.iepsxl * p.litros * cfp.cantidad_rechazada ELSE 0 END) * imp.iva, 4)
                + ROUND(cfp.costo_unitario_bruto * cfp.cantidad_rechazada * imp.ieps, 4)
                + CASE 
                    WHEN imp.iepsxl > 0 THEN imp.iepsxl * p.litros * cfp.cantidad_rechazada
                    ELSE 0 
                END
            ) AS descGlobalImpuestos,    

            -- Sumar los descuentos globales usando un subquery
            COALESCE((
                SELECT SUM(cfd.descuento) 
                FROM gmcv_compra_factura_desc cfd 
                WHERE cfd.id_compra_factura = cf.id
            ), 0) AS descGlobal,

            -- Total después de aplicar los descuentos globales
            (
                SUM(
                    cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada)
                    + ROUND((cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada) + cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada) * imp.ieps + CASE WHEN imp.iepsxl > 0 THEN imp.iepsxl * p.litros * (cfp.cantidad_facturada - cfp.cantidad_rechazada) ELSE 0 END) * imp.iva, 4)
                    + ROUND(cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada) * imp.ieps, 4)
                    + CASE 
                        WHEN imp.iepsxl > 0 THEN imp.iepsxl * p.litros * (cfp.cantidad_facturada - cfp.cantidad_rechazada)
                        ELSE 0 
                    END
                ) - COALESCE((
                    SELECT SUM(cfd.descuento) 
                    FROM gmcv_compra_factura_desc cfd 
                    WHERE cfd.id_compra_factura = cf.id
                ), 0)
            ) AS totalAPagar,

            -- Total después de aplicar los descuentos globales
            (
                SUM(
                    ((cfp.costo_unitario_bruto * cfp.cantidad_facturada) - ((cfp.costo_unitario_bruto * cfp.cantidad_rechazada) + cfp.ajuste_bruto )) +
                    ROUND((cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada) + cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada) * imp.ieps) * imp.iva, 4) +
                    ROUND(cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada) * imp.ieps, 4) +
                    CASE 
                        WHEN imp.iepsxl > 0 THEN imp.iepsxl * p.litros * (cfp.cantidad_facturada - cfp.cantidad_rechazada)
                        ELSE 0 
                    END
                ) - COALESCE((
                    SELECT SUM(cfd.descuento) 
                    FROM gmcv_compra_factura_desc cfd 
                    WHERE cfd.id_compra_factura = cf.id
                ), 0)
            ) AS totalAPagar2,

            -- Campos complementarios L1
            cf.id_compra, b.nombre AS bodega, cf.fecha, cf.fecha_llegada, cf.uuid,

            -- L2
            SUM(cfp.descuento) AS descuento, 
            SUM(cfp.costo_unitario_bruto * cfp.cantidad_rechazada) AS descRechazo,
            proveedor.corto, proveedor.largo as razonSocial, cf.id as idFactura, cf.id_status AS status_factura,
            SUM(cfp.costo_unitario_bruto * cfp.cantidad_facturada) AS subTotalBruto
        FROM gmcv_compra_factura_prod cfp
        JOIN gmcv_compra_factura cf ON cf.id = cfp.id_compra_factura
        LEFT JOIN prod_compra pc ON cfp.id_prod_compra = pc.id
        LEFT JOIN compra c ON c.id = pc.id_compra
        LEFT JOIN producto p ON p.id = pc.id_prod
        JOIN sys_impuestos imp ON imp.id = p.id_impuesto AND imp.id_status = 4
        JOIN bodega b on b.id = c.id_bodega
        JOIN proveedor on proveedor.id  = c.id_prov
        WHERE c.id_prov = $id_prov AND cf.fecha BETWEEN DATE('$fechaIni') AND DATE('$fachaFin') AND cf.validada = 1
        GROUP BY cf.id
        ORDER BY cf.fecha DESC";

        //Escribismos el query en un archivo de texto
        // file_put_contents('resumenFacturasValidadas.txt', $query);
        $result = db::query($query);
        $facturas = array();
        while ($row = db::fetch_assoc($result)) {
            $facturas[] = $row;
        }
        return $facturas;
    }
    //Esta funcion retorna los totales simples sin considerar descuentos por rechazos o globales de una factura
    public function getTotalesFacturaById($idFactura){
        $query = "SELECT 
            SUM(cfp.costo_unitario_bruto * cfp.cantidad_facturada) AS totalBruto,

            -- Calcular el total del IEPS
            SUM(ROUND(cfp.costo_unitario_bruto * cfp.cantidad_facturada * imp.ieps, 4)) AS totalIEPS,
            SUM(CASE 
                    WHEN imp.iepsxl > 0 THEN imp.iepsxl * p.litros * cfp.cantidad_facturada
                    ELSE 0 
                END) AS totalIEPSxL,

            -- Calcular el IVA después de sumar el IEPS al costo base
            SUM(ROUND((cfp.costo_unitario_bruto * cfp.cantidad_facturada 
                + cfp.costo_unitario_bruto * cfp.cantidad_facturada * imp.ieps
                + CASE 
                    WHEN imp.iepsxl > 0 THEN imp.iepsxl * p.litros * cfp.cantidad_facturada
                    ELSE 0 
                END) * imp.iva, 4)) AS totalIVA,

            -- Calcular el total neto
            SUM(
                cfp.costo_unitario_bruto * cfp.cantidad_facturada 
                + ROUND(cfp.costo_unitario_bruto * cfp.cantidad_facturada * imp.ieps, 4)
                + CASE 
                    WHEN imp.iepsxl > 0 THEN imp.iepsxl * p.litros * cfp.cantidad_facturada
                    ELSE 0 
                END
                + ROUND((cfp.costo_unitario_bruto * cfp.cantidad_facturada 
                    + cfp.costo_unitario_bruto * cfp.cantidad_facturada * imp.ieps
                    + CASE 
                        WHEN imp.iepsxl > 0 THEN imp.iepsxl * p.litros * cfp.cantidad_facturada
                        ELSE 0 
                    END) * imp.iva, 4)
            ) AS totalNeto

        FROM gmcv_compra_factura_prod cfp
        JOIN gmcv_compra_factura cf ON cf.id = cfp.id_compra_factura
        LEFT JOIN prod_compra pc ON cfp.id_prod_compra = pc.id
        LEFT JOIN compra c ON c.id = pc.id_compra
        LEFT JOIN producto p ON p.id = pc.id_prod
        JOIN sys_impuestos imp ON imp.id = p.id_impuesto AND imp.id_status = 4
        WHERE cfp.id_compra_factura = $idFactura
        ORDER BY cf.fecha DESC";
        $result = db::query($query);
        //En caso de que exista un resultado lo devolvemos en un array
        if (db::num_rows($result) > 0) {
            return db::fetch_assoc($result);
        } else {
            return false;
        }
    }

    //Esta funcion retorna los totales de una factura considerando descuentos por rechazos utilizando costoPactado
    public function getTotalesFacturaRechazosById($idFactura) {
        $query = "SELECT 
            -- Total original de la factura (sin considerar rechazos)
            SUM(cfp.costo_unitario_bruto * cfp.cantidad_facturada) AS totalOriginalBruto,
            
            -- Calcular el IEPS primero y luego sumar para calcular el IVA correctamente
            SUM(
                ROUND(
                    (cfp.costo_unitario_bruto * cfp.cantidad_facturada 
                    + cfp.costo_unitario_bruto * cfp.cantidad_facturada * imp.ieps
                    + CASE 
                        WHEN imp.iepsxl > 0 THEN imp.iepsxl * p.litros * cfp.cantidad_facturada
                        ELSE 0 
                    END) * imp.iva, 4
                )
            ) AS totalOriginalIVA,
            
            SUM(ROUND(cfp.costo_unitario_bruto * cfp.cantidad_facturada * imp.ieps, 4)) AS totalOriginalIEPS,
            
            SUM(CASE 
                    WHEN imp.iepsxl > 0 THEN imp.iepsxl * p.litros * cfp.cantidad_facturada
                    ELSE 0 
                END) AS totalOriginalIEPSxL,
            
            -- Calcular el total neto correctamente
            SUM(
                cfp.costo_unitario_bruto * cfp.cantidad_facturada 
                + ROUND(
                    (cfp.costo_unitario_bruto * cfp.cantidad_facturada 
                    + cfp.costo_unitario_bruto * cfp.cantidad_facturada * imp.ieps
                    + CASE 
                        WHEN imp.iepsxl > 0 THEN imp.iepsxl * p.litros * cfp.cantidad_facturada
                        ELSE 0 
                    END) * imp.iva, 4
                )
                + ROUND(cfp.costo_unitario_bruto * cfp.cantidad_facturada * imp.ieps, 4)
                + CASE 
                    WHEN imp.iepsxl > 0 THEN imp.iepsxl * p.litros * cfp.cantidad_facturada
                    ELSE 0 
                END
            ) AS totalOriginalNeto,

            -- Total después de descontar cantidades rechazadas
            SUM(cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada)) AS totalDescuentoBruto,
            
            SUM(
                ROUND(
                    (cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada)
                    + cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada) * imp.ieps
                    + CASE 
                        WHEN imp.iepsxl > 0 THEN imp.iepsxl * p.litros * (cfp.cantidad_facturada - cfp.cantidad_rechazada)
                        ELSE 0 
                    END) * imp.iva, 4
                )
            ) AS totalDescuentoIVA,
            
            SUM(ROUND(cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada) * imp.ieps, 4)) AS totalDescuentoIEPS,
            
            SUM(CASE 
                    WHEN imp.iepsxl > 0 THEN imp.iepsxl * p.litros * (cfp.cantidad_facturada - cfp.cantidad_rechazada)
                    ELSE 0 
                END) AS totalDescuentoIEPSxL,
            
            SUM(
                cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada)
                + ROUND(
                    (cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada)
                    + cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada) * imp.ieps
                    + CASE 
                        WHEN imp.iepsxl > 0 THEN imp.iepsxl * p.litros * (cfp.cantidad_facturada - cfp.cantidad_rechazada)
                        ELSE 0 
                    END) * imp.iva, 4
                )
                + ROUND(cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada) * imp.ieps, 4)
                + CASE 
                    WHEN imp.iepsxl > 0 THEN imp.iepsxl * p.litros * (cfp.cantidad_facturada - cfp.cantidad_rechazada)
                    ELSE 0 
                END
            ) AS totalDescuentoNeto,

            -- Monto total descontado
            SUM(
                cfp.costo_unitario_bruto * cfp.cantidad_rechazada 
                + ROUND(
                    (cfp.costo_unitario_bruto * cfp.cantidad_rechazada
                    + cfp.costo_unitario_bruto * cfp.cantidad_rechazada * imp.ieps
                    + CASE 
                        WHEN imp.iepsxl > 0 THEN imp.iepsxl * p.litros * cfp.cantidad_rechazada
                        ELSE 0 
                    END) * imp.iva, 4
                )
                + ROUND(cfp.costo_unitario_bruto * cfp.cantidad_rechazada * imp.ieps, 4)
                + CASE 
                    WHEN imp.iepsxl > 0 THEN imp.iepsxl * p.litros * cfp.cantidad_rechazada
                    ELSE 0 
                END
            ) AS totalDescontado
        FROM gmcv_compra_factura_prod cfp
        JOIN gmcv_compra_factura cf ON cf.id = cfp.id_compra_factura
        LEFT JOIN prod_compra pc ON cfp.id_prod_compra = pc.id
        LEFT JOIN compra c ON c.id = pc.id_compra
        LEFT JOIN producto p ON p.id = pc.id_prod
        JOIN sys_impuestos imp ON imp.id = p.id_impuesto AND imp.id_status = 4
        WHERE cfp.id_compra_factura = $idFactura
        ORDER BY cf.fecha DESC";
        
        $result = db::query($query);
        //En caso de que exista un resultado lo devolvemos en un array
        if (db::num_rows($result) > 0) {
            return db::fetch_assoc($result);
        } else {
            return false;
        }
    }


    //Obtnemos la bodega de una factura por id de factura
    public static function getBodegaByFactura($id_factura) {
        $query = "SELECT b.nombre AS nombreBodega, b.id
        FROM gmcv_compra_factura cf
        JOIN compra c ON cf.id_compra = c.id
        JOIN bodega b ON c.id_bodega = b.id
        WHERE cf.id = '".db::real_escape_string($id_factura)."'";
        $result = db::query($query);

        return db::fetch_array($result);
    }

    //Obtnemos el status de una factura por id de factura
    public static function getStatusByFactura($id_factura) {
        $query = "SELECT s.nombre AS nombreStatus, s.id
        FROM gmcv_compra_factura cf
        JOIN sys_status s ON cf.id_status = s.id
        WHERE cf.id = '".db::real_escape_string($id_factura)."'";
        $result = db::query($query);

        //Enviamos el array con los datos del status
        return db::fetch_array($result);
    }
    

    //////// CONCIILIACION DE FACTURAS ////////
    public static function getFacturasByidStatus($id_status) {
        $query = "SELECT * FROM gmcv_compra_factura WHERE id_status in ($id_status)";
        $result = db::query($query);

        $facturas = array();
        while ($row = db::fetch_assoc($result)) {
            $facturas[] = $row;
        }
        return $facturas;
    }

    //Creamos un metodo para obtener facturas por las que vamos a conciliar
    public static function getFacturasConciliar($idProveedor, $fechaIni, $fechaFin, $idStatus) {
        $status = $idStatus == 0 ? "AND cf.id_status IN (1,4,23)" : "AND cf.id_status = $idStatus";

        // $query = "SELECT p.corto as proveedor, p.id as idProveedor, c.id_bodega as idBodega, b.nombre as bodega, cf.id AS idFactura, pf.id AS idPagoFactura, cf.*, pf.*,
        //     IFNULL(SUM(pf.monto), 0) AS totalAbonado, cf.subTotalBruto as totalFactura, cf.total_iva as ivaFactura, cf.total_ieps as iepsFactura, 
        //     (cf.totalAPagar - IFNULL(SUM(pf.monto), 0)) AS saldoPendiente,
        //     GROUP_CONCAT(DISTINCT pago.uuid ORDER BY pago.uuid SEPARATOR ', ') AS uuidsAbonos
        // FROM gmcv_compra_factura cf
        // JOIN compra c ON c.id = cf.id_compra
        // JOIN bodega b ON b.id = c.id_bodega
        // JOIN proveedor p ON p.id = c.id_prov
        // LEFT JOIN gmcv_pago_factura pf ON pf.id_compra_factura = cf.id
        // LEFT JOIN gmcv_pago pago ON pago.id = pf.id_pago
        // WHERE p.id = $idProveedor 
        // AND cf.fecha BETWEEN DATE('$fechaIni') AND DATE('$fechaFin') $status
        // GROUP BY cf.id";
        

        $query = "SELECT 
            SUM(cfp.costo_unitario_bruto * cfp.cantidad_facturada) AS costo_sin_impuestos,

            -- Aplicamos las tasas de IEPS primero y luego calculamos el IVA sobre el total resultante
            SUM(ROUND((cfp.costo_unitario_bruto * cfp.cantidad_facturada + cfp.costo_unitario_bruto * cfp.cantidad_facturada * imp.ieps) * imp.iva, 4)) AS ivaFactura,

            SUM(ROUND(cfp.costo_unitario_bruto * cfp.cantidad_facturada * imp.ieps, 4)) AS iepsFactura,
            SUM(CASE 
                    WHEN imp.iepsxl > 0 THEN imp.iepsxl * p.litros * cfp.cantidad_facturada
                    ELSE 0 
                END) AS total_iepsxL,
            SUM(
                cfp.costo_unitario_bruto * cfp.cantidad_facturada 
                + ROUND(cfp.costo_unitario_bruto * cfp.cantidad_facturada * imp.ieps, 4)
                + ROUND((cfp.costo_unitario_bruto * cfp.cantidad_facturada + cfp.costo_unitario_bruto * cfp.cantidad_facturada * imp.ieps) * imp.iva, 4)
                + CASE 
                    WHEN imp.iepsxl > 0 THEN imp.iepsxl * p.litros * cfp.cantidad_facturada
                    ELSE 0 
                END
            ) AS total,

            -- Total después de descontar cantidades rechazadas
            SUM(cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada)) AS totaldescuentoBruto,
            SUM(ROUND((cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada) + cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada) * imp.ieps) * imp.iva, 4)) AS ivaTraslado,
            SUM(ROUND(cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada) * imp.ieps, 4)) AS iepsTraslado,
            SUM(CASE 
                    WHEN imp.iepsxl > 0 THEN imp.iepsxl * p.litros * (cfp.cantidad_facturada - cfp.cantidad_rechazada)
                    ELSE 0 
                END) AS iepsTrasladoxL,
            SUM(
                cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada)
                + ROUND(cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada) * imp.ieps, 4)
                + ROUND((cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada) + cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada) * imp.ieps) * imp.iva, 4)
                + CASE 
                    WHEN imp.iepsxl > 0 THEN imp.iepsxl * p.litros * (cfp.cantidad_facturada - cfp.cantidad_rechazada)
                    ELSE 0 
                END
            ) AS subTotalNeto,

            -- Monto total descontado
            SUM(
                cfp.costo_unitario_bruto * cfp.cantidad_rechazada 
                + ROUND(cfp.costo_unitario_bruto * cfp.cantidad_rechazada * imp.ieps, 4)
                + ROUND((cfp.costo_unitario_bruto * cfp.cantidad_rechazada + cfp.costo_unitario_bruto * cfp.cantidad_rechazada * imp.ieps) * imp.iva, 4)
                + CASE 
                    WHEN imp.iepsxl > 0 THEN imp.iepsxl * p.litros * cfp.cantidad_rechazada
                    ELSE 0 
                END
            ) AS descGlobalImpuestos,    

            -- Sumar los descuentos globales usando un subquery
            COALESCE((
                SELECT SUM(cfd.descuento) 
                FROM gmcv_compra_factura_desc cfd 
                WHERE cfd.id_compra_factura = cf.id
            ), 0) AS descGlobal,

            -- Total después de aplicar los descuentos globales
            (
                SUM(
                    ((cfp.costo_unitario_bruto * cfp.cantidad_facturada) - ((cfp.costo_unitario_bruto * cfp.cantidad_rechazada) + cfp.ajuste_bruto )) +
                    ROUND((cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada) + cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada) * imp.ieps) * imp.iva, 4) +
                    ROUND(cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada) * imp.ieps, 4) +
                    CASE 
                        WHEN imp.iepsxl > 0 THEN imp.iepsxl * p.litros * (cfp.cantidad_facturada - cfp.cantidad_rechazada)
                        ELSE 0 
                    END
                ) - COALESCE((
                    SELECT SUM(cfd.descuento) 
                    FROM gmcv_compra_factura_desc cfd 
                    WHERE cfd.id_compra_factura = cf.id
                ), 0)
            ) AS totalAPagar,

            -- Calculamos el saldo pendiente que tiene la factura en función de los abonos realizados
            (
                SUM(
                    ((cfp.costo_unitario_bruto * cfp.cantidad_facturada) - ((cfp.costo_unitario_bruto * cfp.cantidad_rechazada) + cfp.ajuste_bruto )) +
                    ROUND((cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada) + cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada) * imp.ieps) * imp.iva, 4) +
                    ROUND(cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada) * imp.ieps, 4) +
                    CASE 
                        WHEN imp.iepsxl > 0 THEN imp.iepsxl * p.litros * (cfp.cantidad_facturada - cfp.cantidad_rechazada)
                        ELSE 0 
                    END
                ) - COALESCE((
                    SELECT SUM(cfd.descuento) 
                    FROM gmcv_compra_factura_desc cfd 
                    WHERE cfd.id_compra_factura = cf.id
                ), 0)
            )  - IFNULL(pf.monto, 0) AS saldoPendiente,

            GROUP_CONCAT(DISTINCT pago.uuid ORDER BY pago.uuid SEPARATOR ', ') AS uuidsAbonos,

            -- Campos complementarios L1
            cf.id_compra, c.id_bodega as idBodega, b.nombre AS bodega, cf.fecha, cf.fecha_llegada, cf.uuid,
            
            -- L2
            SUM(cfp.descuento) AS descuento, SUM(cfp.costo_unitario_bruto * cfp.cantidad_rechazada) AS descRechazo, pf.id AS idPagoFactura, cf.*, pf.*,
            proveedor.corto, proveedor.largo as razonSocial, cf.id as idFactura, cf.id_status AS status_factura, proveedor.corto AS proveedor, p.id as idProveedor,

            -- Verificar esta
            SUM(cfp.costo_unitario_bruto * cfp.cantidad_facturada) AS subTotalBruto, IFNULL(pf.monto, 0) AS totalAbonado

        FROM gmcv_compra_factura_prod cfp
        JOIN gmcv_compra_factura cf ON cf.id = cfp.id_compra_factura
        LEFT JOIN prod_compra pc ON cfp.id_prod_compra = pc.id
        LEFT JOIN compra c ON c.id = pc.id_compra
        LEFT JOIN producto p ON p.id = pc.id_prod
        LEFT JOIN gmcv_pago_factura pf ON pf.id_compra_factura = cf.id
        LEFT JOIN gmcv_pago pago ON pago.id = pf.id_pago
        JOIN sys_impuestos imp ON imp.id = p.id_impuesto AND imp.id_status = 4
        JOIN bodega b on b.id = c.id_bodega
        JOIN proveedor on proveedor.id  = c.id_prov
        WHERE c.id_prov = $idProveedor
        AND cf.fecha BETWEEN DATE('$fechaIni') AND DATE('$fechaFin') $status
        AND cf.validada = 1
        GROUP BY cf.id
        ORDER BY cf.fecha ASC";
        //Escribimos el query en un archivo de texto
        file_put_contents('getFacturasConciliar.txt', $query);

        $result = db::query($query);
        $facturas = array();
        while ($row = db::fetch_assoc($result)) {
            // Pintamos la celda de pago en función de la fecha compromiso en forma de semáforo con un código de color hexadecimal
            // Rojo si la fecha compromiso es menor a la fecha actual y naranja si hay 1 semana de diferencia
            $row['colorFCompromiso'] = '';
            $fechaActual = date('Y-m-d');
            if ($row['fecha_compromiso'] < $fechaActual) {
                $row['colorFCompromiso'] = '#FF0000'; // Rojo si la fecha compromiso es menor a la fecha actual
            } elseif ($row['fecha_compromiso'] >= $fechaActual && $row['fecha_compromiso'] <= date('Y-m-d', strtotime($fechaActual . ' +7 days'))) {
                $row['colorFCompromiso'] = '#FFA500'; // Naranja si la fecha compromiso está dentro de la próxima semana
            }
            // Pintamos la celda de saldo pendiente en función de si el saldo pendiente es mayor a 0 (amarillo) y si es 0 (verde)
            // No pintar nada si saldoPendiente es igual a totalAPagar
            $row['colorSaldo'] = '';
            if ($row['saldoPendiente'] > 0 && $row['saldoPendiente'] != $row['totalAPagar']) {
                $row['colorSaldo'] = '#FFFF00'; // Amarillo si el saldo pendiente es mayor a 0 y no igual a totalAPagar
            } elseif ($row['saldoPendiente'] == 0) {
                $row['colorSaldo'] = '#00FF00'; // Verde si el saldo pendiente es 0
                //Eliminamos el color de la fecha compromiso si el saldo es 0
                $row['colorFCompromiso'] = '';
            }

            $facturas[] = $row;
        }
        return $facturas;
    }

    //Creamos un metodo para obtener los proveedores que tienen facturas por conciliar
    public static function getProveedoresConciliar() {
        $query = "SELECT p.* from proveedor p
        JOIN compra c ON c.id_prov = p.id 
        JOIN gmcv_compra_factura cf on cf.id_compra = c.id AND p.id = c.id_prov
        GROUP by p.id
        ORDER BY p.corto ASC";
        $result = db::query($query);

        $proveedores = array();
        while ($row = db::fetch_assoc($result)) {
            $proveedores[] = $row;
        }
        return $proveedores;
    }

    public function getSaldoFactura($idFactura) {
        // $query = "SELECT p.corto as proveedor, p.id as idProveedor, b.nombre as bodega, cf.id AS idFactura, 
        //     IFNULL(SUM(pf.monto), 0) AS totalAbonado, 
        //     (cf.totalAPagar - IFNULL(SUM(pf.monto), 0)) AS saldoPendiente,
        //     GROUP_CONCAT(DISTINCT pago.uuid ORDER BY pago.uuid SEPARATOR ',') AS uuidsAbonos,
        //     GROUP_CONCAT(DISTINCT pago.id ORDER BY pago.id SEPARATOR ',') AS idsAbonos
        // FROM gmcv_compra_factura cf
        // JOIN compra c ON c.id = cf.id_compra
        // JOIN bodega b ON b.id = c.id_bodega
        // JOIN proveedor p ON p.id = c.id_prov
        // LEFT JOIN gmcv_pago_factura pf ON pf.id_compra_factura = cf.id
        // LEFT JOIN gmcv_pago pago ON pago.id = pf.id_pago
        // WHERE cf.id = $idFactura
        // GROUP BY cf.id";

        $query = "SELECT 
            -- Total después de aplicar los descuentos globales
            (
                SUM(
                    (cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada) - cfp.ajuste_bruto)
                    + ROUND(
                        (cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada)
                        + cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada) * imp.ieps
                        + CASE 
                            WHEN imp.iepsxl > 0 THEN imp.iepsxl * p.litros * (cfp.cantidad_facturada - cfp.cantidad_rechazada)
                            ELSE 0 
                        END
                        ) * imp.iva, 4)
                    + ROUND(cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada) * imp.ieps, 4)
                    + CASE 
                        WHEN imp.iepsxl > 0 THEN imp.iepsxl * p.litros * (cfp.cantidad_facturada - cfp.cantidad_rechazada)
                        ELSE 0 
                    END
                ) - COALESCE((
                    SELECT SUM(cfd.descuento) 
                    FROM gmcv_compra_factura_desc cfd 
                    WHERE cfd.id_compra_factura = cf.id
                ), 0)
            ) AS totalAPagar,

            -- Calculamos el saldo pendiente que tiene la factura en función de los abonos realizados
            (
                SUM(
                    (cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada) - cfp.ajuste_bruto)
                    + ROUND(
                        (cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada)
                        + cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada) * imp.ieps
                        + CASE 
                            WHEN imp.iepsxl > 0 THEN imp.iepsxl * p.litros * (cfp.cantidad_facturada - cfp.cantidad_rechazada)
                            ELSE 0 
                        END
                        ) * imp.iva, 4)
                    + ROUND(cfp.costo_unitario_bruto * (cfp.cantidad_facturada - cfp.cantidad_rechazada) * imp.ieps, 4)
                    + CASE 
                        WHEN imp.iepsxl > 0 THEN imp.iepsxl * p.litros * (cfp.cantidad_facturada - cfp.cantidad_rechazada)
                        ELSE 0 
                    END
                ) - COALESCE((
                    SELECT SUM(cfd.descuento) 
                    FROM gmcv_compra_factura_desc cfd 
                    WHERE cfd.id_compra_factura = cf.id
                ), 0)
            )  - IFNULL(SUM(pf.monto), 0) AS saldoPendiente,

            GROUP_CONCAT(DISTINCT pago.uuid ORDER BY pago.uuid SEPARATOR ',') AS uuidsAbonos,
            GROUP_CONCAT(DISTINCT pago.id ORDER BY pago.id SEPARATOR ',') AS idsAbonos,

            -- Campos complementarios L1
            cf.id_compra, c.id_bodega as idBodega, b.nombre AS bodega, cf.fecha, cf.fecha_llegada, cf.uuid,

            -- L2
            SUM(cfp.descuento) AS descuento, 
            SUM(cfp.costo_unitario_bruto * cfp.cantidad_rechazada) AS descRechazo, 
            pf.id AS idPagoFactura,

            proveedor.corto, proveedor.largo as razonSocial, cf.id as idFactura, cf.id_status AS status_factura, proveedor.corto AS proveedor, p.id as idProveedor,

            -- Verificar esta
            SUM(cfp.costo_unitario_bruto * cfp.cantidad_facturada) AS subTotalBruto, 
            IFNULL(SUM(pf.monto), 0) AS totalAbonado

        FROM gmcv_compra_factura_prod cfp
        JOIN gmcv_compra_factura cf ON cf.id = cfp.id_compra_factura
        LEFT JOIN prod_compra pc ON cfp.id_prod_compra = pc.id
        LEFT JOIN compra c ON c.id = pc.id_compra
        LEFT JOIN producto p ON p.id = pc.id_prod
        LEFT JOIN gmcv_pago_factura pf ON pf.id_compra_factura = cf.id
        LEFT JOIN gmcv_pago pago ON pago.id = pf.id_pago
        JOIN sys_impuestos imp ON imp.id = p.id_impuesto AND imp.id_status = 4
        JOIN bodega b on b.id = c.id_bodega
        JOIN proveedor on proveedor.id = c.id_prov
        WHERE cf.id = $idFactura
        GROUP BY cf.id
        ORDER BY cf.fecha DESC";

        $result = db::query($query);
        return db::fetch_assoc($result);
    }

    
}



?>