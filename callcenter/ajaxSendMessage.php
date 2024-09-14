<?
$ruta = "../../../";
include $ruta."sys/precarga_mysqli.php";
//include $ruta."sys/fcn/fcnSelects.php";

$permitidos = array(1, 8, 9, 10, 13);
$acceso = validaAccesoAutorizado($permitidos);

if($acceso == 0)
	redirect("", "");

// Creamos un encabezado del ticket de venta del pedido
$encabezado = "Ticket de Venta\n";


$ch = curl_init();
// Configuramos las opciones del curl para enviar un mensaje de WhatsApp 
curl_setopt($ch, CURLOPT_URL, 'https://api.twilio.com/2010-04-01/Accounts/ACbb9490668fcef8b553398b62346a2b4b/Messages.json');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
    'To' => 'whatsapp:+5212481135123',
    'From' => 'whatsapp:+14155238886',
    'Body' => 'Mensaje de prueba desde PHP. Texto en negritas *negritas* y texto en cursivas _cursivas_ y texto tachado ~tachado~. Emoji: 😃'
)));
curl_setopt($ch, CURLOPT_USERPWD, 'ACbb9490668fcef8b553398b62346a2b4b' . ':' . '7949037b98f1be4370ea6c4747fcd500');

$result = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
}
curl_close($ch);

echo $result;

/*
$.ajax({
    url: 'ajaxSendMessage.php', 
    type: 'POST',
    dataType: 'json', 
    success: function(response) {
        console.log(response)
    },
    error: function(xhr, status, error) {
        console.error('Error en la solicitud AJAX:', error);
    }
});
*/

?>
