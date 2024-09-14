<?php
//atencion de REQUEST de AJAX
// require_once '../controllers/productoController.php';
# Define main path
$controllerPath = '../controllers/';

# Data send by POST
$controllerName = $_POST['controller'].'Controller';
$action = $_POST['action'];
$args = isset($_POST['args']) && !empty($_POST['args']) ? $_POST['args'] : null;

# Define controller path
$controllerPath .= $controllerName.'.php';

# Verifi if controller exists
if (is_file($controllerPath)) {
    require_once $controllerPath;
}# End if
else {
    echo json_encode(['error' => 'Controlador no encontrado.']);
    exit();
}# End else

# Create Instance
$controller = new $controllerName();

# Verify if the method exixts
if (method_exists($controller, $action)) {
    
    # Execute the called method
    $result = $controller->$action($args);
    echo json_encode($result);
}# End 
else {
    echo json_encode(['error' => 'Acción no encontrada.']);
    exit();
}# End else 
