<?php
// Incluimos el header
include_once 'header.php';

class ProdOficina extends db {
    private $table_name = "prod_oficina";

   public $id_prod;
   public $id_oficina;
   public $id_status;
   public $alta;
   public $venta;
   public $fecha_cambio;
   public $venta_nva;
   public $pendiente;

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " (
            id_prod,
            id_oficina,
            id_status,
            alta,
            venta,
            fecha_cambio,
            venta_nva,
            pendiente
        ) VALUES (
            ".$this->id_prod.",
            ".$this->id_oficina.",
            ".$this->id_status.",
            ".$this->alta.",
            ".$this->venta.",
            '" . db::real_escape_string($this->fecha_cambio) . "',
            ".$this->venta_nva.  ",
            ".$this->pendiente . "
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
            id_status = ".$this->id_status.  ",
            alta = '".$this->alta.  "',
            venta = ".$this->venta.  ",
            fecha_cambio = '".$this->fecha_cambio.  "',
            venta_nva = ".$this->venta_nva.  ",
            pendiente = ".$this->pendiente . "
        WHERE id_prod = ".$this->id_prod . " AND id_oficina = ".$this->id_oficina;
        //Escribimos en un archivo de debug que se intento actualizar un producto de oficina
        // $file = fopen("precioVenta.txt", "a");
        // fwrite($file, $query . "\n");
        // fclose($file);

        $result = db::query($query);

        if ($result) {
            return true;
        } else {
            return false;
        }
    }   

    public static function getByIdProdIdOficina($id_prod, $id_oficina) {
        $query = "SELECT * FROM prod_oficina WHERE id_prod = " . $id_prod . " AND id_oficina = " . $id_oficina;
        $result = db::query($query);
        //Alacenamos una copia de los querys en un archivo de debug
        // $file = fopen("querys_precioLista.txt", "a");
        // fwrite($file, $query . "\n");
        // fclose($file);
        if ($result) {
            //Retornamos el array
            $array = $result->fetch_array(MYSQLI_ASSOC);
            return $array;
        } else {
            return false;
        }
       
    }

    public static function getByIdProdIdOficinaObject($id_prod, $id_oficina) {
        
        $prodOficinaArray = ProdOficina::getByIdProdIdOficina($id_prod, $id_oficina);

        //Si recibismo un array con datos entonces creamos el objeto de otro modo retornamos false
        if (!$prodOficinaArray){
            return false;
        }

        // //Escribimos en un archivo de debug que se intento actualizar un producto de oficina
        // $file = fopen("rows_prod_ofi.txt", "a");
        // fwrite($file, json_encode($prodOficinaArray) . "\n\n");
        //Intanciamos un objeto de la clase ProdOficina
        $prodOficina = new ProdOficina();

        $prodOficina->id_prod = $prodOficinaArray['id_prod'];
        $prodOficina->id_oficina = $prodOficinaArray['id_oficina'];
        $prodOficina->id_status = $prodOficinaArray['id_status'];
        $prodOficina->alta = $prodOficinaArray['alta'];
        $prodOficina->venta = $prodOficinaArray['venta'];
        $prodOficina->fecha_cambio = $prodOficinaArray['fecha_cambio'];
        $prodOficina->venta_nva = $prodOficinaArray['venta_nva'];
        $prodOficina->pendiente = $prodOficinaArray['pendiente'];
        return $prodOficina;
    }

    //Creamos un metodo para obtener los productos de la oficina
    public static function getProdOficina($id_prod, $id_oficina) {
        //Consultamos en las 
        $query = "SELECT po.id_prod, po.id_oficina, po.id_status, po.alta, po.venta, po.fecha_cambio, po.venta_nva, po.pendiente
        FROM prod_oficina po
        WHERE po.id_prod = $id_prod AND po.id_oficina = $id_oficina";     
        $result = db::query($query);
        $array = array();
        while ($row = db::fetch_assoc($result)) {
            $array[] = $row;
        }

        if ($result) {
            return $array;
        } else {
            return false;
        }
    }
}
   