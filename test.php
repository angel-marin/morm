<?php
require_once "morm/config.php";

$res=$morm_query->create()->from('tracks')->execute();

foreach($res as $r){
	echo $r->name.' '.$r->duration.'<br>';
}
?>
