<?php
// Incluimos el header
include_once 'header.php';

class GmcvPagoFactura extends db {
    private $table_name = "gmcv_pago_factura";

    public $id;
    public $id_pago;
    public $id_compra_factura;
    public $monto;

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " (
            id_pago,
            id_compra_factura,
            monto
            ) VALUES (
            ".$this->id_pago.",
            ".$this->id_compra_factura.",
            ".$this->monto."
            )";
        //Escribimos el query en un archivo para debuggear
        file_put_contents('query.txt', $query);

        $result = db::query($query);

        if ($result) {
            return db::insert_id();
        } else {
            return false;
        }

    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = " . $this->id;
        $result = db::query($query);

        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    public static function getById($id) {
        $query = "SELECT * FROM gmcv_pago_factura WHERE id = " . $id;
        $result = db::query($query);

        if ($result) {
            return db::fetch_array($result);
        } else {
            return false;
        }
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " SET
            id_pago = ".$this->id_pago.",
            id_compra_factura = ".$this->id_compra_factura.",
            monto = ".$this->monto."
            WHERE id = " . $this->id;
        $result = db::query($query);

        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    public static function getByidPagoidFacturaObject($id_pago, $id_factura) {
        $query = "SELECT * FROM gmcv_pago_factura WHERE id_pago = " . $id_pago . " AND id_compra_factura = " . $id_factura;
        $result = db::query($query);
        $pagoFactura = new GmcvPagoFactura();
        $row = db::fetch_array($result);
        if ($row) {
            $pagoFactura->id = $row['id'];
            $pagoFactura->id_pago = $row['id_pago'];
            $pagoFactura->id_compra_factura = $row['id_compra_factura'];
            $pagoFactura->monto = $row['monto'];
            return $pagoFactura;
        } else {
            return false;
        }
    }




}

?>