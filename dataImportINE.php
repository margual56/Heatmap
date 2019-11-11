<?php
die('NO EJECUTAR');
try {
	set_time_limit ( 3600 );

	define ('DBS', serialize(array(
		'team10' => array(//Debe valer lo mismo que _DB_NAME_
			'_DB_HOST_' => 'osnola.es',
			'_DB_USER_' => 'team10imn',
			'_DB_PASSWD_' => 'kN7_g8i7',
			'_DB_NAME_' => 'team10',
		),
	)));

	require ('./MysqliDB.php');
	require ('./zMisc.php');

	//header('Content-Type: text/html; charset=ISO-8859-1');
	header('Content-Type: text/html; charset=utf-8');

	/*
	$rsl=$db->query('SHOW DATABASES');
	while ($data=$rsl->fetch_object()) {
		var_dump($data);
	}
	die();
	*/

	$filesDir='./INEdata'.'/';
	$files = glob($filesDir.'*.csv');
	$formats=array(
		(object) array(
			"lineasEncabezado" => 2,
			"campos" => array("CPRO","CMUN","NOMMU","TOTAL","VARONES","MUJERES")
		),
		(object) array(
			"lineasEncabezado" => 1,
			"campos" => array("CPRO","NOMPRO","CMUN","NOMMU","TOTAL","VARONES","MUJERES")
		),
		(object) array(
			"lineasEncabezado" => 1,
			"campos" => array("comunidad","provincia","poblacion","lat","lng","alt","hab","H","M")
		)
	);


	$db=cDb::confByKey('team10');
	$sl="\n";	echo "<pre>";
	foreach($files as $file) {
		echo $file.$sl;
		$anio=substr($file, 19,4);
		$tabla='datosINE';
		//echo $anio;
		$fila = 1;
		if ($anio<2000) {$formatoDeAnio=0;} else {$formatoDeAnio=1;}
		if (($gestor = fopen($file, "r")) !== FALSE) {
			//echo '<pre style="overflow:auto; height:100px;">';
			while (($datos = fgetcsv($gestor, 1000, ";")) !== FALSE) {
				$numero = count($datos);
				$fila++;
				$arrayToInsert=array();
				for ($c=0; $c < $numero; $c++) {
					$arrayToInsert[$formats[$formatoDeAnio]->campos[$c]]=$datos[$c];
				}
				/*
				$geoQuery=
				$key='78f91c039669443ba944da97cb43c411';
				$urlGeodata='https://api.opencagedata.com/geocode/v1/json?q='.$geoQuery.'&key='.$key.'&language=es&pretty=1'
				$json = file_get_contents();
				$obj = json_decode($json);
				*/
				
				
				$arrayToInsert['anio']=$anio;
				$db->insertArray($arrayToInsert,$tabla);

				//echo trim(preg_replace('/\s+/', ' ', ob_var_dump($arrayToInsert))).$sl;

				/*
				$sql='INSERT INTO poblacion (cpro,cnum,nombre,total,varones,mujeres) VALUES (';
				for ($c=0; $c < $numero; $c++) {
					$sql.='"'.$datos[$c].'",';
				}
				$sql=substr($sql, 0,-1);
				$sql.=')';
				echo $sql.$sl;
				*/

				//if ($fila>10) {
				//	break;
				//}
			}
			//echo "</pre>";
			fclose($gestor);
		} else {
			echo "no se pudo abrir ".$file.$sl;
		}
		//break;
	}
} catch (Exception $e) {
	var_dump($e);
}
?>