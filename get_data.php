<?php

/* 
Provee un endpoint GET para obtener los registros de la tabla reportes,
modificados para poder regresar la URL de la imagen (ruta del servidor), 
en vez del nombre de la imagen
*/

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    die(json_encode(['error' => 'Método no permitido']));
}

$user = $_ENV['DB_USER'];
$password = $_ENV['DB_PASSWORD'];
$db = $_ENV['DB_NAME'];
$server_ip = $_ENV['SERVER_IP'];

try {
    $conn = new mysqli('localhost', $user, $password, $db);

    $query = "SELECT titulo, descripcion, nombre_imagen, latitud, longitud, creado_en FROM reportes";
    $result = $conn->query($query);

    if (!$result) {
        throw new Exception("Error en la consulta: " . $conn->error);
    }

    $reportes = [];
    $uploads_url = "http://$server_ip/uploads/";

    while ($row = $result->fetch_assoc()) {
        // Construir el objeto reporte con la URL completa de la imagen

        $reporte = [
            "titulo" => $row['titulo'],
            "descripcion" => $row['descripcion'],
            "ruta_imagen" => $uploads_url . $row['nombre_imagen'],
            "latitud" => $row['latitud'],
            "longitud" => $row['longitud'],
            "creado_en" => $row['creado_en']
        ];

        $reportes[] = $reporte;
    }

    echo json_encode([
        'exito' => true,
        'data' => $reportes
    ]);

    $result->free();
    $conn->close();

    return $result;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'exito' => false,
        'error' => 'Error al obtener los reportes',
        'detalles' => $e->getMessage()
    ]);

    error_log("Error al obtener registros: " . $e->getMessage());
}
