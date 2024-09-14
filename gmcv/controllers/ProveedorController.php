<?php
//Array de modelos a incluir según sea necesario
$modelos = ['Proveedor']; 
include "header.php";

class ProveedorController extends db {
    //Obnemos las oficina por id de proveedor y id de bodega
    public function getOficinasByidProvIdBod($args=[]){
        //Instanciamos el modelo de proveedor
        $proveedor = new Proveedor();       
        return $proveedor->getOficinasByidProvIdBod($args['proveedores'],$args['bodegas']);        
    }
}
?>
