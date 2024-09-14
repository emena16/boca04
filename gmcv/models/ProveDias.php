<?php
// Incluimos el header
include_once 'header.php';

/* Tabla que vamosa modelar: 
CREATE TABLE `prove_dias` (
  `id` int(11) NOT NULL,
  `id_bodega` smallint(2) NOT NULL,
  `id_prov` mediumint(6) NOT NULL,
  `dias` int(11) NOT NULL,
  `dias_compromiso` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `id_segmento` smallint(6) NOT NULL DEFAULT '6'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci; 
*/

class ProveDias extends db {
    private $table_name = "prove_dias";

    public $id;
    public $id_bodega;
    public $id_prov;
    public $dias;
    public $dias_compromiso;
    public $id_segmento;

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " (
            id,
            id_bodega,
            id_prov,
            dias,
            dias_compromiso,
            id_segmento
        ) VALUES (
            '" . db::real_escape_string($this->id) . "',
            '" . db::real_escape_string($this->id_bodega) . "',
            '" . db::real_escape_string($this->id_prov) . "',
            '" . db::real_escape_string($this->dias) . "',
            '" . db::real_escape_string($this->dias_compromiso) . "',
            '" . db::real_escape_string($this->id_segmento) . "'
        )";

        $result = db::query($query);

        if ($result) {
            return db::insert_id();
        } else {
            return false;
        }
    }

    public function update(){
        $query = "UPDATE " . $this->table_name . " SET
            id_bodega = '" . db::real_escape_string($this->id_bodega) . "',
            id_prov = '" . db::real_escape_string($this->id_prov) . "',
            dias = '" . db::real_escape_string($this->dias) . "',
            dias_compromiso = '" . db::real_escape_string($this->dias_compromiso) . "',
            id_segmento = '" . db::real_escape_string($this->id_segmento) . "'
        WHERE id = " . $this->id;

        $result = db::query($query);

        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    public static function getById($id) {
        $query = "SELECT * FROM prove_dias WHERE id = $id";
        $result = db::query($query);
        //Retornamos el array
        return db::fetch_assoc($result);
    }

    public static function getByIdObject($id) {
        $proveObj = new ProveDias();
        // Llenamos los datos del objeto
        $proveArray = self::getById($id);
        foreach ($proveArray as $key => $value) {
            $proveObj->$key = $value;
        }
        return $proveObj;
    }

    public static function getByProvBod($id_prov, $id_bodega) {
        $query = "SELECT * FROM prove_dias WHERE id_prov = $id_prov AND id_bodega = $id_bodega";
        $result = db::query($query);
        //Retornamos el array
        return db::fetch_assoc($result);
    }


}