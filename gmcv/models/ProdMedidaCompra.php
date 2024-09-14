<?php

// Incluimos el header
include_once 'header.php';

class ProdMedidaCompra extends db {
    private $table_name = "prod_medida_compra";

    public $id;
    public $id_prod;
    public $id_medida;
    public $id_status;
    public $id_minimo;
    public $cantidad;

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " (
            id,
            id_prod,
            id_medida,
            id_status,
            id_minimo,
            cantidad
        ) VALUES (
            '" . db::real_escape_string($this->id) . "',
            '" . db::real_escape_string($this->id_prod) . "',
            '" . db::real_escape_string($this->id_medida) . "',
            '" . db::real_escape_string($this->id_status) . "',
            '" . db::real_escape_string($this->id_minimo) . "',
            '" . db::real_escape_string($this->cantidad) . "'
        )";

        $result = db::query($query);

        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " SET 
            id_prod = '" . db::real_escape_string($this->id_prod) . "',
            id_medida = '" . db::real_escape_string($this->id_medida) . "',
            id_status = '" . db::real_escape_string($this->id_status) . "',
            id_minimo = '" . db::real_escape_string($this->id_minimo) . "',
            cantidad = '" . db::real_escape_string($this->cantidad) . "'
            WHERE id = '" . db::real_escape_string($this->id) . "'";
        
        return db::query($query);
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = '" . db::real_escape_string($this->id) . "'";
        return db::query($query);
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = '" . db::real_escape_string($id) . "' LIMIT 1";
        $result = db::query($query);

        if ($result && db::num_rows($result) > 0) {
            $row = db::fetch_assoc($result);
            $prodMedidaCompra = new self();
            $prodMedidaCompra->id = $row['id'];
            $prodMedidaCompra->id_prod = $row['id_prod'];
            $prodMedidaCompra->id_medida = $row['id_medida'];
            $prodMedidaCompra->id_status = $row['id_status'];
            $prodMedidaCompra->id_minimo = $row['id_minimo'];
            $prodMedidaCompra->cantidad = $row['cantidad'];
            return $prodMedidaCompra;
        } else {
            return null;
        }
    }


    public static function getByIdProd($id_prod) {
        $query = "SELECT * FROM prod_medida_compra WHERE id_prod = '" . db::real_escape_string($id_prod) . "'";
        $result = db::query($query);

        $prodMedidaCompra = array();
        while ($row = db::fetch_assoc($result)) {
            $prodMedidaCompra[] = $row;
        }
        return $prodMedidaCompra;
    }


    public function getByIdProdObeject($id_prod) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id_prod = '" . db::real_escape_string($id_prod) . "'";
        $result = db::query($query);

        $row = db::fetch_assoc($result);
        if ($row) {
            $prodMedidaCompra = new self();
            $prodMedidaCompra->id = $row['id'];
            $prodMedidaCompra->id_prod = $row['id_prod'];
            $prodMedidaCompra->id_medida = $row['id_medida'];
            $prodMedidaCompra->id_status = $row['id_status'];
            $prodMedidaCompra->id_minimo = $row['id_minimo'];
            $prodMedidaCompra->cantidad = $row['cantidad'];
        }else{
            $prodMedidaCompra = null;
        }
        return $prodMedidaCompra;
    }
}

?>
