<?php
// Incluimos el header
include_once 'header.php';


class GmcvDescuentoBodega extends db {
    private $table_name = "gmcv_descuento_bodega";

    public $id_descuento;
    public $id_bodega;

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " (
            id_descuento,
            id_bodega
        ) VALUES (
            ".$this->id_descuento.",
            ".$this->id_bodega. "
        )";
        $result = db::query($query);

        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_descuento = '" . db::real_escape_string($this->id_descuento) . "'";
        $result = db::query($query);

        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    //Creamos un metodo para eliminar todo un descuento
    public function deleteDescuento($id_descuento) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_descuento = '" . db::real_escape_string($id_descuento) . "'";
        $result = db::query($query);

        if ($result) {
            return true;
        } else {
            return false;
        }
    }


    //Creamos un metodo para obtener las bodegas de un descuento y las que actualmente esta asignado
    public static function getBodegasByDescuento($id_descuento, $id_prov) {
        $query = "SELECT id_bodega, nombre
        FROM gmcv_descuento_bodega dbod
        INNER JOIN (SELECT bo.id_bodega as id, b.nombre
            from producto p
            JOIN prod_medida pm on p.id = pm.id_prod && pm.id_status = 4
            JOIN prod_oficina po on pm.id = po.id_prod && po.id_status = 4
            JOIN bod_oficina bo on po.id_oficina = bo.id_oficina
            JOIN bodega b on bo.id_bodega = b.id && b.id_status = 4
            where p.id_tipo_venta = 1 && p.id_prov in ($id_prov)
            group by bo.id_bodega order by b.nombre) as bod_prov ON bod_prov.id = dbod.id_bodega
        WHERE dbod.id_descuento = $id_descuento";
        $result = db::query($query);

        $bodegas = array();
        while ($row = db::fetch_assoc($result)) {
            $bodegas[] = $row;
        }
        return $bodegas;
    }



}



?>