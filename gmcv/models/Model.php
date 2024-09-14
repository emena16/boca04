<?php

$ruta = "../../../../";
if(file_exists($ruta."sys/precarga_mysqli.php"))
	include $ruta."sys/precarga_mysqli.php";
else
	require_once '../../../sys/precarga_mysqli.php';

class db {
	
	public static function query($sql) {
		global $conexion_bd;
		$rs = mysqli_query($conexion_bd, $sql);
		if ($rs === false) throw new Exception(mysqli_error($conexion_bd));
		return $rs;
	}
	public static function fetch_array($rs) { return mysqli_fetch_array($rs, MYSQLI_ASSOC); }
	public static function fetch_assoc($rs) { return mysqli_fetch_assoc($rs); }
	public static function fetch_row($rs) { return mysqli_fetch_row($rs); }
	public static function insert_id() { global $conexion_bd; return mysqli_insert_id($conexion_bd); }
	public static function affected_rows() { global $conexion_bd; return mysqli_affected_rows($conexion_bd); }
	public static function real_escape_string($string) { global $conexion_bd; return mysqli_real_escape_string($conexion_bd, $string);}
	public static function num_rows($rs) { return mysqli_num_rows($rs); }
	public static function select_db($db) { global $conexion_bd; return mysqli_select_db($conexion_bd, $db); }
	public static function fetch_all($rs) { return mysqli_fetch_all($rs, MYSQLI_ASSOC); }
	public static function errno() { global $conexion_bd; return mysqli_errno($conexion_bd); }
	public static function error() { global $conexion_bd; return mysqli_errno($conexion_bd) . " - " . mysqli_error($conexion_bd); }
}
	

// class db {

// 	// public static function query($sql) {
// 	// 	$rs = mysqli_query($conexion_bd,$sql);
//     // 	if($rs === false) throw new Exception(mysql_error());
//     // 	return $rs;
// 	// }
// 	// public static function fetch_array($rs) { return mysqli_fetch_array($rs); }
// 	// public static function fetch_assoc($rs) { return mysqli_fetch_assoc($rs); }
// 	// public static function fetch_row($rs)   { return mysqli_fetch_row($rs); }
// 	// public static function insert_id()   	{ return mysqli_insert_id(db::query("SELECT LAST_INSERT_ID()")); }
// 	// public static function affected_rows() 	{ return mysqli_affected_rows(); }
// 	// public static function real_escape_string($string) { return mysqli_real_escape_string($conexion_bd,$string); }
// 	// public static function num_rows($rs)    { return mysqli_num_rows($rs); }
// 	// public static function select_db($db)   { return mysqli_select_db($conexion_bd, $db); }
// 	// public static function errno()          { return mysqli_errno(); }
// 	// public static function error()          { return mysqli_errno()." - ".mysqli_error(); }
// }

?>	