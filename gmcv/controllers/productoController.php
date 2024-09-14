<?php
//Incluimos el modelo producto
if(file_exists("../models/productoModel.php"))
    require_once '../models/productoModel.php';
else
    require_once "./models/productoModel.php";


//Creamos el controlador del modelo producto
class productoController {
    
    # Instanciamos el modelo producto
    private $productoModel;

    /**
     * Constructor de la clase productoController
     */
    public function __construct() {
        $this->productoModel = new productoModel();
    }# End __construct

    /**
     * Mejora la impresion de print_r
     * @param type $mixed
     * @param type $stop
     */
    public static function pr($mixed, $stop = false) {
        echo '<pre>';
        print_r($mixed);
        if($stop) exit;
    }# End pr

    /**
     * Metodo que crea los <options> para un select
     * @param array $options
     * @param null|int $defaultValue
     * @return string
     */
    public static function createSelectOptions($options, $defaultValue=null) {
        // $data = '<option value="" selected disabled>- Selecciona una opción -</option>';
        $data = '';

        foreach ($options as $key => $value) {
            $keyOptions = array_keys($options[$key]);
            $data .= '<option value="' .
                $value[$keyOptions[0]] . '" ' . ($defaultValue == $value[$keyOptions[0]] ? 'selected ': '');

            # Verify data-target exists
            if (count($keyOptions) > 2) {
                for ($i=2, $iMax = count($keyOptions); $i < $iMax; $i++) {
                    $data.=' data-'.$keyOptions[$i].'='.$value[$keyOptions[$i]];
                }# End for
            }# End if

            $data.='>' . $value[$keyOptions[1]]
                . '</option>';
        }# End foreach

        return $data;
    }# End createSelectOptions

    /**
     * Metodo que lista la información de las oficinas
     * @return array
     */
    public function listarOficinas() {
        $oficinas = $this->productoModel->getOficinas();
        return $oficinas;
    }# End listarOficinas

    /**
     * Metodo que lista los grupos
     * @return array
     */
    public function listarGrupos() {
        $grupos = $this->productoModel->getGrupos();
        return $grupos;
    }# End listarGrupos

    /**
     * Metodo que retorna un array de proveedores para la vista
     * @return array|false
     */
    public function listarProveedores(){
        return $this->productoModel->getProviders();
    }# End listarProveedores

    /**
     * Metodo que retorna las bodegas asignadas al usuario
     * @return array|false
     */
    public function listarBodegas(){
        return $this->productoModel->getStores();
    }# End listarBodegas

    /**
     * Metodo que retorna las unidades operativas de la bodega
     * @return array|false
     */
    public function listarOficinasPorBodega($args = []){
        $storeID = isset($args['storeID']) && !empty($args['storeID']) ? $args['storeID'] : null;
        
        return $this->productoModel->getOffices($storeID);
    }# End listarOficinasPorBodega

    /**
     * Metodo que obtiene los intecambiables
     * @param null|array $args
     * @return array
     */
    public function getInterchangeables($args=[]) {
        $data = [];
        $stores = [];

        # Get data send by post
        $providerID = isset($args['providerID']) && !empty($args['providerID']) ? $args['providerID'] : null;
        $storeID = isset($args['storeID']) && !empty($args['storeID']) ? $args['storeID'] : null;
        $officeID = isset($args['officeID']) && !empty($args['officeID']) ? $args['officeID'] : null;
        $inactive = isset($args['inactive']) && !empty($args['inactive']) ? $args['inactive'] : null;

        # Get all active stores
        $stores = $this->productoModel->getStores();
        # Get interchangeable data
        $data['data'] = $this->productoModel->getInterchangeable($providerID, $storeID, $officeID, $inactive);

        # Create checkboxes for store
        foreach ($data['data'] as $key => $value) {
            $activeStores = explode(',',$value['bodegas']);
            foreach ($stores as $store) {
                if (in_array($store['id'], $activeStores)) {
                    $data['data'][$key]['bodegasText'] .=  "
                    <div class='col-sm-6 mb-n2'>
                        <label class='new-control new-checkbox'>
                            <input type='checkbox' class='new-control-input' disabled checked>
                            <span class='new-control-indicator'></span>
                            $store[nombre]
                        </label>
                    </div>";
                }# End if
                else {
                    $data['data'][$key]['bodegasText'] .= "
                    <div class='col-sm-6 mb-n2'>
                        <label class='new-control new-checkbox'>
                            <input type='checkbox' class='new-control-input' disabled>
                            <span class='new-control-indicator'></span>
                            $store[nombre]
                        </label> 
                    </div>";
                }# End else
            }# End if

            # Get products content
            $productsContainer = $this->productoModel->getContenido($value['prodMedidaID']);
            foreach ($productsContainer as $container) {
                $data['data'][$key]['productosText'] .= "<div class='col-sm-6'>{$container['contenido']}</div>";
            }# End foreach
        }# End foreach

        # Return intechangeables
        return $data;
    }# End getInterchangeables

    /**
     * Método que retorna un array de status para la vista
     * @return array
     */
    public function listarStatus(){
        return $this->productoModel->getSysStatus();
    }# End listarStatus

    /**
     * Método que retorna un array de Sub-Grupos para la vista
     * @return array
     * @throws Exception
     */
    public function listarSubGrupos(){
        return $this->productoModel->getSubGrupos();
    }# End listarSubGrupos
    
    /**
     * Método que retorna un array con los productos para la vista
     * @return array
     */
    public function listarProductos(){
        return $this->productoModel->getProductosIntercambiables();
    }# End listarProductos

    /**
     * LLamamos a un contenedor de productos a traves del metodo getContenedor
     * @return array
     */
    public function listarContenedor($args=[]){
        return $this->productoModel->getContenedor($args['args']['idContenedor']);
    }# End listarContenedor

    /**
     * Metodo que obtiene los productos para un select2
     * @return array
     */
    public function getItemsProductosSelect2(){
        return $this->productoModel->getItemsProductosSelect2();
    }# End getItemsProductosSelect2

    /**
     * LLamamos a los datos de un contenedor a traves del metodo getContenedorDatosGenerales
     * @return array
     */
    public function listarContenedorDatosGenerales($args=[]){
        return $this->productoModel->getContenedorDatosGenerales($args['args']['idContenedor']);
    }# End listarContenedorDatosGenerales

    /**
     * Metodo que verifica si ya existe alguna venta asociada a un producto
     * @param null|array $args
     * @return array
     */
    public function verifySales($args=[]) {
        # Define data
        $data = ['code' => 200, 'msg' => 'ok', 'salesExists' => false];
        $sales = [];

        try {
            // $sales = $this->;

        }# End try
        catch (Exception $ex) {
            $data['code'] = 500;
            $data['msg'] = $ex->getMessage();
        } # End catch

        return $data;
    }# End verifySales

    /**
     * Metodo que obtiene los grupos con productos asociados y las unidades operativas asociadas y no asociadas
     * @param null|array $args
     * @return array
     */
    public function getProducts($args=[]) {
        # Define data
        $data = ['code' => 200, 'msg' => 'ok', 'content' => []];
        $content = [
            'products' => [],
            'activeOffices' => '',
            'inactiveOffices' => '',
        ];

        try {
            # Get data send by post
            $containerID = isset($args['containerID']) && !empty($args['containerID']) ? $args['containerID'] : null;
            $stores = isset($args['stores']) && !empty($args['stores']) ? $args['stores'] : null;

            # Get groups products content
            $products = $this->productoModel->getContenido($containerID);
            # Create all group products
            foreach ($products as $value) {
                $content['products'][$value['nombreGrupo']][] = $value['contenido'];
            }# End foreach

            # Get all offices by stores
            $currentOffices = $stores ? $this->productoModel->getOffices($stores) : [];
            # Get all active offices
            $activeOffices = $this->productoModel->getActiveOffices($containerID)[0];
            $activeOffices = explode(',', $activeOffices['oficinas']);

            # Create active and inactive array offices
            foreach ($currentOffices as $value) {
                if (in_array($value['id'], $activeOffices)) {
                    $content['activeOffices'] .= "
                    <div class='col-12 col-sm-6 mb-n2'>
                        <label class='new-control new-checkbox'>
                            <input type='checkbox' class='new-control-input' disabled checked>
                            <span class='new-control-indicator'></span>
                            <p class='card-text text-wrap'>$value[nombre]</p>
                        </label>
                    </div>";
                }# En if
                else {
                    $content['inactiveOffices'] .= "<div class='col-6'><p class='card-text text-wrap'>".$value['nombre']."</p></div>";
                }# End else
            }# End foreach

            # Assign content data
            $data['content'] = $content;
        }# End try
        catch (Exception $ex) {
            $data['code'] = 500;
            $data['msg'] = $ex->getMessage();
        } # End catch
        return $data;
    }# End getProducts

    //Obtenemos las bodegas de un combo intercambiable 
    public function listarBodegasCombo($args=[]){
        return $this->productoModel->getBodegasByIdProdMed($args['args']['idCombo']);
    }# End

    //Obtenemos las oficinas de un combo intercambiable
    public function listarOficinasCombo($args=[]){
        return $this->productoModel->getOficinasByIdProd($args['args']['idCombo']);
    }# End

    //Obtenemos las oficinas asignadas a un combo intercambiable en funcion a una bodega y producto
    public function listarOficinasComboByProdBodega($args=[]){
        return $this->productoModel->getOficinasActivasByProdBodega($args['idProducto'], $args['idBodega']);
    }# End

            /**
     * Metodo para realizar el guardado de un nuevo producto
     * @param null|array $args
     * @return array
     */
    public function crearIntrcambiable($args=[]) {
        # Se define la información de retorno
        $data = ['code' => 200, 'msg' => 'ok', 'created' => false];

        try {
            # Obtener la información enviada por POST
            $nombre = isset($args['nombre']) && !empty($args['nombre']) ? $args['nombre'] : null;
            $ventaID = isset($args['idVenta']) && !empty($args['idVenta']) ? $args['idVenta'] : null;
            $proveedorID = isset($args['idProveedor']) && !empty($args['idProveedor']) ? $args['idProveedor'] : null;
            $grupoID = isset($args['idGrupo']) && !empty($args['idGrupo']) ? $args['idGrupo'] : null;
            $subGrupoID = isset($args['idSubGrupo']) && !empty($args['idSubGrupo']) ? $args['idSubGrupo'] : null;

            $intercambiables = isset($args['intercambiables']) && !empty($args['intercambiables']) ? $args['intercambiables'] : null;
            $bodegas = isset($args['bodegas']) && !empty($args['bodegas']) ? $args['bodegas'] : null;

            # Creamos el array del producto
            $productoData = [
                'nombre' => $nombre,
                'status' => $ventaID,
                'proveedorID' => $proveedorID,
                'grupoID' => $grupoID,
                'subGrupoID' => $subGrupoID
            ];
            # Insertamos la información del producto
            $productoID = $this->productoModel->crearProducto($productoData);

            # Creamos el array de producto medida
            $productoMedidaData = [
                'productoID' => $productoID,
                'status' => $ventaID,
            ];

            # Insertamos la información del producto medida
            $productoMedidaID = $this->productoModel->crearProductoMedida($productoMedidaData);

            $x=1;
            # Recorre la información para cada uno de los intercambiables
            foreach ($intercambiables as $intercambiable) {
                # Se crea array contenedor
                $contenedorData = [
                    'contenedorID' => $productoMedidaID,
                    'nombre' => $intercambiable['nombre'],
                    'cantidad' => $intercambiable['cantidad'],
                    'grupoID' => $x,
                ];
                
                # Se insertan los productos y se agregan a los grupos
                foreach ($intercambiable['productos'] as $producto) {
                    $contenedorData['productoID'] = $producto;
                    $this->productoModel->crearContenidoProductos($contenedorData);
                }# End foreach
                $x++;
            }# End foreach


            # Recorre la información para cada una de las bodegas y sus unidades operativas
            foreach ($bodegas as $key => $bodega) {
                $bodegaData = [
                    'productoID' => $productoID,
                    'bodegaID' => $key,
                ];
                
                # Se agrega la asociacion de la bodega
                $this->productoModel->crearAsociacionBodega($bodegaData);

                foreach ($bodega['oficinas'] as $keyTwo => $value) {
                    $oficinaData = [
                        'productoMedidaID' => $productoMedidaID,
                        'oficinaID' => $keyTwo,
                    ];
                    
                    # Se agrega la asociacion de la bodega
                    $this->productoModel->crearAsociacionOficina($oficinaData);
                }# End foreach
            }# End foreach

            $data['created'] = TRUE;
        }# End try
        catch (Exception $ex) {
            $data['code'] = 500;
            $data['msg'] = $ex->getMessage();
        }# End catch
        return $data;
    }# End crearIntrcambiable
}