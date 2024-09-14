<?
$ruta = "../../../";
include $ruta."sys/precarga_mysqli.php";
//include $ruta."sys/fcn/fcnSelects.php";

$permitidos = array(1, 8, 9, 10, 13);
$acceso = validaAccesoAutorizado($permitidos);

if($acceso == 0)
	redirect("", "");

    function formatearTelefono($numero) {
        if (strlen($numero) != 10) {
            return $numero;
        }
        return substr($numero, 0, 3) . '-' . substr($numero, 3, 3) . '-' . substr($numero, 6);
    }

    $response = array(
        'telefonos' => array(),
        'mensaje' => "",
        'status' => 0
    );
    //Vamos a insertar el nuevo telefono en la base de datos
    if(isset($_POST['nuevoTelefono'])){
        //Verificamos que el telefono no este vacio y que sea un numero sin letras
        if(empty($_POST['nuevoTelefono']) || !is_numeric($_POST['nuevoTelefono'])){
            $response['mensaje'] = "El teléfono no puede estar vacio y debe ser un número";
            $response['status'] = 0;
            echo json_encode($response);
            exit();
        }

        //Antes vamos a verificar que no exista el telefono en la base de datos
        $queryVerifica = "SELECT * FROM callcenter_telefonos WHERE numero = '".$_POST['nuevoTelefono']."'";
        if(!$result = mysqli_query($conexion_bd,$queryVerifica))errores(mysqli_errno($conexion_bd), 0);
        //Si el telefono ya existe en la base de datos, mandamos un mensaje de error y no insertamos nada de lo contrario insertamos el telefono
        if(mysqli_num_rows($result) > 0){
            $response['mensaje'] = "El teléfono ya existe en la base de datos";
            $response['status'] = 0;
        }else{
            //Si todo esta bien, insertamos el telefono
            $query = "INSERT INTO callcenter_telefonos (numero, id_status) VALUES ('".$_POST['nuevoTelefono']."', 4)";
            if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
            
            $response['mensaje'] = "El teléfono se ha guardado correctamente";
            $response['status'] = 1;
        }        
    }
    $query = "SELECT * FROM callcenter_telefonos";
    if(!$result = mysqli_query($conexion_bd,$query))errores(mysqli_errno($conexion_bd), 0);
    $aux = array();
    while($row = mysqli_fetch_array($result)){
        //Utilizamos un array auxiliar para guardar los datos de cada fila
        $aux['id'] = $row['id'];
        $aux['numero'] = formatearTelefono($row['numero']);
        $aux['id_status'] = $row['id_status'];
        $aux['boton'] = $row['id_status'] == 4 ? "<td><button class='btn btn-success btn-sm btnCambiaEstadoTelefono' id='".$row['id']."'><span><i style=' color: #f6fcfb;' data-feather='check'></i></span>&emsp;Activo</button></td>" : "<td><button class='btn btn-danger btn-sm btnCambiaEstadoTelefono' id='".$row['id']."'><span><i style=' color: #f6fcfb;' data-feather='slash'></i></span>&emsp;Inactivo</button></td>";
        //Guardamos el array auxiliar en el array principal
        array_push($response['telefonos'], $aux);
        //Limipiamos el array auxiliar
        $aux = array();
    }
    
    echo json_encode($response);

?>