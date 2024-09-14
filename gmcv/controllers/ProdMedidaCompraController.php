<?php
$modelo = 'ProdMedidaCompra';
include 'header.php';

class ProdMedidaCompraController {
    public function create($data) {
        $prodMedidaCompra = new ProdMedidaCompra();
        $prodMedidaCompra->id = $data['id'];
        $prodMedidaCompra->id_prod = $data['id_prod'];
        $prodMedidaCompra->id_medida = $data['id_medida'];
        $prodMedidaCompra->id_status = $data['id_status'];
        $prodMedidaCompra->id_minimo = $data['id_minimo'];
        $prodMedidaCompra->cantidad = $data['cantidad'];
        $prodMedidaCompra->create();

        return db::insert_id();
    }

    public function read($id) {
        return ProdMedidaCompra::getById($id);
    }

    public function update($id, $data) {
        $prodMedidaCompra = ProdMedidaCompra::getById($id);
        if (!$prodMedidaCompra) {
            return false;
        }
        $prodMedidaCompra->id_prod = $data['id_prod'];
        $prodMedidaCompra->id_medida = $data['id_medida'];
        $prodMedidaCompra->id_status = $data['id_status'];
        $prodMedidaCompra->id_minimo = $data['id_minimo'];
        $prodMedidaCompra->cantidad = $data['cantidad'];
        $prodMedidaCompra->update();

        return true;
    }

    public function delete($id) {
        $prodMedidaCompra = ProdMedidaCompra::getById($id);
        if (!$prodMedidaCompra) {
            return false;
        }
        $prodMedidaCompra->delete();

        return true;
    }
}
?>
