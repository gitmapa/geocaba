# GEOCABA
Normalización de datos geográficos de la API (vieja) de CABA

## Funcionamiento
Funciona en local del siguiente modo

- Se requiere una base de datos postgres (con extensión postgis) que aloje los datos
- Se requiere que la pc tenga conexión con el servidor (local o remoto)
- Se requiere XAMPP o similar instalado en la PC
- Se requiere alojar los archivos en la carpetra htdocs
- Se requiere modificar el archivo conn.php para generar la conexión
- Se requiere modificar los campos de sxcripts.php con los nombres de campos de la base de datos creada a tal fin
- Se requiere comentar/descomentar los llamados a las funciones en index.php
