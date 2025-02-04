<?php

//////////////////////////////////////////
// Función para normalizar calle altura //
//////////////////////////////////////////

function normalizar_calle_altura()
{
    include 'conn.php';    
    $q1 = "SELECT newid, dir_calle, dir_numero FROM public.cuis0125 WHERE geolocalizable IS true";
    $res1 = pg_query($dbconn, $q1) or die('Error: ' . pg_last_error());
    while($row1 = pg_fetch_array($res1,NULL,PGSQL_ASSOC) )
        {
            $elaidi = $row1['newid'];
            // API Procesos Geográficos https://ws.usig.buenosaires.gob.ar/rest/normalizar_direcciones?calle=julio%20roca&altura=782&desambiguar=1    
            $peticion1 = str_replace(" ","%20",'https://ws.usig.buenosaires.gob.ar/rest/normalizar_direcciones?'
                    . 'calle=' . $row1['dir_calle']
                    . '&altura=' . $row1['dir_numero']
                    . '&desambiguar=1');
            $json1 = file_get_contents($peticion1, true);
            $json1_output = json_decode($json1);
            // si el json en TipoResultado trae algo distinto a DireccionNormalizada (error) pongo estado en 1 y salgo del if
            if($json1_output->TipoResultado != 'DireccionNormalizada')
                {
                    $q2 = "UPDATE public.cuis0125 SET estado = 21 WHERE newid = " . $elaidi;
                    $res2 = pg_query($dbconn,$q2);                
                }
                else
                {
                    $cn = str_replace("'","`",$json1_output->DireccionesCalleAltura->direcciones[0]->Calle);
                    $q3 = "UPDATE public.cuis0125 SET
                    cod_calle = " . $json1_output->DireccionesCalleAltura->direcciones[0]->CodigoCalle 
                    . ", calle_norm = '" . $cn
                    . "', altura_norm = " . $json1_output->DireccionesCalleAltura->direcciones[0]->Altura 
                    . ", estado = 22 " 
                    . " WHERE newid = " . $elaidi;
                    $res3 = pg_query($dbconn,$q3);
                }; // if
        }; // while
}; // funcion

/////////////////////////////////////
// Función para traer Datos Útiles //
/////////////////////////////////////

function traer_datos_utiles()
{
    include 'conn.php';
    $q4 = "SELECT newid, calle_norm, altura_norm FROM public.cuis0125 WHERE estado = 22";
    $res4 = pg_query($dbconn, $q4);
    while($row4 = pg_fetch_array($res4,NULL,PGSQL_ASSOC) )
        {
            $elaidi = $row4['newid'];
            // API Datos Utiles https://ws.usig.buenosaires.gob.ar/datos_utiles?calle=peru&altura=782
            $peticion4 = str_replace(" ","%20",'https://ws.usig.buenosaires.gob.ar/datos_utiles?'
                    . 'calle=' . $row4['calle_norm'] 
                    . '&altura=' . $row4['altura_norm']);
            $json4 = file_get_contents($peticion4, true);
            $json4_output = json_decode($json4);
            if(empty($json4_output))
            {
                $q6 = "UPDATE public.cuis0125 SET estado = 23 WHERE newid = " . $elaidi;
                $res6 = pg_query($dbconn,$q6);
            }
            else
            {
                // paso los atributos a variables para que no pinchen los que vienen vacíos - Si son integer, ponerle 9999 y no null
                if (empty($json4_output->comuna)){$comuna = null;}else{$comuna = $json4_output->comuna;};
                if (empty($json4_output->barrio)){$barrio = null;}else{$barrio = $json4_output->barrio;};
                if (empty($json4_output->comisaria)){$comisaria = null;}else{$comisaria = $json4_output->comisaria;};
                if (empty($json4_output->area_hospitalaria)){$area_hospitalaria = null;}else{$area_hospitalaria = $json4_output->area_hospitalaria;};
                if (empty($json4_output->region_sanitaria)){$region_sanitaria = null;}else{$region_sanitaria = $json4_output->region_sanitaria;};
                if (empty($json4_output->distrito_escolar)){$distrito_escolar = null;}else{$distrito_escolar = $json4_output->distrito_escolar;};
                if (empty($json4_output->codigo_postal)){$codigo_postal = 9999;}else{$codigo_postal = $json4_output->codigo_postal;};
                if (empty($json4_output->codigo_postal_argentino)){$codigo_postal_argentino = null;}else{$codigo_postal_argentino = $json4_output->codigo_postal_argentino;};
                $q5 = "UPDATE public.cuis0125 SET
                comuna_norm = '" . $comuna
                . "', barrio_norm = '" . $barrio
                . "', comisaria_norm = '" . $comisaria
                . "', area_norm = '" . $area_hospitalaria
                . "', region_norm = '" . $region_sanitaria
                . "', de_norm = '" . $distrito_escolar
                . "', cp_norm = '" . $codigo_postal
                . "', cpar_norm = '" . $codigo_postal_argentino
                . "', estado = 24 "
                . "WHERE newid = " . $elaidi;
                $res5 = pg_query($dbconn, $q5);
            }; // if
        }; // while
}; // funcion

//////////////////////////////////////////
// Función para traer datos de catastro //
//////////////////////////////////////////

function traer_datos_de_catastro()
{
    include 'conn.php';
    $q7 = "SELECT newid, cod_calle, altura_norm FROM sig.public.direcciones_norm WHERE cod_calle is not null and parcela is null";
    $res7 = pg_query($dbconn, $q7);
    while($row7 = pg_fetch_array($res7,NULL,PGSQL_ASSOC) )
        {
            $elaidi = $row7['newid'];
            // API catastro https://datosabiertos-catastro-apis.buenosaires.gob.ar/catastro/parcela/?codigo_calle=17071&altura=782
            $peticion7 = 'https://datosabiertos-catastro-apis.buenosaires.gob.ar/catastro/parcela/?'
                    . 'codigo_calle=' . $row7['cod_calle']
                    . '&altura=' . $row7['altura_norm']
                    . '&aprox';
            $json7 = file_get_contents($peticion7, False);
            $json7_output = json_decode($json7);
            if(empty($json7_output))
                {
                    $q9 = "UPDATE sig.public.direcciones_norm SET estado = 35 WHERE newid = " . $elaidi;
                    $res9 = pg_query($dbconn,$q9);
                }
                else
                {
                    // paso los atributos a variables para que no pinchen los que vienen vacíos
                if (empty($json7_output->smp)){$smp = null;}else{$smp = $json7_output->smp;};
                if (empty($json7_output->seccion)){$seccion = null;}else{$seccion = $json7_output->seccion;};
                if (empty($json7_output->manzana)){$manzana = null;}else{$manzana = $json7_output->manzana;};
                if (empty($json7_output->parcela)){$parcela = null;}else{$parcela = $json7_output->parcela;};
                if (empty($json7_output->centroide)){$centx = null;}else{$centx = $json7_output->centroide[0];};
                if (empty($json7_output->centroide)){$centy = null;}else{$centy = $json7_output->centroide[1];};
                // if (empty($json7_output->superficie_total)){$superficie_total = 'null';}else{$superficie_total = $json7_output->superficie_total;};
                // if (empty($json7_output->superficie_cubierta)){$superficie_cubierta = 'null';}else{$superficie_cubierta = $json7_output->superficie_cubierta;};
                // if (empty($json7_output->frente)){$frente = 'null';}else{$frente = $json7_output->frente;};
                // if (empty($json7_output->fondo)){$fondo = 'null';}else{$fondo = $json7_output->fondo;};
                // if (empty($json7_output->pisos_bajo_rasante)){$pisos_bajo_rasante = 'null';}else{$pisos_bajo_rasante = $json7_output->pisos_bajo_rasante;};
                // if (empty($json7_output->pisos_sobre_rasante)){$pisos_sobre_rasante = 'null';}else{$pisos_sobre_rasante = $json7_output->pisos_sobre_rasante;};
                // if (empty($json7_output->fuente)){$fuente = null;}else{$fuente = $json7_output->fuente;};
                //if (empty($json7_output->cantidad_puertas)){$cantidad_puertas = 'null';}else{$cantidad_puertas = $json7_output->cantidad_puertas;};
                    $q8 = "UPDATE sig.public.direcciones_norm SET 
                    seccion = '" . $seccion
                    . "', manzana = '" . $manzana
                    . "', parcela = '" . $parcela
                    . "', centx = '" . $centx
                    . "', centy = '" . $centy
                    // . "', superficie_total = " . $superficie_total
                    // . ", superficie_cubierta = " . $superficie_cubierta
                    // . ", frente = " . $frente
                    // . ", fondo = " . $fondo
                    // . ", pisos_bajo_rasante = " . $pisos_bajo_rasante
                    // . ", pisos_sobre_rasante = " . $pisos_sobre_rasante
                    // . ", fuente_catastro = '" . $fuente
                    // . "', cantidad_puertas = " . $cantidad_puertas
                    . "', estado = 36 WHERE newid = " . $elaidi;
                    $res8 = pg_query($dbconn, $q8);        
                }; // if
        }; // while
}; //funcion

//////////////////////////////////////////////////
// Función para traer pares de coordenadas GKBA //
//////////////////////////////////////////////////

function traer_coordenadas_gkba()
{
    include 'conn.php';
    $q10 = "SELECT newid, cod_calle, altura_norm FROM public.cuis0125 WHERE estado = 24";
    $res10 = pg_query($dbconn, $q10);
    while($row10 = pg_fetch_array($res10,NULL,PGSQL_ASSOC) )
        {
            $elaidi = $row10['newid'];
            // API usig https://ws.usig.buenosaires.gob.ar/geocoder/2.2/geocoding?cod_calle=17071&altura=782&metodo=puertas
            $peticion10 = 'https://ws.usig.buenosaires.gob.ar/geocoder/2.2/geocoding?'
                    . 'cod_calle=' . $row10['cod_calle']
                    . '&altura=' . $row10['altura_norm']
                    . '&metodo=puertas';
            // como devuelve un json mal armado, lo masajeo
            $json10 = file_get_contents($peticion10, true);
            $sin1 = str_replace("(","",$json10);
            $sin2 = str_replace(")","",$sin1);
            $json10_output = json_decode($sin2);
            if(empty($json10_output))
                {
                    $q11= "UPDATE public.cuis0125 SET estado = 27 WHERE newid = " . $elaidi;
                    $res11 = pg_query($dbconn, $q11);
                }
                else
                {
                    $q12 = "UPDATE public.cuis0125 SET 
                    x_gkba = " . $json10_output->x
                    . ", y_gkba = " . $json10_output->y
                    . ", estado = 28 WHERE newid = " . $elaidi;
                    $res12 = pg_query($dbconn, $q12);        
                }; // if
        }; // while
}; //funcion

///////////////////////////////////////////////////
// Función para traer pares de coordenadas WGS84 //
///////////////////////////////////////////////////

function traer_coordenadas_wgs84()
{
    include 'conn.php';
    $q13 = "SELECT newid, x_gkba, y_gkba FROM public.cuis0125 WHERE estado = 28";
    $res13 = pg_query($dbconn, $q13);
    while($row13 = pg_fetch_array($res13,NULL,PGSQL_ASSOC) )
        {
            $elaidi = $row13['newid'];
            // API usig https://ws.usig.buenosaires.gob.ar/rest/convertir_coordenadas?x=108150.992445&y=101357.282955&output=lonlat
            $peticion13 = 'https://ws.usig.buenosaires.gob.ar/rest/convertir_coordenadas?'
                    . 'x=' . $row13['x_gkba']
                    . '&y=' . $row13['y_gkba']
                    . '&output=lonlat';
            $json13 = file_get_contents($peticion13, False);
            $json13_output = json_decode($json13);
            if(empty($json13_output))
                {
                    $q14= "UPDATE public.cuis0125 SET estado = 29 WHERE newid = " . $elaidi;
                    $res14 = pg_query($dbconn,$q14);
                }
                else
                {
                    $q15 = "UPDATE public.cuis0125 SET 
                    x_wgs84 = " . $json13_output->resultado->x
                    . ", y_wgs84 = " . $json13_output->resultado->y
                    . ", estado = 30 WHERE newid = " . $elaidi;
                    $res15 = pg_query($dbconn, $q15);        
                }; // if
        }; // while
}; //funcion

////////////////////////////////////////
// Función para crear el geom en 4326 //
////////////////////////////////////////

function crear_geom4326()
{
    include 'conn.php';
    // hago el geom con postgis
    $q20 = 'UPDATE sig.mapa.diruni set geom4326 = ST_SetSRID(ST_MakePoint(x_wgs84, y_wgs84),4326) where x_wgs84 is not null and y_wgs84 is not null';
    $res20 = pg_query($dbconn, $q20);
}


////////////////////////////////////////////////////
// Función para traer datos del reverse geocoding //
////////////////////////////////////////////////////

// https://datosabiertos-usig-apis.buenosaires.gob.ar/geocoder/2.2/reversegeocoding?x=108749.43809645309&y=100680.7324121159

/*
(
    {
    "parcela":"04-047-006",
    "puerta":"PASEO COLON AV. 1318",
    "puerta_x":"108735.390742",
    "puerta_y":"100683.965816",
    "calle_alturas":"PASEO COLON AV. 1301-1400",
    "esquina":"PASEO COLON AV. y COCHABAMBA",
    "metros_a_esquina":"33.9",
    "altura_par":"PASEO COLON AV. 1326",
    "altura_impar":"PASEO COLON AV. 1325"
    }
)
*/

?>