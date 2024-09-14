<?php
// Incluimos el header
include_once 'header.php';
/*
CREATE TABLE `gmcv_compra_factura_desc` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_compra_factura` int(10) UNSIGNED NOT NULL,
  `descuento` decimal(12,4) UNSIGNED NOT NULL DEFAULT '0.0000' COMMENT 'Valor en monto bruto',
  `nota` text COLLATE utf8_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Descuentos Globales para gmcv_compra_factura';

*/


class GmcvCompraFactDescuento extends db {
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
            ".$this->id_compra_factura.",
            ".$this->descuento.",
            '" . db::real_escape_string($this->nota) . "'
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
            id_compra_factura = ".$this->id_compra_factura.",
            descuento = ".$this->descuento.",
            nota = '" . db::real_escape_string($this->nota) . "'
            WHERE id = ".$this->id;
        $result = db::query($query);

        if ($result) {
            return true;
        } else {
            return false;
        }
    }
}

?>