<?php
// detalles de la conexion
$conn_string = "host=localhost port=5432 dbname=sig user=postgres password=Qatarairways";
// establecemos una conexion con el servidor postgresSQL
$dbconn = pg_connect($conn_string);
// revisamos el estado de la conexion en caso de errores.
if(!$dbconn) 
    {
        echo "Error: No se ha podido conectar a la base de datos";
    }
else
    {
    };
?>

