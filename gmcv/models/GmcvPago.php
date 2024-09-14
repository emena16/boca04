<?php
// Incluimos el header
include_once 'header.php';


// CREATE TABLE `gmcv_pago` (
//     `id` int(10) UNSIGNED NOT NULL,
//     `id_proveedor` mediumint(6) NOT NULL,
//     `uuid` varchar(36) COLLATE utf8_unicode_ci NOT NULL,
//     `fecha` date NOT NULL,
//     `monto` decimal(12,4) NOT NULL DEFAULT '0.0000',
//     `id_status` tinyint(4) NOT NULL DEFAULT '0',
//     `tipo` tinyint(3) UNSIGNED NOT NULL DEFAULT '0'
//   ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


class GmcvPago extends db {
    private $table_name = "gmcv_pago";

    public $id;
    public $id_proveedor;
    public $uuid;
    public $fecha;
    public $monto;
    public $id_status;
    public $tipo;

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " (
            id_proveedor,
            uuid,
            fecha,
            monto,
            id_status,
            tipo
        ) VALUES (
            ".$this->id_proveedor.",
            '" . db::real_escape_string($this->uuid) . "',
            '" . db::real_escape_string($this->fecha) . "',
            ".$this->monto.",
            ".$this->id_status.",
            ".$this->tipo."
        )";
        $result = db::query($query);

        if ($result) {
            return db::insert_id();
        } else {
            return false;
        }
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " SET
            id_proveedor = ".$this->id_proveedor.",
            uuid = '" . db::real_escape_string($this->uuid) . "',
            fecha = '" . db::real_escape_string($this->fecha) . "',
            monto = ".$this->monto.",
            id_status = ".$this->id_status.",
            tipo = ".$this->tipo."
            WHERE id = " . $this->id;
        $result = db::query($query);

        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    public static function getById($id) {
        $query = "SELECT * FROM gmcv_pago WHERE id = " . $id;
        $result = db::query($query);

        if ($result) {
            return db::fetch_array($result);
        } else {
            return false;
        }
    }

    public static function getByIdObject($id) {
        $pago = GmcvPago::getById($id);
        if ($pago) {
            $pagoObj = new GmcvPago();
            $pagoObj->id = $pago['id'];
            $pagoObj->id_proveedor = $pago['id_proveedor'];
            $pagoObj->uuid = $pago['uuid'];
            $pagoObj->fecha = $pago['fecha'];
            $pagoObj->monto = $pago['monto'];
            $pagoObj->id_status = $pago['id_status'];
            $pagoObj->tipo = $pago['tipo'];
            return $pagoObj;
        } else {
            return false;
        }
    }

    public static function getAll() {
        $query = "SELECT * FROM gmcv_pago";
        $result = db::query($query);

        if ($result) {
            return db::fetch_all($result);
        } else {
            return false;
        }
    }

    public static function getByTipo($tipo) {
        $query = "SELECT * FROM gmcv_pago WHERE tipo = " . $tipo;
        $result = db::query($query);

        if ($result) {
            return db::fetch_all($result);
        } else {
            return false;
        }
    }

    public static function getNotasCR() {
        $query = "SELECT * FROM gmcv_pago WHERE tipo = 1";
        $result = db::query($query);

        if ($result) {
            return db::fetch_all($result);
        } else {
            return false;
        }
    }

    public static function getNotasDB() {
        $query = "SELECT * FROM gmcv_pago WHERE tipo = 2";
        $result = db::query($query);

        if ($result) {
            return db::fetch_all($result);
        } else {
            return false;
        }
    }

    public static function getPagosByProveedor($id_proveedor) {
        $query = "SELECT * FROM gmcv_pago WHERE id_proveedor = " . $id_proveedor;
        $result = db::query($query);

        if ($result) {
            return db::fetch_all($result);
        } else {
            return false;
        }
    }

    public function getDocumentosConSaldoByProveedor($id_proveedor) {
        $query = "SELECT p.id AS id_pago, p.id_proveedor, p.uuid AS pago_uuid, p.fecha, p.monto AS monto_pago,
        IFNULL(SUM(pf.monto), 0) AS monto_aplicado, (p.monto - IFNULL(SUM(pf.monto), 0)) AS saldo_disponible,
        GROUP_CONCAT(cf.uuid ORDER BY cf.fecha SEPARATOR ', ') AS facturas,
        GROUP_CONCAT(cf.id ORDER BY cf.fecha SEPARATOR ', ') AS idFacturas,
        p.tipo AS tipo_documento, pr.corto AS proveedor
        FROM gmcv_pago p
        JOIN proveedor pr ON p.id_proveedor = pr.id
        LEFT JOIN gmcv_pago_factura pf ON p.id = pf.id_pago
        LEFT JOIN gmcv_compra_factura cf ON pf.id_compra_factura = cf.id
        WHERE p.id_proveedor = $id_proveedor
        GROUP BY p.id
        HAVING saldo_disponible > 0";
        $result = db::query($query);
        
        if ($result) {
            return db::fetch_all($result);
        } else {
            return false;
        }
    }

    public static function getSaldoDispPago($id_pago){
        $query = "SELECT p.monto AS monto_pago, p.uuid, (p.monto - IFNULL(SUM(pf.monto), 0)) AS saldo_disponible
        FROM gmcv_pago p
        LEFT JOIN gmcv_pago_factura pf ON p.id = pf.id_pago
        WHERE p.id = $id_pago
        GROUP BY p.id;
";
        $result = db::query($query);
        
        if ($result) {
            return db::fetch_array($result);
        } else {
            return false;
        }
    }


    //Metodo para buscar por uuid
    public static function getByUuid($uuid) {
        $query = "SELECT * FROM gmcv_pago WHERE uuid = '" . db::real_escape_string($uuid) . "'";
        $result = db::query($query);

        if ($result) {
            return db::fetch_array($result);
        } else {
            return false;
        }
    }


    public static function getDocumentosByProveedorFechas($id_proveedor, $fecha_inicio, $fecha_fin, $tipo_documento) {
        $query = "SELECT p.id AS id_pago, p.id_proveedor, p.uuid AS pago_uuid, p.fecha, p.monto AS monto_pago,
        IFNULL(SUM(pf.monto), 0) AS monto_aplicado, (p.monto - IFNULL(SUM(pf.monto), 0)) AS saldo_disponible,
        GROUP_CONCAT(cf.uuid ORDER BY cf.fecha SEPARATOR ', ') AS facturas,
        GROUP_CONCAT(cf.id ORDER BY cf.fecha SEPARATOR ', ') AS idFacturas,
        p.tipo AS tipo_documento, pr.corto AS proveedor
        FROM gmcv_pago p
        JOIN proveedor pr ON p.id_proveedor = pr.id
        LEFT JOIN gmcv_pago_factura pf ON p.id = pf.id_pago
        LEFT JOIN gmcv_compra_factura cf ON pf.id_compra_factura = cf.id
        WHERE p.id_proveedor = $id_proveedor AND p.fecha BETWEEN '$fecha_inicio' AND '$fecha_fin' AND p.tipo = $tipo_documento
        GROUP BY p.id";
        $result = db::query($query);
        
        if ($result) {
            return db::fetch_all($result);
        } else {
            return false;
        }
    }








}