<?php
// Incluimos el header
include_once 'header.php';

class GmcvDescuento extends db {
    private $table_name = "gmcv_descuento";

    public $id;
    public $id_prov;
    public $nombre;
    public $id_status;
    public $posteriorCP;

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " (
            id_prov,
            nombre,
            id_status,
            posteriorCP
        ) VALUES (
            " . $this->id_prov . ",
            '" . db::real_escape_string($this->nombre) . "',
            " . $this->id_status . ",
            " . $this->posteriorCP . "
        )";
        file_put_contents('query_create_descuento.txt', $query);
        $result = db::query($query);

        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " SET
            id_prov = " . db::real_escape_string($this->id_prov) . ",
            nombre = '" . db::real_escape_string($this->nombre) . "',
            id_status = " . db::real_escape_string($this->id_status) . ",
            posteriorCP = " . db::real_escape_string($this->posteriorCP) . "
            WHERE id = " . db::real_escape_string($this->id);
        $result = db::query($query);
        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    public static function getById($id) {
        $query = "SELECT * FROM gmcv_descuento WHERE id = '" . db::real_escape_string($id) . "'";
        $result = db::query($query);

        return db::fetch_assoc($result);
    }

    //Creamos un metodo para buscar parecidos por nombre de descuento
    public static function getDescuentosByNombre($nombre, $id_prov) {
        $query = "SELECT * FROM gmcv_descuento WHERE id_prov = " . db::real_escape_string($id_prov) . " AND nombre = '" . db::real_escape_string($nombre) . "'";
        $result = db::query($query);
        //Si hay resultados mandamos un true
        if (db::num_rows($result) > 0) {
            return true;
        } else {
            return false;
        }
    }

    public static function getByIdObject($id) {
        //Instnaicamos el objeto
        $descuento = new GmcvDescuento();
        //Obtenemos el descuento
        $descuentoInfo = $descuento->getById($id);
        //Retornamos el objeto
        $descuento->id = $descuentoInfo['id'];
        $descuento->id_prov = $descuentoInfo['id_prov'];
        $descuento->nombre = $descuentoInfo['nombre'];
        $descuento->id_status = $descuentoInfo['id_status'];
        $descuento->posteriorCP = $descuentoInfo['posteriorCP'];

        return $descuento;
    
    }


    public static function getDescuentosByFacturaId($id_factura) {
        $query = "SELECT gd.* FROM gmcv_descuento gd
                  INNER JOIN gmcv_compra_factura_desc gfd ON gd.id = gfd.id
                  WHERE gfd.id_compra_factura = '" . db::real_escape_string($id_factura) . "'";
        $result = db::query($query);

        $descuentos = array();
        while ($row = db::fetch_assoc($result)) {
            $descuentos[] = $row;
        }
        return $descuentos;
    }


    public static function getDescuentosProducto($id_prod, $id_prov, $id_bodega) {
        $query = "SELECT d.id, d.id_prov, d.nombre, d.id_status, d.posteriorCP, db.id_descuento, db.id_bodega, dp.id_descuento, dp.id_prov, dp.id_bodega, dp.id_prod, dp.ini, dp.id_status, dp.descuento
        FROM gmcv_descuento d
        JOIN gmcv_descuento_bodega db ON db.id_descuento = d.id AND d.id_status = 4
        JOIN gmcv_descuento_producto dp ON dp.id_descuento = d.id
        JOIN (
                SELECT id_descuento, id_prov, id_bodega, id_prod, MAX(ini) AS max_ini
                FROM gmcv_descuento_producto
                WHERE id_bodega = $id_bodega AND id_prov = $id_prov AND id_prod = $id_prod
                GROUP BY id_descuento, id_prov, id_bodega, id_prod
            ) dp_max ON dp.id_descuento = dp_max.id_descuento AND dp.id_prov = dp_max.id_prov AND dp.id_bodega = dp_max.id_bodega AND dp.id_prod = dp_max.id_prod AND dp.ini = dp_max.max_ini
        WHERE db.id_bodega = $id_bodega AND d.id_prov = $id_prov AND dp.id_prod = $id_prod ORDER BY d.posteriorCP ASC";
        
        $result = db::query($query);

        $descuentos = array();
        while ($row = db::fetch_assoc($result)) {
            $descuentos[] = $row;
        }
        return $descuentos;
    }

    //Metodo para obener los descuentos inactivos
    public static function getDescuentosInactivosByProveedor($id_prov) {
        $query = "SELECT * FROM gmcv_descuento WHERE id_status = 3 and id_prov = " . db::real_escape_string($id_prov);
        $result = db::query($query);

        $descuentos = array();
        while ($row = db::fetch_assoc($result)) {
            $descuentos[] = $row;
        }
        return $descuentos;
    }

    //Metodo para activar un descuento
    public static function activarDescuento($id_descuento) {
        $query = "UPDATE gmcv_descuento SET id_status = 4 WHERE id = " . db::real_escape_string($id_descuento);
        $result = db::query($query);
        if ($result) {
            return true;
        } else {
            return false;
        }
    }



}
?>