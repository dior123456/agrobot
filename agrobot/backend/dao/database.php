<?php
/**
 * Database.php - Clase de conexión a MySQL
 * Proyecto AgroBot - Guinea Ecuatorial
 * Versión: 2.0
 */

class Database {
    // Configuración de conexión
    private $host = "localhost";
    private $port = 3306;
    private $db_name = "agrobot";
    private $username = "root";
    private $password = "";
    private $charset = "utf8mb4";
    public $conn = null;
    
    // Opciones de PDO
    private $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];

    /**
     * Constructor - Intenta conectar automáticamente
     */
    public function __construct() {
        $this->connect();
    }

    /**
     * Establecer conexión a la base de datos
     * @return PDO|null
     */
    public function connect() {
        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset={$this->charset}";
            $this->conn = new PDO($dsn, $this->username, $this->password, $this->options);
            return $this->conn;
        } catch (PDOException $e) {
            $this->logError("Error de conexión: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener la conexión activa
     * @return PDO|null
     */
    public function getConnection() {
        if ($this->conn === null) {
            $this->connect();
        }
        return $this->conn;
    }

    /**
     * Verificar si la conexión está activa
     * @return bool
     */
    public function isConnected() {
        try {
            if ($this->conn === null) return false;
            $this->conn->query("SELECT 1");
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Cerrar la conexión
     */
    public function close() {
        $this->conn = null;
    }

    /**
     * Ejecutar una consulta SQL
     * @param string $sql
     * @param array $params
     * @return PDOStatement|false
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $this->logError("Error en query: " . $e->getMessage() . " - SQL: " . $sql);
            return false;
        }
    }

    /**
     * Obtener todos los registros
     * @param string $sql
     * @param array $params
     * @return array
     */
    public function getAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if ($stmt) {
            return $stmt->fetchAll();
        }
        return [];
    }

    /**
     * Obtener un solo registro
     * @param string $sql
     * @param array $params
     * @return array|null
     */
    public function getOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if ($stmt) {
            return $stmt->fetch();
        }
        return null;
    }

    /**
     * Insertar un registro
     * @param string $table
     * @param array $data
     * @return int|false
     */
    public function insert($table, $data) {
        $columns = implode(", ", array_keys($data));
        $placeholders = ":" . implode(", :", array_keys($data));
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        
        $stmt = $this->query($sql, $data);
        if ($stmt) {
            return $this->getConnection()->lastInsertId();
        }
        return false;
    }

    /**
     * Actualizar un registro
     * @param string $table
     * @param array $data
     * @param int $id
     * @return bool
     */
    public function update($table, $data, $id) {
        $set = "";
        foreach (array_keys($data) as $col) {
            $set .= "$col = :$col, ";
        }
        $set = rtrim($set, ", ");
        $sql = "UPDATE $table SET $set WHERE id = :id";
        $data['id'] = $id;
        
        $stmt = $this->query($sql, $data);
        return $stmt !== false;
    }

    /**
     * Eliminar un registro
     * @param string $table
     * @param int $id
     * @return bool
     */
    public function delete($table, $id) {
        $sql = "DELETE FROM $table WHERE id = :id";
        $stmt = $this->query($sql, ['id' => $id]);
        return $stmt !== false;
    }

    /**
     * Obtener el último ID insertado
     * @return int
     */
    public function lastInsertId() {
        return $this->getConnection()->lastInsertId();
    }

    /**
     * Iniciar transacción
     */
    public function beginTransaction() {
        $this->getConnection()->beginTransaction();
    }

    /**
     * Confirmar transacción
     */
    public function commit() {
        $this->getConnection()->commit();
    }

    /**
     * Revertir transacción
     */
    public function rollback() {
        $this->getConnection()->rollBack();
    }

    /**
     * Registrar errores en log
     * @param string $message
     */
    private function logError($message) {
        $logFile = __DIR__ . '/../logs/db_error.log';
        $dir = dirname($logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
    }

    /**
     * Obtener estadísticas de la base de datos
     * @return array
     */
    public function getStats() {
        $stats = [
            'total_usuarios' => 0,
            'total_respuestas' => 0,
            'total_consultas' => 0,
            'consultas_hoy' => 0
        ];
        
        try {
            $stats['total_usuarios'] = (int)$this->getConnection()->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'user'")->fetchColumn();
            $stats['total_respuestas'] = (int)$this->getConnection()->query("SELECT COUNT(*) FROM respuestas")->fetchColumn();
            $stats['total_consultas'] = (int)$this->getConnection()->query("SELECT COUNT(*) FROM consultas")->fetchColumn();
            $stats['consultas_hoy'] = (int)$this->getConnection()->query("SELECT COUNT(*) FROM consultas WHERE DATE(fecha) = CURDATE()")->fetchColumn();
        } catch (PDOException $e) {
            $this->logError("Error getStats: " . $e->getMessage());
        }
        
        return $stats;
    }
}

// Si se ejecuta directamente este archivo, probar conexión
if (basename($_SERVER['PHP_SELF']) == 'Database.php') {
    header('Content-Type: application/json');
    $db = new Database();
    echo json_encode([
        'success' => $db->isConnected(),
        'message' => $db->isConnected() ? 'Conexión exitosa a MySQL' : ' Error de conexión',
        'database' => $db->db_name,
        'host' => $db->host
    ], JSON_PRETTY_PRINT);
}
?>