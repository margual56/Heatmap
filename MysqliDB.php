<?php
class MysqliDB_Exception extends \Exception {}
class DBException extends \MysqliDB_Exception {}//Obsdoleta, eliminar todos sus lanzamientos en Sintax y dejarla de usar en las apps

class MysqliDB extends \mysqli implements Serializable {
	/**
	 * host de MySQL
	 * @var string
	 */
	protected $host='localhost';
	/**
	 * Usuario de acceso a MySQL
	 * @var string
	 */
	protected $user='root';
	/**
	 * Contraseña del usuario de acceso a MySQL
	 * @var string
	 */
	protected $pass='';
	/**
	 * Nombre del esquema de MySQL
	 * @var string
	 */
	protected $db='';
	/**
	 * Constructor: Conecta a MySQL y establece el charset a utf8
	 * @param string $host host de MySQL
	 * @param string $user usuario de acceso a MySQL
	 * @param string $pass contraseña del usuario de acceso a MySQL
	 * @param string $db nombbre de esquema de MySQL
	 * @throws MysqliDB_Exception si no puede conectar o establecer el charset a utf8
	 */
	public function __construct($host, $user, $pass, $db) {
		$this->host=$host;
		$this->user=$user;
		$this->pass=$pass;
		$this->db=$db;

		$this->reconnect();
	}

	/**
	 * Destructor: cierra la conexión a MySQL si está abierta
	 * @throws MysqliDB_Exception si no se puede cerrar la conexion
	 */
	public function __destruct() {
		try {
			$pingResult=false;
			$pingResult=parent::ping();
		} catch (\Exception $e) {
			$pingResult=false;
		}
		if ($pingResult) {
			if (!parent::close()) {
				throw new MysqliDB_Exception(mysqli_connect_error(), mysqli_connect_errno());
			}
		}
	}

	public function serialize() {
		return serialize([
			$this->host,
			$this->user,
			$this->pass,
			$this->db,
		]);
	}
	public function unserialize($serialized) {
		list(
			$this->host,
			$this->user,
			$this->pass,
			$this->db,
		) = unserialize($serialized);
		$this->reconnect();
	}

	public function GEThost() {return $this->host;}
	public function GETuser() {return $this->user;}
	public function GETpass() {return $this->pass;}
	public function GETdb() {return $this->db;}

	private function reconnect() {
		parent::init();


		/* Avisos y excepciones del controlar
		$controlador = new mysqli_driver();
		$controlador->report_mode = MYSQLI_REPORT_ALL;
		*/

		/*
		if (!parent::options(MYSQLI_INIT_COMMAND, 'SET AUTOCOMMIT = 0')) {
			throw new exception('Setting MYSQLI_INIT_COMMAND failed');
		}

		if (!parent::options(MYSQLI_OPT_CONNECT_TIMEOUT, 5)) {
			throw new exception('Setting MYSQLI_OPT_CONNECT_TIMEOUT failed');
		}
		*/
		if (!parent::real_connect($this->host, $this->user, $this->pass, $this->db)) {
			//throw new exception($this->connect_error, $this->connect_errno);
			//$connect_error falla hasta PHP 5.3.0, asi que usamos la siguiente
			throw new MysqliDB_Exception(mysqli_connect_error(), mysqli_connect_errno());
		}
		/* change character set to utf8 */
		if (!parent::set_charset("utf8")) {
			throw new MysqliDB_Exception(mysqli_connect_error(), mysqli_connect_errno());
		}
	}

	/**
	 * Ejecuta una consulta
	 * @param  string $query Consulta SQL a ejecutar
	 * @return mysqli_result instancia de mysqli_result
	 */
	public function query($query, $resultmode=NULL) {
		$localeActual=setlocale(LC_ALL, 0);
	 	setlocale(LC_ALL,'en_US.utf8');

		$result=parent::query($query);

		setlocale(LC_ALL,$localeActual);
		/*
		if(mysqli_error($this)){
			throw new exception(mysqli_error($this), mysqli_errno($this));
		}
		*/
		if($this->errno!=0) {
			$sql=(strlen($query)<1023)?$query:substr($query,0,1023)."[RESTO DE LA CONSULTA ELIMINADA]";
			throw new MysqliDB_Exception("SQL: ".$sql.". ".$this->error, $this->errno);
		}
		return $result;
	}
	/**
	 * Recupera un campo
	 * @param  string $query Consulta SQL a ejecutar
	 * @return string Primer campo del primer registro del resultado la consulta ejecutada
	 */
	public function get_var($query) {
		return $this->get_data($query,"var");
	}
	/**
	 * Recurepra una fila
	 * @param  string $query Consulta SQL a ejecutar
	 * @return object Primer registro del resultado la consulta ejecutada
	 */
	public function get_obj($query) {
		return $this->get_data($query,"obj");
	}
	/**
	 * alias de get_obj (obsoleto)
	 */
	public function get_row($query) {return $this->get_obj($query);}
	/**
	 * Recupera un array de campos
	 * @param  string $query Consulta SQL a ejecutar
	 * @return array Primer campo de cada registro del resultado de la consulta
	 */
	public function get_arrVars($query) {
		return $this->get_data($query,"arrVars");
	}
	/**
	 * Recupera un array de registros
	 * @param  string $query Consulta SQL a ejecutar
	 * @return array Contiene un array por cada registro del resultado la consulta ejecutada
	 */
	public function get_arrArrs($query) {
		return $this->get_data($query,"arrArrs");
	}
	/**
	 * alias de get_arrArrs (obsoleto)
	 */
	public function get_results($query) {return $this->get_arrArrs($query);}
	/**
	 * Recupera un objeto
	 * @param  string $query Consulta SQL a ejecutar
	 * @return array Contiene un objecto por cada registro del resultado la consulta ejecutada
	 */
	public function get_arrObjs($query) {
		return $this->get_data($query,"arrObjs");
	}
	/**
	 * Recupera el número de registros
	 * @param  string $query Consulta SQL a ejecutar
	 * @return integer Número de registros del resultado de la consulta ejecutada
	 */
	public function get_num_rows($query) {
		return $this->get_data($query,"num_rows");
	}
	/**
	 * Recupera datos en diversos formatos
	 * @param  string $query Consulta SQL a ejecutar
	 * @param  string $tipo  Tipo de resultado deseado (mysqli_result | var | obj | arrVars | arrArrs | arrObjs | num_rows | html_table)
	 * @return mixed mysqli_result | string | object | array | integer
	 */
	private function get_data($query,$tipo="mysqli_result") {
		$qResult=$this->query($query);
		switch ($tipo) {
			case "mysqli_result":
				$result=$qResult;
			case "var":
				$row=$qResult->fetch_array();
				$result=$row[0];
				break;
			case "obj":
				$result=$qResult->fetch_object();
				break;
			case "arrVars":
				$result=array();
				while ($row=$qResult->fetch_array(MYSQLI_NUM)) {
					array_push($result,$row[0]);
				}
				break;
			case "arrArrs":
				//$result=$qResult->fetch_all(MYSQLI_BOTH);//<- Según el manual esta disponible a partir de PHP 5.3.0 pero probé en dl333 con PHP 5.3.3 y undefined method mysqli_result::fetch_all(), quiza porque Available only with mysqlnd (nd=native driver)
				$result=array();
				while ($row=$qResult->fetch_array(MYSQLI_ASSOC)) {
					array_push($result,$row);
				}
				break;
			case "arrObjs":
				$result=array();
				while ($row=$qResult->fetch_object()) {
					array_push($result,$row);
				}
				break;
			case "num_rows":
				$result=$qResult->num_rows;
				break;
			case "html_table":
				$result=MysqliDB::mysqli_result_to_html_table($qResult);
				break;
		}
		$qResult->free();
		return $result;
	}

	/* nextId *****************************************************
	**************************************************************/
	/**
	 * Calcula el siguiente valor para incrementar el campo de la tabla pasados como parametros.
	 * Si existe la clase contador, se aopya en ella para generar el valor.
	 * @param  string $tabla tabla para la que se desea generar el valor
	 * @param  string $campo campo para el que se desea generar el valor
	 * @return integer Siguiente valor que se puede grabar en el campo
	 */
	public function nextId($tabla,$campo) {
		$maxCampo=$this->get_var ("SELECT max($campo) as maxCampo FROM $tabla");
		$max=$maxCampo+1;
		if (class_exists('contador')) {
			/*
			CREATE TABLE IF NOT EXISTS `contador` (
				`id` INT NOT NULL,
				`insert` TIMESTAMP NULL DEFAULT NULL,
				`update` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
				`tabla` VARCHAR(255) NOT NULL,
				`campo` VARCHAR(255) NOT NULL,
				`valor` INT NOT NULL DEFAULT 0,
			PRIMARY KEY (`id`),
			UNIQUE INDEX `tablaCampo` (`tabla` ASC, `campo` ASC))
			*/
			$sqlContador="SELECT * FROM contador WHERE tabla='".$tabla."' AND campo='".$campo."'";
			$dataContador=$this->get_obj($sqlContador);
			if ($dataContador) {
				$contador = new Contador($dataContador->id);
				if ($contador->GETvalor()>$maxCampo) {
					$max=($contador->GETvalor())+1;
				}
				$contador->SETvalor($max);
				$contador->grabar();
			}
		}
		return ($max);
	}

	/* mysqli_result_to_html_table ********************************
	**************************************************************/
	/**
	 * Convierte una instancia de mysqli_result en una tabla html
	 * @param  mysqli_result $mysqli_result instancia de mysqli_result
	 * @param  function $afterTrCallback funcion a llamar justo despues
	 * de cada apertura de tr de la tabla. Se le pasan dos parametros,
	 * número de fila (0 para la fila de encabezados) y un array con
	 * los datos de la fila (datos de la primera fila para el tr del
	 * encabezado)
	 * @return string Marcado html de la tabla
	 */
	public static function result_to_html_table ($mysqli_result,$afterTrCallback=NULL) {
		$result='';
		$result.='<table border="1" style="width:100%;">';
		$i=0;
		while ($columnInfo = $mysqli_result->fetch_array(MYSQLI_ASSOC)) {
			if ($i==0) {
				$result.='	<tr>';
				if (!is_null($afterTrCallback)) {$result.=$afterTrCallback($i,$columnInfo);}
				foreach ($columnInfo as $key => $value) {
					$result.='		<th>'.$key.'</th>';
				}
				$result.='	</tr>';
			}
			$i++;
			$result.='	<tr>';
			if (!is_null($afterTrCallback)) {$result.=$afterTrCallback($i,$columnInfo);}
			foreach ($columnInfo as $key => $value) {
				$result.='		<td>'.$value.'</td>';
			}
			$result.='	</tr>';
		}
		$result.='</table>';
		return $result;
	}

	/* insertArray ************************************************
	**************************************************************/
	/**
	 * Inserta el array pasado como parametro en la tabla pasada
	 * como parametro. Si la tabla no existe se crea, con todos los
	 * campos VARCHAR(255) NULL. Si el array contiene alguna clave
	 * para la que no exista campo, se realiza el ALTER correspondiente.
	 * @param  array $arr Array asociativo con los nombres y valores de los campos a insertar
	 * @param  string $nombreTabla Nombre de la tabla en la que insertar el aaray
	 */
	public function insertArray($arr,$nombreTabla) {
		$sqlShowTables='show tables LIKE "'.$nombreTabla.'"';
		$tableExists=($this->get_num_rows($sqlShowTables))?TRUE:FALSE;
		if (!$tableExists) {
			$sqlCreateTable='CREATE TABLE IF NOT EXISTS `'.$nombreTabla.'` (
				`id` INT NOT NULL,
				`insert` TIMESTAMP NULL DEFAULT NULL,
				`update` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,'.PHP_EOL;
			foreach ($arr as $name => $value) {
				$sqlCreateTable.='`'.$name.'` VARCHAR(255) NULL,'.PHP_EOL;
			}
			$sqlCreateTable.='`extraData` VARCHAR(255) NULL COMMENT "Datos nuevos que no llegaban cuando se creo la tabla o datos a mayores para depuracion",';
			$sqlCreateTable.='
				PRIMARY KEY (`id`))
				ENGINE = InnoDB
			';
			$this->query ($sqlCreateTable);
		}

		$sqlInsertInto='INSERT INTO `'.$nombreTabla.'` (';
		$sqlInsertInto.='`id`,';
		$sqlInsertInto.='`insert`,';
		$sqlInsertInto.='`update`,';
		$lastCheckedColumn='';
		foreach ($arr as $name => $value) {
			$sqlShowColumns='SHOW COLUMNS FROM `'.$nombreTabla.'` LIKE "'.$name.'"';
			$fieldExists=($this->get_num_rows($sqlShowColumns))?TRUE:FALSE;
			if (!$fieldExists) {
				//lo creamos
				$firstAfter=($lastCheckedColumn!='')?'AFTER `'.$lastCheckedColumn.'`':'FIRST';
				$sqlAlterTable='ALTER TABLE `'.$nombreTabla.'` ADD COLUMN `'.$name.'` VARCHAR(255) NULL '.$firstAfter;
				'`'.$name.'` VARCHAR(255) NULL,'.PHP_EOL;
				$this->query ($sqlAlterTable);
				$sqlShowColumns='SHOW COLUMNS FROM `'.$nombreTabla.'` LIKE "'.$name.'"';
				$fieldExists=($this->get_num_rows($sqlShowColumns))?TRUE:FALSE;
			}
			if ($fieldExists) {
				$sqlInsertInto.='`'.$name.'`,';
			}
			$lastCheckedColumn=$name;
		}

		$sqlInsertInto.='`extraData`) VALUES (';

		$sqlInsertInto.=''.$this->nextId($nombreTabla,'id').',';
		$sqlInsertInto.='"'.date("YmdHis").'",';
		$sqlInsertInto.='NULL,';
		$extraData='{';
		$extraData.='"getRemoteIPAddress()":"'.getRemoteIPAddress().'",';
		foreach ($arr as $name => $value) {
			$sqlShowColumns='SHOW COLUMNS FROM `'.$nombreTabla.'` LIKE "'.$name.'"';
			$fieldExists=($this->get_num_rows($sqlShowColumns))?TRUE:FALSE;
			if ($fieldExists) {
				$sqlInsertInto.='"'.$this->real_escape_string($value).'",';
			} else {
				$extraData.='"'.$name.'":"'.$value.'",';
			}
		}

		$extraData=substr($extraData,0,-1).'}';
		$sqlInsertInto=substr($sqlInsertInto,0,-1).',';
		$sqlInsertInto.='\''.$extraData.'\')';
		$this->query($sqlInsertInto);
	}

}

final class cDb extends MysqliDB {
	/**
	 * NULL o instancia de la propia clase, ya conectada a la BD
	 * @var NULL o instancia de self
	 */
	private static $singleton=NULL;
	/**
	 * Realiza conexión a la base de datos
	 * @param string $host host de MySQL
	 * @param string $user usuario de acceso a MySQL
	 * @param string $pass contraseña del usuario de acceso a MySQL
	 * @param string $db nombbre de esquema de MySQL
	 * @return object: instancia de self
	 */
	public static function conf($host, $user, $pass, $db) {
		if(self::$singleton instanceof self) {self::$singleton->close();}
		self::$singleton=NULL;
		return self::getInstance($host, $user, $pass, $db);
	}

	/**
	 * Realiza conexión a la base de datos mediante una key del array DBS
	 * @param string $arrKey Clave del array DBS que contiene los datos de conexión
	 * @return object: instancia de self
	 * @throws MysqliDB_Exception En caso de que la constante DBS no esté definida, o no contenga un array, ono exista clave arrKey o la clave arrKey no contenga a su vez otro array
	 */
	public static function confByKey($arrKey) {
		if (!defined('DBS')) {throw new MysqliDB_Exception("DBS no definida", 1);}
		$arrDbs=unserialize(DBS);
		if (!is_array($arrDbs)) {throw new MysqliDB_Exception("DBS no contiene un array", 1);}
		if (!isset($arrDbs[$arrKey])) {throw new MysqliDB_Exception("key [".$arrKey."] no isset", 1);}
		if (!is_array($arrDbs[$arrKey])) {throw new MysqliDB_Exception("DBS[".$arrKey."] no contiene un array", 1);}
		self::conf($arrDbs[$arrKey]['_DB_HOST_'],$arrDbs[$arrKey]['_DB_USER_'],$arrDbs[$arrKey]['_DB_PASSWD_'],$arrDbs[$arrKey]['_DB_NAME_']);
		return self::getInstance();
	}

	/**
	 * devuelve una referencia a la instancia conectada
	 * @return object: instancia de self
	 */
	public static function getInstance($host="localhost", $user="root", $pass="", $db="") {
		if(!self::$singleton instanceof self) {
			self::$singleton = new self($host, $user, $pass, $db);
		}
		return self::$singleton;
	}
	/**
	 * alias de getInstance
	 */
	public static function gI() {
		return self::getInstance();
	}
}
?>