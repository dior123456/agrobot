<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ==================== CONFIGURACIÓN ====================

define('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', ''); // <--- COLOCA TU API KEY AQUÍ SI TIENES
define('OPENAI_API_URL', 'https://api.openai.com/v1/chat/completions');

require_once __DIR__ . '/../dao/Database.php';

$database = new Database();
$db = $database->getConnection();
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

function sendResponse($success, $data = null, $message = null, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    exit;
}

// ==================== LOGIN ====================
if ($action === 'login' && $method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    if (!isset($data['nombre']) || !isset($data['codigo'])) {
        sendResponse(false, null, 'Nombre y código requeridos', 400);
    }
    
    $stmt = $db->prepare("SELECT id, nombre, codigo, rol, estado FROM usuarios WHERE nombre = :nombre AND codigo = :codigo");
    $stmt->execute([':nombre' => $data['nombre'], ':codigo' => $data['codigo']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && $user['estado'] === 'activo') {
        $update = $db->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?");
        $update->execute([$user['id']]);
        sendResponse(true, $user, 'Login exitoso');
    } else {
        sendResponse(false, null, 'Credenciales incorrectas o usuario inactivo');
    }
}

// ==================== REGISTRO ====================
if ($action === 'register' && $method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    if (!isset($data['nombre']) || !isset($data['codigo'])) {
        sendResponse(false, null, 'Nombre y código requeridos', 400);
    }
    
    // Verificar si ya existe
    $check = $db->prepare("SELECT id FROM usuarios WHERE codigo = :codigo");
    $check->execute([':codigo' => $data['codigo']]);
    if ($check->rowCount() > 0) {
        sendResponse(false, null, 'El código ya está registrado');
    }
    
    $stmt = $db->prepare("INSERT INTO usuarios (nombre, codigo, rol) VALUES (:nombre, :codigo, 'user')");
    if ($stmt->execute([':nombre' => $data['nombre'], ':codigo' => $data['codigo']])) {
        sendResponse(true, null, 'Registro exitoso. Ahora puedes iniciar sesión.');
    } else {
        sendResponse(false, null, 'Error al registrar usuario');
    }
}

// ==================== CHAT (VERSIÓN CORREGIDA) ====================
if ($action === 'chat' && $method === 'POST') {
    // Obtener datos de entrada
    $inputJSON = file_get_contents("php://input");
    $data = json_decode($inputJSON, true);
    
    // Log para debugging
    error_log("Chat request recibida: " . $inputJSON);
    
    if (!$data) {
        sendResponse(false, null, 'Datos inválidos: ' . json_last_error_msg(), 400);
    }
    
    $mensaje = $data['mensaje'] ?? '';
    $usuario_id = $data['usuario_id'] ?? null;
    $usuario_nombre = $data['usuario_nombre'] ?? 'Anónimo';
    $idioma = $data['idioma'] ?? 'español';
    $imagen_url = $data['imagen_url'] ?? null;
    $texto_adjunto = $data['texto_adjunto'] ?? null;
    
    if (empty($mensaje)) {
        sendResponse(false, null, 'Mensaje requerido', 400);
    }
    
    // BUSCAR EN RESPUESTAS LOCALES
    $respuesta = null;
    try {
        $stmt = $db->prepare("SELECT palabra_clave, respuesta FROM respuestas WHERE :mensaje LIKE CONCAT('%', palabra_clave, '%') LIMIT 1");
        $stmt->execute([':mensaje' => $mensaje]);
        $local = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($local) {
            $respuesta = $local['respuesta'];
            error_log("Respuesta local encontrada para: " . $local['palabra_clave']);
        }
    } catch (PDOException $e) {
        error_log("Error en búsqueda local: " . $e->getMessage());
    }
    
    // SI NO HAY RESPUESTA LOCAL, USAR OPENAI
    if (!$respuesta && !empty(OPENAI_API_KEY) && OPENAI_API_KEY !== '') {
        try {
            $system_prompt = "Eres AgroBot, un asistente agrícola experto para pequeños agricultores de Guinea Ecuatorial. Hablas $idioma, y también puedes responder en fang y bubi. Ayudas con plagas, enfermedades, rendimiento y cultivos locales como cacao, café, yuca, plátano. Responde de forma clara, práctica y amigable.";
            
            $full_message = $mensaje;
            if ($texto_adjunto) {
                $full_message .= "\n\nTexto adicional: " . $texto_adjunto;
            }
            if ($imagen_url) {
                $full_message .= "\n\n[IMAGEN ADJUNTA] El usuario ha enviado una imagen de una planta. Analiza posibles signos de plagas o enfermedades.";
            }
            
            $post_data = [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => $system_prompt],
                    ['role' => 'user', 'content' => $full_message]
                ],
                'temperature' => 0.7,
                'max_tokens' => 500
            ];
            
            $ch = curl_init(OPENAI_API_URL);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . OPENAI_API_KEY
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);
            
            if ($http_code === 200 && !$curl_error) {
                $result = json_decode($response, true);
                $respuesta = $result['choices'][0]['message']['content'] ?? null;
                error_log("OpenAI respuesta obtenida correctamente");
            } else {
                error_log("OpenAI error - HTTP: $http_code, Error: $curl_error");
            }
        } catch (Exception $e) {
            error_log("Excepción OpenAI: " . $e->getMessage());
        }
    }
    
    // RESPUESTA POR DEFECTO SI TODO FALLA
    if (!$respuesta) {
        $respuestas_defecto = [
            "Gracias por tu consulta. Para ayudarte mejor, ¿puedes darme más detalles sobre el cultivo o el problema específico que observas?",
            " Soy AgroBot, tu asistente agrícola. ¿Me puedes decir si tu consulta es sobre cacao, café, yuca, plátano u otro cultivo?",
            " Lo siento, aún no tengo información específica sobre eso. Te recomiendo consultar con un técnico agrícola local o visitar la oficina del Ministerio de Agricultura más cercana.",
            " Estoy aprendiendo sobre agricultura ecuatoguineana cada día. ¿Puedes reformular tu pregunta o darme más detalles?",
            " Para plagas en cacao, prueba con extracto de nim. Para enfermedades como moniliasis, retira los frutos infectados.",
            " La yuca se cosecha entre 8-12 meses. El rendimiento mejora con suelo bien drenado y rotación de cultivos.",
            " El café arábica crece mejor en altitudes de 800-1200m. La poda después de cosecha es importante."
        ];
        $respuesta = $respuestas_defecto[array_rand($respuestas_defecto)];
    }
    
    // Añadir información de imagen si existe
    if ($imagen_url) {
        $respuesta .= "\n\n He recibido tu imagen. Si observas manchas, insectos o deformaciones en las hojas, puedo ayudarte a identificarlos. ¿Puedes describir qué ves en la imagen?";
    }
    
    // GUARDAR CONSULTA EN BASE DE DATOS
    try {
        $stmt = $db->prepare("INSERT INTO consultas (usuario_id, usuario_nombre, consulta, respuesta, imagen_url, texto_adjunto, idioma, fecha) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$usuario_id, $usuario_nombre, $mensaje, $respuesta, $imagen_url, $texto_adjunto, $idioma]);
        error_log("Consulta guardada correctamente");
    } catch (PDOException $e) {
        error_log("Error al guardar consulta: " . $e->getMessage());
        // No fallamos la respuesta por esto, solo log
    }
    
    sendResponse(true, ['respuesta' => $respuesta]);
}

// ==================== ADMIN: TABLAS COMPLETAS ====================

// Obtener todas las tablas
if ($action === 'admin_tablas' && $method === 'GET') {
    sendResponse(true, ['usuarios', 'respuestas', 'consultas', 'cultivos', 'plagas', 'consejos', 'logs']);
}

// CRUD genérico para tablas
$tablas_permitidas = ['usuarios', 'respuestas', 'cultivos', 'plagas', 'consejos', 'consultas', 'logs'];

if (in_array($action, $tablas_permitidas)) {
    $tabla = $action;
    
    // GET - Obtener todos
    if ($method === 'GET') {
        try {
            $stmt = $db->query("SELECT * FROM $tabla ORDER BY id DESC");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            sendResponse(true, $data);
        } catch (PDOException $e) {
            sendResponse(false, null, $e->getMessage());
        }
    }
    
    // POST - Crear
    if ($method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data) {
            sendResponse(false, null, 'Datos inválidos');
        }
        
        unset($data['id']);
        $columns = implode(", ", array_keys($data));
        $placeholders = ":" . implode(", :", array_keys($data));
        
        try {
            $stmt = $db->prepare("INSERT INTO $tabla ($columns) VALUES ($placeholders)");
            $stmt->execute($data);
            sendResponse(true, ['id' => $db->lastInsertId()], 'Registro creado');
        } catch (PDOException $e) {
            sendResponse(false, null, $e->getMessage());
        }
    }
    
    // PUT - Actualizar
    if ($method === 'PUT') {
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || !isset($data['id'])) {
            sendResponse(false, null, 'ID requerido');
        }
        
        $id = $data['id'];
        unset($data['id']);
        
        $set = "";
        foreach (array_keys($data) as $col) {
            $set .= "$col = :$col, ";
        }
        $set = rtrim($set, ", ");
        
        try {
            $stmt = $db->prepare("UPDATE $tabla SET $set WHERE id = :id");
            $data['id'] = $id;
            $stmt->execute($data);
            sendResponse(true, null, 'Registro actualizado');
        } catch (PDOException $e) {
            sendResponse(false, null, $e->getMessage());
        }
    }
    
    // DELETE - Eliminar
    if ($method === 'DELETE') {
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || !isset($data['id'])) {
            sendResponse(false, null, 'ID requerido');
        }
        
        try {
            $stmt = $db->prepare("DELETE FROM $tabla WHERE id = ?");
            $stmt->execute([$data['id']]);
            sendResponse(true, null, 'Registro eliminado');
        } catch (PDOException $e) {
            sendResponse(false, null, $e->getMessage());
        }
    }
}

// ==================== ESTADÍSTICAS ====================
if ($action === 'estadisticas' && $method === 'GET') {
    try {
        $total_usuarios = $db->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'user'")->fetchColumn();
        $total_respuestas = $db->query("SELECT COUNT(*) FROM respuestas")->fetchColumn();
        $consultas_hoy = $db->query("SELECT COUNT(*) FROM consultas WHERE DATE(fecha) = CURDATE()")->fetchColumn();
        
        sendResponse(true, [
            'total_usuarios' => (int)$total_usuarios,
            'total_respuestas' => (int)$total_respuestas,
            'consultas_hoy' => (int)$consultas_hoy
        ]);
    } catch (PDOException $e) {
        sendResponse(false, null, $e->getMessage());
    }
}

// ==================== HEALTH CHECK ====================
if ($action === 'health' && $method === 'GET') {
    sendResponse(true, [
        'status' => 'ok',
        'database' => $database->testConnection(),
        'php_version' => PHP_VERSION,
        'openai_configured' => !empty(OPENAI_API_KEY) && OPENAI_API_KEY !== ''
    ]);
}

// Si no hay acción válida
sendResponse(false, null, 'Acción no válida: ' . $action, 404);
?>