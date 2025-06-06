<?php

/* 
API que provee un endpoint POST para recibir los siguientes datos de un reporte:
Título, descripción, imagen, latitud y longitud 

Estos datos se insertarán en una tabla reportes de una base de datos MySQL
*/

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(crearRespuesta(false, 'Method not allowed')));
}

// Obtener datos de la petición
$titulo = $_POST['titulo'] ?? null;
$descripcion = $_POST['descripcion'] ?? null;
$latitud = floatval($_POST['latitud'] ?? 0);
$longitud = floatval($_POST['longitud'] ?? 0);

// Validar campos
if (empty($titulo) || empty($descripcion) || $latitud === 0 || $longitud === 0) {
    http_response_code(400);
    die(json_encode(crearRespuesta(false, 'Some fields are missing')));
}

// Procesar la imagen enviada
// Si no se recibió un archivo de imagen con el nombre de variable "imagen"
if (!isset($_FILES['imagen'])) {
    http_response_code(400);
    die(json_encode(crearRespuesta(false, 'The field imagen was not received')));
}

$imagen = $_FILES['imagen'];

if ($imagen['error'] !== UPLOAD_ERR_OK) {
    // echo $imagen['error'];
    die(json_encode(crearRespuesta(false, 'An error ocurred uploading the image')));
}

$extensiones_permitidas = ['jpg', 'jpeg', 'png'];
$permitidos_mime_types = ['image/jpeg', 'image/png'];

// Obtenemos la extensión y tipo mime de la imagen
$extension = strtolower(pathinfo($imagen['name'], PATHINFO_EXTENSION));
$mime_type = mime_content_type($imagen['tmp_name']);

if (!in_array($extension, $extensiones_permitidas) || !in_array($mime_type, $permitidos_mime_types)) {
    die(json_encode(crearRespuesta(false, 'The image must be a valid jpg/png file')));
}

$nombre_imagen_basename = basename($imagen['name']);
$ruta_destino = 'uploads/' . $nombre_imagen_basename;

if (!move_uploaded_file($imagen['tmp_name'], $ruta_destino)) {
    http_response_code(500);
    die(json_encode(crearRespuesta(false, 'An error ocurred saving the image')));
}

// Guardar en la tabla reportes
$resultado = insertar_registro_reporte($titulo, $descripcion, $nombre_imagen_basename, $latitud, $longitud);

if ($resultado) {
    echo json_encode(crearRespuesta(true, 'Data stored in the DB succesfully'));
} else {
    http_response_code(500);
    echo json_encode(crearRespuesta(false, 'An error ocurred storing the data in the DB'));
}

function crearRespuesta(bool $exito, string $mensaje): array {
    return [
        'success' => $exito,
        'message' => $mensaje
    ];
}

function insertar_registro_reporte($titulo, $descripcion, $nombre_imagen, $latitud, $longitud): bool
{
    $user = $_ENV['DB_USER'];
    $password = $_ENV['DB_PASSWORD'];
    $db = $_ENV['DB_NAME'];

    try {
        $conn = new mysqli('localhost', $user, $password, $db);

        $stmt = $conn->prepare("
            INSERT INTO reportes (
                titulo, 
                descripcion, 
                nombre_imagen, 
                latitud, 
                longitud
            ) VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            // Tipos: s=string, d=double
            'sssdd',
            $titulo,
            $descripcion,
            $nombre_imagen,
            $latitud,
            $longitud
        );

        $result = $stmt->execute();

        $stmt->close();
        $conn->close();

        return $result;
    } catch (Exception $e) {
        error_log("Error en insertar_registro_reporte: " . $e->getMessage());
        return false;
    }
}
