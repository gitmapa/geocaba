<?php 

$peticion7 = 'https://datosabiertos-catastro-apis.buenosaires.gob.ar/catastro/parcela/?codigo_calle=17071&altura=782';
$json7 = file_get_contents($peticion7, False);
$json7_output = json_decode($json7);
echo "smp: " . $json7_output->smp;
echo "<br>";
echo "centroide: " . $json7_output->centroide[0];
echo "<br>";
echo "centroide: " . $json7_output->centroide[1];