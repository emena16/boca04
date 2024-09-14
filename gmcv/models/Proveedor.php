<?php
// Incluimos el header
include_once 'header.php';
//Cremos la clase Proveedor
class Proveedor extends db {
    private $table_name = "proveedor";

    public $id;
    public $corto;
    public $largo;
    public $correo;
    public $id_status;
    public $cod_prod_obligatorio;
    public $cod_ean_obligatorio;

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " (
            id,
            corto,
            largo,
            correo,
            id_status,
            cod_prod_obligatorio,
            cod_ean_obligatorio
        ) VALUES (
            '" . db::real_escape_string($this->id) . "',
            '" . db::real_escape_string($this->corto) . "',
            '" . db::real_escape_string($this->largo) . "',
            '" . db::real_escape_string($this->correo) . "',
            '" . db::real_escape_string($this->id_status) . "',
            '" . db::real_escape_string($this->cod_prod_obligatorio) . "',
            '" . db::real_escape_string($this->cod_ean_obligatorio) . "'
        )";

        $result = db::query($query);

        if ($result) {
            return db::insert_id();
        } else {
            return false;
        }
    }

    public static function getById($id) {
        $query = "SELECT * FROM proveedor WHERE id = $id";
        $result = db::query($query);
        //Retornamos el array
        $descuentos = array();
        while ($row = db::fetch_assoc($result)) {
            $descuentos[] = $row;
        }
        return $descuentos;
    }

    public function getOficinasByidProvIdBod($proveedores,$bodegas){
		$query = "SELECT bo.id_oficina as id, o.nombre
		from producto p
		join prod_medida pm on p.id = pm.id_prod && pm.id_status = 4
		join prod_oficina po on pm.id = po.id_prod && po.id_status = 4
		join bod_oficina bo on po.id_oficina = bo.id_oficina
		join bodega b on bo.id_bodega = b.id && b.id_status = 4
		join oficina o on po.id_oficina = o.id && o.id_status = 4
		WHERE p.id_tipo_venta = 1 && p.id_prov in ($proveedores) && bo.id_bodega in ($bodegas)
		group by bo.id_oficina order by o.nombre";

        $result = db::query($query);
        $oficinas = array();
        while ($row = db::fetch_assoc($result)) {
            $oficinas[] = $row;
        }
        return $oficinas;		
	}


}