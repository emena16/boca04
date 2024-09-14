<?php
// Incluimos el header
include_once 'header.php';

class ProdCompra extends db {
    private $table_name = "prod_compra";

    public $id;
    public $id_compra;
    public $id_prod;
    public $sugerido;
    public $cantidad;
    public $id_status;
    public $cant_solicitada;
    public $cant_no_recibida;
    public $cant_ingresada;
    public $costo_unitario;
    public $prod_agregado;
    public $escaner;

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " (
            id_compra,
            id_prod,
            sugerido,
            cantidad,
            id_status,
            cant_solicitada,
            cant_no_recibida,
            cant_ingresada,
            costo_unitario,
            prod_agregado,
            escaner
        ) VALUES (
            '" . db::real_escape_string($this->id_compra) . "',
            '" . db::real_escape_string($this->id_prod) . "',
            NULL,
            '" . db::real_escape_string($this->cantidad) . "',
            '" . db::real_escape_string($this->id_status) . "',
            '" . db::real_escape_string($this->cant_solicitada) . "',
            '" . db::real_escape_string($this->cant_no_recibida) . "',
            '" . db::real_escape_string($this->cant_ingresada) . "',
            '" . db::real_escape_string($this->costo_unitario) . "',
            '" . db::real_escape_string($this->prod_agregado) . "',
            '" . db::real_escape_string($this->escaner) . "'
        )";
        // //Creamos un ficher para guardar el query en caso de no existir
        // $file = fopen('query.txt', 'w');
        // //Escribimos el query en un fichero
        // file_put_contents('query.txt', $query);
        // //Cerramos el fichero
        // fclose($file);

        $result = db::query($query);

        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    public static function getById($id) {
        $query = "SELECT * FROM prod_compra WHERE id = '" . db::real_escape_string($id) . "'";
        $result = db::query($query);

        if ($row = db::fetch_assoc($result)) {
            $prodCompra = new ProdCompra();
            $prodCompra->id = $row['id'];
            $prodCompra->id_compra = $row['id_compra'];
            $prodCompra->id_prod = $row['id_prod'];
            $prodCompra->sugerido = $row['sugerido'];
            $prodCompra->cantidad = $row['cantidad'];
            $prodCompra->id_status = $row['id_status'];
            $prodCompra->cant_solicitada = $row['cant_solicitada'];
            $prodCompra->cant_no_recibida = $row['cant_no_recibida'];
            $prodCompra->cant_ingresada = $row['cant_ingresada'];
            $prodCompra->costo_unitario = $row['costo_unitario'];
            $prodCompra->prod_agregado = $row['prod_agregado'];
            $prodCompra->escaner = $row['escaner'];
            //Escribimos en un fichero el query
            // file_put_contents('prodCompra_getById.txt', $query);
            return $prodCompra;
        }

        return null;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " SET 
            id_compra =" . $this->id_compra . ",
            id_prod =" . $this->id_prod. ",
            cantidad =" . $this->cantidad . ",
            id_status =" . $this->id_status . ",
            cant_solicitada =" . $this->cant_solicitada . ",
            cant_no_recibida =" . $this->cant_no_recibida . ",
            cant_ingresada =" . $this->cant_ingresada . ",
            costo_unitario =" . $this->costo_unitario. ",
            prod_agregado =" . $this->prod_agregado . ",
            escaner =" .$this->escaner . "
            WHERE id =" . $this->id. "";
        //Escribimos el query en el fichero
        // file_put_contents('prodCompra_update.txt', $query);
        

        $result = db::query($query);

        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = '" . db::real_escape_string($this->id) . "'";
        $result = db::query($query);

        if ($result) {
            return true;
        } else {
            return false;
        }
    }
}

?>
