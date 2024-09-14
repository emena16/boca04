<?
$ruta = "../../../";
include $ruta."sys/precarga_mysqli.php";
//include $ruta."sys/fcn/fcnSelects.php";

$permitidos = array(1, 8, 9, 10, 13);
$acceso = validaAccesoAutorizado($permitidos);

if($acceso == 0)
	redirect("", "");


function limpiaCadena($cadena) {
    // Reemplazamos los acentos
    $sinAcentos = str_replace(
        ['á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú'],
        ['a', 'e', 'i', 'o', 'u', 'A', 'E', 'I', 'O', 'U'],
        $cadena
    );

    // Eliminamos las comas y los guiones
    $sinComasNiGuiones = str_replace(['-', ','], '', $sinAcentos);

    return $sinComasNiGuiones;
}

$response = array();

//Nos comportamos de aceurdo a la acción solicitada
if(isset($_POST['accion'])) {
    switch($_POST['accion']) {
        case 'obtenerUOperativas':
            $query = "SELECT id,nombre 
            from oficina
            INNER JOIN bod_oficina ON bod_oficina.id_oficina = oficina.id
            WHERE oficina.id_status = 4 AND bod_oficina.id_bodega = {$_POST['idBodega']} ORDER by oficina.nombre ASC";
            if(!$resultUsuarios = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
            //Recorremos las unidades operativas
            $unidadesOperativas = array();
            $aux = array();
            while($row = mysqli_fetch_assoc($resultUsuarios)) {
                $aux['id'] = $row['id'];
                $aux['nombre'] = $row['nombre'];
                array_push($response, $aux);
                //limipiamos el aux
                $aux = array();
            }
            break;

        case 'obtenerRutas':
            $query = "SELECT id, nombre FROM ruta WHERE id_oficina = {$_POST['idUOperativa']} ORDER BY nombre ASC";
            if(!$resultRutas = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
            //Recorremos las rutas
            $rutas = array();
            $aux = array();
            while($row = mysqli_fetch_assoc($resultRutas)) {
                $aux['id'] = $row['id'];
                $aux['nombre'] = $row['nombre'];
                array_push($response, $aux);
                //limipiamos el aux
                $aux = array();
            }
            break;

        default:
            $response['mensaje'] = 'Acción no válida';
            break;
    }
} else {
    $response['mensaje'] = 'No se ha especificado una acción';
    //Devolvemos un error
    http_response_code(500);
}

echo json_encode($response);