<?php
// Incluimos el header
include_once 'header.php';

class CompraFacturaDesc extends db {
    private $table_name = "gmcv_compra_factura_desc";

    public $id;
    public $id_compra_factura;
    public $descuento;
    public $nota;

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " (
            id_compra_factura,
            descuento,
            nota
        ) VALUES (
            '" . db::real_escape_string($this->id_compra_factura) . "',
            '" . db::real_escape_string($this->descuento) . "',
            '" . db::real_escape_string($this->nota) . "'
        )";

        $result = db::query($query);

        if ($result) {
            return true;
        } else {
            return false;
        }
    }

}


?>