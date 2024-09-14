<?php
$modelo = ['GmcvDescuentoProducto'];
include 'header.php';

class GmcvDescuentoProductoController {
    public function create($data) {
        $descuentoProducto = new GmcvDescuentoProducto();
        $descuentoProducto->id_descuento = $data['id_descuento'];
        $descuentoProducto->id_prov = $data['id_prov'];
        $descuentoProducto->id_bodega = $data['id_bodega'];
        $descuentoProducto->id_prod = $data['id_prod'];
        $descuentoProducto->ini = $data['ini'];
        $descuentoProducto->id_status = $data['id_status'];
        $descuentoProducto->descuento = $data['descuento'];
        $descuentoProducto->create();

        return db::insert_id();
    }

    public function read($id_descuento, $id_prod) {
        return GmcvDescuentoProducto::getById($id_descuento, $id_prod);
    }

    public function update($id_descuento, $id_prod, $data) {
        $descuentoProducto = GmcvDescuentoProducto::getById($id_descuento, $id_prod);
        if (!$descuentoProducto) {
            return false;
        }
        $descuentoProducto->id_prov = $data['id_prov'];
        $descuentoProducto->id_bodega = $data['id_bodega'];
        $descuentoProducto->id_prod = $data['id_prod'];
        $descuentoProducto->ini = $data['ini'];
        $descuentoProducto->id_status = $data['id_status'];
        $descuentoProducto->descuento = $data['descuento'];
        $descuentoProducto->update();

        return true;
    }

    public function delete($id_descuento, $id_prod) {
        $descuentoProducto = GmcvDescuentoProducto::getById($id_descuento, $id_prod);
        if (!$descuentoProducto) {
            return false;
        }
        $descuentoProducto->delete();

        return true;
    }
}
?>
