<?
$ruta = "../../../";
include $ruta."sys/precarga_mysqli.php";
//include $ruta."sys/fcn/fcnSelects.php";

$permitidos = array(1, 8, 9, 10, 13);
$acceso = validaAccesoAutorizado($permitidos);

if($acceso == 0)
	redirect("", "");

//Algoritomo de cifrado para generar el URL de visualización del pedido
function encriptaIdPdo($idPdo) {
    $partes     = explode('-',$idPdo);
    $idUsr      = substr("0000".$partes[0], -5);
    $alta       = ($partes[1]);
    $mezcla     = $idUsr[0].$alta[0].$idUsr[1].$alta[1].$idUsr[2].$alta[2].$idUsr[3].$alta[3]
        .$idUsr[4].$alta[4].substr($alta, -6);
    $encriptado = encriptaInt($mezcla);
//     echo  " idPdo: ".$idPdo .", mezcla: ".$mezcla .", enc: ".$encriptado;
    return $encriptado;
}
function encriptaInt($numInt) {
    $str = "@".$numInt;
    $res = '';
    $len = strlen($str)-1;
    while ($len > 0) {
        $res .= encriptaDigito($str[$len]);
        $len--;
    }
    return strrev($res);
}
function getSemillaEncripta() {
    // LA SEMILLA DEBE SER MULTIPLO DE 10 Y NO TENER CARACTERES REPETIDOS
    //return 'TiDHB264-[ZjGcUO*qtfeYRKL.h9]1swF:xM0uCWNb3yXVSl87m;P{JrIdzAganQ5vpkoE';
    return 'TiDHB264-(ZjGcUO*qtfeYRKL.h9)1swF:xM0uCWNb3yXVSl87m;P_JrIdzAganQ5vpkoE';
}

function encriptaDigito($unDig) {
    $val        = intval($unDig);
    $sem        = getSemillaEncripta();
    $cripChar   = $sem[(rand(0,6)*10+$val)];
    return $cripChar;
}

function desencriptaChar($char) {
    return strpos(getSemillaEncripta(), $char[0])%10;
}

//Obtenemos los datos
$response = array();
$url = 'https://'.$_SERVER["HTTP_HOST"].'/mtto/admin/pdo/ticketweb/ticket/'.encriptaIdPdo($_POST['idPedido']."1");
$response['url'] = $url;
$response['objeto'] = '<a target="_blank" href="'.$url.'">Ver</a>';

echo json_encode($response);
?>

