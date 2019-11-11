<?php

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

	$obj=new \stdClass();
	$obj->type="FeatureCollection";
	$obj->features=array();

	$db=cDb::confByKey('team10');

	//$sql="SELECT * FROM poblacion p INNER JOIN location l ON p.id=l.idPoblacion";
	//var_dump($_GET);
	$query=(isset($_GET['query']))?'WHERE '.$_GET['query']:'';
	$where='';
	foreach ($_GET as $key => $value) {
		if ($where=='') {$where='WHERE ';}
		$where.=$key."='".$db->real_escape_string($value)."' AND ";
	}
	$where=substr($where,0, -4);
	//$sql="SELECT * FROM poblacion p ".$where;
	$sql="SELECT comunidad,provincia,poblacion,TOTAL,VARONES,MUJERES,anio,lng,lat FROM poblacion p INNER JOIN datosINE d ON p.poblacion=d.NOMMU ".$where;
	//echo $sql;
	$rsl=$db->query($sql);

	$i=0;
	while ($data=$rsl->fetch_object()) {
		$obj->features[$i]=new stdClass();
		$obj->features[$i]->type="Feature";
		$obj->features[$i]->properties=new stdClass();
		$obj->features[$i]->properties->comunidad=$data->comunidad;
		$obj->features[$i]->properties->provincia=$data->provincia;
		$obj->features[$i]->properties->poblacion=$data->poblacion;
		/*
		$obj->features[$i]->properties->mag=$data->hab;
		$obj->features[$i]->properties->habitantes=$data->hab;
		$obj->features[$i]->properties->hombres=$data->H;
		$obj->features[$i]->properties->mujeres=$data->M;
		*/
		$obj->features[$i]->properties->mag=$data->TOTAL;
		$obj->features[$i]->properties->habitantes=$data->TOTAL;
		$obj->features[$i]->properties->hombres=$data->VARONES;
		$obj->features[$i]->properties->mujeres=$data->MUJERES;
		$obj->features[$i]->properties->anio=$data->anio;

		$obj->features[$i]->geometry=new stdClass();
		$obj->features[$i]->geometry->type="point";
		$obj->features[$i]->geometry->coordinates=array();
		$obj->features[$i]->geometry->coordinates[0]=$data->lng;
		$obj->features[$i]->geometry->coordinates[1]=$data->lat;
		$i++;
	}


	echo json_encode($obj,JSON_UNESCAPED_UNICODE);

/*
{
	"type": "FeatureCollection",
	"features": [
		{
			"type": "Feature",
			"properties": {
				"mag": 0.6
			},
			"geometry": {
				"type": "Point",
				"coordinates": [
					-116.8805,
					33.082
				]
			}
		}
	]
}
*/
?>