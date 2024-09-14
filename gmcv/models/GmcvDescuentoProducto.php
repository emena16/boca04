<?php
// Incluimos el header
include_once 'header.php';
class GmcvDescuentoProducto extends db {
    private $table_name = "gmcv_descuento_producto";

    public $id_descuento;
    public $id_prov;
    public $id_bodega;
    public $id_prod;
    public $ini;
    public $id_status;
    public $descuento;

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " (
            id_descuento,
            id_prov,
            id_bodega,
            id_prod,
            ini,
            id_status,
            descuento
        ) VALUES (
            " .$this->id_descuento . ",
            " .$this->id_prov . ",
            " .$this->id_bodega . ",
            " .$this->id_prod . ",
            '" .$this->ini . "',
            " .$this->id_status . ",
            " .$this->descuento . "
        )";
        
        $result = db::query($query);

        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    //Creamos un metodo para obtner los productos a traves de un id producto
    public static function getDescuentosProducto($id_prod, $id_prov, $id_bodega, $fecha) {
        //Consultamos en las 
        // $query = "SELECT d.id, d.id_prov, d.nombre, d.id_status, d.posteriorCP, db.id_descuento, db.id_bodega, dp.id_descuento, dp.id_prov, dp.id_bodega, dp.id_prod, dp.ini, dp.id_status, dp.descuento
        // FROM gmcv_descuento d
        // JOIN gmcv_descuento_bodega db ON db.id_descuento = d.id AND d.id_status = 4
        // JOIN gmcv_descuento_producto dp ON dp.id_descuento = d.id
        // JOIN (
        //         SELECT id_descuento, id_prov, id_bodega, id_prod, MAX(ini) AS max_ini
        //         FROM gmcv_descuento_producto
        //         WHERE id_bodega = $id_bodega AND id_prov = $id_prov AND id_prod = $id_prod
        //         GROUP BY id_descuento, id_prov, id_bodega, id_prod
        //     ) dp_max ON dp.id_descuento = dp_max.id_descuento AND dp.id_prov = dp_max.id_prov AND dp.id_bodega = dp_max.id_bodega AND dp.id_prod = dp_max.id_prod AND dp.ini = dp_max.max_ini
        // WHERE db.id_bodega = $id_bodega AND d.id_prov = $id_prov AND dp.id_prod = $id_prod ORDER BY d.posteriorCP ASC";
        $query = "SELECT d.id, d.id_prov, d.nombre, d.id_status, d.posteriorCP, db.id_descuento, db.id_bodega, dp.id_descuento, dp.id_prov, dp.id_bodega, dp.id_prod, dp.ini, dp.id_status, dp.descuento
        FROM gmcv_descuento d
        JOIN gmcv_descuento_bodega db ON db.id_descuento = d.id AND d.id_status = 4
        JOIN gmcv_descuento_producto dp ON dp.id_descuento = d.id
        JOIN (
                SELECT id_descuento, id_prov, id_bodega, id_prod, MAX(ini) AS max_ini
                FROM gmcv_descuento_producto
                WHERE id_bodega = $id_bodega AND id_prov = $id_prov AND id_prod = $id_prod AND ini <= '$fecha' -- Ajuste para comparar con la fecha proporcionada
                GROUP BY id_descuento, id_prov, id_bodega, id_prod
            ) dp_max ON dp.id_descuento = dp_max.id_descuento        AND dp.id_prov = dp_max.id_prov        AND dp.id_bodega = dp_max.id_bodega        AND dp.id_prod = dp_max.id_prod        AND dp.ini = dp_max.max_ini
        WHERE db.id_bodega = $id_bodega AND d.id_prov = $id_prov AND dp.id_prod = $id_prod 
        ORDER BY d.posteriorCP ASC";
        //Escribir el query en un archivo para debugear
        file_put_contents('query______getDescuentosProducto.txt', $query);
        $result = db::query($query);
        
        //Retornamos el array
        $descuentos = array();
        while ($row = db::fetch_assoc($result)) {
            $descuentos[] = $row;
        }
        return $descuentos;


    }

    //Creamos un metodo para obtner los productos a traves de un id producto
    public static function getDescuentosProductoByIdDescuento($id_prod, $id_prov, $id_bodega, $fecha, $id_descuento) {
        //Es query es igual al de la funcion: getDescuentosProducto, solo que se agrega la condicion de id_descuento
        $query = "SELECT d.id, d.id_prov, d.nombre, d.id_status, d.posteriorCP, db.id_descuento, db.id_bodega, dp.id_descuento, dp.id_prov, dp.id_bodega, dp.id_prod, dp.ini, dp.id_status, dp.descuento
        FROM gmcv_descuento d
        JOIN gmcv_descuento_bodega db ON db.id_descuento = d.id AND d.id_status = 4
        JOIN gmcv_descuento_producto dp ON dp.id_descuento = d.id
        JOIN (
                SELECT id_descuento, id_prov, id_bodega, id_prod, MAX(ini) AS max_ini
                FROM gmcv_descuento_producto
                WHERE id_bodega = $id_bodega AND id_prov = $id_prov AND id_prod = $id_prod AND ini <= '$fecha' -- Ajuste para comparar con la fecha proporcionada
                GROUP BY id_descuento, id_prov, id_bodega, id_prod
            ) dp_max ON dp.id_descuento = dp_max.id_descuento        AND dp.id_prov = dp_max.id_prov AND dp.id_bodega = dp_max.id_bodega        AND dp.id_prod = dp_max.id_prod        AND dp.ini = dp_max.max_ini
        WHERE db.id_bodega = $id_bodega AND d.id_prov = $id_prov AND dp.id_prod = $id_prod AND d.id = $id_descuento
        ORDER BY d.posteriorCP ASC";
        //Escribir el query en un archivo para debugear
        file_put_contents('query_getDescuentosProductoByIdDescuento.txt', $query);
        $result = db::query($query);
        if (!$result) {
            return false;
        }
        //Retornamos el array
        return db::fetch_array($result);
    }


    //Creamos un metodo para obtner los productos a traves de un id producto
    public static function getDescuentosProductoByBodegasFecha($id_prod, $id_prov, $bodegas,$fecha) {
        //Consultamos en las 
        $query = "SELECT d.id, d.id_prov, d.nombre, d.id_status, d.posteriorCP, dp.id_descuento, dp.id_prov, dp.id_prod, dp.ini, dp.id_status, dp.descuento,
            GROUP_CONCAT(DISTINCT db.id_bodega ORDER BY db.id_bodega) AS bodegasArray
        FROM gmcv_descuento d
        JOIN gmcv_descuento_producto dp ON dp.id_descuento = d.id
        JOIN gmcv_descuento_bodega db ON db.id_descuento = d.id
        JOIN (
            -- Obtenemos el maximo ini
            SELECT id_descuento,id_prov,id_prod,MAX(ini) AS max_ini
            FROM gmcv_descuento_producto
            WHERE id_prov = $id_prov AND id_prod = $id_prod
            AND ini <= DATE('$fecha') AND id_bodega IN ($bodegas)
            GROUP BY id_descuento,id_prov,id_prod
        ) dp_max 
        ON dp.id_descuento = dp_max.id_descuento AND dp.id_prov = dp_max.id_prov AND dp.id_prod = dp_max.id_prod AND dp.ini = dp_max.max_ini
        WHERE d.id_prov = $id_prov AND dp.id_prod = $id_prod AND db.id_bodega IN ($bodegas) AND d.id_status = 4 
        GROUP BY d.id, dp.id_descuento, dp.id_prov, dp.id_prod, dp.ini, dp.id_status, dp.descuento
        ORDER BY d.posteriorCP ASC";
        $result = db::query($query);
        //Agregamos el query a un archivo de para poder debugear
        file_put_contents('query_getDescuentosProductoByBodegasFecha.txt', $query);
        //Retornamos el array
        $descuentos = array();
        while ($row = db::fetch_assoc($result)) {
            $descuentos[] = $row;
        }
        //Escribimos el array en json en el archivo de debug: precioLista.txt
        // file_put_contents('JSON_getDescuentosProductoByBodegasFecha.txt', json_encode($descuentos));

        return $descuentos;
    }

    //Creamos un metodo para eliminar id_prov, id_boega e ini
    function deleteByIdProvBodIni($id_prov, $bodegas, $ini) {
        $query = "DELETE FROM " . $this->table_name . 
        " WHERE id_prov = " .$id_prov . " 
        AND id_bodega IN (" . db::real_escape_string($bodegas) . ") 
        AND ini = '" .$ini . "'";
        $result = db::query($query);

        // $query = "DELETE FROM " . $this->table_name . 
        // " WHERE id_prov = " .$id_prov . " 
        // AND ini = '" .$ini . "'";
        // $result = db::query($query);

        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    //Creamos un metodo para cambiar el status de un descuento en funcion a su id y id_prov
    public static function cambiarStatusDescuento($id_descuento, $id_prov, $status) {
        $query = "UPDATE gmcv_descuento_producto SET id_status = $status WHERE id_descuento = $id_descuento AND id_prov = $id_prov";
        $result = db::query($query);
        if ($result) {
            return true;
        } else {
            return false;
        }
    }
    
    


}




?>