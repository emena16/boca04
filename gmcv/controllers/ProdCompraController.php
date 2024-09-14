<?php

//Array de modelos a incluir según sea necesario
$modelos = ['ProdCompra']; 
include "header.php";

class ProdCompraController {
    
    public function create($data) {
        $prodCompra = new ProdCompra();
        $prodCompra->id_compra = $data['id_compra'];
        $prodCompra->id_prod = $data['id_prod'];
        $prodCompra->sugerido = $data['sugerido'];
        $prodCompra->cantidad = $data['cantidad'];
        $prodCompra->id_status = $data['id_status'];
        $prodCompra->cant_solicitada = $data['cant_solicitada'];
        $prodCompra->cant_no_recibida = $data['cant_no_recibida'];
        $prodCompra->cant_ingresada = $data['cant_ingresada'];
        $prodCompra->costo_unitario = $data['costo_unitario'];
        $prodCompra->prod_agregado = $data['prod_agregado'];
        $prodCompra->escaner = $data['escaner'];
        $prodCompra->create();

        return db::insert_id();
    }

    public function read($id) {
        return ProdCompra::getById($id);
    }

    public function update($id, $data) {
        $prodCompra = ProdCompra::getById($id);
        if (!$prodCompra) {
            return false;
        }
        $prodCompra->id_compra = $data['id_compra'];
        $prodCompra->id_prod = $data['id_prod'];
        $prodCompra->sugerido = $data['sugerido'];
        $prodCompra->cantidad = $data['cantidad'];
        $prodCompra->id_status = $data['id_status'];
        $prodCompra->cant_solicitada = $data['cant_solicitada'];
        $prodCompra->cant_no_recibida = $data['cant_no_recibida'];
        $prodCompra->cant_ingresada = $data['cant_ingresada'];
        $prodCompra->costo_unitario = $data['costo_unitario'];
        $prodCompra->prod_agregado = $data['prod_agregado'];
        $prodCompra->escaner = $data['escaner'];
        $prodCompra->update();

        return true;
    }

    public function delete($id) {
        $prodCompra = ProdCompra::getById($id);
        if (!$prodCompra) {
            return false;
        }
        $prodCompra->delete();

        return true;
    }
}
?>
