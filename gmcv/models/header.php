<?php
//En caso de ser llamado por el controller o por el service se debe cambiar la ruta
if(file_exists("../models/Model.php"))
include_once '../models/Model.php';
else
require_once "./models/Model.php";

?>