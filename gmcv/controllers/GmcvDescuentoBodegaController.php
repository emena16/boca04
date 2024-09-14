<?php
//Array de modelos a incluir según sea necesario
$modelos = ['GmcvDescuentoBodega']; 
include "header.php";

class GmcvDescuentoBodegaController {
    public function create($data) {
        $descuentoBodega = new GmcvDescuentoBodega();
        $descuentoBodega->id_descuento = $data['id_descuento'];
        $descuentoBodega->id_bodega = $data['id_bodega'];
        $descuentoBodega->create();

        return db::insert_id();
    }

    public function read($id_descuento, $id_bodega) {
        return GmcvDescuentoBodega::getById($id_descuento, $id_bodega);
    }

    public function delete($id_descuento, $id_bodega) {
        $descuentoBodega = GmcvDescuentoBodega::getById($id_descuento, $id_bodega);
        if (!$descuentoBodega) {
            return false;
        }
        $descuentoBodega->delete();

        return true;
    }
}
?>
