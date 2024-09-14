<?php
// Incluimos el header
include_once 'header.php';

class Almacen extends db {
    private $table_name = "almacen";

    public $id;
    public $id_compra;
    public $cantidad;
    public $id_status;
    public $alta;
    public $ucompra;
    public $id_bodega;
    public $costo_unitario;
    public $caducidad;
    public $extraido;
    public $factura;

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " (
            id_compra,
            cantidad,
            id_status,
            alta,
            ucompra,
            id_bodega,
            costo_unitario,
            caducidad,
            extraido,
            factura
        ) VALUES (
            '" . db::real_escape_string($this->id_compra) . "',
            ".$this->cantidad.",
            '" . db::real_escape_string($this->id_status) . "',
            '" . db::real_escape_string($this->alta) . "',
            ".$this->ucompra.",
            '" . db::real_escape_string($this->id_bodega) . "',
            '" . db::real_escape_string($this->costo_unitario) . "',
            '" . db::real_escape_string($this->caducidad) . "',
            '" . db::real_escape_string($this->extraido) . "',
            '" . db::real_escape_string($this->factura) . "'
        )";
        
        $result = db::query($query);

        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    public static function getAlmacenByIdProdCompra($id_compra) {
        $query = "SELECT * FROM almacen WHERE id_compra = '" . db::real_escape_string($id_compra) . "'";
        //Escribimos el query en un fichero
        // file_put_contents('Almacen_getAlmacenByIdProdCompra.txt', $query);
        $result = db::query($query);
        return db::fetch_array($result);
    }


    public static function getAlmacenByCompraId($id_compra) {
        $query = "SELECT * FROM almacen WHERE id_compra = '" . db::real_escape_string($id_compra) . "'";
        $result = db::query($query);
        $almacen = array();
        while ($row = db::fetch_assoc($result)) {
            $almacen[] = $row;
        }
        return $almacen;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " SET 
            id_compra = '" . db::real_escape_string($this->id_compra) . "',
            cantidad = '" . db::real_escape_string($this->cantidad) . "',
            id_status = '" . db::real_escape_string($this->id_status) . "',
            alta = '" . db::real_escape_string($this->alta) . "',
            ucompra = '" . db::real_escape_string($this->ucompra) . "',
            id_bodega = '" . db::real_escape_string($this->id_bodega) . "',
            costo_unitario = '" . db::real_escape_string($this->costo_unitario) . "',
            caducidad = '" . db::real_escape_string($this->caducidad) . "',
            extraido = '" . db::real_escape_string($this->extraido) . "',
            factura = '" . db::real_escape_string($this->factura) . "'
            WHERE id = '" . db::real_escape_string($this->id) . "'";

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

    public function getAlmacenByIdObject() {
        //Instanciamos el objeto que vamos a retornar
        $almacenObject = new Almacen();
        //Obtenemos el almacen por el id
        $almacenArray = $almacenObject->getAlmacenById($this->id);
        //Recorremos el array para asignar los valores al objeto+
        foreach ($almacenArray as $row) {
            $almacenObject->id = $row['id'];
            $almacenObject->id_compra = $row['id_compra'];
            $almacenObject->cantidad = $row['cantidad'];
            $almacenObject->id_status = $row['id_status'];
            $almacenObject->alta = $row['alta'];
            $almacenObject->ucompra = $row['ucompra'];
            $almacenObject->id_bodega = $row['id_bodega'];
            $almacenObject->costo_unitario = $row['costo_unitario'];
            $almacenObject->caducidad = $row['caducidad'];
            $almacenObject->extraido = $row['extraido'];
            $almacenObject->factura = $row['factura'];
        }
        //Retornamos el objeto
        return $almacenObject;
    }

    public function getAlmacenByIdCompra($id_compra){
        $query = "SELECT * FROM almacen WHERE id_compra = '" . db::real_escape_string($id_compra) . "'";
        $result = db::query($query);

        $almacen = array();
        while ($row = db::fetch_assoc($result)) {
            $almacen[] = $row;
        }
        return $almacen;
    }

    public function getAlmacenByIdCompraObject($id_compra){
        //Instanciamos el objeto que vamos a retornar
        $almacenObject = new Almacen();
        //Obtenemos el almacen por el id
        $almacenArray = $almacenObject->getAlmacenByIdCompra($id_compra);
        //Si no hay registros retornamos false
        if(empty($almacenArray)) return false;


        //Recorremos el array para asignar los valores al objeto+
        foreach ($almacenArray as $row) {
            $almacenObject->id = $row['id'];
            $almacenObject->id_compra = $row['id_compra'];
            $almacenObject->cantidad = $row['cantidad'];
            $almacenObject->id_status = $row['id_status'];
            $almacenObject->alta = $row['alta'];
            $almacenObject->ucompra = $row['ucompra'];
            $almacenObject->id_bodega = $row['id_bodega'];
            $almacenObject->costo_unitario = $row['costo_unitario'];
            $almacenObject->caducidad = $row['caducidad'];
            $almacenObject->extraido = $row['extraido'];
            $almacenObject->factura = $row['factura'];
        }
        //Retornamos el objeto
        return $almacenObject;
    }

    public static function getAlmacenById($id) {
        $query = "SELECT * FROM almacen WHERE id = '" . db::real_escape_string($id) . "'";
        $result = db::query($query);

        $almacen = array();
        while ($row = db::fetch_assoc($result)) {
            $almacen[] = $row;
        }
        return $almacen;
    }
}

?>
